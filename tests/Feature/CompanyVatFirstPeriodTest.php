<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Support\CompanyBooks;
use App\Support\CompanyComplianceCalendar;
use App\Support\CompanyDeskReport;
use App\Support\CompanyVatReturn;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CompanyVatFirstPeriodTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_desk_and_full_calendar_show_the_assigned_period_on_21_november_2026(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 21));
        $profile = new CompanyProfile([
            'legal_name' => 'Cerulean Labs Limited',
            'registration_number' => 'C 116764',
            'vat_status' => 'article_10',
            'vat_filing_frequency' => 'quarterly',
            'financial_year_end_month' => 12,
            'financial_year_end_day' => 31,
            'first_period_start' => '2026-07-29',
            'first_period_end' => '2026-12-31',
            'share_capital_eur' => 1200,
        ]);

        $assigned = CompanyComplianceCalendar::assignedFiling($profile);
        $events = CompanyComplianceCalendar::events($profile, 2026);
        $filing = null;
        foreach ($events as $event) {
            if ($event['key'] === 'vat_first_20260801') {
                $filing = $event;
                break;
            }
        }
        $this->assertNotNull($filing);

        $window = CompanyVatReturn::activityWindow(
            Carbon::parse($filing['period_from']),
            Carbon::parse($filing['period_to']),
            Carbon::parse('2026-07-29'),
            Carbon::parse('2026-11-21')
        );
        $vatReturn = array_merge(CompanyVatReturn::fromTotals(0, 0, 0, 0, 0, 0, 0, 1, 118), [
            'key' => $filing['key'],
            'label' => $filing['label'],
            'due' => $filing['due'],
            'hint' => $filing['hint'],
            'from' => $window['from']->toDateString(),
            'to' => $window['to']->toDateString(),
            'period_from' => $filing['period_from'],
            'period_to' => $filing['period_to'],
            'period_open' => $window['open'],
            'first_return' => true,
            'assigned_first' => true,
            'sales_count' => 0,
            'credit_count' => 0,
            'expense_count' => 0,
        ]);

        $desk = view('company.desk', [
            'profile' => $profile,
            'periodLabel' => CompanyBooks::periodLabel($profile),
            'netBilled' => 0,
            'collected' => 0,
            'expensesExVat' => 0,
            'outputVat' => 0,
            'inputVat' => 0,
            'reverseChargeVat' => 0,
            'vatBalance' => 0,
            'owedToDirector' => 0,
            'openRfps' => 1,
            'monthBilled' => 0,
            'year' => 2026,
            'netProfit' => 0,
            'booksBalanced' => true,
            'bankBalance' => 0,
            'liquidFunds' => [
                'available' => 0,
                'share_capital' => 0,
                'lines' => [],
            ],
            'complianceUpcoming' => CompanyComplianceCalendar::deskDeadlines($profile, 8),
            'assignedVat' => $assigned,
            'deskReport' => CompanyDeskReport::build([], [], [], '2026-11-21'),
        ])->render();

        $this->assertStringContainsString('1 August 2026', $desk);
        $this->assertStringContainsString('31 August 2027', $desk);
        $this->assertStringContainsString('15 October 2027', $desk);
        $this->assertStringContainsString('29 Jul 2026', $desk);
        $this->assertStringContainsString('VAT number not on file yet', $desk);
        $this->assertStringNotContainsString('Q3 2026', $desk);
        $this->assertStringNotContainsString('· overdue', $desk);
        $this->assertStringNotContainsString('vat_number', $desk);

        $calendar = view('company.compliance', [
            'profile' => $profile,
            'year' => 2026,
            'events' => $events,
            'upcoming' => CompanyComplianceCalendar::upcoming($profile, 12),
            'vatReturn' => $vatReturn,
        ])->render();

        $this->assertStringContainsString('1 August 2026', $calendar);
        $this->assertStringContainsString('31 August 2027', $calendar);
        $this->assertStringContainsString('15 October 2027', $calendar);
        $this->assertStringContainsString('29–31 July 2026 sit outside it', $calendar);
        $this->assertStringContainsString('are left off the return', $calendar);
        $this->assertStringContainsString('does not send a return to MTCA', $calendar);
        $this->assertStringNotContainsString('Q3 2026', $calendar);
        $this->assertStringNotContainsString('· overdue', $calendar);
        $this->assertStringNotContainsString('15 Nov', $calendar);
    }
}
