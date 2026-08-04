<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBooksLock extends Model
{
    protected $fillable = [
        'user_id',
        'locked_through',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'locked_through' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
