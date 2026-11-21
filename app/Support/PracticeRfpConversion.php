<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turn part or all of a proforma into a tax invoice and move existing client cash across.
 * Cash is reassigned. A second receipt is not created.
 */
class PracticeRfpConversion
{
    public static function convert(User $user, Invoice $document, float $conversionAmount, string $today): Invoice
    {
        $conversionAmount = round($conversionAmount, 2);
        $created = null;

        DB::transaction(function () use ($user, $document, $conversionAmount, $today, &$created) {
            $rfp = Invoice::where('user_id', $user->id)
                ->where('id', $document->id)
                ->where('type', 'rfp')
                ->lockForUpdate()
                ->firstOrFail();

            $existingConversions = (float) $rfp->childDocuments()
                ->where('user_id', $user->id)
                ->where('type', 'invoice')
                ->sum('total');
            $maxAllowable = round((float) $rfp->total - $existingConversions, 2);

            if ($conversionAmount > $maxAllowable + 0.001) {
                throw ValidationException::withMessages([
                    'payment_error' => 'Conversion cannot exceed the remaining RFP value of €'.number_format($maxAllowable, 2),
                ]);
            }

            $partialRegime = RegimeHistory::forDate($user, $today);
            if (($partialRegime['vat_status'] ?? $user->vat_status) === 'article_11') {
                $year = FiscalYearGuard::yearFromDate($today);
                $ytdNet = FiscalYearTotals::forUserYear($user->id, $year)['net_total'];
                if (($ytdNet + $conversionAmount) > 35000) {
                    session()->flash('revenue_warning', 'Legal alert: Converting this RFP pushed your annual billed revenue over €35,000 (Article 11). You must apply for Article 10 VAT registration within 30 days.');
                }
            }

            $vatRate = (float) $rfp->vat_total > 0 ? 0.18 : 0;
            if ($vatRate > 0) {
                $subtotal = round($conversionAmount / 1.18, 2);
                $vat = round($conversionAmount - $subtotal, 2);
            } else {
                $subtotal = $conversionAmount;
                $vat = 0.0;
            }

            $invNumber = DocumentNumber::next($user->id, 'invoice', FiscalYearGuard::yearFromDate($today));

            $newInvoice = Invoice::create([
                'user_id' => $user->id,
                'client_id' => $rfp->client_id,
                'parent_document_id' => $rfp->id,
                'type' => 'invoice',
                'invoice_number' => $invNumber,
                'issue_date' => $today,
                'due_date' => now()->addDays(14)->toDateString(),
                'subtotal' => $subtotal,
                'vat_total' => $vat,
                'total' => $conversionAmount,
                'amount_paid' => 0,
                'status' => 'unpaid',
                'items' => [
                    [
                        'description' => 'Milestone Billing for '.$rfp->invoice_number,
                        'quantity' => 1,
                        'price' => $subtotal,
                        'amount' => $subtotal,
                    ],
                ],
            ]);

            $remainingToTransfer = $conversionAmount;
            $totalTransferred = 0.0;

            $cashPayments = Payment::where('user_id', $user->id)
                ->where('invoice_id', $rfp->id)
                ->where('is_transfer', false)
                ->where('amount', '>', 0)
                ->orderBy('payment_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($cashPayments as $payment) {
                if ($remainingToTransfer <= 0.009) {
                    break;
                }

                $payAmount = round((float) $payment->amount, 2);
                $take = round(min($payAmount, $remainingToTransfer), 2);
                if ($take <= 0) {
                    continue;
                }

                if (abs($take - $payAmount) < 0.009) {
                    $payment->update(['invoice_id' => $newInvoice->id]);
                } else {
                    $payment->update(['amount' => round($payAmount - $take, 2)]);
                    Payment::create([
                        'user_id' => $user->id,
                        'invoice_id' => $newInvoice->id,
                        'amount' => $take,
                        'payment_date' => $payment->payment_date,
                        'payment_method' => $payment->payment_method,
                        'notes' => trim(($payment->notes ? $payment->notes.' · ' : '').'Split from '.$rfp->invoice_number.' on convert'),
                        'is_transfer' => false,
                    ]);
                }

                $totalTransferred = round($totalTransferred + $take, 2);
                $remainingToTransfer = round($remainingToTransfer - $take, 2);
            }

            if ($totalTransferred > 0) {
                $newInvoice->update([
                    'amount_paid' => $totalTransferred,
                    'status' => ($totalTransferred >= $conversionAmount) ? 'paid' : 'partially_paid',
                ]);

                $rfpRemainingCash = (float) Payment::where('user_id', $user->id)
                    ->where('invoice_id', $rfp->id)
                    ->sum('amount');
                $rfp->update([
                    'amount_paid' => max(0, $rfpRemainingCash),
                ]);
            }

            if (abs($conversionAmount - $maxAllowable) < 0.009) {
                $rfp->update(['status' => 'converted']);
            }

            $created = $newInvoice->fresh();
        });

        return $created;
    }
}
