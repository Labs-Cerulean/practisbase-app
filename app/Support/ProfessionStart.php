<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserRegimeSegment;

/**
 * First session for a warrant: profession, then a plan. VAT starts from the profession.
 * Doctors stay Fifth Schedule exempt. Everyone else starts on Article 11.
 */
class ProfessionStart
{
    public const PLAN_PENDING = 'pending';

    public static function vatStatusFor(?string $profession): string
    {
        return $profession === 'Medical Professional' ? 'exempt' : 'article_11';
    }

    public static function onboardingComplete(?string $profession, ?string $tier): bool
    {
        return self::present($profession)
            && self::present($tier)
            && $tier !== self::PLAN_PENDING;
    }

    public static function redirectPath(?string $profession, ?string $tier): string
    {
        if (! self::present($profession)) {
            return '/onboarding/profession';
        }

        if (! self::onboardingComplete($profession, $tier)) {
            return '/onboarding/plans';
        }

        return '/dashboard';
    }

    /**
     * Write the profession VAT default only before any regime history exists.
     */
    public static function applyVatDefault(User $user): void
    {
        if (! self::present($user->profession)) {
            return;
        }

        if (UserRegimeSegment::where('user_id', $user->id)->exists()) {
            return;
        }

        $status = self::vatStatusFor($user->profession);
        if ($user->vat_status === $status) {
            return;
        }

        $user->vat_status = $status;
        $user->save();
    }

    private static function present(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
