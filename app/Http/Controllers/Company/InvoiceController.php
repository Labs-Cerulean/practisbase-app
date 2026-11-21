<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyClient;
use App\Models\CompanyInvoice;
use App\Models\CompanyJournalEntry;
use App\Models\CompanyPayment;
use App\Models\CompanyProfile;
use App\Models\User;
use App\Support\CompanyBooks;
use App\Support\CompanyClientStatement;
use App\Support\CompanyLedger;
use App\Support\ReceiptSplit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        CompanyBooks::ensureProfile($user);

        $documents = CompanyInvoice::with(['client', 'payments', 'childDocuments', 'linkedDocument'])
            ->where('user_id', $user->id)
            ->whereNull('parent_document_id')
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->get();

        $documents = CompanyInvoice::withoutSupersededProformas($documents);
        $documents = $this->filterCompanyDocuments($documents, $request);

        $clients = CompanyClient::where('user_id', $user->id)->orderBy('name')->get();
        $groupByClient = $request->input('group') === 'client';

        return view('company.invoices-index', compact('documents', 'clients', 'groupByClient'));
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

        // Proforma-first: all new billing starts as RFP (€0 fiscal / no output VAT) until paid & converted.
        $request->merge(['type' => 'rfp']);

        $request->validate([
            'type' => 'required|in:rfp',
            'company_client_id' => 'required|exists:company_clients,id,user_id,'.$user->id,
            'issue_date' => 'required|date|before_or_equal:today',
            'supply_date' => 'nullable|date|before_or_equal:today',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'coverage_start' => 'nullable|date',
            'coverage_end' => 'nullable|date',
            'item_desc' => 'required|array|min:1',
            'item_desc.*' => 'required|string',
            'item_qty' => 'required|array|min:1',
            'item_qty.*' => 'required|numeric|min:0.01',
            'item_price' => 'required|array|min:1',
            'item_price.*' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($error = $this->validateIssueDate($profile, $request->issue_date, 'rfp')) {
            return back()->withErrors(['issue_date' => $error])->withInput();
        }

        $coverageStart = $request->filled('coverage_start') ? $request->coverage_start : null;
        $coverageEnd = $request->filled('coverage_end') ? $request->coverage_end : null;
        if (($coverageStart && ! $coverageEnd) || (! $coverageStart && $coverageEnd)) {
            return back()->withErrors([
                'coverage_end' => 'Enter both the coverage start and the coverage end.',
            ])->withInput();
        }
        if ($coverageStart && $coverageEnd && $coverageEnd < $coverageStart) {
            return back()->withErrors([
                'coverage_end' => 'The coverage end cannot be earlier than the coverage start.',
            ])->withInput();
        }

        $supplyDate = $request->filled('supply_date') ? $request->supply_date : $request->issue_date;
        if ($error = $this->validateSupplyDate($profile, $supplyDate, 'rfp')) {
            return back()->withErrors(['supply_date' => $error])->withInput();
        }

        $applyVat = $profile->isArticle10() && $request->boolean('apply_vat');

        CompanyClient::where('user_id', $user->id)
            ->where('id', $request->company_client_id)
            ->firstOrFail();

        [$items, $subtotal] = $this->buildItems($request);
        $vatTotal = $applyVat ? round($subtotal * 0.18, 2) : 0.0;
        $total = round($subtotal + $vatTotal, 2);

        $year = (int) date('Y', strtotime($request->issue_date));
        $number = CompanyBooks::nextDocumentNumber($user->id, 'rfp', $year);

        DB::transaction(function () use ($user, $request, $number, $supplyDate, $coverageStart, $coverageEnd, $subtotal, $vatTotal, $total, $items) {
            CompanyInvoice::create([
                'user_id' => $user->id,
                'company_client_id' => $request->company_client_id,
                'type' => 'rfp',
                'document_number' => $number,
                'issue_date' => $request->issue_date,
                'supply_date' => $supplyDate,
                'coverage_start' => $coverageStart,
                'coverage_end' => $coverageEnd,
                'due_date' => $request->due_date,
                'subtotal' => $subtotal,
                'vat_total' => $vatTotal,
                'total' => $total,
                'amount_paid' => 0,
                'status' => 'unpaid',
                'items' => $items,
                'notes' => $request->notes,
            ]);
        });

        return redirect('/company/invoices')->with(
            'success',
            'Proforma '.$number.' created. No VAT is committed until it is paid and converted to a tax invoice.'
        );
    }

    public function convert(int $document)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $rfp = CompanyInvoice::with(['client', 'payments'])
            ->where('user_id', $user->id)
            ->where('id', $document)
            ->where('type', 'rfp')
            ->firstOrFail();

        try {
            DB::transaction(function () use ($user, $profile, $rfp) {
                $this->convertRfpToTaxInvoice($user, $profile, $rfp, now()->toDateString());
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect('/company/invoices')->with('success', 'RFP converted to tax invoice.');
    }

    public function pay(Request $request, int $document)
    {
        $user = Auth::user();
        $invoice = CompanyInvoice::where('user_id', $user->id)->where('id', $document)->firstOrFail();

        if (in_array($invoice->status, ['converted'], true)) {
            return back()->withErrors(['payment' => 'Converted RFPs cannot receive new payments. Pay the tax invoice instead.']);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|in:bank_transfer,stripe,other',
            'notes' => 'nullable|string|max:500',
        ]);

        $balance = max(0, (float) $invoice->balance());
        if ((float) $validated['amount'] - $balance > 0.009 && $invoice->type === 'invoice') {
            return back()->withErrors(['amount' => 'Payment exceeds open balance of €'.number_format($balance, 2).'.']);
        }

        $autoConverted = false;
        $convertBlocked = null;

        try {
            DB::transaction(function () use ($user, $invoice, $validated, &$autoConverted, &$convertBlocked) {
                $payment = CompanyPayment::create([
                    'user_id' => $user->id,
                    'company_invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'notes' => $validated['notes'] ?? null,
                    'is_transfer' => false,
                ]);

                CompanyLedger::ensureChart($user);
                CompanyLedger::postPayment($payment, $invoice);

                $paid = (float) $invoice->payments()->sum('amount');
                $invoice->update([
                    'amount_paid' => $paid,
                    'status' => $paid >= (float) $invoice->total ? 'paid' : 'partial',
                ]);

                // Proforma-until-paid: once the RFP is fully settled, commit VAT via tax invoice.
                if ($invoice->type === 'rfp'
                    && $invoice->status !== 'converted'
                    && $paid + 0.009 >= (float) $invoice->total
                ) {
                    $profile = CompanyBooks::ensureProfile($user);
                    $invoice->load('client');
                    try {
                        $this->convertRfpToTaxInvoice($user, $profile, $invoice, $validated['payment_date']);
                        $autoConverted = true;
                    } catch (ValidationException $e) {
                        $convertBlocked = $e->errors();
                    }
                }
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        if ($autoConverted) {
            return back()->with('success', 'Payment recorded. Proforma auto-converted to a tax invoice — output VAT is now on the books.');
        }

        if ($convertBlocked) {
            $first = collect($convertBlocked)->flatten()->first();

            return back()
                ->with('success', 'Payment recorded on the proforma (cash as client advance).')
                ->with('warning', $first
                    ? 'Could not auto-convert to a tax invoice yet: '.$first
                    : 'Could not auto-convert to a tax invoice yet. Fix client/company VAT details, then convert manually.');
        }

        return back()->with('success', 'Payment recorded and posted to the ledger.');
    }

    public function clientReceipt(Request $request, int $client)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $model = CompanyClient::where('user_id', $user->id)->where('id', $client)->firstOrFail();

        $validated = $request->validate([
            'payment_date' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:bank_transfer,stripe,other',
            'reference' => 'nullable|string|max:120',
        ]);

        $paymentDate = $validated['payment_date'];
        $amount = round((float) $validated['amount'], 2);
        $note = 'Client receipt';
        if (filled($validated['reference'] ?? null)) {
            $note .= ' · '.$validated['reference'];
        }

        try {
            CompanyLedger::assertDateOpen($user->id, $paymentDate);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $applied = [];

        try {
            DB::transaction(function () use ($user, $profile, $model, $paymentDate, $amount, $note, $validated, &$applied) {
                $documents = CompanyInvoice::query()
                    ->where('user_id', $user->id)
                    ->where('company_client_id', $model->id)
                    ->orderBy('issue_date')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $ids = $documents->pluck('id');
                if ($ids->isNotEmpty()) {
                    CompanyPayment::where('user_id', $user->id)
                        ->whereIn('company_invoice_id', $ids)
                        ->lockForUpdate()
                        ->get();
                }

                $documents->load([
                    'payments' => fn ($q) => $q->where('user_id', $user->id)->orderBy('payment_date')->orderBy('id'),
                    'childDocuments' => fn ($q) => $q->where('user_id', $user->id),
                ]);

                $lines = CompanyClientStatement::openStatement($documents)['rows']
                    ->map(fn (array $row) => [
                        'id' => (int) $row['id'],
                        'due' => (float) $row['due'],
                    ])
                    ->all();

                $openTotal = ReceiptSplit::openTotal($lines);
                if ($openTotal <= 0.009) {
                    throw ValidationException::withMessages([
                        'amount' => 'This client has nothing open to receive.',
                    ]);
                }
                if ($amount - $openTotal > 0.009) {
                    throw ValidationException::withMessages([
                        'amount' => 'This receipt is larger than the €'.number_format($openTotal, 2).' still open for this client.',
                    ]);
                }

                $byId = $documents->keyBy('id');
                foreach (ReceiptSplit::apply($lines, $amount) as $slice) {
                    $document = $byId->get($slice['id']);
                    if (! $document
                        || (int) $document->user_id !== (int) $user->id
                        || (int) $document->company_client_id !== (int) $model->id
                    ) {
                        throw ValidationException::withMessages([
                            'amount' => 'That document is not on this client.',
                        ]);
                    }

                    $payment = CompanyPayment::create([
                        'user_id' => $user->id,
                        'company_invoice_id' => $document->id,
                        'amount' => $slice['amount'],
                        'payment_date' => $paymentDate,
                        'payment_method' => $validated['payment_method'],
                        'notes' => $note,
                        'is_transfer' => false,
                    ]);

                    CompanyLedger::postPayment($payment, $document);

                    $paid = (float) CompanyPayment::where('user_id', $user->id)
                        ->where('company_invoice_id', $document->id)
                        ->sum('amount');
                    $document->update([
                        'amount_paid' => $paid,
                        'status' => $paid + 0.009 >= (float) $document->total ? 'paid' : 'partial',
                    ]);

                    $detail = 'Applied to '.$document->document_number.'.';
                    $convertedNumber = null;
                    if ($document->type === 'rfp'
                        && $slice['settles']
                        && $document->status !== 'converted'
                        && $paid + 0.009 >= (float) $document->total
                    ) {
                        $document->load('client');
                        $invoice = $this->convertRfpToTaxInvoice($user, $profile, $document, $paymentDate);
                        $convertedNumber = $invoice->document_number;
                        $detail = 'Settled '.$document->document_number.' and converted it to '.$invoice->document_number.'.';
                    }

                    $applied[] = [
                        'label' => $document->document_number,
                        'amount' => $slice['amount'],
                        'detail' => $detail,
                        'converted' => $convertedNumber,
                    ];
                }
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $converted = collect($applied)->pluck('converted')->filter()->values();
        $message = 'Receipt of €'.number_format($amount, 2).' recorded.';
        if ($converted->isNotEmpty()) {
            $message .= ' Converted '.$converted->implode(', ').'.';
        }

        return redirect('/company/clients/'.$model->id)
            ->with('success', $message)
            ->with('receipt', [
                'amount' => $amount,
                'date' => $paymentDate,
                'reference' => $validated['reference'] ?? null,
                'lines' => $applied,
            ]);
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
        $invoice->loadMissing('linkedDocument');
        $coverage = $invoice->resolvedCoverage();

        $credit = CompanyInvoice::create([
            'user_id' => $user->id,
            'company_client_id' => $invoice->company_client_id,
            'parent_document_id' => $invoice->id,
            'type' => 'credit_note',
            'document_number' => $number,
            'issue_date' => $issueDate,
            'supply_date' => $supplyDate,
            'coverage_start' => $coverage[0] ?? null,
            'coverage_end' => $coverage[1] ?? null,
            'due_date' => $issueDate,
            'subtotal' => $invoice->subtotal,
            'vat_total' => $invoice->vat_total,
            'total' => $invoice->total,
            'amount_paid' => 0,
            'status' => 'issued',
            'items' => $invoice->items,
            'notes' => 'Credit note amending tax invoice '.$invoice->document_number.'.',
        ]);

        CompanyLedger::ensureChart($user);
        CompanyLedger::postCreditNote($credit);

        return back()->with('success', 'Credit note '.$number.' issued and posted.');
    }

    public function pdf(int $document)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $doc = CompanyInvoice::with(['client', 'parentDocument.linkedDocument', 'linkedDocument'])
            ->where('user_id', $user->id)
            ->where('id', $document)
            ->firstOrFail();

        $pdf = Pdf::loadView('company.pdf.document', [
            'document' => $doc,
            'profile' => $profile,
            'creditedInvoice' => $doc->type === 'credit_note' ? $doc->parentDocument : null,
        ]);

        return $pdf->download($doc->pdfDownloadName());
    }

    public function destroy(int $document)
    {
        $user = Auth::user();
        $rfp = CompanyInvoice::where('user_id', $user->id)
            ->where('id', $document)
            ->firstOrFail();

        if ($rfp->type !== 'rfp') {
            return back()->withErrors([
                'document' => 'Tax invoices stay on the books. Issue a credit note instead of deleting.',
            ]);
        }

        if ($rfp->status === 'converted' || CompanyInvoice::where('user_id', $user->id)->where('linked_document_id', $rfp->id)->where('type', 'invoice')->exists()) {
            return back()->withErrors([
                'document' => 'This proforma was already converted to a tax invoice, so it cannot be deleted.',
            ]);
        }

        if (! $rfp->canDelete()) {
            return back()->withErrors([
                'document' => 'This proforma has a payment recorded. Only unpaid proformas can be deleted.',
            ]);
        }

        try {
            CompanyLedger::assertDateOpen($user->id, $rfp->issue_date->format('Y-m-d'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $hasLedger = CompanyJournalEntry::where('user_id', $user->id)
            ->where('source_id', $rfp->id)
            ->whereIn('source_type', ['company_invoice', 'company_invoice_convert'])
            ->exists();

        if ($hasLedger) {
            return back()->withErrors([
                'document' => 'This proforma has ledger entries and cannot be deleted.',
            ]);
        }

        $number = $rfp->document_number;
        $rfp->delete();

        return back()->with('success', 'Proforma '.$number.' deleted. It had no tax effect.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CompanyInvoice>  $documents
     * @return \Illuminate\Support\Collection<int, CompanyInvoice>
     */
    private function filterCompanyDocuments($documents, Request $request)
    {
        if ($request->filled('client_id')) {
            $clientId = (int) $request->input('client_id');
            $documents = $documents->filter(fn (CompanyInvoice $doc) => (int) $doc->company_client_id === $clientId)->values();
        }

        $type = (string) $request->input('type', '');
        if (in_array($type, ['invoice', 'rfp'], true)) {
            $documents = $documents->filter(fn (CompanyInvoice $doc) => $doc->type === $type)->values();
        }

        $needle = mb_strtolower(trim((string) $request->input('q', '')));
        if ($needle !== '') {
            $documents = $documents->filter(function (CompanyInvoice $doc) use ($needle) {
                $linked = $doc->linkedDocument->document_number ?? '';
                $hay = mb_strtolower(implode(' ', array_filter([
                    $doc->document_number,
                    (string) $doc->notes,
                    $doc->client->name ?? '',
                    $linked,
                    $doc->coverageLabel(),
                ])));

                return str_contains($hay, $needle);
            })->values();
        }

        $status = (string) $request->input('status', '');
        if ($status === 'open') {
            $documents = $documents->filter(fn (CompanyInvoice $doc) => $doc->balance() > 0.009)->values();
        } elseif ($status === 'settled') {
            $documents = $documents->filter(fn (CompanyInvoice $doc) => $doc->balance() <= 0.009)->values();
        }

        $sort = (string) $request->input('sort', 'date_desc');
        if (! in_array($sort, ['date_desc', 'date_asc', 'value_desc', 'value_asc', 'client_asc'], true)) {
            $sort = 'date_desc';
        }
        $groupByClient = $request->input('group') === 'client';

        return $documents->sortBy(function (CompanyInvoice $doc) use ($sort, $groupByClient) {
            $stamp = $doc->issue_date ? $doc->issue_date->timestamp : 0;
            $secondary = match ($sort) {
                'date_asc' => sprintf('%010d', $stamp),
                'value_asc' => sprintf('%012.2f', (float) $doc->total),
                'value_desc' => sprintf('%012.2f', 100000000 - (float) $doc->total),
                'client_asc' => sprintf('%010d', 9999999999 - $stamp),
                default => sprintf('%010d', 9999999999 - $stamp),
            };
            $name = ($groupByClient || $sort === 'client_asc')
                ? mb_strtolower((string) ($doc->client->name ?? ''))
                : '';

            return $name.'|'.$secondary.'|'.sprintf('%010d', (int) $doc->id);
        })->values();
    }

    /**
     * Convert an RFP into a tax invoice (output VAT posts here). Caller may wrap in a transaction.
     *
     * @throws ValidationException
     */
    private function convertRfpToTaxInvoice(User $user, CompanyProfile $profile, CompanyInvoice $rfp, string $issueDate): CompanyInvoice
    {
        $locked = CompanyInvoice::where('user_id', $user->id)
            ->where('id', $rfp->id)
            ->where('type', 'rfp')
            ->lockForUpdate()
            ->firstOrFail();

        if ($locked->status === 'converted' || CompanyInvoice::where('user_id', $user->id)->where('linked_document_id', $locked->id)->where('type', 'invoice')->exists()) {
            throw ValidationException::withMessages([
                'document' => 'This RFP has already been converted.',
            ]);
        }

        if (! $profile->hasVatNumber() && $profile->isArticle10()) {
            throw ValidationException::withMessages([
                'vat_number' => 'Add the company VAT number before converting an RFP to a tax invoice.',
            ]);
        }

        $locked->loadMissing('client');
        if ($clientError = $this->validateClientForTaxInvoice($locked->client)) {
            throw ValidationException::withMessages([
                'company_client_id' => $clientError,
            ]);
        }

        if ($error = $this->validateIssueDate($profile, $issueDate, 'invoice')) {
            throw ValidationException::withMessages(['issue_date' => $error]);
        }

        $supplyDate = optional($locked->supply_date)->format('Y-m-d') ?: $issueDate;
        if ($error = $this->validateSupplyDate($profile, $supplyDate, 'invoice')) {
            throw ValidationException::withMessages(['supply_date' => $error]);
        }

        $coverage = $locked->resolvedCoverage();
        $coverageStart = $coverage[0] ?? null;
        $coverageEnd = $coverage[1] ?? null;

        $year = (int) date('Y', strtotime($issueDate));
        $number = CompanyBooks::nextDocumentNumber($user->id, 'invoice', $year);

        $applyVat = $profile->isArticle10();
        $vatTotal = $applyVat ? round((float) $locked->subtotal * 0.18, 2) : 0.0;
        $total = round((float) $locked->subtotal + $vatTotal, 2);

        CompanyLedger::ensureChart($user);

        $invoice = CompanyInvoice::create([
            'user_id' => $user->id,
            'company_client_id' => $locked->company_client_id,
            'parent_document_id' => null,
            'linked_document_id' => $locked->id,
            'company_recurring_invoice_id' => $locked->company_recurring_invoice_id,
            'type' => 'invoice',
            'document_number' => $number,
            'issue_date' => $issueDate,
            'supply_date' => $supplyDate,
            'coverage_start' => $coverageStart,
            'coverage_end' => $coverageEnd,
            'due_date' => $locked->due_date,
            'subtotal' => $locked->subtotal,
            'vat_total' => $vatTotal,
            'total' => $total,
            'amount_paid' => 0,
            'status' => 'unpaid',
            'items' => $locked->items,
            'notes' => $locked->notes,
        ]);

        CompanyLedger::postInvoiceIssued($invoice);

        $cashPayments = CompanyPayment::where('user_id', $user->id)
            ->where('company_invoice_id', $locked->id)
            ->where('is_transfer', false)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $applied = 0.0;
        foreach ($cashPayments as $payment) {
            if ($applied >= $total) {
                break;
            }
            $room = round($total - $applied, 2);
            $take = min((float) $payment->amount, $room);
            if ($take <= 0) {
                continue;
            }

            if (round($take, 2) === round((float) $payment->amount, 2)) {
                $payment->update([
                    'company_invoice_id' => $invoice->id,
                    'notes' => trim(($payment->notes ? $payment->notes.' · ' : '').'Reassigned from '.$locked->document_number),
                ]);
            } else {
                $payment->update(['amount' => round((float) $payment->amount - $take, 2)]);
                CompanyPayment::create([
                    'user_id' => $user->id,
                    'company_invoice_id' => $invoice->id,
                    'amount' => $take,
                    'payment_date' => $payment->payment_date,
                    'payment_method' => $payment->payment_method,
                    'notes' => 'Split / reassigned from '.$locked->document_number,
                    'is_transfer' => false,
                ]);
            }

            CompanyLedger::postAdvanceToReceivable(
                $invoice,
                $take,
                optional($payment->payment_date)->format('Y-m-d') ?: $issueDate,
                'company_invoice:'.$invoice->id.':advance:'.$payment->id
            );
            $applied = round($applied + $take, 2);
        }

        $invoice->update([
            'amount_paid' => $applied,
            'status' => $applied >= $total ? 'paid' : ($applied > 0 ? 'partial' : 'unpaid'),
        ]);

        $rfpRemaining = (float) CompanyPayment::where('company_invoice_id', $locked->id)->sum('amount');
        $locked->update([
            'amount_paid' => $rfpRemaining,
            'status' => 'converted',
        ]);

        return $invoice->fresh();
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
