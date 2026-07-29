<?php

namespace App\Http\Controllers;

use App\Models\CapitalAsset;
use App\Models\Expense;
use App\Support\ExpenseTreatment;
use App\Support\FiscalYearGuard;
use App\Support\RegimeHistory;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $year = (int) $request->input('year', date('Y'));

        $expenses = Expense::where('user_id', $user->id)
            ->whereYear('expense_date', $year)
            ->with('capitalAsset')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        $yearTotal = $expenses->sum(fn (Expense $e) => $e->totalWithVat());
        $categories = Expense::CATEGORIES;
        $years = range((int) date('Y'), (int) date('Y') - 5);

        $assets = CapitalAsset::where('user_id', $user->id)
            ->whereYear('purchase_date', '<=', $year)
            ->orderByDesc('purchase_date')
            ->get()
            ->map(function (CapitalAsset $asset) use ($year) {
                return [
                    'id' => $asset->id,
                    'description' => $asset->description,
                    'asset_class' => $asset->asset_class,
                    'purchase_date' => $asset->purchase_date->format('Y-m-d'),
                    'cost_basis' => (float) $asset->cost_basis,
                    'business_use_percent' => (float) $asset->business_use_percent,
                    'annual_rate' => (float) $asset->annual_rate,
                    'allowance_this_year' => $asset->allowanceForYear($year),
                ];
            });

        return view('expenses.index', compact(
            'expenses', 'year', 'yearTotal', 'categories', 'years', 'user', 'assets'
        ));
    }

    public function create()
    {
        $user = Auth::user();

        return view('expenses.create', [
            'categories' => Expense::CATEGORIES,
            'user' => $user,
            'capitalRates' => ExpenseTreatment::CAPITAL_RATES,
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
            'business_use_percent' => 'nullable|numeric|min:1|max:100',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $category = $validated['category'];
        $year = FiscalYearGuard::yearFromDate($validated['expense_date']);
        if ($lockError = FiscalYearGuard::ensureOpen($user->id, $year)) {
            return back()->withErrors(['fiscal_error' => $lockError])->withInput();
        }

        if (ExpenseTreatment::requiresBusinessUsePercent($category) || $category === 'car') {
            $request->validate([
                'business_use_percent' => 'required|numeric|min:1|max:100',
            ], [
                'business_use_percent.required' => 'Enter the practice-use percentage (personal use is not claimable). Use the helper if you are unsure.',
            ]);
            $validated['business_use_percent'] = (float) $request->input('business_use_percent');
        } elseif (ExpenseTreatment::requiresHomeOfficePercent($category)) {
            $homePct = $user->home_office_percent;
            if ($homePct === null || (float) $homePct <= 0) {
                return back()->withErrors([
                    'category' => 'Set your home-office percentage first (use the Working from home helper), then log household bills.',
                ])->withInput();
            }
            $validated['business_use_percent'] = (float) $homePct;
        } else {
            $validated['business_use_percent'] = null;
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store(
                TenantStorage::receiptsPath($user->id),
                TenantStorage::diskName()
            );
        }

        $amount = (float) $validated['amount'];
        $vatAmount = (float) ($validated['vat_amount'] ?? 0);
        $businessUse = isset($validated['business_use_percent'])
            ? (float) $validated['business_use_percent']
            : null;

        DB::transaction(function () use ($user, $validated, $category, $amount, $vatAmount, $businessUse, $receiptPath) {
            $expense = Expense::create([
                'user_id' => $user->id,
                'expense_date' => $validated['expense_date'],
                'category' => $category,
                'description' => $validated['description'],
                'amount' => $amount,
                'vat_amount' => $vatAmount,
                'business_use_percent' => $businessUse,
                'receipt_path' => $receiptPath,
            ]);

            if (ExpenseTreatment::isCapital($category)) {
                $regime = RegimeHistory::forDate($user, $validated['expense_date']);
                $isArt10 = ($regime['vat_status'] ?? '') === 'article_10';
                $costBasis = $isArt10 ? $amount : ($amount + $vatAmount);
                $rate = ExpenseTreatment::capitalRate($category) ?? 0.25;
                $pct = $category === 'car'
                    ? (float) $businessUse
                    : 100.0;

                CapitalAsset::create([
                    'user_id' => $user->id,
                    'expense_id' => $expense->id,
                    'asset_class' => $category,
                    'description' => $validated['description'],
                    'purchase_date' => $validated['expense_date'],
                    'cost_basis' => $costBasis,
                    'cost_ex_vat' => $amount,
                    'vat_amount' => $vatAmount,
                    'business_use_percent' => $pct,
                    'annual_rate' => $rate,
                ]);
            }

            if ($category === 'car' && $businessUse !== null) {
                $user->update(['car_business_use_percent' => $businessUse]);
            }
            if ($category === 'fuel' && $businessUse !== null && $user->car_business_use_percent === null) {
                $user->update(['car_business_use_percent' => $businessUse]);
            }
        });

        $msg = 'Expense logged successfully.';
        if (ExpenseTreatment::isCapital($category)) {
            $rate = (ExpenseTreatment::capitalRate($category) ?? 0.25) * 100;
            $msg .= " This is a capital item — about {$rate}% wear & tear per year is applied for tax (not the full cost in year one).";
        } elseif (ExpenseTreatment::requiresBusinessUsePercent($category) || ExpenseTreatment::requiresHomeOfficePercent($category)) {
            $msg .= ' Only the practice / home-office share counts toward your deductible expenses.';
        }

        return redirect('/expenses?year=' . $year)->with('success', $msg);
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

        DB::transaction(function () use ($expense) {
            CapitalAsset::where('expense_id', $expense->id)->delete();
            if ($expense->receipt_path) {
                TenantStorage::disk()->delete($expense->receipt_path);
            }
            $expense->delete();
        });

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

    public function attachReceipt(Request $request, Expense $expense)
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
            return back()->withErrors(['receipt' => 'This expense already has a receipt. Remove the expense and re-add it to replace the file.']);
        }

        $request->validate([
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $path = $request->file('receipt')->store(
            TenantStorage::receiptsPath($user->id),
            TenantStorage::diskName()
        );

        $expense->update(['receipt_path' => $path]);

        return back()->with('success', 'Receipt attached (private tenant storage / Cloudflare R2 when configured).');
    }

    public function updateBusinessUse(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'car_business_use_percent' => 'nullable|numeric|min:1|max:100',
            'home_office_percent' => 'nullable|numeric|min:1|max:100',
        ]);

        $updates = [];
        if ($request->filled('car_business_use_percent')) {
            $updates['car_business_use_percent'] = (float) $validated['car_business_use_percent'];
        }
        if ($request->filled('home_office_percent')) {
            $updates['home_office_percent'] = (float) $validated['home_office_percent'];
        }

        if ($updates === []) {
            return back()->withErrors(['business_use' => 'Enter at least one percentage to save.']);
        }

        $user->update($updates);

        $redirect = $request->input('redirect_to', '/expenses');
        if (! is_string($redirect) || ! str_starts_with($redirect, '/')) {
            $redirect = '/expenses';
        }

        return redirect($redirect)->with('success', 'Business-use percentages saved. New car, fuel, and home bills will use these figures.');
    }
}
