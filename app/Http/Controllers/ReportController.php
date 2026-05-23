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
        // We only calculate tax on 'paid' or 'partially_paid' invoices for the current year.
        $collectedRevenue = Invoice::where('user_id', $user->id)
            ->where('type', 'invoice')
            ->whereYear('issue_date', $currentYear)
            ->sum('amount_paid');

        $netProfit = max(0, $collectedRevenue - $user->estimated_expenses);

        // 2. Fetch the Government Rules for the current year
        $computationType = 'income_' . $user->tax_computation;
        
        // Using value() instead of first() prevents crashes if the user hasn't saved their settings yet!
        $taxBrackets = TaxRate::where('year', $currentYear)->where('type', $computationType)->value('rates_json') ?? [];
        $ta22Rules = TaxRate::where('year', $currentYear)->where('type', 'ta22')->value('rates_json') ?? [];
        $sscPtRules = TaxRate::where('year', $currentYear)->where('type', 'ssc_pt')->value('rates_json') ?? [];
        $sscFtRules = TaxRate::where('year', $currentYear)->where('type', 'ssc_ft')->value('rates_json') ?? [];

        // 3. Initialize Liabilities
        $incomeTaxLiability = 0;
        $ta22Liability = 0;
        $sscLiability = 0;
        $vatLiability = 0;

        // --- MATH ENGINE: VAT ---
        if ($user->vat_status === 'article_10') {
            // Under Article 10, the collected revenue INCLUDES 18% VAT. 
            // To find the VAT portion: Revenue - (Revenue / 1.18)
            $vatLiability = $collectedRevenue - ($collectedRevenue / 1.18);
            // We must also adjust Net Profit to exclude VAT
            $netProfit = max(0, ($collectedRevenue / 1.18) - $user->estimated_expenses);
        }

        // --- MATH ENGINE: INCOME TAX ---
        if ($user->employment_type === 'part_time') {
            // TA22 Logic (10% on first €12k)
            $ta22Cap = $ta22Rules['max_limit'] ?? 12000;
            $ta22Rate = $ta22Rules['rate'] ?? 0.10;

            $amountEligibleForTa22 = min($netProfit, $ta22Cap);
            $ta22Liability = $amountEligibleForTa22 * $ta22Rate;

            // Spillover to Progressive Tax
            $spilloverProfit = max(0, $netProfit - $ta22Cap);
            if ($spilloverProfit > 0) {
                $totalTaxableIncome = $user->primary_salary + $spilloverProfit;
                $incomeTaxLiability = $this->calculateProgressiveTax($totalTaxableIncome, $taxBrackets);
                // Subtract the tax they already pay on their main salary so we only show the business liability
                $baseSalaryTax = $this->calculateProgressiveTax($user->primary_salary, $taxBrackets);
                $incomeTaxLiability = max(0, $incomeTaxLiability - $baseSalaryTax);
            }
        } else {
            // Full-Time Progressive Tax Logic
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
            // Full-Time Class 2 SSC Logic
            foreach ($sscFtRules as $bracket) {
                if ($netProfit >= $bracket['min'] && $netProfit <= $bracket['max']) {
                    if (isset($bracket['weekly_rate']) && $bracket['weekly_rate'] < 1) {
                         // It's a percentage (like Category SF - 15%)
                         $sscLiability = $netProfit * $bracket['weekly_rate'];
                    } else {
                         // It's a fixed weekly rate
                         $sscLiability = $bracket['weekly_rate'] * 52;
                    }
                    break;
                }
            }
        }

        // 4. Return data to the view
        return view('reports.index', compact(
            'user', 
            'currentYear', 
            'collectedRevenue', 
            'netProfit', 
            'ta22Liability', 
            'incomeTaxLiability', 
            'sscLiability', 
            'vatLiability'
        ));
    }

    /**
     * Helper Function: Calculates Progressive Tax based on CFR brackets.
     */
    private function calculateProgressiveTax($income, $brackets)
    {
        $tax = 0;
        foreach ($brackets as $bracket) {
            if ($income >= $bracket['min'] && $income <= $bracket['max']) {
                $tax = ($income * $bracket['rate']) - $bracket['subtract'];
                break;
            }
        }
        return max(0, $tax);
    }
}