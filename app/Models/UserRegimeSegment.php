<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'effective_from',
    'vat_status',
    'employment_type',
    'max_ssc_paid',
    'primary_salary',
    'tax_computation',
])]
class UserRegimeSegment extends Model
{
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'max_ssc_paid' => 'boolean',
            'primary_salary' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{vat_status: string, employment_type: string, max_ssc_paid: bool, primary_salary: float, tax_computation: string}
     */
    public function toRegimeArray(): array
    {
        return [
            'vat_status' => (string) $this->vat_status,
            'employment_type' => (string) $this->employment_type,
            'max_ssc_paid' => (bool) $this->max_ssc_paid,
            'primary_salary' => (float) $this->primary_salary,
            'tax_computation' => (string) ($this->tax_computation ?: 'single'),
        ];
    }
}
