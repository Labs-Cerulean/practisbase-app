<?php

namespace App\Support;

/**
 * Cerulean Estate Hub monthly packages — OS base + optional Plant / Sales hubs.
 */
class EstateHubBilling
{
    public const SECTION_OS = 'os';

    public const SECTION_PLANT = 'plant';

    public const SECTION_SALES = 'sales';

    public const SECTION_LABELS = [
        self::SECTION_OS => 'Estate hub: OS',
        self::SECTION_PLANT => 'Plant hub',
        self::SECTION_SALES => 'Sales hub',
    ];

    /**
     * @param  list<string>  $sections
     */
    public static function packageLabel(array $sections): string
    {
        $hasOs = in_array(self::SECTION_OS, $sections, true);
        $hasPlant = in_array(self::SECTION_PLANT, $sections, true);
        $hasSales = in_array(self::SECTION_SALES, $sections, true);

        if ($hasOs && $hasPlant && $hasSales) {
            return 'Estate hub: OS + Plant hub + Sales hub';
        }
        if ($hasOs && $hasPlant) {
            return 'Estate hub: OS + Plant hub';
        }
        if ($hasOs && $hasSales) {
            return 'Estate hub: OS + Sales hub';
        }
        if ($hasOs) {
            return 'Estate hub: OS Only';
        }

        return 'Estate hub';
    }

    /**
     * Build invoice line items (quantity / unit_price / row_total) from selected sections + rates.
     *
     * @param  list<string>  $sections
     * @param  array{os?: float|null, plant?: float|null, sales?: float|null}  $rates
     * @return list<array{description: string, quantity: float, unit_price: float, row_total: float, section: string}>
     */
    public static function buildItems(array $sections, array $rates): array
    {
        $items = [];
        $order = [self::SECTION_OS, self::SECTION_PLANT, self::SECTION_SALES];

        foreach ($order as $section) {
            if (! in_array($section, $sections, true)) {
                continue;
            }
            $price = round((float) ($rates[$section] ?? 0), 2);
            $label = self::SECTION_LABELS[$section] ?? $section;
            $items[] = [
                'description' => $label.' — monthly service',
                'quantity' => 1.0,
                'unit_price' => $price,
                'row_total' => $price,
                'section' => $section,
            ];
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public static function itemsSubtotal(array $items): float
    {
        return round(collect($items)->sum(function ($row) {
            return (float) ($row['row_total'] ?? $row['line_total'] ?? 0);
        }), 2);
    }

    /**
     * Normalize checkbox / JSON input into a unique ordered section list (OS always first when present).
     *
     * @param  mixed  $raw
     * @return list<string>
     */
    public static function normalizeSections(mixed $raw): array
    {
        $list = is_array($raw) ? $raw : [];
        $allowed = [self::SECTION_OS, self::SECTION_PLANT, self::SECTION_SALES];
        $out = [];
        foreach ($allowed as $key) {
            if (in_array($key, $list, true) || in_array((string) $key, $list, true)) {
                $out[] = $key;
            }
        }

        return $out;
    }
}
