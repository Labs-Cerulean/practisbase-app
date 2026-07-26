<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxPayment extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'payment_type',
        'amount',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'year' => 'integer',
        ];
    }
}
