<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id',
    'type',
    'name',
    'email',
    'phone',
    'billing_address',
    'profile_data'
])]
class Client extends Model
{
    use HasFactory;

    public const BILLING_PROFILE_KEYS = [
        'vat_number',
        'registration_number',
        'contact_person',
        'id_card_number',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'profile_data' => 'array',
        ];
    }

    public static function billingProfileOnly(array $profileData): array
    {
        $clean = [];
        foreach (self::BILLING_PROFILE_KEYS as $key) {
            if (array_key_exists($key, $profileData) && $profileData[$key] !== null && $profileData[$key] !== '') {
                $clean[$key] = $profileData[$key];
            }
        }

        return $clean;
    }

    /**
     * Relationship: A client belongs to a User (The Professional)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A client has many Invoices/RFPs
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}