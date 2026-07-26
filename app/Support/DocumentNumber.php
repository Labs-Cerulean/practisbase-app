<?php

namespace App\Support;

use App\Models\Invoice;

class DocumentNumber
{
    /**
     * Next document number for a user/type/year: PREFIX-YYYY-NNNN
     */
    public static function next(int $userId, string $type, ?int $year = null): string
    {
        $year = $year ?: (int) date('Y');
        $prefix = match ($type) {
            'rfp' => 'RFP',
            'invoice' => 'INV',
            'credit_note' => 'CN',
            default => strtoupper($type),
        };

        $patternPrefix = $prefix . '-' . $year . '-';

        $latest = Invoice::where('user_id', $userId)
            ->where('type', $type)
            ->where('invoice_number', 'like', $patternPrefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextSeq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        return $patternPrefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
