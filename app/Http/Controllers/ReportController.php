<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $selectedYear = $request->input('year', date('Y'));
        
        $isYearClosed = DB::table('fiscal_years')
            ->where('user_id', $user->id)
            ->where('year', $selectedYear)
            ->exists();

        $uninvoicedRfps = Invoice::where('user_id', $user->id)
            ->where('type', 'rfp')
            ->where('amount_paid', '>', 0)
            ->where('status', '!=', 'converted')
            ->whereYear('issue_date', '<=', $selectedYear)
            ->get();
            
        $uninvoicedRfpCount = $uninvoicedRfps->count();
        $uninvoicedRfpCash = $uninvoicedRfps->sum('amount_paid');

        // --- ACCRUAL FISCAL REVENUE ENGINE ---
        $totalInvoiced = Invoice::where('user_id', $user->id)->where('type', 'invoice')->whereYear('issue_date', $selectedYear)->sum('total');
        $totalCredited = Invoice::where('user_id', $user->id)->where('type', 'credit_note')->whereYear('issue_date', $selectedYear)->sum('total');
        $invoicedRevenue = max(0, $totalInvoiced - $totalCredited);

        $collectedRevenue = Payment::where('user_id', $user->id)->whereYear('payment_date', $selectedYear)->whereHas('invoice', fn($q) => $q->where('type', 'invoice'))->sum('amount');
        $netProfit = max(0, $invoicedRevenue - $user->estimated_expenses);

        // --- FETCH GOVERNMENT BRACKETS ---
        $compType = $user->tax_computation ?: 'single'; 
        $computationType = 'income_' . $compType;
        
        $exactRateExists = TaxRate::where('type', $computationType)->where('year', $selectedYear)->exists();
        $appliedRatesYear = $exactRateExists ? $selectedYear : TaxRate::where('type', $computationType)->orderBy('year', 'desc')->value('year');

        $taxBrackets = $this->getRatesSafely($computationType, $selectedYear);
        $ta22Rules   = $this->getRatesSafely('ta22', $selectedYear);
        $sscPtRules  = $this->getRatesSafely('ssc_pt', $selectedYear);
        $sscFtRules  = $this->getRatesSafely('ssc_ft', $selectedYear);

        // --- INITIALIZE LIABILITIES & BREAKDOWNS ---
        $incomeTaxLiability = 0;
        $ta22Liability = 0;
        $sscLiability = 0;
        $vatLiability = 0;
        
        $breakdowns = [
            'ta22' => [],
            'income_tax' => [],
            'ssc' => []
        ];

        // --- MATH ENGINE: VAT ---
        if ($user->vat_status === 'article_10') {
            $vatLiability = $invoicedRevenue - ($invoicedRevenue / 1.18);
            $netProfit = max(0, ($invoicedRevenue / 1.18) - $user->estimated_expenses);
        }

        // --- MATH ENGINE: INCOME TAX & TA22 ---
        if ($user->employment_type === 'part_time') {
            $ta22Cap = $ta22Rules['max_limit'] ?? 12000;
            $ta22Rate = $ta22Rules['rate'] ?? 0.10;

            $amountEligibleForTa22 = min($netProfit, $ta22Cap);
            $ta22Liability = $amountEligibleForTa22 * $ta22Rate;
            
            $breakdowns['ta22'] = [
                'Eligible Net Profit' => '€' . number_format($amountEligibleForTa22, 2),
                'TA22 Flat Rate' => ($ta22Rate * 100) . '%',
                'Calculation' => '€' . number_format($amountEligibleForTa22, 2) . ' × ' . ($ta22Rate * 100) . '%',
                'Final TA22 Tax Due' => '€' . number_format($ta22Liability, 2),
            ];

            $spilloverProfit = max(0, $netProfit - $ta22Cap);
            if ($spilloverProfit > 0) {
                $totalTaxableIncome = $user->primary_salary + $spilloverProfit;
                $calcTotal = $this->calculateProgressiveTaxWithBracket($totalTaxableIncome, $taxBrackets);
                $calcBase = $this->calculateProgressiveTaxWithBracket($user->primary_salary, $taxBrackets);
                $incomeTaxLiability = max(0, $calcTotal['tax'] - $calcBase['tax']);

                $breakdowns['income_tax'] = [
                    'Base Salary (Employment)' => '€' . number_format($user->primary_salary, 2),
                    'Business Profit (Spillover)' => '€' . number_format($spilloverProfit, 2),
                    'Total Taxable Income' => '€' . number_format($totalTaxableIncome, 2),
                    'Bracket Applied' => '€' . number_format($calcTotal['bracket']['min'] ?? 0) . ' - ' . (($calcTotal['bracket']['max'] ?? 9999999) >= 999999 ? 'No Limit' : '€' . number_format($calcTotal['bracket']['max'] ?? 0)) . ' @ ' . (($calcTotal['bracket']['rate'] ?? 0) * 100) . '%',
                    'Gross Tax Calculation' => '(€' . number_format($totalTaxableIncome, 2) . ' × ' . (($calcTotal['bracket']['rate'] ?? 0) * 100) . '%) - €' . number_format($calcTotal['bracket']['subtract'] ?? 0, 2) . ' = €' . number_format($calcTotal['tax'], 2),
                ];
                if ($user->primary_salary > 0) {
                    $breakdowns['income_tax']['Less Tax on Base Salary'] = '-€' . number_format($calcBase['tax'], 2);
                }
                $breakdowns['income_tax']['Final Business Tax Due'] = '€' . number_format($incomeTaxLiability, 2);
            } else {
                $breakdowns['income_tax'] = ['Status' => 'No spillover profit. All business profit is fully covered by the TA22 scheme.'];
            }
        } else {
            $totalTaxableIncome = $user->primary_salary + $netProfit;
            $calcTotal = $this->calculateProgressiveTaxWithBracket($totalTaxableIncome, $taxBrackets);
            $calcBase = $this->calculateProgressiveTaxWithBracket($user->primary_salary, $taxBrackets);
            $incomeTaxLiability = max(0, $calcTotal['tax'] - $calcBase['tax']);

            $breakdowns['income_tax'] = [
                'Base Salary (Employment)' => '€' . number_format($user->primary_salary, 2),
                'Business Net Profit' => '€' . number_format($netProfit, 2),
                'Total Taxable Income' => '€' . number_format($totalTaxableIncome, 2),
                'Bracket Applied' => '€' . number_format($calcTotal['bracket']['min'] ?? 0) . ' - ' . (($calcTotal['bracket']['max'] ?? 9999999) >= 999999 ? 'No Limit' : '€' . number_format($calcTotal['bracket']['max'] ?? 0)) . ' @ ' . (($calcTotal['bracket']['rate'] ?? 0) * 100) . '%',
                'Gross Tax Calculation' => '(€' . number_format($totalTaxableIncome, 2) . ' × ' . (($calcTotal['bracket']['rate'] ?? 0) * 100) . '%) - €' . number_format($calcTotal['bracket']['subtract'] ?? 0, 2) . ' = €' . number_format($calcTotal['tax'], 2),
            ];
            if ($user->primary_salary > 0) {
                $breakdowns['income_tax']['Less Tax on Base Salary'] = '-€' . number_format($calcBase['tax'], 2);
            }
            $breakdowns['income_tax']['Final Business Tax Due'] = '€' . number_format($incomeTaxLiability, 2);
        }

        // --- MATH ENGINE: SOCIAL SECURITY (SSC) ---
        if ($user->employment_type === 'part_time') {
            if (!$user->max_ssc_paid) {
                $ptRate = $sscPtRules['rate'] ?? 0.15;
                $maxCap = $sscPtRules['max_annual_contribution'] ?? 4024.65;
                $sscLiability = min($netProfit * $ptRate, $maxCap);
                
                $breakdowns['ssc'] = [
                    'Business Net Profit' => '€' . number_format($netProfit, 2),
                    'Pro-Rata SSC Rate' => ($ptRate * 100) . '%',
                    'Calculated SSC' => '€' . number_format($netProfit * $ptRate, 2),
                    'Maximum Annual Cap' => '€' . number_format($maxCap, 2),
                    'Final SSC Due' => '€' . number_format($sscLiability, 2) . ($sscLiability == $maxCap ? ' (Capped)' : ''),
                ];
            } else {
                $breakdowns['ssc'] = ['Status' => 'Exempt. You have certified that your maximum legal SSC is already paid through your primary employment.'];
            }
        } else {
            if (!empty($sscFtRules)) {
                $matchedBracket = null;
                foreach ($sscFtRules as $bracket) {
                    if ($netProfit >= $bracket['min'] && $netProfit <= ($bracket['max'] + 0.99)) {
                        $matchedBracket = $bracket;
                        break;
                    }
                }
                if (!$matchedBracket) $matchedBracket = end($sscFtRules);

                if (isset($matchedBracket['weekly_rate']) && $matchedBracket['weekly_rate'] < 1) {
                     $sscLiability = $netProfit * $matchedBracket['weekly_rate'];
                     $calcStr = '€' . number_format($netProfit, 2) . ' × ' . ($matchedBracket['weekly_rate'] * 100) . '%';
                     $rateStr = ($matchedBracket['weekly_rate'] * 100) . '% of profit';
                } else {
                     $sscLiability = ($matchedBracket['weekly_rate'] ?? 0) * 52;
                     $calcStr = '€' . number_format($matchedBracket['weekly_rate'] ?? 0, 2) . ' × 52 weeks';
                     $rateStr = '€' . number_format($matchedBracket['weekly_rate'] ?? 0, 2) . ' / week';
                }

                $breakdowns['ssc'] = [
                    'Business Net Profit' => '€' . number_format($netProfit, 2),
                    'Bracket Applied' => '€' . number_format($matchedBracket['min'] ?? 0) . ' - ' . (($matchedBracket['max'] ?? 9999999) >= 999999 ? 'No Limit' : '€' . number_format($matchedBracket['max'] ?? 0)),
                    'Weekly Rate' => $rateStr,
                    'Calculation' => $calcStr,
                    'Final SSC Due' => '€' . number_format($sscLiability, 2),
                ];
            }
        }

        return view('reports.index', compact(
            'user', 'selectedYear', 'isYearClosed', 'uninvoicedRfpCount', 'uninvoicedRfpCash',
            'collectedRevenue', 'invoicedRevenue', 'netProfit', 'ta22Liability', 
            'incomeTaxLiability', 'sscLiability', 'vatLiability', 'appliedRatesYear', 'breakdowns'
        ));
    }

    public function closeYear(Request $request)
    {
        $request->validate(['year' => 'required|integer']);
        $year = $request->year;
        $user = Auth::user();

        if ($year >= date('Y')) {
            return back()->withErrors(['fiscal_error' => 'You cannot close the current fiscal year until December 31st has passed.']);
        }

        DB::table('fiscal_years')->insertOrIgnore([
            'user_id' => $user->id, 'year' => $year,
            'closed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', "Fiscal Year {$year} has been permanently closed and locked. You may now send this final report to your accountant.");
    }

    private function getRatesSafely($type, $year)
    {
        $rate = TaxRate::where('type', $type)->where('year', $year)->first();
        if (!$rate) $rate = TaxRate::where('type', $type)->orderBy('year', 'desc')->first();
        return $rate?->rates_json ?? [];
    }

    // NEW: Returns both the calculation AND the bracket used!
    private function calculateProgressiveTaxWithBracket($income, $brackets)
    {
        if (empty($brackets)) return ['tax' => 0, 'bracket' => null];

        foreach ($brackets as $bracket) {
            if ($income >= $bracket['min'] && $income <= ($bracket['max'] + 0.99)) {
                return [
                    'tax' => max(0, ($income * $bracket['rate']) - $bracket['subtract']),
                    'bracket' => $bracket
                ];
            }
        }
        $lastBracket = end($brackets);
        return [
            'tax' => max(0, ($income * $lastBracket['rate']) - $lastBracket['subtract']),
            'bracket' => $lastBracket
        ];
    }
}