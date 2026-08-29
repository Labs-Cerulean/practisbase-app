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

    /** Standard Maltese VAT rate used for reverse-charge self-assessment. */
    public const REVERSE_CHARGE_RATE = 0.18;

    protected $fillable = [
        'user_id',
        'expense_date',
        'category',
        'description',
        'amount',
        'vat_amount',
        'is_reverse_charge',
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
            'is_reverse_charge' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cash paid to the supplier (reverse charge: net only — no VAT remitted to supplier).
     */
    public function cashTotal(): float
    {
        if ($this->is_reverse_charge) {
            return round((float) $this->amount, 2);
        }

        return round((float) $this->amount + (float) $this->vat_amount, 2);
    }

    /**
     * @deprecated Prefer cashTotal(); kept for existing call sites.
     */
    public function totalWithVat(): float
    {
        return $this->cashTotal();
    }

    /** Self-assessed output/input VAT on reverse-charge purchases (else 0). */
    public function reverseChargeVat(): float
    {
        if (! $this->is_reverse_charge) {
            return 0.0;
        }

        return round((float) $this->vat_amount, 2);
    }

    public static function reverseChargeVatOn(float $net): float
    {
        return round(max(0, $net) * self::REVERSE_CHARGE_RATE, 2);
    }

    public function isOwedToDirector(): bool
    {
        return $this->funded_by === 'director' && $this->director_refunded_at === null;
    }
}
