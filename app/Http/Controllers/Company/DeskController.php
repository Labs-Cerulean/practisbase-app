<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyExpense;
use App\Models\CompanyInvoice;
use App\Models\CompanyPayment;
use App\Support\CompanyBooks;
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

        $inputVat = (float) CompanyExpense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->sum('vat_amount');

        $expensesExVat = (float) CompanyExpense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->sum('amount');

        $collected = (float) CompanyPayment::where('user_id', $userId)
            ->whereYear('payment_date', $year)
            ->sum('amount');

        $openRfps = CompanyInvoice::where('user_id', $userId)
            ->where('type', 'rfp')
            ->whereNull('parent_document_id')
            ->count();

        $owedToDirector = (float) CompanyExpense::where('user_id', $userId)
            ->where('funded_by', 'director')
            ->whereNull('director_refunded_at')
            ->get()
            ->sum(fn (CompanyExpense $e) => $e->totalWithVat());

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

        return view('company.desk', [
            'profile' => $profile,
            'periodLabel' => CompanyBooks::periodLabel($profile),
            'netBilled' => $netBilled,
            'collected' => $collected,
            'expensesExVat' => $expensesExVat,
            'outputVat' => $outputVat,
            'inputVat' => $inputVat,
            'vatBalance' => $outputVat - $inputVat,
            'owedToDirector' => $owedToDirector,
            'openRfps' => $openRfps,
            'monthBilled' => $monthBilled,
            'year' => $year,
        ]);
    }
}
