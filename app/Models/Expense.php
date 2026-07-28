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
    'business_use_percent',
    'receipt_path',
])]
class Expense extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'office' => 'Office & admin',
        'travel' => 'Travel (not fuel)',
        'professional' => 'Professional fees',
        'software' => 'Software & subscriptions',
        'marketing' => 'Marketing',
        'premises' => 'Commercial premises & utilities',
        'general' => 'General / other',
        'laptop' => 'Laptop / computer / phone / tablet',
        'equipment' => 'Practice equipment / instruments',
        'car' => 'Car / van (practice)',
        'fuel' => 'Fuel',
        'wfh_electricity' => 'Working from home — Electricity',
        'wfh_internet' => 'Working from home — Internet',
        'wfh_water' => 'Working from home — Water',
        'wfh_heating' => 'Working from home — Heating / cooling',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'business_use_percent' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function capitalAsset()
    {
        return $this->hasOne(CapitalAsset::class);
    }

    public function totalWithVat(): float
    {
        return (float) $this->amount + (float) $this->vat_amount;
    }
}
