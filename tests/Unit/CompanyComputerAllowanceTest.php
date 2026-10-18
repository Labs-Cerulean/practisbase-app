<?php

namespace Tests\Unit;

use App\Support\CompanyChartOfAccounts;
use App\Support\CompanyComputerAllowance;
use App\Support\CompanyLedger;
use PHPUnit\Framework\TestCase;

class CompanyComputerAllowanceTest extends TestCase
{
    public function test_german_inclusive_total_splits_off_nineteen_percent_vat(): void
    {
        $split = CompanyComputerAllowance::splitInclusive(1848.75, 0.19);

        $this->assertSame(1553.57, $split['amount']);
        $this->assertSame(295.18, $split['vat']);
        $this->assertSame(1848.75, round($split['amount'] + $split['vat'], 2));
    }

    public function test_maltese_inclusive_total_splits_off_eighteen_percent_vat(): void
    {
        $split = CompanyComputerAllowance::splitInclusive(1848.75, 0.18);

        $this->assertSame(1566.74, $split['amount']);
        $this->assertSame(282.01, $split['vat']);
    }

    public function test_wear_and_tear_is_four_slices_from_the_day_it_is_brought_into_use(): void
    {
        $slices = CompanyComputerAllowance::slices(1553.57, '2026-10-18', '2026-12-31');

        $this->assertSame(
            ['2026-10-18', '2027-01-01', '2028-01-01', '2029-01-01'],
            array_column($slices, 'date')
        );
        $this->assertSame(388.39, $slices[0]['amount']);
        $this->assertSame(388.39, $slices[1]['amount']);
        $this->assertSame(388.39, $slices[2]['amount']);
        $this->assertSame(388.40, $slices[3]['amount']);
        $this->assertSame(1553.57, round(array_sum(array_column($slices, 'amount')), 2));
    }

    public function test_irrecoverable_vat_stays_inside_the_cost_that_is_written_off(): void
    {
        $slices = CompanyComputerAllowance::slices(1848.75, '2026-10-18', '2026-12-31');

        $this->assertSame(462.19, $slices[0]['amount']);
        $this->assertSame(462.18, $slices[3]['amount']);
        $this->assertSame(1848.75, round(array_sum(array_column($slices, 'amount')), 2));
    }

    public function test_only_slices_up_to_today_are_due(): void
    {
        $slices = CompanyComputerAllowance::slices(1553.57, '2026-10-18', '2026-12-31');
        $due = CompanyComputerAllowance::dueSlices($slices, '2026-10-18');

        $this->assertCount(1, $due);
        $this->assertSame(0, $due[0]['index']);
    }

    public function test_computer_purchase_debits_the_asset_and_keeps_reclaimable_vat_separate(): void
    {
        $lines = CompanyLedger::expenseJournalLines(1553.57, 295.18, false, 'company', 'computer', 'Work PC');

        $this->assertSame(1553.57, $this->line($lines, CompanyChartOfAccounts::COMPUTER_EQUIPMENT, 'debit')['amount']);
        $this->assertSame(295.18, $this->line($lines, CompanyChartOfAccounts::INPUT_VAT, 'debit')['amount']);
        $this->assertSame(1848.75, $this->line($lines, CompanyChartOfAccounts::BANK, 'credit')['amount']);
        $this->assertNull($this->find($lines, CompanyChartOfAccounts::expenseAccountCode('office'), 'debit'));
    }

    public function test_wear_and_tear_moves_a_quarter_from_the_asset_to_profit(): void
    {
        $lines = CompanyLedger::computerDepreciationLines(388.39, 'Work PC');

        $this->assertSame(388.39, $this->line($lines, CompanyChartOfAccounts::DEPRECIATION, 'debit')['amount']);
        $this->assertSame(388.39, $this->line($lines, CompanyChartOfAccounts::COMPUTER_EQUIPMENT, 'credit')['amount']);
    }

    public function test_reversing_the_purchase_and_the_wear_and_tear_clears_the_asset(): void
    {
        $purchase = CompanyLedger::expenseJournalLines(1553.57, 295.18, false, 'company', 'computer', 'Work PC');
        $wear = CompanyLedger::computerDepreciationLines(388.39, 'Work PC');
        $reversal = array_merge(
            CompanyLedger::swapSides($purchase),
            CompanyLedger::swapSides($wear)
        );

        $this->assertSame(0.0, $this->net(array_merge($purchase, $wear, $reversal), CompanyChartOfAccounts::COMPUTER_EQUIPMENT));
        $this->assertSame(0.0, $this->net(array_merge($purchase, $wear, $reversal), CompanyChartOfAccounts::DEPRECIATION));
        $this->assertSame(0.0, $this->net(array_merge($purchase, $wear, $reversal), CompanyChartOfAccounts::BANK));
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float}>  $lines
     * @return array{account_code: string, side: string, amount: float}
     */
    private function line(array $lines, string $code, string $side): array
    {
        $line = $this->find($lines, $code, $side);
        $this->assertNotNull($line);

        return $line;
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float}>  $lines
     * @return array{account_code: string, side: string, amount: float}|null
     */
    private function find(array $lines, string $code, string $side): ?array
    {
        foreach ($lines as $line) {
            if ($line['account_code'] === $code && $line['side'] === $side) {
                return $line;
            }
        }

        return null;
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float}>  $lines
     */
    private function net(array $lines, string $code): float
    {
        $net = 0.0;
        foreach ($lines as $line) {
            if ($line['account_code'] !== $code) {
                continue;
            }
            $net += ($line['side'] === 'debit' ? 1 : -1) * (float) $line['amount'];
        }

        return round($net, 2);
    }
}
