<?php

namespace Tests\Feature;

use App\Models\CompanyBooksLock;
use App\Models\CompanyGlAccount;
use App\Models\CompanyJournalEntry;
use App\Models\CompanyJournalLine;
use App\Models\CompanyProfile;
use App\Models\TaxRate;
use App\Models\User;
use App\Support\CompanyBooks;
use App\Support\CompanyCorporateTax;
use App\Support\CompanyLedger;
use App\Support\FiscalYearGuard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class CompanyCorporateTaxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $this->app->forgetInstance('encrypter');
        Carbon::setTestNow('2026-11-21');
        $this->forgetGuards();
        $this->createBooksTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->forgetGuards();
        parent::tearDown();
    }

    public function test_profit_posts_35_percent_to_7000_and_2500_and_the_screens_show_the_receipt(): void
    {
        $user = $this->companyUser(1);
        $this->profileFor(1);
        $this->postJournal(1, '2026-10-30', '1100', '4000', 1000, 'revenue');
        $this->postJournal(1, '2026-11-02', '6000', '1000', 200, 'software');
        $this->postJournal(1, '2026-06-01', '6000', '1000', 50, 'before-incorporation');

        $tax = CompanyCorporateTax::sync(1);

        $this->assertSame(800.0, $tax['profit_before_tax']);
        $this->assertSame(800.0, $tax['taxable_profit']);
        $this->assertSame(280.0, $tax['liability']);
        $this->assertSame(280.0, $tax['posted_amount']);
        $this->assertSame('2026-11-21', $tax['posted_date']);
        $this->assertSame('statutory', $tax['rate_source']);
        $this->assertTrue($tax['assumed']);
        $this->assertSame('2026-07-29', $tax['period_from']);
        $this->assertSame('2026-12-31', $tax['period_to']);
        $this->assertStringContainsString('35%', $tax['assumption']);
        $this->assertStringContainsString('TA22', $tax['assumption']);
        $this->assertStringContainsString('max(0, €800.00 × 35%) = €280.00', $tax['working']);
        $this->assertFalse($tax['blocked']);

        $entry = CompanyJournalEntry::where('user_id', 1)->where('source_type', 'company_tax')->first();
        $this->assertNotNull($entry);
        $this->assertSame('2026-11-21', $entry->entry_date->format('Y-m-d'));
        $this->assertSame(1, CompanyJournalEntry::where('user_id', 1)->where('source_type', 'company_tax')->count());
        $lines = CompanyJournalLine::where('user_id', 1)->where('journal_entry_id', $entry->id)->get();
        $this->assertCount(2, $lines);
        $codes = [];
        foreach ($lines as $line) {
            $code = CompanyGlAccount::where('user_id', 1)->where('id', $line->gl_account_id)->value('account_code');
            $codes[$line->side] = $code;
            $this->assertSame('280.00', number_format((float) $line->amount, 2, '.', ''));
            $this->assertSame(1, (int) $line->user_id);
        }
        $this->assertSame('7000', $codes['debit']);
        $this->assertSame('2500', $codes['credit']);

        $pl = CompanyLedger::profitAndLoss(1, '2026-07-29', '2026-11-21');
        $this->assertSame(800.0, $pl['profit_before_tax']);
        $this->assertSame(280.0, $pl['tax_total']);
        $this->assertSame(520.0, $pl['net_profit']);
        $this->assertNotSame($pl['profit_before_tax'], $pl['net_profit']);

        $sheet = CompanyLedger::balanceSheet(1, '2026-11-21', '2026-07-29', $pl);
        $this->assertTrue($sheet['balanced']);

        $accounts = $this->actingAs($user)->get('/company/accounts');
        $accounts->assertOk();
        $accounts->assertSee('Corporate tax');
        $accounts->assertSee('Receipt of the math');
        $accounts->assertSee('Profit before tax');
        $accounts->assertSee('800.00');
        $accounts->assertSee('280.00');
        $accounts->assertSee('520.00');
        $accounts->assertSee('7000');
        $accounts->assertSee('2500');
        $accounts->assertSee('Statutory Maltese corporate tax of 35%');
        $accounts->assertSee('Sole-trader TA22 and personal brackets are not applied');
        $accounts->assertSee('Nothing is filed from this screen');
        $accounts->assertSee('max(0, €800.00 × 35%) = €280.00');
        $accounts->assertDontSee('MT12345678');

        $compliance = $this->actingAs($user)->get('/company/compliance');
        $compliance->assertOk();
        $compliance->assertSee('Income tax return · FY 2026');
        $compliance->assertSee('Liability €280.00');
        $compliance->assertSee('Nothing is filed from this screen');
        $compliance->assertSee('1 August 2026');
        $compliance->assertSee('31 August 2027');
        $compliance->assertSee('15 October 2027');
        $compliance->assertSee('29–31 July 2026 sit outside it');
        $compliance->assertDontSee('Q3 2026');
        $compliance->assertDontSee('15 Nov');
        $compliance->assertDontSee('MT12345678');
        $this->assertNull(CompanyProfile::where('user_id', 1)->value('vat_number'));
        $this->assertSame(CompanyBooks::VAT_FIRST_PERIOD_START, '2026-08-01');
        $this->assertSame(CompanyBooks::VAT_FIRST_PERIOD_END, '2027-08-31');
        $this->assertSame(CompanyBooks::VAT_FIRST_PERIOD_DUE, '2027-10-15');
    }

    public function test_a_loss_posts_nothing_and_a_later_profit_can_replace_the_provision(): void
    {
        $this->companyUser(1);
        $this->profileFor(1);
        $this->postJournal(1, '2026-11-02', '6000', '1000', 200, 'software');

        $loss = CompanyCorporateTax::sync(1);
        $this->assertSame(-200.0, $loss['profit_before_tax']);
        $this->assertSame(0.0, $loss['liability']);
        $this->assertSame(0.0, $loss['posted_amount']);
        $this->assertNull(CompanyJournalEntry::where('user_id', 1)->where('source_type', 'company_tax')->first());

        $this->postJournal(1, '2026-11-03', '1100', '4000', 1000, 'revenue');
        $profit = CompanyCorporateTax::sync(1);
        $this->assertSame(280.0, $profit['liability']);
        $this->assertSame(1, CompanyJournalEntry::where('user_id', 1)->where('source_type', 'company_tax')->count());

        $this->postJournal(1, '2026-11-04', '6000', '1000', 2000, 'more-software');
        $again = CompanyCorporateTax::sync(1);
        $this->assertSame(0.0, $again['liability']);
        $this->assertSame(0.0, $again['posted_amount']);
        $this->assertNull(CompanyJournalEntry::where('user_id', 1)->where('source_type', 'company_tax')->first());
    }

    public function test_the_provision_follows_profit_and_ignores_another_users_books(): void
    {
        $this->companyUser(1);
        $this->companyUser(2);
        $this->profileFor(1);
        $this->profileFor(2);
        $this->postJournal(1, '2026-10-30', '1100', '4000', 1000, 'u1-revenue');
        $this->postJournal(2, '2026-10-30', '1100', '4000', 5000, 'u2-revenue');

        $first = CompanyCorporateTax::sync(1);
        $this->assertSame(350.0, $first['liability']);

        $this->postJournal(1, '2026-11-02', '6000', '1000', 200, 'u1-software');
        $second = CompanyCorporateTax::sync(1);
        $this->assertSame(280.0, $second['liability']);
        $this->assertSame(280.0, $second['posted_amount']);
        $this->assertSame(1, CompanyJournalEntry::where('user_id', 1)->where('source_type', 'company_tax')->count());

        $other = CompanyCorporateTax::sync(2);
        $this->assertSame(1750.0, $other['liability']);
        $this->assertSame(1, CompanyJournalEntry::where('user_id', 2)->where('source_type', 'company_tax')->count());
        $this->assertSame(280.0, CompanyCorporateTax::sync(1)['liability']);
    }

    public function test_personal_brackets_are_ignored_and_a_stored_company_rate_is_used(): void
    {
        $this->companyUser(1);
        $this->profileFor(1);
        $this->postJournal(1, '2026-10-30', '1100', '4000', 1000, 'revenue');
        TaxRate::create([
            'year' => 2026,
            'type' => 'income_single',
            'rates_json' => [
                ['min' => 0, 'max' => 9999999, 'rate' => 0.15, 'subtract' => 0],
            ],
        ]);
        TaxRate::create([
            'year' => 2026,
            'type' => 'ta22',
            'rates_json' => ['rate' => 0.10, 'max_limit' => 12000],
        ]);

        $statutory = CompanyCorporateTax::sync(1);
        $this->assertSame(350.0, $statutory['liability']);
        $this->assertSame('statutory', $statutory['rate_source']);

        TaxRate::create([
            'year' => 2026,
            'type' => 'company',
            'rates_json' => ['rate' => 0.25],
        ]);
        $stored = CompanyCorporateTax::sync(1);
        $this->assertSame(250.0, $stored['liability']);
        $this->assertSame('stored', $stored['rate_source']);
        $this->assertFalse($stored['assumed']);
        $this->assertSame(250.0, $this->provisionAmount(1));
    }

    public function test_a_missing_year_falls_back_to_the_previous_company_rate(): void
    {
        $this->companyUser(1);
        $this->profileFor(1);
        $this->postJournal(1, '2026-10-30', '1100', '4000', 1000, 'revenue');
        TaxRate::create([
            'year' => 2025,
            'type' => 'company',
            'rates_json' => ['rate' => 0.30],
        ]);

        $tax = CompanyCorporateTax::sync(1);

        $this->assertSame(300.0, $tax['liability']);
        $this->assertSame('prior_year', $tax['rate_source']);
        $this->assertSame(2025, $tax['rate_year']);
        $this->assertStringContainsString('2025', $tax['assumption']);
        $this->assertStringContainsString('30%', $tax['assumption']);
    }

    public function test_a_company_row_that_is_not_a_flat_rate_stays_on_the_statutory_35_percent(): void
    {
        $this->companyUser(1);
        $this->profileFor(1);
        $this->postJournal(1, '2026-10-30', '1100', '4000', 1000, 'revenue');
        TaxRate::create([
            'year' => 2026,
            'type' => 'company',
            'rates_json' => [
                ['min' => 0, 'max' => 9999999, 'rate' => 0.10, 'subtract' => 0],
            ],
        ]);

        $tax = CompanyCorporateTax::sync(1);

        $this->assertSame(350.0, $tax['liability']);
        $this->assertSame('statutory', $tax['rate_source']);
        $this->assertStringContainsString('not a flat rate', $tax['assumption']);
    }

    public function test_a_locked_year_is_not_rewritten(): void
    {
        $this->companyUser(1);
        $this->profileFor(1);
        $this->postJournal(1, '2026-10-30', '1100', '4000', 1000, 'revenue');
        $this->assertSame(350.0, CompanyCorporateTax::sync(1)['liability']);

        $this->closeYear(1, 2026);
        $this->forgetGuards();
        $this->postJournal(1, '2026-11-01', '1100', '4000', 1000, 'more-revenue');

        $tax = CompanyCorporateTax::sync(1);

        $this->assertSame(700.0, $tax['liability']);
        $this->assertSame(350.0, $tax['posted_amount']);
        $this->assertTrue($tax['blocked']);
        $this->assertSame(350.0, $this->provisionAmount(1));
        $this->assertSame(1, CompanyJournalEntry::where('user_id', 1)->where('source_type', 'company_tax')->count());
    }

    public function test_a_books_lock_blocks_the_first_post(): void
    {
        $this->companyUser(1);
        $this->profileFor(1);
        $this->postJournal(1, '2026-10-30', '1100', '4000', 1000, 'revenue');
        CompanyBooksLock::create([
            'user_id' => 1,
            'locked_through' => '2026-11-21',
            'note' => 'Closed',
        ]);

        $tax = CompanyCorporateTax::sync(1);

        $this->assertSame(350.0, $tax['liability']);
        $this->assertSame(0.0, $tax['posted_amount']);
        $this->assertTrue($tax['blocked']);
        $this->assertNull(CompanyJournalEntry::where('user_id', 1)->where('source_type', 'company_tax')->first());
    }

    public function test_after_the_period_ends_the_provision_moves_to_the_period_end(): void
    {
        $this->companyUser(1);
        $this->profileFor(1);
        $this->postJournal(1, '2026-10-30', '1100', '4000', 1000, 'revenue');
        $this->assertSame('2026-11-21', CompanyCorporateTax::sync(1)['posted_date']);

        Carbon::setTestNow('2027-01-15');
        $current = CompanyCorporateTax::sync(1);

        $this->assertSame(2027, $current['period_year']);
        $this->assertSame(0.0, $current['liability']);
        $closed = CompanyJournalEntry::where('user_id', 1)
            ->where('source_key', 'company_tax:2026-12-31:provision')
            ->first();
        $this->assertNotNull($closed);
        $this->assertSame('2026-12-31', $closed->entry_date->format('Y-m-d'));
        $this->assertSame(350.0, $this->provisionAmount(1));
        $this->assertNull(CompanyJournalEntry::where('user_id', 1)->where('source_key', 'company_tax:2027-12-31:provision')->first());
    }

    private function provisionAmount(int $userId): float
    {
        $entry = CompanyJournalEntry::where('user_id', $userId)->where('source_type', 'company_tax')->first();
        if (! $entry) {
            return 0.0;
        }
        $line = CompanyJournalLine::where('user_id', $userId)
            ->where('journal_entry_id', $entry->id)
            ->where('side', 'debit')
            ->first();

        return round((float) ($line->amount ?? 0), 2);
    }

    private function postJournal(int $userId, string $date, string $debit, string $credit, float $amount, string $key): void
    {
        CompanyLedger::ensureChart(User::findOrFail($userId));
        CompanyLedger::post($userId, $date, $key, [
            ['account_code' => $debit, 'side' => 'debit', 'amount' => $amount, 'memo' => $key],
            ['account_code' => $credit, 'side' => 'credit', 'amount' => $amount, 'memo' => $key],
        ], 'test', null, 'test:'.$userId.':'.$key);
    }

    private function companyUser(int $id): User
    {
        DB::table('users')->insert([
            'id' => $id,
            'name' => 'Nicholas',
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
            'registered_office' => 'Flat 6B, Marigold Court, Triq Carmelo Schembri, Mosta',
            'financial_year_end_month' => 12,
            'financial_year_end_day' => 31,
            'first_period_start' => '2026-07-29',
            'first_period_end' => '2026-12-31',
            'vat_status' => 'article_10',
            'vat_filing_frequency' => 'quarterly',
            'share_capital_eur' => 1200,
        ]);
    }

    private function closeYear(int $userId, int $year): void
    {
        DB::table('fiscal_years')->insert([
            'user_id' => $userId,
            'year' => $year,
            'closed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createBooksTables(): void
    {
        Schema::dropIfExists('company_journal_lines');
        Schema::dropIfExists('company_journal_entries');
        Schema::dropIfExists('company_books_locks');
        Schema::dropIfExists('company_gl_accounts');
        Schema::dropIfExists('company_invoices');
        Schema::dropIfExists('company_expenses');
        Schema::dropIfExists('company_profiles');
        Schema::dropIfExists('tax_rates');
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
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_rates', function ($table) {
            $table->id();
            $table->integer('year');
            $table->string('type');
            $table->json('rates_json');
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

        Schema::create('company_expenses', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('expense_date');
            $table->string('category')->default('software');
            $table->text('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->boolean('is_reverse_charge')->default(false);
            $table->boolean('is_pre_incorporation')->default(false);
            $table->date('reversed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('company_invoices', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('status')->default('issued');
            $table->date('issue_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
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

    private function forgetGuards(): void
    {
        $years = (new ReflectionClass(FiscalYearGuard::class))->getProperty('closedYears');
        $years->setAccessible(true);
        $years->setValue(null, []);

        $ledger = new ReflectionClass(CompanyLedger::class);
        foreach (['computerDepreciationSynced', 'chartReadyForUser'] as $name) {
            $property = $ledger->getProperty($name);
            $property->setAccessible(true);
            $property->setValue(null, []);
        }
    }
}
