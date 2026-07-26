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
}