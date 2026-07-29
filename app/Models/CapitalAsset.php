<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'expense_id',
    'asset_class',
    'description',
    'purchase_date',
    'cost_basis',
    'cost_ex_vat',
    'vat_amount',
    'business_use_percent',
    'annual_rate',
])]
class CapitalAsset extends Model
{
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'cost_basis' => 'decimal:2',
            'cost_ex_vat' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'business_use_percent' => 'decimal:2',
            'annual_rate' => 'decimal:4',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Straight-line wear & tear for a calendar year (0 after fully written off).
     */
    public function allowanceForYear(int $year): float
    {
        $purchaseYear = (int) $this->purchase_date->format('Y');
        if ($year < $purchaseYear) {
            return 0.0;
        }

        $rate = (float) $this->annual_rate;
        if ($rate <= 0) {
            return 0.0;
        }

        $businessShare = max(0.0, min(100.0, (float) $this->business_use_percent)) / 100.0;
        $claimableTotal = (float) $this->cost_basis * $businessShare;
        $annual = round((float) $this->cost_basis * $rate * $businessShare, 2);
        if ($annual <= 0 || $claimableTotal <= 0) {
            return 0.0;
        }

        $yearsSincePurchase = $year - $purchaseYear;
        $alreadyClaimed = round($annual * $yearsSincePurchase, 2);
        if ($alreadyClaimed >= $claimableTotal) {
            return 0.0;
        }

        return round(min($annual, $claimableTotal - $alreadyClaimed), 2);
    }
}
