<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Support\FiscalYearGuard;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $year = (int) $request->input('year', date('Y'));

        $expenses = Expense::where('user_id', $user->id)
            ->whereYear('expense_date', $year)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        $yearTotal = $expenses->sum(fn (Expense $e) => $e->totalWithVat());
        $categories = Expense::CATEGORIES;
        $years = range((int) date('Y'), (int) date('Y') - 5);

        return view('expenses.index', compact('expenses', 'year', 'yearTotal', 'categories', 'years', 'user'));
    }

    public function create()
    {
        return view('expenses.create', [
            'categories' => Expense::CATEGORIES,
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'expense_date' => 'required|date|before_or_equal:today',
            'category' => 'required|in:' . implode(',', array_keys(Expense::CATEGORIES)),
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'vat_amount' => 'nullable|numeric|min:0',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $year = FiscalYearGuard::yearFromDate($validated['expense_date']);
        if ($lockError = FiscalYearGuard::ensureOpen($user->id, $year)) {
            return back()->withErrors(['fiscal_error' => $lockError])->withInput();
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store(
                TenantStorage::receiptsPath($user->id),
                TenantStorage::diskName()
            );
        }

        Expense::create([
            'user_id' => $user->id,
            'expense_date' => $validated['expense_date'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'vat_amount' => $validated['vat_amount'] ?? 0,
            'receipt_path' => $receiptPath,
        ]);

        return redirect('/expenses?year=' . $year)->with('success', 'Expense logged successfully.');
    }

    public function destroy(Expense $expense)
    {
        $user = Auth::user();

        if ($expense->user_id !== $user->id) {
            abort(403);
        }

        $year = FiscalYearGuard::yearFromDate($expense->expense_date);
        if ($lockError = FiscalYearGuard::ensureOpen($user->id, $year)) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }

        if ($expense->receipt_path) {
            TenantStorage::disk()->delete($expense->receipt_path);
        }

        $expense->delete();

        return back()->with('success', 'Expense removed.');
    }

    public function downloadReceipt(Expense $expense)
    {
        $user = Auth::user();

        if ($expense->user_id !== $user->id) {
            abort(403);
        }

        if (! $expense->receipt_path || ! TenantStorage::disk()->exists($expense->receipt_path)) {
            abort(404, 'Receipt file not found.');
        }

        return TenantStorage::disk()->download(
            $expense->receipt_path,
            'receipt-' . $expense->id . '.' . pathinfo($expense->receipt_path, PATHINFO_EXTENSION)
        );
    }
}
