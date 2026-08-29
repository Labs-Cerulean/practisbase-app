<?php

namespace App\Support;

use App\Models\CompanyProfile;
use Illuminate\Support\Carbon;

/**
 * Cerulean Labs Ltd compliance calendar — VAT returns, tax filings, and year-end cues.
 * Advisory dates for the operator desk (confirm statutory windows with your accountant).
 * Events for periods entirely before incorporation / first_period_start are omitted.
 */
class CompanyComplianceCalendar
{
    /**
     * Full-year events (past + upcoming), sorted by due date.
     *
     * @return list<array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string}>
     */
    public static function events(CompanyProfile $profile, ?int $year = null): array
    {
        $year = $year ?: (int) date('Y');
        $today = Carbon::today();
        $events = [];

        $events = array_merge($events, self::vatEvents($profile, $year, $today));
        $events = array_merge($events, self::taxEvents($profile, $year, $today));
        $events = array_merge($events, self::yearEndEvents($profile, $year, $today));

        usort($events, fn ($a, $b) => strcmp($a['due'], $b['due']));

        return $events;
    }

    /**
     * Next items for desk chips (overdue first, then upcoming within ~60 days).
     *
     * @return list<array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string}>
     */
    public static function upcoming(CompanyProfile $profile, int $limit = 8): array
    {
        $today = Carbon::today();
        $horizon = $today->copy()->addDays(60);
        $lookback = $today->copy()->subDays(14);
        $year = (int) $today->format('Y');

        $merged = array_merge(self::events($profile, $year), self::events($profile, $year + 1));
        $seen = [];
        $unique = [];
        foreach ($merged as $event) {
            if (isset($seen[$event['key']])) {
                continue;
            }
            $seen[$event['key']] = true;
            $unique[] = $event;
        }
        usort($unique, fn ($a, $b) => strcmp($a['due'], $b['due']));

        $picked = [];
        foreach ($unique as $event) {
            $due = Carbon::parse($event['due']);
            if ($due->lt($lookback) || $due->gt($horizon)) {
                continue;
            }
            $picked[] = $event;
            if (count($picked) >= $limit) {
                break;
            }
        }

        return $picked;
    }

    private static function companyStart(CompanyProfile $profile): ?Carbon
    {
        if (! $profile->first_period_start) {
            return null;
        }

        return $profile->first_period_start->copy()->startOfDay();
    }

    /**
     * True when the company existed for at least one day of the period
     * (period end on/after incorporation).
     */
    private static function periodTouchesCompany(?Carbon $start, Carbon $periodEnd): bool
    {
        if (! $start) {
            return true;
        }

        return $periodEnd->copy()->startOfDay()->gte($start);
    }

    /**
     * @return list<array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string}>
     */
    private static function vatEvents(CompanyProfile $profile, int $year, Carbon $today): array
    {
        $events = [];
        $freq = $profile->vat_filing_frequency ?: 'quarterly';
        $start = self::companyStart($profile);

        if (! $profile->isArticle10()) {
            return $events;
        }

        if ($freq === 'monthly') {
            for ($m = 1; $m <= 12; $m++) {
                $periodMonth = $m === 1 ? 12 : $m - 1;
                $periodYear = $m === 1 ? $year - 1 : $year;
                $periodEnd = Carbon::create($periodYear, $periodMonth, 1)->endOfMonth()->startOfDay();
                if (! self::periodTouchesCompany($start, $periodEnd)) {
                    continue;
                }
                $due = Carbon::create($year, $m, 15);
                $events[] = self::chip(
                    'vat_m_'.$periodYear.'_'.$periodMonth,
                    'VAT · '.Carbon::create($periodYear, $periodMonth, 1)->format('M Y'),
                    'vat',
                    'Monthly return — around '.$due->format('d M Y').'. Confirm CFR window.',
                    $due,
                    $today,
                    '/company/invoices'
                );
            }

            return $events;
        }

        $quarters = [
            [
                'due' => Carbon::create($year, 2, 15),
                'label' => 'VAT Q4 '.($year - 1),
                'key' => 'vat_q4_'.($year - 1),
                'period_end' => Carbon::create($year - 1, 12, 31),
            ],
            [
                'due' => Carbon::create($year, 5, 15),
                'label' => 'VAT Q1 '.$year,
                'key' => 'vat_q1_'.$year,
                'period_end' => Carbon::create($year, 3, 31),
            ],
            [
                'due' => Carbon::create($year, 8, 15),
                'label' => 'VAT Q2 '.$year,
                'key' => 'vat_q2_'.$year,
                'period_end' => Carbon::create($year, 6, 30),
            ],
            [
                'due' => Carbon::create($year, 11, 15),
                'label' => 'VAT Q3 '.$year,
                'key' => 'vat_q3_'.$year,
                'period_end' => Carbon::create($year, 9, 30),
            ],
            [
                'due' => Carbon::create($year + 1, 2, 15),
                'label' => 'VAT Q4 '.$year,
                'key' => 'vat_q4_'.$year,
                'period_end' => Carbon::create($year, 12, 31),
            ],
        ];

        foreach ($quarters as $q) {
            if (! self::periodTouchesCompany($start, $q['period_end'])) {
                continue;
            }
            $events[] = self::chip(
                $q['key'],
                $q['label'],
                'vat',
                'Quarterly Art 10 return — around '.$q['due']->format('d M Y').'. Confirm CFR window.',
                $q['due'],
                $today,
                '/company'
            );
        }

        return $events;
    }

    /**
     * @return list<array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string}>
     */
    private static function taxEvents(CompanyProfile $profile, int $year, Carbon $today): array
    {
        $events = [];
        $start = self::companyStart($profile);

        // Soft provisional / payment-on-account cues (confirm with accountant for Ltd).
        foreach ([
            [4, 30, 'Provisional tax · Apr'],
            [8, 31, 'Provisional tax · Aug'],
            [12, 21, 'Provisional tax · Dec'],
        ] as [$month, $day, $label]) {
            $due = Carbon::create($year, $month, $day);
            if ($start && $due->lt($start)) {
                continue;
            }
            $events[] = self::chip(
                'pt_'.$year.'_'.$month,
                $label,
                'tax',
                'Company payment on account — confirm amounts and dates with your accountant.',
                $due,
                $today,
                '/company/accounts'
            );
        }

        $fyeMonth = (int) ($profile->financial_year_end_month ?: 12);
        $fyeDay = (int) ($profile->financial_year_end_day ?: 31);
        $fye = Carbon::create($year, $fyeMonth, min($fyeDay, Carbon::create($year, $fyeMonth, 1)->daysInMonth));

        if (! $start || $fye->gte($start)) {
            $taxReturnDue = $fye->copy()->addMonthsNoOverflow(9);
            $events[] = self::chip(
                'ct_return_'.$year,
                'Income tax return · FY '.$year,
                'tax',
                'Target ~9 months after year end ('.$taxReturnDue->format('d M Y').'). Confirm with accountant.',
                $taxReturnDue,
                $today,
                '/company/accounts'
            );
        }

        return $events;
    }

    /**
     * @return list<array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string}>
     */
    private static function yearEndEvents(CompanyProfile $profile, int $year, Carbon $today): array
    {
        $events = [];
        $start = self::companyStart($profile);
        $fyeMonth = (int) ($profile->financial_year_end_month ?: 12);
        $fyeDay = (int) ($profile->financial_year_end_day ?: 31);
        $fye = Carbon::create($year, $fyeMonth, min($fyeDay, Carbon::create($year, $fyeMonth, 1)->daysInMonth));

        if (! $start || $fye->gte($start)) {
            $events[] = self::chip(
                'fye_'.$year,
                'Financial year end',
                'books',
                'Close books for '.$fye->format('d M Y').'. Lock period when ready.',
                $fye,
                $today,
                '/company/accounts'
            );
        }

        if ($profile->first_period_start) {
            $anniversary = $profile->first_period_start->copy()->year($year)->startOfDay();
            // Skip pre-incorporation anniversary clones (e.g. Jul 2025 when formed Jul 2026).
            if (! $start || $anniversary->gte($start)) {
                $events[] = self::chip(
                    'mbr_'.$year,
                    'MBR annual return',
                    'corporate',
                    'Company anniversary cue ('.$anniversary->format('d M').'). Confirm MBR filing deadline.',
                    $anniversary,
                    $today,
                    '/company/profile'
                );
            }
        }

        return $events;
    }

    /**
     * @return array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string}
     */
    private static function chip(
        string $key,
        string $label,
        string $category,
        string $hint,
        Carbon $due,
        Carbon $today,
        string $href
    ): array {
        $overdue = $due->lt($today);
        $urgent = $overdue || $due->diffInDays($today) <= 21;

        return [
            'key' => $key,
            'label' => $label,
            'category' => $category,
            'hint' => $hint,
            'due' => $due->toDateString(),
            'urgent' => $urgent,
            'overdue' => $overdue,
            'href' => $href,
        ];
    }
}
