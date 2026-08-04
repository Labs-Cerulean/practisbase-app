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

    public static function medicalAttachmentsPath(int $userId, int $vaultId): string
    {
        return 'medical/' . $userId . '/vault_' . $vaultId . '/attachments';
    }

    public static function architectDocumentsPath(int $userId): string
    {
        return 'tenants/' . $userId . '/architect/documents';
    }

    public static function engineerDocumentsPath(int $userId): string
    {
        return 'tenants/' . $userId . '/engineer/documents';
    }

    public static function engineerCertificatesPath(int $userId): string
    {
        return 'tenants/' . $userId . '/engineer/certificates';
    }

    public static function companyReceiptsPath(int $userId): string
    {
        return 'tenants/' . $userId . '/company/receipts';
    }

    public static function companyBrandingPath(int $userId): string
    {
        return 'tenants/' . $userId . '/company/branding';
    }
}
