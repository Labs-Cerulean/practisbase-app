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
        
        // 1. DYNAMIC YEAR TRAVERSAL
        $selectedYear = $request->input('year', date('Y'));
        
        // 2. CHECK IF YEAR IS LOCKED
        $isYearClosed = DB::table('fiscal_years')
            ->where('user_id', $user->id)
            ->where('year', $selectedYear)
            ->exists();

        // 3. HUNT FOR HIDDEN CASH (Paid but Uninvoiced RFPs)
        $uninvoicedRfps = Invoice::where('user_id', $user->id)
            ->where('type', 'rfp')
            ->where('amount_paid', '>', 0)
            ->where('status', '!=', 'converted')
            ->whereYear('issue_date', '<=', $selectedYear)
            ->get();
            
        $uninvoicedRfpCount = $uninvoicedRfps->count();
        $uninvoicedRfpCash = $uninvoicedRfps->sum('amount_paid');

        // --- 4. STRICT FISCAL REVENUE ENGINE ---
        
        // Accrual-Basis: Total Official Revenue Billed THIS selected year.
        $totalInvoiced = Invoice::where('user_id', $user->id)
            ->where('type', 'invoice')
            ->whereYear('issue_date', $selectedYear)
            ->sum('total');
            
        $totalCredited = Invoice::where('user_id', $user->id)
            ->where('type', 'credit_note')
            ->whereYear('issue_date', $selectedYear)
            ->sum('total');
            
        $invoicedRevenue = max(0, $totalInvoiced - $totalCredited);

        // Cash-Basis: Kept strictly for the "Cash Collected" metric, NOT used for tax math.
        $collectedRevenue = Payment::where('user_id', $user->id)
            ->whereYear('payment_date', $selectedYear)
            ->whereHas('invoice', function($q) {
                $q->where('type', 'invoice');
            })
            ->sum('amount');

        // --- ACCRUAL PROFIT CALCULATION ---
        $netProfit = max(0, $invoicedRevenue - $user->estimated_expenses);

        // --- 5. FETCH GOVERNMENT BRACKETS ---
        $computationType = 'income_' . $user->tax_computation;
        
        $taxBrackets = TaxRate::where('year', $selectedYear)->where('type', $computationType)->first()?->rates_json ?? [];
        $ta22Rules = TaxRate::where('year', $selectedYear)->where('type', 'ta22')->first()?->rates_json ?? [];
        $sscPtRules = TaxRate::where('year', $selectedYear)->where('type', 'ssc_pt')->first()?->rates_json ?? [];
        $sscFtRules = TaxRate::where('year', $selectedYear)->where('type', 'ssc_ft')->first()?->rates_json ?? [];

        // --- 6. INITIALIZE LIABILITIES ---
        $incomeTaxLiability = 0;
        $ta22Liability = 0;
        $sscLiability = 0;
        $vatLiability = 0;

        // --- MATH ENGINE: VAT (Accrual Basis) ---
        if ($user->vat_status === 'article_10') {
            $vatLiability = $invoicedRevenue - ($invoicedRevenue / 1.18);
            // Deduct VAT from the revenue before calculating Income Tax/SSC profit!
            $netProfit = max(0, ($invoicedRevenue / 1.18) - $user->estimated_expenses);
        }

        // --- MATH ENGINE: INCOME TAX ---
        if ($user->employment_type === 'part_time') {
            $ta22Cap = $ta22Rules['max_limit'] ?? 12000;
            $ta22Rate = $ta22Rules['rate'] ?? 0.10;

            $amountEligibleForTa22 = min($netProfit, $ta22Cap);
            $ta22Liability = $amountEligibleForTa22 * $ta22Rate;

            $spilloverProfit = max(0, $netProfit - $ta22Cap);
            if ($spilloverProfit > 0) {
                $totalTaxableIncome = $user->primary_salary + $spilloverProfit;
                $incomeTaxLiability = $this->calculateProgressiveTax($totalTaxableIncome, $taxBrackets);
                $baseSalaryTax = $this->calculateProgressiveTax($user->primary_salary, $taxBrackets);
                $incomeTaxLiability = max(0, $incomeTaxLiability - $baseSalaryTax);
            }
        } else {
            $totalTaxableIncome = $user->primary_salary + $netProfit;
            $incomeTaxLiability = $this->calculateProgressiveTax($totalTaxableIncome, $taxBrackets);
            $baseSalaryTax = $this->calculateProgressiveTax($user->primary_salary, $taxBrackets);
            $incomeTaxLiability = max(0, $incomeTaxLiability - $baseSalaryTax);
        }

        // --- MATH ENGINE: SOCIAL SECURITY (SSC) ---
        if ($user->employment_type === 'part_time') {
            if (!$user->max_ssc_paid) {
                $ptRate = $sscPtRules['rate'] ?? 0.15;
                $maxCap = $sscPtRules['max_annual_contribution'] ?? 4024.65;
                $sscLiability = min($netProfit * $ptRate, $maxCap);
            }
        } else {
            foreach ($sscFtRules as $bracket) {
                if ($netProfit >= $bracket['min'] && $netProfit <= ($bracket['max'] + 0.99)) {
                    if (isset($bracket['weekly_rate']) && $bracket['weekly_rate'] < 1) {
                         $sscLiability = $netProfit * $bracket['weekly_rate'];
                    } else {
                         $sscLiability = $bracket['weekly_rate'] * 52;
                    }
                    break;
                }
            }
        }

        return view('reports.index', compact(
            'user', 
            'selectedYear', 
            'isYearClosed',
            'uninvoicedRfpCount',
            'uninvoicedRfpCash',
            'collectedRevenue', 
            'invoicedRevenue', 
            'netProfit', 
            'ta22Liability', 
            'incomeTaxLiability', 
            'sscLiability', 
            'vatLiability'
        ));
    }

    // --- 7. THE YEAR-END CLOSING ENGINE ---
    public function closeYear(Request $request)
    {
        $request->validate(['year' => 'required|integer']);
        $year = $request->year;
        $user = Auth::user();

        // Security 1: Cannot close current or future years
        if ($year >= date('Y')) {
            return back()->withErrors(['fiscal_error' => 'You cannot close the current fiscal year until December 31st has passed.']);
        }

        // Permanently Lock the Year
        DB::table('fiscal_years')->insertOrIgnore([
            'user_id' => $user->id,
            'year' => $year,
            'closed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', "Fiscal Year {$year} has been permanently closed and locked. You may now send this final report to your accountant.");
    }

    private function calculateProgressiveTax($income, $brackets)
    {
        $tax = 0;
        foreach ($brackets as $bracket) {
            if ($income >= $bracket['min'] && $income <= ($bracket['max'] + 0.99)) {
                $tax = ($income * $bracket['rate']) - $bracket['subtract'];
                break;
            }
        }
        return max(0, $tax);
    }
}