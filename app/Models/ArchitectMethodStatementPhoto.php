<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchitectMethodStatementPhoto extends Model
{
    protected $fillable = [
        'user_id',
        'architect_method_statement_id',
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

    public function statement(): BelongsTo
    {
        return $this->belongsTo(ArchitectMethodStatement::class, 'architect_method_statement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
