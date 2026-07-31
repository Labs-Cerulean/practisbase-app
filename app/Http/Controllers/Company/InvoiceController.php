<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyClient;
use App\Models\CompanyInvoice;
use App\Models\CompanyPayment;
use App\Models\CompanyProfile;
use App\Support\CompanyBooks;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        CompanyBooks::ensureProfile($user);

        $documents = CompanyInvoice::with(['client', 'payments', 'childDocuments'])
            ->where('user_id', $user->id)
            ->whereNull('parent_document_id')
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->get();

        return view('company.invoices-index', compact('documents'));
    }

    public function create()
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $clients = CompanyClient::where('user_id', $user->id)->orderBy('name')->get();

        if ($clients->isEmpty()) {
            return redirect('/company/clients/create')
                ->with('error', 'Add a company client before creating a document.');
        }

        $clientMeta = $clients->mapWithKeys(function (CompanyClient $client) {
            return [
                (string) $client->id => [
                    'has_vat' => filled($client->vat_number),
                    'has_address' => filled($client->billing_address),
                    'name' => $client->name,
                ],
            ];
        });

        return view('company.invoices-create', [
            'clients' => $clients,
            'profile' => $profile,
            'clientMeta' => $clientMeta,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);

        $request->validate([
            'type' => 'required|in:invoice,rfp',
            'company_client_id' => 'required|exists:company_clients,id,user_id,'.$user->id,
            'issue_date' => 'required|date|before_or_equal:today',
            'supply_date' => 'nullable|date|before_or_equal:today',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'item_desc' => 'required|array|min:1',
            'item_desc.*' => 'required|string',
            'item_qty' => 'required|array|min:1',
            'item_qty.*' => 'required|numeric|min:0.01',
            'item_price' => 'required|array|min:1',
            'item_price.*' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($error = $this->validateIssueDate($profile, $request->issue_date, $request->type)) {
            return back()->withErrors(['issue_date' => $error])->withInput();
        }

        $supplyDate = $request->filled('supply_date') ? $request->supply_date : $request->issue_date;
        if ($error = $this->validateSupplyDate($profile, $supplyDate, $request->type)) {
            return back()->withErrors(['supply_date' => $error])->withInput();
        }

        $applyVat = $profile->isArticle10() && $request->boolean('apply_vat');
        if ($profile->isArticle10() && ! $profile->hasVatNumber() && ($request->type === 'invoice' || $applyVat)) {
            return back()
                ->withErrors([
                    'vat_number' => 'Add the company VAT number in Company profile before issuing an Article 10 invoice or charging 18% VAT. RFPs can wait.',
                ])
                ->withInput();
        }

        $client = CompanyClient::where('user_id', $user->id)
            ->where('id', $request->company_client_id)
            ->firstOrFail();

        if ($request->type === 'invoice') {
            if ($clientError = $this->validateClientForTaxInvoice($client)) {
                return back()->withErrors(['company_client_id' => $clientError])->withInput();
            }
        }

        [$items, $subtotal] = $this->buildItems($request);
        $vatTotal = $applyVat ? round($subtotal * 0.18, 2) : 0.0;
        $total = round($subtotal + $vatTotal, 2);

        $year = (int) date('Y', strtotime($request->issue_date));
        $number = CompanyBooks::nextDocumentNumber($user->id, $request->type, $year);

        CompanyInvoice::create([
            'user_id' => $user->id,
            'company_client_id' => $request->company_client_id,
            'type' => $request->type,
            'document_number' => $number,
            'issue_date' => $request->issue_date,
            'supply_date' => $supplyDate,
            'due_date' => $request->due_date,
            'subtotal' => $subtotal,
            'vat_total' => $vatTotal,
            'total' => $total,
            'amount_paid' => 0,
            'status' => 'unpaid',
            'items' => $items,
            'notes' => $request->notes,
        ]);

        return redirect('/company/invoices')->with('success', 'Document '.$number.' created.');
    }

    public function convert(int $document)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $rfp = CompanyInvoice::with('client')
            ->where('user_id', $user->id)
            ->where('id', $document)
            ->where('type', 'rfp')
            ->firstOrFail();

        if (! $profile->hasVatNumber() && $profile->isArticle10()) {
            return back()->withErrors([
                'vat_number' => 'Add the company VAT number before converting an RFP to a tax invoice.',
            ]);
        }

        if ($clientError = $this->validateClientForTaxInvoice($rfp->client)) {
            return back()->withErrors(['company_client_id' => $clientError]);
        }

        $issueDate = now()->toDateString();
        if ($error = $this->validateIssueDate($profile, $issueDate, 'invoice')) {
            return back()->withErrors(['issue_date' => $error]);
        }

        $supplyDate = optional($rfp->supply_date)->format('Y-m-d') ?: $issueDate;
        if ($error = $this->validateSupplyDate($profile, $supplyDate, 'invoice')) {
            return back()->withErrors(['supply_date' => $error]);
        }

        $year = (int) date('Y');
        $number = CompanyBooks::nextDocumentNumber($user->id, 'invoice', $year);

        $applyVat = $profile->isArticle10();
        $vatTotal = $applyVat ? round((float) $rfp->subtotal * 0.18, 2) : 0.0;
        $total = round((float) $rfp->subtotal + $vatTotal, 2);

        DB::transaction(function () use ($user, $rfp, $number, $issueDate, $supplyDate, $vatTotal, $total) {
            $invoice = CompanyInvoice::create([
                'user_id' => $user->id,
                'company_client_id' => $rfp->company_client_id,
                'parent_document_id' => null,
                'linked_document_id' => $rfp->id,
                'type' => 'invoice',
                'document_number' => $number,
                'issue_date' => $issueDate,
                'supply_date' => $supplyDate,
                'due_date' => $rfp->due_date,
                'subtotal' => $rfp->subtotal,
                'vat_total' => $vatTotal,
                'total' => $total,
                'amount_paid' => 0,
                'status' => 'unpaid',
                'items' => $rfp->items,
                'notes' => $rfp->notes,
            ]);

            $rfpPaid = (float) $rfp->amount_paid;
            if ($rfpPaid > 0) {
                $transfer = min($rfpPaid, $total);
                CompanyPayment::create([
                    'user_id' => $user->id,
                    'company_invoice_id' => $invoice->id,
                    'amount' => $transfer,
                    'payment_date' => now()->toDateString(),
                    'payment_method' => 'transfer_from_rfp',
                    'notes' => 'Transferred from '.$rfp->document_number,
                ]);
                $invoice->update([
                    'amount_paid' => $transfer,
                    'status' => $transfer >= $total ? 'paid' : 'partial',
                ]);
                $rfp->update([
                    'amount_paid' => max(0, $rfpPaid - $transfer),
                    'status' => 'converted',
                ]);
            } else {
                $rfp->update(['status' => 'converted']);
            }
        });

        return redirect('/company/invoices')->with('success', 'RFP converted to tax invoice.');
    }

    public function pay(Request $request, int $document)
    {
        $user = Auth::user();
        $invoice = CompanyInvoice::where('user_id', $user->id)->where('id', $document)->firstOrFail();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|in:bank_transfer,stripe,other',
            'notes' => 'nullable|string|max:500',
        ]);

        CompanyPayment::create([
            'user_id' => $user->id,
            'company_invoice_id' => $invoice->id,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $paid = (float) $invoice->payments()->sum('amount');
        $invoice->update([
            'amount_paid' => $paid,
            'status' => $paid >= (float) $invoice->total ? 'paid' : 'partial',
        ]);

        return back()->with('success', 'Payment recorded.');
    }

    public function credit(int $document)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $invoice = CompanyInvoice::where('user_id', $user->id)
            ->where('id', $document)
            ->where('type', 'invoice')
            ->firstOrFail();

        $issueDate = now()->toDateString();
        if ($error = $this->validateIssueDate($profile, $issueDate, 'credit_note')) {
            return back()->withErrors(['issue_date' => $error]);
        }

        $number = CompanyBooks::nextDocumentNumber($user->id, 'credit_note', (int) date('Y'));
        $supplyDate = optional($invoice->supply_date)->format('Y-m-d') ?: $issueDate;

        CompanyInvoice::create([
            'user_id' => $user->id,
            'company_client_id' => $invoice->company_client_id,
            'parent_document_id' => $invoice->id,
            'type' => 'credit_note',
            'document_number' => $number,
            'issue_date' => $issueDate,
            'supply_date' => $supplyDate,
            'due_date' => $issueDate,
            'subtotal' => $invoice->subtotal,
            'vat_total' => $invoice->vat_total,
            'total' => $invoice->total,
            'amount_paid' => 0,
            'status' => 'issued',
            'items' => $invoice->items,
            'notes' => 'Credit note amending tax invoice '.$invoice->document_number.'.',
        ]);

        return back()->with('success', 'Credit note '.$number.' issued.');
    }

    public function pdf(int $document)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $doc = CompanyInvoice::with(['client', 'parentDocument'])
            ->where('user_id', $user->id)
            ->where('id', $document)
            ->firstOrFail();

        $pdf = Pdf::loadView('company.pdf.document', [
            'document' => $doc,
            'profile' => $profile,
            'creditedInvoice' => $doc->type === 'credit_note' ? $doc->parentDocument : null,
        ]);

        return $pdf->download($doc->document_number.'.pdf');
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: float}
     */
    private function buildItems(Request $request): array
    {
        $items = [];
        $subtotal = 0.0;

        for ($i = 0; $i < count($request->item_desc); $i++) {
            $qty = (float) $request->item_qty[$i];
            $price = (float) $request->item_price[$i];
            $rowTotal = round($qty * $price, 2);
            $subtotal += $rowTotal;
            $items[] = [
                'description' => $request->item_desc[$i],
                'quantity' => $qty,
                'unit_price' => $price,
                'row_total' => $rowTotal,
            ];
        }

        return [$items, round($subtotal, 2)];
    }

    private function validateClientForTaxInvoice(?CompanyClient $client): ?string
    {
        if (! $client) {
            return 'Select a company client.';
        }

        if (! filled($client->billing_address)) {
            return 'Add a billing address on the client record before issuing a tax invoice (required for VAT).';
        }

        if (! filled($client->vat_number)) {
            return 'Add the client VAT number before issuing a tax invoice to a VAT-registered B2B customer.';
        }

        return null;
    }

    private function validateIssueDate(CompanyProfile $profile, string $issueDate, string $type): ?string
    {
        $date = strtotime($issueDate);
        $end = $profile->first_period_end->getTimestamp();
        if ($date > $end) {
            return 'Issue date is after the first financial period end ('.$profile->first_period_end->format('Y-m-d').').';
        }

        if (in_array($type, ['invoice', 'credit_note'], true)
            && $date < $profile->first_period_start->getTimestamp()) {
            return 'Tax invoices cannot be dated before incorporation ('.$profile->first_period_start->format('Y-m-d').').';
        }

        return null;
    }

    private function validateSupplyDate(CompanyProfile $profile, string $supplyDate, string $type): ?string
    {
        $date = strtotime($supplyDate);
        $end = $profile->first_period_end->getTimestamp();
        if ($date > $end) {
            return 'Supply date is after the first financial period end ('.$profile->first_period_end->format('Y-m-d').').';
        }

        if (in_array($type, ['invoice', 'credit_note'], true)
            && $date < $profile->first_period_start->getTimestamp()) {
            return 'Supply date cannot be before incorporation ('.$profile->first_period_start->format('Y-m-d').').';
        }

        return null;
    }
}
