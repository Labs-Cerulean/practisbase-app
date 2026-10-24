<?php

namespace App\Support;

/**
 * Compares a name or ID card with charts already in the vault.
 * Identity fields are encrypted, so the caller decrypts candidates first.
 */
class PatientIdentity
{
    public static function normalizeId(?string $value): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
    }

    public static function normalizeName(?string $value): string
    {
        $name = mb_strtolower(trim((string) $value));

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }

    /**
     * @return 'id'|'name'|null
     */
    public static function reason(string $name, ?string $idCard, string $otherName, ?string $otherId): ?string
    {
        $leftId = self::normalizeId($idCard);
        $rightId = self::normalizeId($otherId);

        if ($leftId !== '' && $rightId !== '') {
            if ($leftId === $rightId && strlen($leftId) >= 5) {
                return 'id';
            }

            $short = strlen($leftId) <= strlen($rightId) ? $leftId : $rightId;
            $long = strlen($leftId) <= strlen($rightId) ? $rightId : $leftId;
            $extra = strlen($long) - strlen($short);
            if (strlen($short) >= 6 && $extra >= 1 && $extra <= 2 && str_starts_with($long, $short)) {
                return 'id';
            }
        }

        $left = self::normalizeName($name);
        $right = self::normalizeName($otherName);
        if (mb_strlen($left) < 3 || mb_strlen($right) < 3) {
            return null;
        }

        if ($left === $right) {
            return 'name';
        }

        if (mb_strlen($left) >= 8 && mb_strlen($right) >= 8) {
            similar_text($left, $right, $percent);
            if ($percent >= 92.0) {
                return 'name';
            }
        }

        return null;
    }

    /**
     * @param  list<array{id: int, name: string, id_card?: ?string, date_of_birth?: ?string, archived?: bool}>  $candidates
     * @return list<array{id: int, name: string, id_card: string, date_of_birth: string, archived: bool, reason: string}>
     */
    public static function findMatches(string $name, ?string $idCard, array $candidates, int $ignoreId = 0): array
    {
        $idHits = [];
        $nameHits = [];

        foreach ($candidates as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id === 0 || $id === $ignoreId) {
                continue;
            }

            $reason = self::reason($name, $idCard, (string) ($row['name'] ?? ''), $row['id_card'] ?? null);
            if ($reason === null) {
                continue;
            }

            $hit = [
                'id' => $id,
                'name' => (string) ($row['name'] ?? 'Patient'),
                'id_card' => (string) ($row['id_card'] ?? ''),
                'date_of_birth' => (string) ($row['date_of_birth'] ?? ''),
                'archived' => (bool) ($row['archived'] ?? false),
                'reason' => $reason,
            ];

            if ($reason === 'id') {
                $idHits[] = $hit;
            } else {
                $nameHits[] = $hit;
            }
        }

        return array_slice(array_merge($idHits, $nameHits), 0, 8);
    }
}
