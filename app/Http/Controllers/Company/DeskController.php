<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyExpense;
use App\Models\CompanyInvoice;
use App\Models\CompanyPayment;
use App\Support\CompanyBooks;
use App\Support\CompanyComplianceCalendar;
use App\Support\CompanyLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeskController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $userId = $user->id;
        $year = (int) $profile->first_period_end->format('Y');

        $invoiced = (float) CompanyInvoice::where('user_id', $userId)
            ->where('type', 'invoice')
            ->whereYear('issue_date', $year)
            ->sum('total');
        $credits = (float) CompanyInvoice::where('user_id', $userId)
            ->where('type', 'credit_note')
            ->whereYear('issue_date', $year)
            ->sum('total');
        $netBilled = $invoiced - $credits;

        $outputVat = (float) CompanyInvoice::where('user_id', $userId)
            ->where('type', 'invoice')
            ->whereYear('issue_date', $year)
            ->sum('vat_total');
        $creditVat = (float) CompanyInvoice::where('user_id', $userId)
            ->where('type', 'credit_note')
            ->whereYear('issue_date', $year)
            ->sum('vat_total');
        $outputVat = max(0, $outputVat - $creditVat);

        $reverseChargeVat = (float) CompanyExpense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->where('is_reverse_charge', true)
            ->sum('vat_amount');
        $outputVat = round($outputVat + $reverseChargeVat, 2);

        $inputVat = (float) CompanyExpense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->sum('vat_amount');

        $expensesExVat = (float) CompanyExpense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->sum('amount');

        $collected = (float) CompanyPayment::where('user_id', $userId)
            ->where('is_transfer', false)
            ->whereYear('payment_date', $year)
            ->sum('amount');

        $openRfps = CompanyInvoice::where('user_id', $userId)
            ->where('type', 'rfp')
            ->where('status', '!=', 'converted')
            ->whereNull('parent_document_id')
            ->count();

        $owedToDirector = (float) CompanyExpense::where('user_id', $userId)
            ->where('funded_by', 'director')
            ->whereNull('director_refunded_at')
            ->get()
            ->sum(fn (CompanyExpense $e) => $e->cashTotal());

        $month = (int) date('n');
        $monthBilled = (float) CompanyInvoice::where('user_id', $userId)
            ->where('type', 'invoice')
            ->whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->sum('total')
            - (float) CompanyInvoice::where('user_id', $userId)
                ->where('type', 'credit_note')
                ->whereYear('issue_date', $year)
                ->whereMonth('issue_date', $month)
                ->sum('total');

        CompanyLedger::ensureChart($user);
        $asOf = now()->toDateString();
        $periodStart = $profile->first_period_start->format('Y-m-d');
        $pl = CompanyLedger::profitAndLoss($userId, $periodStart, $asOf);
        $bs = CompanyLedger::balanceSheet($userId, $asOf, $periodStart);

        return view('company.desk', [
            'profile' => $profile,
            'periodLabel' => CompanyBooks::periodLabel($profile),
            'netBilled' => $netBilled,
            'collected' => $collected,
            'expensesExVat' => $expensesExVat,
            'outputVat' => $outputVat,
            'inputVat' => $inputVat,
            'reverseChargeVat' => $reverseChargeVat,
            'vatBalance' => $outputVat - $inputVat,
            'owedToDirector' => $owedToDirector,
            'openRfps' => $openRfps,
            'monthBilled' => $monthBilled,
            'year' => $year,
            'netProfit' => $pl['net_profit'],
            'booksBalanced' => $bs['balanced'],
            'bankBalance' => CompanyLedger::naturalBalance(
                \App\Models\CompanyGlAccount::where('user_id', $userId)->where('account_code', '1000')->firstOrFail(),
                (float) (CompanyLedger::accountBalances($userId, $asOf)['1000'] ?? 0)
            ),
            'complianceUpcoming' => CompanyComplianceCalendar::upcoming($profile, 8),
        ]);
    }

    public function compliance(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $year = (int) $request->input('year', date('Y'));
        if ($year < 2020 || $year > 2100) {
            $year = (int) date('Y');
        }

        return view('company.compliance', [
            'profile' => $profile,
            'year' => $year,
            'events' => CompanyComplianceCalendar::events($profile, $year),
            'upcoming' => CompanyComplianceCalendar::upcoming($profile, 12),
        ]);
    }
}
