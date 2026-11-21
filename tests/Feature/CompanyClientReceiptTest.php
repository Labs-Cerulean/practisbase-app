<?php

namespace Tests\Feature;

use App\Models\CompanyBooksLock;
use App\Models\CompanyClient;
use App\Models\CompanyGlAccount;
use App\Models\CompanyInvoice;
use App\Models\CompanyJournalLine;
use App\Models\CompanyPayment;
use App\Models\CompanyProfile;
use App\Models\User;
use App\Support\CompanyChartOfAccounts;
use App\Support\CompanyLedger;
use App\Support\FiscalYearGuard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class CompanyClientReceiptTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $this->app->forgetInstance('encrypter');
        Carbon::setTestNow('2026-11-21');
        $this->forgetStatic();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->forgetStatic();
        parent::tearDown();
    }

    public function test_one_client_receipt_converts_the_proformas_it_settles_and_leaves_a_partial_proforma_off_revenue(): void
    {
        $user = $this->user(1);
        $other = $this->user(2);
        $this->profileFor(1);
        $client = $this->client(1, 'Grepor Invest Ltd', 'MT11111111');
        $otherClient = $this->client(1, 'J Portelli Projects', 'MT22222222');
        $foreign = $this->client(2, 'Someone Else', 'MT33333333');

        $october = $this->rfp(1, $client, 'CL-RFP-2026-0016', '2026-10-01', 100);
        $november = $this->rfp(1, $client, 'CL-RFP-2026-0017', '2026-11-01', 80);
        $leftOpen = $this->rfp(1, $client, 'CL-RFP-2026-0018', '2026-11-10', 50);
        $portelli = $this->rfp(1, $otherClient, 'CL-RFP-2026-0015', '2026-10-01', 40);
        $foreignRfp = $this->rfp(2, $foreign, 'CL-RFP-2026-0090', '2026-10-01', 40);

        $page = $this->actingAs($user)->get('/company/clients/'.$client->id);
        $page->assertOk();
        $page->assertSee('Take a receipt');
        $page->assertSee('Record receipt');
        $page->assertSee('Bank transfer');
        $page->assertDontSee('value="cheque"', false);

        $this->actingAs($user)->post('/company/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 200,
            'payment_method' => 'bank_transfer',
            'reference' => 'BOV batch',
        ])->assertRedirect('/company/clients/'.$client->id);

        $this->actingAs($user)->get('/company/clients/'.$client->id)
            ->assertOk()
            ->assertSee('Settled CL-RFP-2026-0016')
            ->assertSee('BOV batch')
            ->assertSee('stays on the proforma');

        $this->assertSame('converted', $october->fresh()->status);
        $this->assertSame('converted', $november->fresh()->status);
        $this->assertSame('partial', $leftOpen->fresh()->status);
        $this->assertEquals(20, (float) $leftOpen->fresh()->amount_paid);
        $this->assertSame('unpaid', $portelli->fresh()->status);
        $this->assertSame('unpaid', $foreignRfp->fresh()->status);
        $this->assertSame(0, CompanyPayment::where('user_id', 2)->count());
        $this->assertSame(0, CompanyPayment::where('company_invoice_id', $october->id)->count());
        $this->assertSame(0, CompanyPayment::where('company_invoice_id', $november->id)->count());
        $this->assertSame(1, CompanyPayment::where('user_id', 1)->where('company_invoice_id', $leftOpen->id)->count());

        $taxInvoices = CompanyInvoice::where('user_id', 1)->where('type', 'invoice')->orderBy('id')->get();
        $this->assertCount(2, $taxInvoices);
        $this->assertEquals(118.0, (float) $taxInvoices[0]->total);
        $this->assertEquals(100.0, (float) $taxInvoices[0]->amount_paid);
        $this->assertEquals(94.4, (float) $taxInvoices[1]->total);
        $this->assertSame('2026-11-21', $taxInvoices[0]->issue_date->toDateString());
        $this->assertSame(2, CompanyPayment::where('user_id', 1)->whereIn('company_invoice_id', $taxInvoices->pluck('id'))->count());

        $revenueId = CompanyGlAccount::where('user_id', 1)->where('account_code', CompanyChartOfAccounts::REVENUE_SAAS)->value('id');
        $revenue = (float) CompanyJournalLine::where('user_id', 1)->where('gl_account_id', $revenueId)->where('side', 'credit')->sum('amount');
        $this->assertSame(180.0, $revenue);

        $this->actingAs($other)->post('/company/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 10,
            'payment_method' => 'bank_transfer',
        ])->assertNotFound();

        $this->actingAs($user)->from('/company/clients/'.$client->id)->post('/company/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-22',
            'amount' => 10,
            'payment_method' => 'bank_transfer',
        ])->assertSessionHasErrors('payment_date');

        $this->actingAs($user)->from('/company/clients/'.$client->id)->post('/company/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 10,
            'payment_method' => 'cheque',
        ])->assertSessionHasErrors('payment_method');
    }

    public function test_a_locked_books_date_blocks_the_receipt_and_a_missing_vat_number_rolls_the_conversion_back(): void
    {
        $user = $this->user(1);
        $this->profileFor(1, null);
        $client = $this->client(1, 'Grepor Invest Ltd', 'MT11111111');
        $rfp = $this->rfp(1, $client, 'CL-RFP-2026-0004', '2026-10-01', 100);

        $this->actingAs($user)->from('/company/clients/'.$client->id)->post('/company/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 100,
            'payment_method' => 'bank_transfer',
        ])->assertSessionHasErrors();

        $this->assertSame('unpaid', $rfp->fresh()->status);
        $this->assertSame(0, CompanyPayment::where('user_id', 1)->count());
        $this->assertSame(0, CompanyInvoice::where('user_id', 1)->where('type', 'invoice')->count());

        CompanyProfile::where('user_id', 1)->update(['vat_number' => 'MT99999999']);
        CompanyBooksLock::create([
            'user_id' => 1,
            'locked_through' => '2026-11-21',
        ]);

        $this->actingAs($user)->from('/company/clients/'.$client->id)->post('/company/clients/'.$client->id.'/receipt', [
            'payment_date' => '2026-11-21',
            'amount' => 100,
            'payment_method' => 'bank_transfer',
        ])->assertSessionHasErrors('books');

        $this->assertSame('unpaid', $rfp->fresh()->status);
        $this->assertSame(0, CompanyPayment::where('user_id', 1)->count());
    }

    public function test_the_company_invoice_list_can_be_searched_filtered_grouped_and_sorted(): void
    {
        $user = $this->user(1);
        $this->profileFor(1);
        $grepor = $this->client(1, 'Grepor Invest Ltd', 'MT11111111');
        $portelli = $this->client(1, 'J Portelli Projects', 'MT22222222');
        $this->rfp(1, $portelli, 'CL-RFP-2026-0015', '2026-11-02', 1180);
        $this->rfp(1, $grepor, 'CL-RFP-2026-0016', '2026-10-01', 590);
        $paid = $this->rfp(1, $grepor, 'CL-RFP-2026-0009', '2026-09-01', 200);
        $paid->update(['amount_paid' => 200, 'status' => 'paid']);

        $grouped = $this->actingAs($user)->get('/company/invoices?group=client&sort=client_asc');
        $grouped->assertOk();
        $html = $grouped->getContent();
        $this->assertStringContainsString('name="q"', $html);
        $this->assertStringContainsString('Group by client', $html);
        $this->assertTrue(strpos($html, 'Grepor Invest Ltd') < strpos($html, 'J Portelli Projects'));

        $search = $this->actingAs($user)->get('/company/invoices?q=0016');
        $search->assertSee('CL-RFP-2026-0016');
        $search->assertDontSee('CL-RFP-2026-0015');

        $clientFilter = $this->actingAs($user)->get('/company/invoices?client_id='.$portelli->id);
        $clientFilter->assertSee('CL-RFP-2026-0015');
        $clientFilter->assertDontSee('CL-RFP-2026-0016');

        $open = $this->actingAs($user)->get('/company/invoices?status=open&client_id='.$grepor->id);
        $open->assertSee('CL-RFP-2026-0016');
        $open->assertDontSee('CL-RFP-2026-0009');

        $value = $this->actingAs($user)->get('/company/invoices?sort=value_desc');
        $body = $value->getContent();
        $this->assertTrue(strpos($body, 'CL-RFP-2026-0015') < strpos($body, 'CL-RFP-2026-0016'));
    }

    private function user(int $id): User
    {
        DB::table('users')->insert([
            'id' => $id,
            'name' => 'User '.$id,
            'email' => 'company'.$id.'@example.test',
            'password' => 'secret',
            'profession' => 'Company',
            'tier' => 'pro',
            'company_books_enabled' => 1,
            'must_verify_email' => 0,
            'terms_accepted_at' => '2026-07-29 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function profileFor(int $userId, ?string $vatNumber = 'MT99999999'): void
    {
        CompanyProfile::create([
            'user_id' => $userId,
            'legal_name' => 'Cerulean Labs Limited',
            'registration_number' => 'C 116764',
            'registered_office' => 'Flat 6B, Marigold Court, Triq Carmelo Schembri, Mosta',
            'financial_year_end_month' => 12,
            'financial_year_end_day' => 31,
            'first_period_start' => '2026-07-29',
            'first_period_end' => '2026-12-31',
            'vat_status' => 'article_10',
            'vat_number' => $vatNumber,
            'vat_filing_frequency' => 'quarterly',
            'share_capital_eur' => 1200,
        ]);
    }

    private function client(int $userId, string $name, string $vat): CompanyClient
    {
        return CompanyClient::create([
            'user_id' => $userId,
            'name' => $name,
            'billing_address' => 'Mosta',
            'vat_number' => $vat,
        ]);
    }

    private function rfp(int $userId, CompanyClient $client, string $number, string $date, float $amount): CompanyInvoice
    {
        return CompanyInvoice::create([
            'user_id' => $userId,
            'company_client_id' => $client->id,
            'type' => 'rfp',
            'document_number' => $number,
            'issue_date' => $date,
            'supply_date' => $date,
            'due_date' => $date,
            'subtotal' => $amount,
            'vat_total' => 0,
            'total' => $amount,
            'amount_paid' => 0,
            'status' => 'unpaid',
            'items' => [['description' => 'Monthly', 'quantity' => 1, 'unit_price' => $amount, 'row_total' => $amount]],
        ]);
    }

    private function createTables(): void
    {
        Schema::dropIfExists('company_journal_lines');
        Schema::dropIfExists('company_journal_entries');
        Schema::dropIfExists('company_payments');
        Schema::dropIfExists('company_invoices');
        Schema::dropIfExists('company_clients');
        Schema::dropIfExists('company_books_locks');
        Schema::dropIfExists('company_gl_accounts');
        Schema::dropIfExists('company_profiles');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('users');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('profession')->nullable();
            $table->string('tier')->nullable();
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

        Schema::create('company_profiles', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('legal_name');
            $table->string('registration_number');
            $table->text('registered_office');
            $table->unsignedSmallInteger('financial_year_end_month')->default(12);
            $table->unsignedSmallInteger('financial_year_end_day')->default(31);
            $table->date('first_period_start');
            $table->date('first_period_end');
            $table->string('vat_status')->default('article_10');
            $table->string('vat_number')->nullable();
            $table->string('vat_filing_frequency')->default('quarterly');
            $table->string('bank_name')->nullable();
            $table->string('bank_iban')->nullable();
            $table->decimal('share_capital_eur', 12, 2)->default(1200);
            $table->date('share_capital_received_at')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('company_clients', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('company_invoices', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_client_id')->nullable();
            $table->unsignedBigInteger('parent_document_id')->nullable();
            $table->unsignedBigInteger('linked_document_id')->nullable();
            $table->unsignedBigInteger('company_recurring_invoice_id')->nullable();
            $table->string('document_number');
            $table->string('type');
            $table->string('status')->default('unpaid');
            $table->date('issue_date');
            $table->date('supply_date')->nullable();
            $table->date('coverage_start')->nullable();
            $table->date('coverage_end')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->text('items')->nullable();
            $table->text('notes')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->timestamps();
        });

        Schema::create('company_payments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_invoice_id');
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_transfer')->default(false);
            $table->string('stripe_invoice_id')->nullable();
            $table->timestamps();
        });

        Schema::create('company_gl_accounts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('account_code');
            $table->string('name');
            $table->string('type');
            $table->string('balance_sheet_category')->nullable();
            $table->string('pl_group')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_books_locks', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->date('locked_through');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('company_journal_entries', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('entry_date');
            $table->string('description');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_key')->nullable();
            $table->string('status')->default('posted');
            $table->timestamps();
        });

        Schema::create('company_journal_lines', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('gl_account_id');
            $table->unsignedBigInteger('company_client_id')->nullable();
            $table->string('side');
            $table->decimal('amount', 14, 2);
            $table->string('memo')->nullable();
            $table->unsignedBigInteger('bank_statement_line_id')->nullable();
            $table->timestamps();
        });
    }

    private function forgetStatic(): void
    {
        $years = (new ReflectionClass(FiscalYearGuard::class))->getProperty('closedYears');
        $years->setAccessible(true);
        $years->setValue(null, []);

        $ledger = new ReflectionClass(CompanyLedger::class);
        foreach (['computerDepreciationSynced', 'chartReadyForUser'] as $name) {
            if (! $ledger->hasProperty($name)) {
                continue;
            }
            $property = $ledger->getProperty($name);
            $property->setAccessible(true);
            $property->setValue(null, []);
        }
    }
}
