<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EngineerProject extends Model
{
    protected $fillable = [
        'user_id',
        'engineer_client_id',
        'name',
        'reference_code',
        'discipline',
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

    public const DISCIPLINES = [
        'general' => 'General',
        'electrical' => 'Electrical',
        'mechanical' => 'Mechanical',
        'civil' => 'Civil / structural',
        'ems' => 'EMS',
        'bms' => 'BMS',
    ];

    public const PHASES = [
        'design' => 'Design',
        'tender' => 'Tender',
        'installation' => 'Installation',
        'commissioning' => 'Commissioning',
        'handover' => 'Handover',
        'maintenance' => 'Maintenance',
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
        return $this->belongsTo(EngineerClient::class, 'engineer_client_id');
    }

    public function paApplications(): HasMany
    {
        return $this->hasMany(EngineerPaApplication::class, 'engineer_project_id');
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
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
