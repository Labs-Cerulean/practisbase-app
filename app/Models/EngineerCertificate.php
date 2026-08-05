<?php

namespace App\Models;

use App\Support\EngineerCertificateBlueprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EngineerCertificate extends Model
{
    protected $fillable = [
        'user_id',
        'engineer_project_id',
        'engineer_pa_application_id',
        'equipment_id',
        'title',
        'certificate_number',
        'inspected_on',
        'issued_on',
        'expires_on',
        'next_inspection_on',
        'outcome',
        'holder_name',
        'holder_address',
        'contact_person',
        'contact_phone',
        'site_address',
        'payload',
        'stamped_at',
        'issue_code',
    ];

    protected function casts(): array
    {
        return [
            'inspected_on' => 'date',
            'issued_on' => 'date',
            'expires_on' => 'date',
            'next_inspection_on' => 'date',
            'stamped_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(EngineerProject::class, 'engineer_project_id');
    }

    public function paApplication(): BelongsTo
    {
        return $this->belongsTo(EngineerPaApplication::class, 'engineer_pa_application_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(EngineerEquipment::class, 'equipment_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EngineerCertificatePhoto::class, 'engineer_certificate_id')->orderBy('sort_order')->orderBy('id');
    }

    public function isStamped(): bool
    {
        return $this->stamped_at !== null;
    }

    public function isEditable(): bool
    {
        return ! $this->isStamped();
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->lt(now()->startOfDay());
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizedPayload(): array
    {
        return EngineerCertificateBlueprint::normalize($this->payload);
    }
}
