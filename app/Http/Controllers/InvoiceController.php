<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use App\Support\DocumentNumber;
use App\Support\FiscalYearGuard;

class InvoiceController extends Controller
{
    // 1. Show the Master Financial Ledger (Intelligent AR Dashboard)
    public function index(Request $request)
    {
        $userId = Auth::id();

        // --- 1. INTELLIGENT KPI ENGINE ---
        // Official Revenue (Invoices minus Credit Notes)
        $totalInvoices = Invoice::where('user_id', $userId)->where('type', 'invoice')->sum('total');
        $totalCredits = Invoice::where('user_id', $userId)->where('type', 'credit_note')->sum('total');
        $netInvoiced = $totalInvoices - $totalCredits;

        // Total Pipeline (To avoid double counting, we ONLY sum Level 0 Masters, then deduct credits)
        $topLevelTotal = Invoice::where('user_id', $userId)->whereNull('parent_document_id')->sum('total');
        $totalPipeline = $topLevelTotal - $totalCredits; 

        // Unbilled Pipeline (What is locked in RFPs but not yet invoiced)
        $unbilledPipeline = max(0, $totalPipeline - $netInvoiced);

        // Cash Analysis 
        $totalCollected = Payment::where('user_id', $userId)->sum('amount');
        $rfpCash = Payment::where('user_id', $userId)->whereHas('invoice', fn($q) => $q->where('type', 'rfp'))->sum('amount');
        $invoiceCash = Payment::where('user_id', $userId)->whereHas('invoice', fn($q) => $q->where('type', 'invoice'))->sum('amount');

        // NEW: Dues Analysis (Fixed: max(0, $cash) prevents refunds from creating fake debt)
        $officialDues = max(0, $netInvoiced - max(0, $invoiceCash));
        $unbilledDues = max(0, $unbilledPipeline - max(0, $rfpCash));
        $totalDues = $officialDues + $unbilledDues;

        // --- 2. FILTERING & SORTING ENGINE ---
        $query = Invoice::with(['client', 'payments', 'childDocuments.payments', 'childDocuments.childDocuments'])
            ->where('user_id', $userId)
            ->whereNull('parent_document_id'); // Only fetch Masters

        // Client Filter
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Sorting (Fixed: Now uses created_at to factor in exact time of day)
        $sort = $request->input('sort', 'date_desc');
        switch ($sort) {
            case 'date_asc': $query->orderBy('created_at', 'asc'); break;
            case 'value_desc': $query->orderBy('total', 'desc'); break;
            case 'value_asc': $query->orderBy('total', 'asc'); break;
            default: $query->orderBy('created_at', 'desc'); break; // date_desc
        }

        $invoices = $query->get();

        // Status Filter 
        if ($request->filled('status')) {
            $invoices = $invoices->filter(function($parent) use ($request) {
                $familyPaid = $parent->amount_paid + $parent->childDocuments->sum('amount_paid');
                $familyCredits = $parent->childDocuments->where('type', 'credit_note')->sum('total');
                foreach($parent->childDocuments as $child) {
                    $familyCredits += $child->childDocuments ? $child->childDocuments->where('type', 'credit_note')->sum('total') : 0;
                }
                $balance = ($parent->total - $familyCredits) - $familyPaid;

                if ($request->status === 'open') return round($balance, 2) > 0;
                if ($request->status === 'balanced') return round($balance, 2) == 0;
                if ($request->status === 'overpaid') return round($balance, 2) < 0;
                return true;
            });
        }

        $clients = \App\Models\Client::where('user_id', $userId)->orderBy('name')->get();

        return view('invoices.index', compact(
            'invoices', 'clients', 'totalPipeline', 'netInvoiced', 
            'unbilledPipeline', 'totalCollected', 'rfpCash', 'invoiceCash',
            'totalDues', 'officialDues', 'unbilledDues'
        ));
    }

    // 2. Show the Create Document Form
    public function create()
    {
        $user = Auth::user();
        $clients = $user->clients;

        if ($clients->isEmpty()) {
            return redirect('/clients/create')->with('error', 'You need to add a client before creating a document.');
        }

        return view('invoices.create', compact('clients', 'user'));
    }

    // 3. Process and Save the Document
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'type' => 'required|in:invoice,rfp',
            'client_id' => 'required|exists:clients,id,user_id,' . $user->id,
            'issue_date' => 'required|date|before_or_equal:today',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'item_desc' => 'required|array|min:1',
            'item_desc.*' => 'required|string',
            'item_qty' => 'required|array|min:1',
            'item_qty.*' => 'required|numeric|min:0.01',
            'item_price' => 'required|array|min:1',
            'item_price.*' => 'required|numeric|min:0',
        ]);

        $issueYear = FiscalYearGuard::yearFromDate($request->issue_date);
        if ($lockError = FiscalYearGuard::ensureOpen($user->id, $issueYear)) {
            return back()->withErrors(['fiscal_error' => $lockError])->withInput();
        }

        // Article 10 official invoices / charging VAT require a supplier VAT number on the document.
        $applyingVat = $user->vat_status === 'article_10' && $request->has('apply_vat');
        if ($user->missingVatNumberForArticle10Documents() && ($request->type === 'invoice' || $applyingVat)) {
            return back()
                ->withErrors([
                    'vat_number' => 'Add your VAT number in Settings before issuing an Article 10 invoice or charging 18% VAT. You can skip this for RFPs until you have your MT number.',
                ])
                ->withInput();
        }

        // 1. Build the JSON items array & calculate Subtotal
        $items = [];
        $subtotal = 0;

        for ($i = 0; $i < count($request->item_desc); $i++) {
            $qty = (float) $request->item_qty[$i];
            $price = (float) $request->item_price[$i];
            $rowTotal = $qty * $price;
            
            $subtotal += $rowTotal;

            $items[] = [
                'description' => $request->item_desc[$i],
                'quantity' => $qty,
                'unit_price' => $price,
                'row_total' => $rowTotal
            ];
        }

        // 2. Strict VAT Enforcement Logic
        $vatTotal = 0;
        // Only Article 10 users are legally allowed to apply VAT
        if ($user->vat_status === 'article_10' && $request->has('apply_vat')) {
            $vatTotal = $subtotal * 0.18;
        }
        $total = $subtotal + $vatTotal;

        // 3. €35k Threshold Monitor (Only check if they are Art 11 and issuing a real Invoice)
        if ($user->vat_status === 'article_11' && $request->type === 'invoice') {
            $ytdRevenue = Invoice::where('user_id', $user->id)
                ->where('type', 'invoice')
                ->whereYear('issue_date', date('Y', strtotime($request->issue_date)))
                ->sum('total');

            if (($ytdRevenue + $total) > 35000) {
                // Flash a persistent warning to the session
                session()->flash('revenue_warning', '⚠️ Legal Alert: This invoice pushed your annual revenue over €35,000. You must apply for an Article 10 VAT Registration within 30 days.');
            }
        }

        // 4. Generate the Document Number (per-user, per-type, per-year sequence)
        $documentNumber = DocumentNumber::next(
            $user->id,
            $request->type,
            FiscalYearGuard::yearFromDate($request->issue_date)
        );

        // 5. Save to Database
        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $request->client_id,
            'type' => $request->type,
            'invoice_number' => $documentNumber,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'subtotal' => $subtotal,
            'vat_total' => $vatTotal,
            'total' => $total,
            'status' => 'unpaid',
            'items' => $items,
            'notes' => $request->notes,
        ]);

        return redirect('/ledger')->with('success', ucfirst($request->type) . ' generated successfully!');
    }

    // 4. Convert an RFP to a Tax Invoice
    /*public function convertToInvoice(Invoice $document)
    {
        $user = Auth::user();

        // Security Checks
        if ($document->user_id !== $user->id) abort(403);
        if ($document->type !== 'rfp') abort(400, 'Only RFPs can be converted.');
        if ($document->status === 'cancelled') abort(400, 'Cannot convert a cancelled RFP.');

        // 1. Check the €35k limit for Article 11 users BEFORE converting
        if ($user->vat_status === 'article_11') {
            $ytdRevenue = Invoice::where('user_id', $user->id)
                ->where('type', 'invoice')
                ->whereYear('issue_date', date('Y'))
                ->sum('total');

            if (($ytdRevenue + $document->total) > 35000) {
                session()->flash('revenue_warning', '⚠️ Legal Alert: Converting this RFP pushed your annual revenue over €35,000. You must apply for an Article 10 VAT Registration within 30 days.');
            }
        }

        // 2. Generate the New Invoice Number
        $latestInvoice = Invoice::where('user_id', $user->id)->where('type', 'invoice')->latest('id')->first();
        $nextId = $latestInvoice ? $latestInvoice->id + 1 : 1;
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // 3. Create the New Official Invoice
        $newInvoice = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $document->client_id,
            'type' => 'invoice',
            'linked_document_id' => $document->id, // Link it back to the original RFP
            'invoice_number' => $invoiceNumber,
            'issue_date' => date('Y-m-d'), // Issued today
            'due_date' => $document->due_date,
            'subtotal' => $document->subtotal,
            'vat_total' => $document->vat_total,
            'total' => $document->total,
            'status' => 'unpaid',
            'items' => $document->items,
            'notes' => 'Converted from RFP: ' . $document->invoice_number,
        ]);

        // 4. Mark the old RFP as cancelled/closed
        $document->update(['status' => 'cancelled']);

        return back()->with('success', 'RFP successfully converted to Tax Invoice ' . $invoiceNumber);
    }*/

    // 5. Cancel an Invoice by Issuing a Credit Note
    /*public function issueCreditNote(Invoice $document)
    {
        $user = Auth::user();

        // Security Checks
        if ($document->user_id !== $user->id) abort(403);
        if ($document->type !== 'invoice') abort(400, 'Credit Notes can only be issued against Tax Invoices.');
        if ($document->status === 'cancelled') abort(400, 'This invoice is already cancelled.');

        // 1. Generate the Credit Note Number
        $latestCN = Invoice::where('user_id', $user->id)->where('type', 'credit_note')->latest('id')->first();
        $nextId = $latestCN ? $latestCN->id + 1 : 1;
        $cnNumber = 'CN-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // 2. Create the Credit Note (Values are technically negative in accounting, but we store them as positive and handle math via the type)
        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $document->client_id,
            'type' => 'credit_note',
            'linked_document_id' => $document->id, // Link it to the original Invoice
            'invoice_number' => $cnNumber,
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d'),
            'subtotal' => $document->subtotal,
            'vat_total' => $document->vat_total,
            'total' => $document->total,
            'status' => 'paid', // Credit notes are instantly "settled"
            'items' => $document->items,
            'notes' => 'Issued to cancel Invoice: ' . $document->invoice_number,
        ]);

        // 3. Update the original invoice status
        $document->update(['status' => 'cancelled']);

        return back()->with('success', 'Credit Note ' . $cnNumber . ' successfully issued and linked.');
    }*/

    // 6. Generate and Download PDF Document
    public function downloadPdf(Invoice $document)
    {
        $user = Auth::user();

        // Security Check
        if ($document->user_id !== $user->id) abort(403);

        // Load the client data so we can print their address on the invoice
        $document->load('client');

        // Render the PDF using a dedicated Blade view
        $pdf = Pdf::loadView('invoices.pdf', compact('document', 'user'));

        // Format for standard European A4 Paper
        $pdf->setPaper('a4', 'portrait');

        // Download the file with a clean name (e.g., INV-2026-0001.pdf)
        return $pdf->download($document->invoice_number . '.pdf');
    }

    // 7. Process a Payment (Full or Partial)
    public function processPayment(Request $request, Invoice $document)
    {
        $user = Auth::user();

        // Security Checks
        if ($document->user_id !== $user->id) abort(403);
        if ($document->status === 'paid') abort(400, 'This document is already fully paid.');
        if ($document->status === 'cancelled') abort(400, 'Cannot apply payments to a cancelled document.');

        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date|before_or_equal:today'
        ]);

        if ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($request->payment_date))) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }
        if ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($document->issue_date))) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }

        $paymentAmount = (float) $request->payment_amount;
        
        // --- CALCULATE THE FAMILY BALANCE ---
        $familyPaid = $document->amount_paid;
        $familyCredits = 0;

        if ($document->type === 'rfp') {
            $familyPaid += $document->childDocuments()->sum('amount_paid');
            $familyCredits += $document->childDocuments()->where('type', 'credit_note')->sum('total');
            
            foreach($document->childDocuments as $child) {
                $familyCredits += $child->childDocuments()->where('type', 'credit_note')->sum('total');
            }
        } elseif ($document->type === 'invoice') {
            $familyCredits += $document->childDocuments()->where('type', 'credit_note')->sum('total');
        }
        
        $effectiveValue = $document->total - $familyCredits;
        $familyBalanceDue = $effectiveValue - $familyPaid;

        // Prevent overpayment of the project
        if (round($paymentAmount, 2) > round($familyBalanceDue, 2)) {
            return back()->withErrors(['payment_error' => 'Payment cannot exceed the remaining project balance of €' . number_format($familyBalanceDue, 2)]);
        }

        $newTotalPaid = $document->amount_paid + $paymentAmount;
        $newStatus = ($newTotalPaid >= $document->total) ? 'paid' : 'partially_paid';

        DB::transaction(function () use ($user, $document, $paymentAmount, $newTotalPaid, $newStatus, $request) {
            Payment::create([
                'user_id' => $user->id,
                'invoice_id' => $document->id,
                'amount' => $paymentAmount,
                'payment_date' => $request->payment_date,                
            ]);

            $document->update([
                'amount_paid' => $newTotalPaid,
                'status' => $newStatus
            ]);
        });

        return back()->with('success', 'Payment of €' . number_format($paymentAmount, 2) . ' applied successfully.');
    }

    // 8. Download Payment Receipt (PDF)
    public function downloadReceipt(\App\Models\Payment $payment)
    {
        $user = Auth::user();
        
        // Load the associated invoice and client
        $payment->load('invoice.client');

        // Security Check: Does this payment belong to the authenticated user?
        if ($payment->user_id !== $user->id) abort(403);
        
        // Strict Rule: No receipts for RFPs
        if ($payment->invoice->type !== 'invoice') abort(400, 'Receipts cannot be issued for Requests for Payment.');

        // Generate the PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
            'client' => $payment->invoice->client,
            'user' => $user
        ]);

        // Download format: Receipt_INV-001_2026-05-23.pdf
        $filename = 'Receipt_' . $payment->invoice->invoice_number . '_' . $payment->payment_date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    // 9. Issue a Partial or Full Credit Note
    public function issueCreditNote(Request $request, Invoice $document)
    {
        $user = Auth::user();

        // Security & Legal Checks
        if ($document->user_id !== $user->id) abort(403);
        if ($document->type !== 'invoice') abort(400, 'Credit notes can only be issued against official Tax Invoices.');
        if ($document->status === 'cancelled') abort(400, 'This invoice is already fully cancelled.');

        if ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($document->issue_date))) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }
        if ($lockError = FiscalYearGuard::ensureOpen($user->id, (int) date('Y'))) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }

        $request->validate([
            'credit_amount' => 'required|numeric|min:0.01'
        ]);

        $creditTotal = (float) $request->credit_amount;

        // 1. Calculate Maximum Allowable Credit
        // You cannot issue more credit than the value of the invoice!
        $existingCredits = $document->childDocuments()->where('type', 'credit_note')->sum('total');
        $maxAllowableCredit = $document->total - $existingCredits;

        if ($creditTotal > $maxAllowableCredit) {
            return back()->withErrors(['payment_error' => 'Credit amount cannot exceed the remaining invoice value of €' . number_format($maxAllowableCredit, 2)]);
        }

        // 2. Reverse VAT Proportionally
        // If the parent invoice had VAT, the credit note must extract 18% from the requested total
        $vatRate = $document->vat_total > 0 ? 0.18 : 0;
        
        if ($vatRate > 0) {
            $creditSubtotal = $creditTotal / 1.18;
            $creditVat = $creditTotal - $creditSubtotal;
        } else {
            $creditSubtotal = $creditTotal;
            $creditVat = 0;
        }

        // 3. Generate a Sequential Credit Note Number (e.g., CN-INV-004-1)
        $cnCount = $document->childDocuments()->count() + 1;
        $cnNumber = 'CN-' . $document->invoice_number . '-' . $cnCount;

        // 4. Create the Child Document securely
        DB::transaction(function () use ($user, $document, $cnNumber, $creditSubtotal, $creditVat, $creditTotal, $maxAllowableCredit) {
            
            Invoice::create([
                'user_id' => $user->id,
                'client_id' => $document->client_id,
                'parent_document_id' => $document->id,
                'type' => 'credit_note',
                'invoice_number' => $cnNumber,
                'issue_date' => now(),
                'due_date' => now(),
                'subtotal' => $creditSubtotal,
                'vat_total' => $creditVat,
                'total' => $creditTotal,
                'amount_paid' => 0, 
                'status' => 'paid', 
                // NEW: Provide a summary line item for the credit
                'items' => [
                    [
                        'description' => 'Partial Credit / Reversal against ' . $document->invoice_number,
                        'quantity' => 1,
                        'price' => $creditSubtotal,
                        'amount' => $creditSubtotal
                    ]
                ]
            ]);

            // 5. If they credited the absolute maximum remaining amount, mark the parent as cancelled
            if ($creditTotal == $maxAllowableCredit && $document->amount_paid == 0) {
                $document->update(['status' => 'cancelled']);
            }
        });

        return back()->with('success', 'Credit Note ' . $cnNumber . ' successfully issued for €' . number_format($creditTotal, 2));
    }
    // 10. Convert RFP to (Partial or Full) Tax Invoice
    public function convertToInvoice(Request $request, Invoice $document)
    {
        $user = Auth::user();

        // Security Checks
        if ($document->user_id !== $user->id) abort(403);
        if ($document->type !== 'rfp') abort(400, 'Only RFPs can be converted to Invoices.');

        if ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($document->issue_date))) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }
        if ($lockError = FiscalYearGuard::ensureOpen($user->id, (int) date('Y'))) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }

        $request->validate([
            'conversion_amount' => 'required|numeric|min:0.01'
        ]);

        $conversionAmount = (float) $request->conversion_amount;

        // 1. Calculate Allowable Conversion
        $existingConversions = $document->childDocuments()->where('type', 'invoice')->sum('total');
        $maxAllowable = $document->total - $existingConversions;

        if ($conversionAmount > $maxAllowable) {
            return back()->withErrors(['payment_error' => 'Conversion cannot exceed the remaining RFP value of €' . number_format($maxAllowable, 2)]);
        }

        // 2. Pro-Rata VAT Math
        $vatRate = $document->vat_total > 0 ? 0.18 : 0;
        if ($vatRate > 0) {
            $subtotal = $conversionAmount / 1.18;
            $vat = $conversionAmount - $subtotal;
        } else {
            $subtotal = $conversionAmount;
            $vat = 0;
        }

        // 3. Generate Sequential Invoice Number (per-user per-year)
        $invNumber = DocumentNumber::next($user->id, 'invoice');

        DB::transaction(function () use ($user, $document, $conversionAmount, $subtotal, $vat, $maxAllowable, $invNumber) {
            
            // 4. Create the Child Tax Invoice
            $newInvoice = Invoice::create([
                'user_id' => $user->id,
                'client_id' => $document->client_id,
                'parent_document_id' => $document->id, 
                'type' => 'invoice',
                'invoice_number' => $invNumber,
                'issue_date' => now(),
                'due_date' => now()->addDays(14),
                'subtotal' => $subtotal,
                'vat_total' => $vat,
                'total' => $conversionAmount,
                'amount_paid' => 0,
                'status' => 'unpaid',
                // NEW: Provide a summary line item for the milestone
                'items' => [
                    [
                        'description' => 'Milestone Billing for ' . $document->invoice_number,
                        'quantity' => 1,
                        'price' => $subtotal,
                        'amount' => $subtotal
                    ]
                ]
            ]);

            // 5. Intelligent Payment Transfer 
            // If they already logged a €10k payment on the RFP, move it to this new Invoice!
            $remainingToTransfer = $conversionAmount;
            $totalTransferred = 0;
            
            foreach ($document->payments as $payment) {
                // If the payment fits inside this new invoice, transfer it over
                if ($remainingToTransfer >= $payment->amount) {
                    $payment->update(['invoice_id' => $newInvoice->id]);
                    $remainingToTransfer -= $payment->amount;
                    $totalTransferred += $payment->amount;
                }
            }

            // 6. Update document statuses based on the transferred cash
            if ($totalTransferred > 0) {
                $newInvoice->update([
                    'amount_paid' => $totalTransferred,
                    'status' => ($totalTransferred >= $conversionAmount) ? 'paid' : 'partially_paid'
                ]);
                
                // Reduce parent RFP's amount_paid to reflect that the cash has moved to the official invoice
                $document->update([
                    'amount_paid' => max(0, $document->amount_paid - $totalTransferred)
                ]);
            }

            // 7. Auto-Close the RFP if it has been fully drawn down!
            if ($conversionAmount == $maxAllowable) {
                $document->update(['status' => 'converted']);
            }
        });

        return back()->with('success', 'Tax Invoice ' . $invNumber . ' generated for €' . number_format($conversionAmount, 2) . '. Payments transferred where applicable.');
    }

    // 11. Reverse/Delete a Payment
    public function deletePayment(\App\Models\Payment $payment)
    {
        $user = Auth::user();
        $invoice = $payment->invoice;

        // Security Check
        if ($payment->user_id !== $user->id) abort(403);

        if ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($payment->payment_date))) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }
        if ($invoice && ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($invoice->issue_date)))) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }

        DB::transaction(function () use ($payment, $invoice) {
            // 1. Deduct the amount from the parent invoice
            $newPaid = max(0, $invoice->amount_paid - $payment->amount);
            
            // 2. Re-evaluate the status
            $newStatus = ($newPaid == 0) ? 'unpaid' : 'partially_paid';

            $invoice->update([
                'amount_paid' => $newPaid,
                'status' => $newStatus
            ]);

            // 3. Destroy the payment record
            $payment->delete();
        });

        return back()->with('success', 'Payment reversed successfully. Invoice balances have been recalculated.');
    }

    // 12. Move a Payment from an RFP to a Child Invoice
    public function transferPayment(Request $request, \App\Models\Payment $payment)
    {
        $user = Auth::user();
        if ($payment->user_id !== $user->id) abort(403);

        $request->validate([
            'target_invoice_id' => 'required|exists:invoices,id,user_id,' . $user->id,
        ]);
        
        $targetInvoice = Invoice::where('id', $request->target_invoice_id)->where('user_id', $user->id)->firstOrFail();
        $oldInvoice = $payment->invoice;

        if ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($payment->payment_date))) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }

        DB::transaction(function () use ($payment, $oldInvoice, $targetInvoice) {
            // 1. Deduct from Parent RFP
            $oldPaid = max(0, $oldInvoice->amount_paid - $payment->amount);
            $oldInvoice->update(['amount_paid' => $oldPaid]);

            // 2. Add to Child Invoice
            $newPaid = $targetInvoice->amount_paid + $payment->amount;
            $targetInvoice->update([
                'amount_paid' => $newPaid,
                'status' => ($newPaid >= $targetInvoice->total) ? 'paid' : 'partially_paid'
            ]);

            // 3. Move the physical payment record
            $payment->update(['invoice_id' => $targetInvoice->id]);
        });

        return back()->with('success', 'Payment successfully moved to Invoice ' . $targetInvoice->invoice_number);
    }

    // 13. Process a Refund (For Overpaid Documents)
    public function processRefund(Request $request, Invoice $document)
    {
        $user = Auth::user();
        if ($document->user_id !== $user->id) abort(403);

        $request->validate([
            'refund_amount' => 'required|numeric|min:0.01',
            'refund_date' => 'required|date|before_or_equal:today'
        ]);

        if ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($request->refund_date))) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }
        if ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($document->issue_date))) {
            return back()->withErrors(['fiscal_error' => $lockError]);
        }

        $refundAmount = (float) $request->refund_amount;

        // Calculate the family overpayment
        $familyPaid = $document->amount_paid;
        $familyCredits = 0;

        if ($document->type === 'rfp') {
            $familyPaid += $document->childDocuments()->sum('amount_paid');
            $familyCredits += $document->childDocuments()->where('type', 'credit_note')->sum('total');
            foreach($document->childDocuments as $child) {
                $familyCredits += $child->childDocuments()->where('type', 'credit_note')->sum('total');
            }
        } elseif ($document->type === 'invoice') {
            $familyCredits += $document->childDocuments()->where('type', 'credit_note')->sum('total');
        }

        $effectiveValue = $document->total - $familyCredits;
        $familyBalance = $effectiveValue - $familyPaid; 

        if ($familyBalance >= 0) {
            return back()->withErrors(['payment_error' => 'This project is not overpaid. No refund required.']);
        }

        $maxRefund = abs($familyBalance);

        if (round($refundAmount, 2) > round($maxRefund, 2)) {
            return back()->withErrors(['payment_error' => 'Refund cannot exceed the overpaid amount of €' . number_format($maxRefund, 2)]);
        }

        DB::transaction(function () use ($user, $document, $refundAmount, $request) {
            
            $remainingToRefund = $refundAmount;

            // 1. Try to deduct from the Master Document first
            if ($document->amount_paid > 0) {
                $deduct = min($document->amount_paid, $remainingToRefund);
                $newPaid = $document->amount_paid - $deduct;
                
                // Log the negative payment against THIS specific document
                Payment::create([
                    'user_id' => $user->id,
                    'invoice_id' => $document->id,
                    'amount' => -$deduct, 
                    'payment_date' => $request->refund_date,
                ]);

                $status = $document->status;
                if (in_array($status, ['paid', 'partially_paid'])) {
                     $status = ($newPaid == 0) ? 'unpaid' : (($newPaid >= $document->total) ? 'paid' : 'partially_paid');
                }

                $document->update(['amount_paid' => $newPaid, 'status' => $status]);
                $remainingToRefund -= $deduct;
            }

            // 2. If cash is still owed, reach into the Child Invoices and deduct from them
            if ($remainingToRefund > 0 && $document->type === 'rfp') {
                foreach ($document->childDocuments()->where('type', 'invoice')->get() as $child) {
                    if ($child->amount_paid > 0) {
                        $deduct = min($child->amount_paid, $remainingToRefund);
                        $newPaid = $child->amount_paid - $deduct;
                        
                        // Log the negative payment against THIS CHILD document!
                        Payment::create([
                            'user_id' => $user->id,
                            'invoice_id' => $child->id,
                            'amount' => -$deduct, 
                            'payment_date' => $request->refund_date,
                        ]);

                        $child->update([
                            'amount_paid' => $newPaid,
                            'status' => ($newPaid == 0) ? 'unpaid' : (($newPaid >= $child->total) ? 'paid' : 'partially_paid')
                        ]);
                        
                        $remainingToRefund -= $deduct;
                        if ($remainingToRefund <= 0) break;
                    }
                }
            }
        });

        return back()->with('success', 'Refund of €' . number_format($refundAmount, 2) . ' logged successfully. The account is now balanced.');
    }
}