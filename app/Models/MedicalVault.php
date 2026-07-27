<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalVault extends Model
{
    protected $fillable = [
        'user_id',
        'recovery_verifier',
        'acknowledged_at',
        'acknowledged_ip',
        'last_backup_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'last_backup_at' => 'datetime',
        ];
    }

    public static function activeForUser(int $userId): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();
    }

    public function isBackupOverdue(int $maxDays = 7): bool
    {
        if (! $this->last_backup_at) {
            return true;
        }

        return $this->last_backup_at->lt(now()->subDays($maxDays));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'vault_id');
    }
}
