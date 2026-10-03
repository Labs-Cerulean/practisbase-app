<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyExpense;
use App\Models\CompanyGlAccount;
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
        $yearStart = sprintf('%04d-01-01', $year);
        $yearEnd = sprintf('%04d-12-31', $year);
        $month = (int) date('n');
        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $invoiceRows = CompanyInvoice::query()
            ->where('user_id', $userId)
            ->whereBetween('issue_date', [$yearStart, $yearEnd])
            ->selectRaw("type, COALESCE(SUM(total), 0) as total_sum, COALESCE(SUM(vat_total), 0) as vat_sum")
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $invoiced = (float) ($invoiceRows->get('invoice')->total_sum ?? 0);
        $credits = (float) ($invoiceRows->get('credit_note')->total_sum ?? 0);
        $netBilled = $invoiced - $credits;
        $outputVat = max(0, (float) ($invoiceRows->get('invoice')->vat_sum ?? 0) - (float) ($invoiceRows->get('credit_note')->vat_sum ?? 0));

        $expenseAgg = CompanyExpense::query()
            ->where('user_id', $userId)
            ->whereNull('reversed_at')
            ->whereBetween('expense_date', [$yearStart, $yearEnd])
            ->selectRaw('COALESCE(SUM(amount), 0) as amount_sum, COALESCE(SUM(vat_amount), 0) as vat_sum, COALESCE(SUM(CASE WHEN is_reverse_charge THEN vat_amount ELSE 0 END), 0) as rc_vat_sum')
            ->first();

        $reverseChargeVat = round((float) ($expenseAgg->rc_vat_sum ?? 0), 2);
        $outputVat = round($outputVat + $reverseChargeVat, 2);
        $inputVat = (float) ($expenseAgg->vat_sum ?? 0);
        $expensesExVat = (float) ($expenseAgg->amount_sum ?? 0);

        $collected = (float) CompanyPayment::where('user_id', $userId)
            ->where('is_transfer', false)
            ->whereBetween('payment_date', [$yearStart, $yearEnd])
            ->sum('amount');

        $openRfps = CompanyInvoice::where('user_id', $userId)
            ->where('type', 'rfp')
            ->where('status', '!=', 'converted')
            ->whereNull('parent_document_id')
            ->count();

        $owedToDirector = (float) CompanyExpense::where('user_id', $userId)
            ->where('funded_by', 'director')
            ->whereNull('reversed_at')
            ->whereNull('director_refunded_at')
            ->get(['amount', 'vat_amount', 'is_reverse_charge'])
            ->sum(fn (CompanyExpense $e) => $e->cashTotal());

        $monthRows = CompanyInvoice::query()
            ->where('user_id', $userId)
            ->whereBetween('issue_date', [$monthStart, $monthEnd])
            ->whereIn('type', ['invoice', 'credit_note'])
            ->selectRaw("type, COALESCE(SUM(total), 0) as total_sum")
            ->groupBy('type')
            ->get()
            ->keyBy('type');
        $monthBilled = (float) ($monthRows->get('invoice')->total_sum ?? 0)
            - (float) ($monthRows->get('credit_note')->total_sum ?? 0);

        CompanyLedger::ensureChart($user);
        $asOf = now()->toDateString();
        $periodStart = $profile->first_period_start->format('Y-m-d');
        $pl = CompanyLedger::profitAndLoss($userId, $periodStart, $asOf);
        $bs = CompanyLedger::balanceSheet($userId, $asOf, $periodStart, $pl);

        $bankAccount = CompanyGlAccount::where('user_id', $userId)
            ->where('account_code', '1000')
            ->firstOrFail();
        $bankBalance = CompanyLedger::naturalBalance(
            $bankAccount,
            (float) (($bs['balances']['1000'] ?? 0))
        );

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
            'bankBalance' => $bankBalance,
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
