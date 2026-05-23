<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\TaxRate;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $currentYear = date('Y');

        // 1. Get Financial Totals
        // Cash-Basis: Only what has actually hit the bank account
        $collectedRevenue = Invoice::where('user_id', $user->id)
            ->where('type', 'invoice')
            ->whereYear('issue_date', $currentYear)
            ->sum('amount_paid');

        $netProfit = max(0, $collectedRevenue - $user->estimated_expenses);

        // Accrual-Basis: Total Billed (Needed strictly for VAT Article 11 Compliance)
        $invoicedRevenue = Invoice::where('user_id', $user->id)->where('type', 'invoice')->whereYear('issue_date', $currentYear)->sum('total') 
                         - Invoice::where('user_id', $user->id)->where('type', 'credit_note')->whereYear('issue_date', $currentYear)->sum('total');

        // 2. Fetch the Government Rules safely (Restoring the array casts)
        $computationType = 'income_' . $user->tax_computation;
        
        $taxBrackets = TaxRate::where('year', $currentYear)->where('type', $computationType)->first()?->rates_json ?? [];
        $ta22Rules = TaxRate::where('year', $currentYear)->where('type', 'ta22')->first()?->rates_json ?? [];
        $sscPtRules = TaxRate::where('year', $currentYear)->where('type', 'ssc_pt')->first()?->rates_json ?? [];
        $sscFtRules = TaxRate::where('year', $currentYear)->where('type', 'ssc_ft')->first()?->rates_json ?? [];

        // 3. Initialize Liabilities
        $incomeTaxLiability = 0;
        $ta22Liability = 0;
        $sscLiability = 0;
        $vatLiability = 0;

        // --- MATH ENGINE: VAT ---
        if ($user->vat_status === 'article_10') {
            $vatLiability = $collectedRevenue - ($collectedRevenue / 1.18);
            $netProfit = max(0, ($collectedRevenue / 1.18) - $user->estimated_expenses);
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
                // FIXED: Adding 0.99 to max to catch decimal net profits!
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
            'currentYear', 
            'collectedRevenue', 
            'invoicedRevenue', // NEW: Sent to the view for VAT limits
            'netProfit', 
            'ta22Liability', 
            'incomeTaxLiability', 
            'sscLiability', 
            'vatLiability'
        ));
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