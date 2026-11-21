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
        'png' => 'image/png',
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

        $path = str_replace('\\', '/', $path);
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, '//')) {
            return null;
        }

        $prefix = trim(str_replace('\\', '/', $prefix), '/');
        if ($prefix === '' || ! str_starts_with($path, $prefix.'/')) {
            return null;
        }

        if (self::extension($path) === null) {
            return null;
        }

        return $path;
    }
}
