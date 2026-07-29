<?php

namespace App\Support;

/**
 * Sole-trader expense treatments: immediate, capital (wear & tear), business-share, WFH share.
 */
class ExpenseTreatment
{
    public const IMMEDIATE = 'immediate';

    public const CAPITAL = 'capital';

    public const BUSINESS_SHARE = 'business_share';

    public const WFH_SHARE = 'wfh_share';

    /** @var array<string, float> */
    public const CAPITAL_RATES = [
        'laptop' => 0.25,
        'equipment' => 0.25,
        'car' => 0.20,
    ];

    public static function forCategory(string $category): string
    {
        return match ($category) {
            'laptop', 'equipment', 'car' => self::CAPITAL,
            'fuel' => self::BUSINESS_SHARE,
            'wfh_electricity', 'wfh_internet', 'wfh_water', 'wfh_heating' => self::WFH_SHARE,
            default => self::IMMEDIATE,
        };
    }

    public static function capitalRate(string $category): ?float
    {
        return self::CAPITAL_RATES[$category] ?? null;
    }

    public static function requiresBusinessUsePercent(string $category): bool
    {
        return in_array($category, ['car', 'fuel'], true);
    }

    public static function requiresHomeOfficePercent(string $category): bool
    {
        return self::forCategory($category) === self::WFH_SHARE;
    }

    public static function isCapital(string $category): bool
    {
        return self::forCategory($category) === self::CAPITAL;
    }
}
