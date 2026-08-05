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
        'multi_discipline' => 'Multi-discipline',
        'electrical' => 'Electrical',
        'mechanical' => 'Mechanical',
        'fire' => 'Fire',
        'hvac' => 'HVAC',
        'other' => 'Other',
    ];

    /** Old keys kept for display / edit of existing projects. */
    public const DISCIPLINE_LEGACY = [
        'general' => 'Multi-discipline',
        'civil' => 'Other',
        'ems' => 'Other',
        'bms' => 'Other',
    ];

    public static function disciplineOptions(): array
    {
        return self::DISCIPLINES;
    }

    public static function disciplineLabel(?string $key): string
    {
        if ($key === null || $key === '') {
            return '—';
        }

        return self::DISCIPLINES[$key]
            ?? self::DISCIPLINE_LEGACY[$key]
            ?? $key;
    }

    public static function normalizeDiscipline(?string $key): string
    {
        $key = trim((string) $key);
        if (isset(self::DISCIPLINES[$key])) {
            return $key;
        }

        return match ($key) {
            'general' => 'multi_discipline',
            'civil', 'ems', 'bms' => 'other',
            default => 'other',
        };
    }

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

    public function documents(): HasMany
    {
        return $this->hasMany(EngineerDocument::class, 'engineer_project_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(EngineerCertificate::class, 'engineer_project_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(EngineerReport::class, 'engineer_project_id');
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
