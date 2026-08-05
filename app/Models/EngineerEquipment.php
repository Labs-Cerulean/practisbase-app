<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class EngineerEquipment extends Model
{
    public const CATEGORIES = [
        'forklift' => 'Forklift',
        'side_loader' => 'Side loader',
        'crane' => 'Crane',
        'chain' => 'Lifting chain / sling',
        'brick_fork' => 'Brick fork',
        'lifting_accessory' => 'Lifting accessory',
        'mewp' => 'MEWP / cherry picker',
        'scaffold' => 'Scaffold / tower',
        'pressure' => 'Pressure equipment',
        'other' => 'Other plant / equipment',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'disposed' => 'Disposed',
    ];

    protected $table = 'engineer_equipment';

    protected $fillable = [
        'user_id',
        'client_id',
        'category',
        'name',
        'make',
        'model',
        'serial_number',
        'asset_code',
        'capacity_rating',
        'year_of_manufacture',
        'site_location',
        'status',
        'notes',
        'last_certified_on',
        'next_due_on',
    ];

    protected function casts(): array
    {
        return [
            'year_of_manufacture' => 'integer',
            'last_certified_on' => 'date',
            'next_due_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(EngineerCertificate::class, 'equipment_id')->orderByDesc('id');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isDue(?Carbon $within = null): bool
    {
        if (! $this->next_due_on || $this->status !== 'active') {
            return false;
        }

        $limit = $within ?? now()->startOfDay();

        return $this->next_due_on->lte($limit);
    }

    public function isOverdue(): bool
    {
        return $this->isDue(now()->startOfDay()->subDay());
    }

    public function dueTone(): array
    {
        if (! $this->next_due_on || $this->status !== 'active') {
            return ['bg' => '#f8fafc', 'fg' => '#64748b', 'border' => '#cbd5e1', 'label' => 'No due date'];
        }

        $today = now()->startOfDay();
        if ($this->next_due_on->lt($today)) {
            return ['bg' => '#fef2f2', 'fg' => '#991b1b', 'border' => '#fecaca', 'label' => 'Overdue'];
        }
        if ($this->next_due_on->lte($today->copy()->addDays(14))) {
            return ['bg' => '#fff7ed', 'fg' => '#9a3412', 'border' => '#fed7aa', 'label' => 'Due soon'];
        }
        if ($this->next_due_on->lte($today->copy()->addDays(30))) {
            return ['bg' => '#fffbeb', 'fg' => '#92400e', 'border' => '#fde68a', 'label' => 'Due in 30 days'];
        }

        return ['bg' => '#ecfdf5', 'fg' => '#065f46', 'border' => '#a7f3d0', 'label' => 'Scheduled'];
    }

    public function displayLabel(): string
    {
        $bits = array_filter([$this->name, $this->serial_number ? 'S/N '.$this->serial_number : null]);

        return implode(' · ', $bits) ?: $this->asset_code;
    }
}
