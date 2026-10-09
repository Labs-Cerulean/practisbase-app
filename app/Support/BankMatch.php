<?php

namespace App\Support;

/**
 * One bank statement movement may cover several ledger lines.
 * A €700 receipt matches two €350 invoices when their signed amounts add up.
 */
class BankMatch
{
    public static function totalsMatch(float $statementAmount, float $ledgerTotal): bool
    {
        return abs(round($statementAmount, 2) - round($ledgerTotal, 2)) <= 0.009;
    }

    /**
     * @param  iterable<int, float>  $signedAmounts
     */
    public static function signedTotal(iterable $signedAmounts): float
    {
        $total = 0.0;
        foreach ($signedAmounts as $amount) {
            $total += (float) $amount;
        }

        return round($total, 2);
    }
}
