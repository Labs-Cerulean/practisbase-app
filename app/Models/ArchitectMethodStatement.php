<?php

namespace App\Models;

use App\Support\ArchitectMethodStatementBlueprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchitectMethodStatement extends Model
{
    protected $fillable = [
        'user_id',
        'architect_project_id',
        'architect_pa_application_id',
        'title',
        'statement_type',
        'statement_number',
        'issued_on',
        'commencement_note',
        'client_name',
        'client_address',
        'project_description',
        'site_address',
        'payload',
        'stamped_at',
        'issue_code',
    ];

    protected function casts(): array
    {
        return [
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
        return $this->hasMany(ArchitectMethodStatementPhoto::class, 'architect_method_statement_id')
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
        return ArchitectMethodStatementBlueprint::normalize($this->payload);
    }

    public function typeLabel(): string
    {
        $starters = ArchitectMethodStatementBlueprint::starters();
        $key = (string) ($this->statement_type ?? '');

        return $starters[$key]['label'] ?? ($key !== '' ? ucfirst(str_replace('_', ' ', $key)) : 'Method statement');
    }
}
