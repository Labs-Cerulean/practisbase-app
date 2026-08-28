<?php

namespace App\Models;

use App\Support\EstateHubBilling;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'package_sections',
        'agreed_rate_os',
        'agreed_rate_plant',
        'agreed_rate_sales',
        'start_date',
        'sla_path',
        'sla_original_name',
        'auto_email',
        'auto_reminders',
        'reminder_include_statement',
    ];

    protected function casts(): array
    {
        return [
            'day_of_month' => 'integer',
            'due_days' => 'integer',
            'next_issue_on' => 'date',
            'last_generated_on' => 'date',
            'start_date' => 'date',
            'items' => 'array',
            'package_sections' => 'array',
            'is_active' => 'boolean',
            'auto_email' => 'boolean',
            'auto_reminders' => 'boolean',
            'reminder_include_statement' => 'boolean',
            'agreed_rate_os' => 'decimal:2',
            'agreed_rate_plant' => 'decimal:2',
            'agreed_rate_sales' => 'decimal:2',
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

    public function documents(): HasMany
    {
        return $this->hasMany(CompanyInvoice::class, 'company_recurring_invoice_id');
    }

    public function hasSla(): bool
    {
        return filled($this->sla_path);
    }

    public function packageLabel(): string
    {
        return EstateHubBilling::packageLabel($this->package_sections ?? []);
    }

    public function monthlySubtotal(): float
    {
        return EstateHubBilling::itemsSubtotal($this->items ?? []);
    }
}
