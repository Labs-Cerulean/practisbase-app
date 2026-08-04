<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngineerReportPhoto extends Model
{
    protected $fillable = [
        'user_id',
        'engineer_report_id',
        'file_path',
        'caption',
        'linked_row_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(EngineerReport::class, 'engineer_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
