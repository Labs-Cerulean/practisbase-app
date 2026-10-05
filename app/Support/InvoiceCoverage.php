<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class InvoiceCoverage
{
    public static function monthEnd(string $start): string
    {
        return CarbonImmutable::parse($start)->startOfDay()->addMonthNoOverflow()->subDay()->toDateString();
    }

    public static function label(string $start, string $end): string
    {
        $from = CarbonImmutable::parse($start)->format('d M Y');
        $to = CarbonImmutable::parse($end)->format('d M Y');

        return '(From: '.$from.', To: '.$to.')';
    }
}
