<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    // 1. Show the Master Financial Ledger
    public function index()
    {
        $userId = Auth::id();

        // Get all documents (Invoices, RFPs, Credit Notes)
        $invoices = Invoice::with(['client', 'payments', 'childDocuments'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate Ledger KPIs dynamically (ONLY count official Invoices, ignore RFPs)
        $totalPaid = Invoice::where('user_id', $userId)->where('type', 'invoice')->where('status', 'paid')->sum('total');
        $totalUnpaid = Invoice::where('user_id', $userId)->where('type', 'invoice')->where('status', 'unpaid')->sum('total');

        return view('invoices.index', compact('invoices', 'totalPaid', 'totalUnpaid'));
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
            'client_id' => 'required|exists:clients,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'item_desc' => 'required|array|min:1',
            'item_desc.*' => 'required|string',
            'item_qty' => 'required|array|min:1',
            'item_qty.*' => 'required|numeric|min:0.01',
            'item_price' => 'required|array|min:1',
            'item_price.*' => 'required|numeric|min:0',
        ]);

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

        // 4. Generate the Document Number
        $prefix = $request->type === 'rfp' ? 'RFP-' : 'INV-';
        $latestDoc = Invoice::where('user_id', $user->id)->where('type', $request->type)->latest('id')->first();
        $nextId = $latestDoc ? $latestDoc->id + 1 : 1;
        $documentNumber = $prefix . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

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

        // Validate the incoming payment amount
        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01'
        ]);

        $paymentAmount = (float) $request->payment_amount;
        $newTotalPaid = $document->amount_paid + $paymentAmount;
        $balanceDue = $document->total - $newTotalPaid;

        // Prevent overpayment 
        if ($newTotalPaid > $document->total) {
            return back()->withErrors(['payment_error' => 'Payment cannot exceed the outstanding balance of €' . number_format($document->total - $document->amount_paid, 2)]);
        }

        // Determine new status
        $newStatus = ($newTotalPaid >= $document->total) ? 'paid' : 'partially_paid';

        // Execute a secure Database Transaction
        // This ensures that if creating the Payment fails, the Invoice isn't updated (preventing bad math)
        DB::transaction(function () use ($user, $document, $paymentAmount, $newTotalPaid, $newStatus) {
            
            // 1. Log the individual payment record
            Payment::create([
                'user_id' => $user->id,
                'invoice_id' => $document->id,
                'amount' => $paymentAmount,
                'payment_date' => now(), // Logs the exact date and time the button was clicked
            ]);

            // 2. Update the parent document
            $document->update([
                'amount_paid' => $newTotalPaid,
                'status' => $newStatus
            ]);
        });

        // Tailor the success message based on document type
        $docType = $document->type === 'rfp' ? 'RFP' : 'Invoice';
        $message = 'Payment of €' . number_format($paymentAmount, 2) . ' applied to ' . $docType . '.';
        
        if ($balanceDue > 0) {
            $message .= ' Remaining balance: €' . number_format($balanceDue, 2);
        } else {
            $message .= ' Document is now fully paid.';
        }

        return back()->with('success', $message);
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
                'parent_document_id' => $document->id, // Linking it to the parent!
                'type' => 'credit_note',
                'invoice_number' => $cnNumber,
                'issue_date' => now(),
                'due_date' => now(),
                'subtotal' => $creditSubtotal,
                'vat_total' => $creditVat,
                'total' => $creditTotal,
                'amount_paid' => 0, 
                'status' => 'paid', // Credit notes don't require payment, they are inherently resolved
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

        // 3. Generate Sequential Invoice Number (e.g., INV-20260523-0004)
        $invCount = Invoice::where('user_id', $user->id)->where('type', 'invoice')->count() + 1;
        $invNumber = 'INV-' . date('Ymd') . '-' . str_pad($invCount, 4, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $document, $conversionAmount, $subtotal, $vat, $maxAllowable, $invNumber) {
            
            // 4. Create the Child Tax Invoice
            $newInvoice = Invoice::create([
                'user_id' => $user->id,
                'client_id' => $document->client_id,
                'parent_document_id' => $document->id, // Linked to the RFP!
                'type' => 'invoice',
                'invoice_number' => $invNumber,
                'issue_date' => now(),
                'due_date' => now()->addDays(14),
                'subtotal' => $subtotal,
                'vat_total' => $vat,
                'total' => $conversionAmount,
                'amount_paid' => 0,
                'status' => 'unpaid',
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
        });

        return back()->with('success', 'Tax Invoice ' . $invNumber . ' generated for €' . number_format($conversionAmount, 2) . '. Payments transferred where applicable.');
    }
}