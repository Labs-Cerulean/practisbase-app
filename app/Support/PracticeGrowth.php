<?php

namespace App\Support;

use App\Models\Client;
use App\Models\ClinicalEntry;
use App\Models\Invoice;
use App\Models\Patient;
use Carbon\Carbon;

/**
 * Practice-health metrics for Overview: panel size, return frequency, growth vs time.
 * Uses plaintext dates only (no vault decrypt).
 */
class PracticeGrowth
{
    public const RETURN_WINDOW_DAYS = 90;

    public const ACTIVE_MONTHS = 12;

    /**
     * @return array{
     *   kind: string,
     *   panel_label: string,
     *   panel_size: int,
     *   return_rate: float|null,
     *   return_label: string,
     *   new_this_month: int,
     *   new_last_month: int,
     *   growth_delta: int,
     *   monthly: list<array{label: string, count: int}>
     * }
     */
    public static function forMedical(int $userId): array
    {
        $now = Carbon::now()->startOfDay();
        $activeSince = $now->copy()->subMonths(self::ACTIVE_MONTHS)->toDateString();

        $panelSize = (int) ClinicalEntry::where('user_id', $userId)
            ->whereDate('entry_date', '>=', $activeSince)
            ->select('patient_id')
            ->distinct()
            ->count();

        if ($panelSize === 0) {
            $panelSize = Patient::where('user_id', $userId)->count();
        }

        $byPatient = ClinicalEntry::where('user_id', $userId)
            ->orderBy('entry_date')
            ->get(['patient_id', 'entry_date'])
            ->groupBy('patient_id');

        $eligible = 0;
        $returned = 0;
        $windowEnd = $now->copy()->subDays(self::RETURN_WINDOW_DAYS);

        foreach ($byPatient as $dates) {
            $first = Carbon::parse($dates->first()->entry_date)->startOfDay();
            if ($first->gt($windowEnd)) {
                continue;
            }
            $eligible++;
            $deadline = $first->copy()->addDays(self::RETURN_WINDOW_DAYS);
            foreach ($dates->slice(1) as $entry) {
                $d = Carbon::parse($entry->entry_date)->startOfDay();
                if ($d->gt($first) && $d->lte($deadline)) {
                    $returned++;
                    break;
                }
            }
        }

        $returnRate = $eligible > 0 ? round(100 * $returned / $eligible, 1) : null;

        $monthly = self::monthlyNewCounts(
            Patient::where('user_id', $userId),
            'created_at',
            6
        );

        return self::pack(
            kind: 'patients',
            panelLabel: 'Active patients',
            panelSize: $panelSize,
            returnRate: $returnRate,
            returnLabel: 'Return in 90 days',
            monthly: $monthly,
        );
    }

    /**
     * @return array{
     *   kind: string,
     *   panel_label: string,
     *   panel_size: int,
     *   return_rate: float|null,
     *   return_label: string,
     *   new_this_month: int,
     *   new_last_month: int,
     *   growth_delta: int,
     *   monthly: list<array{label: string, count: int}>
     * }
     */
    public static function forClients(int $userId): array
    {
        $now = Carbon::now()->startOfDay();
        $activeSince = $now->copy()->subMonths(self::ACTIVE_MONTHS)->toDateString();

        $panelSize = (int) Invoice::where('user_id', $userId)
            ->whereNull('parent_document_id')
            ->whereDate('issue_date', '>=', $activeSince)
            ->whereNotNull('client_id')
            ->select('client_id')
            ->distinct()
            ->count();

        if ($panelSize === 0) {
            $panelSize = Client::where('user_id', $userId)->count();
        }

        $byClient = Invoice::where('user_id', $userId)
            ->whereNull('parent_document_id')
            ->whereNotNull('client_id')
            ->orderBy('issue_date')
            ->get(['client_id', 'issue_date'])
            ->groupBy('client_id');

        $eligible = 0;
        $returned = 0;
        $windowEnd = $now->copy()->subDays(self::RETURN_WINDOW_DAYS);

        foreach ($byClient as $dates) {
            $first = Carbon::parse($dates->first()->issue_date)->startOfDay();
            if ($first->gt($windowEnd)) {
                continue;
            }
            $eligible++;
            $deadline = $first->copy()->addDays(self::RETURN_WINDOW_DAYS);
            foreach ($dates->slice(1) as $doc) {
                $d = Carbon::parse($doc->issue_date)->startOfDay();
                if ($d->gt($first) && $d->lte($deadline)) {
                    $returned++;
                    break;
                }
            }
        }

        $returnRate = $eligible > 0 ? round(100 * $returned / $eligible, 1) : null;

        $monthly = self::monthlyNewCounts(
            Client::where('user_id', $userId),
            'created_at',
            6
        );

        return self::pack(
            kind: 'clients',
            panelLabel: 'Active clients',
            panelSize: $panelSize,
            returnRate: $returnRate,
            returnLabel: 'Return in 90 days',
            monthly: $monthly,
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return list<array{label: string, count: int}>
     */
    private static function monthlyNewCounts($query, string $dateColumn, int $months): array
    {
        $out = [];
        $cursor = Carbon::now()->startOfMonth()->subMonths($months - 1);

        for ($i = 0; $i < $months; $i++) {
            $start = $cursor->copy()->addMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $count = (clone $query)
                ->where($dateColumn, '>=', $start)
                ->where($dateColumn, '<=', $end)
                ->count();
            $out[] = [
                'label' => $start->format('M'),
                'count' => $count,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{label: string, count: int}>  $monthly
     * @return array{
     *   kind: string,
     *   panel_label: string,
     *   panel_size: int,
     *   return_rate: float|null,
     *   return_label: string,
     *   new_this_month: int,
     *   new_last_month: int,
     *   growth_delta: int,
     *   monthly: list<array{label: string, count: int}>
     * }
     */
    private static function pack(
        string $kind,
        string $panelLabel,
        int $panelSize,
        ?float $returnRate,
        string $returnLabel,
        array $monthly,
    ): array {
        $thisMonth = $monthly[count($monthly) - 1]['count'] ?? 0;
        $lastMonth = $monthly[count($monthly) - 2]['count'] ?? 0;

        return [
            'kind' => $kind,
            'panel_label' => $panelLabel,
            'panel_size' => $panelSize,
            'return_rate' => $returnRate,
            'return_label' => $returnLabel,
            'new_this_month' => $thisMonth,
            'new_last_month' => $lastMonth,
            'growth_delta' => $thisMonth - $lastMonth,
            'monthly' => $monthly,
        ];
    }
}
