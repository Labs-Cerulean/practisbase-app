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
}
