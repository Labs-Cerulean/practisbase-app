<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyExpensePayment extends Model
{
    protected $fillable = [
        'user_id',
        'paid_on',
        'reference',
        'amount',
        'proof_path',
    ];

    protected function casts(): array
    {
        return [
            'paid_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CompanyExpense::class);
    }

    /**
     * Cash the company is paying back, using each expense's own cash total.
     *
     * @param  iterable<int, CompanyExpense>  $expenses
     */
    public static function cashTotal(iterable $expenses): float
    {
        $total = 0.0;
        foreach ($expenses as $expense) {
            $total += $expense->cashTotal();
        }

        return round($total, 2);
    }
}
