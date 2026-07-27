<?php

namespace App\Support;

use App\Models\Certificate;
use App\Models\ClinicalEntry;

/**
 * Opaque authenticity codes for stamped professional documents.
 * Printed on issued PDFs so replication / reuse can be checked against the practice register.
 */
class IssueCode
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public static function prefixForClinicalType(string $entryType): string
    {
        return match ($entryType) {
            'prescription' => 'RX',
            'referral' => 'RF',
            'certificate' => 'MC',
            default => 'PB',
        };
    }

    public static function prefixForCertificate(): string
    {
        return 'CT';
    }

    public static function generate(string $prefix): string
    {
        return strtoupper($prefix) . '-' . self::segment(4) . '-' . self::segment(4);
    }

    /**
     * Allocate a code unique across clinical entries and shared certificates.
     */
    public static function allocateUnique(string $prefix, int $maxAttempts = 12): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = self::generate($prefix);
            if (! self::exists($code)) {
                return $code;
            }
        }

        // Extremely unlikely; append an extra entropy segment.
        return self::generate($prefix) . '-' . self::segment(4);
    }

    public static function allocateForClinicalEntry(string $entryType): string
    {
        return self::allocateUnique(self::prefixForClinicalType($entryType));
    }

    public static function allocateForCertificate(): string
    {
        return self::allocateUnique(self::prefixForCertificate());
    }

    public static function exists(string $code): bool
    {
        $normalized = strtoupper(trim($code));

        return ClinicalEntry::where('issue_code', $normalized)->exists()
            || Certificate::where('issue_code', $normalized)->exists();
    }

    public static function normalize(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim($code)) ?? '');

        return $normalized !== '' ? $normalized : null;
    }

    private static function segment(int $length): string
    {
        $out = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }
}
