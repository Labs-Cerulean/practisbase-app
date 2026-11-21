<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;

class EmailVerification
{
    public static function required(bool $schemaReady, bool $mustVerifyEmail, bool $verified): bool
    {
        return $schemaReady && $mustVerifyEmail && ! $verified;
    }

    /**
     * Decide from the row already loaded. When must_verify_email is on the
     * model, the column exists and no catalog lookup is required.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function requiredForAttributes(array $attributes): bool
    {
        if (! array_key_exists('must_verify_email', $attributes)) {
            return false;
        }

        return self::required(
            true,
            (bool) $attributes['must_verify_email'],
            ($attributes['email_verified_at'] ?? null) !== null,
        );
    }

    public static function nextPath(User $user): string
    {
        if ($user->mustVerifyEmailNow()) {
            return '/email/verify';
        }

        if (! $user->isOnboardingComplete()) {
            return $user->onboardingRedirectPath();
        }

        return $user->canAccessCompanyBooks() ? '/company' : '/dashboard';
    }

    /**
     * Assign the new address. New accounts that must confirm email are unverified again.
     */
    public static function applyEmailChange(User $user, string $newEmail): bool
    {
        $changed = strcasecmp((string) $user->email, $newEmail) !== 0;
        $user->email = $newEmail;

        if (! $changed || ! RegisterBillingSchema::ready()) {
            return false;
        }

        if (! (bool) ($user->getAttributes()['must_verify_email'] ?? false)) {
            return false;
        }

        $user->email_verified_at = null;

        return true;
    }

    public static function send(User $user): void
    {
        $user->notify(new VerifyEmailNotification);
    }
}
