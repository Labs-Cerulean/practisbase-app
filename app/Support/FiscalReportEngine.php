<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\TaxPayment;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Live fiscal report math + closed-year snapshots.
 * Closed years must never drift when the user later changes Settings.
 */
class FiscalReportEngine
{
    /**
     * @return array<string, mixed>
     */
    public static function compute(User $user, int $year): array
    {
        $totals = FiscalYearTotals::forUserYear($user->id, $year);
        $invoicedRevenue = $totals['net_total'];
        $netInvoicedSubtotal = $totals['net_subtotal'];
        $netOutputVat = $totals['net_output_vat'];

        $collectedRevenue = (float) Payment::where('user_id', $user->id)
            ->whereYear('payment_date', $year)
            ->whereHas('invoice', fn ($q) => $q->where('type', 'invoice'))
            ->sum('amount');

        $expenseInfo = $user->deductibleExpensesForYear($year);
        $deductibleExpenses = (float) $expenseInfo['amount'];
        $inputVat = (float) ($expenseInfo['input_vat'] ?? 0);

        $isArticle10 = $user->vat_status === 'article_10';
        $fiscalRevenue = $isArticle10 ? $netInvoicedSubtotal : $invoicedRevenue;
        $netProfit = max(0, $fiscalRevenue - $deductibleExpenses);

        $compType = $user->tax_computation ?: 'single';
        $computationType = 'income_'.$compType;

        $exactRateExists = TaxRate::where('type', $computationType)->where('year', $year)->exists();
        $appliedRatesYear = $exactRateExists
            ? $year
            : TaxRate::where('type', $computationType)
                ->where('year', '<=', $year)
                ->orderBy('year', 'desc')
                ->value('year');
        if (! $appliedRatesYear) {
            $appliedRatesYear = TaxRate::where('type', $computationType)->orderBy('year', 'desc')->value('year') ?: $year;
        }

        $taxBrackets = self::getRatesSafely($computationType, $year);
        $ta22Rules = self::getRatesSafely('ta22', $year);
        $sscPtRules = self::getRatesSafely('ssc_pt', $year);
        $sscFtRules = self::getRatesSafely('ssc_ft', $year);

        $incomeTaxLiability = 0.0;
        $ta22Liability = 0.0;
        $sscLiability = 0.0;
        $vatLiability = 0.0;
        $breakdowns = [
            'ta22' => [],
            'income_tax' => [],
            'ssc' => [],
            'vat' => [],
        ];

        if ($isArticle10) {
            $vatLiability = $netOutputVat - $inputVat;
            $breakdowns['vat'] = [
                'Output VAT (invoices − credits on those invoices)' => '€'.number_format($netOutputVat, 2),
                'Less: Input VAT (expenses)' => '-€'.number_format($inputVat, 2),
                'Net VAT Due' => '€'.number_format($vatLiability, 2).($vatLiability < 0 ? ' (reclaim)' : ''),
                'Net of VAT revenue (subtotals)' => '€'.number_format($netInvoicedSubtotal, 2),
                'Deductible expenses (ex-VAT)' => '€'.number_format($deductibleExpenses, 2),
            ];
        }

        $primarySalary = (float) ($user->primary_salary ?? 0);

        if ($user->employment_type === 'part_time') {
            $ta22Cap = $ta22Rules['max_limit'] ?? 12000;
            $ta22Rate = $ta22Rules['rate'] ?? 0.10;
            $amountEligibleForTa22 = min($netProfit, $ta22Cap);
            $ta22Liability = $amountEligibleForTa22 * $ta22Rate;

            $breakdowns['ta22'] = [
                'Eligible Net Profit' => '€'.number_format($amountEligibleForTa22, 2),
                'TA22 Flat Rate' => ($ta22Rate * 100).'%',
                'Calculation' => '€'.number_format($amountEligibleForTa22, 2).' × '.($ta22Rate * 100).'%',
                'Final TA22 Tax Due' => '€'.number_format($ta22Liability, 2),
            ];

            $spilloverProfit = max(0, $netProfit - $ta22Cap);
            if ($spilloverProfit > 0) {
                $totalTaxableIncome = $primarySalary + $spilloverProfit;
                $calcTotal = self::progressiveTax($totalTaxableIncome, $taxBrackets);
                $calcBase = self::progressiveTax($primarySalary, $taxBrackets);
                $incomeTaxLiability = max(0, $calcTotal['tax'] - $calcBase['tax']);
                $breakdowns['income_tax'] = self::incomeTaxBreakdown($primarySalary, $spilloverProfit, $totalTaxableIncome, $calcTotal, $calcBase, $incomeTaxLiability, true);
            } else {
                $breakdowns['income_tax'] = ['Status' => 'No spillover profit. All business profit is fully covered by the TA22 scheme.'];
            }
        } else {
            $totalTaxableIncome = $primarySalary + $netProfit;
            $calcTotal = self::progressiveTax($totalTaxableIncome, $taxBrackets);
            $calcBase = self::progressiveTax($primarySalary, $taxBrackets);
            $incomeTaxLiability = max(0, $calcTotal['tax'] - $calcBase['tax']);
            $breakdowns['income_tax'] = self::incomeTaxBreakdown($primarySalary, $netProfit, $totalTaxableIncome, $calcTotal, $calcBase, $incomeTaxLiability, false);
        }

        $ageAtYearEnd = self::ageAtYearEnd($user, $year);

        if ($user->employment_type === 'part_time') {
            if (! $user->max_ssc_paid) {
                if ($ageAtYearEnd !== null && $ageAtYearEnd < 18) {
                    $sscLiability = 0.0;
                    $breakdowns['ssc'] = [
                        'Status' => 'No Class 2 SSC applied — under 18 at year end (age '.$ageAtYearEnd.').',
                        'Final SSC Due' => '€0.00',
                    ];
                } else {
                    $ptRate = $sscPtRules['rate'] ?? 0.15;
                    $maxCap = $sscPtRules['max_annual_contribution'] ?? 4024.65;
                    $profitCap = $sscPtRules['max_annual_profit_cap'] ?? null;
                    $sscBase = $netProfit;
                    if (is_numeric($profitCap) && (float) $profitCap > 0) {
                        $sscBase = min($netProfit, (float) $profitCap);
                    }
                    $sscLiability = min($sscBase * $ptRate, $maxCap);
                    $breakdowns['ssc'] = [
                        'Business Net Profit' => '€'.number_format($netProfit, 2),
                        'SSC Base (after profit cap)' => '€'.number_format($sscBase, 2),
                        'Pro-Rata SSC Rate' => ($ptRate * 100).'%',
                        'Calculated SSC' => '€'.number_format($sscBase * $ptRate, 2),
                        'Maximum Annual Cap' => '€'.number_format($maxCap, 2),
                        'Final SSC Due' => '€'.number_format($sscLiability, 2).($sscLiability == $maxCap ? ' (Capped)' : ''),
                    ];
                }
            } else {
                $breakdowns['ssc'] = ['Status' => 'Exempt. You have certified that your maximum legal SSC is already paid through your primary employment.'];
            }
        } elseif (! empty($sscFtRules)) {
            if ($ageAtYearEnd !== null && $ageAtYearEnd < 18) {
                $sscLiability = 0.0;
                $breakdowns['ssc'] = [
                    'Status' => 'No Class 2 SSC applied — under 18 at year end (age '.$ageAtYearEnd.').',
                    'Final SSC Due' => '€0.00',
                ];
            } else {
                $matchedBracket = null;
                foreach ($sscFtRules as $bracket) {
                    if ($netProfit >= $bracket['min'] && $netProfit <= ($bracket['max'] + 0.99)) {
                        $matchedBracket = $bracket;
                        break;
                    }
                }
                if (! $matchedBracket) {
                    $matchedBracket = end($sscFtRules);
                }

                if (isset($matchedBracket['weekly_rate']) && $matchedBracket['weekly_rate'] < 1) {
                    $sscLiability = $netProfit * $matchedBracket['weekly_rate'];
                    $calcStr = '€'.number_format($netProfit, 2).' × '.($matchedBracket['weekly_rate'] * 100).'%';
                    $rateStr = ($matchedBracket['weekly_rate'] * 100).'% of profit';
                } else {
                    $sscLiability = ($matchedBracket['weekly_rate'] ?? 0) * 52;
                    $calcStr = '€'.number_format($matchedBracket['weekly_rate'] ?? 0, 2).' × 52 weeks';
                    $rateStr = '€'.number_format($matchedBracket['weekly_rate'] ?? 0, 2).' / week';
                }

                $ageNote = 'Standard Class 2 self-employed table.';
                if ($ageAtYearEnd !== null) {
                    $ageNote = "Age at {$year}-12-31: {$ageAtYearEnd}. ";
                    if ($ageAtYearEnd >= 65) {
                        $ageNote .= 'Pension-age SSC treatments can differ — verify the final Class 2 rate with Social Security; this report uses the standard table.';
                    } else {
                        $ageNote .= 'Using standard Class 2 bands for this age.';
                    }
                } else {
                    $ageNote .= ' Add date of birth in Settings for age checks (under-18 exemption).';
                }

                $breakdowns['ssc'] = [
                    'Business Net Profit' => '€'.number_format($netProfit, 2),
                    'Bracket Applied' => '€'.number_format($matchedBracket['min'] ?? 0).' - '.(($matchedBracket['max'] ?? 9999999) >= 999999 ? 'No Limit' : '€'.number_format($matchedBracket['max'] ?? 0)),
                    'Weekly Rate' => $rateStr,
                    'Calculation' => $calcStr,
                    'Note' => $ageNote,
                    'Final SSC Due' => '€'.number_format($sscLiability, 2),
                ];
            }
        }

        $taxPayments = TaxPayment::where('user_id', $user->id)
            ->where('year', $year)
            ->orderBy('payment_date', 'desc')
            ->get();

        $ptTaxPaid = (float) $taxPayments->where('payment_type', 'income_tax')->sum('amount');
        $ptSscPaid = (float) $taxPayments->where('payment_type', 'ssc')->sum('amount');
        $vatPaid = (float) $taxPayments->where('payment_type', 'vat')->sum('amount');

        $totalTaxLiability = $incomeTaxLiability + $ta22Liability;
        $taxBalance = $totalTaxLiability - $ptTaxPaid;
        $sscBalance = $sscLiability - $ptSscPaid;
        $vatBalance = $vatLiability - $vatPaid;

        return [
            'from_snapshot' => false,
            'totals' => $totals,
            'collectedRevenue' => $collectedRevenue,
            'invoicedRevenue' => $invoicedRevenue,
            'fiscalRevenue' => $fiscalRevenue,
            'isArticle10' => $isArticle10,
            'netProfit' => $netProfit,
            'deductibleExpenses' => $deductibleExpenses,
            'expenseInfo' => $expenseInfo,
            'ta22Liability' => $ta22Liability,
            'incomeTaxLiability' => $incomeTaxLiability,
            'sscLiability' => $sscLiability,
            'vatLiability' => $vatLiability,
            'appliedRatesYear' => (int) $appliedRatesYear,
            'breakdowns' => $breakdowns,
            'taxPayments' => $taxPayments,
            'ptTaxPaid' => $ptTaxPaid,
            'ptSscPaid' => $ptSscPaid,
            'vatPaid' => $vatPaid,
            'totalTaxLiability' => $totalTaxLiability,
            'taxBalance' => $taxBalance,
            'sscBalance' => $sscBalance,
            'vatBalance' => $vatBalance,
            'profile' => [
                'employment_type' => $user->employment_type,
                'vat_status' => $user->vat_status,
                'tax_computation' => $user->tax_computation ?: 'single',
                'primary_salary' => $primarySalary,
                'max_ssc_paid' => (bool) $user->max_ssc_paid,
                'date_of_birth' => optional($user->date_of_birth)->format('Y-m-d'),
                'age_at_year_end' => $ageAtYearEnd,
                'estimated_expenses_used' => (float) ($expenseInfo['estimate'] ?? 0),
                'expense_source' => $expenseInfo['source'] ?? 'estimate',
            ],
        ];
    }

    /**
     * Persist a freeze of profile + liabilities for a closed year.
     *
     * @param  array<string, mixed>  $report
     */
    public static function buildSnapshotPayload(array $report): array
    {
        return [
            'version' => 1,
            'frozen_at' => now()->toIso8601String(),
            'profile' => $report['profile'],
            'totals' => $report['totals'],
            'collectedRevenue' => $report['collectedRevenue'],
            'invoicedRevenue' => $report['invoicedRevenue'],
            'fiscalRevenue' => $report['fiscalRevenue'],
            'isArticle10' => $report['isArticle10'],
            'netProfit' => $report['netProfit'],
            'deductibleExpenses' => $report['deductibleExpenses'],
            'expenseInfo' => $report['expenseInfo'],
            'ta22Liability' => $report['ta22Liability'],
            'incomeTaxLiability' => $report['incomeTaxLiability'],
            'sscLiability' => $report['sscLiability'],
            'vatLiability' => $report['vatLiability'],
            'appliedRatesYear' => $report['appliedRatesYear'],
            'breakdowns' => $report['breakdowns'],
            'ptTaxPaid' => $report['ptTaxPaid'],
            'ptSscPaid' => $report['ptSscPaid'],
            'vatPaid' => $report['vatPaid'],
            'totalTaxLiability' => $report['totalTaxLiability'],
            'taxBalance' => $report['taxBalance'],
            'sscBalance' => $report['sscBalance'],
            'vatBalance' => $report['vatBalance'],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    public static function hydrateFromSnapshot(?array $snapshot, User $user, int $year): ?array
    {
        if (! is_array($snapshot) || (int) ($snapshot['version'] ?? 0) < 1) {
            return null;
        }

        $taxPayments = TaxPayment::where('user_id', $user->id)
            ->where('year', $year)
            ->orderBy('payment_date', 'desc')
            ->get();

        // Payments stay locked after close; re-read so the ledger list is complete,
        // but liability totals stay frozen from the snapshot.
        return [
            'from_snapshot' => true,
            'totals' => $snapshot['totals'] ?? [],
            'collectedRevenue' => (float) ($snapshot['collectedRevenue'] ?? 0),
            'invoicedRevenue' => (float) ($snapshot['invoicedRevenue'] ?? 0),
            'fiscalRevenue' => (float) ($snapshot['fiscalRevenue'] ?? 0),
            'isArticle10' => (bool) ($snapshot['isArticle10'] ?? false),
            'netProfit' => (float) ($snapshot['netProfit'] ?? 0),
            'deductibleExpenses' => (float) ($snapshot['deductibleExpenses'] ?? 0),
            'expenseInfo' => $snapshot['expenseInfo'] ?? ['source' => 'snapshot', 'amount' => 0, 'estimate' => 0, 'ledger_total' => 0],
            'ta22Liability' => (float) ($snapshot['ta22Liability'] ?? 0),
            'incomeTaxLiability' => (float) ($snapshot['incomeTaxLiability'] ?? 0),
            'sscLiability' => (float) ($snapshot['sscLiability'] ?? 0),
            'vatLiability' => (float) ($snapshot['vatLiability'] ?? 0),
            'appliedRatesYear' => (int) ($snapshot['appliedRatesYear'] ?? $year),
            'breakdowns' => $snapshot['breakdowns'] ?? ['ta22' => [], 'income_tax' => [], 'ssc' => [], 'vat' => []],
            'taxPayments' => $taxPayments,
            'ptTaxPaid' => (float) ($snapshot['ptTaxPaid'] ?? 0),
            'ptSscPaid' => (float) ($snapshot['ptSscPaid'] ?? 0),
            'vatPaid' => (float) ($snapshot['vatPaid'] ?? 0),
            'totalTaxLiability' => (float) ($snapshot['totalTaxLiability'] ?? 0),
            'taxBalance' => (float) ($snapshot['taxBalance'] ?? 0),
            'sscBalance' => (float) ($snapshot['sscBalance'] ?? 0),
            'vatBalance' => (float) ($snapshot['vatBalance'] ?? 0),
            'profile' => $snapshot['profile'] ?? [],
            'frozen_at' => $snapshot['frozen_at'] ?? null,
        ];
    }

    public static function loadClosedYearRow(int $userId, int $year): ?object
    {
        return DB::table('fiscal_years')
            ->where('user_id', $userId)
            ->where('year', $year)
            ->first();
    }

    public static function ageAtYearEnd(User $user, int $year): ?int
    {
        if (! $user->date_of_birth) {
            return null;
        }

        $end = \Illuminate\Support\Carbon::create($year, 12, 31)->startOfDay();

        return $user->date_of_birth->diffInYears($end);
    }

    public static function hasClosedYears(int $userId): bool
    {
        return DB::table('fiscal_years')->where('user_id', $userId)->exists();
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function getRatesSafely(string $type, int $year): array
    {
        $rate = TaxRate::where('type', $type)->where('year', $year)->first();

        if (! $rate) {
            $rate = TaxRate::where('type', $type)
                ->where('year', '<=', $year)
                ->orderBy('year', 'desc')
                ->first();
        }

        if (! $rate) {
            $rate = TaxRate::where('type', $type)->orderBy('year', 'desc')->first();
        }

        $json = $rate?->rates_json ?? [];
        if (! empty($json)) {
            return $json;
        }

        return self::builtinFallbackRates($type);
    }

    /**
     * @return array<int|string, mixed>
     */
    private static function builtinFallbackRates(string $type): array
    {
        return match ($type) {
            'income_single' => [
                ['min' => 0, 'max' => 9100, 'rate' => 0.00, 'subtract' => 0],
                ['min' => 9101, 'max' => 14500, 'rate' => 0.15, 'subtract' => 1365],
                ['min' => 14501, 'max' => 19500, 'rate' => 0.25, 'subtract' => 2815],
                ['min' => 19501, 'max' => 60000, 'rate' => 0.25, 'subtract' => 2725],
                ['min' => 60001, 'max' => 9999999, 'rate' => 0.35, 'subtract' => 8725],
            ],
            'income_married' => [
                ['min' => 0, 'max' => 12700, 'rate' => 0.00, 'subtract' => 0],
                ['min' => 12701, 'max' => 21200, 'rate' => 0.15, 'subtract' => 1905],
                ['min' => 21201, 'max' => 28700, 'rate' => 0.25, 'subtract' => 4025],
                ['min' => 28701, 'max' => 60000, 'rate' => 0.25, 'subtract' => 3905],
                ['min' => 60001, 'max' => 9999999, 'rate' => 0.35, 'subtract' => 9905],
            ],
            'income_parent' => [
                ['min' => 0, 'max' => 10500, 'rate' => 0.00, 'subtract' => 0],
                ['min' => 10501, 'max' => 15800, 'rate' => 0.15, 'subtract' => 1575],
                ['min' => 15801, 'max' => 21200, 'rate' => 0.25, 'subtract' => 3155],
                ['min' => 21201, 'max' => 60000, 'rate' => 0.25, 'subtract' => 3050],
                ['min' => 60001, 'max' => 9999999, 'rate' => 0.35, 'subtract' => 9050],
            ],
            'ta22' => [
                'rate' => 0.10,
                'max_limit' => 12000,
            ],
            'ssc_pt' => [
                'rate' => 0.15,
                'max_annual_profit_cap' => 26831,
                'max_annual_contribution' => 4024.65,
            ],
            'ssc_ft' => [
                ['category' => 'SA', 'min' => 0, 'max' => 11986, 'weekly_rate' => 34.58],
                ['category' => 'SB', 'min' => 11987, 'max' => 13045, 'weekly_rate' => 37.63],
                ['category' => 'SC', 'min' => 13046, 'max' => 14352, 'weekly_rate' => 41.40],
                ['category' => 'SD', 'min' => 14353, 'max' => 15652, 'weekly_rate' => 45.15],
                ['category' => 'SE', 'min' => 15653, 'max' => 16952, 'weekly_rate' => 48.90],
                ['category' => 'SF', 'min' => 16953, 'max' => 26831, 'weekly_rate' => 0.15],
                ['category' => 'SP', 'min' => 26832, 'max' => 9999999, 'weekly_rate' => 77.40],
            ],
            default => [],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $brackets
     * @return array{tax: float, bracket: ?array}
     */
    private static function progressiveTax(float $income, array $brackets): array
    {
        if ($brackets === []) {
            return ['tax' => 0.0, 'bracket' => null];
        }

        foreach ($brackets as $bracket) {
            if ($income >= $bracket['min'] && $income <= ($bracket['max'] + 0.99)) {
                return [
                    'tax' => max(0, ($income * $bracket['rate']) - $bracket['subtract']),
                    'bracket' => $bracket,
                ];
            }
        }

        $lastBracket = end($brackets);

        return [
            'tax' => max(0, ($income * $lastBracket['rate']) - $lastBracket['subtract']),
            'bracket' => $lastBracket,
        ];
    }

    /**
     * @param  array{tax: float, bracket: ?array}  $calcTotal
     * @param  array{tax: float, bracket: ?array}  $calcBase
     * @return array<string, string>
     */
    private static function incomeTaxBreakdown(
        float $primarySalary,
        float $businessComponent,
        float $totalTaxableIncome,
        array $calcTotal,
        array $calcBase,
        float $incomeTaxLiability,
        bool $spillover
    ): array {
        $rows = [
            'Base Salary (Employment)' => '€'.number_format($primarySalary, 2),
            ($spillover ? 'Business Profit (Spillover)' : 'Business Net Profit') => '€'.number_format($businessComponent, 2),
            'Total Taxable Income' => '€'.number_format($totalTaxableIncome, 2),
            'Bracket Applied' => '€'.number_format($calcTotal['bracket']['min'] ?? 0).' - '.(($calcTotal['bracket']['max'] ?? 9999999) >= 999999 ? 'No Limit' : '€'.number_format($calcTotal['bracket']['max'] ?? 0)).' @ '.(($calcTotal['bracket']['rate'] ?? 0) * 100).'%',
            'Gross Tax Calculation' => '(€'.number_format($totalTaxableIncome, 2).' × '.(($calcTotal['bracket']['rate'] ?? 0) * 100).'%) - €'.number_format($calcTotal['bracket']['subtract'] ?? 0, 2).' = €'.number_format($calcTotal['tax'], 2),
        ];
        if ($primarySalary > 0) {
            $rows['Less Tax on Base Salary'] = '-€'.number_format($calcBase['tax'], 2);
        }
        $rows['Final Business Tax Due'] = '€'.number_format($incomeTaxLiability, 2);

        return $rows;
    }
}
