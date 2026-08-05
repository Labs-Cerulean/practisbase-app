<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeDocumentTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'kind',
        'name',
        'title_default',
        'starter_key',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public const KIND_REPORT = 'report';

    public const KIND_CERTIFICATE = 'certificate';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
