<?php

namespace App\Models;

use App\Support\ArchitectConditionReportBlueprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchitectConditionReport extends Model
{
    protected $fillable = [
        'user_id',
        'architect_project_id',
        'architect_pa_application_id',
        'title',
        'report_type',
        'report_number',
        'inspected_on',
        'issued_on',
        'client_name',
        'client_address',
        'project_description',
        'inspected_address',
        'development_address',
        'payload',
        'stamped_at',
        'issue_code',
    ];

    protected function casts(): array
    {
        return [
            'inspected_on' => 'date',
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
        return $this->belongsTo(ArchitectProject::class, 'architect_project_id');
    }

    public function paApplication(): BelongsTo
    {
        return $this->belongsTo(ArchitectPaApplication::class, 'architect_pa_application_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ArchitectConditionReportPhoto::class, 'architect_condition_report_id')
            ->orderBy('sort_order')
            ->orderBy('id');
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
        return ArchitectConditionReportBlueprint::normalize($this->payload);
    }

    public function typeLabel(): string
    {
        $starters = ArchitectConditionReportBlueprint::starters();
        $key = (string) ($this->report_type ?? '');

        return $starters[$key]['label'] ?? ($key !== '' ? ucfirst(str_replace('_', ' ', $key)) : 'Condition report');
    }
}
