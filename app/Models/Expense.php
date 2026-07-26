<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id',
    'expense_date',
    'category',
    'description',
    'amount',
    'vat_amount',
    'receipt_path',
])]
class Expense extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'office' => 'Office & admin',
        'travel' => 'Travel & transport',
        'equipment' => 'Equipment & tools',
        'professional' => 'Professional fees',
        'software' => 'Software & subscriptions',
        'marketing' => 'Marketing',
        'premises' => 'Premises & utilities',
        'general' => 'General / other',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function totalWithVat(): float
    {
        return (float) $this->amount + (float) $this->vat_amount;
    }
}
