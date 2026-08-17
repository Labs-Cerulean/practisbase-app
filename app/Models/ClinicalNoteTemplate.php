<?php

namespace App\Models;

use App\Support\ClinicalNoteTemplates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalNoteTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'fields_json',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'fields_json' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return list<array{key: string, label: string, type: string}>
     */
    public function normalizedFields(): array
    {
        return ClinicalNoteTemplates::sanitizeFieldDefinitions($this->fields_json ?? []);
    }

    public function catalogueKey(): string
    {
        return ClinicalNoteTemplates::customKey((int) $this->id);
    }
}
