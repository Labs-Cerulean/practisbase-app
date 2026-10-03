<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyExpense;
use App\Models\CompanySupplier;
use App\Support\CompanyBooks;
use App\Support\CompanyLedger;
use App\Support\PdfPlainText;
use App\Support\SupplierInvoiceReader;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $year = (int) $request->input('year', $profile->first_period_end->format('Y'));

        $suppliers = CompanySupplier::where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        $supplierFilter = null;
        if ($request->filled('supplier')) {
            $supplierFilter = $suppliers->firstWhere('id', (int) $request->input('supplier'));
        }

        $expenses = CompanyExpense::where('user_id', $user->id)
            ->with('supplier')
            ->whereYear('expense_date', $year)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        $unassignedCount = $expenses->whereNull('company_supplier_id')->count();
        $supplierRows = $suppliers->map(fn (CompanySupplier $supplier) => [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'vat_number' => $supplier->vat_number,
        ])->all();

        $suggestions = [];
        foreach ($expenses as $expense) {
            if ($expense->company_supplier_id) {
                continue;
            }
            $suggestions[$expense->id] = SupplierInvoiceReader::suggestSupplierId(
                (string) $expense->description,
                $supplierRows
            );
        }
        $suggestedCount = count(array_filter($suggestions));

        if ($supplierFilter) {
            $expenses = $expenses
                ->where('company_supplier_id', $supplierFilter->id)
                ->values();
        }

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
            'suppliers' => $suppliers,
            'supplierFilter' => $supplierFilter,
            'unassignedCount' => $unassignedCount,
            'suggestions' => $suggestions,
            'suggestedCount' => $suggestedCount,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $suppliers = CompanySupplier::where('user_id', $user->id)->orderBy('name')->get();
        $draft = $this->draftFromRequest($request);

        return view('company.expenses-create', [
            'categories' => CompanyExpense::CATEGORIES,
            'profile' => $profile,
            'canReverseCharge' => $profile->isArticle10(),
            'suppliers' => $suppliers,
            'draft' => $draft,
            'read' => $draft['read'] ?? [],
        ]);
    }

    public function intake(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);

        $request->validate([
            'invoice' => 'required|file|mimes:pdf|max:8192',
        ]);

        $binary = (string) $request->file('invoice')->get();
        $text = PdfPlainText::fromBinary($binary);

        $suppliers = CompanySupplier::where('user_id', $user->id)->orderBy('name')->get();
        $rows = $suppliers->map(fn (CompanySupplier $supplier) => [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'vat_number' => $supplier->vat_number,
        ])->all();

        $ignore = array_values(array_filter([
            (string) $profile->legal_name,
            'Cerulean Labs Limited',
            'Cerulean Labs',
            'PractisBase',
        ]));

        $read = SupplierInvoiceReader::read($text, $rows, $ignore);

        $this->forgetDraftFile();

        $token = Str::random(40);
        $dir = TenantStorage::companyReceiptsPath($user->id).'/drafts';
        $path = $request->file('invoice')->storeAs($dir, $token.'.pdf', TenantStorage::diskName());
        if (! is_string($path) || $path === '') {
            return back()->withErrors([
                'invoice' => 'The invoice could not be stored. Try the upload again.',
            ])->withInput();
        }

        session([
            'company_expense_invoice_draft' => [
                'token' => $token,
                'path' => $path,
                'original_name' => $request->file('invoice')->getClientOriginalName(),
                'read' => $read,
            ],
        ]);

        $message = $read['supplier_name']
            ? 'Invoice read. Check the supplier and the amounts, then save.'
            : 'Invoice attached. Check the details below, then save.';

        return redirect('/company/expenses/create?draft='.$token)->with('success', $message);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);

        $validated = $request->validate([
            'expense_date' => 'required|date|before_or_equal:today',
            'category' => 'required|in:'.implode(',', array_keys(CompanyExpense::CATEGORIES)),
            'description' => 'required|string|max:1000',
            'supplier_invoice_number' => 'nullable|string|max:120',
            'amount' => 'required|numeric|min:0.01',
            'vat_amount' => 'nullable|numeric|min:0',
            'is_reverse_charge' => 'sometimes|boolean',
            'funded_by' => 'required|in:company,director',
            'supplier_mode' => 'required|in:existing,new',
            'company_supplier_id' => 'nullable|integer',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_vat' => 'nullable|string|max:64',
            'supplier_email' => 'nullable|email|max:255',
            'supplier_country' => 'nullable|string|max:80',
            'supplier_address' => 'nullable|string|max:1000',
            'draft_token' => 'nullable|string|max:80',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8192',
        ]);

        if ($validated['supplier_mode'] === 'existing' && empty($validated['company_supplier_id'])) {
            return back()->withErrors([
                'company_supplier_id' => 'Choose the supplier this invoice belongs to.',
            ])->withInput();
        }
        if ($validated['supplier_mode'] === 'new' && trim((string) ($validated['supplier_name'] ?? '')) === '') {
            return back()->withErrors([
                'supplier_name' => 'Enter the supplier name, or link an existing supplier.',
            ])->withInput();
        }

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

        try {
            CompanyLedger::assertDateOpen($user->id, $expenseDate);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $isPre = strtotime($expenseDate) < $profile->first_period_start->getTimestamp();

        $amount = round((float) $validated['amount'], 2);
        if ($isReverseCharge) {
            $vatAmount = CompanyExpense::reverseChargeVatOn($amount);
        } else {
            $vatAmount = round((float) ($validated['vat_amount'] ?? 0), 2);
        }

        if ($validated['supplier_mode'] === 'existing') {
            $supplier = CompanySupplier::where('user_id', $user->id)
                ->where('id', (int) $validated['company_supplier_id'])
                ->firstOrFail();
        } else {
            $supplier = CompanySupplier::resolveForUser($user->id, (string) $validated['supplier_name'], [
                'vat_number' => $validated['supplier_vat'] ?? null,
                'email' => $validated['supplier_email'] ?? null,
                'country' => $validated['supplier_country'] ?? null,
                'address' => $validated['supplier_address'] ?? null,
                'default_category' => $validated['category'],
            ]);
        }

        $invoiceNumber = trim((string) ($validated['supplier_invoice_number'] ?? ''));
        if ($invoiceNumber !== '') {
            $duplicate = CompanyExpense::where('user_id', $user->id)
                ->where('company_supplier_id', $supplier->id)
                ->whereRaw('lower(supplier_invoice_number) = ?', [mb_strtolower($invoiceNumber)])
                ->exists();
            if ($duplicate) {
                return back()->withErrors([
                    'supplier_invoice_number' => 'This supplier invoice number is already logged.',
                ])->withInput();
            }
        }

        $receiptPath = null;
        $draft = $this->draftFromToken((string) ($validated['draft_token'] ?? ''));
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store(
                TenantStorage::companyReceiptsPath($user->id),
                TenantStorage::diskName()
            );
            $this->forgetDraftFile();
        } elseif ($draft && filled($draft['path'] ?? null)) {
            $receiptPath = $this->promoteDraft($user->id, $draft);
        }

        $expense = CompanyExpense::create([
            'user_id' => $user->id,
            'company_supplier_id' => $supplier->id,
            'expense_date' => $expenseDate,
            'category' => $validated['category'],
            'description' => $validated['description'],
            'supplier_invoice_number' => $invoiceNumber !== '' ? $invoiceNumber : null,
            'amount' => $amount,
            'vat_amount' => $vatAmount,
            'is_reverse_charge' => $isReverseCharge,
            'funded_by' => $validated['funded_by'],
            'receipt_path' => $receiptPath,
            'is_pre_incorporation' => $isPre,
        ]);

        session()->forget('company_expense_invoice_draft');

        CompanyLedger::ensureChart($user);
        CompanyLedger::postExpense($expense);

        $msg = $isReverseCharge
            ? 'Expense logged for '.$supplier->name.' with reverse charge (output + input VAT €'.number_format($vatAmount, 2).').'
            : 'Expense logged for '.$supplier->name.' and posted to the ledger.';

        return redirect('/company/expenses')->with('success', $msg);
    }

    public function assignSuggested(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $year = (int) $request->input('year', $profile->first_period_end->format('Y'));

        $suppliers = CompanySupplier::where('user_id', $user->id)->get();
        $rows = $suppliers->map(fn (CompanySupplier $supplier) => [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'vat_number' => $supplier->vat_number,
        ])->all();

        $expenses = CompanyExpense::where('user_id', $user->id)
            ->whereYear('expense_date', $year)
            ->whereNull('company_supplier_id')
            ->get();

        $plan = [];
        foreach ($expenses as $expense) {
            $supplierId = SupplierInvoiceReader::suggestSupplierId((string) $expense->description, $rows);
            if (! $supplierId || ! $suppliers->firstWhere('id', $supplierId)) {
                continue;
            }
            try {
                CompanyLedger::assertDateOpen($user->id, $expense->expense_date->format('Y-m-d'));
            } catch (ValidationException $e) {
                return back()->withErrors($e->errors());
            }
            $plan[] = [$expense, $supplierId];
        }

        foreach ($plan as [$expense, $supplierId]) {
            $expense->update(['company_supplier_id' => $supplierId]);
        }
        $linked = count($plan);

        $message = $linked > 0
            ? $linked.' '.($linked === 1 ? 'expense was' : 'expenses were').' linked to the suggested supplier.'
            : 'No expenses matched a saved supplier name.';

        return back()->with('success', $message);
    }

    public function assignSupplier(Request $request, int $expense)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'company_supplier_id' => 'required|integer',
        ]);

        $model = CompanyExpense::where('user_id', $user->id)->where('id', $expense)->firstOrFail();
        $supplier = CompanySupplier::where('user_id', $user->id)
            ->where('id', (int) $validated['company_supplier_id'])
            ->firstOrFail();

        try {
            CompanyLedger::assertDateOpen($user->id, $model->expense_date->format('Y-m-d'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $model->update([
            'company_supplier_id' => $supplier->id,
        ]);

        return back()->with('success', 'Assigned to '.$supplier->name.'.');
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

    private function draftFromRequest(Request $request): ?array
    {
        return $this->draftFromToken((string) $request->query('draft', ''));
    }

    private function draftFromToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $draft = session('company_expense_invoice_draft');
        if (! is_array($draft) || ! isset($draft['token']) || ! is_string($draft['token'])) {
            return null;
        }
        if (! hash_equals($draft['token'], $token)) {
            return null;
        }

        $user = Auth::user();
        $prefix = TenantStorage::companyReceiptsPath($user->id).'/drafts/';
        if (! isset($draft['path']) || ! is_string($draft['path']) || ! str_starts_with($draft['path'], $prefix)) {
            return null;
        }

        return $draft;
    }

    private function promoteDraft(int $userId, array $draft): ?string
    {
        $path = (string) ($draft['path'] ?? '');
        if ($path === '' || ! TenantStorage::disk()->exists($path)) {
            return null;
        }

        $final = TenantStorage::companyReceiptsPath($userId).'/'.Str::random(40).'.pdf';
        TenantStorage::disk()->move($path, $final);

        return $final;
    }

    private function forgetDraftFile(): void
    {
        $draft = session('company_expense_invoice_draft');
        if (! is_array($draft)) {
            return;
        }
        $path = $draft['path'] ?? null;
        $user = Auth::user();
        $prefix = TenantStorage::companyReceiptsPath($user->id).'/drafts/';
        if (is_string($path) && str_starts_with($path, $prefix) && TenantStorage::disk()->exists($path)) {
            TenantStorage::disk()->delete($path);
        }
        session()->forget('company_expense_invoice_draft');
    }
}
