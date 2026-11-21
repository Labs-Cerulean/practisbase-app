<?php

namespace App\Support;

/**
 * Company expense receipts and payment proofs already stored for one user.
 * The path must stay under that user's prefix. Another tenant's file is refused.
 */
class CompanyStoredFile
{
    public const MIME = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'jfif' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'heic' => 'image/heic',
        'heif' => 'image/heif',
    ];

    public static function extension(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $ext = strtolower(pathinfo(str_replace('\\', '/', $path), PATHINFO_EXTENSION));

        return array_key_exists($ext, self::MIME) ? $ext : null;
    }

    public static function mime(?string $path): ?string
    {
        $ext = self::extension($path);

        return $ext === null ? null : self::MIME[$ext];
    }

    public static function previewKind(?string $path): ?string
    {
        $ext = self::extension($path);
        if ($ext === null) {
            return null;
        }

        return $ext === 'pdf' ? 'pdf' : 'image';
    }

    /**
     * A stored path this user may see. Null when it is empty, climbs out of
     * the prefix, or is not a photo or PDF already accepted on upload.
     */
    public static function owned(?string $path, string $prefix): ?string
    {
        if (! is_string($path) || $path === '' || str_contains($path, "\0")) {
            return null;
        }

        $path = self::normalize($path);
        if ($path === null) {
            return null;
        }

        $prefix = trim(str_replace('\\', '/', $prefix), '/');
        $needle = $prefix.'/';
        if ($prefix === '' || $needle === '/') {
            return null;
        }

        if (! str_starts_with($path, $needle)) {
            $at = strpos($path, '/'.$needle);
            if ($at === false) {
                return null;
            }
            $path = substr($path, $at + 1);
        }

        if (! str_starts_with($path, $needle) || self::extension($path) === null) {
            return null;
        }

        return $path;
    }

    /**
     * The stored object key when it is longer than the owned path, for
     * example a storage-root prefix that is part of the key itself.
     * A web address is not a key. A path that climbs out is refused.
     */
    public static function rawKey(?string $path): ?string
    {
        if (! is_string($path) || $path === '' || str_contains($path, "\0")) {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('/^\xEF\xBB\xBF/', '', $path) ?? $path;
        $path = trim($path, " \t\n\r\0\x0B\"'");
        if ($path === '' || preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
            return null;
        }
        if (str_contains($path, '..') || str_contains($path, '//')) {
            return null;
        }

        return ltrim($path, '/');
    }

    /**
     * Slash style, a storage-root prefix, or a URL whose path contains the
     * object key. A path that climbs out with ".." is refused before any
     * prefix is removed. The URL itself is never requested.
     */
    private static function normalize(string $path): ?string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('/^\xEF\xBB\xBF/', '', $path) ?? $path;
        $path = trim($path, " \t\n\r\0\x0B\"'");
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
            $parts = parse_url($path);
            $path = (is_array($parts) && isset($parts['path']) && is_string($parts['path'])) ? $parts['path'] : '';
            $path = str_replace('\\', '/', $path);
        }

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '//')) {
            return null;
        }

        foreach (['storage/app/private/', 'app/private/', 'private/'] as $root) {
            $at = strpos($path, $root);
            if ($at !== false && ($at === 0 || ($path[$at - 1] ?? '') === '/')) {
                $path = substr($path, $at + strlen($root));
                break;
            }
        }

        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '//') || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }
}
