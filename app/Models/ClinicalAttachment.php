<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalAttachment extends Model
{
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    public const MAX_KILOBYTES = 10240;

    protected $fillable = [
        'user_id',
        'vault_id',
        'patient_id',
        'clinical_entry_id',
        'meta_ciphertext',
        'meta_nonce',
        'file_nonce',
        'storage_path',
        'byte_size',
        'ciphertext_sha256',
    ];

    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinicalEntry(): BelongsTo
    {
        return $this->belongsTo(ClinicalEntry::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(MedicalVault::class, 'vault_id');
    }
}
