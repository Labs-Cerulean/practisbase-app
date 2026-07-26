<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class FiscalYearGuard
{
    public static function isClosed(int $userId, int $year): bool
    {
        return DB::table('fiscal_years')
            ->where('user_id', $userId)
            ->where('year', $year)
            ->exists();
    }

    public static function ensureOpen(int $userId, int $year): ?string
    {
        if (self::isClosed($userId, $year)) {
            return "Fiscal year {$year} is permanently closed. You cannot create or change documents or payments dated in that year.";
        }

        return null;
    }

    public static function yearFromDate(string|\DateTimeInterface $date): int
    {
        if ($date instanceof \DateTimeInterface) {
            return (int) $date->format('Y');
        }

        return (int) date('Y', strtotime($date));
    }
}
