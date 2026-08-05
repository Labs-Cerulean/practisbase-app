<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 
    'email', 
    'password',
    // Legal & Compliance
    'terms_accepted_at',
    'accepted_ip',
    'read_duration_seconds',
    // Professional Data
    'profession',
    'warrant_type',
    'warrant_number',
    'postnominals',
    // SaaS Tier & Referrals
    'tier',
    'referral_code',
    'referred_by_id',
    // Fiscal & Tax Data (NEW)
    'employment_type',
    'date_of_birth',
    'vat_status',
    'vat_number',
    'payment_instructions',
    'payment_methods',
    
    // Live Fiscal Report Settings (NEW)
    'tax_computation',
    'primary_salary',
    'max_ssc_paid',
    'estimated_expenses',
    'estimated_expenses_by_year',
    'car_business_use_percent',
    'home_office_percent',
    'clients_created_count',
    'logo_path',
    'clinic_phone',
    'clinic_address',
    'clinical_stamp_path',
    'company_books_enabled',
    'beta_invite_code_id',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'terms_accepted_at' => 'datetime',
            'date_of_birth' => 'date',
            'payment_methods' => 'array',
            'max_ssc_paid' => 'boolean',
            'primary_salary' => 'decimal:2',
            'estimated_expenses' => 'decimal:2',
            'estimated_expenses_by_year' => 'array',
            'car_business_use_percent' => 'decimal:2',
            'home_office_percent' => 'decimal:2',
            'clients_created_count' => 'integer',
            'company_books_enabled' => 'boolean',
        ];
    }

    /**
     * Relationships
     */
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Standard+ with ledger rows for the year → use ledger total.
     * Article 10: deduct expense net (ex-VAT); reclaimable VAT is handled on the VAT side.
     * Article 11 / exempt: deduct gross (amount + VAT).
     *
     * @return array{amount: float, source: string, ledger_total: float, ledger_ex_vat: float, input_vat: float, estimate: float, ex_vat: bool}
     */
    public function deductibleExpensesForYear(int $year): array
    {
        $byYear = $this->estimated_expenses_by_year ?? [];
        $yearKey = (string) $year;
        $estimate = array_key_exists($yearKey, $byYear)
            ? (float) $byYear[$yearKey]
            : (float) ($this->estimated_expenses ?? 0);

        $row = Expense::where('user_id', $this->id)
            ->whereYear('expense_date', $year)
            ->selectRaw('COALESCE(SUM(amount), 0) as ex_vat, COALESCE(SUM(vat_amount), 0) as input_vat')
            ->first();

        $ledgerExVat = (float) ($row->ex_vat ?? 0);
        $inputVat = (float) ($row->input_vat ?? 0);
        $useExVat = $this->vat_status === 'article_10';
        $ledgerTotal = $useExVat ? $ledgerExVat : ($ledgerExVat + $inputVat);

        if ($this->canAccessStandardTools() && ($ledgerExVat + $inputVat) > 0) {
            return [
                'amount' => $ledgerTotal,
                'source' => 'ledger',
                'ledger_total' => $ledgerTotal,
                'ledger_ex_vat' => $ledgerExVat,
                'input_vat' => $inputVat,
                'estimate' => $estimate,
                'ex_vat' => $useExVat,
            ];
        }

        return [
            'amount' => $estimate,
            'source' => 'estimate',
            'ledger_total' => $ledgerTotal,
            'ledger_ex_vat' => $ledgerExVat,
            'input_vat' => $inputVat,
            'estimate' => $estimate,
            'ex_vat' => $useExVat,
        ];
    }

    public function logoDataUri(): ?string
    {
        return $this->storedImageDataUri($this->logo_path);
    }

    public function clinicalStampDataUri(): ?string
    {
        return $this->storedImageDataUri($this->clinical_stamp_path);
    }

    private function storedImageDataUri(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $disk = \App\Support\TenantStorage::disk();

        if (! $disk->exists($path)) {
            return null;
        }

        $binary = $disk->get($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    /** Letters after the name on PDFs / stamps (e.g. MD, MRCS). */
    public function postnominalsLine(): ?string
    {
        $value = trim((string) ($this->postnominals ?? ''));

        return $value !== '' ? $value : null;
    }

    public function lifetimeClientCount(): int
    {
        return \App\Support\TierPolicy::lifetimeClientCount($this);
    }

    public function canAddClient(): bool
    {
        return \App\Support\TierPolicy::canAddClient($this);
    }

    public function canAccessReports(): bool
    {
        return \App\Support\TierPolicy::canAccessReports($this);
    }

    public function canAccessStandardTools(): bool
    {
        return \App\Support\TierPolicy::canAccessStandardTools($this);
    }

    public function hasStandardFinancial(): bool
    {
        return \App\Support\TierPolicy::hasStandardFinancial($this);
    }

    public function hasPracticeTools(): bool
    {
        return \App\Support\TierPolicy::hasPracticeTools($this);
    }

    public function hasUnlimitedClients(): bool
    {
        return \App\Support\TierPolicy::hasUnlimitedClients($this);
    }

    public function isPracticeOnly(): bool
    {
        return \App\Support\TierPolicy::isPracticeOnly($this);
    }

    public function isPaid(): bool
    {
        return \App\Support\TierPolicy::isPaid($this);
    }

    public function isPro(): bool
    {
        return \App\Support\TierPolicy::isPro($this);
    }

    public function canAccessProPackage(string $package): bool
    {
        return \App\Support\TierPolicy::canAccessProPackage($this, $package);
    }

    /** Internal Cerulean Labs Ltd desk — not a sellable product tier. */
    public function canAccessCompanyBooks(): bool
    {
        return (bool) ($this->company_books_enabled ?? false);
    }

    public function proPackage(): ?string
    {
        return \App\Support\TierPolicy::proPackage($this);
    }

    public function hasAcceptedTerms(): bool
    {
        return $this->terms_accepted_at !== null;
    }

    public function hasVatNumber(): bool
    {
        return filled($this->vat_number);
    }

    /**
     * Article 10 invoices (and any document charging 18% VAT) must show the supplier VAT ID.
     * Onboarding leaves the number optional so starters are not blocked.
     */
    public function missingVatNumberForArticle10Documents(): bool
    {
        return $this->vat_status === 'article_10' && ! $this->hasVatNumber();
    }

    public function isOnboardingComplete(): bool
    {
        return filled($this->profession) && filled($this->employment_type) && filled($this->tier);
    }

    public function onboardingRedirectPath(): string
    {
        if (! filled($this->profession)) {
            return '/onboarding/profession';
        }

        if (! filled($this->employment_type)) {
            return '/onboarding/financial';
        }

        return '/onboarding/plans';
    }

    public function freeClientCap(): int
    {
        return \App\Support\TierPolicy::FREE_CLIENT_LIFETIME_CAP;
    }

    public function clientUsageLabel(): string
    {
        $used = $this->lifetimeClientCount();

        if ($this->hasUnlimitedClients()) {
            return $used . ' lifetime clients created (unlimited on your plan)';
        }

        return $used . ' / ' . $this->freeClientCap() . ' lifetime clients used';
    }
}