<?php

namespace Tests\Unit;

use App\Models\CompanyProfile;
use App\Support\CompanyComplianceCalendar;
use Illuminate\Support\Carbon;
use Tests\TestCase;

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
        $this->assertNotContains('vat_q3_2026', $keys);
        $this->assertNotContains('vat_q4_2026', $keys);
        $this->assertNotContains('pt_2026_4', $keys);

        $this->assertContains('vat_first_20260801', $keys);
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

        $vat = $byKey['vat_first_20260801'];
        $this->assertSame('filing', $vat['severity']);
        $this->assertFalse($vat['overdue']);
        $this->assertSame('2026-08-01', $vat['period_from']);
        $this->assertSame('2027-08-31', $vat['period_to']);
        $this->assertSame('2027-10-15', $vat['due']);
        $this->assertStringContainsString('1 August 2026', $vat['label']);
        $this->assertStringContainsString('31 August 2027', $vat['label']);
        $this->assertStringContainsString('15 October 2027', $vat['hint']);
        $this->assertStringNotContainsString('Q3 2026', $vat['label'].$vat['hint']);

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
        $this->assertNotContains('vat_q3_2026', $keys);
        $this->assertNotContains('pt_2026_8', $keys);
        $this->assertNotContains('fye_2026', $keys);
        $this->assertNotContains('mbr_2026', $keys);
    }

    public function test_21_november_2026_desk_shows_the_assigned_period_and_not_an_overdue_q3(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 21));
        $profile = $this->ceruleanProfile();

        $desk = CompanyComplianceCalendar::deskDeadlines($profile, 8);
        $vat = null;
        foreach ($desk as $item) {
            $this->assertNotSame('vat_q3_2026', $item['key']);
            $blob = $item['label'].' '.$item['hint'];
            $this->assertStringNotContainsString('Q3 2026', $blob);
            $this->assertStringNotContainsString('overdue', strtolower($blob));
            if ($item['key'] === 'vat_first_20260801') {
                $vat = $item;
            }
        }

        $this->assertNotNull($vat);
        $this->assertFalse($vat['overdue']);
        $this->assertFalse($vat['urgent']);
        $this->assertSame('2026-08-01', $vat['period_from']);
        $this->assertSame('2027-08-31', $vat['period_to']);
        $this->assertSame('2027-10-15', $vat['due']);
        $this->assertStringContainsString('1 August 2026', $vat['label']);
        $this->assertStringContainsString('31 August 2027', $vat['label']);
        $this->assertStringContainsString('15 October 2027', $vat['hint']);
        $this->assertStringContainsString('29–31 July 2026', $vat['hint']);
        $this->assertStringContainsString('Open proformas stay off the return', $vat['hint']);

        $keys2026 = array_column(CompanyComplianceCalendar::events($profile, 2026), 'key');
        $this->assertContains('vat_first_20260801', $keys2026);
        $this->assertNotContains('vat_q3_2026', $keys2026);
        $this->assertNotContains('vat_q4_2026', $keys2026);

        $keys2027 = array_column(CompanyComplianceCalendar::events($profile, 2027), 'key');
        $this->assertContains('vat_first_20260801', $keys2027);
        $this->assertNotContains('vat_q1_2027', $keys2027);
        $this->assertNotContains('vat_q2_2027', $keys2027);
        $this->assertNotContains('vat_q3_2027', $keys2027);
        $this->assertContains('vat_q4_2027', $keys2027);

        $upcoming = array_column(CompanyComplianceCalendar::upcoming($profile, 12), 'key');
        $this->assertNotContains('vat_q3_2026', $upcoming);
    }

    public function test_assigned_period_is_due_soon_only_inside_three_weeks_and_overdue_after_the_date(): void
    {
        $profile = $this->ceruleanProfile();

        Carbon::setTestNow(Carbon::create(2027, 10, 1));
        $soon = CompanyComplianceCalendar::assignedFiling($profile);
        $this->assertFalse($soon['overdue']);
        $this->assertTrue($soon['urgent']);
        $this->assertSame('due_soon', $soon['status']);

        Carbon::setTestNow(Carbon::create(2027, 10, 15));
        $dueToday = CompanyComplianceCalendar::assignedFiling($profile);
        $this->assertFalse($dueToday['overdue']);
        $this->assertTrue($dueToday['urgent']);

        Carbon::setTestNow(Carbon::create(2027, 10, 16));
        $late = CompanyComplianceCalendar::assignedFiling($profile);
        $this->assertTrue($late['overdue']);
        $this->assertSame('overdue', $late['status']);
    }

    public function test_monthly_frequency_does_not_revive_months_inside_the_assigned_period(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 21));
        $profile = $this->ceruleanProfile();
        $profile->vat_filing_frequency = 'monthly';

        $keys = array_column(CompanyComplianceCalendar::events($profile, 2026), 'key');

        $this->assertNotContains('vat_m_2026_7', $keys);
        $this->assertNotContains('vat_m_2026_8', $keys);
        $this->assertNotContains('vat_m_2026_11', $keys);
        $this->assertContains('vat_first_20260801', $keys);
    }

    public function test_article_11_has_no_assigned_vat_period(): void
    {
        $profile = $this->ceruleanProfile();
        $profile->vat_status = 'article_11';

        $this->assertNull(CompanyComplianceCalendar::assignedFiling($profile));
        $keys = array_column(CompanyComplianceCalendar::events($profile, 2026), 'key');
        $this->assertNotContains('vat_first_20260801', $keys);
        $this->assertNotContains('vat_q3_2026', $keys);
    }
}
