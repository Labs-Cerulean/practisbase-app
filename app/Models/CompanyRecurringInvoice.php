<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyRecurringInvoice extends Model
{
    protected $fillable = [
        'user_id',
        'company_client_id',
        'title',
        'day_of_month',
        'next_issue_on',
        'due_days',
        'items',
        'notes',
        'is_active',
        'last_generated_on',
        'last_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'day_of_month' => 'integer',
            'due_days' => 'integer',
            'next_issue_on' => 'date',
            'last_generated_on' => 'date',
            'items' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(CompanyClient::class, 'company_client_id');
    }

    public function lastInvoice(): BelongsTo
    {
        return $this->belongsTo(CompanyInvoice::class, 'last_invoice_id');
    }
}
