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
 * That draft is shown only when its text contains the invoice number on the row.
 * A file with no text is not matched.
 * A photo taken with the expense is tenants/{user}/company/receipts/{name}.jpg
 * and that key is receipt_path. A payment proof is
 * tenants/{user}/company/payments/{name}.ext. Older sole-trader uploads are
 * tenants/{user}/receipts/{name}.ext. Same disk, this user's folders only.
 */
class CompanyExpenseFiles
{
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
            }
        }

        if ($needed === []) {
            return $direct;
        }

        return $direct + self::matchUnlinked($userId, $needed, $used);
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
            $used
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
     * @return array<int, string>
     */
    private static function matchUnlinked(int $userId, array $needed, array $used): array
    {
        $hits = [];
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

        return $found;
    }

    /**
     * One file, or the single draft when a copy also sits outside drafts.
     * Two drafts with the same number are not a match.
     *
     * @param  array<int, string>  $paths
     */
    private static function choose(array $paths): ?string
    {
        if (count($paths) === 1) {
            return $paths[0];
        }

        $drafts = array_values(array_filter(
            $paths,
            fn (string $path) => str_contains($path, '/drafts/')
        ));

        return count($drafts) === 1 ? $drafts[0] : null;
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
