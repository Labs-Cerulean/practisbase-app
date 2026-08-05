<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'feedback_id',
    'user_id',
    'body',
    'is_staff',
])]
class CommunityFeedbackMessage extends Model
{
    protected function casts(): array
    {
        return [
            'is_staff' => 'boolean',
        ];
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(CommunityFeedback::class, 'feedback_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
