<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyGlAccount extends Model
{
    protected $fillable = [
        'user_id',
        'account_code',
        'name',
        'type',
        'balance_sheet_category',
        'pl_group',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CompanyJournalLine::class, 'gl_account_id');
    }
}
