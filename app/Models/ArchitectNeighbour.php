<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ArchitectNeighbour extends Model
{
    protected $fillable = [
        'user_id',
        'architect_project_id',
        'architect_pa_application_id',
        'architect_condition_report_id',
        'address',
        'premises',
        'street',
        'locality',
        'owner_occupier_name',
        'phone',
        'email',
        'relation',
        'status',
        'appointment_on',
        'notes',
        'latitude',
        'longitude',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'appointment_on' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public const RELATIONS = [
        'abutting' => 'Abutting',
        'overlying' => 'Overlying',
        'underlying' => 'Underlying',
        'excavation_affected' => 'Excavation affected',
    ];

    public const STATUSES = [
        'identified' => 'Identified',
        'contacted' => 'Contacted',
        'appointment_booked' => 'Appointment booked',
        'survey_done' => 'Survey done',
        'report_drafted' => 'Report drafted',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'objected' => 'Objected',
        'filed_bca' => 'Filed for BCA',
    ];

    /** Linear tracker order — accepted/objected share a step before filed_bca. */
    public const STATUS_ORDER = [
        'identified' => 0,
        'contacted' => 1,
        'appointment_booked' => 2,
        'survey_done' => 3,
        'report_drafted' => 4,
        'sent' => 5,
        'accepted' => 6,
        'objected' => 6,
        'filed_bca' => 7,
    ];

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

    public function conditionReport(): BelongsTo
    {
        return $this->belongsTo(ArchitectConditionReport::class, 'architect_condition_report_id');
    }

    /**
     * A neighbour property can hold many condition reports (e.g. a block of flats).
     */
    public function conditionReports(): HasMany
    {
        return $this->hasMany(ArchitectConditionReport::class, 'architect_neighbour_id')
            ->orderByDesc('updated_at');
    }

    public function relationLabel(): string
    {
        return self::RELATIONS[$this->relation] ?? $this->relation;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function addressLine(): string
    {
        $primary = trim((string) $this->address);
        if ($primary !== '') {
            return $primary;
        }

        return implode(', ', array_filter([
            trim((string) $this->premises),
            trim((string) $this->street),
            trim((string) $this->locality),
        ]));
    }

    public function missingEmail(): bool
    {
        return ! filled($this->email);
    }

    public function appointmentOverdue(): bool
    {
        if (! $this->appointment_on) {
            return false;
        }

        $step = self::STATUS_ORDER[$this->status] ?? 0;
        if ($step >= (self::STATUS_ORDER['survey_done'] ?? 3)) {
            return false;
        }

        return $this->appointment_on->lt(Carbon::today());
    }

    public function isObjected(): bool
    {
        return $this->status === 'objected';
    }

    public function isPackReady(): bool
    {
        if (! in_array($this->status, ['accepted', 'filed_bca'], true)) {
            return false;
        }

        if ($this->relationLoaded('conditionReports')) {
            return $this->conditionReports->contains(fn ($r) => $r->stamped_at !== null);
        }

        if ($this->conditionReports()->whereNotNull('stamped_at')->exists()) {
            return true;
        }

        $report = $this->conditionReport;

        return $report && $report->stamped_at;
    }

    /**
     * Advance status forward only (never backwards; accepted↔objected allowed switch).
     */
    public function advanceStatusTo(string $target): bool
    {
        if (! array_key_exists($target, self::STATUSES)) {
            return false;
        }

        $current = self::STATUS_ORDER[$this->status] ?? -1;
        $next = self::STATUS_ORDER[$target] ?? -1;

        if ($next < 0) {
            return false;
        }

        if ($next < $current) {
            return false;
        }

        if ($next === $current && $this->status === $target) {
            return false;
        }

        $this->status = $target;

        return true;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, self>  $neighbours
     * @return array{total: int, missing_email: int, overdue: int, objected: int, pack_ready: int, cues: list<string>}
     */
    public static function deskSummary($neighbours): array
    {
        $missingEmail = 0;
        $overdue = 0;
        $objected = 0;
        $packReady = 0;

        foreach ($neighbours as $n) {
            if ($n->missingEmail()) {
                $missingEmail++;
            }
            if ($n->appointmentOverdue()) {
                $overdue++;
            }
            if ($n->isObjected()) {
                $objected++;
            }
            if ($n->isPackReady()) {
                $packReady++;
            }
        }

        $total = $neighbours->count();
        $cues = [];
        if ($missingEmail > 0) {
            $cues[] = $missingEmail.' missing email';
        }
        if ($overdue > 0) {
            $cues[] = $overdue.' overdue appointment'.($overdue === 1 ? '' : 's');
        }
        if ($objected > 0) {
            $cues[] = $objected.' objection'.($objected === 1 ? '' : 's');
        }
        if ($total > 0) {
            $cues[] = $packReady.' of '.$total.' BCA-ready';
        }

        return [
            'total' => $total,
            'missing_email' => $missingEmail,
            'overdue' => $overdue,
            'objected' => $objected,
            'pack_ready' => $packReady,
            'cues' => $cues,
        ];
    }
}
