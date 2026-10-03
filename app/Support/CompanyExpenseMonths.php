<?php

namespace App\Support;

/**
 * Groups a year of company expenses into months for the expense list.
 * Reversed invoices stay in the month and out of the cash and owed totals.
 */
class CompanyExpenseMonths
{
    public static function likeTerm(string $term): string
    {
        $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return '%'.$term.'%';
    }

    /**
     * @param  list<array{id: int, date: string, reversed: bool, cash: float, owed: float}>  $rows
     * @return list<array{
     *     key: string,
     *     label: string,
     *     open: bool,
     *     active_ids: list<int>,
     *     reversed_ids: list<int>,
     *     cash: float,
     *     owed: float
     * }>
     */
    public static function group(array $rows, string $today, bool $reversedOnly): array
    {
        $buckets = [];
        foreach ($rows as $row) {
            $key = substr((string) $row['date'], 0, 7);
            $buckets[$key][] = $row;
        }
        krsort($buckets);

        $openKey = self::openKey(array_keys($buckets), $today);
        $months = [];
        foreach ($buckets as $key => $items) {
            $active = [];
            $reversed = [];
            $cash = 0.0;
            $owed = 0.0;
            foreach ($items as $item) {
                if ($item['reversed']) {
                    $reversed[] = (int) $item['id'];
                    if ($reversedOnly) {
                        $cash += (float) $item['cash'];
                    }
                } else {
                    $active[] = (int) $item['id'];
                    if (! $reversedOnly) {
                        $cash += (float) $item['cash'];
                        $owed += (float) $item['owed'];
                    }
                }
            }

            $labelDate = \DateTimeImmutable::createFromFormat('!Y-m', $key);
            $months[] = [
                'key' => $key,
                'label' => $labelDate instanceof \DateTimeImmutable ? $labelDate->format('F Y') : $key,
                'open' => $key === $openKey,
                'active_ids' => $reversedOnly ? [] : $active,
                'reversed_ids' => $reversed,
                'cash' => round($cash, 2),
                'owed' => round($owed, 2),
            ];
        }

        return $months;
    }

    /**
     * @param  list<string>  $keys
     */
    public static function openKey(array $keys, string $today): ?string
    {
        if ($keys === []) {
            return null;
        }

        $current = substr($today, 0, 7);
        if (in_array($current, $keys, true)) {
            return $current;
        }

        rsort($keys);

        return $keys[0];
    }
}
