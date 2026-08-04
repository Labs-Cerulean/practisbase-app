<?php

namespace App\Support;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class DocumentNumber
{
    /**
     * Next document number for a user/type/year: PREFIX-YYYY-NNNN
     * Uses a PostgreSQL advisory lock so concurrent creates cannot collide.
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

        $patternPrefix = $prefix.'-'.$year.'-';

        return DB::transaction(function () use ($userId, $type, $year, $patternPrefix) {
            $lockKey = self::advisoryLockKey($userId, $type, $year);
            DB::select('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

            $latest = Invoice::where('user_id', $userId)
                ->where('type', $type)
                ->where('invoice_number', 'like', $patternPrefix.'%')
                ->orderByDesc('invoice_number')
                ->value('invoice_number');

            $nextSeq = 1;
            if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
                $nextSeq = ((int) $matches[1]) + 1;
            }

            return $patternPrefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        });
    }

    private static function advisoryLockKey(int $userId, string $type, int $year): int
    {
        $hash = crc32('pb_doc_'.$userId.'_'.$type.'_'.$year);

        return (int) sprintf('%u', $hash) % 2147483647;
    }
}
