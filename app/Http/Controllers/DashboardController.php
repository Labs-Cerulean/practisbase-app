<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userId = $user->id;
        $year = (int) date('Y');

        $clientCount = Client::where('user_id', $userId)->count();
        $archivedCount = Client::onlyTrashed()->where('user_id', $userId)->count();

        // --- Current calendar year (official invoices only for fiscal-facing KPIs) ---
        $ytdInvoices = Invoice::where('user_id', $userId)
            ->where('type', 'invoice')
            ->whereYear('issue_date', $year)
            ->sum('total');
        $ytdCredits = Invoice::where('user_id', $userId)
            ->where('type', 'credit_note')
            ->whereYear('issue_date', $year)
            ->sum('total');
        $ytdNetInvoiced = $ytdInvoices - $ytdCredits;

        $ytdInvoiceCash = Payment::where('user_id', $userId)
            ->whereYear('payment_date', $year)
            ->whereHas('invoice', fn ($q) => $q->where('type', 'invoice'))
            ->sum('amount');
        $ytdRfpCash = Payment::where('user_id', $userId)
            ->whereYear('payment_date', $year)
            ->whereHas('invoice', fn ($q) => $q->where('type', 'rfp'))
            ->sum('amount');

        $ytdOfficialDues = max(0, $ytdNetInvoiced - max(0, $ytdInvoiceCash));

        // --- Open / overdue official invoices (all-time open books) ---
        $openInvoices = Invoice::where('user_id', $userId)
            ->where('type', 'invoice')
            ->with([
                'client',
                'childDocuments' => fn ($q) => $q->where('type', 'credit_note'),
            ])
            ->orderBy('due_date')
            ->get()
            ->map(function (Invoice $invoice) {
                $credits = $invoice->childDocuments->sum('total');
                $balance = max(0, ($invoice->total - $credits) - (float) $invoice->amount_paid);
                $invoice->open_balance = $balance;
                $invoice->is_overdue = $balance > 0.009
                    && $invoice->due_date
                    && $invoice->due_date->lt(now()->startOfDay());

                return $invoice;
            })
            ->filter(fn (Invoice $invoice) => $invoice->open_balance > 0.009)
            ->values();

        $unpaidCount = $openInvoices->count();
        $overdueCount = $openInvoices->where('is_overdue', true)->count();
        $overdueTotal = $openInvoices->where('is_overdue', true)->sum('open_balance');
        $unpaidTotal = $openInvoices->sum('open_balance');

        $recentOpen = $openInvoices->take(5);

        // --- Lifetime snapshot (secondary; not tax liability) ---
        $topLevelTotal = Invoice::where('user_id', $userId)->whereNull('parent_document_id')->sum('total');
        $lifetimeCredits = Invoice::where('user_id', $userId)->where('type', 'credit_note')->sum('total');
        $totalPipeline = $topLevelTotal - $lifetimeCredits;
        $lifetimeInvoices = Invoice::where('user_id', $userId)->where('type', 'invoice')->sum('total');
        $netInvoiced = $lifetimeInvoices - $lifetimeCredits;
        $unbilledPipeline = max(0, $totalPipeline - $netInvoiced);

        return view('dashboard', compact(
            'user',
            'year',
            'clientCount',
            'archivedCount',
            'ytdNetInvoiced',
            'ytdInvoiceCash',
            'ytdRfpCash',
            'ytdOfficialDues',
            'unpaidCount',
            'overdueCount',
            'overdueTotal',
            'unpaidTotal',
            'recentOpen',
            'totalPipeline',
            'netInvoiced',
            'unbilledPipeline'
        ));
    }
}
