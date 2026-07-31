<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPayment extends Model
{
    protected $fillable = [
        'user_id',
        'company_invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CompanyInvoice::class, 'company_invoice_id');
    }
}
