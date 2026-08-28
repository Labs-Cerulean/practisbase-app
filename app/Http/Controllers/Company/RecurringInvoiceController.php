<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyClient;
use App\Models\CompanyInvoice;
use App\Models\CompanyRecurringInvoice;
use App\Support\CompanyBooks;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $schedules = CompanyRecurringInvoice::with('client')
            ->where('user_id', $user->id)
            ->orderByDesc('is_active')
            ->orderBy('next_issue_on')
            ->get();
        $clients = CompanyClient::where('user_id', $user->id)->orderBy('name')->get();

        return view('company.accounts.recurring', compact('schedules', 'clients'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'company_client_id' => 'required|exists:company_clients,id,user_id,'.$user->id,
            'title' => 'required|string|max:255',
            'day_of_month' => 'required|integer|min:1|max:28',
            'next_issue_on' => 'required|date',
            'due_days' => 'required|integer|min:0|max:90',
            'item_desc' => 'required|array|min:1',
            'item_desc.*' => 'required|string|max:500',
            'item_qty' => 'required|array|min:1',
            'item_qty.*' => 'required|numeric|min:0.01',
            'item_price' => 'required|array|min:1',
            'item_price.*' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $items = [];
        $subtotal = 0.0;
        foreach ($validated['item_desc'] as $i => $desc) {
            $qty = (float) $validated['item_qty'][$i];
            $price = (float) $validated['item_price'][$i];
            $line = round($qty * $price, 2);
            $subtotal += $line;
            $items[] = [
                'description' => $desc,
                'qty' => $qty,
                'unit_price' => $price,
                'line_total' => $line,
            ];
        }

        CompanyRecurringInvoice::create([
            'user_id' => $user->id,
            'company_client_id' => $validated['company_client_id'],
            'title' => $validated['title'],
            'day_of_month' => $validated['day_of_month'],
            'next_issue_on' => $validated['next_issue_on'],
            'due_days' => $validated['due_days'],
            'items' => $items,
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Monthly proforma schedule saved. Subtotal base €'.number_format($subtotal, 2).' (+ VAT estimate). Tax invoice / VAT commits when paid.');
    }

    public function generateDue()
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        $today = now()->toDateString();
        $due = CompanyRecurringInvoice::with('client')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('next_issue_on', '<=', $today)
            ->get();

        $created = 0;
        foreach ($due as $schedule) {
            DB::transaction(function () use ($user, $profile, $schedule, &$created) {
                $issueDate = $schedule->next_issue_on->format('Y-m-d');
                $items = $schedule->items ?? [];
                $subtotal = round(collect($items)->sum(fn ($row) => (float) ($row['line_total'] ?? 0)), 2);
                $vat = $profile->isArticle10() ? round($subtotal * 0.18, 2) : 0.0;
                $total = round($subtotal + $vat, 2);
                // Proforma-first: recurring bills as RFP until paid (no output VAT until convert).
                $number = CompanyBooks::nextDocumentNumber($user->id, 'rfp', (int) $schedule->next_issue_on->format('Y'));

                $rfp = CompanyInvoice::create([
                    'user_id' => $user->id,
                    'company_client_id' => $schedule->company_client_id,
                    'type' => 'rfp',
                    'document_number' => $number,
                    'issue_date' => $issueDate,
                    'supply_date' => $issueDate,
                    'due_date' => Carbon::parse($issueDate)->addDays((int) $schedule->due_days)->toDateString(),
                    'subtotal' => $subtotal,
                    'vat_total' => $vat,
                    'total' => $total,
                    'amount_paid' => 0,
                    'status' => 'unpaid',
                    'items' => $items,
                    'notes' => trim(($schedule->notes ? $schedule->notes."\n" : '').'Recurring proforma: '.$schedule->title),
                ]);

                $next = $schedule->next_issue_on->copy()->addMonthNoOverflow()->day(min((int) $schedule->day_of_month, 28));
                $schedule->update([
                    'last_generated_on' => $issueDate,
                    'last_invoice_id' => $rfp->id,
                    'next_issue_on' => $next->toDateString(),
                ]);
                $created++;
            });
        }

        return back()->with('success', $created === 0
            ? 'No recurring proformas were due today.'
            : $created.' recurring proforma(s) generated (VAT commits when paid and converted).');
    }

    public function toggle(int $schedule)
    {
        $model = CompanyRecurringInvoice::where('user_id', Auth::id())->where('id', $schedule)->firstOrFail();
        $model->update(['is_active' => ! $model->is_active]);

        return back()->with('success', $model->is_active ? 'Schedule activated.' : 'Schedule paused.');
    }
}
