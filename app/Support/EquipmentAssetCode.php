<?php

namespace App\Support;

use App\Models\EngineerEquipment;
use Illuminate\Support\Facades\DB;

class EquipmentAssetCode
{
    public static function next(int $userId, ?int $year = null): string
    {
        $year = $year ?: (int) date('Y');
        $patternPrefix = 'EQ-'.$year.'-';

        return DB::transaction(function () use ($userId, $year, $patternPrefix) {
            $lockKey = (int) sprintf('%u', crc32('pb_eq_'.$userId.'_'.$year)) % 2147483647;
            DB::select('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

            $latest = EngineerEquipment::where('user_id', $userId)
                ->where('asset_code', 'like', $patternPrefix.'%')
                ->orderByDesc('asset_code')
                ->value('asset_code');

            $nextSeq = 1;
            if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
                $nextSeq = ((int) $matches[1]) + 1;
            }

            return $patternPrefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        });
    }
}
