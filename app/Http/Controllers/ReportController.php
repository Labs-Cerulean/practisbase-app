<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TaxRate;
use App\Models\TaxPayment;
use App\Support\FiscalYearTotals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // --- SMART YEAR BOUNDARIES ---
        $currentYear = (int) date('Y');

        $earliestInvoice = Invoice::where('user_id', $user->id)->orderBy('issue_date', 'asc')->first();
        $earliestPayment = Payment::where('user_id', $user->id)->orderBy('payment_date', 'asc')->first();
        
        $earliestYear = $currentYear;
        if ($earliestInvoice) $earliestYear = min($earliestYear, (int) date('Y', strtotime($earliestInvoice->issue_date)));
        if ($earliestPayment) $earliestYear = min($earliestYear, (int) date('Y', strtotime($earliestPayment->payment_date)));

        $requestedYear = (int) $request->input('year', $currentYear);
        $selectedYear = max($earliestYear, min($currentYear, $requestedYear));
        
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
        // Credits reverse the parent invoice year (not the CN print date).
        $totals = FiscalYearTotals::forUserYear($user->id, $selectedYear);
        $invoicedRevenueGross = $totals['net_total'];
        $netInvoicedSubtotal = $totals['net_subtotal'];
        $netOutputVat = $totals['net_output_vat'];

        // Display / Art 11 threshold: VAT-inclusive net. Art 10 profit uses subtotals.
        $invoicedRevenue = $invoicedRevenueGross;

        $collectedRevenue = Payment::where('user_id', $user->id)
            ->whereYear('payment_date', $selectedYear)
            ->whereHas('invoice', fn($q) => $q->where('type', 'invoice'))
            ->sum('amount');

        $expenseInfo = $user->deductibleExpensesForYear($selectedYear);
        $deductibleExpenses = $expenseInfo['amount'];
        $inputVat = (float) ($expenseInfo['input_vat'] ?? 0);

        $isArticle10 = $user->vat_status === 'article_10';
        $fiscalRevenue = $isArticle10 ? $netInvoicedSubtotal : $invoicedRevenue;
        $netProfit = max(0, $fiscalRevenue - $deductibleExpenses);

        // --- FETCH GOVERNMENT BRACKETS ---
        $compType = $user->tax_computation ?: 'single'; 
        $computationType = 'income_' . $compType;
        
        $exactRateExists = TaxRate::where('type', $computationType)->where('year', $selectedYear)->exists();
        $appliedRatesYear = $exactRateExists
            ? $selectedYear
            : TaxRate::where('type', $computationType)
                ->where('year', '<=', $selectedYear)
                ->orderBy('year', 'desc')
                ->value('year');
        if (! $appliedRatesYear) {
            $appliedRatesYear = TaxRate::where('type', $computationType)->orderBy('year', 'desc')->value('year');
        }
        if (! $appliedRatesYear) {
            $appliedRatesYear = $selectedYear;
        }

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
            'ssc' => [],
            'vat' => [],
        ];

        // --- MATH ENGINE: VAT ---
        // Article 10: document VAT − expense input VAT. Negative = reclaim due.
        if ($isArticle10) {
            $vatLiability = $netOutputVat - $inputVat;
            $breakdowns['vat'] = [
                'Output VAT (invoices − credits on those invoices)' => '€' . number_format($netOutputVat, 2),
                'Less: Input VAT (expenses)' => '-€' . number_format($inputVat, 2),
                'Net VAT Due' => '€' . number_format($vatLiability, 2) . ($vatLiability < 0 ? ' (reclaim)' : ''),
                'Net of VAT revenue (subtotals)' => '€' . number_format($netInvoicedSubtotal, 2),
                'Deductible expenses (ex-VAT)' => '€' . number_format($deductibleExpenses, 2),
            ];
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
            if (! $user->max_ssc_paid) {
                $ptRate = $sscPtRules['rate'] ?? 0.15;
                $maxCap = $sscPtRules['max_annual_contribution'] ?? 4024.65;
                $profitCap = $sscPtRules['max_annual_profit_cap'] ?? null;
                $sscBase = $netProfit;
                if (is_numeric($profitCap) && (float) $profitCap > 0) {
                    $sscBase = min($netProfit, (float) $profitCap);
                }
                $sscLiability = min($sscBase * $ptRate, $maxCap);
                
                $breakdowns['ssc'] = [
                    'Business Net Profit' => '€' . number_format($netProfit, 2),
                    'SSC Base (after profit cap)' => '€' . number_format($sscBase, 2),
                    'Pro-Rata SSC Rate' => ($ptRate * 100) . '%',
                    'Calculated SSC' => '€' . number_format($sscBase * $ptRate, 2),
                    'Maximum Annual Cap' => '€' . number_format($maxCap, 2),
                    'Final SSC Due' => '€' . number_format($sscLiability, 2) . ($sscLiability == $maxCap ? ' (Capped)' : ''),
                ];
            } else {
                $breakdowns['ssc'] = ['Status' => 'Exempt. You have certified that your maximum legal SSC is already paid through your primary employment.'];
            }
        } else {
            if (! empty($sscFtRules)) {
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

                // weekly_rate < 1 encodes a percentage-of-profit band (e.g. SF at 15%).
                if (isset($matchedBracket['weekly_rate']) && $matchedBracket['weekly_rate'] < 1) {
                    $sscLiability = $netProfit * $matchedBracket['weekly_rate'];
                    $calcStr = '€' . number_format($netProfit, 2) . ' × ' . ($matchedBracket['weekly_rate'] * 100) . '%';
                    $rateStr = ($matchedBracket['weekly_rate'] * 100) . '% of profit';
                } else {
                    $sscLiability = ($matchedBracket['weekly_rate'] ?? 0) * 52;
                    $calcStr = '€' . number_format($matchedBracket['weekly_rate'] ?? 0, 2) . ' × 52 weeks';
                    $rateStr = '€' . number_format($matchedBracket['weekly_rate'] ?? 0, 2) . ' / week';
                }

                $ageNote = 'Standard Class 2 self-employed table (age-specific SSC rate tables not applied yet).';
                if ($user->date_of_birth) {
                    $age = $user->date_of_birth->age;
                    $ageNote = "DOB on file (age {$age}). Age-banded SSC tables are not applied yet — using standard Class 2 rates.";
                }

                $breakdowns['ssc'] = [
                    'Business Net Profit' => '€' . number_format($netProfit, 2),
                    'Bracket Applied' => '€' . number_format($matchedBracket['min'] ?? 0) . ' - ' . (($matchedBracket['max'] ?? 9999999) >= 999999 ? 'No Limit' : '€' . number_format($matchedBracket['max'] ?? 0)),
                    'Weekly Rate' => $rateStr,
                    'Calculation' => $calcStr,
                    'Note' => $ageNote,
                    'Final SSC Due' => '€' . number_format($sscLiability, 2),
                ];
            }
        }

        // --- FETCH TAX PAYMENTS ---
        $taxPayments = TaxPayment::where('user_id', $user->id)
            ->where('year', $selectedYear)
            ->orderBy('payment_date', 'desc')
            ->get();
        
        $ptTaxPaid = $taxPayments->where('payment_type', 'income_tax')->sum('amount');
        $ptSscPaid = $taxPayments->where('payment_type', 'ssc')->sum('amount');
        $vatPaid = $taxPayments->where('payment_type', 'vat')->sum('amount');

        $totalTaxLiability = $incomeTaxLiability + $ta22Liability;
        $taxBalance = $totalTaxLiability - $ptTaxPaid;
        $sscBalance = $sscLiability - $ptSscPaid;
        $vatBalance = $vatLiability - $vatPaid;

        return view('reports.index', compact(
            'user', 'selectedYear', 'currentYear', 'earliestYear', 'isYearClosed', 'uninvoicedRfpCount', 'uninvoicedRfpCash',
            'collectedRevenue', 'invoicedRevenue', 'fiscalRevenue', 'isArticle10', 'netProfit', 'ta22Liability', 
            'incomeTaxLiability', 'sscLiability', 'vatLiability', 'appliedRatesYear', 'breakdowns',
            'taxPayments', 'ptTaxPaid', 'ptSscPaid', 'vatPaid', 'totalTaxLiability', 'taxBalance', 'sscBalance', 'vatBalance',
            'deductibleExpenses', 'expenseInfo', 'totals'
        ));
    }

    public function downloadTa22(Request $request)
    {
        $user = Auth::user();

        if ($user->employment_type !== 'part_time') {
            return redirect('/reports')->withErrors([
                'fiscal_error' => 'TA22 summary is only available for part-time self-employed profiles.',
            ]);
        }

        $selectedYear = (int) $request->input('year', date('Y'));
        $expenseInfo = $user->deductibleExpensesForYear($selectedYear);
        $deductibleExpenses = $expenseInfo['amount'];

        $totals = FiscalYearTotals::forUserYear($user->id, $selectedYear);
        $invoicedRevenue = $totals['net_total'];

        if ($user->vat_status === 'article_10') {
            $netProfit = max(0, $totals['net_subtotal'] - $deductibleExpenses);
        } else {
            $netProfit = max(0, $invoicedRevenue - $deductibleExpenses);
        }

        $ta22Rules = $this->getRatesSafely('ta22', $selectedYear);
        $ta22Cap = $ta22Rules['max_limit'] ?? 12000;
        $ta22Rate = $ta22Rules['rate'] ?? 0.10;
        $amountEligibleForTa22 = min($netProfit, $ta22Cap);
        $ta22Liability = $amountEligibleForTa22 * $ta22Rate;
        $spilloverProfit = max(0, $netProfit - $ta22Cap);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.ta22', compact(
            'user', 'selectedYear', 'invoicedRevenue', 'deductibleExpenses', 'expenseInfo',
            'netProfit', 'ta22Cap', 'ta22Rate', 'amountEligibleForTa22', 'ta22Liability', 'spilloverProfit'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('TA22-summary-' . $selectedYear . '.pdf');
    }

    // --- SAVE TAX PAYMENT ---
    public function storeTaxPayment(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'payment_type' => 'required|in:income_tax,ssc,vat',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date|before_or_equal:today',
        ]);

        if (DB::table('fiscal_years')->where('user_id', Auth::id())->where('year', $request->year)->exists()) {
            return back()->withErrors(['fiscal_error' => 'Cannot modify payments for a closed fiscal year.']);
        }

        TaxPayment::create([
            'user_id' => Auth::id(),
            'year' => $request->year,
            'payment_type' => $request->payment_type,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
        ]);

        return back()->with('success', 'Tax payment logged successfully.');
    }

    // --- DELETE TAX PAYMENT ---
    public function destroyTaxPayment($id)
    {
        $payment = TaxPayment::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        
        if (DB::table('fiscal_years')->where('user_id', Auth::id())->where('year', $payment->year)->exists()) {
            return back()->withErrors(['fiscal_error' => 'Cannot modify payments for a closed fiscal year.']);
        }

        $payment->delete();
        return back()->with('success', 'Tax payment removed.');
    }

    // --- THE YEAR-END CLOSING ENGINE ---
    public function closeYear(Request $request)
    {
        $request->validate(['year' => 'required|integer']);
        $year = $request->year;
        $user = Auth::user();

        if ($year >= date('Y')) {
            return back()->withErrors(['fiscal_error' => 'You cannot close the current fiscal year until December 31st has passed.']);
        }

        DB::table('fiscal_years')->insertOrIgnore([
            'user_id' => $user->id,
            'year' => $year,
            'closed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', "Fiscal Year {$year} has been permanently closed and locked. You may now send this final report to your accountant.");
    }

    // --- HELPER METHODS ---

    private function getRatesSafely($type, $year)
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

        // Never zero out income tax when brackets are missing from the DB.
        return $this->builtinFallbackRates((string) $type);
    }

    /**
     * Hardcoded Maltese fallbacks matching TaxRateSeeder — used only when tax_rates has no rows.
     */
    private function builtinFallbackRates(string $type): array
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

    private function calculateProgressiveTaxWithBracket($income, $brackets)
    {
        if (empty($brackets)) {
            return ['tax' => 0, 'bracket' => null];
        }

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