<?php

namespace App\Models;

use App\Support\Architect\EappsCaseUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchitectPaApplication extends Model
{
    protected $fillable = [
        'user_id',
        'architect_project_id',
        'pa_number',
        'case_type',
        'case_number',
        'case_year',
        'title',
        'status',
        'works_commencement_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'works_commencement_date' => 'date',
        ];
    }

    public const CASE_TYPES = [
        'PA' => 'PA — Planning Application',
        'PC' => 'PC — Planning Control',
        'DN' => 'DN — Development Notification',
        'RG' => 'RG — Regularisation',
        'DS' => 'DS — Dangerous Structure',
        'EC' => 'EC — Enforcement / related',
    ];

    public const STATUSES = [
        'tracking' => 'Tracking',
        'pending' => 'Pending / Awaiting Decision',
        'recommended' => 'Recommended for Approval or Refusal',
        'decided' => 'Decided',
        'endorsed' => 'Endorsed',
        'fee_payment' => 'Fee Payment',
        'under_appeal' => 'Under Appeal',
        'refused' => 'Refused',
        'revoked' => 'Revoked',
        'withdrawn' => 'Withdrawn',
        'archived' => 'Archived',
    ];

    /** Pre-migration values still readable until SQL upgrade runs. */
    public const LEGACY_STATUSES = [
        'active' => 'Tracking',
        'approved' => 'Endorsed',
    ];

    /** Statuses that still need perit attention on the desk. */
    public const OPEN_STATUSES = [
        'tracking',
        'pending',
        'recommended',
        'decided',
        'fee_payment',
        'under_appeal',
        'active',
    ];

    public static function statusOptions(): array
    {
        return self::STATUSES;
    }

    public static function statusLabelFor(?string $status): string
    {
        if ($status === null || $status === '') {
            return '—';
        }

        return self::STATUSES[$status]
            ?? self::LEGACY_STATUSES[$status]
            ?? $status;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ArchitectProject::class, 'architect_project_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ArchitectDocument::class, 'architect_pa_application_id');
    }

    public function resolvedCaseType(): string
    {
        $type = strtoupper(trim((string) ($this->case_type ?? '')));
        if ($type !== '') {
            return $type;
        }

        $parsed = EappsCaseUrl::parse($this->pa_number);

        return $parsed['case_type'] ?? 'PA';
    }

    public function resolvedCaseNumber(): string
    {
        $number = EappsCaseUrl::padCaseNumber($this->case_number);
        if ($number !== '') {
            return $number;
        }

        $parsed = EappsCaseUrl::parse($this->pa_number);

        return $parsed['case_number'] ?? '';
    }

    public function resolvedCaseYear(): string
    {
        $year = trim((string) ($this->case_year ?? ''));
        if (strlen($year) === 4) {
            $year = substr($year, -2);
        }
        if ($year !== '') {
            return $year;
        }

        $parsed = EappsCaseUrl::parse($this->pa_number);

        return $parsed['case_year'] ?? '';
    }

    public function canonicalNumber(): string
    {
        $formatted = EappsCaseUrl::formatDisplay(
            $this->resolvedCaseType(),
            $this->resolvedCaseNumber(),
            $this->resolvedCaseYear()
        );

        if ($formatted !== '') {
            return $formatted;
        }

        return trim((string) ($this->pa_number ?? ''));
    }

    public function eappsUrl(): ?string
    {
        return EappsCaseUrl::build([
            'case_type' => $this->resolvedCaseType(),
            'case_number' => $this->resolvedCaseNumber(),
            'case_year' => $this->resolvedCaseYear(),
            'pa_number' => $this->pa_number,
        ]);
    }

    public function displayLabel(): string
    {
        $number = $this->canonicalNumber();
        $title = trim((string) ($this->title ?? ''));

        if ($number === '') {
            $type = $this->resolvedCaseType();

            return $title !== '' ? $type.' pending · '.$title : $type.' pending';
        }

        return $title !== '' ? $number.' · '.$title : $number;
    }

    public function statusLabel(): string
    {
        return self::statusLabelFor($this->status);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
