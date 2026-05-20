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
}