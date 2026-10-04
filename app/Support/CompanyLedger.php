<?php

namespace App\Support;

use App\Models\CompanyBankStatementLine;
use App\Models\CompanyBooksLock;
use App\Models\CompanyDividend;
use App\Models\CompanyExpense;
use App\Models\CompanyExpensePayment;
use App\Models\CompanyGlAccount;
use App\Models\CompanyInvoice;
use App\Models\CompanyJournalEntry;
use App\Models\CompanyJournalLine;
use App\Models\CompanyPayment;
use App\Models\CompanyPersonalReceipt;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Cerulean Labs Ltd double-entry ledger.
 * Operator-only. Never used by sole-trader fiscal paths.
 */
class CompanyLedger
{
    /** @var array<int, true> */
    private static array $chartReadyForUser = [];

    /**
     * Seed missing GL accounts in bulk (1–2 queries). Memoized per request —
     * never loop firstOrCreate (that is ~30 round-trips and times out on remote PG).
     */
    public static function ensureChart(User $user): void
    {
        if (isset(self::$chartReadyForUser[$user->id])) {
            return;
        }

        $definitions = CompanyChartOfAccounts::definitions();
        $codes = array_column($definitions, 'account_code');
        $existing = CompanyGlAccount::where('user_id', $user->id)
            ->whereIn('account_code', $codes)
            ->pluck('account_code')
            ->all();
        $have = array_flip($existing);

        $now = now();
        $insert = [];
        foreach ($definitions as $row) {
            if (isset($have[$row['account_code']])) {
                continue;
            }
            $insert[] = [
                'user_id' => $user->id,
                'account_code' => $row['account_code'],
                'name' => $row['name'],
                'type' => $row['type'],
                'balance_sheet_category' => $row['balance_sheet_category'],
                'pl_group' => $row['pl_group'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($insert !== []) {
            CompanyGlAccount::insert($insert);
        }

        self::$chartReadyForUser[$user->id] = true;
    }

    /** Skip User::findOrFail when the chart was already seeded this request. */
    public static function ensureChartForUserId(int $userId): void
    {
        if (isset(self::$chartReadyForUser[$userId])) {
            return;
        }

        self::ensureChart(User::findOrFail($userId));
    }

    public static function assertDateOpen(int $userId, string $date): void
    {
        $lock = CompanyBooksLock::where('user_id', $userId)->first();
        if ($lock && $date <= $lock->locked_through->format('Y-m-d')) {
            throw ValidationException::withMessages([
                'books' => 'Company books are locked through '.$lock->locked_through->format('d M Y').'. Unlock or post a reversing entry.',
            ]);
        }
    }

    public static function account(int $userId, string $code): CompanyGlAccount
    {
        $account = CompanyGlAccount::where('user_id', $userId)
            ->where('account_code', $code)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            throw new RuntimeException('Missing GL account '.$code.'. Seed the Cerulean chart of accounts.');
        }

        return $account;
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float|int|string, company_client_id?: ?int, memo?: ?string}>  $lines
     */
    public static function post(
        int $userId,
        string $entryDate,
        string $description,
        array $lines,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $sourceKey = null
    ): CompanyJournalEntry {
        self::assertDateOpen($userId, $entryDate);

        if ($sourceKey) {
            $existing = CompanyJournalEntry::where('user_id', $userId)
                ->where('source_key', $sourceKey)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $debits = 0.0;
        $credits = 0.0;
        $normalized = [];
        foreach ($lines as $line) {
            $amount = round((float) $line['amount'], 2);
            if ($amount <= 0) {
                continue;
            }
            $side = $line['side'];
            if (! in_array($side, ['debit', 'credit'], true)) {
                throw new RuntimeException('Journal line side must be debit or credit.');
            }
            if ($side === 'debit') {
                $debits += $amount;
            } else {
                $credits += $amount;
            }
            $normalized[] = [
                'account_code' => $line['account_code'],
                'side' => $side,
                'amount' => $amount,
                'company_client_id' => $line['company_client_id'] ?? null,
                'memo' => $line['memo'] ?? null,
            ];
        }

        if ($normalized === []) {
            throw new RuntimeException('Journal entry has no lines.');
        }

        if (round($debits, 2) !== round($credits, 2)) {
            throw new RuntimeException(sprintf(
                'Unbalanced journal (%s): debits %.2f != credits %.2f',
                $description,
                $debits,
                $credits
            ));
        }

        return DB::transaction(function () use ($userId, $entryDate, $description, $normalized, $sourceType, $sourceId, $sourceKey) {
            $entry = CompanyJournalEntry::create([
                'user_id' => $userId,
                'entry_date' => $entryDate,
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_key' => $sourceKey,
                'status' => 'posted',
            ]);

            foreach ($normalized as $line) {
                $account = self::account($userId, $line['account_code']);
                CompanyJournalLine::create([
                    'user_id' => $userId,
                    'journal_entry_id' => $entry->id,
                    'gl_account_id' => $account->id,
                    'company_client_id' => $line['company_client_id'],
                    'side' => $line['side'],
                    'amount' => $line['amount'],
                    'memo' => $line['memo'],
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    public static function postInvoiceIssued(CompanyInvoice $invoice): ?CompanyJournalEntry
    {
        if ($invoice->type !== 'invoice') {
            return null;
        }

        self::ensureChart(User::findOrFail($invoice->user_id));

        $subtotal = round((float) $invoice->subtotal, 2);
        $vat = round((float) $invoice->vat_total, 2);
        $total = round((float) $invoice->total, 2);
        $lines = [
            [
                'account_code' => CompanyChartOfAccounts::AR,
                'side' => 'debit',
                'amount' => $total,
                'company_client_id' => $invoice->company_client_id,
                'memo' => $invoice->document_number,
            ],
            [
                'account_code' => CompanyChartOfAccounts::REVENUE_SAAS,
                'side' => 'credit',
                'amount' => $subtotal,
                'company_client_id' => $invoice->company_client_id,
                'memo' => $invoice->document_number,
            ],
        ];
        if ($vat > 0) {
            $lines[] = [
                'account_code' => CompanyChartOfAccounts::OUTPUT_VAT,
                'side' => 'credit',
                'amount' => $vat,
                'company_client_id' => $invoice->company_client_id,
                'memo' => $invoice->document_number,
            ];
        }

        return self::post(
            $invoice->user_id,
            optional($invoice->supply_date)->format('Y-m-d') ?: $invoice->issue_date->format('Y-m-d'),
            'Invoice '.$invoice->document_number,
            $lines,
            'company_invoice',
            $invoice->id,
            'company_invoice:'.$invoice->id.':issued'
        );
    }

    public static function postCreditNote(CompanyInvoice $credit): ?CompanyJournalEntry
    {
        if ($credit->type !== 'credit_note') {
            return null;
        }

        self::ensureChart(User::findOrFail($credit->user_id));

        $subtotal = round((float) $credit->subtotal, 2);
        $vat = round((float) $credit->vat_total, 2);
        $total = round((float) $credit->total, 2);
        $lines = [
            [
                'account_code' => CompanyChartOfAccounts::SALES_RETURNS,
                'side' => 'debit',
                'amount' => $subtotal,
                'company_client_id' => $credit->company_client_id,
                'memo' => $credit->document_number,
            ],
            [
                'account_code' => CompanyChartOfAccounts::AR,
                'side' => 'credit',
                'amount' => $total,
                'company_client_id' => $credit->company_client_id,
                'memo' => $credit->document_number,
            ],
        ];
        if ($vat > 0) {
            $lines[] = [
                'account_code' => CompanyChartOfAccounts::OUTPUT_VAT,
                'side' => 'debit',
                'amount' => $vat,
                'company_client_id' => $credit->company_client_id,
                'memo' => $credit->document_number,
            ];
        }

        return self::post(
            $credit->user_id,
            $credit->issue_date->format('Y-m-d'),
            'Credit note '.$credit->document_number,
            $lines,
            'company_credit_note',
            $credit->id,
            'company_invoice:'.$credit->id.':credit'
        );
    }

    public static function postPayment(CompanyPayment $payment, CompanyInvoice $document): ?CompanyJournalEntry
    {
        if ($payment->is_transfer) {
            return null;
        }

        self::ensureChart(User::findOrFail($payment->user_id));

        $amount = round((float) $payment->amount, 2);
        $cashCode = CompanyChartOfAccounts::cashAccountCode((string) $payment->payment_method);
        $contra = $document->type === 'rfp'
            ? CompanyChartOfAccounts::CUSTOMER_ADVANCES
            : CompanyChartOfAccounts::AR;

        return self::post(
            $payment->user_id,
            $payment->payment_date->format('Y-m-d'),
            'Receipt '.$document->document_number,
            [
                [
                    'account_code' => $cashCode,
                    'side' => 'debit',
                    'amount' => $amount,
                    'company_client_id' => $document->company_client_id,
                    'memo' => $payment->payment_method,
                ],
                [
                    'account_code' => $contra,
                    'side' => 'credit',
                    'amount' => $amount,
                    'company_client_id' => $document->company_client_id,
                    'memo' => $document->document_number,
                ],
            ],
            'company_payment',
            $payment->id,
            'company_payment:'.$payment->id.':received'
        );
    }

    /**
     * Reclassify customer advances onto AR when an RFP converts (no second bank debit).
     */
    public static function postAdvanceToReceivable(
        CompanyInvoice $invoice,
        float $amount,
        string $entryDate,
        string $sourceKey
    ): ?CompanyJournalEntry {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }

        self::ensureChart(User::findOrFail($invoice->user_id));

        return self::post(
            $invoice->user_id,
            $entryDate,
            'Apply advances to '.$invoice->document_number,
            [
                [
                    'account_code' => CompanyChartOfAccounts::CUSTOMER_ADVANCES,
                    'side' => 'debit',
                    'amount' => $amount,
                    'company_client_id' => $invoice->company_client_id,
                ],
                [
                    'account_code' => CompanyChartOfAccounts::AR,
                    'side' => 'credit',
                    'amount' => $amount,
                    'company_client_id' => $invoice->company_client_id,
                ],
            ],
            'company_invoice_convert',
            $invoice->id,
            $sourceKey
        );
    }

    /**
     * @return list<array{account_code: string, side: string, amount: float, memo: ?string}>
     */
    public static function expenseJournalLines(
        float $net,
        float $vat,
        bool $reverseCharge,
        string $fundedBy,
        string $category,
        string $description
    ): array {
        $net = round($net, 2);
        $vat = round($vat, 2);
        $cash = $reverseCharge ? $net : round($net + $vat, 2);
        $expenseCode = CompanyChartOfAccounts::expenseAccountCode($category);
        $creditCode = match ($fundedBy) {
            'director' => CompanyChartOfAccounts::DIRECTOR_LOAN,
            'payable' => CompanyChartOfAccounts::TRADE_PAYABLES,
            default => CompanyChartOfAccounts::BANK,
        };

        $lines = [
            [
                'account_code' => $expenseCode,
                'side' => 'debit',
                'amount' => $net,
                'memo' => $description,
            ],
        ];

        if ($reverseCharge && $vat > 0) {
            $rcMemo = 'Reverse charge: '.$description;
            $lines[] = [
                'account_code' => CompanyChartOfAccounts::INPUT_VAT,
                'side' => 'debit',
                'amount' => $vat,
                'memo' => $rcMemo,
            ];
            $lines[] = [
                'account_code' => CompanyChartOfAccounts::OUTPUT_VAT,
                'side' => 'credit',
                'amount' => $vat,
                'memo' => $rcMemo,
            ];
        } elseif ($vat > 0) {
            $lines[] = [
                'account_code' => CompanyChartOfAccounts::INPUT_VAT,
                'side' => 'debit',
                'amount' => $vat,
                'memo' => $description,
            ];
        }

        $lines[] = [
            'account_code' => $creditCode,
            'side' => 'credit',
            'amount' => $cash,
            'memo' => $fundedBy,
        ];

        return $lines;
    }

    /**
     * @return list<array{account_code: string, side: string, amount: float, memo: ?string}>
     */
    public static function directorRefundJournalLines(float $gross, ?string $reference): array
    {
        $gross = round($gross, 2);

        return [
            [
                'account_code' => CompanyChartOfAccounts::DIRECTOR_LOAN,
                'side' => 'debit',
                'amount' => $gross,
                'memo' => $reference,
            ],
            [
                'account_code' => CompanyChartOfAccounts::BANK,
                'side' => 'credit',
                'amount' => $gross,
                'memo' => $reference,
            ],
        ];
    }

    /**
     * @param  list<array{account_code: string, side: string, amount: float|int|string, memo?: ?string}>  $lines
     * @return list<array{account_code: string, side: string, amount: float|int|string, memo?: ?string}>
     */
    public static function swapSides(array $lines): array
    {
        $swapped = [];
        foreach ($lines as $line) {
            $line['side'] = $line['side'] === 'debit' ? 'credit' : 'debit';
            $swapped[] = $line;
        }

        return $swapped;
    }

    public static function postExpense(CompanyExpense $expense): CompanyJournalEntry
    {
        self::ensureChart(User::findOrFail($expense->user_id));

        $isReverseCharge = (bool) $expense->is_reverse_charge;
        $narrative = $isReverseCharge
            ? 'Expense (reverse charge): '.$expense->description
            : 'Expense: '.$expense->description;

        return self::post(
            $expense->user_id,
            $expense->expense_date->format('Y-m-d'),
            $narrative,
            self::expenseJournalLines(
                (float) $expense->amount,
                (float) $expense->vat_amount,
                $isReverseCharge,
                (string) $expense->funded_by,
                (string) $expense->category,
                (string) $expense->description
            ),
            'company_expense',
            $expense->id,
            'company_expense:'.$expense->id.':posted'
        );
    }

    public static function postDirectorRefund(CompanyExpense $expense): CompanyJournalEntry
    {
        self::ensureChart(User::findOrFail($expense->user_id));

        $date = optional($expense->director_refunded_at)->format('Y-m-d') ?: now()->toDateString();

        return self::post(
            $expense->user_id,
            $date,
            'Director refund: '.$expense->description,
            self::directorRefundJournalLines($expense->cashTotal(), $expense->refund_reference),
            'company_expense_refund',
            $expense->id,
            'company_expense:'.$expense->id.':director_refund'
        );
    }

    /**
     * One bank payment that refunds several director-funded expenses.
     */
    public static function postDirectorRefundBatch(
        int $userId,
        string $date,
        float $amount,
        ?string $reference,
        int $paymentId,
        int $expenseCount
    ): CompanyJournalEntry {
        self::ensureChart(User::findOrFail($userId));

        $narrative = 'Director refund: '.$expenseCount.' '.($expenseCount === 1 ? 'expense' : 'expenses');

        return self::post(
            $userId,
            $date,
            $narrative,
            self::directorRefundJournalLines($amount, $reference),
            'company_expense_payment',
            $paymentId,
            'company_expense_payment:'.$paymentId.':posted'
        );
    }

    /**
     * @return list<array{account_code: string, side: string, amount: float, memo: ?string}>
     */
    public static function supplierPaymentJournalLines(float $gross, ?string $reference): array
    {
        $gross = round($gross, 2);

        return [
            [
                'account_code' => CompanyChartOfAccounts::TRADE_PAYABLES,
                'side' => 'debit',
                'amount' => $gross,
                'memo' => $reference,
            ],
            [
                'account_code' => CompanyChartOfAccounts::BANK,
                'side' => 'credit',
                'amount' => $gross,
                'memo' => $reference,
            ],
        ];
    }

    /**
     * One bank payment that settles supplier invoices previously credited to trade payables.
     */
    public static function postSupplierPaymentBatch(
        int $userId,
        string $date,
        float $amount,
        ?string $reference,
        int $paymentId,
        int $expenseCount
    ): CompanyJournalEntry {
        self::ensureChart(User::findOrFail($userId));

        $narrative = 'Supplier payment: '.$expenseCount.' '.($expenseCount === 1 ? 'expense' : 'expenses');

        return self::post(
            $userId,
            $date,
            $narrative,
            self::supplierPaymentJournalLines($amount, $reference),
            'company_expense_payment',
            $paymentId,
            'company_expense_payment:'.$paymentId.':posted'
        );
    }

    /**
     * Post the opposite of the expense (and of a director refund, when one was posted).
     * Returns how many bank-statement matches were released.
     */
    public static function reverseExpense(CompanyExpense $expense, string $reversalDate, ?string $note = null): int
    {
        if ($expense->reversed_at) {
            throw ValidationException::withMessages([
                'expense' => 'This expense is already reversed.',
            ]);
        }

        self::assertDateOpen($expense->user_id, $reversalDate);
        self::ensureChart(User::findOrFail($expense->user_id));

        return DB::transaction(function () use ($expense, $reversalDate, $note) {
            $released = self::releaseExpenseBankMatches($expense);

            $isReverseCharge = (bool) $expense->is_reverse_charge;
            self::post(
                $expense->user_id,
                $reversalDate,
                'Reversal of expense: '.$expense->description,
                self::swapSides(self::expenseJournalLines(
                    (float) $expense->amount,
                    (float) $expense->vat_amount,
                    $isReverseCharge,
                    (string) $expense->funded_by,
                    (string) $expense->category,
                    (string) $expense->description
                )),
                'company_expense_reversal',
                $expense->id,
                'company_expense:'.$expense->id.':reversal'
            );

            if ($expense->director_refunded_at) {
                self::post(
                    $expense->user_id,
                    $reversalDate,
                    'Reversal of director refund: '.$expense->description,
                    self::swapSides(self::directorRefundJournalLines($expense->cashTotal(), $expense->refund_reference)),
                    'company_expense_refund_reversal',
                    $expense->id,
                    'company_expense:'.$expense->id.':director_refund_reversal'
                );
            }

            if ($expense->funded_by === 'payable' && $expense->company_expense_payment_id) {
                $payment = $expense->relationLoaded('payment')
                    ? $expense->payment
                    : CompanyExpensePayment::where('user_id', $expense->user_id)
                        ->where('id', $expense->company_expense_payment_id)
                        ->first();

                self::post(
                    $expense->user_id,
                    $reversalDate,
                    'Reversal of supplier payment: '.$expense->description,
                    self::swapSides(self::supplierPaymentJournalLines(
                        $expense->cashTotal(),
                        $payment?->reference
                    )),
                    'company_expense_supplier_payment_reversal',
                    $expense->id,
                    'company_expense:'.$expense->id.':supplier_payment_reversal'
                );
            }

            $expense->update([
                'reversed_at' => $reversalDate,
                'reversal_note' => $note,
            ]);

            return $released;
        });
    }

    private static function releaseExpenseBankMatches(CompanyExpense $expense): int
    {
        $entries = CompanyJournalEntry::where('user_id', $expense->user_id)
            ->where('source_id', $expense->id)
            ->whereIn('source_type', ['company_expense', 'company_expense_refund'])
            ->get();

        if ($expense->company_expense_payment_id) {
            $paymentEntries = CompanyJournalEntry::where('user_id', $expense->user_id)
                ->where('source_type', 'company_expense_payment')
                ->where('source_id', $expense->company_expense_payment_id)
                ->get();
            $entries = $entries->concat($paymentEntries);
        }

        $released = 0;
        foreach ($entries as $entry) {
            $lines = CompanyJournalLine::where('user_id', $expense->user_id)
                ->where('journal_entry_id', $entry->id)
                ->whereNotNull('bank_statement_line_id')
                ->get();

            foreach ($lines as $line) {
                CompanyBankStatementLine::where('user_id', $expense->user_id)
                    ->where('id', $line->bank_statement_line_id)
                    ->update([
                        'status' => 'unreconciled',
                        'matched_journal_line_id' => null,
                    ]);
                $line->update(['bank_statement_line_id' => null]);
                $released++;
            }

            if ($entry->status === 'reconciled') {
                $entry->update(['status' => 'posted']);
            }
        }

        return $released;
    }

    public static function postShareCapital(CompanyProfile $profile): ?CompanyJournalEntry
    {
        if (! $profile->share_capital_received_at) {
            return null;
        }

        self::ensureChart(User::findOrFail($profile->user_id));
        $amount = round((float) $profile->share_capital_eur, 2);
        if ($amount <= 0) {
            return null;
        }

        return self::post(
            $profile->user_id,
            $profile->share_capital_received_at->format('Y-m-d'),
            'Share capital received',
            [
                [
                    'account_code' => CompanyChartOfAccounts::BANK,
                    'side' => 'debit',
                    'amount' => $amount,
                ],
                [
                    'account_code' => CompanyChartOfAccounts::SHARE_CAPITAL,
                    'side' => 'credit',
                    'amount' => $amount,
                ],
            ],
            'company_share_capital',
            $profile->id,
            'company_profile:'.$profile->id.':share_capital'
        );
    }

    public static function postDividendDeclared(CompanyDividend $dividend): CompanyJournalEntry
    {
        self::ensureChart(User::findOrFail($dividend->user_id));
        $amount = round((float) $dividend->amount, 2);

        return self::post(
            $dividend->user_id,
            $dividend->declared_on->format('Y-m-d'),
            $dividend->description,
            [
                [
                    'account_code' => CompanyChartOfAccounts::RETAINED_EARNINGS,
                    'side' => 'debit',
                    'amount' => $amount,
                ],
                [
                    'account_code' => CompanyChartOfAccounts::DIVIDEND_PAYABLE,
                    'side' => 'credit',
                    'amount' => $amount,
                ],
            ],
            'company_dividend',
            $dividend->id,
            'company_dividend:'.$dividend->id.':declared'
        );
    }

    public static function postDividendPaid(CompanyDividend $dividend): CompanyJournalEntry
    {
        self::ensureChart(User::findOrFail($dividend->user_id));
        $amount = round((float) $dividend->amount, 2);
        $date = optional($dividend->paid_on)->format('Y-m-d') ?: now()->toDateString();

        return self::post(
            $dividend->user_id,
            $date,
            'Dividend paid: '.$dividend->description,
            [
                [
                    'account_code' => CompanyChartOfAccounts::DIVIDEND_PAYABLE,
                    'side' => 'debit',
                    'amount' => $amount,
                ],
                [
                    'account_code' => CompanyChartOfAccounts::BANK,
                    'side' => 'credit',
                    'amount' => $amount,
                ],
            ],
            'company_dividend_paid',
            $dividend->id,
            'company_dividend:'.$dividend->id.':paid'
        );
    }

    /**
     * Money that arrived in the company bank but belongs to the director personally.
     *
     * @return list<array{account_code: string, side: string, amount: float, memo: ?string}>
     */
    public static function personalFundsReceivedLines(float $amount, ?string $reference): array
    {
        $amount = round($amount, 2);

        return [
            [
                'account_code' => CompanyChartOfAccounts::BANK,
                'side' => 'debit',
                'amount' => $amount,
                'memo' => $reference,
            ],
            [
                'account_code' => CompanyChartOfAccounts::DIRECTOR_LOAN,
                'side' => 'credit',
                'amount' => $amount,
                'memo' => $reference,
            ],
        ];
    }

    /**
     * @return list<array{account_code: string, side: string, amount: float, memo: ?string}>
     */
    public static function personalFundsReturnedLines(float $amount, ?string $reference): array
    {
        return self::swapSides(self::personalFundsReceivedLines($amount, $reference));
    }

    public static function postPersonalFundsReceived(CompanyPersonalReceipt $receipt): CompanyJournalEntry
    {
        self::ensureChart(User::findOrFail($receipt->user_id));

        return self::post(
            $receipt->user_id,
            $receipt->received_on->format('Y-m-d'),
            'Personal funds received: '.$receipt->description,
            self::personalFundsReceivedLines((float) $receipt->amount, $receipt->reference),
            'company_personal_receipt',
            $receipt->id,
            'company_personal_receipt:'.$receipt->id.':received'
        );
    }

    public static function postPersonalFundsReturned(CompanyPersonalReceipt $receipt): CompanyJournalEntry
    {
        self::ensureChart(User::findOrFail($receipt->user_id));
        $date = optional($receipt->returned_on)->format('Y-m-d') ?: now()->toDateString();

        return self::post(
            $receipt->user_id,
            $date,
            'Personal funds returned: '.$receipt->description,
            self::personalFundsReturnedLines((float) $receipt->amount, $receipt->return_reference),
            'company_personal_return',
            $receipt->id,
            'company_personal_receipt:'.$receipt->id.':returned'
        );
    }

    public static function reversePersonalReceipt(CompanyPersonalReceipt $receipt, string $reversalDate, ?string $note = null): int
    {
        if ($receipt->reversed_at) {
            throw ValidationException::withMessages([
                'receipt' => 'This personal receipt is already reversed.',
            ]);
        }

        self::assertDateOpen($receipt->user_id, $reversalDate);
        self::ensureChart(User::findOrFail($receipt->user_id));

        return DB::transaction(function () use ($receipt, $reversalDate, $note) {
            $released = self::releasePersonalReceiptMatches($receipt);

            self::post(
                $receipt->user_id,
                $reversalDate,
                'Reversal of personal funds received: '.$receipt->description,
                self::swapSides(self::personalFundsReceivedLines((float) $receipt->amount, $receipt->reference)),
                'company_personal_receipt_reversal',
                $receipt->id,
                'company_personal_receipt:'.$receipt->id.':received_reversal'
            );

            if ($receipt->returned_on) {
                self::post(
                    $receipt->user_id,
                    $reversalDate,
                    'Reversal of personal funds returned: '.$receipt->description,
                    self::swapSides(self::personalFundsReturnedLines((float) $receipt->amount, $receipt->return_reference)),
                    'company_personal_return_reversal',
                    $receipt->id,
                    'company_personal_receipt:'.$receipt->id.':returned_reversal'
                );
            }

            $receipt->update([
                'reversed_at' => $reversalDate,
                'reversal_note' => $note,
            ]);

            return $released;
        });
    }

    private static function releasePersonalReceiptMatches(CompanyPersonalReceipt $receipt): int
    {
        $entries = CompanyJournalEntry::where('user_id', $receipt->user_id)
            ->where('source_id', $receipt->id)
            ->whereIn('source_type', ['company_personal_receipt', 'company_personal_return'])
            ->get();

        $released = 0;
        foreach ($entries as $entry) {
            $lines = CompanyJournalLine::where('user_id', $receipt->user_id)
                ->where('journal_entry_id', $entry->id)
                ->whereNotNull('bank_statement_line_id')
                ->get();

            foreach ($lines as $line) {
                CompanyBankStatementLine::where('user_id', $receipt->user_id)
                    ->where('id', $line->bank_statement_line_id)
                    ->update([
                        'status' => 'unreconciled',
                        'matched_journal_line_id' => null,
                    ]);
                $line->update(['bank_statement_line_id' => null]);
                $released++;
            }

            if ($entry->status === 'reconciled') {
                $entry->update(['status' => 'posted']);
            }
        }

        return $released;
    }

    /**
     * @return array<string, float> account_code => signed balance (debit positive for assets/expenses)
     */
    public static function accountBalances(int $userId, ?string $asOfDate = null, ?string $fromDate = null): array
    {
        $query = CompanyJournalLine::query()
            ->selectRaw("company_gl_accounts.account_code as account_code, COALESCE(SUM(CASE WHEN company_journal_lines.side = 'debit' THEN company_journal_lines.amount ELSE -company_journal_lines.amount END), 0) as balance")
            ->join('company_journal_entries', 'company_journal_entries.id', '=', 'company_journal_lines.journal_entry_id')
            ->join('company_gl_accounts', 'company_gl_accounts.id', '=', 'company_journal_lines.gl_account_id')
            ->where('company_journal_lines.user_id', $userId)
            ->whereIn('company_journal_entries.status', ['posted', 'reconciled'])
            ->groupBy('company_gl_accounts.account_code');

        if ($asOfDate) {
            $query->where('company_journal_entries.entry_date', '<=', $asOfDate);
        }
        if ($fromDate) {
            $query->where('company_journal_entries.entry_date', '>=', $fromDate);
        }

        $balances = [];
        foreach ($query->get() as $row) {
            $balances[(string) $row->account_code] = round((float) $row->balance, 2);
        }

        return $balances;
    }

    /**
     * Natural balance for reporting: assets/expenses debit-positive; liability/equity/revenue credit-positive.
     */
    public static function naturalBalance(CompanyGlAccount $account, float $debitPositive): float
    {
        return in_array($account->type, ['liability', 'equity', 'revenue'], true)
            ? round(-$debitPositive, 2)
            : round($debitPositive, 2);
    }

    /**
     * @param  array<string, float>|null  $balances  Optional precomputed accountBalances($userId, $asOfDate).
     * @return array<string, mixed>
     */
    public static function trialBalance(int $userId, string $asOfDate, ?array $balances = null): array
    {
        self::ensureChartForUserId($userId);
        $balances ??= self::accountBalances($userId, $asOfDate);
        $accounts = CompanyGlAccount::where('user_id', $userId)->orderBy('account_code')->get();
        $rows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accounts as $account) {
            $raw = round((float) ($balances[$account->account_code] ?? 0), 2);
            if (abs($raw) < 0.005) {
                continue;
            }
            $debit = $raw > 0 ? $raw : 0.0;
            $credit = $raw < 0 ? abs($raw) : 0.0;
            $totalDebit += $debit;
            $totalCredit += $credit;
            $rows[] = [
                'account_code' => $account->account_code,
                'name' => $account->name,
                'type' => $account->type,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return [
            'as_of' => $asOfDate,
            'rows' => $rows,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'balanced' => abs(round($totalDebit - $totalCredit, 2)) < 0.005,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function profitAndLoss(int $userId, string $from, string $to): array
    {
        self::ensureChartForUserId($userId);
        $balances = self::accountBalances($userId, $to, $from);
        $accounts = CompanyGlAccount::where('user_id', $userId)
            ->whereIn('type', ['revenue', 'expense'])
            ->orderBy('account_code')
            ->get();

        $revenue = [];
        $costOfSales = [];
        $operating = [];
        $tax = [];
        $revenueTotal = 0.0;
        $cosTotal = 0.0;
        $opexTotal = 0.0;
        $taxTotal = 0.0;

        foreach ($accounts as $account) {
            $raw = round((float) ($balances[$account->account_code] ?? 0), 2);
            $natural = self::naturalBalance($account, $raw);
            if (abs($natural) < 0.005) {
                continue;
            }
            $row = [
                'account_code' => $account->account_code,
                'name' => $account->name,
                'amount' => $natural,
            ];
            if ($account->type === 'revenue') {
                $revenue[] = $row;
                $revenueTotal += $natural;
            } elseif ($account->pl_group === 'cost_of_sales') {
                $costOfSales[] = $row;
                $cosTotal += $natural;
            } elseif ($account->pl_group === 'tax') {
                $tax[] = $row;
                $taxTotal += $natural;
            } else {
                $operating[] = $row;
                $opexTotal += $natural;
            }
        }

        $gross = round($revenueTotal - $cosTotal, 2);
        $netBeforeTax = round($gross - $opexTotal, 2);
        $net = round($netBeforeTax - $taxTotal, 2);

        return [
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'cost_of_sales' => $costOfSales,
            'operating' => $operating,
            'tax' => $tax,
            'revenue_total' => round($revenueTotal, 2),
            'cost_of_sales_total' => round($cosTotal, 2),
            'gross_profit' => $gross,
            'operating_total' => round($opexTotal, 2),
            'profit_before_tax' => $netBeforeTax,
            'tax_total' => round($taxTotal, 2),
            'net_profit' => $net,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>|null  $profitAndLoss  Pass a prior profitAndLoss() result to avoid a second P&L / balance scan.
     */
    public static function balanceSheet(int $userId, string $asOfDate, string $periodStart, ?array $profitAndLoss = null): array
    {
        self::ensureChartForUserId($userId);
        $balances = self::accountBalances($userId, $asOfDate);
        $accounts = CompanyGlAccount::where('user_id', $userId)->orderBy('account_code')->get()->keyBy('account_code');

        $groups = [
            'non_current_assets' => [],
            'current_assets' => [],
            'capital_reserves' => [],
            'non_current_liabilities' => [],
            'current_liabilities' => [],
        ];

        foreach ($accounts as $code => $account) {
            if (! $account->balance_sheet_category) {
                continue;
            }
            $raw = round((float) ($balances[$code] ?? 0), 2);
            $natural = self::naturalBalance($account, $raw);
            if (abs($natural) < 0.005) {
                continue;
            }
            $groups[$account->balance_sheet_category][] = [
                'account_code' => $code,
                'name' => $account->name,
                'amount' => $natural,
            ];
        }

        $pl = $profitAndLoss ?? self::profitAndLoss($userId, $periodStart, $asOfDate);
        $currentEarnings = (float) $pl['net_profit'];
        if (abs($currentEarnings) >= 0.005) {
            $groups['capital_reserves'][] = [
                'account_code' => 'P&L',
                'name' => 'Current period profit / (loss)',
                'amount' => $currentEarnings,
            ];
        }

        $sum = function (array $rows): float {
            return round(array_sum(array_column($rows, 'amount')), 2);
        };

        $assets = round($sum($groups['non_current_assets']) + $sum($groups['current_assets']), 2);
        $equityLiab = round(
            $sum($groups['capital_reserves'])
            + $sum($groups['non_current_liabilities'])
            + $sum($groups['current_liabilities']),
            2
        );

        return [
            'as_of' => $asOfDate,
            'period_start' => $periodStart,
            'groups' => $groups,
            'totals' => [
                'non_current_assets' => $sum($groups['non_current_assets']),
                'current_assets' => $sum($groups['current_assets']),
                'assets' => $assets,
                'capital_reserves' => $sum($groups['capital_reserves']),
                'non_current_liabilities' => $sum($groups['non_current_liabilities']),
                'current_liabilities' => $sum($groups['current_liabilities']),
                'equity_and_liabilities' => $equityLiab,
            ],
            'balanced' => abs($assets - $equityLiab) < 0.005,
            'difference' => round($assets - $equityLiab, 2),
            'current_period_profit' => $currentEarnings,
            'balances' => $balances,
        ];
    }
}
