<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalVaultDevice extends Model
{
    protected $table = 'medical_vault_devices';

    protected $fillable = [
        'user_id',
        'vault_id',
        'credential_id',
        'public_key',
        'attestation_format',
        'wrap_nonce',
        'wrapped_dek',
        'device_label',
        'signature_counter',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_counter' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(MedicalVault::class, 'vault_id');
    }
}
