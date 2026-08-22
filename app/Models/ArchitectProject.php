<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchitectProject extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'name',
        'reference_code',
        'engagement_type',
        'phase',
        'status',
        'notes',
        'site_premises',
        'site_street',
        'site_locality',
        'site_address',
        'latitude',
        'longitude',
        'commencement_date',
    ];

    protected function casts(): array
    {
        return [
            'commencement_date' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public const ENGAGEMENT_TYPES = [
        'full_project' => 'Full project',
        'design_only' => 'Design only',
        'architecture_only' => 'Architecture only',
        'structural_only' => 'Structural only',
        'other' => 'Other',
    ];

    public const PHASES = [
        'concept' => 'Concept',
        'permit' => 'Permit / PA',
        'bca' => 'BCA / Method Statement',
        'construction' => 'Construction',
        'completion' => 'Completion',
    ];

    /** Natural perit sequence — used for auto-advance (never steps backwards). */
    public const PHASE_ORDER = [
        'concept',
        'permit',
        'bca',
        'construction',
        'completion',
    ];

    public static function engagementLabel(?string $key): string
    {
        if ($key === null || $key === '') {
            return '—';
        }

        return self::ENGAGEMENT_TYPES[$key] ?? $key;
    }

    public const STATUSES = [
        'active' => 'Active',
        'on_hold' => 'On hold',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ];

    /**
     * Move phase forward only (Concept → … → Completion). Returns true if changed.
     */
    public function advancePhaseTo(string $target): bool
    {
        if (! array_key_exists($target, self::PHASES)) {
            return false;
        }

        $order = array_flip(self::PHASE_ORDER);
        $currentIdx = $order[$this->phase] ?? -1;
        $targetIdx = $order[$target] ?? -1;

        if ($targetIdx < 0 || $targetIdx <= $currentIdx) {
            return false;
        }

        $this->phase = $target;

        return true;
    }

    /**
     * Soft phase cues from project status only — never moves backwards.
     * Construction advance comes from PA “works started on site”, not a project date.
     *
     * @param  array{status?: ?string, phase?: ?string}  $input
     * @return array{status?: ?string, phase?: ?string}
     */
    public static function applyProgressToPhase(array $input, ?self $existing = null): array
    {
        $phase = $input['phase'] ?? $existing?->phase ?? 'concept';
        $status = $input['status'] ?? $existing?->status ?? 'active';

        if ($status === 'completed') {
            $phase = 'completion';
        }

        $input['phase'] = $phase;

        return $input;
    }

    /**
     * When a planning case moves forward, nudge the parent project phase.
     */
    public function syncPhaseFromCase(ArchitectPaApplication $case): bool
    {
        $changed = false;

        if (in_array($case->status, ['decided', 'endorsed', 'fee_payment'], true)) {
            $changed = $this->advancePhaseTo('bca') || $changed;
        }

        if ($case->works_commencement_date) {
            $changed = $this->advancePhaseTo('construction') || $changed;
        }

        if (in_array($case->status, ['tracking', 'pending', 'recommended', 'active'], true)) {
            $changed = $this->advancePhaseTo('permit') || $changed;
        }

        if ($changed) {
            $this->save();
        }

        return $changed;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
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

    public function neighbours(): HasMany
    {
        return $this->hasMany(ArchitectNeighbour::class, 'architect_project_id');
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

    public function hasMapPin(): bool
    {
        return $this->latitude !== null
            && $this->longitude !== null
            && is_finite((float) $this->latitude)
            && is_finite((float) $this->longitude);
    }

    /**
     * @return array{id: int, name: string, lat: float, lng: float, locality: string, client: string, href: string, status: string}|null
     */
    public function mapPinPayload(): ?array
    {
        if (! $this->hasMapPin()) {
            return null;
        }

        return [
            'id' => $this->id,
            'name' => (string) $this->name,
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
            'locality' => trim((string) ($this->site_locality ?? '')),
            'client' => (string) ($this->client->name ?? ''),
            'href' => '/pro/architect/projects/'.$this->id,
            'status' => (string) $this->status,
        ];
    }
}
