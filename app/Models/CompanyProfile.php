<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyProfile extends Model
{
    protected $fillable = [
        'user_id',
        'legal_name',
        'registration_number',
        'registered_office',
        'financial_year_end_month',
        'financial_year_end_day',
        'first_period_start',
        'first_period_end',
        'vat_status',
        'vat_number',
        'vat_filing_frequency',
        'bank_name',
        'bank_iban',
        'share_capital_eur',
        'share_capital_received_at',
        'payment_instructions',
    ];

    protected function casts(): array
    {
        return [
            'first_period_start' => 'date',
            'first_period_end' => 'date',
            'share_capital_received_at' => 'date',
            'share_capital_eur' => 'decimal:2',
            'financial_year_end_month' => 'integer',
            'financial_year_end_day' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isArticle10(): bool
    {
        return ($this->vat_status ?? '') === 'article_10';
    }

    public function hasVatNumber(): bool
    {
        return filled($this->vat_number);
    }

    public function shareCapitalReceived(): bool
    {
        return $this->share_capital_received_at !== null;
    }
}
