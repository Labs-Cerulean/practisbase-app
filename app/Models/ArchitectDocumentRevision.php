<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchitectDocumentRevision extends Model
{
    protected $fillable = [
        'user_id',
        'architect_document_id',
        'revision_no',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'change_note',
        'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'revision_no' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ArchitectDocument::class, 'architect_document_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function isInlineViewable(): bool
    {
        $mime = strtolower((string) ($this->mime_type ?? ''));
        if ($mime === 'application/pdf' || str_starts_with($mime, 'image/')) {
            return true;
        }

        $name = strtolower((string) ($this->original_name ?? ''));

        return str_ends_with($name, '.pdf')
            || preg_match('/\.(jpe?g|png|gif|webp)$/', $name) === 1;
    }
}
