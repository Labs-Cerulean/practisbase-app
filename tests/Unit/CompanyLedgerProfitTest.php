<?php

namespace Tests\Unit;

use App\Support\CompanyLedger;
use PHPUnit\Framework\TestCase;

class CompanyLedgerProfitTest extends TestCase
{
    public function test_pre_incorporation_expenses_are_the_balance_sheet_gap(): void
    {
        $periodProfit = 1984.33;
        $cumulativeProfit = round(2166.00 - 317.20, 2);

        $this->assertSame(1848.80, $cumulativeProfit);
        $this->assertSame(-135.53, CompanyLedger::profitOutsidePeriod($cumulativeProfit, $periodProfit));
    }
}
