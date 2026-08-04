<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyJournalEntry extends Model
{
    protected $fillable = [
        'user_id',
        'entry_date',
        'description',
        'source_type',
        'source_id',
        'source_key',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CompanyJournalLine::class, 'journal_entry_id')->orderBy('id');
    }

    public function isPosted(): bool
    {
        return in_array($this->status, ['posted', 'reconciled'], true);
    }
}
