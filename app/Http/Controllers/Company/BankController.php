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
            'statement_line_ids' => 'nullable|array|max:20',
            'statement_line_ids.*' => 'integer',
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['journal_line_ids'])));
        $statementIds = array_values(array_unique(array_merge(
            [$line],
            array_map('intval', $validated['statement_line_ids'] ?? [])
        )));
        sort($statementIds);

        $bank = CompanyLedger::account($user->id, CompanyChartOfAccounts::BANK);
        $statementCount = 0;
        $ledgerCount = 0;

        DB::transaction(function () use ($user, $ids, $statementIds, $bank, &$statementCount, &$ledgerCount) {
            $statements = CompanyBankStatementLine::where('user_id', $user->id)
                ->whereIn('id', $statementIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($statements->count() !== count($statementIds)) {
                throw ValidationException::withMessages([
                    'statement_line_ids' => 'One of those bank lines is no longer available to match.',
                ]);
            }

            foreach ($statements as $statement) {
                if ($statement->status !== 'unreconciled') {
                    throw ValidationException::withMessages([
                        'statement_line_ids' => 'One of those bank lines is already matched.',
                    ]);
                }
            }

            $journalLines = CompanyJournalLine::with('entry')
                ->where('user_id', $user->id)
                ->whereIn('id', $ids)
                ->where('gl_account_id', $bank->id)
                ->whereNull('bank_statement_line_id')
                ->whereHas('entry', fn ($q) => $q->whereIn('status', ['posted', 'reconciled']))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($journalLines->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'journal_line_ids' => 'One of those ledger lines is no longer available to match.',
                ]);
            }

            $statementTotal = BankMatch::signedTotal($statements->map(fn (CompanyBankStatementLine $row) => (float) $row->amount));
            $ledgerTotal = BankMatch::signedTotal($journalLines->map(fn (CompanyJournalLine $row) => $row->signedAmount()));

            if (! BankMatch::totalsMatch($statementTotal, $ledgerTotal)) {
                throw ValidationException::withMessages([
                    'journal_line_ids' => 'Amounts do not match (bank €'.number_format($statementTotal, 2).' vs ledger €'.number_format($ledgerTotal, 2).'). Tick every bank line and every invoice or expense in this payment.',
                ]);
            }

            $anchorId = (int) $statements->first()->id;
            $firstJournalId = (int) $journalLines->first()->id;

            foreach ($statements as $statement) {
                $statement->update([
                    'status' => 'matched',
                    'matched_journal_line_id' => $firstJournalId,
                ]);
            }

            foreach ($journalLines as $journalLine) {
                $journalLine->update(['bank_statement_line_id' => $anchorId]);
                if ($journalLine->entry && $journalLine->entry->status !== 'reconciled') {
                    $journalLine->entry->update(['status' => 'reconciled']);
                }
            }

            $statementCount = $statements->count();
            $ledgerCount = $journalLines->count();
        });

        if ($statementCount === 1 && $ledgerCount === 1) {
            return back()->with('success', 'Bank line matched and journal marked reconciled.');
        }

        return back()->with('success', $statementCount.' bank '.($statementCount === 1 ? 'line' : 'lines').' matched to '.$ledgerCount.' ledger '.($ledgerCount === 1 ? 'line' : 'lines').'.');
    }
}
