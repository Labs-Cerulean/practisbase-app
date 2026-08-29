<?php

namespace Tests\Unit;

use App\Models\CompanyProfile;
use App\Support\CompanyComplianceCalendar;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CompanyComplianceCalendarTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_skips_vat_and_tax_before_incorporation(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 29));

        $profile = new CompanyProfile([
            'vat_status' => 'article_10',
            'vat_filing_frequency' => 'quarterly',
            'financial_year_end_month' => 12,
            'financial_year_end_day' => 31,
            'first_period_start' => '2026-07-29',
            'first_period_end' => '2026-12-31',
        ]);

        $keys = array_column(CompanyComplianceCalendar::events($profile, 2026), 'key');

        $this->assertNotContains('vat_q4_2025', $keys);
        $this->assertNotContains('vat_q1_2026', $keys);
        $this->assertNotContains('vat_q2_2026', $keys);
        $this->assertNotContains('pt_2026_4', $keys);

        $this->assertContains('vat_q3_2026', $keys);
        $this->assertContains('vat_q4_2026', $keys);
        $this->assertContains('pt_2026_8', $keys);
        $this->assertContains('pt_2026_12', $keys);
        $this->assertContains('mbr_2026', $keys);
        $this->assertContains('fye_2026', $keys);
    }

    public function test_upcoming_does_not_flag_pre_formation_vat_overdue(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 29));

        $profile = new CompanyProfile([
            'vat_status' => 'article_10',
            'vat_filing_frequency' => 'quarterly',
            'financial_year_end_month' => 12,
            'financial_year_end_day' => 31,
            'first_period_start' => '2026-07-29',
            'first_period_end' => '2026-12-31',
        ]);

        $keys = array_column(CompanyComplianceCalendar::upcoming($profile, 12), 'key');

        $this->assertNotContains('vat_q2_2026', $keys);
        $this->assertContains('pt_2026_8', $keys);
    }
}
