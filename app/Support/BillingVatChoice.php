<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class BillingVatChoice
{
    public static function normalize(?string $raw): string
    {
        $value = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $raw));

        if (preg_match('/^\d{8}$/', $value) === 1) {
            return 'MT'.$value;
        }

        return $value;
    }

    public static function remember(User $user, bool $include, ?string $raw): void
    {
        if (! RegisterBillingSchema::ensure()) {
            if ($include) {
                throw ValidationException::withMessages([
                    'billing_vat_number' => 'VAT details cannot be saved on this server yet.',
                ]);
            }

            return;
        }

        $vat = self::normalize($raw);
        if ($include && $vat === '') {
            throw ValidationException::withMessages([
                'billing_vat_number' => 'Enter the VAT number to show on the invoice and receipt.',
            ]);
        }

        if ($include && strlen($vat) > 32) {
            throw ValidationException::withMessages([
                'billing_vat_number' => 'That VAT number is too long.',
            ]);
        }

        $user->forceFill([
            'include_billing_vat' => $include,
            'billing_vat_number' => $include ? $vat : null,
        ])->save();
    }
}
