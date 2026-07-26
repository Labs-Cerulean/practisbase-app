<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalEntry extends Model
{
    protected $fillable = [
        'user_id',
        'vault_id',
        'patient_id',
        'entry_type',
        'entry_date',
        'payload_ciphertext',
        'payload_nonce',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }

    public const TYPES = [
        'journal' => 'Journal note',
        'prescription' => 'Digital prescription',
        'referral' => 'Referral letter',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
