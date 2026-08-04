<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyBankStatementLine;
use App\Models\CompanyJournalLine;
use App\Support\CompanyChartOfAccounts;
use App\Support\CompanyLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        CompanyLedger::ensureChart($user);

        $lines = CompanyBankStatementLine::where('user_id', $user->id)
            ->orderByDesc('statement_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $bank = CompanyLedger::account($user->id, CompanyChartOfAccounts::BANK);
        $unmatchedLedger = CompanyJournalLine::with('entry')
            ->where('user_id', $user->id)
            ->where('gl_account_id', $bank->id)
            ->whereNull('bank_statement_line_id')
            ->whereHas('entry', fn ($q) => $q->whereIn('status', ['posted', 'reconciled']))
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('company.accounts.bank', [
            'lines' => $lines,
            'unmatchedLedger' => $unmatchedLedger,
            'unreconciledCount' => $lines->where('status', 'unreconciled')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'statement_date' => 'required|date|before_or_equal:today',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric',
        ]);

        CompanyBankStatementLine::create([
            'user_id' => $user->id,
            'statement_date' => $validated['statement_date'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'status' => 'unreconciled',
            'import_batch' => 'manual-'.now()->format('Ymd'),
        ]);

        return back()->with('success', 'Bank statement line added.');
    }

    public function match(Request $request, int $line)
    {
        $user = Auth::user();
        $statement = CompanyBankStatementLine::where('user_id', $user->id)->where('id', $line)->firstOrFail();
        $validated = $request->validate([
            'journal_line_id' => 'required|integer',
        ]);

        $journalLine = CompanyJournalLine::where('user_id', $user->id)
            ->where('id', $validated['journal_line_id'])
            ->firstOrFail();

        $signed = $journalLine->side === 'debit'
            ? (float) $journalLine->amount
            : -1 * (float) $journalLine->amount;

        if (abs($signed - (float) $statement->amount) > 0.009) {
            return back()->withErrors([
                'journal_line_id' => 'Amounts do not match (statement €'.number_format((float) $statement->amount, 2).' vs ledger €'.number_format($signed, 2).').',
            ]);
        }

        DB::transaction(function () use ($statement, $journalLine) {
            $statement->update([
                'status' => 'matched',
                'matched_journal_line_id' => $journalLine->id,
            ]);
            $journalLine->update(['bank_statement_line_id' => $statement->id]);
            $journalLine->entry()->update(['status' => 'reconciled']);
        });

        return back()->with('success', 'Bank line matched and journal marked reconciled.');
    }
}
