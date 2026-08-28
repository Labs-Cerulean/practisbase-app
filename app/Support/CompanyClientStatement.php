<?php

namespace App\Support;

use App\Models\CompanyClient;
use App\Models\CompanyInvoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Open-item and history statements for company clients (proforma vs tax invoice tracks).
 */
class CompanyClientStatement
{
    /**
     * @return array{rows: Collection, official_owed: float, rfp_owed: float, total_owed: float}
     */
    public static function forClient(CompanyClient $client, ?int $userId = null): array
    {
        $userId = $userId ?: (int) Auth::id();
        $documents = CompanyInvoice::with([
            'payments' => fn ($q) => $q->orderBy('payment_date')->orderBy('id'),
            'childDocuments',
        ])
            ->where('user_id', $userId)
            ->where('company_client_id', $client->id)
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        return self::openStatement($documents);
    }

    /**
     * Open-item statement — settled invoices and fully converted RFPs omitted.
     *
     * @param  Collection<int, CompanyInvoice>  $documents
     * @return array{rows: Collection, official_owed: float, rfp_owed: float, total_owed: float}
     */
    public static function openStatement(Collection $documents): array
    {
        $rows = collect();
        $officialOwed = 0.0;
        $rfpOwed = 0.0;

        foreach ($documents as $doc) {
            if ($doc->type === 'invoice') {
                $credits = (float) $doc->childDocuments->where('type', 'credit_note')->sum('total');
                $paid = (float) $doc->payments->where('is_transfer', false)->sum('amount');
                $due = round((float) $doc->total - $credits - $paid, 2);
                if ($due <= 0.009) {
                    continue;
                }
                $officialOwed = round($officialOwed + $due, 2);
                $rows->push([
                    'date' => $doc->issue_date,
                    'label' => $doc->document_number,
                    'kind' => 'invoice',
                    'billed' => (float) $doc->total,
                    'credits' => $credits,
                    'paid' => $paid,
                    'due' => $due,
                ]);
            } elseif ($doc->type === 'rfp') {
                $converted = (float) $documents->where('linked_document_id', $doc->id)->where('type', 'invoice')->sum('total');
                $remaining = round(max(0, (float) $doc->total - $converted), 2);
                $paid = (float) $doc->payments->where('is_transfer', false)->sum('amount');
                $due = round($remaining - $paid, 2);
                if ($due <= 0.009) {
                    continue;
                }
                $rfpOwed = round($rfpOwed + $due, 2);
                $rows->push([
                    'date' => $doc->issue_date,
                    'label' => $doc->document_number.' (RFP)',
                    'kind' => 'rfp',
                    'billed' => $remaining,
                    'credits' => 0.0,
                    'paid' => $paid,
                    'due' => $due,
                ]);
            }
        }

        $rows = $rows->sortBy(fn ($row) => $row['date']->format('Y-m-d').'-'.$row['label'])->values();

        return [
            'rows' => $rows,
            'official_owed' => $officialOwed,
            'rfp_owed' => $rfpOwed,
            'total_owed' => round($officialOwed + $rfpOwed, 2),
        ];
    }

    /**
     * Full chronological log — includes settled invoices and converted RFPs.
     *
     * @param  Collection<int, CompanyInvoice>  $documents
     * @return array{rows: Collection, official_owed: float, rfp_owed: float, total_owed: float}
     */
    public static function transactionHistory(Collection $documents): array
    {
        $rows = collect();
        $runningOfficial = 0.0;
        $runningRfp = 0.0;

        foreach ($documents as $doc) {
            if ($doc->type === 'credit_note') {
                $runningOfficial = round($runningOfficial - (float) $doc->total, 2);
                $rows->push([
                    'date' => $doc->issue_date,
                    'label' => 'Credit '.$doc->document_number,
                    'kind' => 'credit',
                    'debit' => 0.0,
                    'credit' => (float) $doc->total,
                    'official_balance' => $runningOfficial,
                    'rfp_balance' => $runningRfp,
                    'note' => null,
                ]);
                continue;
            }

            if ($doc->type === 'invoice') {
                $runningOfficial = round($runningOfficial + (float) $doc->total, 2);
                $note = null;
                if ($doc->linked_document_id) {
                    $rfp = $documents->firstWhere('id', $doc->linked_document_id);
                    $note = $rfp ? 'Converted from '.$rfp->document_number : 'Converted from RFP';
                }
                $rows->push([
                    'date' => $doc->issue_date,
                    'label' => 'Invoice '.$doc->document_number,
                    'kind' => 'invoice',
                    'debit' => (float) $doc->total,
                    'credit' => 0.0,
                    'official_balance' => $runningOfficial,
                    'rfp_balance' => $runningRfp,
                    'note' => $note,
                ]);
            } elseif ($doc->type === 'rfp') {
                $converted = (float) $documents->where('linked_document_id', $doc->id)->where('type', 'invoice')->sum('total');
                $label = 'RFP '.$doc->document_number;
                $label .= ($doc->status === 'converted' || $converted >= (float) $doc->total - 0.009)
                    ? ' (converted)'
                    : ' (pro-forma)';

                $runningRfp = round($runningRfp + (float) $doc->total, 2);
                $rows->push([
                    'date' => $doc->issue_date,
                    'label' => $label,
                    'kind' => 'rfp',
                    'debit' => (float) $doc->total,
                    'credit' => 0.0,
                    'official_balance' => $runningOfficial,
                    'rfp_balance' => $runningRfp,
                    'note' => $converted >= 0.009
                        ? '€'.number_format($converted, 2).' later converted to tax invoice(s)'
                        : null,
                ]);

                if ($converted >= 0.009) {
                    $runningRfp = round($runningRfp - $converted, 2);
                    $rows->push([
                        'date' => $doc->issue_date,
                        'label' => 'RFP '.$doc->document_number.' → converted to tax invoice',
                        'kind' => 'convert',
                        'debit' => 0.0,
                        'credit' => $converted,
                        'official_balance' => $runningOfficial,
                        'rfp_balance' => $runningRfp,
                        'note' => 'Removes converted amount from RFP track (invoice rows carry the tax bill)',
                    ]);
                }
            }

            foreach ($doc->payments as $payment) {
                $amount = (float) $payment->amount;
                $isTransfer = (bool) $payment->is_transfer;
                if (! $isTransfer) {
                    if ($doc->type === 'invoice') {
                        $runningOfficial = round($runningOfficial - $amount, 2);
                    } elseif ($doc->type === 'rfp') {
                        $runningRfp = round($runningRfp - $amount, 2);
                    }
                }
                $rows->push([
                    'date' => $payment->payment_date,
                    'label' => ($amount < 0 ? 'Refund on ' : 'Payment on ').$doc->document_number,
                    'kind' => $isTransfer ? 'transfer' : 'payment',
                    'debit' => $amount < 0 ? abs($amount) : 0.0,
                    'credit' => $amount > 0 ? $amount : 0.0,
                    'official_balance' => $runningOfficial,
                    'rfp_balance' => $runningRfp,
                    'note' => $isTransfer ? 'Internal transfer — not client cash collected' : null,
                ]);
            }
        }

        return [
            'rows' => $rows,
            'official_owed' => max(0, $runningOfficial),
            'rfp_owed' => max(0, $runningRfp),
            'total_owed' => max(0, $runningOfficial) + max(0, $runningRfp),
        ];
    }
}
