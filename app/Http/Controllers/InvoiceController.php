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
}