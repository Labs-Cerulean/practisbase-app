<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class CompanyExpensePayment extends Model
{
    public const KIND_DIRECTOR = 'director_refund';

    public const KIND_SUPPLIER = 'supplier_payment';

    protected $fillable = [
        'user_id',
        'paid_on',
        'reference',
        'amount',
        'proof_path',
        'kind',
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

    /**
     * Director refunds may mix suppliers. A supplier payment must be one supplier.
     *
     * @param  iterable<int, CompanyExpense>  $expenses
     */
    public static function kindFor(iterable $expenses): string
    {
        $director = 0;
        $supplier = 0;
        $supplierIds = [];

        foreach ($expenses as $expense) {
            if ($expense->isOwedToDirector()) {
                $director++;
                continue;
            }
            if ($expense->isAwaitingSupplierPayment()) {
                $supplier++;
                $supplierIds[(int) ($expense->company_supplier_id ?? 0)] = true;
                continue;
            }

            throw new InvalidArgumentException($expense->description.' is not waiting for a payment.');
        }

        if ($director > 0 && $supplier > 0) {
            throw new InvalidArgumentException('Record a director refund and a supplier payment separately.');
        }

        if ($supplier > 0) {
            if (count($supplierIds) !== 1 || isset($supplierIds[0])) {
                throw new InvalidArgumentException('Pay one supplier at a time, and keep a supplier on every invoice.');
            }

            return self::KIND_SUPPLIER;
        }

        if ($director === 0) {
            throw new InvalidArgumentException('Tick at least one invoice.');
        }

        return self::KIND_DIRECTOR;
    }
}
