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

    private function ceruleanProfile(): CompanyProfile
    {
        return new CompanyProfile([
            'vat_status' => 'article_10',
            'vat_filing_frequency' => 'quarterly',
            'financial_year_end_month' => 12,
            'financial_year_end_day' => 31,
            'first_period_start' => '2026-07-29',
            'first_period_end' => '2026-12-31',
        ]);
    }

    public function test_skips_vat_before_incorporation(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 29));
        $keys = array_column(CompanyComplianceCalendar::events($this->ceruleanProfile(), 2026), 'key');

        $this->assertNotContains('vat_q4_2025', $keys);
        $this->assertNotContains('vat_q1_2026', $keys);
        $this->assertNotContains('vat_q2_2026', $keys);
        $this->assertNotContains('pt_2026_4', $keys);

        $this->assertContains('vat_q3_2026', $keys);
        $this->assertContains('vat_q4_2026', $keys);
        $this->assertContains('pt_2026_8', $keys);
        $this->assertContains('pt_2026_12', $keys);
        $this->assertContains('fye_2026', $keys);
        $this->assertNotContains('mbr_2026', $keys);
    }

    public function test_year1_pt_is_note_not_alarm_and_mbr_starts_next_year(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 29));
        $profile = $this->ceruleanProfile();

        $byKey = [];
        foreach (CompanyComplianceCalendar::events($profile, 2026) as $event) {
            $byKey[$event['key']] = $event;
        }

        $this->assertSame('note', $byKey['pt_2026_8']['severity']);
        $this->assertSame('year1_pass', $byKey['pt_2026_8']['status']);
        $this->assertFalse($byKey['pt_2026_8']['overdue']);
        $this->assertFalse($byKey['pt_2026_8']['urgent']);

        $this->assertSame('info', $byKey['fye_2026']['severity']);
        $this->assertFalse($byKey['fye_2026']['overdue']);

        $this->assertStringContainsString('First VAT return', $byKey['vat_q3_2026']['hint']);
        $this->assertSame('filing', $byKey['vat_q3_2026']['severity']);

        $keys2027 = array_column(CompanyComplianceCalendar::events($profile, 2027), 'key');
        $this->assertContains('mbr_2027', $keys2027);
        $this->assertContains('pt_2027_4', $keys2027);

        $pt2027 = null;
        foreach (CompanyComplianceCalendar::events($profile, 2027) as $event) {
            if ($event['key'] === 'pt_2027_4') {
                $pt2027 = $event;
                break;
            }
        }
        $this->assertNotNull($pt2027);
        $this->assertSame('filing', $pt2027['severity']);
    }

    public function test_upcoming_only_actionable_filings(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 29));
        $keys = array_column(CompanyComplianceCalendar::upcoming($this->ceruleanProfile(), 12), 'key');

        $this->assertNotContains('vat_q2_2026', $keys);
        $this->assertNotContains('pt_2026_8', $keys);
        $this->assertNotContains('fye_2026', $keys);
        $this->assertNotContains('mbr_2026', $keys);
    }
}
