<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'issued_at',
        'issued_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'issued_at' => 'datetime',
        ];
    }

    public const TYPES = [
        'journal' => 'Journal note',
        'prescription' => 'Digital prescription',
        'referral' => 'Referral letter',
        'certificate' => 'Medical certificate',
    ];

    /** Types that become immutable after Stamp & issue. */
    public const STAMPABLE_TYPES = [
        'prescription',
        'referral',
        'certificate',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ClinicalAttachment::class);
    }

    public function isStampable(): bool
    {
        return in_array($this->entry_type, self::STAMPABLE_TYPES, true);
    }

    public function isIssued(): bool
    {
        return $this->isStampable() && $this->issued_at !== null;
    }

    public function isEditable(): bool
    {
        return ! $this->isIssued();
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->entry_type] ?? $this->entry_type;
    }
}
