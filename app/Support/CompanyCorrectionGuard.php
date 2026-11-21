<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class CompanyCorrectionGuard
{
    /**
     * Block a correction when any of these dates sits in a closed fiscal year
     * or on a company-books lock. A supplier with no dated rows has nothing to lock.
     *
     * @param  list<string|\DateTimeInterface|null>  $dates
     */
    public static function assertDatesMutable(int $userId, array $dates): void
    {
        $clean = [];
        foreach ($dates as $date) {
            if ($date === null || $date === '') {
                continue;
            }
            $clean[] = $date instanceof \DateTimeInterface
                ? $date->format('Y-m-d')
                : substr((string) $date, 0, 10);
        }

        if ($clean === []) {
            return;
        }

        if ($error = FiscalYearGuard::ensureOpenForDates($userId, $clean)) {
            throw ValidationException::withMessages([
                'books' => $error,
            ]);
        }

        foreach ($clean as $date) {
            CompanyLedger::assertDateOpen($userId, $date);
        }
    }
}
