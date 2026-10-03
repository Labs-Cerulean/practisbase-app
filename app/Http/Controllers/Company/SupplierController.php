<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyExpense;
use App\Models\CompanySupplier;
use App\Support\CompanyBooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $year = (int) $request->input('year', $profile->first_period_end->format('Y'));

        $suppliers = CompanySupplier::where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        $expenses = CompanyExpense::where('user_id', $user->id)
            ->whereYear('expense_date', $year)
            ->get(['id', 'company_supplier_id', 'amount', 'vat_amount', 'is_reverse_charge', 'funded_by']);

        $spend = [];
        $unassignedCount = 0;
        $unassignedCash = 0.0;
        foreach ($expenses as $expense) {
            if (! $expense->company_supplier_id) {
                $unassignedCount++;
                $unassignedCash += $expense->cashTotal();
                continue;
            }
            $id = (int) $expense->company_supplier_id;
            if (! isset($spend[$id])) {
                $spend[$id] = ['count' => 0, 'net' => 0.0, 'cash' => 0.0];
            }
            $spend[$id]['count']++;
            $spend[$id]['net'] += (float) $expense->amount;
            $spend[$id]['cash'] += $expense->cashTotal();
        }

        $ranked = $suppliers->sortByDesc(fn (CompanySupplier $supplier) => $spend[$supplier->id]['cash'] ?? 0)->values();

        return view('company.suppliers-index', [
            'suppliers' => $ranked,
            'spend' => $spend,
            'year' => $year,
            'categories' => CompanyExpense::CATEGORIES,
            'unassignedCount' => $unassignedCount,
            'unassignedCash' => $unassignedCash,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'vat_number' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'country' => 'nullable|string|max:80',
            'address' => 'nullable|string|max:1000',
            'default_category' => 'nullable|in:'.implode(',', array_keys(CompanyExpense::CATEGORIES)),
        ]);

        $supplier = CompanySupplier::resolveForUser($user->id, $validated['name'], [
            'vat_number' => $validated['vat_number'] ?? null,
            'email' => $validated['email'] ?? null,
            'country' => $validated['country'] ?? null,
            'address' => $validated['address'] ?? null,
            'default_category' => $validated['default_category'] ?? null,
        ]);

        return redirect('/company/suppliers')->with('success', 'Supplier saved: '.$supplier->name.'.');
    }
}
