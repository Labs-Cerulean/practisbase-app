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
            'max_ssc_paid' => 'boolean', // Ensures 1/0 from DB becomes true/false in PHP
            'primary_salary' => 'decimal:2',
            'estimated_expenses' => 'decimal:2',
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
}