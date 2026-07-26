<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchitectProject extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'reference_code',
        'phase',
        'status',
        'notes',
    ];

    public const PHASES = [
        'concept' => 'Concept',
        'permit' => 'Permit / PA',
        'construction' => 'Construction',
        'completion' => 'Completion',
        'bca' => 'BCA / Method Statement',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
