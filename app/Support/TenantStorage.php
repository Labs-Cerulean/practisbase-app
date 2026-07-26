<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Tenant blob storage (receipts, logos). Prefer Cloudflare R2 in production.
 */
class TenantStorage
{
    public static function diskName(): string
    {
        return (string) config('filesystems.tenant_disk', 'local');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }

    public static function receiptsPath(int $userId): string
    {
        return 'tenants/' . $userId . '/receipts';
    }

    public static function brandingPath(int $userId): string
    {
        return 'tenants/' . $userId . '/branding';
    }
}
