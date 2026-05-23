<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxRate;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $year = date('Y'); // Default to current year

        $rates = [
            // 1. Single Computation
            [
                'year' => $year,
                'type' => 'income_single',
                'rates_json' => [
                    ['min' => 0, 'max' => 9100, 'rate' => 0.00, 'subtract' => 0],
                    ['min' => 9101, 'max' => 14500, 'rate' => 0.15, 'subtract' => 1365],
                    ['min' => 14501, 'max' => 19500, 'rate' => 0.25, 'subtract' => 2815],
                    ['min' => 19501, 'max' => 60000, 'rate' => 0.25, 'subtract' => 2725],
                    ['min' => 60001, 'max' => 9999999, 'rate' => 0.35, 'subtract' => 8725],
                ]
            ],
            // 2. Married Computation
            [
                'year' => $year,
                'type' => 'income_married',
                'rates_json' => [
                    ['min' => 0, 'max' => 12700, 'rate' => 0.00, 'subtract' => 0],
                    ['min' => 12701, 'max' => 21200, 'rate' => 0.15, 'subtract' => 1905],
                    ['min' => 21201, 'max' => 28700, 'rate' => 0.25, 'subtract' => 4025],
                    ['min' => 28701, 'max' => 60000, 'rate' => 0.25, 'subtract' => 3905],
                    ['min' => 60001, 'max' => 9999999, 'rate' => 0.35, 'subtract' => 9905],
                ]
            ],
            // 3. Parent Computation
            [
                'year' => $year,
                'type' => 'income_parent',
                'rates_json' => [
                    ['min' => 0, 'max' => 10500, 'rate' => 0.00, 'subtract' => 0],
                    ['min' => 10501, 'max' => 15800, 'rate' => 0.15, 'subtract' => 1575],
                    ['min' => 15801, 'max' => 21200, 'rate' => 0.25, 'subtract' => 3155],
                    ['min' => 21201, 'max' => 60000, 'rate' => 0.25, 'subtract' => 3050],
                    ['min' => 60001, 'max' => 9999999, 'rate' => 0.35, 'subtract' => 9050],
                ]
            ],
            // 4. TA22 (Part-Time Self-Employed Flat Tax)
            [
                'year' => $year,
                'type' => 'ta22',
                'rates_json' => [
                    'rate' => 0.10, // 10%
                    'max_limit' => 12000 // Up to €12,000
                ]
            ],
            // 5. Part-Time SSC Rules
            [
                'year' => $year,
                'type' => 'ssc_pt',
                'rates_json' => [
                    'rate' => 0.15, // 15% of net profit
                    'max_annual_profit_cap' => 26831, // Stops taxing after this profit
                    'max_annual_contribution' => 4024.65 // Maximum PT SSC liability
                ]
            ],
            // 6. Full-Time SSC Rules (Class 2 Weekly Brackets)
            [
                'year' => $year,
                'type' => 'ssc_ft',
                'rates_json' => [
                    ['category' => 'SA', 'min' => 0, 'max' => 11986, 'weekly_rate' => 34.58],
                    ['category' => 'SB', 'min' => 11987, 'max' => 13045, 'weekly_rate' => 37.63],
                    ['category' => 'SC', 'min' => 13046, 'max' => 14352, 'weekly_rate' => 41.40],
                    ['category' => 'SD', 'min' => 14353, 'max' => 15652, 'weekly_rate' => 45.15],
                    ['category' => 'SE', 'min' => 15653, 'max' => 16952, 'weekly_rate' => 48.90],
                    ['category' => 'SF', 'min' => 16953, 'max' => 26831, 'weekly_rate' => 0.15], // 15% if in this band
                    ['category' => 'SP', 'min' => 26832, 'max' => 9999999, 'weekly_rate' => 77.40], // Max Cap
                ]
            ]
        ];

        foreach ($rates as $rate) {
            TaxRate::updateOrCreate(
                ['year' => $rate['year'], 'type' => $rate['type']],
                ['rates_json' => $rate['rates_json']]
            );
        }
    }
}