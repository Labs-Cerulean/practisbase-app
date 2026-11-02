<?php

namespace App\Support;

use App\Models\CompanyExpense;

/**
 * Malta wear and tear for computers and electronic equipment: 25% a year for four years.
 * The amount written off is the ex-VAT cost. Reclaimable VAT is not part of it.
 */
class CompanyComputerAllowance
{
    public const YEARS = 4;

    /**
     * Turn the invoice total into the cost and the VAT figure the form should store.
     * zero: supplier charged no VAT, so the total is the cost and Malta self-assesses 18%.
     * de19 / mt18: that rate is already inside the total.
     * irrecoverable: the whole total is the cost, including VAT that cannot be reclaimed.
     *
     * @return array{amount: float, vat_amount: float, reverse_charge: bool}
     */
    public static function fromInvoiceTotal(float $total, string $treatment): array
    {
        $total = round($total, 2);
        if ($treatment === 'de19' || $treatment === 'mt18') {
            $split = self::splitInclusive($total, $treatment === 'de19' ? 0.19 : 0.18);

            return [
                'amount' => $split['amount'],
                'vat_amount' => $split['vat'],
                'reverse_charge' => false,
            ];
        }

        if ($treatment === 'zero') {
            return [
                'amount' => $total,
                'vat_amount' => round(max(0, $total) * CompanyExpense::REVERSE_CHARGE_RATE, 2),
                'reverse_charge' => true,
            ];
        }

        return [
            'amount' => $total,
            'vat_amount' => 0.0,
            'reverse_charge' => false,
        ];
    }

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
