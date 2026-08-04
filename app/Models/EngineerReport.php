<?php

namespace App\Models;

use App\Support\EngineerReportBlueprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EngineerReport extends Model
{
    protected $fillable = [
        'user_id',
        'engineer_project_id',
        'engineer_pa_application_id',
        'title',
        'report_type',
        'report_number',
        'surveyed_on',
        'issued_on',
        'conclusion',
        'client_name',
        'client_address',
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
            'surveyed_on' => 'date',
            'issued_on' => 'date',
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

    public function photos(): HasMany
    {
        return $this->hasMany(EngineerReportPhoto::class, 'engineer_report_id')->orderBy('sort_order')->orderBy('id');
    }

    public function isStamped(): bool
    {
        return $this->stamped_at !== null;
    }

    public function isEditable(): bool
    {
        return ! $this->isStamped();
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizedPayload(): array
    {
        return EngineerReportBlueprint::normalize($this->payload);
    }

    public function typeLabel(): string
    {
        $starters = EngineerReportBlueprint::starters();
        $key = (string) ($this->report_type ?? '');

        return $starters[$key]['label'] ?? ($key !== '' ? ucfirst($key) : 'Report');
    }
}
