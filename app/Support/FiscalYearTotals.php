<?php

namespace App\Support;

use App\Models\Invoice;

/**
 * Accrual fiscal totals for a calendar year.
 * Credit notes reverse the parent invoice's year (not the CN print date).
 */
class FiscalYearTotals
{
    /**
     * @return array{
     *     invoice_total: float,
     *     credit_total: float,
     *     net_total: float,
     *     invoice_subtotal: float,
     *     credit_subtotal: float,
     *     net_subtotal: float,
     *     output_vat: float,
     *     credited_vat: float,
     *     net_output_vat: float
     * }
     */
    public static function forUserYear(int $userId, int $year): array
    {
        $invoices = Invoice::where('user_id', $userId)
            ->where('type', 'invoice')
            ->whereYear('issue_date', $year)
            ->selectRaw('COALESCE(SUM(total), 0) as total_sum, COALESCE(SUM(subtotal), 0) as subtotal_sum, COALESCE(SUM(vat_total), 0) as vat_sum')
            ->first();

        $credits = Invoice::where('user_id', $userId)
            ->where('type', 'credit_note')
            ->whereHas('parentDocument', function ($q) use ($userId, $year) {
                $q->where('user_id', $userId)
                    ->where('type', 'invoice')
                    ->whereYear('issue_date', $year);
            })
            ->selectRaw('COALESCE(SUM(total), 0) as total_sum, COALESCE(SUM(subtotal), 0) as subtotal_sum, COALESCE(SUM(vat_total), 0) as vat_sum')
            ->first();

        $invoiceTotal = (float) ($invoices->total_sum ?? 0);
        $invoiceSubtotal = (float) ($invoices->subtotal_sum ?? 0);
        $outputVat = (float) ($invoices->vat_sum ?? 0);
        $creditTotal = (float) ($credits->total_sum ?? 0);
        $creditSubtotal = (float) ($credits->subtotal_sum ?? 0);
        $creditedVat = (float) ($credits->vat_sum ?? 0);

        return [
            'invoice_total' => $invoiceTotal,
            'credit_total' => $creditTotal,
            'net_total' => $invoiceTotal - $creditTotal,
            'invoice_subtotal' => $invoiceSubtotal,
            'credit_subtotal' => $creditSubtotal,
            'net_subtotal' => $invoiceSubtotal - $creditSubtotal,
            'output_vat' => $outputVat,
            'credited_vat' => $creditedVat,
            'net_output_vat' => $outputVat - $creditedVat,
        ];
    }
}
