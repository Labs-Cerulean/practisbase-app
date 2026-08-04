<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDividend extends Model
{
    protected $fillable = [
        'user_id',
        'declared_on',
        'paid_on',
        'amount',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'declared_on' => 'date',
            'paid_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
