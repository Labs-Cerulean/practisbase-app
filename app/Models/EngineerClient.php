<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EngineerClient extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'id_card',
        'email',
        'phone',
        'address',
        'locality',
        'billing_client_id',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function billingClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'billing_client_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(EngineerProject::class, 'engineer_client_id');
    }

    public function displayAddress(): string
    {
        $parts = array_filter([
            trim((string) $this->address),
            trim((string) $this->locality),
        ]);

        return implode(', ', $parts);
    }
}
