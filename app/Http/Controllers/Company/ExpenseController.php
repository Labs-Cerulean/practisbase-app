<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyExpense;
use App\Support\CompanyBooks;
use App\Support\CompanyLedger;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $year = (int) $request->input('year', $profile->first_period_end->format('Y'));

        $expenses = CompanyExpense::where('user_id', $user->id)
            ->whereYear('expense_date', $year)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        $owed = $expenses->filter(fn (CompanyExpense $e) => $e->isOwedToDirector())
            ->sum(fn (CompanyExpense $e) => $e->cashTotal());

        $reverseChargeVat = (float) $expenses
            ->filter(fn (CompanyExpense $e) => $e->is_reverse_charge)
            ->sum(fn (CompanyExpense $e) => $e->reverseChargeVat());

        return view('company.expenses-index', [
            'expenses' => $expenses,
            'categories' => CompanyExpense::CATEGORIES,
            'year' => $year,
            'owedToDirector' => $owed,
            'reverseChargeVat' => $reverseChargeVat,
            'profile' => $profile,
        ]);
    }

    public function create()
    {
        $profile = CompanyBooks::ensureProfile(Auth::user());

        return view('company.expenses-create', [
            'categories' => CompanyExpense::CATEGORIES,
            'profile' => $profile,
            'canReverseCharge' => $profile->isArticle10(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);

        $validated = $request->validate([
            'expense_date' => 'required|date|before_or_equal:today',
            'category' => 'required|in:'.implode(',', array_keys(CompanyExpense::CATEGORIES)),
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'vat_amount' => 'nullable|numeric|min:0',
            'is_reverse_charge' => 'sometimes|boolean',
            'funded_by' => 'required|in:company,director',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8192',
        ]);

        $isReverseCharge = $request->boolean('is_reverse_charge');

        if ($isReverseCharge && ! $profile->isArticle10()) {
            return back()->withErrors([
                'is_reverse_charge' => 'Reverse charge only applies while the company is on Article 10 VAT.',
            ])->withInput();
        }

        $expenseDate = $validated['expense_date'];
        if (strtotime($expenseDate) > $profile->first_period_end->getTimestamp()) {
            return back()->withErrors([
                'expense_date' => 'Expense date is after the first financial period end.',
            ])->withInput();
        }

        $isPre = strtotime($expenseDate) < $profile->first_period_start->getTimestamp();

        $amount = round((float) $validated['amount'], 2);
        if ($isReverseCharge) {
            $vatAmount = CompanyExpense::reverseChargeVatOn($amount);
        } else {
            $vatAmount = round((float) ($validated['vat_amount'] ?? 0), 2);
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store(
                TenantStorage::companyReceiptsPath($user->id),
                TenantStorage::diskName()
            );
        }

        $expense = CompanyExpense::create([
            'user_id' => $user->id,
            'expense_date' => $expenseDate,
            'category' => $validated['category'],
            'description' => $validated['description'],
            'amount' => $amount,
            'vat_amount' => $vatAmount,
            'is_reverse_charge' => $isReverseCharge,
            'funded_by' => $validated['funded_by'],
            'receipt_path' => $receiptPath,
            'is_pre_incorporation' => $isPre,
        ]);

        CompanyLedger::ensureChart($user);
        CompanyLedger::postExpense($expense);

        $msg = $isReverseCharge
            ? 'Expense logged with reverse charge (output + input VAT €'.number_format($vatAmount, 2).').'
            : 'Expense logged and posted to the ledger.';

        return redirect('/company/expenses')->with('success', $msg);
    }

    public function markRefunded(Request $request, int $expense)
    {
        $user = Auth::user();
        $model = CompanyExpense::where('user_id', $user->id)->where('id', $expense)->firstOrFail();

        if ($model->funded_by !== 'director') {
            return back()->withErrors(['expense' => 'Only director-funded costs can be marked refunded.']);
        }
        if ($model->director_refunded_at) {
            return back()->withErrors(['expense' => 'This director cost was already marked refunded.']);
        }

        $validated = $request->validate([
            'director_refunded_at' => 'required|date|before_or_equal:today',
            'refund_reference' => 'nullable|string|max:120',
        ]);

        $model->update([
            'director_refunded_at' => $validated['director_refunded_at'],
            'refund_reference' => $validated['refund_reference'] ?? null,
        ]);

        CompanyLedger::ensureChart($user);
        CompanyLedger::postDirectorRefund($model->fresh());

        return back()->with('success', 'Director refund recorded and posted.');
    }

    public function receipt(int $expense)
    {
        $user = Auth::user();
        $model = CompanyExpense::where('user_id', $user->id)->where('id', $expense)->firstOrFail();

        if (! filled($model->receipt_path)) {
            abort(404);
        }

        return TenantStorage::disk()->download($model->receipt_path);
    }
}
