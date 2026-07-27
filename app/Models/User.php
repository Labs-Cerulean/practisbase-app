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
    'clients_created_count',
    'logo_path',
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
            'clients_created_count' => 'integer',
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
     * Otherwise fall back to estimated_expenses (Free / empty ledger).
     *
     * @return array{amount: float, source: string, ledger_total: float, estimate: float}
     */
    public function deductibleExpensesForYear(int $year): array
    {
        $estimate = (float) ($this->estimated_expenses ?? 0);
        $ledgerTotal = (float) Expense::where('user_id', $this->id)
            ->whereYear('expense_date', $year)
            ->selectRaw('COALESCE(SUM(amount + vat_amount), 0) as total')
            ->value('total');

        if ($this->canAccessStandardTools() && $ledgerTotal > 0) {
            return [
                'amount' => $ledgerTotal,
                'source' => 'ledger',
                'ledger_total' => $ledgerTotal,
                'estimate' => $estimate,
            ];
        }

        return [
            'amount' => $estimate,
            'source' => 'estimate',
            'ledger_total' => $ledgerTotal,
            'estimate' => $estimate,
        ];
    }

    public function logoDataUri(): ?string
    {
        if (! filled($this->logo_path)) {
            return null;
        }

        $disk = \App\Support\TenantStorage::disk();

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

        if ($this->isPaid()) {
            return $used . ' lifetime clients created (unlimited on your plan)';
        }

        return $used . ' / ' . $this->freeClientCap() . ' lifetime clients used';
    }
}