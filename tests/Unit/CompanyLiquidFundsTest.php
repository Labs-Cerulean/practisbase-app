<?php

namespace Tests\Unit;

use App\Support\CompanyLiquidFunds;
use PHPUnit\Framework\TestCase;

class CompanyLiquidFundsTest extends TestCase
{
    public function test_cash_after_vat_is_what_the_company_can_use(): void
    {
        $funds = CompanyLiquidFunds::fromNaturalBalances([
            '1000' => 3418.48,
            '1200' => 48.39,
            '2100' => 418.07,
        ], 1200);

        $this->assertSame(3048.80, $funds['available']);
        $this->assertSame(1200.0, $funds['share_capital']);
        $this->assertSame(-369.68, $funds['lines'][1]['amount']);
    }

    public function test_unpaid_bills_and_director_debt_are_set_aside(): void
    {
        $funds = CompanyLiquidFunds::fromNaturalBalances([
            '1000' => 1000,
            '1010' => 50,
            '2000' => 100,
            '2300' => 80,
            '2200' => 20,
        ]);

        $this->assertSame(850.0, $funds['available']);
    }

    public function test_a_vat_refund_and_a_director_debit_are_not_treated_as_cash(): void
    {
        $funds = CompanyLiquidFunds::fromNaturalBalances([
            '1000' => 500,
            '1200' => 80,
            '2100' => 10,
            '2300' => -40,
        ]);

        $this->assertSame(500.0, $funds['available']);
    }
}
