<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One receipt on a practice client, applied oldest-first across that client's open documents.
 * A proforma converts only when this receipt settles it. Cash left on an open proforma stays off tax.
 */
class ClientReceipt
{
    /**
     * @param  Collection<int, Invoice>  $documents
     * @return array{open: list<array<string, mixed>>, locked: list<array<string, mixed>>, open_total: float}
     */
    public static function preview(Collection $documents, int $userId): array
    {
        $lines = [];

        foreach ($documents as $doc) {
            $due = self::dueFromLoaded($doc);
            if ($due <= 0.009 || ! in_array($doc->type, ['invoice', 'rfp'], true)) {
                continue;
            }

            $year = FiscalYearGuard::yearFromDate($doc->issue_date);
            $lines[] = [
                'id' => (int) $doc->id,
                'label' => $doc->type === 'rfp' ? $doc->invoice_number.' (RFP)' : $doc->invoice_number,
                'kind' => $doc->type,
                'due' => $due,
                'date' => $doc->issue_date->format('Y-m-d'),
                'locked' => FiscalYearGuard::isClosed($userId, $year),
            ];
        }

        usort($lines, fn (array $a, array $b) => [$a['date'], $a['id']] <=> [$b['date'], $b['id']]);

        $open = array_values(array_filter($lines, fn (array $line) => ! $line['locked']));
        $locked = array_values(array_filter($lines, fn (array $line) => $line['locked']));

        return [
            'open' => $open,
            'locked' => $locked,
            'open_total' => ReceiptSplit::openTotal($open),
        ];
    }

    /**
     * @return array{amount: float, date: string, reference: ?string, lines: list<array<string, mixed>>, message: string}
     */
    public static function record(User $user, Client $client, string $paymentDate, float $amount, ?string $reference): array
    {
        if ((int) $client->user_id !== (int) $user->id) {
            abort(403);
        }

        $amount = round($amount, 2);
        $today = now()->toDateString();

        if ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($paymentDate))) {
            throw ValidationException::withMessages(['fiscal_error' => $lockError]);
        }

        $applied = [];

        DB::transaction(function () use ($user, $client, $paymentDate, $amount, $reference, $today, &$applied) {
            $documents = Invoice::where('user_id', $user->id)
                ->where('client_id', $client->id)
                ->orderBy('issue_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $ids = $documents->pluck('id');
            if ($ids->isNotEmpty()) {
                Payment::where('user_id', $user->id)
                    ->whereIn('invoice_id', $ids)
                    ->lockForUpdate()
                    ->get();
            }

            $documents->load([
                'payments' => fn ($q) => $q->where('user_id', $user->id),
                'childDocuments' => fn ($q) => $q->where('user_id', $user->id),
            ]);

            $preview = self::preview($documents, $user->id);
            $openTotal = $preview['open_total'];
            $lockedTotal = ReceiptSplit::openTotal($preview['locked']);

            if ($openTotal <= 0.009) {
                $message = $lockedTotal > 0.009
                    ? 'The open amounts on this client sit in a locked fiscal year.'
                    : 'This client has nothing open to receive.';
                throw ValidationException::withMessages(['amount' => $message]);
            }

            if ($amount - $openTotal > 0.009) {
                $message = 'This receipt is larger than the €'.number_format($openTotal, 2).' still open for this client.';
                if ($lockedTotal > 0.009) {
                    $message .= ' €'.number_format($lockedTotal, 2).' sits in a locked fiscal year and stays unchanged.';
                }
                throw ValidationException::withMessages(['amount' => $message]);
            }

            $slices = ReceiptSplit::apply($preview['open'], $amount);
            $settlesRfp = false;
            foreach ($slices as $slice) {
                $doc = $documents->firstWhere('id', $slice['id']);
                if ($slice['settles'] && $doc && $doc->type === 'rfp') {
                    $settlesRfp = true;
                }
            }

            if ($settlesRfp && ($lockError = FiscalYearGuard::ensureOpen($user->id, FiscalYearGuard::yearFromDate($today)))) {
                throw ValidationException::withMessages(['fiscal_error' => $lockError]);
            }

            $note = 'Client receipt';
            if (filled($reference)) {
                $note .= ' · '.$reference;
            }

            foreach ($slices as $slice) {
                $document = Invoice::where('user_id', $user->id)
                    ->where('client_id', $client->id)
                    ->where('id', $slice['id'])
                    ->lockForUpdate()
                    ->first();

                if (! $document || ! in_array($document->type, ['invoice', 'rfp'], true)) {
                    throw ValidationException::withMessages([
                        'amount' => 'That document is not on this client.',
                    ]);
                }

                if ($lockError = FiscalYearGuard::ensureOpenForDates($user->id, [
                    $paymentDate,
                    $document->issue_date,
                ])) {
                    throw ValidationException::withMessages(['fiscal_error' => $lockError]);
                }

                self::recordCash($user, $document, $slice['amount'], $paymentDate, $note);

                $detail = 'Applied to '.$document->invoice_number.'.';
                $convertedTo = null;

                if ($document->type === 'rfp' && $slice['settles']) {
                    $converted = (float) $document->childDocuments()
                        ->where('user_id', $user->id)
                        ->where('type', 'invoice')
                        ->sum('total');
                    $remaining = round((float) $document->total - $converted, 2);
                    if ($remaining > 0.009) {
                        $invoice = PracticeRfpConversion::convert($user, $document, $remaining, $today);
                        $convertedTo = $invoice->invoice_number;
                        $detail = 'Settled '.$document->invoice_number.' and converted it to '.$invoice->invoice_number.'.';
                    }
                }

                $applied[] = [
                    'label' => $document->invoice_number,
                    'amount' => $slice['amount'],
                    'detail' => $detail,
                    'converted' => $convertedTo,
                ];
            }
        });

        $converted = collect($applied)->pluck('converted')->filter()->values();
        $message = 'Receipt of €'.number_format($amount, 2).' recorded.';
        if ($converted->isNotEmpty()) {
            $message .= ' Converted '.$converted->implode(', ').'.';
        }

        return [
            'amount' => $amount,
            'date' => $paymentDate,
            'reference' => filled($reference) ? $reference : null,
            'lines' => $applied,
            'message' => $message,
        ];
    }

    private static function recordCash(User $user, Invoice $document, float $amount, string $paymentDate, string $note): void
    {
        Payment::create([
            'user_id' => $user->id,
            'invoice_id' => $document->id,
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'notes' => $note,
            'is_transfer' => false,
        ]);

        $paid = (float) Payment::where('user_id', $user->id)
            ->where('invoice_id', $document->id)
            ->sum('amount');

        $document->update([
            'amount_paid' => $paid,
            'status' => $paid + 0.009 >= (float) $document->total ? 'paid' : 'partially_paid',
        ]);
    }

    private static function dueFromLoaded(Invoice $doc): float
    {
        if ($doc->status === 'cancelled' || $doc->type === 'credit_note') {
            return 0.0;
        }

        $payments = $doc->relationLoaded('payments') ? $doc->payments : $doc->payments()->get();
        $children = $doc->relationLoaded('childDocuments') ? $doc->childDocuments : $doc->childDocuments()->get();
        $paid = (float) $payments->where('is_transfer', false)->sum('amount');

        if ($doc->type === 'invoice') {
            $credits = (float) $children->where('type', 'credit_note')->sum('total');

            return round((float) $doc->total - $credits - $paid, 2);
        }

        if ($doc->type === 'rfp') {
            if ($doc->status === 'converted') {
                return 0.0;
            }
            $converted = (float) $children->where('type', 'invoice')->sum('total');
            $remaining = round(max(0, (float) $doc->total - $converted), 2);

            return round($remaining - $paid, 2);
        }

        return 0.0;
    }
}
