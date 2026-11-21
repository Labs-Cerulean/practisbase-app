<?php

namespace Tests\Feature;

use App\Models\CompanyExpense;
use App\Models\CompanyJournalEntry;
use App\Models\CompanyJournalLine;
use App\Models\CompanyProfile;
use App\Models\CompanySupplier;
use App\Models\User;
use App\Support\CompanyChartOfAccounts;
use App\Support\CompanyLedger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class CompanyExpenseListSpeedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $this->app->forgetInstance('encrypter');
        $this->forgetLedgerMemos();
        Carbon::setTestNow('2026-11-21');
        $this->createBooksTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->forgetLedgerMemos();
        parent::tearDown();
    }

    public function test_the_expense_list_does_not_wait_on_depreciation_journals(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $other = $this->companyUser(2, 'Someone else');
        $this->profileFor(1);

        $supplier = CompanySupplier::create([
            'user_id' => 1,
            'name' => 'Cyberport SE',
        ]);

        CompanyExpense::create([
            'user_id' => 1,
            'company_supplier_id' => $supplier->id,
            'expense_date' => '2026-10-30',
            'category' => 'computer',
            'description' => 'Cyberport computer parts',
            'amount' => 1553.57,
            'vat_amount' => 279.64,
            'is_reverse_charge' => true,
            'funded_by' => 'company',
        ]);
        CompanyExpense::create([
            'user_id' => 1,
            'company_supplier_id' => $supplier->id,
            'expense_date' => '2026-11-02',
            'category' => 'software',
            'description' => 'Cursor subscription',
            'amount' => 10.00,
            'vat_amount' => 1.80,
            'is_reverse_charge' => false,
            'funded_by' => 'company',
        ]);
        CompanyExpense::create([
            'user_id' => 1,
            'expense_date' => '2025-12-01',
            'category' => 'software',
            'description' => 'Last year hosting',
            'amount' => 40.00,
            'vat_amount' => 0,
            'funded_by' => 'company',
        ]);
        CompanyExpense::create([
            'user_id' => 2,
            'expense_date' => '2026-11-01',
            'category' => 'computer',
            'description' => 'Other company computer',
            'amount' => 900.00,
            'vat_amount' => 0,
            'funded_by' => 'company',
        ]);

        DB::table('company_journal_entries')->insert([
            'user_id' => 1,
            'entry_date' => '2026-10-30',
            'description' => 'Computer wear and tear: Cyberport computer parts',
            'source_type' => 'company_expense_depreciation',
            'source_id' => 1,
            'source_key' => 'company_expense:1:depreciation:0',
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sql = [];
        DB::listen(function ($query) use (&$sql) {
            $sql[] = $query->sql;
        });

        $response = $this->actingAs($user)->get('/company/expenses');

        $response->assertOk();
        $response->assertSee('Cyberport SE');
        $response->assertSee('Cyberport computer parts');
        $response->assertSee('Cursor subscription');
        $response->assertSee('€1,565.37');
        $response->assertSee('€388.39');
        $response->assertSee('30 Oct 2026');
        $response->assertSee('1 Jan 2027');
        $response->assertSee('>2025<', false);
        $response->assertDontSee('Last year hosting');
        $response->assertDontSee('Other company computer');
        $response->assertDontSee('Hidden Vendor');

        $joined = implode("\n", $sql);
        $this->assertStringNotContainsString('company_journal', $joined);
        $this->assertStringNotContainsString('company_gl_accounts', $joined);
        $this->assertStringNotContainsString('company_books_locks', $joined);
        $this->assertSame(1, CompanyJournalEntry::where('user_id', 1)->count());
        $this->assertLessThanOrEqual(6, count($sql), $joined);

        $theirs = $this->actingAs($other)->get('/company/expenses');
        $theirs->assertOk();
        $theirs->assertSee('Other company computer');
        $theirs->assertDontSee('Cyberport computer parts');
        $theirs->assertDontSee('Cursor subscription');
        $this->assertSame(0, CompanyJournalEntry::where('user_id', 2)->count());
        $this->assertSame(1, CompanyJournalEntry::where('user_id', 1)->count());
    }

    public function test_a_posted_computer_is_one_lookup_and_does_not_change_the_figure(): void
    {
        $this->companyUser(1, 'Nicholas');
        $this->profileFor(1);
        $ids = [];
        foreach (['Work PC', 'Spare laptop', 'Bench machine'] as $name) {
            $ids[] = CompanyExpense::create([
                'user_id' => 1,
                'expense_date' => '2026-10-30',
                'category' => 'computer',
                'description' => $name,
                'amount' => 1553.57,
                'vat_amount' => 279.64,
                'is_reverse_charge' => true,
                'funded_by' => 'company',
            ])->id;
        }

        foreach ($ids as $id) {
            DB::table('company_journal_entries')->insert([
                'user_id' => 1,
                'entry_date' => '2026-10-30',
                'description' => 'already posted',
                'source_type' => 'company_expense_depreciation',
                'source_id' => $id,
                'source_key' => 'company_expense:'.$id.':depreciation:0',
                'status' => 'posted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->forgetLedgerMemos();
        $sql = [];
        DB::listen(function ($query) use (&$sql) {
            $sql[] = $query->sql;
        });

        CompanyLedger::syncComputerDepreciation(1);

        $this->assertCount(3, $sql, implode("\n", $sql));
        $joined = implode("\n", $sql);
        $this->assertSame(1, substr_count($joined, 'company_expenses'));
        $this->assertSame(1, substr_count($joined, 'company_profiles'));
        $this->assertSame(1, substr_count($joined, 'company_journal_entries'));
        $this->assertStringNotContainsString('company_gl_accounts', $joined);
        $this->assertStringNotContainsString('company_books_locks', $joined);
        $this->assertStringContainsString('user_id', $joined);
        $this->assertSame(3, CompanyJournalEntry::where('user_id', 1)->count());

        $before = count($sql);
        CompanyLedger::syncComputerDepreciation(1);
        $this->assertCount($before, $sql);
    }

    public function test_a_missing_slice_posts_the_same_wear_and_tear_once(): void
    {
        $this->companyUser(1, 'Nicholas');
        $this->companyUser(2, 'Someone else');
        $this->profileFor(1);

        $expense = CompanyExpense::create([
            'user_id' => 1,
            'expense_date' => '2026-10-30',
            'category' => 'computer',
            'description' => 'Cyberport computer parts',
            'amount' => 1553.57,
            'vat_amount' => 279.64,
            'is_reverse_charge' => true,
            'funded_by' => 'company',
        ]);
        $other = CompanyExpense::create([
            'user_id' => 2,
            'expense_date' => '2026-10-30',
            'category' => 'computer',
            'description' => 'Other company computer',
            'amount' => 800.00,
            'vat_amount' => 0,
            'funded_by' => 'company',
        ]);
        CompanyExpense::create([
            'user_id' => 1,
            'expense_date' => '2026-10-18',
            'category' => 'computer',
            'description' => 'Reversed computer',
            'amount' => 400.00,
            'vat_amount' => 0,
            'funded_by' => 'company',
            'reversed_at' => '2026-10-19',
        ]);

        $this->forgetLedgerMemos();
        CompanyLedger::syncComputerDepreciation(1);

        $entries = CompanyJournalEntry::where('user_id', 1)->orderBy('source_key')->get();
        $this->assertCount(1, $entries);
        $this->assertSame('company_expense:'.$expense->id.':depreciation:0', $entries[0]->source_key);
        $this->assertSame('2026-10-30', $entries[0]->entry_date->format('Y-m-d'));
        $this->assertSame('Computer wear and tear: Cyberport computer parts', $entries[0]->description);
        $this->assertSame(0, CompanyJournalEntry::where('user_id', 2)->count());
        $this->assertNull(
            CompanyJournalEntry::where('source_key', 'company_expense:'.$expense->id.':depreciation:1')->first()
        );
        $this->assertNull(
            CompanyJournalEntry::where('source_key', 'company_expense:'.$other->id.':depreciation:0')->first()
        );

        $lines = CompanyJournalLine::where('user_id', 1)->with('account')->get();
        $this->assertCount(2, $lines);
        $debit = $lines->first(fn (CompanyJournalLine $line) => $line->side === 'debit');
        $credit = $lines->first(fn (CompanyJournalLine $line) => $line->side === 'credit');
        $this->assertSame(388.39, round((float) $debit->amount, 2));
        $this->assertSame(388.39, round((float) $credit->amount, 2));
        $this->assertSame(CompanyChartOfAccounts::DEPRECIATION, $debit->account->account_code);
        $this->assertSame(CompanyChartOfAccounts::COMPUTER_EQUIPMENT, $credit->account->account_code);
        $this->assertSame(1, (int) $debit->user_id);

        $this->forgetLedgerMemos();
        CompanyLedger::syncComputerDepreciation(1);

        $this->assertSame(1, CompanyJournalEntry::where('user_id', 1)->count());
        $this->assertSame(388.39, round((float) CompanyJournalLine::where('user_id', 1)->where('side', 'debit')->value('amount'), 2));
    }

    public function test_a_locked_year_does_not_post_the_slice(): void
    {
        $this->companyUser(1, 'Nicholas');
        $this->profileFor(1);
        CompanyExpense::create([
            'user_id' => 1,
            'expense_date' => '2026-10-30',
            'category' => 'computer',
            'description' => 'Cyberport computer parts',
            'amount' => 1553.57,
            'vat_amount' => 279.64,
            'is_reverse_charge' => true,
            'funded_by' => 'company',
        ]);
        DB::table('company_books_locks')->insert([
            'user_id' => 1,
            'locked_through' => '2026-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->forgetLedgerMemos();
        CompanyLedger::syncComputerDepreciation(1);

        $this->assertSame(0, CompanyJournalEntry::where('user_id', 1)->count());
    }

    private function companyUser(int $id, string $name): User
    {
        DB::table('users')->insert([
            'id' => $id,
            'name' => $name,
            'email' => 'user'.$id.'@example.test',
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

    private function profileFor(int $userId): void
    {
        CompanyProfile::create([
            'user_id' => $userId,
            'legal_name' => 'Cerulean Labs Limited',
            'registration_number' => 'C 116764',
            'registered_office' => 'Flat 6B, Marigold Court, Mosta',
            'financial_year_end_month' => 12,
            'financial_year_end_day' => 31,
            'first_period_start' => '2026-07-29',
            'first_period_end' => '2026-12-31',
            'vat_status' => 'article_10',
            'vat_filing_frequency' => 'quarterly',
            'share_capital_eur' => 1200,
        ]);
    }

    private function createBooksTables(): void
    {
        Schema::dropIfExists('company_journal_lines');
        Schema::dropIfExists('company_journal_entries');
        Schema::dropIfExists('company_books_locks');
        Schema::dropIfExists('company_gl_accounts');
        Schema::dropIfExists('company_expenses');
        Schema::dropIfExists('company_suppliers');
        Schema::dropIfExists('company_expense_payments');
        Schema::dropIfExists('company_profiles');
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

        Schema::create('company_suppliers', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('vat_number')->nullable();
            $table->string('email')->nullable();
            $table->string('country')->nullable();
            $table->text('address')->nullable();
            $table->string('default_category')->nullable();
            $table->timestamps();
        });

        Schema::create('company_expense_payments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('paid_on');
            $table->string('reference')->nullable();
            $table->string('kind')->default('director_refund');
            $table->decimal('amount', 12, 2);
            $table->string('proof_path')->nullable();
            $table->timestamps();
        });

        Schema::create('company_expenses', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_supplier_id')->nullable();
            $table->unsignedBigInteger('company_expense_payment_id')->nullable();
            $table->date('expense_date');
            $table->string('category')->default('software');
            $table->text('description');
            $table->string('supplier_invoice_number')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->boolean('is_reverse_charge')->default(false);
            $table->string('funded_by')->default('company');
            $table->date('director_refunded_at')->nullable();
            $table->string('refund_reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->boolean('is_pre_incorporation')->default(false);
            $table->date('reversed_at')->nullable();
            $table->string('reversal_note')->nullable();
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

    private function forgetLedgerMemos(): void
    {
        $reflection = new ReflectionClass(CompanyLedger::class);
        foreach (['computerDepreciationSynced', 'chartReadyForUser'] as $name) {
            $property = $reflection->getProperty($name);
            $property->setAccessible(true);
            $property->setValue(null, []);
        }
    }
}
