<?php

namespace Tests\Unit;

use App\Models\CompanyProfile;
use App\Support\CompanyCorporateTax;
use Tests\TestCase;

class CompanyCorporateTaxTest extends TestCase
{
    public function test_liability_cannot_go_negative(): void
    {
        $this->assertSame(350.0, CompanyCorporateTax::liability(1000, 0.35));
        $this->assertSame(3.5, CompanyCorporateTax::liability(10, 0.35));
        $this->assertSame(35.04, CompanyCorporateTax::liability(100.10, 0.35));
        $this->assertSame(0.0, CompanyCorporateTax::liability(0, 0.35));
        $this->assertSame(0.0, CompanyCorporateTax::liability(-200, 0.35));
        $this->assertSame(0.0, CompanyCorporateTax::liability(-0.01, 0.35));
        $this->assertSame(0.0, CompanyCorporateTax::liability(1000, 0));
        $this->assertSame(0.0, CompanyCorporateTax::liability(1000, -0.35));
    }

    public function test_first_period_on_21_november_2026_is_measured_to_that_day(): void
    {
        $period = CompanyCorporateTax::periodContaining($this->cerulean(), '2026-11-21');

        $this->assertSame('2026-07-29', $period['from']);
        $this->assertSame('2026-12-31', $period['to']);
        $this->assertSame('2026-11-21', $period['measured_to']);
        $this->assertTrue($period['open']);
    }

    public function test_days_before_incorporation_are_outside_the_period(): void
    {
        $this->assertNull(CompanyCorporateTax::periodContaining($this->cerulean(), '2026-07-28'));
    }

    public function test_the_next_financial_year_starts_the_day_after_the_first_period(): void
    {
        $period = CompanyCorporateTax::periodContaining($this->cerulean(), '2027-01-15');

        $this->assertSame('2027-01-01', $period['from']);
        $this->assertSame('2027-12-31', $period['to']);
        $this->assertSame('2027-01-15', $period['measured_to']);
        $this->assertTrue($period['open']);
        $this->assertSame(
            ['from' => '2026-07-29', 'to' => '2026-12-31'],
            CompanyCorporateTax::periodEndingInYear($this->cerulean(), 2026)
        );
        $this->assertSame(
            ['from' => '2027-01-01', 'to' => '2027-12-31'],
            CompanyCorporateTax::periodEndingInYear($this->cerulean(), 2027)
        );
    }

    private function cerulean(): CompanyProfile
    {
        return new CompanyProfile([
            'legal_name' => 'Cerulean Labs Limited',
            'financial_year_end_month' => 12,
            'financial_year_end_day' => 31,
            'first_period_start' => '2026-07-29',
            'first_period_end' => '2026-12-31',
            'vat_status' => 'article_10',
        ]);
    }
}
