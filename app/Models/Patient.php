<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'user_id',
        'vault_id',
        'public_ref',
        'billing_client_id',
        'payload_ciphertext',
        'payload_nonce',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(MedicalVault::class, 'vault_id');
    }

    public function clinicalEntries(): HasMany
    {
        return $this->hasMany(ClinicalEntry::class);
    }
}
