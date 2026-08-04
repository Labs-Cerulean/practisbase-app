<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchitectProject extends Model
{
    protected $fillable = [
        'user_id',
        'architect_client_id',
        'name',
        'reference_code',
        'phase',
        'status',
        'notes',
        'site_premises',
        'site_street',
        'site_locality',
        'site_address',
        'commencement_date',
    ];

    protected function casts(): array
    {
        return [
            'commencement_date' => 'date',
        ];
    }

    public const PHASES = [
        'concept' => 'Concept',
        'permit' => 'Permit / PA',
        'construction' => 'Construction',
        'completion' => 'Completion',
        'bca' => 'BCA / Method Statement',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'on_hold' => 'On hold',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ArchitectClient::class, 'architect_client_id');
    }

    public function paApplications(): HasMany
    {
        return $this->hasMany(ArchitectPaApplication::class, 'architect_project_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ArchitectDocument::class, 'architect_project_id');
    }

    public function siteParties(): HasMany
    {
        return $this->hasMany(ArchitectSiteParty::class, 'architect_project_id');
    }

    public function conditionReports(): HasMany
    {
        return $this->hasMany(ArchitectConditionReport::class, 'architect_project_id');
    }

    public function methodStatements(): HasMany
    {
        return $this->hasMany(ArchitectMethodStatement::class, 'architect_project_id');
    }

    public function siteAddressLine(): string
    {
        if (filled($this->site_address)) {
            return trim((string) $this->site_address);
        }

        $parts = array_filter([
            trim((string) $this->site_premises),
            trim((string) $this->site_street),
            trim((string) $this->site_locality),
        ]);

        return implode(', ', $parts);
    }
}
