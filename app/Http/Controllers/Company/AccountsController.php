<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyBooksLock;
use App\Models\CompanyClient;
use App\Models\CompanyGlAccount;
use App\Models\CompanyJournalEntry;
use App\Models\CompanyJournalLine;
use App\Support\CompanyBooks;
use App\Support\CompanyChartOfAccounts;
use App\Support\CompanyLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $profile = CompanyBooks::ensureProfile($user);
        CompanyLedger::ensureChart($user);

        $asOf = $request->input('as_of', now()->toDateString());
        $from = $request->input('from', $profile->first_period_start->format('Y-m-d'));
        $to = $request->input('to', $asOf);

        return view('company.accounts.index', [
            'profile' => $profile,
            'asOf' => $asOf,
            'from' => $from,
            'to' => $to,
            'trialBalance' => CompanyLedger::trialBalance($user->id, $asOf),
            'profitAndLoss' => CompanyLedger::profitAndLoss($user->id, $from, $to),
            'balanceSheet' => CompanyLedger::balanceSheet($user->id, $asOf, $from),
            'lock' => CompanyBooksLock::where('user_id', $user->id)->first(),
            'journalCount' => CompanyJournalEntry::where('user_id', $user->id)->count(),
            'accountCount' => CompanyGlAccount::where('user_id', $user->id)->count(),
        ]);
    }

    public function chart()
    {
        $user = Auth::user();
        CompanyLedger::ensureChart($user);
        $accounts = CompanyGlAccount::where('user_id', $user->id)->orderBy('account_code')->get();

        return view('company.accounts.chart', compact('accounts'));
    }

    public function journals(Request $request)
    {
        $user = Auth::user();
        CompanyLedger::ensureChart($user);

        $entries = CompanyJournalEntry::with(['lines.account'])
            ->where('user_id', $user->id)
            ->when($request->filled('from'), fn ($q) => $q->where('entry_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('entry_date', '<=', $request->input('to')))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('company.accounts.journals', compact('entries'));
    }

    public function customerStatement(Request $request)
    {
        $user = Auth::user();
        CompanyLedger::ensureChart($user);
        $clients = CompanyClient::where('user_id', $user->id)->orderBy('name')->get();
        $clientId = (int) $request->input('client_id');
        $from = $request->input('from', CompanyBooks::ensureProfile($user)->first_period_start->format('Y-m-d'));
        $to = $request->input('to', now()->toDateString());

        $rows = collect();
        $client = null;
        $running = 0.0;

        if ($clientId > 0) {
            $client = $clients->firstWhere('id', $clientId);
            $ar = CompanyLedger::account($user->id, CompanyChartOfAccounts::AR);
            $lines = CompanyJournalLine::query()
                ->with('entry')
                ->where('user_id', $user->id)
                ->where('gl_account_id', $ar->id)
                ->where('company_client_id', $clientId)
                ->whereHas('entry', function ($q) use ($from, $to) {
                    $q->whereIn('status', ['posted', 'reconciled'])
                        ->where('entry_date', '>=', $from)
                        ->where('entry_date', '<=', $to);
                })
                ->get()
                ->sortBy(fn ($line) => $line->entry->entry_date->format('Y-m-d').'-'.$line->id);

            foreach ($lines as $line) {
                $debit = $line->side === 'debit' ? (float) $line->amount : 0.0;
                $credit = $line->side === 'credit' ? (float) $line->amount : 0.0;
                $running = round($running + $debit - $credit, 2);
                $rows->push([
                    'date' => $line->entry->entry_date,
                    'reference' => $line->entry->description,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $running,
                ]);
            }
        }

        return view('company.accounts.customer-statement', [
            'clients' => $clients,
            'client' => $client,
            'clientId' => $clientId,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'closing' => $running,
        ]);
    }

    public function lock(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'locked_through' => 'required|date|before_or_equal:today',
            'note' => 'nullable|string|max:500',
        ]);

        CompanyBooksLock::updateOrCreate(
            ['user_id' => $user->id],
            [
                'locked_through' => $validated['locked_through'],
                'note' => $validated['note'] ?? null,
            ]
        );

        return back()->with('success', 'Company books locked through '.$validated['locked_through'].'.');
    }

    public function unlock()
    {
        CompanyBooksLock::where('user_id', Auth::id())->delete();

        return back()->with('success', 'Company books lock removed.');
    }
}
