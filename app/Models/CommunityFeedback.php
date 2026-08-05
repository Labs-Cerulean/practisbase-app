<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'category',
    'subject',
    'status',
    'status_note',
    'staff_unread',
    'user_unread',
])]
class CommunityFeedback extends Model
{
    public const CATEGORIES = [
        'suggestion' => 'Suggestion',
        'bug' => 'Bug / problem',
        'question' => 'Question',
        'praise' => 'What works well',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'acknowledged' => 'Acknowledged',
        'in_progress' => 'In progress',
        'implemented' => 'Implemented',
        'deferred' => 'Deferred',
        'closed' => 'Closed',
    ];

    protected function casts(): array
    {
        return [
            'staff_unread' => 'boolean',
            'user_unread' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommunityFeedbackMessage::class, 'feedback_id')->orderBy('created_at');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusTone(): array
    {
        return match ($this->status) {
            'open' => ['bg' => '#eff6ff', 'fg' => '#1e40af', 'border' => '#bfdbfe'],
            'acknowledged' => ['bg' => '#f8fafc', 'fg' => '#334155', 'border' => '#cbd5e1'],
            'in_progress' => ['bg' => '#fffbeb', 'fg' => '#92400e', 'border' => '#fde68a'],
            'implemented' => ['bg' => '#ecfdf5', 'fg' => '#065f46', 'border' => '#a7f3d0'],
            'deferred' => ['bg' => '#faf5ff', 'fg' => '#6b21a8', 'border' => '#e9d5ff'],
            'closed' => ['bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#cbd5e1'],
            default => ['bg' => '#f8fafc', 'fg' => '#334155', 'border' => '#cbd5e1'],
        };
    }

    public function isOpenForReply(): bool
    {
        return ! in_array($this->status, ['closed'], true);
    }
}
