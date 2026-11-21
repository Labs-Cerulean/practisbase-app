<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\FiscalYearGuard;
use App\Support\FiscalYearTotals;
use App\Support\ReceiptSplit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class ClientReceiptTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $this->app->forgetInstance('encrypter');
        Carbon::setTestNow('2026-11-21');
        $this->flushClosedYears();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->flushClosedYears();
        parent::tearDown();
    }

    public function test_one_receipt_settles_open_documents_and_converts_only_the_proformas_it_covers(): void
    {
        $user = $this->user(1);
        $other = $this->user(2);
        $client = $this->client($user, 'Harbour Ltd');
        $stranger = $this->client($user, 'Beacon Co');
        $foreign = $this->client($other, 'Other Client');

        $invoice = $this->document($user, $client, [
            'invoice_number' => 'INV-2026-0001',
            'type' => 'invoice',
            'issue_date' => '2026-09-01',
            'total' => 30,
            'subtotal' => 30,
        ]);
        $firstRfp = $this->document($user, $client, [
            'invoice_number' => 'RFP-2026-0002',
            'type' => 'rfp',
            'issue_date' => '2026-10-01',
            'total' => 50,
            'subtotal' => 50,
        ]);
        $secondRfp = $this->document($user, $client, [
            'invoice_number' => 'RFP-2026-0003',
            'type' => 'rfp',
            'issue_date' => '2026-10-15',
            'total' => 80,
            'subtotal' => 80,
        ]);
        $otherClientRfp = $this->document($user, $stranger, [
            'invoice_number' => 'RFP-2026-0099',
            'type' => 'rfp',
            'issue_date' => '2026-08-01',
            'total' => 40,
            'subtotal' => 40,
        ]);
        $foreignRfp = $this->document($other, $foreign, [
            'invoice_number' => 'RFP-2026-0088',
            'type' => 'rfp',
            'issue_date' => '2026-08-01',
            'total' => 70,
            'subtotal' => 70,
        ]);

        $before = FiscalYearTotals::forUserYear(1, 2026);
        $this->assertSame(30.0, $before['net_total']);

        $page = $this->actingAs($user)->get('/clients/'.$client->id);
        $page->assertOk();
        $page->assertSee('Take a receipt');
        $page->assertSee('Record receipt');
        $page->assertSee('€0 for tax');
        $page->assertDontSee('cheque');

        $partial = $this->actingAs($user)->post('/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 80,
            'reference' => 'BOV 441',
        ]);
        $partial->assertRedirect('/clients/'.$client->id);
        $partial->assertSessionHas('success');
        $this->actingAs($user)->get('/clients/'.$client->id)
            ->assertOk()
            ->assertSee('Settled RFP-2026-0002')
            ->assertSee('BOV 441');

        $invoice->refresh();
        $firstRfp->refresh();
        $secondRfp->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertEquals(30, (float) $invoice->amount_paid);
        $this->assertSame('unpaid', $secondRfp->status);
        $this->assertEquals(0, (float) $secondRfp->amount_paid);
        $this->assertSame(1, Invoice::where('user_id', 1)->where('type', 'invoice')->where('parent_document_id', $firstRfp->id)->count());
        $converted = Invoice::where('user_id', 1)->where('type', 'invoice')->where('parent_document_id', $firstRfp->id)->first();
        $this->assertNotNull($converted);
        $this->assertSame('2026-11-21', $converted->issue_date->toDateString());
        $this->assertEquals(50, (float) $converted->total);
        $this->assertEquals(50, (float) $converted->amount_paid);
        $this->assertSame(0, Payment::where('user_id', 1)->where('invoice_id', $firstRfp->id)->count());
        $this->assertSame(1, Payment::where('user_id', 1)->where('invoice_id', $converted->id)->count());
        $this->assertSame(0, Payment::where('user_id', 1)->where('invoice_id', $secondRfp->id)->count());
        $this->assertStringContainsString('BOV 441', (string) Payment::where('user_id', 1)->where('invoice_id', $converted->id)->value('notes'));
        $this->assertSame('unpaid', $otherClientRfp->fresh()->status);
        $this->assertSame('unpaid', $foreignRfp->fresh()->status);
        $this->assertSame(0, Payment::where('user_id', 2)->count());

        $afterPartial = FiscalYearTotals::forUserYear(1, 2026);
        $this->assertSame(80.0, $afterPartial['net_total']);
        $this->assertSame(0.0, FiscalYearTotals::forUserYear(2, 2026)['net_total']);

        $rest = $this->actingAs($user)->post('/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 80,
        ]);
        $rest->assertRedirect('/clients/'.$client->id);
        $secondRfp->refresh();
        $this->assertSame('converted', $secondRfp->status);
        $this->assertSame(0, Payment::where('user_id', 1)->where('invoice_id', $secondRfp->id)->count());
        $this->assertSame(160.0, FiscalYearTotals::forUserYear(1, 2026)['net_total']);
        $this->assertSame('unpaid', $otherClientRfp->fresh()->status);
    }

    public function test_a_partial_proforma_receipt_stays_off_the_tax_total(): void
    {
        $user = $this->user(1);
        $client = $this->client($user, 'Harbour Ltd');
        $rfp = $this->document($user, $client, [
            'invoice_number' => 'RFP-2026-0007',
            'type' => 'rfp',
            'issue_date' => '2026-10-01',
            'total' => 100,
            'subtotal' => 100,
        ]);

        $this->actingAs($user)->post('/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 40,
        ])->assertRedirect('/clients/'.$client->id);

        $rfp->refresh();
        $this->assertSame('partially_paid', $rfp->status);
        $this->assertEquals(40, (float) $rfp->amount_paid);
        $this->assertSame(0, Invoice::where('user_id', 1)->where('type', 'invoice')->count());
        $this->assertSame(0.0, FiscalYearTotals::forUserYear(1, 2026)['net_total']);
        $this->assertSame(1, Payment::where('user_id', 1)->where('invoice_id', $rfp->id)->where('is_transfer', false)->count());
    }

    public function test_a_future_date_a_locked_year_and_another_user_cannot_record_the_receipt(): void
    {
        $user = $this->user(1);
        $other = $this->user(2);
        $client = $this->client($user, 'Harbour Ltd');
        $open = $this->document($user, $client, [
            'invoice_number' => 'RFP-2026-0004',
            'type' => 'rfp',
            'issue_date' => '2026-10-01',
            'total' => 25,
            'subtotal' => 25,
        ]);
        $lockedDoc = $this->document($user, $client, [
            'invoice_number' => 'INV-2025-0001',
            'type' => 'invoice',
            'issue_date' => '2025-12-01',
            'total' => 15,
            'subtotal' => 15,
        ]);
        DB::table('fiscal_years')->insert([
            'user_id' => 1,
            'year' => 2025,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->flushClosedYears();

        $this->actingAs($user)->from('/clients/'.$client->id)->post('/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-22',
            'amount' => 25,
        ])->assertSessionHasErrors('payment_date');

        $this->actingAs($user)->from('/clients/'.$client->id)->post('/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 40,
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, Payment::where('user_id', 1)->count());
        $this->assertEquals(0, (float) $lockedDoc->fresh()->amount_paid);
        $this->assertSame('unpaid', $open->fresh()->status);

        $this->actingAs($user)->post('/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 25,
        ])->assertRedirect('/clients/'.$client->id);

        $this->assertSame('converted', $open->fresh()->status);
        $this->assertEquals(0, (float) $lockedDoc->fresh()->amount_paid);
        $this->assertSame(0, Payment::where('invoice_id', $lockedDoc->id)->count());

        DB::table('fiscal_years')->insert([
            'user_id' => 1,
            'year' => 2026,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->flushClosedYears();
        $again = $this->document($user, $client, [
            'invoice_number' => 'RFP-2026-0005',
            'type' => 'rfp',
            'issue_date' => '2026-11-01',
            'total' => 10,
            'subtotal' => 10,
        ]);
        $this->actingAs($user)->from('/clients/'.$client->id)->post('/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 10,
        ])->assertSessionHasErrors('fiscal_error');
        $this->assertSame('unpaid', $again->fresh()->status);

        $this->actingAs($other)->post('/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 10,
        ])->assertForbidden();
    }

    public function test_the_invoice_list_can_be_searched_filtered_grouped_and_sorted(): void
    {
        $user = $this->user(1);
        $alpha = $this->client($user, 'Alpha Studio');
        $zeta = $this->client($user, 'Zeta Works');
        $this->document($user, $zeta, [
            'invoice_number' => 'INV-2026-0100',
            'type' => 'invoice',
            'issue_date' => '2026-11-01',
            'total' => 10,
            'subtotal' => 10,
            'notes' => 'retainer',
        ]);
        $this->document($user, $alpha, [
            'invoice_number' => 'RFP-2026-0101',
            'type' => 'rfp',
            'issue_date' => '2026-10-01',
            'total' => 90,
            'subtotal' => 90,
            'notes' => 'october care',
        ]);
        $paid = $this->document($user, $alpha, [
            'invoice_number' => 'INV-2026-0102',
            'type' => 'invoice',
            'issue_date' => '2026-09-01',
            'total' => 5,
            'subtotal' => 5,
            'amount_paid' => 5,
            'status' => 'paid',
        ]);

        $grouped = $this->actingAs($user)->get('/ledger?group=client&sort=client_asc');
        $grouped->assertOk();
        $html = $grouped->getContent();
        $this->assertStringContainsString('<h2', $html);
        $this->assertTrue(strpos($html, 'Alpha Studio') < strpos($html, 'Zeta Works'));
        $this->assertStringContainsString('name="q"', $html);
        $this->assertStringContainsString('Group by client', $html);

        $search = $this->actingAs($user)->get('/ledger?q=october');
        $search->assertOk();
        $search->assertSee('RFP-2026-0101');
        $search->assertDontSee('INV-2026-0100');

        $byClient = $this->actingAs($user)->get('/ledger?client_id='.$zeta->id);
        $byClient->assertSee('INV-2026-0100');
        $byClient->assertDontSee('RFP-2026-0101');

        $rfps = $this->actingAs($user)->get('/ledger?doc_type=rfp');
        $rfps->assertSee('RFP-2026-0101');
        $rfps->assertDontSee('INV-2026-0100');

        $open = $this->actingAs($user)->get('/ledger?status=open&client_id='.$alpha->id);
        $open->assertSee('RFP-2026-0101');
        $open->assertDontSee($paid->invoice_number);

        $value = $this->actingAs($user)->get('/ledger?sort=value_desc&client_id='.$alpha->id);
        $body = $value->getContent();
        $this->assertTrue(strpos($body, 'RFP-2026-0101') < strpos($body, 'INV-2026-0102'));

        $this->assertSame(
            [['id' => 2, 'amount' => 10.0, 'settles' => true], ['id' => 9, 'amount' => 5.0, 'settles' => false]],
            ReceiptSplit::apply([
                ['id' => 2, 'due' => 10],
                ['id' => 9, 'due' => 20],
            ], 15)
        );
    }

    private function user(int $id): User
    {
        DB::table('users')->insert([
            'id' => $id,
            'name' => 'User '.$id,
            'email' => 'user'.$id.'@example.test',
            'password' => 'secret',
            'profession' => 'Accountant',
            'tier' => 'standard',
            'vat_status' => 'article_11',
            'company_books_enabled' => 0,
            'must_verify_email' => 0,
            'terms_accepted_at' => '2026-01-01 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function client(User $user, string $name): Client
    {
        return Client::create([
            'user_id' => $user->id,
            'type' => 'company',
            'name' => $name,
        ]);
    }

    private function document(User $user, Client $client, array $overrides): Invoice
    {
        return Invoice::create(array_merge([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-2026-0001',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'subtotal' => 10,
            'vat_total' => 0,
            'total' => 10,
            'amount_paid' => 0,
            'status' => 'unpaid',
            'type' => 'invoice',
            'items' => [['description' => 'Work', 'quantity' => 1, 'unit_price' => 10, 'row_total' => 10]],
        ], $overrides));
    }

    private function createTables(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('user_regime_segments');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('users');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('profession')->nullable();
            $table->string('tier')->nullable();
            $table->string('vat_status')->nullable();
            $table->boolean('company_books_enabled')->default(false);
            $table->boolean('must_verify_email')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fiscal_years', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('year');
            $table->timestamps();
        });

        Schema::create('user_regime_segments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('effective_from');
            $table->string('vat_status')->nullable();
            $table->timestamps();
        });

        Schema::create('clients', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type')->default('individual');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('profile_data')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('parent_document_id')->nullable();
            $table->string('invoice_number');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->string('type')->default('invoice');
            $table->unsignedBigInteger('linked_document_id')->nullable();
            $table->text('items')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_transfer')->default(false);
            $table->timestamps();
        });
    }

    private function flushClosedYears(): void
    {
        $years = (new ReflectionClass(FiscalYearGuard::class))->getProperty('closedYears');
        $years->setAccessible(true);
        $years->setValue(null, []);
    }
}
