<?php

namespace App\Support;

use App\Models\CompanyExpense;

/**
 * Finds the invoice file already stored for one company expense.
 *
 * A supplier PDF read on the way in is stored at
 * tenants/{user}/company/receipts/drafts/{token}.pdf on the tenant disk.
 * The token is forty letters. Saving is supposed to move that file to
 * tenants/{user}/company/receipts/{name}.pdf and write that key on
 * receipt_path. When the save did not, the object is still the drafts key.
 * A photo taken with the expense is tenants/{user}/company/receipts/{name}.jpg
 * and that key is receipt_path. A payment proof is
 * tenants/{user}/company/payments/{name}.ext. Older sole-trader uploads are
 * tenants/{user}/receipts/{name}.ext. Same disk, this user's folders only.
 */
class CompanyExpenseFiles
{
    /** A scan uploaded with the form and the row saved in the same sitting. */
    private const SITTING_SECONDS = 3 * 3600;

    public static function prefixes(int $userId): array
    {
        return [
            TenantStorage::companyReceiptsPath($userId),
            TenantStorage::companyPaymentsPath($userId),
            TenantStorage::receiptsPath($userId),
        ];
    }

    /**
     * A name returned by a directory listing, joined back onto the directory
     * when the listing omitted it. The result must still sit under that
     * directory. A name that climbs out is refused.
     */
    public static function listedObject(string $directory, string $listed): ?string
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');
        $listed = str_replace('\\', '/', trim($listed));
        if ($directory === '' || $listed === '' || str_contains($directory, "\0") || str_contains($listed, "\0")) {
            return null;
        }
        if (str_contains($directory, '..') || str_contains($listed, '..')) {
            return null;
        }

        if (! str_starts_with($listed, $directory.'/') && $listed !== $directory) {
            if (str_starts_with($listed, 'tenants/')) {
                return null;
            }
            if (str_contains($listed, '/') && ! preg_match('#^[A-Za-z0-9._/-]{1,240}$#', $listed)) {
                return null;
            }
            if (! str_contains($listed, '/') && ! preg_match('/^[A-Za-z0-9._-]{1,180}$/', $listed)) {
                return null;
            }
            $listed = $directory.'/'.ltrim($listed, '/');
        }

        return CompanyStoredFile::owned($listed, $directory);
    }

    /**
     * @param  iterable<int, CompanyExpense>  $expenses
     * @return array<int, string>
     */
    public static function forExpenses(int $userId, iterable $expenses): array
    {
        $direct = [];
        $needed = [];
        $savedAt = [];
        $used = [];

        foreach ($expenses as $expense) {
            if ((int) $expense->user_id !== $userId) {
                continue;
            }
            $path = self::direct($expense);
            if ($path !== null) {
                $direct[(int) $expense->id] = $path;
                $used[$path] = true;

                continue;
            }
            $number = trim((string) ($expense->supplier_invoice_number ?? ''));
            if ($number !== '') {
                $needed[(int) $expense->id] = $number;
                $savedAt[(int) $expense->id] = self::savedAt($expense);
            }
        }

        if ($needed === []) {
            return $direct;
        }

        return $direct + self::matchUnlinked($userId, $needed, $used, $savedAt);
    }

    public static function forExpense(CompanyExpense $expense): ?string
    {
        $direct = self::direct($expense);
        if ($direct !== null) {
            return $direct;
        }

        $number = trim((string) ($expense->supplier_invoice_number ?? ''));
        if ($number === '') {
            return null;
        }

        $used = [];
        $paths = CompanyExpense::where('user_id', $expense->user_id)
            ->where('id', '!=', $expense->id)
            ->whereNotNull('receipt_path')
            ->pluck('receipt_path');
        foreach ($paths as $path) {
            $safe = self::ownedPath((int) $expense->user_id, is_string($path) ? $path : null);
            if ($safe !== null) {
                $used[$safe] = true;
            }
        }

        $id = (int) $expense->id;
        $found = self::matchUnlinked(
            (int) $expense->user_id,
            [$id => $number],
            $used,
            [$id => self::savedAt($expense)]
        );

        return $found[$id] ?? null;
    }

    public static function prefixFor(int $userId, ?string $key): ?string
    {
        if ($key === null) {
            return null;
        }
        foreach (self::prefixes($userId) as $prefix) {
            if (CompanyStoredFile::owned($key, $prefix) !== null) {
                return $prefix;
            }
        }

        return null;
    }

    public static function direct(CompanyExpense $expense): ?string
    {
        return self::ownedPath((int) $expense->user_id, $expense->receipt_path);
    }

    public static function ownedPath(int $userId, ?string $path): ?string
    {
        foreach (self::prefixes($userId) as $prefix) {
            $safe = CompanyStoredFile::owned($path, $prefix);
            if ($safe !== null) {
                return $safe;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $needed
     * @param  array<string, bool>  $used
     * @param  array<int, int>  $savedAt
     * @return array<int, string>
     */
    private static function matchUnlinked(int $userId, array $needed, array $used, array $savedAt): array
    {
        $hits = [];
        $silent = [];
        foreach (self::candidatePdfs($userId) as $path) {
            if (isset($used[$path])) {
                continue;
            }
            try {
                $size = TenantStorage::disk()->size($path);
                if (! is_int($size) || $size < 8 || $size > 8 * 1024 * 1024) {
                    continue;
                }
                $bytes = TenantStorage::disk()->get($path);
            } catch (\Throwable) {
                continue;
            }
            if (! is_string($bytes) || $bytes === '') {
                continue;
            }
            $text = PdfPlainText::fromBinary($bytes);
            if ($text === '') {
                if (str_contains($path, '/drafts/')) {
                    $silent[$path] = $path;
                }

                continue;
            }
            foreach ($needed as $expenseId => $number) {
                if (self::textHasNumber($text, $number)) {
                    $hits[$expenseId][] = $path;
                }
            }
        }

        $ordered = $needed;
        uasort($ordered, function (string $left, string $right): int {
            return strlen($right) <=> strlen($left);
        });

        $taken = $used;
        $found = [];
        foreach ($ordered as $expenseId => $number) {
            $paths = array_values(array_unique($hits[$expenseId] ?? []));
            $paths = array_values(array_filter($paths, fn (string $path) => ! isset($taken[$path])));
            $chosen = self::choose($paths);
            if ($chosen === null) {
                continue;
            }
            $found[$expenseId] = $chosen;
            $taken[$chosen] = true;
        }

        $waiting = [];
        foreach ($needed as $expenseId => $number) {
            if (! isset($found[$expenseId]) && isset($savedAt[$expenseId])) {
                $waiting[$expenseId] = $savedAt[$expenseId];
            }
        }

        return $found + self::pairSilentDrafts($waiting, $silent, $taken);
    }

    /**
     * @param  array<int, string>  $paths
     */
    private static function choose(array $paths): ?string
    {
        if ($paths === []) {
            return null;
        }

        $drafts = array_values(array_filter(
            $paths,
            fn (string $path) => str_contains($path, '/drafts/')
        ));
        $pool = $drafts !== [] ? $drafts : $paths;
        usort($pool, function (string $left, string $right): int {
            $byTime = self::modifiedAt($right) <=> self::modifiedAt($left);

            return $byTime !== 0 ? $byTime : strcmp($right, $left);
        });

        return $pool[0];
    }

    /**
     * A photographed bill has no text layer, so the invoice number on the
     * row is not inside the file. The object is still the drafts PDF from
     * the same sitting. Pair only when each side is the other's closest
     * file, and only inside this user's drafts listing.
     *
     * @param  array<int, int>  $savedAt
     * @param  array<string, string>  $silent
     * @param  array<string, bool>  $taken
     * @return array<int, string>
     */
    private static function pairSilentDrafts(array $savedAt, array $silent, array $taken): array
    {
        $files = [];
        foreach ($silent as $path) {
            if (isset($taken[$path]) || ! str_contains($path, '/drafts/')) {
                continue;
            }
            $mtime = self::modifiedAt($path);
            if ($mtime > 0) {
                $files[$path] = $mtime;
            }
        }

        $pending = [];
        foreach ($savedAt as $id => $when) {
            if (is_int($when) && $when > 0) {
                $pending[(int) $id] = $when;
            }
        }

        $found = [];
        do {
            $paired = false;
            foreach (array_keys($pending) as $id) {
                if (isset($found[$id])) {
                    continue;
                }
                $choice = self::mutualDraft($id, $pending, $found, $files, $taken);
                if ($choice === null) {
                    continue;
                }
                $found[$id] = $choice;
                $taken[$choice] = true;
                $paired = true;
            }
        } while ($paired);

        return $found;
    }

    /**
     * @param  array<int, int>  $pending
     * @param  array<int, string>  $found
     * @param  array<string, int>  $files
     * @param  array<string, bool>  $taken
     */
    private static function mutualDraft(int $id, array $pending, array $found, array $files, array $taken): ?string
    {
        $path = self::closestFile($pending[$id], $files, $taken);
        if ($path === null) {
            return null;
        }
        $owner = self::closestExpense($files[$path], $pending, $found);

        return $owner === $id ? $path : null;
    }

    /**
     * @param  array<string, int>  $files
     * @param  array<string, bool>  $taken
     */
    private static function closestFile(int $savedAt, array $files, array $taken): ?string
    {
        $bestPath = null;
        $bestGap = null;
        $tie = false;
        foreach ($files as $path => $mtime) {
            if (isset($taken[$path])) {
                continue;
            }
            $gap = abs($mtime - $savedAt);
            if ($gap > self::SITTING_SECONDS) {
                continue;
            }
            if ($bestGap === null || $gap < $bestGap) {
                $bestGap = $gap;
                $bestPath = $path;
                $tie = false;
            } elseif ($gap === $bestGap) {
                $tie = true;
            }
        }

        return $tie ? null : $bestPath;
    }

    /**
     * @param  array<int, int>  $pending
     * @param  array<int, string>  $found
     */
    private static function closestExpense(int $mtime, array $pending, array $found): ?int
    {
        $bestId = null;
        $bestGap = null;
        $tie = false;
        foreach ($pending as $id => $savedAt) {
            if (isset($found[$id])) {
                continue;
            }
            $gap = abs($mtime - $savedAt);
            if ($gap > self::SITTING_SECONDS) {
                continue;
            }
            if ($bestGap === null || $gap < $bestGap) {
                $bestGap = $gap;
                $bestId = (int) $id;
                $tie = false;
            } elseif ($gap === $bestGap) {
                $tie = true;
            }
        }

        return $tie ? null : $bestId;
    }

    /**
     * @return array<int, string>
     */
    private static function candidatePdfs(int $userId): array
    {
        $disk = TenantStorage::disk();
        $dirs = [
            TenantStorage::companyReceiptsPath($userId).'/drafts',
            TenantStorage::companyReceiptsPath($userId),
            TenantStorage::companyPaymentsPath($userId),
            TenantStorage::receiptsPath($userId),
        ];
        $paths = [];
        foreach ($dirs as $dir) {
            try {
                $files = $disk->allFiles($dir);
            } catch (\Throwable) {
                try {
                    $files = $disk->files($dir);
                } catch (\Throwable) {
                    continue;
                }
            }
            if (! is_array($files)) {
                continue;
            }
            foreach ($files as $file) {
                if (! is_string($file)) {
                    continue;
                }
                $safe = self::listedObject($dir, $file);
                if ($safe === null || CompanyStoredFile::extension($safe) !== 'pdf') {
                    continue;
                }
                $paths[$safe] = $safe;
            }
        }

        return array_values($paths);
    }

    private static function savedAt(CompanyExpense $expense): int
    {
        $created = $expense->created_at;

        return $created instanceof \DateTimeInterface ? $created->getTimestamp() : 0;
    }

    private static function modifiedAt(string $path): int
    {
        try {
            $mtime = TenantStorage::disk()->lastModified($path);

            return is_int($mtime) ? $mtime : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function textHasNumber(string $text, string $invoiceNumber): bool
    {
        $number = mb_strtoupper(trim($invoiceNumber));
        if ($number === '') {
            return false;
        }
        $folded = mb_strtoupper($text);
        if (mb_strlen($number) >= 5 && str_contains($folded, $number)) {
            return true;
        }
        $compactNumber = preg_replace('/[^A-Z0-9]/', '', $number) ?? '';
        $compactText = preg_replace('/[^A-Z0-9]/', '', $folded) ?? '';

        return strlen($compactNumber) >= 6 && str_contains($compactText, $compactNumber);
    }
}
