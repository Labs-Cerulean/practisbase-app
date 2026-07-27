<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $table = 'certificates';

    protected $fillable = [
        'user_id',
        'title',
        'subject_name',
        'kind',
        'issued_on',
        'expires_on',
        'photo_path',
        'notes',
        'stamped_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'expires_on' => 'date',
            'stamped_at' => 'datetime',
        ];
    }

    public const KINDS = [
        'certificate' => 'Certificate',
        'declaration' => 'Declaration',
        'attestation' => 'Attestation',
        'medical_certificate' => 'Medical certificate',
        'fitness' => 'Fitness / clearance',
        'other' => 'Other',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->lt(now()->startOfDay());
    }

    public function isStamped(): bool
    {
        return $this->stamped_at !== null;
    }

    public function isEditable(): bool
    {
        return ! $this->isStamped();
    }
}
