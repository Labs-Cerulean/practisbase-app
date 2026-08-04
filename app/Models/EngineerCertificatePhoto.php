<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngineerCertificatePhoto extends Model
{
    protected $fillable = [
        'user_id',
        'engineer_certificate_id',
        'file_path',
        'caption',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(EngineerCertificate::class, 'engineer_certificate_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
