<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalLibraryDocument extends Model
{
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'docx'];

    public const MAX_KILOBYTES = 15360;

    protected $fillable = [
        'user_id',
        'vault_id',
        'title_ciphertext',
        'title_nonce',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(MedicalVault::class, 'vault_id');
    }
}
