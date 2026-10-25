<?php

namespace App\Support;

/**
 * Pure figures for a Cerulean Labs Limited subscription invoice.
 */
class CeruleanBillingDocument
{
    /**
     * @return array{subtotal: float, vat: float, total: float}
     */
    public static function splitGross(float $gross, string $vatPercent = '18'): array
    {
        $total = round($gross, 2);
        if ($total <= 0) {
            return ['subtotal' => 0.0, 'vat' => 0.0, 'total' => 0.0];
        }

        $rate = 1 + ((float) $vatPercent / 100);
        $subtotal = round($total / $rate, 2);

        return [
            'subtotal' => $subtotal,
            'vat' => round($total - $subtotal, 2),
            'total' => $total,
        ];
    }

    /**
     * Stripe period end is exclusive. The printed coverage ends the day before.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function coverageBounds(?int $start, ?int $end, string $timezone = 'Europe/Malta'): ?array
    {
        if ($start === null || $end === null || $end <= $start) {
            return null;
        }

        $tz = new \DateTimeZone($timezone);
        $from = (new \DateTimeImmutable('@'.$start))->setTimezone($tz);
        $to = (new \DateTimeImmutable('@'.$end))->setTimezone($tz)->modify('-1 day');

        return [$from->format('Y-m-d'), $to->format('Y-m-d')];
    }

    public static function formatAddress(mixed $address): ?string
    {
        if (! is_array($address) && ! is_object($address)) {
            return null;
        }

        $lines = [];
        foreach (['line1', 'line2', 'city', 'state', 'postal_code', 'country'] as $key) {
            $value = trim((string) data_get($address, $key, ''));
            if ($value !== '') {
                $lines[] = $value;
            }
        }

        return $lines === [] ? null : implode("\n", $lines);
    }
}
