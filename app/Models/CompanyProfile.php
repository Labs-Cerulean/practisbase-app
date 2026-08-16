<?php

namespace App\Models;

use App\Support\TenantStorage;
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
        'logo_path',
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

    public function logoDataUri(): ?string
    {
        if (! filled($this->logo_path)) {
            return null;
        }

        $disk = TenantStorage::disk();

        if (! $disk->exists($this->logo_path)) {
            return null;
        }

        $binary = $disk->get($this->logo_path);
        $ext = strtolower(pathinfo($this->logo_path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    public function logoDataUriForPdf(): ?string
    {
        return \App\Support\DomPdfImage::embeddable($this->logoDataUri());
    }
}
