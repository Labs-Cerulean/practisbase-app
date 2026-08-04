<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EngineerDocument extends Model
{
    protected $fillable = [
        'user_id',
        'engineer_client_id',
        'engineer_project_id',
        'engineer_pa_application_id',
        'title',
        'doc_type',
        'category',
        'status',
        'doc_code',
        'current_revision',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_revision' => 'integer',
        ];
    }

    public const CATEGORIES = [
        'document' => 'Document',
        'drawing' => 'Drawing',
        'calculation' => 'Calculation',
        'report' => 'Report',
        'photo' => 'Photo',
        'form' => 'Form / template',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'issued' => 'Issued',
        'superseded' => 'Superseded',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(EngineerProject::class, 'engineer_project_id');
    }

    public function paApplication(): BelongsTo
    {
        return $this->belongsTo(EngineerPaApplication::class, 'engineer_pa_application_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(EngineerDocumentRevision::class, 'engineer_document_id')->orderByDesc('revision_no');
    }

    public function latestRevision(): ?EngineerDocumentRevision
    {
        return $this->revisions()->orderByDesc('revision_no')->first();
    }

    public function scopeLabel(): string
    {
        if ($this->engineer_pa_application_id) {
            return 'PA';
        }
        if ($this->engineer_project_id) {
            return 'Project';
        }

        return 'Client';
    }
}
