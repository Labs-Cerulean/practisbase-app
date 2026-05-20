<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    // 1. Show the Master Financial Ledger
    public function index()
    {
        $userId = Auth::id();

        // Get all invoices for this professional with their client relationships loaded
        $invoices = Invoice::with('client')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate Ledger KPIs dynamically
        $totalPaid = Invoice::where('user_id', $userId)->where('status', 'paid')->sum('total');
        $totalUnpaid = Invoice::where('user_id', $userId)->where('status', 'unpaid')->sum('total');

        return view('invoices.index', compact('invoices', 'totalPaid', 'totalUnpaid'));
    }

    // 2. Show the Create Invoice Form
    public function create()
    {
        $clients = Auth::user()->clients;

        // If they have no clients, force them to make one first!
        if ($clients->isEmpty()) {
            return redirect('/clients/create')->with('error', 'You need to add a client before creating an invoice.');
        }

        return view('invoices.create', compact('clients'));
    }

    // 3. Process and Save the Invoice
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'item_desc' => 'required|array|min:1',
            'item_desc.*' => 'required|string',
            'item_qty' => 'required|array|min:1',
            'item_qty.*' => 'required|numeric|min:0.01',
            'item_price' => 'required|array|min:1',
            'item_price.*' => 'required|numeric|min:0',
            'apply_vat' => 'nullable|boolean',
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

        // 2. Calculate VAT (Standard Malta 18% if checked)
        $vatTotal = $request->has('apply_vat') ? $subtotal * 0.18 : 0;
        $total = $subtotal + $vatTotal;

        // 3. Generate a clean Invoice Number (e.g., INV-2026-0001)
        $latestInvoice = Invoice::where('user_id', Auth::id())->latest('id')->first();
        $nextId = $latestInvoice ? $latestInvoice->id + 1 : 1;
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // 4. Save to Database
        $invoice = Invoice::create([
            'user_id' => Auth::id(),
            'client_id' => $request->client_id,
            'invoice_number' => $invoiceNumber,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'subtotal' => $subtotal,
            'vat_total' => $vatTotal,
            'total' => $total,
            'status' => 'unpaid',
            'items' => $items,
            'notes' => $request->notes,
        ]);

        return redirect('/ledger')->with('success', 'Invoice created successfully!');
    }
}