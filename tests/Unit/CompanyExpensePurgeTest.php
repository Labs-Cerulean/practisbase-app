<?php

namespace Tests\Unit;

use App\Support\CompanyLedger;
use PHPUnit\Framework\TestCase;

class CompanyExpensePurgeTest extends TestCase
{
    public function test_railway_fee_and_reversal_can_be_removed_together(): void
    {
        $lines = $this->railwayLines();

        $this->assertTrue(CompanyLedger::journalsAlreadyCancel($lines));
        $this->assertNull(CompanyLedger::reversedExpensePurgeBlock(
            '2026-11-02',
            null,
            ['company_expense', 'company_expense_reversal'],
            false
        ));
        $this->assertTrue(CompanyLedger::canRemoveReversedExpense(
            '2026-11-02',
            null,
            ['company_expense', 'company_expense_reversal'],
            $lines,
            [null, null],
            []
        ));
    }

    public function test_open_expense_cannot_be_removed(): void
    {
        $this->assertSame(
            'Reverse the expense first. Removing it is only for an entry that was a mistake and already cancels itself out.',
            CompanyLedger::reversedExpensePurgeBlock(null, null, ['company_expense', 'company_expense_reversal'], false)
        );
        $this->assertSame(
            'Reverse the expense first. Removing it is only for an entry that was a mistake and already cancels itself out.',
            CompanyLedger::reversedExpensePurgeBlock('', null, ['company_expense', 'company_expense_reversal'], false)
        );
    }

    public function test_supplier_payment_blocks_removal(): void
    {
        $message = CompanyLedger::reversedExpensePurgeBlock(
            '2026-11-02',
            14,
            ['company_expense', 'company_expense_reversal'],
            false
        );

        $this->assertStringContainsString('supplier payment', (string) $message);
    }

    public function test_matched_bank_line_blocks_removal(): void
    {
        $this->assertFalse(CompanyLedger::canRemoveReversedExpense(
            '2026-11-02',
            null,
            ['company_expense', 'company_expense_reversal'],
            $this->railwayLines(),
            [55],
            []
        ));
        $this->assertFalse(CompanyLedger::canRemoveReversedExpense(
            '2026-11-02',
            null,
            ['company_expense', 'company_expense_reversal'],
            $this->railwayLines(),
            [null, null],
            [172]
        ));
        $this->assertStringContainsString(
            'matched to the bank statement',
            (string) CompanyLedger::reversedExpensePurgeBlock(
                '2026-11-02',
                null,
                ['company_expense', 'company_expense_reversal'],
                true
            )
        );
    }

    public function test_missing_reversal_blocks_removal(): void
    {
        $message = CompanyLedger::reversedExpensePurgeBlock('2026-11-02', null, ['company_expense'], false);

        $this->assertStringContainsString('both need to be on the books', (string) $message);
    }

    public function test_fee_alone_does_not_cancel(): void
    {
        $fee = CompanyLedger::expenseJournalLines(0.28, 0.0, false, 'company', 'bank', 'CURRENCY CONVERSION FEE - RAILWAY.COM');

        $this->assertFalse(CompanyLedger::journalsAlreadyCancel($this->asCancelLines($fee)));
        $this->assertFalse(CompanyLedger::journalsAlreadyCancel([]));
    }

    public function test_mismatched_reversal_does_not_cancel(): void
    {
        $fee = CompanyLedger::expenseJournalLines(0.28, 0.0, false, 'company', 'bank', 'CURRENCY CONVERSION FEE - RAILWAY.COM');
        $other = CompanyLedger::expenseJournalLines(0.10, 0.0, false, 'company', 'bank', 'other');
        $lines = $this->asCancelLines(array_merge($fee, CompanyLedger::swapSides($other)));

        $this->assertFalse(CompanyLedger::journalsAlreadyCancel($lines));
        $this->assertFalse(CompanyLedger::canRemoveReversedExpense(
            '2026-11-02',
            null,
            ['company_expense', 'company_expense_reversal'],
            $lines,
            [null, null],
            []
        ));
    }

    public function test_depreciation_without_reversal_blocks_removal(): void
    {
        $message = CompanyLedger::reversedExpensePurgeBlock(
            '2026-11-02',
            null,
            ['company_expense', 'company_expense_reversal', 'company_expense_depreciation'],
            false
        );

        $this->assertStringContainsString('wear and tear', (string) $message);
    }

    public function test_depreciation_pair_can_be_removed_when_it_cancels(): void
    {
        $cost = CompanyLedger::expenseJournalLines(1553.57, 279.64, true, 'company', 'computer', 'Cyberport');
        $wear = CompanyLedger::computerDepreciationLines(388.39, 'Cyberport');
        $lines = $this->asCancelLines(array_merge(
            $cost,
            CompanyLedger::swapSides($cost),
            $wear,
            CompanyLedger::swapSides($wear)
        ));

        $this->assertTrue(CompanyLedger::canRemoveReversedExpense(
            '2026-11-02',
            null,
            [
                'company_expense',
                'company_expense_reversal',
                'company_expense_depreciation',
                'company_expense_depreciation_reversal',
            ],
            $lines,
            [null, null],
            []
        ));
    }

    public function test_director_refund_without_reversal_blocks_removal(): void
    {
        $message = CompanyLedger::reversedExpensePurgeBlock(
            '2026-11-02',
            null,
            ['company_expense', 'company_expense_reversal', 'company_expense_refund'],
            false
        );

        $this->assertStringContainsString('director refund', (string) $message);
    }

    public function test_supplier_payment_reversal_blocks_removal(): void
    {
        $message = CompanyLedger::reversedExpensePurgeBlock(
            '2026-11-02',
            null,
            ['company_expense', 'company_expense_reversal', 'company_expense_supplier_payment_reversal'],
            false
        );

        $this->assertStringContainsString('supplier payment', (string) $message);
    }

    public function test_other_journal_blocks_removal(): void
    {
        $message = CompanyLedger::reversedExpensePurgeBlock(
            '2026-11-02',
            null,
            ['company_expense', 'company_expense_reversal', 'company_invoice'],
            false
        );

        $this->assertStringContainsString('other journal', (string) $message);
    }

    /**
     * @return list<array{account: string, side: string, amount: float|int|string}>
     */
    private function railwayLines(): array
    {
        $fee = CompanyLedger::expenseJournalLines(0.28, 0.0, false, 'company', 'bank', 'CURRENCY CONVERSION FEE - RAILWAY.COM');

        return $this->asCancelLines(array_merge($fee, CompanyLedger::swapSides($fee)));
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float|int|string}>  $lines
     * @return list<array{account: string, side: string, amount: float|int|string}>
     */
    private function asCancelLines(array $lines): array
    {
        $mapped = [];
        foreach ($lines as $line) {
            $mapped[] = [
                'account' => (string) $line['account_code'],
                'side' => (string) $line['side'],
                'amount' => $line['amount'],
            ];
        }

        return $mapped;
    }
}
