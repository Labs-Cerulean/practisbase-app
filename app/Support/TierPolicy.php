<?php

namespace App\Support;

use App\Models\User;

class TierPolicy
{
    public const FREE_CLIENT_LIFETIME_CAP = 5;

    public const TIER_FREE = 'free';
    public const TIER_STANDARD = 'standard';
    public const TIER_PRO_MED = 'pro-med';
    public const TIER_PRO_ARCH = 'pro-arch';
    public const TIER_PRO_ENG = 'pro-eng';

    public static function normalize(?string $tier): string
    {
        $tier = $tier ?: self::TIER_FREE;

        return in_array($tier, [
            self::TIER_FREE,
            self::TIER_STANDARD,
            self::TIER_PRO_MED,
            self::TIER_PRO_ARCH,
            self::TIER_PRO_ENG,
        ], true) ? $tier : self::TIER_FREE;
    }

    public static function isPaid(User $user): bool
    {
        return self::normalize($user->tier) !== self::TIER_FREE;
    }

    public static function isPro(User $user): bool
    {
        return str_starts_with(self::normalize($user->tier), 'pro-');
    }

    public static function proPackage(User $user): ?string
    {
        $tier = self::normalize($user->tier);

        return match ($tier) {
            self::TIER_PRO_MED => 'med',
            self::TIER_PRO_ARCH => 'arch',
            self::TIER_PRO_ENG => 'eng',
            default => null,
        };
    }

    public static function canAccessReports(User $user): bool
    {
        return self::isPaid($user);
    }

    public static function canAccessStandardTools(User $user): bool
    {
        return self::isPaid($user);
    }

    public static function canAccessProPackage(User $user, string $package): bool
    {
        if (! self::isPro($user)) {
            return false;
        }

        $package = strtolower($package);
        $expectedProfession = match ($package) {
            'med' => 'Medical Professional',
            'arch' => 'Architect / Perit',
            'eng' => 'Engineer',
            default => null,
        };

        if ($expectedProfession === null) {
            return false;
        }

        return self::proPackage($user) === $package
            && ($user->profession ?: '') === $expectedProfession;
    }

    public static function lifetimeClientCount(User $user): int
    {
        return (int) ($user->clients_created_count ?? 0);
    }

    public static function canAddClient(User $user): bool
    {
        if (self::isPaid($user)) {
            return true;
        }

        return self::lifetimeClientCount($user) < self::FREE_CLIENT_LIFETIME_CAP;
    }

    public static function allowedTiersForProfession(?string $profession): array
    {
        $tiers = [self::TIER_FREE, self::TIER_STANDARD];

        return match ($profession) {
            'Medical Professional' => array_merge($tiers, [self::TIER_PRO_MED]),
            'Architect / Perit' => array_merge($tiers, [self::TIER_PRO_ARCH]),
            'Engineer' => array_merge($tiers, [self::TIER_PRO_ENG]),
            default => $tiers,
        };
    }

    public static function assertTierAllowedForProfession(User $user, string $tier): void
    {
        $tier = self::normalize($tier);
        $allowed = self::allowedTiersForProfession($user->profession);

        if (! in_array($tier, $allowed, true)) {
            abort(403, 'That plan is not available for your registered profession. Contact support if your profession needs updating.');
        }
    }

    public static function meetsMinimumTier(User $user, array $allowedTiers): bool
    {
        $tier = self::normalize($user->tier);

        if (in_array('standard', $allowedTiers, true) && self::isPaid($user)) {
            return true;
        }

        return in_array($tier, $allowedTiers, true);
    }

    /** Free = 0, Standard = 1, any Pro = 2. */
    public static function tierRank(string $tier): int
    {
        return match (self::normalize($tier)) {
            self::TIER_FREE => 0,
            self::TIER_STANDARD => 1,
            self::TIER_PRO_MED, self::TIER_PRO_ARCH, self::TIER_PRO_ENG => 2,
            default => 0,
        };
    }

    public static function isDowngrade(string $from, string $to): bool
    {
        return self::tierRank($to) < self::tierRank($from);
    }

    public static function label(string $tier): string
    {
        return match (self::normalize($tier)) {
            self::TIER_FREE => 'Free',
            self::TIER_STANDARD => 'Standard',
            self::TIER_PRO_MED => 'Pro Medical',
            self::TIER_PRO_ARCH => 'Pro Architect',
            self::TIER_PRO_ENG => 'Pro Engineer',
            default => ucwords(str_replace('-', ' ', $tier)),
        };
    }

    /**
     * Human-readable consequences of moving from one tier to another.
     *
     * @return list<string>
     */
    public static function changeConsequences(string $from, string $to): array
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        if ($from === $to) {
            return [];
        }

        $notes = [];
        $fromRank = self::tierRank($from);
        $toRank = self::tierRank($to);

        if ($toRank < $fromRank) {
            $notes[] = 'This is a downgrade from '.self::label($from).' to '.self::label($to).'. Your existing data is not deleted, but access to some tools will stop immediately.';
        }

        if (str_starts_with($from, 'pro-') && ! str_starts_with($to, 'pro-')) {
            $notes[] = 'Pro tools become inaccessible (patients, stampables, DMS, stamper, certificates). Medical vault ciphertext stays retained and locked — re-upgrade later still needs your recovery code.';
            $notes[] = 'Vault unlock and medical backup stay unavailable until you return to Pro Medical.';
        }

        if ($fromRank >= 1 && $toRank < 1) {
            $notes[] = 'Standard tools become inaccessible: Fiscal Report, Expenses, Accountant download, and custom branding.';
            $notes[] = 'Free keeps Dashboard + ledger only, with a lifetime cap of '.self::FREE_CLIENT_LIFETIME_CAP.' clients. Existing clients stay visible; deletes do not free slots.';
        } elseif ($fromRank >= 2 && $toRank === 1) {
            $notes[] = 'You keep Standard tools (Fiscal Report, Expenses, Accountant, branding) but lose Pro package features.';
        }

        if ($toRank > $fromRank) {
            $notes[] = 'Upgrade to '.self::label($to).' unlocks that plan’s features. Closed beta: no card charge yet.';
        }

        return array_values(array_unique($notes));
    }
}
