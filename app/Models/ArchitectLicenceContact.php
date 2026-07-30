<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchitectLicenceContact extends Model
{
    protected $fillable = [
        'user_id',
        'licence_type',
        'licence_number',
        'full_name',
        'company_name',
        'mobile',
        'locality',
        'source',
        'notes',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
