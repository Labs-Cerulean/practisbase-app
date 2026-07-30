<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchitectPaApplication extends Model
{
    protected $fillable = [
        'user_id',
        'architect_project_id',
        'pa_number',
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

    public const STATUSES = [
        'active' => 'Active',
        'approved' => 'Approved',
        'refused' => 'Refused',
        'withdrawn' => 'Withdrawn',
        'archived' => 'Archived',
    ];

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

    public function displayLabel(): string
    {
        $title = trim((string) ($this->title ?? ''));

        return $title !== '' ? $this->pa_number.' · '.$title : $this->pa_number;
    }
}
