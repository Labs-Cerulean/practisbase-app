<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAction extends Model
{
    protected $fillable = [
        'user_id',
        'vault_id',
        'patient_id',
        'label_id',
        'note_ciphertext',
        'note_nonce',
        'done_at',
    ];

    protected function casts(): array
    {
        return [
            'done_at' => 'datetime',
        ];
    }

    public function isDone(): bool
    {
        return $this->done_at !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(ClinicalLabel::class, 'label_id');
    }
}
