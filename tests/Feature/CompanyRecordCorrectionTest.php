<?php

namespace Tests\Feature;

use App\Models\CompanyBankStatementLine;
use App\Models\CompanyBooksLock;
use App\Models\CompanyExpense;
use App\Models\CompanyJournalEntry;
use App\Models\CompanyJournalLine;
use App\Models\CompanyProfile;
use App\Models\CompanySupplier;
use App\Models\User;
use App\Support\CompanyLedger;
use App\Support\FiscalYearGuard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class CompanyRecordCorrectionTest extends TestCase
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

    public function test_the_desk_shows_a_correction_for_each_record(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $this->profileFor(1);
        $supplier = CompanySupplier::create([
            'user_id' => 1,
            'name' => 'Cyberport SE',
            'vat_number' => 'DE812345678',
        ]);
        CompanyExpense::create([
            'user_id' => 1,
            'company_supplier_id' => $supplier->id,
            'expense_date' => '2026-11-02',
            'category' => 'software',
            'description' => 'Cursor subscription',
            'amount' => 10,
            'vat_amount' => 1.80,
            'funded_by' => 'company',
        ]);
        CompanyBankStatementLine::create([
            'user_id' => 1,
            'statement_date' => '2026-11-02',
            'description' => 'CURSOR SUBSCRIPTION',
            'amount' => -11.80,
            'status' => 'unreconciled',
        ]);

        $this->actingAs($user)->get('/company/suppliers')
            ->assertOk()
            ->assertSee('Save name')
            ->assertSee('Cyberport SE')
            ->assertSee('DE812345678');

        $this->actingAs($user)->get('/company/expenses')
            ->assertOk()
            ->assertSee('Save correction')
            ->assertSee('Cursor subscription')
            ->assertSee('A locked year cannot be changed.');

        $this->actingAs($user)->get('/company/bank')
            ->assertOk()
            ->assertSee('Save line')
            ->assertSee('Narrative')
            ->assertSee('CURSOR SUBSCRIPTION');
    }

    public function test_a_supplier_name_can_be_corrected_for_this_user_only(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $other = $this->companyUser(2, 'Someone else');
        $this->profileFor(1);
        $supplier = CompanySupplier::create([
            'user_id' => 1,
            'name' => 'Googel',
            'vat_number' => 'IE6388047V',
        ]);
        $taken = CompanySupplier::create([
            'user_id' => 1,
            'name' => 'Google',
        ]);
        $theirs = CompanySupplier::create([
            'user_id' => 2,
            'name' => 'Googel',
        ]);

        $this->actingAs($other)->post('/company/suppliers/'.$supplier->id.'/name', [
            'name' => 'Stolen name',
        ])->assertNotFound();

        $this->assertSame('Googel', $supplier->fresh()->name);
        $this->assertSame('IE6388047V', $supplier->fresh()->vat_number);
        $this->assertSame('Googel', $theirs->fresh()->name);

        $this->actingAs($user)->from('/company/suppliers')->post('/company/suppliers/'.$supplier->id.'/name', [
            'name' => 'Google',
        ])->assertRedirect('/company/suppliers')->assertSessionHasErrors('name');
        $this->assertSame('Googel', $supplier->fresh()->name);
        $this->assertSame('Google', $taken->fresh()->name);

        $this->actingAs($user)->post('/company/suppliers/'.$supplier->id.'/name', [
            'name' => '  Google   Ireland  ',
        ])->assertRedirect('/company/suppliers');

        $supplier->refresh();
        $this->assertSame('Google Ireland', $supplier->name);
        $this->assertSame('IE6388047V', $supplier->vat_number);
        $this->assertSame('Googel', $theirs->fresh()->name);
    }

    public function test_a_supplier_with_no_dated_expense_can_be_renamed_while_a_year_is_closed(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $this->profileFor(1);
        $this->closeYear(1, 2026);
        $supplier = CompanySupplier::create([
            'user_id' => 1,
            'name' => 'Railway',
        ]);

        $this->actingAs($user)->post('/company/suppliers/'.$supplier->id.'/name', [
            'name' => 'Railway Corp',
        ])->assertRedirect('/company/suppliers');

        $this->assertSame('Railway Corp', $supplier->fresh()->name);
    }

    public function test_a_supplier_rename_is_blocked_when_a_linked_expense_year_is_locked(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $this->profileFor(1);
        $supplier = CompanySupplier::create([
            'user_id' => 1,
            'name' => 'Railway',
        ]);
        CompanyExpense::create([
            'user_id' => 1,
            'company_supplier_id' => $supplier->id,
            'expense_date' => '2026-10-18',
            'category' => 'software',
            'description' => 'Hosting',
            'amount' => 20,
            'vat_amount' => 0,
            'funded_by' => 'company',
        ]);
        $this->closeYear(1, 2026);

        $this->actingAs($user)->from('/company/suppliers')->post('/company/suppliers/'.$supplier->id.'/name', [
            'name' => 'Railway Corp',
        ])->assertRedirect('/company/suppliers')->assertSessionHasErrors([
            'books' => 'Fiscal year 2026 is permanently closed. You cannot create or change documents or payments dated in that year.',
        ]);
        $this->assertSame('Railway', $supplier->fresh()->name);

        DB::table('fiscal_years')->delete();
        $this->forgetGuards();
        CompanyBooksLock::create([
            'user_id' => 1,
            'locked_through' => '2026-10-31',
        ]);

        $this->actingAs($user)->from('/company/suppliers')->post('/company/suppliers/'.$supplier->id.'/name', [
            'name' => 'Railway Corp',
        ])->assertRedirect('/company/suppliers')->assertSessionHasErrors([
            'books' => 'Company books are locked through 31 Oct 2026. Unlock or post a reversing entry.',
        ]);
        $this->assertSame('Railway', $supplier->fresh()->name);
    }

    public function test_an_expense_description_and_date_rewrite_the_journal_and_leave_the_amounts(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $this->companyUser(2, 'Someone else');
        $this->profileFor(1);
        $expense = CompanyExpense::create([
            'user_id' => 1,
            'expense_date' => '2026-11-02',
            'category' => 'software',
            'description' => 'Cursor subscription',
            'amount' => 10,
            'vat_amount' => 1.80,
            'funded_by' => 'company',
        ]);
        CompanyLedger::postExpense($expense);

        $foreign = CompanyJournalEntry::create([
            'user_id' => 2,
            'entry_date' => '2026-11-02',
            'description' => 'Expense: Cursor subscription',
            'source_type' => 'company_expense',
            'source_id' => $expense->id,
            'source_key' => 'company_expense:'.$expense->id.':posted-other',
            'status' => 'posted',
        ]);
        $before = CompanyJournalLine::where('user_id', 1)->orderBy('id')->get();
        $this->assertNotEmpty($before);

        $this->actingAs($user)->from('/company/expenses')->post('/company/expenses/'.$expense->id.'/correct', [
            'expense_date' => '2026-01-15',
            'description' => 'Cursor Pro',
        ])->assertRedirect('/company/expenses')->assertSessionHas('success');

        $expense->refresh();
        $this->assertSame('2026-01-15', $expense->expense_date->format('Y-m-d'));
        $this->assertSame('Cursor Pro', $expense->description);
        $this->assertTrue($expense->is_pre_incorporation);
        $this->assertSame('10.00', number_format((float) $expense->amount, 2, '.', ''));
        $this->assertSame('1.80', number_format((float) $expense->vat_amount, 2, '.', ''));
        $this->assertSame('software', $expense->category);
        $this->assertSame('company', $expense->funded_by);

        $entry = CompanyJournalEntry::where('user_id', 1)
            ->where('source_key', 'company_expense:'.$expense->id.':posted')
            ->firstOrFail();
        $this->assertSame('2026-01-15', $entry->entry_date->format('Y-m-d'));
        $this->assertSame('Expense: Cursor Pro', $entry->description);
        $this->assertSame('posted', $entry->status);

        $lines = CompanyJournalLine::where('user_id', 1)->where('journal_entry_id', $entry->id)->orderBy('id')->get();
        $this->assertCount($before->count(), $lines);
        foreach ($before as $index => $original) {
            $this->assertSame(number_format((float) $original->amount, 2, '.', ''), number_format((float) $lines[$index]->amount, 2, '.', ''));
            $this->assertSame($original->side, $lines[$index]->side);
        }
        $this->assertTrue($lines->contains(fn (CompanyJournalLine $line) => $line->side === 'debit' && $line->memo === 'Cursor Pro'));
        $this->assertTrue($lines->contains(fn (CompanyJournalLine $line) => $line->side === 'credit' && $line->memo === 'company'));

        $foreign->refresh();
        $this->assertSame('Expense: Cursor subscription', $foreign->description);
        $this->assertSame('2026-11-02', $foreign->entry_date->format('Y-m-d'));

        $this->actingAs($this->user(2))->post('/company/expenses/'.$expense->id.'/correct', [
            'expense_date' => '2026-08-01',
            'description' => 'Taken',
        ])->assertNotFound();
        $this->assertSame('Cursor Pro', $expense->fresh()->description);
    }

    public function test_an_expense_date_cannot_move_into_the_future_or_past_the_first_period(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $this->profileFor(1);
        $expense = CompanyExpense::create([
            'user_id' => 1,
            'expense_date' => '2026-11-02',
            'category' => 'software',
            'description' => 'Cursor subscription',
            'amount' => 10,
            'vat_amount' => 0,
            'funded_by' => 'company',
        ]);

        $this->actingAs($user)->from('/company/expenses')->post('/company/expenses/'.$expense->id.'/correct', [
            'expense_date' => '2026-11-22',
            'description' => 'Cursor subscription',
        ])->assertRedirect('/company/expenses')->assertSessionHasErrors('expense_date');
        $this->assertSame('2026-11-02', $expense->fresh()->expense_date->format('Y-m-d'));

        Carbon::setTestNow('2027-02-01');
        $this->actingAs($user)->from('/company/expenses')->post('/company/expenses/'.$expense->id.'/correct', [
            'expense_date' => '2027-01-15',
            'description' => 'Cursor subscription',
        ])->assertRedirect('/company/expenses')->assertSessionHasErrors([
            'expense_date' => 'Expense date is after the first financial period end.',
        ]);
        $this->assertSame('2026-11-02', $expense->fresh()->expense_date->format('Y-m-d'));
    }

    public function test_an_expense_correction_is_blocked_for_a_locked_year_or_locked_books(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $this->profileFor(1);
        $expense = CompanyExpense::create([
            'user_id' => 1,
            'expense_date' => '2026-10-18',
            'category' => 'software',
            'description' => 'Hosting',
            'amount' => 20,
            'vat_amount' => 0,
            'funded_by' => 'company',
        ]);
        CompanyLedger::postExpense($expense);
        $this->closeYear(1, 2026);

        $this->actingAs($user)->from('/company/expenses')->post('/company/expenses/'.$expense->id.'/correct', [
            'expense_date' => '2026-10-19',
            'description' => 'Railway hosting',
        ])->assertRedirect('/company/expenses')->assertSessionHasErrors('books');
        $this->assertSame('Hosting', $expense->fresh()->description);
        $this->assertSame('2026-10-18', CompanyJournalEntry::where('user_id', 1)->value('entry_date')->format('Y-m-d'));

        DB::table('fiscal_years')->delete();
        $this->forgetGuards();
        CompanyBooksLock::create([
            'user_id' => 1,
            'locked_through' => '2026-10-31',
        ]);

        $this->actingAs($user)->from('/company/expenses')->post('/company/expenses/'.$expense->id.'/correct', [
            'expense_date' => '2026-11-02',
            'description' => 'Railway hosting',
        ])->assertRedirect('/company/expenses')->assertSessionHasErrors('books');
        $this->assertSame('Hosting', $expense->fresh()->description);
        $this->assertSame('2026-10-18', $expense->fresh()->expense_date->format('Y-m-d'));
    }

    public function test_an_expense_date_cannot_move_past_its_reversal(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $this->profileFor(1);
        $expense = CompanyExpense::create([
            'user_id' => 1,
            'expense_date' => '2026-10-18',
            'category' => 'software',
            'description' => 'Hosting',
            'amount' => 20,
            'vat_amount' => 0,
            'funded_by' => 'company',
            'reversed_at' => '2026-10-20',
        ]);

        $this->actingAs($user)->from('/company/expenses')->post('/company/expenses/'.$expense->id.'/correct', [
            'expense_date' => '2026-10-21',
            'description' => 'Hosting',
        ])->assertRedirect('/company/expenses')->assertSessionHasErrors([
            'expense_date' => 'The expense date cannot be later than the reversal date.',
        ]);
        $this->assertSame('2026-10-18', $expense->fresh()->expense_date->format('Y-m-d'));
    }

    public function test_computer_wear_and_tear_moves_with_the_expense_date_and_does_not_post_next_year(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
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
        CompanyLedger::postExpense($expense);

        $slice = CompanyJournalEntry::where('user_id', 1)
            ->where('source_key', 'company_expense:'.$expense->id.':depreciation:0')
            ->firstOrFail();
        $this->assertSame('2026-10-30', $slice->entry_date->format('Y-m-d'));
        $this->assertSame(388.39, round((float) CompanyJournalLine::where('journal_entry_id', $slice->id)->value('amount'), 2));
        $this->assertNull(
            CompanyJournalEntry::where('source_key', 'company_expense:'.$expense->id.':depreciation:1')->first()
        );

        $this->actingAs($user)->post('/company/expenses/'.$expense->id.'/correct', [
            'expense_date' => '2026-11-02',
            'description' => 'Cyberport tower',
        ])->assertRedirect();

        $expense->refresh();
        $this->assertSame('2026-11-02', $expense->expense_date->format('Y-m-d'));
        $this->assertSame('Cyberport tower', $expense->description);
        $this->assertSame('1553.57', number_format((float) $expense->amount, 2, '.', ''));
        $this->assertFalse($expense->is_pre_incorporation);

        $slice->refresh();
        $this->assertSame('2026-11-02', $slice->entry_date->format('Y-m-d'));
        $this->assertSame('Computer wear and tear: Cyberport tower', $slice->description);
        $this->assertSame('company_expense:'.$expense->id.':depreciation:0', $slice->source_key);
        $this->assertSame(388.39, round((float) CompanyJournalLine::where('user_id', 1)->where('journal_entry_id', $slice->id)->value('amount'), 2));
        $this->assertSame(1, CompanyJournalEntry::where('user_id', 1)->where('source_type', 'company_expense_depreciation')->count());

        $purchase = CompanyJournalEntry::where('user_id', 1)->where('source_type', 'company_expense')->firstOrFail();
        $this->assertSame('2026-11-02', $purchase->entry_date->format('Y-m-d'));
        $this->assertSame('Expense (reverse charge): Cyberport tower', $purchase->description);
        $this->assertTrue(
            CompanyJournalLine::where('user_id', 1)
                ->where('journal_entry_id', $purchase->id)
                ->where('memo', 'Reverse charge: Cyberport tower')
                ->exists()
        );
    }

    public function test_a_wear_and_tear_line_after_the_period_end_can_be_renamed_until_that_year_is_closed(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
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
        $later = CompanyJournalEntry::create([
            'user_id' => 1,
            'entry_date' => '2027-01-01',
            'description' => 'Computer wear and tear: Cyberport computer parts',
            'source_type' => 'company_expense_depreciation',
            'source_id' => $expense->id,
            'source_key' => 'company_expense:'.$expense->id.':depreciation:1',
            'status' => 'posted',
        ]);
        CompanyJournalLine::create([
            'user_id' => 1,
            'journal_entry_id' => $later->id,
            'gl_account_id' => 1,
            'side' => 'debit',
            'amount' => 388.39,
            'memo' => 'Cyberport computer parts',
        ]);

        $this->actingAs($user)->post('/company/expenses/'.$expense->id.'/correct', [
            'expense_date' => '2026-10-30',
            'description' => 'Cyberport tower',
        ])->assertRedirect();

        $later->refresh();
        $this->assertSame('2027-01-01', $later->entry_date->format('Y-m-d'));
        $this->assertSame('Computer wear and tear: Cyberport tower', $later->description);
        $this->assertSame('Cyberport tower', CompanyJournalLine::where('journal_entry_id', $later->id)->value('memo'));
        $this->assertSame(388.39, round((float) CompanyJournalLine::where('journal_entry_id', $later->id)->value('amount'), 2));

        $this->closeYear(1, 2027);
        $this->forgetGuards();

        $this->actingAs($user)->from('/company/expenses')->post('/company/expenses/'.$expense->id.'/correct', [
            'expense_date' => '2026-10-30',
            'description' => 'Cyberport desktop',
        ])->assertRedirect('/company/expenses')->assertSessionHasErrors('books');

        $this->assertSame('Cyberport tower', $expense->fresh()->description);
        $this->assertSame('Computer wear and tear: Cyberport tower', $later->fresh()->description);
    }

    public function test_a_bank_line_date_narrative_and_amount_can_be_corrected(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $other = $this->companyUser(2, 'Someone else');
        $this->profileFor(1);
        $line = CompanyBankStatementLine::create([
            'user_id' => 1,
            'statement_date' => '2026-11-02',
            'description' => 'CURSOR',
            'amount' => -11.80,
            'status' => 'unreconciled',
        ]);
        $theirs = CompanyBankStatementLine::create([
            'user_id' => 2,
            'statement_date' => '2026-11-02',
            'description' => 'CURSOR',
            'amount' => -11.80,
            'status' => 'unreconciled',
        ]);

        $this->actingAs($other)->post('/company/bank/'.$line->id.'/correct', [
            'statement_date' => '2026-11-03',
            'description' => 'Taken',
            'amount' => -1,
        ])->assertNotFound();
        $this->assertSame('CURSOR', $line->fresh()->description);

        $this->actingAs($user)->from('/company/bank')->post('/company/bank/'.$line->id.'/correct', [
            'statement_date' => '2026-11-03',
            'description' => '  CURSOR PRO  ',
            'amount' => -20.5,
        ])->assertRedirect('/company/bank')->assertSessionHas('success');

        $line->refresh();
        $this->assertSame('2026-11-03', $line->statement_date->format('Y-m-d'));
        $this->assertSame('CURSOR PRO', $line->description);
        $this->assertSame('-20.50', number_format((float) $line->amount, 2, '.', ''));
        $this->assertSame('unreconciled', $line->status);
        $this->assertSame('CURSOR', $theirs->fresh()->description);

        $this->actingAs($user)->from('/company/bank')->post('/company/bank/'.$line->id.'/correct', [
            'statement_date' => '2026-11-22',
            'description' => 'CURSOR PRO',
            'amount' => -20.50,
        ])->assertRedirect('/company/bank')->assertSessionHasErrors('statement_date');
        $this->assertSame('2026-11-03', $line->fresh()->statement_date->format('Y-m-d'));
    }

    public function test_a_matched_bank_line_keeps_its_amount_and_can_still_correct_the_narrative(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $this->profileFor(1);
        $journal = CompanyJournalLine::create([
            'user_id' => 1,
            'journal_entry_id' => 1,
            'gl_account_id' => 1,
            'side' => 'credit',
            'amount' => 11.80,
        ]);
        CompanyJournalEntry::create([
            'id' => 1,
            'user_id' => 1,
            'entry_date' => '2026-11-02',
            'description' => 'Expense: Cursor subscription',
            'source_type' => 'company_expense',
            'source_id' => 1,
            'status' => 'reconciled',
        ]);
        $line = CompanyBankStatementLine::create([
            'user_id' => 1,
            'statement_date' => '2026-11-02',
            'description' => 'CURSOR',
            'amount' => -11.80,
            'status' => 'matched',
            'matched_journal_line_id' => $journal->id,
        ]);
        $journal->update(['bank_statement_line_id' => $line->id]);

        $this->actingAs($user)->from('/company/bank')->post('/company/bank/'.$line->id.'/correct', [
            'statement_date' => '2026-11-03',
            'description' => 'CURSOR PRO',
            'amount' => -20.50,
        ])->assertRedirect('/company/bank')->assertSessionHasErrors([
            'amount' => 'This bank line is matched. Leave the amount as it is, or the match would no longer add up. Date and narrative can still be corrected.',
        ]);
        $line->refresh();
        $this->assertSame('CURSOR', $line->description);
        $this->assertSame('-11.80', number_format((float) $line->amount, 2, '.', ''));
        $this->assertSame('matched', $line->status);
        $this->assertSame($line->id, (int) $journal->fresh()->bank_statement_line_id);

        $this->actingAs($user)->from('/company/bank')->post('/company/bank/'.$line->id.'/correct', [
            'statement_date' => '2026-11-03',
            'description' => 'CURSOR PRO',
            'amount' => -11.80,
        ])->assertRedirect('/company/bank')->assertSessionHas('success');

        $line->refresh();
        $this->assertSame('2026-11-03', $line->statement_date->format('Y-m-d'));
        $this->assertSame('CURSOR PRO', $line->description);
        $this->assertSame('-11.80', number_format((float) $line->amount, 2, '.', ''));
        $this->assertSame('matched', $line->status);
        $this->assertSame($line->id, (int) $journal->fresh()->bank_statement_line_id);
    }

    public function test_a_bank_line_date_cannot_pass_the_first_period_or_a_lock(): void
    {
        $user = $this->companyUser(1, 'Nicholas');
        $this->profileFor(1);
        $line = CompanyBankStatementLine::create([
            'user_id' => 1,
            'statement_date' => '2026-11-02',
            'description' => 'CURSOR',
            'amount' => -11.80,
            'status' => 'unreconciled',
        ]);

        Carbon::setTestNow('2027-02-01');
        $this->actingAs($user)->from('/company/bank')->post('/company/bank/'.$line->id.'/correct', [
            'statement_date' => '2027-01-15',
            'description' => 'CURSOR',
            'amount' => -11.80,
        ])->assertRedirect('/company/bank')->assertSessionHasErrors([
            'statement_date' => 'Statement date is after the first financial period end.',
        ]);
        $this->assertSame('2026-11-02', $line->fresh()->statement_date->format('Y-m-d'));

        Carbon::setTestNow('2026-11-21');
        $this->closeYear(1, 2026);
        $this->actingAs($user)->from('/company/bank')->post('/company/bank/'.$line->id.'/correct', [
            'statement_date' => '2026-11-03',
            'description' => 'CURSOR PRO',
            'amount' => -11.80,
        ])->assertRedirect('/company/bank')->assertSessionHasErrors('books');
        $this->assertSame('CURSOR', $line->fresh()->description);

        DB::table('fiscal_years')->delete();
        $this->forgetGuards();
        CompanyBooksLock::create([
            'user_id' => 1,
            'locked_through' => '2026-11-30',
        ]);
        $this->actingAs($user)->from('/company/bank')->post('/company/bank/'.$line->id.'/correct', [
            'statement_date' => '2026-11-03',
            'description' => 'CURSOR PRO',
            'amount' => -11.80,
        ])->assertRedirect('/company/bank')->assertSessionHasErrors('books');
        $this->assertSame('CURSOR', $line->fresh()->description);
        $this->assertSame('2026-11-02', $line->fresh()->statement_date->format('Y-m-d'));
    }

    private function user(int $id): User
    {
        return User::findOrFail($id);
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
        Schema::dropIfExists('company_bank_statement_lines');
        Schema::dropIfExists('company_journal_entries');
        Schema::dropIfExists('company_books_locks');
        Schema::dropIfExists('company_gl_accounts');
        Schema::dropIfExists('company_expenses');
        Schema::dropIfExists('company_suppliers');
        Schema::dropIfExists('company_expense_payments');
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
            $table->timestamp('closed_at')->nullable();
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

        Schema::create('company_bank_statement_lines', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('statement_date');
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->string('status')->default('unreconciled');
            $table->unsignedBigInteger('matched_journal_line_id')->nullable();
            $table->string('import_batch')->nullable();
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
