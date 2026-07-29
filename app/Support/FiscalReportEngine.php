<?php

namespace App\Support;

use App\Models\CapitalAsset;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TaxPayment;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Live fiscal report math + closed-year snapshots.
 * Closed years must never drift when the user later changes Settings.
 * Open years apply RegimeHistory segments by invoice/expense date.
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

        $collectedRevenue = (float) Payment::where('user_id', $user->id)
            ->whereYear('payment_date', $year)
            ->whereHas('invoice', fn ($q) => $q->where('type', 'invoice'))
            ->sum('amount');

        $windows = RegimeHistory::windowsForYear($user, $year);
        $attributed = self::attributeDocumentsToWindows($user, $year, $windows);

        $fiscalRevenue = (float) $attributed['fiscal_revenue'];
        $deductibleExpenses = (float) $attributed['deductible_expenses'];
        $inputVat = (float) $attributed['input_vat'];
        $art10OutputVat = (float) $attributed['art10_output_vat'];
        $expenseInfo = $attributed['expense_info'];
        $netProfit = max(0, $fiscalRevenue - $deductibleExpenses);

        $yearEndRegime = $windows !== [] ? $windows[array_key_last($windows)]['regime'] : RegimeHistory::tipFromUser($user);
        $primarySalary = (float) ($yearEndRegime['primary_salary'] ?? 0);
        $compType = $yearEndRegime['tax_computation'] ?: 'single';
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
            'regimes' => [],
        ];

        $hasArticle10 = (bool) $attributed['has_article_10'];
        $hasArticle11 = (bool) $attributed['has_article_11'];
        $isArticle10 = $hasArticle10;
        $ptProfit = (float) $attributed['pt_profit'];
        $ftProfit = (float) $attributed['ft_profit'];
        $hasPartTime = (bool) $attributed['has_part_time'];
        $hasFullTime = (bool) $attributed['has_full_time'];
        $mixedEmployment = $hasPartTime && $hasFullTime;
        $mixedVat = (bool) $attributed['mixed_vat'];
        $ptMaxSscPaid = (bool) $attributed['pt_max_ssc_paid'];

        if ($hasArticle10) {
            $vatLiability = $art10OutputVat - $inputVat;
            $breakdowns['vat'] = [
                'Output VAT (Article 10 periods only)' => '€'.number_format($art10OutputVat, 2),
                'Less: Input VAT (expenses in Article 10 periods)' => '-€'.number_format($inputVat, 2),
                'Net VAT Due' => '€'.number_format($vatLiability, 2).($vatLiability < 0 ? ' (reclaim)' : ''),
                'Fiscal revenue (regime-aware)' => '€'.number_format($fiscalRevenue, 2),
                'Deductible expenses (regime-aware)' => '€'.number_format($deductibleExpenses, 2),
            ];
            if ($mixedVat) {
                $breakdowns['vat']['Note'] = 'VAT status changed mid-year — only Article 10 dated invoices/expenses enter VAT.';
            }
        }

        foreach ($attributed['window_summaries'] as $summary) {
            $breakdowns['regimes']['From '.$summary['from'].' to '.$summary['to']] =
                strtoupper(str_replace('_', ' ', $summary['employment_type']))
                .' · '.strtoupper(str_replace('_', ' ', $summary['vat_status']))
                .' · Rev €'.number_format($summary['fiscal_revenue'], 2)
                .' · Exp €'.number_format($summary['deductible_expenses'], 2);
        }

        $ageAtYearEnd = self::ageAtYearEnd($user, $year);

        // Employment tax: single regime → classic annual profit; mixed → profit attributed by dated windows.
        if (! $mixedEmployment) {
            $employmentType = $hasPartTime ? 'part_time' : ($yearEndRegime['employment_type'] ?? 'full_time');
            $maxSscPaid = $hasPartTime ? $ptMaxSscPaid : (bool) ($yearEndRegime['max_ssc_paid'] ?? false);

            if ($employmentType === 'part_time') {
                [$ta22Liability, $incomeTaxLiability, $breakdowns['ta22'], $breakdowns['income_tax']] =
                    self::computePartTimeTax($netProfit, $primarySalary, $ta22Rules, $taxBrackets);
                $sscLiability = self::computePartTimeSsc($netProfit, $maxSscPaid, $ageAtYearEnd, $sscPtRules, $breakdowns);
            } else {
                $breakdowns['ta22'] = [];
                [$incomeTaxLiability, $breakdowns['income_tax']] =
                    self::computeFullTimeTax($netProfit, $primarySalary, $taxBrackets);
                $sscLiability = self::computeFullTimeSsc($netProfit, $ageAtYearEnd, $year, $sscFtRules, $breakdowns);
            }
        } else {
            [$ta22Liability, $ptIncomeTax, $breakdowns['ta22'], $ptIncomeBreakdown] =
                self::computePartTimeTax($ptProfit, $primarySalary, $ta22Rules, $taxBrackets);
            [$ftIncomeTax, $ftIncomeBreakdown] =
                self::computeFullTimeTax($ftProfit, $primarySalary, $taxBrackets);
            // Primary salary progressive base applied once (on FT path); strip double-count from PT spillover path when FT also runs.
            if ($ftProfit > 0 && $ptProfit > 0) {
                $ta22Cap = $ta22Rules['max_limit'] ?? 12000;
                $spillover = max(0, $ptProfit - $ta22Cap);
                if ($spillover > 0) {
                    $calcSpill = self::progressiveTax($primarySalary + $spillover, $taxBrackets);
                    $calcBase = self::progressiveTax($primarySalary, $taxBrackets);
                    $ptIncomeTax = max(0, $calcSpill['tax'] - $calcBase['tax']);
                } else {
                    $ptIncomeTax = 0.0;
                }
                $calcFt = self::progressiveTax($primarySalary + $ftProfit, $taxBrackets);
                $calcBaseFt = self::progressiveTax($primarySalary, $taxBrackets);
                $ftIncomeTax = max(0, $calcFt['tax'] - $calcBaseFt['tax']);
            }
            $incomeTaxLiability = $ptIncomeTax + $ftIncomeTax;
            $breakdowns['income_tax'] = array_merge(
                ['Mid-year note' => 'Employment type changed mid-year. Profit split by invoice/expense dates.'],
                ['Part-time period profit' => '€'.number_format($ptProfit, 2)],
                $ptIncomeBreakdown,
                ['Full-time period profit' => '€'.number_format($ftProfit, 2)],
                $ftIncomeBreakdown,
                ['Combined business income tax' => '€'.number_format($incomeTaxLiability, 2)]
            );

            $sscPt = 0.0;
            $sscFt = 0.0;
            $ptBreakdown = [];
            $ftBreakdown = [];
            $sscPt = self::computePartTimeSsc($ptProfit, $ptMaxSscPaid, $ageAtYearEnd, $sscPtRules, $ptBreakdown);
            $sscFt = self::computeFullTimeSsc($ftProfit, $ageAtYearEnd, $year, $sscFtRules, $ftBreakdown);
            $sscLiability = $sscPt + $sscFt;
            $breakdowns['ssc'] = array_merge(
                ['Mid-year note' => 'SSC calculated separately on part-time and full-time period profits.'],
                ['Part-time SSC' => '€'.number_format($sscPt, 2)],
                $ptBreakdown['ssc'] ?? [],
                ['Full-time SSC' => '€'.number_format($sscFt, 2)],
                $ftBreakdown['ssc'] ?? [],
                ['Final SSC Due' => '€'.number_format($sscLiability, 2)]
            );
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

        $displayEmployment = $mixedEmployment
            ? 'mixed'
            : ($hasPartTime ? 'part_time' : ($yearEndRegime['employment_type'] ?? 'full_time'));
        $displayVat = $mixedVat
            ? 'mixed'
            : ($yearEndRegime['vat_status'] ?? $user->vat_status);

        return [
            'from_snapshot' => false,
            'totals' => $totals,
            'collectedRevenue' => $collectedRevenue,
            'invoicedRevenue' => $invoicedRevenue,
            'fiscalRevenue' => $fiscalRevenue,
            'isArticle10' => $isArticle10,
            'hasArticle10' => $hasArticle10,
            'hasArticle11' => $hasArticle11,
            'mixedVat' => $mixedVat,
            'hasPartTime' => $hasPartTime || $displayEmployment === 'part_time',
            'mixedEmployment' => $mixedEmployment,
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
                'employment_type' => $displayEmployment === 'mixed'
                    ? ($yearEndRegime['employment_type'] ?? 'full_time')
                    : $displayEmployment,
                'employment_display' => $displayEmployment,
                'vat_status' => $displayVat === 'mixed'
                    ? ($yearEndRegime['vat_status'] ?? $user->vat_status)
                    : $displayVat,
                'vat_display' => $displayVat,
                'tax_computation' => $compType,
                'primary_salary' => $primarySalary,
                'max_ssc_paid' => $ptMaxSscPaid || (bool) ($yearEndRegime['max_ssc_paid'] ?? false),
                'date_of_birth' => optional($user->date_of_birth)->format('Y-m-d'),
                'age_at_year_end' => $ageAtYearEnd,
                'estimated_expenses_used' => (float) ($expenseInfo['estimate'] ?? 0),
                'expense_source' => $expenseInfo['source'] ?? 'estimate',
                'regime_windows' => $attributed['window_summaries'],
            ],
        ];
    }

    /**
     * @param  list<array{from: string, to: string, regime: array<string, mixed>}>  $windows
     * @return array<string, mixed>
     */
    private static function attributeDocumentsToWindows(User $user, int $year, array $windows): array
    {
        $bucket = [];
        foreach ($windows as $i => $window) {
            $bucket[$i] = [
                'from' => $window['from'],
                'to' => $window['to'],
                'regime' => $window['regime'],
                'fiscal_revenue' => 0.0,
                'gross_revenue' => 0.0,
                'subtotal_revenue' => 0.0,
                'output_vat' => 0.0,
                'deductible_expenses' => 0.0,
                'input_vat' => 0.0,
                'ledger_ex_vat' => 0.0,
                'ledger_vat' => 0.0,
            ];
        }

        $resolveIndex = function (string $date) use ($windows): int {
            foreach ($windows as $i => $window) {
                if ($date >= $window['from'] && $date <= $window['to']) {
                    return $i;
                }
            }

            return max(0, count($windows) - 1);
        };

        $invoices = Invoice::where('user_id', $user->id)
            ->where('type', 'invoice')
            ->whereYear('issue_date', $year)
            ->get(['issue_date', 'total', 'subtotal', 'vat_total']);

        foreach ($invoices as $invoice) {
            $day = Carbon::parse($invoice->issue_date)->toDateString();
            $i = $resolveIndex($day);
            $isArt10 = ($bucket[$i]['regime']['vat_status'] ?? '') === 'article_10';
            $total = (float) $invoice->total;
            $subtotal = (float) $invoice->subtotal;
            $vat = (float) $invoice->vat_total;
            $bucket[$i]['gross_revenue'] += $total;
            $bucket[$i]['subtotal_revenue'] += $subtotal;
            $bucket[$i]['fiscal_revenue'] += $isArt10 ? $subtotal : $total;
            if ($isArt10) {
                $bucket[$i]['output_vat'] += $vat;
            }
        }

        $credits = Invoice::where('user_id', $user->id)
            ->where('type', 'credit_note')
            ->whereHas('parentDocument', function ($q) use ($user, $year) {
                $q->where('user_id', $user->id)
                    ->where('type', 'invoice')
                    ->whereYear('issue_date', $year);
            })
            ->with(['parentDocument:id,issue_date,user_id,type'])
            ->get(['id', 'parent_document_id', 'total', 'subtotal', 'vat_total']);

        foreach ($credits as $credit) {
            $parentDate = $credit->parentDocument?->issue_date;
            $day = $parentDate
                ? Carbon::parse($parentDate)->toDateString()
                : sprintf('%04d-12-31', $year);
            $i = $resolveIndex($day);
            $isArt10 = ($bucket[$i]['regime']['vat_status'] ?? '') === 'article_10';
            $total = (float) $credit->total;
            $subtotal = (float) $credit->subtotal;
            $vat = (float) $credit->vat_total;
            $bucket[$i]['gross_revenue'] -= $total;
            $bucket[$i]['subtotal_revenue'] -= $subtotal;
            $bucket[$i]['fiscal_revenue'] -= $isArt10 ? $subtotal : $total;
            if ($isArt10) {
                $bucket[$i]['output_vat'] -= $vat;
            }
        }

        $expenses = Expense::where('user_id', $user->id)
            ->whereYear('expense_date', $year)
            ->get(['expense_date', 'amount', 'vat_amount', 'category', 'business_use_percent']);

        $ledgerExVat = 0.0;
        $ledgerVat = 0.0;
        $cashDeductible = 0.0;
        $wearAndTear = 0.0;
        $businessShareNote = 0.0;
        $wfhShareNote = 0.0;

        foreach ($expenses as $expense) {
            $day = Carbon::parse($expense->expense_date)->toDateString();
            $i = $resolveIndex($day);
            $isArt10 = ($bucket[$i]['regime']['vat_status'] ?? '') === 'article_10';
            $ex = (float) $expense->amount;
            $vat = (float) $expense->vat_amount;
            $category = (string) $expense->category;
            $treatment = ExpenseTreatment::forCategory($category);
            $ledgerExVat += $ex;
            $ledgerVat += $vat;
            $bucket[$i]['ledger_ex_vat'] += $ex;
            $bucket[$i]['ledger_vat'] += $vat;

            if ($treatment === ExpenseTreatment::CAPITAL) {
                // Full cost is not deducted — wear & tear added below. Art 10 input VAT still reclaimable.
                if ($isArt10) {
                    $bucket[$i]['input_vat'] += $vat;
                }
                continue;
            }

            $grossOrNet = $isArt10 ? $ex : ($ex + $vat);
            $share = 100.0;
            if ($treatment === ExpenseTreatment::BUSINESS_SHARE) {
                $share = max(0.0, min(100.0, (float) ($expense->business_use_percent ?? 0)));
                $businessShareNote += $grossOrNet * ($share / 100.0);
            } elseif ($treatment === ExpenseTreatment::WFH_SHARE) {
                $share = max(0.0, min(100.0, (float) ($expense->business_use_percent ?? $user->home_office_percent ?? 0)));
                $wfhShareNote += $grossOrNet * ($share / 100.0);
            }

            $deduct = $grossOrNet * ($share / 100.0);
            $bucket[$i]['deductible_expenses'] += $deduct;
            $cashDeductible += $deduct;
            if ($isArt10) {
                $bucket[$i]['input_vat'] += $vat * ($share / 100.0);
            }
        }

        $assets = CapitalAsset::where('user_id', $user->id)
            ->where('purchase_date', '<=', sprintf('%04d-12-31', $year))
            ->get();
        foreach ($assets as $asset) {
            $allowance = $asset->allowanceForYear($year);
            if ($allowance <= 0) {
                continue;
            }
            $wearAndTear += $allowance;
            $purchaseYear = (int) $asset->purchase_date->format('Y');
            $attrDate = $purchaseYear === $year
                ? $asset->purchase_date->toDateString()
                : sprintf('%04d-01-01', $year);
            $i = $resolveIndex($attrDate);
            $bucket[$i]['deductible_expenses'] += $allowance;
        }

        $byYear = $user->estimated_expenses_by_year ?? [];
        $yearKey = (string) $year;
        $estimate = array_key_exists($yearKey, $byYear)
            ? (float) $byYear[$yearKey]
            : (float) ($user->estimated_expenses ?? 0);

        $useLedger = $user->canAccessStandardTools() && (($ledgerExVat + $ledgerVat) > 0 || $wearAndTear > 0);
        if (! $useLedger) {
            // Estimate path: apply year-end VAT treatment to the annual estimate.
            $yearEnd = $windows !== [] ? $windows[array_key_last($windows)]['regime'] : RegimeHistory::tipFromUser($user);
            $isArt10 = ($yearEnd['vat_status'] ?? '') === 'article_10';
            foreach ($bucket as $i => $_) {
                $bucket[$i]['deductible_expenses'] = 0.0;
                $bucket[$i]['input_vat'] = 0.0;
            }
            $last = array_key_last($bucket);
            if ($last !== null) {
                $bucket[$last]['deductible_expenses'] = $estimate;
            }
            $expenseInfo = [
                'amount' => $estimate,
                'source' => 'estimate',
                'ledger_total' => $isArt10 ? $ledgerExVat : ($ledgerExVat + $ledgerVat),
                'ledger_ex_vat' => $ledgerExVat,
                'input_vat' => 0.0,
                'estimate' => $estimate,
                'ex_vat' => $isArt10,
                'cash_deductible' => 0.0,
                'wear_and_tear' => 0.0,
                'business_share' => 0.0,
                'wfh_share' => 0.0,
            ];
        } else {
            $expenseInfo = [
                'amount' => array_sum(array_column($bucket, 'deductible_expenses')),
                'source' => 'ledger',
                'ledger_total' => array_sum(array_column($bucket, 'deductible_expenses')),
                'ledger_ex_vat' => $ledgerExVat,
                'input_vat' => array_sum(array_column($bucket, 'input_vat')),
                'estimate' => $estimate,
                'ex_vat' => true,
                'cash_deductible' => round($cashDeductible, 2),
                'wear_and_tear' => round($wearAndTear, 2),
                'business_share' => round($businessShareNote, 2),
                'wfh_share' => round($wfhShareNote, 2),
            ];
        }

        $fiscalRevenue = 0.0;
        $deductible = 0.0;
        $inputVat = 0.0;
        $art10OutputVat = 0.0;
        $ptProfit = 0.0;
        $ftProfit = 0.0;
        $hasArticle10 = false;
        $hasArticle11 = false;
        $hasPartTime = false;
        $hasFullTime = false;
        $vatStatuses = [];
        $ptMaxSscWeighted = false;
        $windowSummaries = [];

        foreach ($bucket as $row) {
            $fiscalRevenue += $row['fiscal_revenue'];
            $deductible += $row['deductible_expenses'];
            $inputVat += $row['input_vat'];
            $vatStatus = $row['regime']['vat_status'] ?? '';
            $employment = $row['regime']['employment_type'] ?? '';
            $vatStatuses[$vatStatus] = true;
            if ($vatStatus === 'article_10') {
                $hasArticle10 = true;
                $art10OutputVat += $row['output_vat'];
            }
            if ($vatStatus === 'article_11') {
                $hasArticle11 = true;
            }
            $profit = $row['fiscal_revenue'] - $row['deductible_expenses'];
            if ($employment === 'part_time') {
                $hasPartTime = true;
                $ptProfit += $profit;
                if (! empty($row['regime']['max_ssc_paid'])) {
                    $ptMaxSscWeighted = true;
                }
            } else {
                $hasFullTime = true;
                $ftProfit += $profit;
            }
            $windowSummaries[] = [
                'from' => $row['from'],
                'to' => $row['to'],
                'vat_status' => $vatStatus,
                'employment_type' => $employment,
                'fiscal_revenue' => round($row['fiscal_revenue'], 2),
                'deductible_expenses' => round($row['deductible_expenses'], 2),
            ];
        }

        return [
            'fiscal_revenue' => $fiscalRevenue,
            'deductible_expenses' => $useLedger ? $deductible : $estimate,
            'input_vat' => $useLedger ? $inputVat : 0.0,
            'art10_output_vat' => $art10OutputVat,
            'expense_info' => $expenseInfo,
            'pt_profit' => max(0, $ptProfit),
            'ft_profit' => max(0, $ftProfit),
            'has_article_10' => $hasArticle10,
            'has_article_11' => $hasArticle11,
            'has_part_time' => $hasPartTime,
            'has_full_time' => $hasFullTime,
            'mixed_vat' => count(array_filter(array_keys($vatStatuses))) > 1,
            'pt_max_ssc_paid' => $ptMaxSscWeighted,
            'window_summaries' => $windowSummaries,
        ];
    }

    /**
     * @param  array<string, mixed>  $ta22Rules
     * @param  array<int, array<string, mixed>>  $taxBrackets
     * @return array{0: float, 1: float, 2: array<string, string>, 3: array<string, string>}
     */
    private static function computePartTimeTax(float $netProfit, float $primarySalary, array $ta22Rules, array $taxBrackets): array
    {
        $ta22Cap = $ta22Rules['max_limit'] ?? 12000;
        $ta22Rate = $ta22Rules['rate'] ?? 0.10;
        $amountEligibleForTa22 = min($netProfit, $ta22Cap);
        $ta22Liability = $amountEligibleForTa22 * $ta22Rate;

        $ta22Breakdown = [
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
            $incomeBreakdown = self::incomeTaxBreakdown($primarySalary, $spilloverProfit, $totalTaxableIncome, $calcTotal, $calcBase, $incomeTaxLiability, true);
        } else {
            $incomeTaxLiability = 0.0;
            $incomeBreakdown = ['Status' => 'No spillover profit. All business profit is fully covered by the TA22 scheme.'];
        }

        return [$ta22Liability, $incomeTaxLiability, $ta22Breakdown, $incomeBreakdown];
    }

    /**
     * @param  array<int, array<string, mixed>>  $taxBrackets
     * @return array{0: float, 1: array<string, string>}
     */
    private static function computeFullTimeTax(float $netProfit, float $primarySalary, array $taxBrackets): array
    {
        $totalTaxableIncome = $primarySalary + $netProfit;
        $calcTotal = self::progressiveTax($totalTaxableIncome, $taxBrackets);
        $calcBase = self::progressiveTax($primarySalary, $taxBrackets);
        $incomeTaxLiability = max(0, $calcTotal['tax'] - $calcBase['tax']);
        $incomeBreakdown = self::incomeTaxBreakdown($primarySalary, $netProfit, $totalTaxableIncome, $calcTotal, $calcBase, $incomeTaxLiability, false);

        return [$incomeTaxLiability, $incomeBreakdown];
    }

    /**
     * @param  array<string, mixed>  $sscPtRules
     * @param  array<string, mixed>  $breakdowns
     */
    private static function computePartTimeSsc(
        float $netProfit,
        bool $maxSscPaid,
        ?int $ageAtYearEnd,
        array $sscPtRules,
        array &$breakdowns
    ): float {
        if ($maxSscPaid) {
            $breakdowns['ssc'] = ['Status' => 'Exempt. You have certified that your maximum legal SSC is already paid through your primary employment.'];

            return 0.0;
        }

        if ($ageAtYearEnd !== null && $ageAtYearEnd < 18) {
            $breakdowns['ssc'] = [
                'Status' => 'No Class 2 SSC applied — under 18 at year end (age '.$ageAtYearEnd.').',
                'Final SSC Due' => '€0.00',
            ];

            return 0.0;
        }

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

        return $sscLiability;
    }

    /**
     * @param  array<int, array<string, mixed>>  $sscFtRules
     * @param  array<string, mixed>  $breakdowns
     */
    private static function computeFullTimeSsc(
        float $netProfit,
        ?int $ageAtYearEnd,
        int $year,
        array $sscFtRules,
        array &$breakdowns
    ): float {
        if ($sscFtRules === []) {
            $breakdowns['ssc'] = ['Status' => 'No full-time SSC rates loaded.'];

            return 0.0;
        }

        if ($ageAtYearEnd !== null && $ageAtYearEnd < 18) {
            $breakdowns['ssc'] = [
                'Status' => 'No Class 2 SSC applied — under 18 at year end (age '.$ageAtYearEnd.').',
                'Final SSC Due' => '€0.00',
            ];

            return 0.0;
        }

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

        return $sscLiability;
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
            'hasArticle10' => $report['hasArticle10'] ?? $report['isArticle10'],
            'hasArticle11' => $report['hasArticle11'] ?? false,
            'mixedVat' => $report['mixedVat'] ?? false,
            'hasPartTime' => $report['hasPartTime'] ?? false,
            'mixedEmployment' => $report['mixedEmployment'] ?? false,
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
            'hasArticle10' => (bool) ($snapshot['hasArticle10'] ?? $snapshot['isArticle10'] ?? false),
            'hasArticle11' => (bool) ($snapshot['hasArticle11'] ?? false),
            'mixedVat' => (bool) ($snapshot['mixedVat'] ?? false),
            'hasPartTime' => (bool) ($snapshot['hasPartTime'] ?? (($snapshot['profile']['employment_type'] ?? '') === 'part_time')),
            'mixedEmployment' => (bool) ($snapshot['mixedEmployment'] ?? false),
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

        return (int) $user->date_of_birth->diffInYears($end);
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
