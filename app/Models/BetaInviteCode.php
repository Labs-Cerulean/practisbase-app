<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BetaInviteCode extends Model
{
    public const PACKAGES = [
        'eng' => 'Engineer (Pro)',
        'arch' => 'Architect / Perit (Pro)',
        'med' => 'Medical Professional (Pro)',
    ];

    public const PACKAGE_PROFESSION = [
        'eng' => 'Engineer',
        'arch' => 'Architect / Perit',
        'med' => 'Medical Professional',
    ];

    public const PACKAGE_TIER = [
        'eng' => 'pro-eng',
        'arch' => 'pro-arch',
        'med' => 'pro-med',
    ];

    protected $fillable = [
        'code',
        'pro_package',
        'label',
        'max_uses',
        'uses_count',
        'expires_at',
        'revoked_at',
        'created_by_user_id',
        'redeemed_by_user_id',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'max_uses' => 'integer',
            'uses_count' => 'integer',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by_user_id');
    }

    public function packageLabel(): string
    {
        return self::PACKAGES[$this->pro_package] ?? $this->pro_package;
    }

    public function profession(): string
    {
        return self::PACKAGE_PROFESSION[$this->pro_package] ?? 'Other';
    }

    public function tier(): string
    {
        return self::PACKAGE_TIER[$this->pro_package] ?? 'free';
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->uses_count >= $this->max_uses;
    }

    public function isRedeemable(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired() && ! $this->isExhausted();
    }

    public function statusLabel(): string
    {
        if ($this->isRevoked()) {
            return 'Revoked';
        }
        if ($this->isExpired()) {
            return 'Expired';
        }
        if ($this->isExhausted()) {
            return 'Used';
        }

        return 'Available';
    }

    public static function normalizeCode(?string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $code) ?? '');
    }

    public static function generateCode(string $proPackage): string
    {
        $prefix = strtoupper($proPackage);

        do {
            $code = $prefix.'-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Atomically redeem a code for a new user. Returns the invite or null if not redeemable.
     */
    public static function redeemForUser(string $rawCode, User $user): ?self
    {
        $normalized = self::normalizeCode($rawCode);
        if ($normalized === '') {
            return null;
        }

        return DB::transaction(function () use ($normalized, $user) {
            $invite = self::query()
                ->where('code', $normalized)
                ->lockForUpdate()
                ->first();

            if (! $invite || ! $invite->isRedeemable()) {
                return null;
            }

            $invite->uses_count = $invite->uses_count + 1;
            if ($invite->redeemed_by_user_id === null) {
                $invite->redeemed_by_user_id = $user->id;
                $invite->redeemed_at = Carbon::now();
            }
            $invite->save();

            return $invite;
        });
    }
}
