<?php

namespace App\Support;

/**
 * Malta wear and tear for computers and electronic equipment: 25% a year for four years.
 * The amount written off is the ex-VAT cost. Reclaimable VAT is not part of it.
 */
class CompanyComputerAllowance
{
    public const YEARS = 4;

    /**
     * Split a VAT-inclusive total into the ex-VAT cost and the VAT.
     *
     * @return array{amount: float, vat: float}
     */
    public static function splitInclusive(float $gross, float $rate): array
    {
        $gross = round($gross, 2);
        if ($gross <= 0 || $rate <= 0 || $rate >= 1) {
            return ['amount' => $gross, 'vat' => 0.0];
        }

        $amount = round($gross / (1 + $rate), 2);

        return [
            'amount' => $amount,
            'vat' => round($gross - $amount, 2),
        ];
    }

    /**
     * Four slices that add back to the cost. The last slice absorbs the rounding cent.
     * The first slice is dated the day the computer is brought into use.
     * The later slices fall on the first day of each following financial year.
     *
     * @return list<array{index: int, date: string, amount: float}>
     */
    public static function slices(float $cost, string $inServiceDate, string $firstPeriodEnd): array
    {
        $cost = round($cost, 2);
        if ($cost <= 0) {
            return [];
        }

        $annual = round($cost / self::YEARS, 2);
        $dates = self::recognitionDates($inServiceDate, $firstPeriodEnd);
        $slices = [];
        $allocated = 0.0;

        foreach ($dates as $index => $date) {
            $amount = $index === self::YEARS - 1
                ? round($cost - $allocated, 2)
                : $annual;
            $allocated = round($allocated + $amount, 2);
            $slices[] = [
                'index' => $index,
                'date' => $date,
                'amount' => $amount,
            ];
        }

        return $slices;
    }

    /**
     * @param  list<array{index: int, date: string, amount: float}>  $slices
     * @return list<array{index: int, date: string, amount: float}>
     */
    public static function dueSlices(array $slices, string $asOf): array
    {
        return array_values(array_filter(
            $slices,
            fn (array $slice) => $slice['date'] <= $asOf && $slice['amount'] > 0
        ));
    }

    /**
     * @return list<string>
     */
    public static function recognitionDates(string $inServiceDate, string $firstPeriodEnd): array
    {
        $inService = new \DateTimeImmutable($inServiceDate);
        $boundary = new \DateTimeImmutable($firstPeriodEnd);

        while ($boundary < $inService) {
            $boundary = $boundary->modify('+1 year');
        }

        $dates = [$inService->format('Y-m-d')];
        $next = $boundary->modify('+1 day');
        for ($i = 1; $i < self::YEARS; $i++) {
            $dates[] = $next->format('Y-m-d');
            $next = $next->modify('+1 year');
        }

        return $dates;
    }
}
