<?php

namespace App\Support;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use App\Models\UserRegimeSegment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Effective-dated sole-trader tax regime history.
 * users.* remains the current tip; segments drive open-year math by document date.
 */
class RegimeHistory
{
    public const REGIME_KEYS = [
        'vat_status',
        'employment_type',
        'max_ssc_paid',
        'primary_salary',
        'tax_computation',
    ];

    /**
     * @return array{vat_status: string, employment_type: string, max_ssc_paid: bool, primary_salary: float, tax_computation: string}
     */
    public static function tipFromUser(User $user): array
    {
        return [
            'vat_status' => (string) ($user->vat_status ?: 'exempt'),
            'employment_type' => (string) ($user->employment_type ?: 'full_time'),
            'max_ssc_paid' => (bool) $user->max_ssc_paid,
            'primary_salary' => (float) ($user->primary_salary ?? 0),
            'tax_computation' => (string) ($user->tax_computation ?: 'single'),
        ];
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    public static function regimeChanged(array $a, array $b): bool
    {
        foreach (self::REGIME_KEYS as $key) {
            if ($key === 'primary_salary') {
                if (round((float) ($a[$key] ?? 0), 2) !== round((float) ($b[$key] ?? 0), 2)) {
                    return true;
                }
                continue;
            }
            if ($key === 'max_ssc_paid') {
                if ((bool) ($a[$key] ?? false) !== (bool) ($b[$key] ?? false)) {
                    return true;
                }
                continue;
            }
            if ((string) ($a[$key] ?? '') !== (string) ($b[$key] ?? '')) {
                return true;
            }
        }

        return false;
    }

    public static function ensureBaseline(User $user): void
    {
        if (UserRegimeSegment::where('user_id', $user->id)->exists()) {
            return;
        }

        $from = self::defaultBaselineDate($user);
        UserRegimeSegment::create(array_merge(self::tipFromUser($user), [
            'user_id' => $user->id,
            'effective_from' => $from,
        ]));
    }

    public static function defaultBaselineDate(User $user): string
    {
        $candidates = [];
        if ($user->created_at) {
            $candidates[] = $user->created_at->toDateString();
        }

        $earliestInvoice = Invoice::where('user_id', $user->id)->orderBy('issue_date')->value('issue_date');
        if ($earliestInvoice) {
            $candidates[] = Carbon::parse($earliestInvoice)->toDateString();
        }

        $earliestExpense = Expense::where('user_id', $user->id)->orderBy('expense_date')->value('expense_date');
        if ($earliestExpense) {
            $candidates[] = Carbon::parse($earliestExpense)->toDateString();
        }

        if ($candidates === []) {
            return now()->toDateString();
        }

        return min($candidates);
    }

    /**
     * Persist a regime change from an effective date (half-open until the next segment).
     * Call ensureBaseline with the OLD tip before updating users.*.
     *
     * @param  array{vat_status: string, employment_type: string, max_ssc_paid: bool, primary_salary: float, tax_computation: string}  $regime
     */
    public static function applyChange(User $user, array $regime, string $effectiveFrom): void
    {
        self::ensureBaseline($user);

        UserRegimeSegment::updateOrCreate(
            [
                'user_id' => $user->id,
                'effective_from' => $effectiveFrom,
            ],
            [
                'vat_status' => $regime['vat_status'],
                'employment_type' => $regime['employment_type'],
                'max_ssc_paid' => (bool) $regime['max_ssc_paid'],
                'primary_salary' => (float) $regime['primary_salary'],
                'tax_computation' => $regime['tax_computation'] ?: 'single',
            ]
        );
    }

    /**
     * @return array{vat_status: string, employment_type: string, max_ssc_paid: bool, primary_salary: float, tax_computation: string}
     */
    public static function forDate(User $user, string|\DateTimeInterface $date): array
    {
        $day = $date instanceof \DateTimeInterface
            ? Carbon::parse($date->format('Y-m-d'))->toDateString()
            : Carbon::parse($date)->toDateString();

        $segment = UserRegimeSegment::where('user_id', $user->id)
            ->where('effective_from', '<=', $day)
            ->orderByDesc('effective_from')
            ->first();

        if ($segment) {
            return $segment->toRegimeArray();
        }

        return self::tipFromUser($user);
    }

    /**
     * Ordered half-open windows covering a calendar year.
     *
     * @return list<array{from: string, to: string, regime: array{vat_status: string, employment_type: string, max_ssc_paid: bool, primary_salary: float, tax_computation: string}}>
     */
    public static function windowsForYear(User $user, int $year): array
    {
        $yearStart = sprintf('%04d-01-01', $year);
        $yearEnd = sprintf('%04d-12-31', $year);

        /** @var Collection<int, UserRegimeSegment> $segments */
        $segments = UserRegimeSegment::where('user_id', $user->id)
            ->orderBy('effective_from')
            ->get();

        if ($segments->isEmpty()) {
            return [[
                'from' => $yearStart,
                'to' => $yearEnd,
                'regime' => self::tipFromUser($user),
            ]];
        }

        $applicable = $segments->filter(fn (UserRegimeSegment $s) => $s->effective_from->toDateString() <= $yearEnd)->values();
        if ($applicable->isEmpty()) {
            return [[
                'from' => $yearStart,
                'to' => $yearEnd,
                'regime' => self::tipFromUser($user),
            ]];
        }

        $windows = [];
        foreach ($applicable as $i => $segment) {
            $start = max($yearStart, $segment->effective_from->toDateString());
            $next = $applicable->get($i + 1);
            if ($next && $next->effective_from->toDateString() <= $yearEnd) {
                $end = Carbon::parse($next->effective_from)->subDay()->toDateString();
            } else {
                $end = $yearEnd;
            }

            if ($start > $end) {
                continue;
            }
            if ($start < $yearStart) {
                $start = $yearStart;
            }

            $windows[] = [
                'from' => $start,
                'to' => $end,
                'regime' => $segment->toRegimeArray(),
            ];
        }

        // Cover Jan 1 if the first applicable segment starts mid-year but an earlier tip existed
        // (handled by ensureBaseline). If first window starts after yearStart, prepend tip from
        // the latest segment before yearStart.
        if ($windows !== [] && $windows[0]['from'] > $yearStart) {
            $before = $segments->filter(fn (UserRegimeSegment $s) => $s->effective_from->toDateString() < $yearStart)->last();
            $regime = $before ? $before->toRegimeArray() : self::tipFromUser($user);
            array_unshift($windows, [
                'from' => $yearStart,
                'to' => Carbon::parse($windows[0]['from'])->subDay()->toDateString(),
                'regime' => $regime,
            ]);
        }

        return $windows !== [] ? $windows : [[
            'from' => $yearStart,
            'to' => $yearEnd,
            'regime' => self::tipFromUser($user),
        ]];
    }

    /**
     * @return list<array{effective_from: string, vat_status: string, employment_type: string, max_ssc_paid: bool, primary_salary: float, tax_computation: string}>
     */
    public static function listForUser(User $user): array
    {
        return UserRegimeSegment::where('user_id', $user->id)
            ->orderByDesc('effective_from')
            ->get()
            ->map(fn (UserRegimeSegment $s) => array_merge($s->toRegimeArray(), [
                'effective_from' => $s->effective_from->toDateString(),
            ]))
            ->all();
    }
}
