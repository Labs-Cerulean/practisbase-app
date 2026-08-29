<?php

namespace App\Support;

use App\Models\CompanyProfile;
use Illuminate\Support\Carbon;

/**
 * Cerulean Labs Ltd compliance calendar — VAT returns, tax filings, and year-end cues.
 * Advisory dates for the operator desk (confirm statutory windows with your accountant).
 *
 * Year-1 aware: skips pre-incorporation periods, omits provisional tax alarms with no
 * prior-year base, delays MBR to the first anniversary year, and treats FYE as a books
 * cutoff (not a filing deadline).
 */
class CompanyComplianceCalendar
{
    /**
     * Full-year events (past + upcoming), sorted by due date.
     *
     * @return list<array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string, severity: string, status: string}>
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
     * Next actionable filings for desk chips (info / Year-1 pass items excluded).
     *
     * @return list<array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string, severity: string, status: string}>
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
            if (($event['severity'] ?? 'filing') !== 'filing') {
                continue;
            }
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

    private static function firstPeriodEnd(CompanyProfile $profile): ?Carbon
    {
        if ($profile->first_period_end) {
            return $profile->first_period_end->copy()->startOfDay();
        }

        $start = self::companyStart($profile);
        if (! $start) {
            return null;
        }

        $fyeMonth = (int) ($profile->financial_year_end_month ?: 12);
        $fyeDay = (int) ($profile->financial_year_end_day ?: 31);
        $end = Carbon::create($start->year, $fyeMonth, min($fyeDay, Carbon::create($start->year, $fyeMonth, 1)->daysInMonth));

        return $end->gte($start) ? $end->startOfDay() : $end->addYear()->startOfDay();
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
     * @return list<array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string, severity: string, status: string}>
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
                $periodStart = Carbon::create($periodYear, $periodMonth, 1)->startOfDay();
                $periodEnd = $periodStart->copy()->endOfMonth()->startOfDay();
                if (! self::periodTouchesCompany($start, $periodEnd)) {
                    continue;
                }
                $due = Carbon::create($year, $m, 15);
                $partial = $start && $start->gt($periodStart) && $start->lte($periodEnd);
                $hint = $partial
                    ? 'First VAT month (from incorporation). File via MTCA even if nil / input-only reclaim.'
                    : 'Monthly return — around '.$due->format('d M Y').'. Confirm CFR window.';
                $events[] = self::chip(
                    'vat_m_'.$periodYear.'_'.$periodMonth,
                    'VAT · '.Carbon::create($periodYear, $periodMonth, 1)->format('M Y'),
                    'vat',
                    $hint,
                    $due,
                    $today,
                    '/company/invoices',
                    'filing'
                );
            }

            return $events;
        }

        $quarters = [
            [
                'due' => Carbon::create($year, 2, 15),
                'label' => 'VAT Q4 '.($year - 1),
                'key' => 'vat_q4_'.($year - 1),
                'period_start' => Carbon::create($year - 1, 10, 1),
                'period_end' => Carbon::create($year - 1, 12, 31),
            ],
            [
                'due' => Carbon::create($year, 5, 15),
                'label' => 'VAT Q1 '.$year,
                'key' => 'vat_q1_'.$year,
                'period_start' => Carbon::create($year, 1, 1),
                'period_end' => Carbon::create($year, 3, 31),
            ],
            [
                'due' => Carbon::create($year, 8, 15),
                'label' => 'VAT Q2 '.$year,
                'key' => 'vat_q2_'.$year,
                'period_start' => Carbon::create($year, 4, 1),
                'period_end' => Carbon::create($year, 6, 30),
            ],
            [
                'due' => Carbon::create($year, 11, 15),
                'label' => 'VAT Q3 '.$year,
                'key' => 'vat_q3_'.$year,
                'period_start' => Carbon::create($year, 7, 1),
                'period_end' => Carbon::create($year, 9, 30),
            ],
            [
                'due' => Carbon::create($year + 1, 2, 15),
                'label' => 'VAT Q4 '.$year,
                'key' => 'vat_q4_'.$year,
                'period_start' => Carbon::create($year, 10, 1),
                'period_end' => Carbon::create($year, 12, 31),
            ],
        ];

        foreach ($quarters as $q) {
            if (! self::periodTouchesCompany($start, $q['period_end'])) {
                continue;
            }
            $partial = $start
                && $start->gt($q['period_start']->copy()->startOfDay())
                && $start->lte($q['period_end']->copy()->startOfDay());
            $hint = $partial
                ? 'First VAT return (partial quarter from incorporation). File via MTCA even if nil / mainly input VAT reclaim on startup costs.'
                : 'Quarterly Art 10 return — around '.$q['due']->format('d M Y').'. Confirm CFR window.';
            $events[] = self::chip(
                $q['key'],
                $q['label'],
                'vat',
                $hint,
                $q['due'],
                $today,
                '/company',
                'filing'
            );
        }

        return $events;
    }

    /**
     * @return list<array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string, severity: string, status: string}>
     */
    private static function taxEvents(CompanyProfile $profile, int $year, Carbon $today): array
    {
        $events = [];
        $start = self::companyStart($profile);
        $firstEnd = self::firstPeriodEnd($profile);

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

            // Year 1 / no prior-year profits: PT base is €0 — show as note, not an alarm.
            $year1Pass = $firstEnd && $due->lte($firstEnd);
            if ($year1Pass) {
                $events[] = self::chip(
                    'pt_'.$year.'_'.$month,
                    $label.' (Year 1 · €0)',
                    'tax',
                    'No prior-year profits — PT liability is €0. If MTCA mails a form, return it with zeros. Not an actionable payment.',
                    $due,
                    $today,
                    '/company/accounts',
                    'note'
                );
                continue;
            }

            $events[] = self::chip(
                'pt_'.$year.'_'.$month,
                $label,
                'tax',
                'Company payment on account based on prior-year profits — confirm amounts and dates with your accountant.',
                $due,
                $today,
                '/company/accounts',
                'filing'
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
                'Statutory target ~9 months after year end ('.$taxReturnDue->format('d M Y').'). Micro-entities may file from the ledger without an audit — confirm eligibility with your accountant.',
                $taxReturnDue,
                $today,
                '/company/accounts',
                'filing'
            );
        }

        return $events;
    }

    /**
     * @return list<array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string, severity: string, status: string}>
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
                'Financial year end (books cutoff)',
                'books',
                'Not a filing deadline. Close/lock the '.$fye->format('Y').' ledger when ready — post-cutoff invoices and expenses belong to the next year.',
                $fye,
                $today,
                '/company/accounts',
                'info'
            );
        }

        if ($profile->first_period_start) {
            $anniversary = $profile->first_period_start->copy()->year($year)->startOfDay();
            // First MBR annual return is the following anniversary year — not the incorporation month.
            $firstMbr = $start ? $start->copy()->addYear() : null;
            if ($firstMbr && $anniversary->gte($firstMbr)) {
                $events[] = self::chip(
                    'mbr_'.$year,
                    'MBR annual return',
                    'corporate',
                    'First annual return cue around '.$anniversary->format('d M Y').' (not due in the incorporation month). Confirm the exact MBR window.',
                    $anniversary,
                    $today,
                    '/company/profile',
                    'filing'
                );
            }
        }

        return $events;
    }

    /**
     * @return array{key: string, label: string, category: string, hint: string, due: string, urgent: bool, overdue: bool, href: string, severity: string, status: string}
     */
    private static function chip(
        string $key,
        string $label,
        string $category,
        string $hint,
        Carbon $due,
        Carbon $today,
        string $href,
        string $severity = 'filing'
    ): array {
        $isFiling = $severity === 'filing';
        $overdue = $isFiling && $due->lt($today);
        $urgent = $isFiling && ($overdue || $due->diffInDays($today) <= 21);
        $status = match (true) {
            $severity === 'note' => 'year1_pass',
            $severity === 'info' => 'info',
            $overdue => 'overdue',
            $urgent => 'due_soon',
            default => 'scheduled',
        };

        return [
            'key' => $key,
            'label' => $label,
            'category' => $category,
            'hint' => $hint,
            'due' => $due->toDateString(),
            'urgent' => $urgent,
            'overdue' => $overdue,
            'href' => $href,
            'severity' => $severity,
            'status' => $status,
        ];
    }
}
