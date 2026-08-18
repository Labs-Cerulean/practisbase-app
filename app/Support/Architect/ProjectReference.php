<?php

namespace App\Support\Architect;

use App\Models\ArchitectProject;
use App\Models\Client;
use App\Models\User;

/**
 * Internal project references: PRACTICE-CLIENT-LOCALITY-NNN
 * Each name part is truncated to PART_MAX (4) characters.
 * Practice base comes from Settings → Full Name / Practice Name.
 */
class ProjectReference
{
    public const SEQUENCE_WIDTH = 3;

    public const PART_MAX = 4;

    /** @var list<string> */
    private const NOISE_WORDS = [
        'THE', 'AND', 'OF', 'A', 'AN', 'FOR',
        'STUDIO', 'ARCHITECTURE', 'ARCHITECT', 'ARCHITECTS', 'PERIT', 'PERITI',
        'LTD', 'LIMITED', 'CO', 'COMPANY', 'PRACTICE', 'OFFICE', 'GROUP',
    ];

    /**
     * Build a suggested reference for this practice + client + locality.
     * Sequence is the next free NNN among this user's matching prefixes.
     */
    public static function suggest(
        User $user,
        Client|string|null $client,
        ?string $locality,
        ?int $excludeProjectId = null
    ): string {
        $clientName = $client instanceof Client ? (string) $client->name : (string) $client;
        $prefix = self::prefix((string) $user->name, $clientName, $locality);

        return self::withNextSequence((int) $user->id, $prefix, $excludeProjectId);
    }

    /**
     * PRACTICE-CLIENT-LOCALITY (no sequence). Each segment ≤ PART_MAX chars.
     */
    public static function prefix(string $practiceName, string $clientName, ?string $locality): string
    {
        $practice = self::slugPart($practiceName, self::PART_MAX, preferMeaningful: true);
        $client = self::slugPart($clientName, self::PART_MAX, preferMeaningful: false);
        $place = self::slugPart((string) $locality, self::PART_MAX, preferMeaningful: false);

        $parts = array_values(array_filter([$practice, $client, $place], fn ($p) => $p !== ''));

        return $parts === [] ? 'PROJ' : implode('-', $parts);
    }

    public static function withNextSequence(int $userId, string $prefix, ?int $excludeProjectId = null): string
    {
        $prefix = strtoupper(trim($prefix, "- \t\n\r\0\x0B"));
        if ($prefix === '') {
            $prefix = 'PROJ';
        }

        $next = self::nextSequence($userId, $prefix, $excludeProjectId);

        return $prefix.'-'.str_pad((string) $next, self::SEQUENCE_WIDTH, '0', STR_PAD_LEFT);
    }

    /**
     * Next free sequence for codes starting with PREFIX- among this user's projects.
     */
    public static function nextSequence(int $userId, string $prefix, ?int $excludeProjectId = null): int
    {
        $prefix = strtoupper(trim($prefix, "- \t\n\r\0\x0B"));
        $needle = $prefix.'-';

        $codes = ArchitectProject::query()
            ->where('user_id', $userId)
            ->when($excludeProjectId, fn ($q) => $q->where('id', '!=', $excludeProjectId))
            ->whereNotNull('reference_code')
            ->where('reference_code', '!=', '')
            ->whereRaw('UPPER(reference_code) LIKE ?', [strtoupper($needle).'%'])
            ->pluck('reference_code');

        return self::nextSequenceFromCodes($codes->all(), $prefix);
    }

    /**
     * @param  iterable<int, string|null>  $codes
     */
    public static function nextSequenceFromCodes(iterable $codes, string $prefix): int
    {
        $prefix = strtoupper(trim($prefix, "- \t\n\r\0\x0B"));
        $pattern = '/^'.preg_quote($prefix, '/').'-(\d+)\s*$/i';
        $max = 0;

        foreach ($codes as $code) {
            $raw = trim((string) $code);
            if ($raw === '') {
                continue;
            }
            if (preg_match($pattern, $raw, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return max(1, $max + 1);
    }

    /**
     * Uppercase alphanumeric slug from a name (noise words stripped when preferMeaningful).
     * Always capped at $maxLen (default PART_MAX = 4).
     */
    public static function slugPart(string $text, int $maxLen = self::PART_MAX, bool $preferMeaningful = false): string
    {
        $maxLen = max(1, $maxLen);
        $raw = strtoupper(trim($text));
        if ($raw === '') {
            return '';
        }

        // Keep letters in names like St. Julian's / O'Brien together.
        $raw = str_replace(["'", '’', '`'], '', $raw);
        $raw = preg_replace('/[^A-Z0-9]+/', ' ', $raw) ?? '';
        $words = array_values(array_filter(explode(' ', $raw), fn ($w) => $w !== ''));

        if ($words === []) {
            return '';
        }

        if ($preferMeaningful) {
            $meaningful = array_values(array_filter(
                $words,
                fn ($w) => ! in_array($w, self::NOISE_WORDS, true)
            ));
            if ($meaningful !== []) {
                $words = $meaningful;
            }
        }

        $joined = implode('', $words);
        if (strlen($joined) <= $maxLen) {
            return $joined;
        }

        // Prefer last word for person/place names (truncate if still long).
        $last = end($words) ?: '';
        if ($preferMeaningful === false && strlen($last) >= 3) {
            return substr($last, 0, $maxLen);
        }

        return substr($joined, 0, $maxLen);
    }

    public static function formatSequence(int $n): string
    {
        return str_pad((string) max(1, $n), self::SEQUENCE_WIDTH, '0', STR_PAD_LEFT);
    }
}
