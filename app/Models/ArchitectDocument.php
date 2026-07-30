<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchitectDocument extends Model
{
    protected $fillable = [
        'user_id',
        'architect_client_id',
        'architect_project_id',
        'architect_pa_application_id',
        'title',
        'doc_type',
        'category',
        'status',
        'doc_code',
        'current_revision',
        'template_key',
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
        'photo' => 'Photo',
        'form' => 'Form / template',
        'declaration' => 'Declaration',
        'method_statement' => 'Method statement',
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
        return $this->belongsTo(ArchitectClient::class, 'architect_client_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ArchitectProject::class, 'architect_project_id');
    }

    public function paApplication(): BelongsTo
    {
        return $this->belongsTo(ArchitectPaApplication::class, 'architect_pa_application_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArchitectDocumentRevision::class, 'architect_document_id')->orderByDesc('revision_no');
    }

    public function latestRevision(): ?ArchitectDocumentRevision
    {
        return $this->revisions()->orderByDesc('revision_no')->first();
    }

    public function scopeLabel(): string
    {
        if ($this->architect_pa_application_id) {
            return 'PA';
        }
        if ($this->architect_project_id) {
            return 'Project';
        }

        return 'Client';
    }
}
