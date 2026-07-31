<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyClient extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'billing_address',
        'vat_number',
        'registration_number',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CompanyInvoice::class);
    }
}
