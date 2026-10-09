<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyBankStatementLine;
use App\Models\CompanyJournalLine;
use App\Support\BankMatch;
use App\Support\CompanyChartOfAccounts;
use App\Support\CompanyLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            ->limit(200)
            ->get();

        $matchedByStatement = collect();
        if ($lines->isNotEmpty()) {
            $matchedByStatement = CompanyJournalLine::with('entry')
                ->where('user_id', $user->id)
                ->whereIn('bank_statement_line_id', $lines->pluck('id'))
                ->orderBy('id')
                ->get()
                ->groupBy('bank_statement_line_id');
        }

        return view('company.accounts.bank', [
            'lines' => $lines,
            'unmatchedLedger' => $unmatchedLedger,
            'matchedByStatement' => $matchedByStatement,
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
        $validated = $request->validate([
            'journal_line_ids' => 'required|array|min:1|max:30',
            'journal_line_ids.*' => 'integer',
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['journal_line_ids'])));
        $bank = CompanyLedger::account($user->id, CompanyChartOfAccounts::BANK);

        $journalLines = CompanyJournalLine::with('entry')
            ->where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->where('gl_account_id', $bank->id)
            ->whereNull('bank_statement_line_id')
            ->whereHas('entry', fn ($q) => $q->whereIn('status', ['posted', 'reconciled']))
            ->get();

        if ($journalLines->count() !== count($ids)) {
            return back()->withErrors([
                'journal_line_ids' => 'One of those ledger lines is no longer available to match.',
            ]);
        }

        $ledgerTotal = BankMatch::signedTotal($journalLines->map(fn (CompanyJournalLine $row) => $row->signedAmount()));

        DB::transaction(function () use ($user, $line, $journalLines, $ledgerTotal) {
            $statement = CompanyBankStatementLine::where('user_id', $user->id)
                ->where('id', $line)
                ->lockForUpdate()
                ->firstOrFail();

            if ($statement->status !== 'unreconciled') {
                throw ValidationException::withMessages([
                    'journal_line_ids' => 'This statement line is already matched.',
                ]);
            }

            if (! BankMatch::totalsMatch((float) $statement->amount, $ledgerTotal)) {
                throw ValidationException::withMessages([
                    'journal_line_ids' => 'Amounts do not match (statement €'.number_format((float) $statement->amount, 2).' vs selected ledger €'.number_format($ledgerTotal, 2).'). Tick every invoice or expense that makes up this bank movement.',
                ]);
            }

            $first = $journalLines->sortBy('id')->first();
            $statement->update([
                'status' => 'matched',
                'matched_journal_line_id' => $first->id,
            ]);

            foreach ($journalLines as $journalLine) {
                $journalLine->update(['bank_statement_line_id' => $statement->id]);
                if ($journalLine->entry && $journalLine->entry->status !== 'reconciled') {
                    $journalLine->entry->update(['status' => 'reconciled']);
                }
            }
        });

        $count = $journalLines->count();

        return back()->with('success', $count === 1
            ? 'Bank line matched and journal marked reconciled.'
            : 'Bank line matched to '.$count.' ledger lines.');
    }
}
