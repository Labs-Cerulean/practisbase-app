<?php

namespace App\Support;

/**
 * One receipt, oldest open document first.
 * A line is settled when the slice covers its open due.
 */
class ReceiptSplit
{
    /**
     * @param  list<array{id: int, due: float}>  $lines
     * @return list<array{id: int, amount: float, settles: bool}>
     */
    public static function apply(array $lines, float $amount): array
    {
        $left = round($amount, 2);
        $out = [];

        foreach ($lines as $line) {
            if ($left <= 0.009) {
                break;
            }

            $due = round((float) $line['due'], 2);
            if ($due <= 0.009) {
                continue;
            }

            $take = round(min($due, $left), 2);
            if ($take <= 0) {
                continue;
            }

            $out[] = [
                'id' => (int) $line['id'],
                'amount' => $take,
                'settles' => ($due - $take) <= 0.009,
            ];
            $left = round($left - $take, 2);
        }

        return $out;
    }

    /**
     * @param  list<array{due: float}>  $lines
     */
    public static function openTotal(array $lines): float
    {
        $total = 0.0;
        foreach ($lines as $line) {
            $total = round($total + max(0, (float) $line['due']), 2);
        }

        return $total;
    }
}
