<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
        'is_transfer',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'is_transfer' => 'boolean',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Client cash only — excludes internal bank/adjustment moves.
     */
    public function scopeClientCash($query)
    {
        return $query->where('is_transfer', false);
    }
}