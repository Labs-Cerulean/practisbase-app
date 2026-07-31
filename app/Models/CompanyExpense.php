<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyExpense extends Model
{
    public const CATEGORIES = [
        'software' => 'Software & subscriptions',
        'professional' => 'Professional fees',
        'marketing' => 'Marketing & websites',
        'office' => 'Office & admin',
        'bank' => 'Bank & formation',
        'travel' => 'Travel',
        'general' => 'General / other',
    ];

    protected $fillable = [
        'user_id',
        'expense_date',
        'category',
        'description',
        'amount',
        'vat_amount',
        'funded_by',
        'director_refunded_at',
        'refund_reference',
        'receipt_path',
        'is_pre_incorporation',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'director_refunded_at' => 'date',
            'amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'is_pre_incorporation' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function totalWithVat(): float
    {
        return (float) $this->amount + (float) $this->vat_amount;
    }

    public function isOwedToDirector(): bool
    {
        return $this->funded_by === 'director' && $this->director_refunded_at === null;
    }
}
