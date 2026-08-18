<?php

namespace App\Support\Architect;

/**
 * Build Planning Authority eApps Case Details URLs.
 *
 * Critical: case numbers must be zero-padded (typically 5 digits).
 * PA/0525/22 loads an empty shell; PA/00525/22 is the real case.
 */
class EappsCaseUrl
{
    public const BASE = 'https://eapps.pa.org.mt/Case/CaseDetails';

    public const CASE_NUMBER_WIDTH = 5;

    /**
     * @return array{case_type: string, case_number: string, case_year: string}|null
     */
    public static function parse(?string $display): ?array
    {
        $raw = trim((string) $display);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^([A-Za-z]+)\s*[\/\s-]\s*0*(\d+)\s*[\/\s-]\s*(\d{2}|\d{4})$/', $raw, $m)) {
            $year = $m[3];
            if (strlen($year) === 4) {
                $year = substr($year, -2);
            }

            return [
                'case_type' => strtoupper($m[1]),
                'case_number' => self::padCaseNumber($m[2]),
                'case_year' => $year,
            ];
        }

        return null;
    }

    public static function padCaseNumber(string|int|null $number): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
        if ($digits === '') {
            return '';
        }

        if (strlen($digits) > self::CASE_NUMBER_WIDTH) {
            return $digits;
        }

        return str_pad($digits, self::CASE_NUMBER_WIDTH, '0', STR_PAD_LEFT);
    }

    public static function formatDisplay(string $caseType, string $caseNumber, string $caseYear): string
    {
        $type = strtoupper(trim($caseType));
        $number = self::padCaseNumber($caseNumber);
        $year = trim($caseYear);
        if (strlen($year) === 4) {
            $year = substr($year, -2);
        }

        if ($type === '' || $number === '' || $year === '') {
            return '';
        }

        return $type.'/'.$number.'/'.$year;
    }

    /**
     * @param  array{case_type?: ?string, case_number?: ?string, case_year?: ?string, pa_number?: ?string}  $parts
     */
    public static function build(array $parts): ?string
    {
        $type = strtoupper(trim((string) ($parts['case_type'] ?? '')));
        $number = self::padCaseNumber($parts['case_number'] ?? '');
        $year = trim((string) ($parts['case_year'] ?? ''));

        if (($type === '' || $number === '' || $year === '') && filled($parts['pa_number'] ?? null)) {
            $parsed = self::parse((string) $parts['pa_number']);
            if ($parsed) {
                $type = $parsed['case_type'];
                $number = $parsed['case_number'];
                $year = $parsed['case_year'];
            }
        }

        if ($type === '' || $number === '' || $year === '') {
            return null;
        }

        if (strlen($year) === 4) {
            $year = substr($year, -2);
        }

        if (! preg_match('/^\d{2}$/', $year)) {
            return null;
        }

        return self::BASE.'?'.http_build_query([
            'casenumber' => $number,
            'caseType' => $type,
            'caseYear' => $year,
        ]);
    }
}
