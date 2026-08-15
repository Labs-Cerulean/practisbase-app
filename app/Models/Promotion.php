<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Promotion extends Model
{
    public const TYPE_FREE_MONTHS = 'free_months';

    public const TYPE_PERCENTAGE = 'percentage_discount';

    public const TYPE_FIXED = 'fixed_discount';

    public const TYPES = [
        self::TYPE_FREE_MONTHS => 'Free months',
        self::TYPE_PERCENTAGE => 'Percentage discount',
        self::TYPE_FIXED => 'Fixed discount (€)',
    ];

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_uses',
        'current_uses',
        'expires_at',
        'is_active',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'max_uses' => 'integer',
            'current_uses' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public static function normalizeCode(?string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $code) ?? '');
    }

    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function valueSummary(): string
    {
        return match ($this->type) {
            self::TYPE_FREE_MONTHS => ((int) $this->value).' free month(s)',
            self::TYPE_PERCENTAGE => rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'% off',
            self::TYPE_FIXED => '€'.number_format((float) $this->value, 2).' off',
            default => (string) $this->value,
        };
    }

    public function remainingUses(): ?int
    {
        if ($this->max_uses === null) {
            return null;
        }

        return max(0, $this->max_uses - $this->current_uses);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'applied_promotion_id');
    }

    public static function generateCode(string $prefix = 'PROMO'): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $prefix) ?: 'PROMO');

        do {
            $code = $prefix.'-'.strtoupper(Str::random(6));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }
}
