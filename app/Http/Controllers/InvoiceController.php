<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    // 1. Show the Master Financial Ledger
    public function index()
    {
        $userId = Auth::id();

        // Get all documents (Invoices, RFPs, Credit Notes)
        $invoices = Invoice::with('client')
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
    public function convertToInvoice(Invoice $document)
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
    }

    // 5. Cancel an Invoice by Issuing a Credit Note
    public function issueCreditNote(Invoice $document)
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
    }

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
}