<?php

namespace Tests\Unit;

use App\Support\CompanyChartOfAccounts;
use App\Support\CompanyLedger;
use PHPUnit\Framework\TestCase;

class CompanyExpenseReversalTest extends TestCase
{
    public function test_company_paid_vat_reversal_nets_every_account_to_zero(): void
    {
        $original = CompanyLedger::expenseJournalLines(100.00, 18.00, false, 'company', 'software', 'Google');
        $this->assertBalancesToZero($original, CompanyLedger::swapSides($original));
        $this->assertSame(CompanyChartOfAccounts::BANK, $this->creditAccount($original));
    }

    public function test_reverse_charge_director_expense_and_refund_net_to_zero(): void
    {
        $expense = CompanyLedger::expenseJournalLines(50.00, 9.00, true, 'director', 'software', 'Railway');
        $refund = CompanyLedger::directorRefundJournalLines(50.00, 'BOV 1');

        $this->assertSame(CompanyChartOfAccounts::DIRECTOR_LOAN, $this->creditAccount($expense));
        $this->assertSame(9.00, $this->amountOn($expense, CompanyChartOfAccounts::OUTPUT_VAT, 'credit'));
        $this->assertSame(9.00, $this->amountOn($expense, CompanyChartOfAccounts::INPUT_VAT, 'debit'));

        $this->assertBalancesToZero(
            array_merge($expense, $refund),
            array_merge(CompanyLedger::swapSides($expense), CompanyLedger::swapSides($refund))
        );
    }

    public function test_swap_sides_keeps_amounts_and_flips_direction(): void
    {
        $lines = CompanyLedger::expenseJournalLines(12.34, 0.0, false, 'company', 'bank', 'BOV fee');
        $swapped = CompanyLedger::swapSides($lines);

        $this->assertCount(count($lines), $swapped);
        foreach ($lines as $i => $line) {
            $this->assertSame($line['amount'], $swapped[$i]['amount']);
            $this->assertSame($line['account_code'], $swapped[$i]['account_code']);
            $this->assertNotSame($line['side'], $swapped[$i]['side']);
        }
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float|int|string}>  $original
     * @param  list<array{account_code: string, side: string, amount: float|int|string}>  $reversal
     */
    private function assertBalancesToZero(array $original, array $reversal): void
    {
        $balances = [];
        foreach (array_merge($original, $reversal) as $line) {
            $signed = ($line['side'] === 'debit' ? 1 : -1) * (float) $line['amount'];
            $balances[$line['account_code']] = ($balances[$line['account_code']] ?? 0) + $signed;
        }

        foreach ($balances as $code => $balance) {
            $this->assertEqualsWithDelta(0.0, $balance, 0.001, $code);
        }
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float|int|string}>  $lines
     */
    private function creditAccount(array $lines): string
    {
        foreach ($lines as $line) {
            if ($line['side'] === 'credit' && in_array($line['account_code'], [CompanyChartOfAccounts::BANK, CompanyChartOfAccounts::DIRECTOR_LOAN], true)) {
                return $line['account_code'];
            }
        }

        $this->fail('No bank or director-loan credit.');
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float|int|string}>  $lines
     */
    private function amountOn(array $lines, string $code, string $side): float
    {
        foreach ($lines as $line) {
            if ($line['account_code'] === $code && $line['side'] === $side) {
                return (float) $line['amount'];
            }
        }

        return 0.0;
    }
}
