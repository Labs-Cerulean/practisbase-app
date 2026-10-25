<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\DomPdfImage;
use App\Support\EmailVerification;
use App\Support\ProfessionStart;
use App\Support\RegisterBillingSchema;
use App\Support\TenantStorage;
use App\Support\TierPolicy;
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
    // SaaS Tier & Referrals / Promo wallet
    'tier',
    'referral_code',
    'referred_by_id',
    'credit_balance',
    'trial_ends_at',
    'applied_promotion_id',
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
    'clinical_note_template',
    'company_books_enabled',
    'exclude_from_kpis',
    'exclude_from_mrr',
    'beta_invite_code_id',
    'must_verify_email',
    'stripe_customer_id',
    'stripe_subscription_id',
    'subscription_status',
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
            'exclude_from_kpis' => 'boolean',
            'exclude_from_mrr' => 'boolean',
            'credit_balance' => 'decimal:2',
            'trial_ends_at' => 'datetime',
            'last_data_backup_at' => 'datetime',
            'must_verify_email' => 'boolean',
        ];
    }

    /** Weekly personal data backup overdue after 7 days (or never downloaded). */
    public function isDataBackupOverdue(int $maxDays = 7): bool
    {
        if (! $this->last_data_backup_at) {
            return true;
        }

        return $this->last_data_backup_at->lt(now()->subDays($maxDays));
    }

    /**
     * Relationships
     */
    public function appliedPromotion()
    {
        return $this->belongsTo(Promotion::class, 'applied_promotion_id');
    }

    public function betaInviteCode()
    {
        return $this->belongsTo(BetaInviteCode::class, 'beta_invite_code_id');
    }

    public function referralsMade()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referralReceived()
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by_id');
    }

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

    public function documentStamps()
    {
        return $this->hasMany(DocumentStamp::class);
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

    /**
     * Stamp image for clinical / professional PDFs.
     * Prefer the default Document Stamp (generator); fall back to a legacy branding upload.
     */
    public function clinicalStampDataUri(): ?string
    {
        $stamp = $this->documentStamps()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if ($stamp) {
            $composed = $stamp->composedDataUri();
            if ($composed) {
                return $composed;
            }
        }

        return $this->storedImageDataUri($this->clinical_stamp_path);
    }

    /** DomPDF-safe logo (omits/converts when needed). */
    public function logoDataUriForPdf(): ?string
    {
        return DomPdfImage::embeddable($this->logoDataUri());
    }

    /** DomPDF-safe clinical / professional stamp. */
    public function clinicalStampDataUriForPdf(): ?string
    {
        return DomPdfImage::embeddable($this->clinicalStampDataUri());
    }

    public function defaultDocumentStamp(): ?DocumentStamp
    {
        return $this->documentStamps()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    public function hasDocumentStamp(): bool
    {
        return $this->documentStamps()->exists();
    }

    private function storedImageDataUri(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $disk = TenantStorage::disk();

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

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    /** Letters after the name on PDFs / stamps (e.g. MD, MRCS). */
    public function postnominalsLine(): ?string
    {
        $value = trim((string) ($this->postnominals ?? ''));

        return $value !== '' ? $value : null;
    }

    public function lifetimeClientCount(): int
    {
        return TierPolicy::lifetimeClientCount($this);
    }

    public function canAddClient(): bool
    {
        return TierPolicy::canAddClient($this);
    }

    public function canAccessReports(): bool
    {
        return TierPolicy::canAccessReports($this);
    }

    public function canAccessDocumentStamper(): bool
    {
        return TierPolicy::canAccessDocumentStamper($this);
    }

    public function canAccessStandardTools(): bool
    {
        return TierPolicy::canAccessStandardTools($this);
    }

    public function hasStandardFinancial(): bool
    {
        return TierPolicy::hasStandardFinancial($this);
    }

    public function hasPracticeTools(): bool
    {
        return TierPolicy::hasPracticeTools($this);
    }

    public function hasUnlimitedClients(): bool
    {
        return TierPolicy::hasUnlimitedClients($this);
    }

    public function isPracticeOnly(): bool
    {
        return TierPolicy::isPracticeOnly($this);
    }

    public function isPaid(): bool
    {
        return TierPolicy::isPaid($this);
    }

    public function isPro(): bool
    {
        return TierPolicy::isPro($this);
    }

    public function canAccessProPackage(string $package): bool
    {
        return TierPolicy::canAccessProPackage($this, $package);
    }

    /** Internal Cerulean Labs Ltd desk — not a sellable product tier. */
    public function canAccessCompanyBooks(): bool
    {
        return (bool) ($this->company_books_enabled ?? false);
    }

    public function proPackage(): ?string
    {
        return TierPolicy::proPackage($this);
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
        return ProfessionStart::onboardingComplete($this->profession, $this->tier);
    }

    /**
     * Access-code plans never collect a card. Existing rows stay exempt from
     * email confirmation because must_verify_email defaults to false.
     */
    public function canActivatePaidTierWithoutStripe(): bool
    {
        return (bool) $this->beta_invite_code_id;
    }

    public function mustVerifyEmailNow(): bool
    {
        return EmailVerification::required(
            RegisterBillingSchema::ensure(),
            (bool) ($this->getAttributes()['must_verify_email'] ?? false),
            $this->email_verified_at !== null,
        );
    }

    public function onboardingRedirectPath(): string
    {
        return ProfessionStart::redirectPath($this->profession, $this->tier);
    }

    public function freeClientCap(): int
    {
        return TierPolicy::FREE_CLIENT_LIFETIME_CAP;
    }

    public function clientUsageLabel(): string
    {
        $used = $this->lifetimeClientCount();

        if ($this->hasUnlimitedClients()) {
            return $used.' lifetime clients created (unlimited on your plan)';
        }

        return $used.' / '.$this->freeClientCap().' lifetime clients used';
    }
}
