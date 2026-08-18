<?php

namespace App\Support\Architect;

use App\Models\ArchitectProject;
use App\Models\Client;
use App\Models\User;

/**
 * Internal project references: PRACTICE-CLIENT-LOCALITY-NNN
 * Practice base comes from Settings → Full Name / Practice Name.
 */
class ProjectReference
{
    public const SEQUENCE_WIDTH = 3;

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
     * PRACTICE-CLIENT-LOCALITY (no sequence).
     */
    public static function prefix(string $practiceName, string $clientName, ?string $locality): string
    {
        $practice = self::slugPart($practiceName, 16, preferMeaningful: true);
        $client = self::slugPart($clientName, 16, preferMeaningful: false);
        $place = self::slugPart((string) $locality, 12, preferMeaningful: false);

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
            ->where('reference_code', 'ilike', $needle.'%')
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
     */
    public static function slugPart(string $text, int $maxLen = 16, bool $preferMeaningful = false): string
    {
        $raw = strtoupper(trim($text));
        if ($raw === '') {
            return '';
        }

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

        // Prefer last word for person/place names when it fits; else truncate.
        $last = end($words) ?: '';
        if ($preferMeaningful === false && strlen($last) >= 3 && strlen($last) <= $maxLen) {
            return $last;
        }

        // Initials of words if many, else hard truncate.
        if (count($words) >= 2) {
            $initials = implode('', array_map(fn ($w) => $w[0], $words));
            if (strlen($initials) >= 2 && strlen($initials) <= $maxLen) {
                // Prefer initials + remainder of last word for uniqueness
                $hybrid = $initials;
                if (strlen($last) > 1) {
                    $hybrid = implode('', array_map(fn ($w) => $w[0], array_slice($words, 0, -1))).$last;
                }
                if (strlen($hybrid) <= $maxLen) {
                    return $hybrid;
                }

                return substr($joined, 0, $maxLen);
            }
        }

        return substr($joined, 0, $maxLen);
    }

    public static function formatSequence(int $n): string
    {
        return str_pad((string) max(1, $n), self::SEQUENCE_WIDTH, '0', STR_PAD_LEFT);
    }
}
