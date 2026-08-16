<?php

namespace App\Support;

use App\Models\BetaInviteCode;
use App\Models\Promotion;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PractisBase SaaS KPIs for the company operator login only.
 * Excludes company_books staff and users marked exclude_from_kpis (test accounts).
 * Users marked exclude_from_mrr (beta) stay in counts but are omitted from list MRR.
 */
class PlatformKpi
{
    public static function snapshot(): array
    {
        $now = now();
        $day7 = $now->copy()->subDays(7);
        $day30 = $now->copy()->subDays(30);
        $day14 = $now->copy()->addDays(14);

        $users = self::endUsersQuery();
        $excludedCount = self::cohortCount('test');
        $betaCount = self::cohortCount('beta');

        $total = (clone $users)->count();
        $free = (clone $users)->where(function ($q) {
            $q->whereNull('tier')->orWhere('tier', TierPolicy::TIER_FREE);
        })->count();
        $paid = max(0, $total - $free);

        $signups7 = (clone $users)->where('created_at', '>=', $day7)->count();
        $signups30 = (clone $users)->where('created_at', '>=', $day30)->count();

        $onboarded = (clone $users)
            ->whereNotNull('profession')
            ->where('profession', '!=', '')
            ->whereNotNull('employment_type')
            ->where('employment_type', '!=', '')
            ->whereNotNull('tier')
            ->where('tier', '!=', '')
            ->count();

        $termsAccepted = (clone $users)->whereNotNull('terms_accepted_at')->count();

        $betaUnlocked = (clone $users)->whereNotNull('beta_invite_code_id')->count();
        $onTrial = (clone $users)->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', $now)->count();
        $trialExpiring = (clone $users)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', $now)
            ->where('trial_ends_at', '<=', $day14)
            ->count();
        $promoApplied = (clone $users)->whereNotNull('applied_promotion_id')->count();
        $creditBalance = (float) (clone $users)->sum('credit_balance');

        $backupOverdue = (clone $users)->where(function ($q) use ($day7) {
            $q->whereNull('last_data_backup_at')
                ->orWhere('last_data_backup_at', '<', $day7);
        })->count();

        $byTier = (clone $users)
            ->selectRaw("COALESCE(NULLIF(tier, ''), 'free') as tier_key, COUNT(*) as total")
            ->groupBy('tier_key')
            ->pluck('total', 'tier_key')
            ->all();

        $byProfession = (clone $users)
            ->selectRaw("COALESCE(NULLIF(profession, ''), 'Unset') as profession_key, COUNT(*) as total")
            ->groupBy('profession_key')
            ->orderByDesc('total')
            ->pluck('total', 'profession_key')
            ->all();

        $byTierMrr = self::mrrUsersQuery()
            ->selectRaw("COALESCE(NULLIF(tier, ''), 'free') as tier_key, COUNT(*) as total")
            ->groupBy('tier_key')
            ->pluck('total', 'tier_key')
            ->all();

        $tierCards = [];
        $listMrr = 0.0;
        foreach (TierPolicy::allTiers() as $tier) {
            $count = (int) ($byTier[$tier] ?? 0);
            $mrrCount = (int) ($byTierMrr[$tier] ?? 0);
            $unit = self::listPriceExVat($tier);
            $tierMrr = round($mrrCount * $unit, 2);
            $tierCards[] = [
                'tier' => $tier,
                'label' => TierPolicy::label($tier),
                'count' => $count,
                'mrr_count' => $mrrCount,
                'unit_price' => $unit,
                'list_mrr' => $tierMrr,
            ];
            $listMrr += $tierMrr;
        }
        foreach ($byTier as $tier => $count) {
            if (! in_array($tier, TierPolicy::allTiers(), true)) {
                $tierCards[] = [
                    'tier' => $tier,
                    'label' => $tier,
                    'count' => (int) $count,
                    'mrr_count' => (int) ($byTierMrr[$tier] ?? 0),
                    'unit_price' => 0.0,
                    'list_mrr' => 0.0,
                ];
            }
        }

        $active7 = self::activeUserCount(7);
        $active30 = self::activeUserCount(30);

        $usage = [
            'with_clients' => self::usersWithRows('clients'),
            'with_invoices' => self::usersWithRows('invoices'),
            'with_expenses' => self::usersWithRows('expenses'),
            'with_stamps' => self::usersWithRows('document_stamps'),
            'with_patients' => self::usersWithRows('patients'),
            'with_arch_projects' => self::usersWithRows('architect_projects'),
            'with_eng_projects' => self::usersWithRows('engineer_projects'),
            'with_certificates' => self::usersWithRows('certificates'),
        ];

        $volume = [
            'clients' => self::safeCount('clients'),
            'invoices' => self::safeCount('invoices', "type = 'invoice'"),
            'rfps' => self::safeCount('invoices', "type = 'rfp'"),
            'expenses' => self::safeCount('expenses'),
            'patients' => self::safeCount('patients'),
            'clinical_issued' => self::safeCount('clinical_entries', 'issued_at is not null'),
            'arch_projects' => self::safeCount('architect_projects'),
            'eng_projects' => self::safeCount('engineer_projects'),
            'certificates_stamped' => self::safeCount('certificates', 'stamped_at is not null'),
            'document_stamps' => self::safeCount('document_stamps'),
            'feedback_open' => self::safeCount('community_feedback', "status in ('open','acknowledged','in_progress')"),
        ];

        $nowSql = $now->toDateTimeString();
        $betaCodes = BetaInviteCode::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN redeemed_at IS NOT NULL THEN 1 ELSE 0 END) as redeemed')
            ->selectRaw('SUM(CASE WHEN revoked_at IS NOT NULL THEN 1 ELSE 0 END) as revoked')
            ->selectRaw("SUM(CASE WHEN redeemed_at IS NULL AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at > ?) THEN 1 ELSE 0 END) as available", [$nowSql])
            ->first();

        $promotions = Promotion::query()
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->get()
            ->map(function (Promotion $p) {
                $max = $p->max_uses;
                $used = (int) $p->current_uses;

                return [
                    'code' => $p->code,
                    'label' => $p->label ?: $p->code,
                    'type' => $p->type,
                    'value' => $p->value,
                    'used' => $used,
                    'max' => $max,
                    'remaining' => $max === null ? null : max(0, (int) $max - $used),
                    'is_active' => (bool) $p->is_active,
                    'expires_at' => optional($p->expires_at)->toDateString(),
                ];
            })
            ->all();

        $referrals = [
            'pending_payment' => Referral::query()->where('status', 'pending_payment')->count(),
            'rewarded' => Referral::query()->where('status', 'rewarded')->count(),
        ];

        return [
            'generated_at' => $now,
            'totals' => [
                'users' => $total,
                'free' => $free,
                'paid' => $paid,
                'signups_7d' => $signups7,
                'signups_30d' => $signups30,
                'onboarded' => $onboarded,
                'onboarding_rate' => $total > 0 ? round(100 * $onboarded / $total, 1) : 0.0,
                'terms_accepted' => $termsAccepted,
                'active_7d' => $active7,
                'active_30d' => $active30,
                'backup_overdue' => $backupOverdue,
                'excluded_test_users' => $excludedCount,
                'beta_users' => $betaCount,
            ],
            'access' => [
                'beta_unlocked' => $betaUnlocked,
                'on_trial' => $onTrial,
                'trial_expiring_14d' => $trialExpiring,
                'promo_applied' => $promoApplied,
                'credit_balance' => $creditBalance,
                'list_mrr_ex_vat' => round($listMrr, 2),
                'list_mrr_inc_vat' => round($listMrr * (1 + ((float) TierPolicy::VAT_RATE_PERCENT / 100)), 2),
                'stripe_live' => false,
            ],
            'tier_cards' => $tierCards,
            'by_profession' => $byProfession,
            'usage' => $usage,
            'volume' => $volume,
            'beta_codes' => [
                'total' => (int) ($betaCodes->total ?? 0),
                'redeemed' => (int) ($betaCodes->redeemed ?? 0),
                'revoked' => (int) ($betaCodes->revoked ?? 0),
                'available' => (int) ($betaCodes->available ?? 0),
            ],
            'promotions' => $promotions,
            'referrals' => $referrals,
        ];
    }

    public static function usersTable(int $perPage = 40, string $view = 'all'): LengthAwarePaginator
    {
        $view = in_array($view, ['all', 'counted', 'beta', 'test'], true) ? $view : 'all';

        $activityMap = [];
        if (self::hasSessionsTable()) {
            $activityMap = DB::table('sessions')
                ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->pluck('last_activity', 'user_id')
                ->all();
        }

        $query = User::query()
            ->where(function ($q) {
                $q->where('company_books_enabled', false)->orWhereNull('company_books_enabled');
            });

        if ($view === 'counted') {
            self::applyCountedScope($query);
            if (self::hasMrrColumn()) {
                $query->where(function ($q) {
                    $q->where('exclude_from_mrr', false)->orWhereNull('exclude_from_mrr');
                });
            }
        } elseif ($view === 'beta') {
            self::applyCountedScope($query);
            if (self::hasMrrColumn()) {
                $query->where('exclude_from_mrr', true);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($view === 'test') {
            if (self::hasExcludeColumn()) {
                $query->where('exclude_from_kpis', true);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query
            ->with(['appliedPromotion:id,code', 'betaInviteCode:id,code,pro_package'])
            ->withCount([
                'clients',
                'invoices as invoices_count' => fn ($q) => $q->where('type', 'invoice'),
                'expenses',
            ])
            ->when(self::hasExcludeColumn(), fn ($q) => $q->orderByDesc('exclude_from_kpis'))
            ->when(self::hasMrrColumn(), fn ($q) => $q->orderByDesc('exclude_from_mrr'))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->appends(['view' => $view])
            ->through(function (User $user) use ($activityMap) {
                $tier = TierPolicy::normalize($user->tier);
                $access = 'Free';
                if ($user->beta_invite_code_id) {
                    $access = 'Access code';
                } elseif ($user->trial_ends_at && $user->trial_ends_at > now()) {
                    $access = 'Trial';
                } elseif ($tier !== TierPolicy::TIER_FREE) {
                    $access = 'Plan selected (unbilled)';
                }

                $lastTs = $activityMap[$user->id] ?? null;
                $isTest = self::hasExcludeColumn() && (bool) $user->exclude_from_kpis;
                $isBeta = ! $isTest && self::hasMrrColumn() && (bool) $user->exclude_from_mrr;
                $cohort = $isTest ? 'test' : ($isBeta ? 'beta' : 'counted');
                $unit = self::listPriceExVat($tier);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tier' => $tier,
                    'tier_label' => TierPolicy::label($tier),
                    'profession' => $user->profession ?: '—',
                    'created_at' => $user->created_at,
                    'last_activity' => $lastTs
                        ? \Carbon\Carbon::createFromTimestamp((int) $lastTs)
                        : null,
                    'clients' => (int) $user->clients_count,
                    'invoices' => (int) $user->invoices_count,
                    'expenses' => (int) $user->expenses_count,
                    'access' => $access,
                    'promo_code' => $user->appliedPromotion?->code,
                    'beta_code' => $user->betaInviteCode?->code,
                    'credit_balance' => (float) $user->credit_balance,
                    'trial_ends_at' => $user->trial_ends_at,
                    'list_price' => ($cohort === 'counted') ? $unit : 0.0,
                    'plan_price' => $unit,
                    'backup_overdue' => ! $user->last_data_backup_at || $user->last_data_backup_at < now()->subDays(7),
                    'cohort' => $cohort,
                    'exclude_from_kpis' => $isTest,
                    'exclude_from_mrr' => $isBeta || $isTest,
                ];
            });
    }

    private static function endUsersQuery()
    {
        $q = User::query()->where(function ($q) {
            $q->where('company_books_enabled', false)->orWhereNull('company_books_enabled');
        });

        return self::applyCountedScope($q);
    }

    private static function mrrUsersQuery()
    {
        $q = self::endUsersQuery();
        if (self::hasMrrColumn()) {
            $q->where(function ($q) {
                $q->where('exclude_from_mrr', false)->orWhereNull('exclude_from_mrr');
            });
        }

        return $q;
    }

    private static function cohortCount(string $cohort): int
    {
        $q = User::query()->where(function ($q) {
            $q->where('company_books_enabled', false)->orWhereNull('company_books_enabled');
        });

        if ($cohort === 'test') {
            if (! self::hasExcludeColumn()) {
                return 0;
            }

            return (int) $q->where('exclude_from_kpis', true)->count();
        }

        if ($cohort === 'beta') {
            if (! self::hasMrrColumn()) {
                return 0;
            }
            self::applyCountedScope($q);

            return (int) $q->where('exclude_from_mrr', true)->count();
        }

        return 0;
    }

    private static function applyCountedScope($query)
    {
        if (self::hasExcludeColumn()) {
            $query->where(function ($q) {
                $q->where('exclude_from_kpis', false)->orWhereNull('exclude_from_kpis');
            });
        }

        return $query;
    }

    private static function applyCountedUserJoin($query)
    {
        $query->where(function ($q) {
            $q->where('users.company_books_enabled', false)
                ->orWhereNull('users.company_books_enabled');
        });

        if (self::hasExcludeColumn()) {
            $query->where(function ($q) {
                $q->where('users.exclude_from_kpis', false)
                    ->orWhereNull('users.exclude_from_kpis');
            });
        }

        return $query;
    }

    private static function hasExcludeColumn(): bool
    {
        try {
            return Schema::hasColumn('users', 'exclude_from_kpis');
        } catch (\Throwable) {
            return false;
        }
    }

    private static function hasMrrColumn(): bool
    {
        try {
            return Schema::hasColumn('users', 'exclude_from_mrr');
        } catch (\Throwable) {
            return false;
        }
    }

    private static function listPriceExVat(string $tier): float
    {
        $tier = TierPolicy::normalize($tier);

        return match (true) {
            $tier === TierPolicy::TIER_STANDARD => (float) TierPolicy::PRICE_STANDARD,
            str_starts_with($tier, 'practice-') => (float) TierPolicy::PRICE_PRACTICE,
            str_starts_with($tier, 'pro-') => (float) TierPolicy::PRICE_PRO,
            default => 0.0,
        };
    }

    private static function hasSessionsTable(): bool
    {
        try {
            return Schema::hasTable('sessions');
        } catch (\Throwable) {
            return false;
        }
    }

    private static function activeUserCount(int $days): int
    {
        if (! self::hasSessionsTable()) {
            return 0;
        }

        $since = now()->subDays($days)->getTimestamp();

        $q = DB::table('sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->where('sessions.last_activity', '>=', $since);

        self::applyCountedUserJoin($q);

        return (int) $q->selectRaw('COUNT(DISTINCT sessions.user_id) as aggregate')->value('aggregate');
    }

    private static function usersWithRows(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $q = DB::table($table)
            ->join('users', 'users.id', '=', $table.'.user_id');

        self::applyCountedUserJoin($q);

        if (Schema::hasColumn($table, 'deleted_at')) {
            $q->whereNull($table.'.deleted_at');
        }

        return (int) $q->selectRaw('COUNT(DISTINCT '.$table.'.user_id) as aggregate')->value('aggregate');
    }

    private static function safeCount(string $table, ?string $whereSql = null): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $q = DB::table($table)
            ->join('users', 'users.id', '=', $table.'.user_id');

        self::applyCountedUserJoin($q);

        if (Schema::hasColumn($table, 'deleted_at')) {
            $q->whereNull($table.'.deleted_at');
        }

        if ($whereSql) {
            $q->whereRaw($whereSql);
        }

        return (int) $q->count();
    }
}
