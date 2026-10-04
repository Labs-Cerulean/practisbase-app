<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPersonalReceipt extends Model
{
    protected $fillable = [
        'user_id',
        'received_on',
        'amount',
        'description',
        'reference',
        'returned_on',
        'return_reference',
        'proof_path',
        'reversed_at',
        'reversal_note',
    ];

    protected function casts(): array
    {
        return [
            'received_on' => 'date',
            'returned_on' => 'date',
            'reversed_at' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }

    public function isReturned(): bool
    {
        return ! $this->isReversed() && $this->returned_on !== null;
    }

    public function isHolding(): bool
    {
        return ! $this->isReversed() && $this->returned_on === null;
    }
}
