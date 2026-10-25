<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

class StripeBilling
{
    public static function configured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    public static function priceIdForTier(string $tier): ?string
    {
        $key = match (true) {
            $tier === TierPolicy::TIER_STANDARD => 'price_standard',
            str_starts_with($tier, 'practice-') => 'price_practice',
            str_starts_with($tier, 'pro-') => 'price_pro',
            default => null,
        };

        if ($key === null) {
            return null;
        }

        $id = config('services.stripe.'.$key);

        return is_string($id) && $id !== '' ? $id : null;
    }

    public static function checkoutUrl(User $user, string $tier, string $returnTo): string
    {
        self::assertReady();

        if (StripeCheckoutPlan::exVatForTier($tier) === null) {
            throw ValidationException::withMessages([
                'tier' => 'Choose a paid plan to continue to card checkout.',
            ]);
        }

        $trialDays = StripeCheckoutPlan::trialPeriodDays(
            filled($user->stripe_subscription_id),
            $user->trial_ends_at?->getTimestamp(),
        );

        $payload = StripeCheckoutPlan::sessionPayload([
            'userId' => $user->id,
            'email' => (string) $user->email,
            'tier' => $tier,
            'label' => TierPolicy::label($tier),
            'trialDays' => $trialDays,
            'successUrl' => url('/onboarding/billing/success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancelUrl' => $returnTo === 'settings' ? url('/settings').'#plan' : url('/onboarding/plans'),
            'priceId' => self::priceIdForTier($tier),
            'customerId' => $user->stripe_customer_id,
            'returnTo' => $returnTo,
        ]);

        try {
            $session = self::client()->checkout->sessions->create($payload);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'tier' => 'Card checkout could not be started. Try again in a moment.',
            ]);
        }

        $url = (string) data_get($session, 'url', '');
        if ($url === '') {
            throw ValidationException::withMessages([
                'tier' => 'Card checkout could not be started. Try again in a moment.',
            ]);
        }

        return $url;
    }

    public static function completeCheckout(User $user, string $sessionId): string
    {
        self::assertReady();

        try {
            $session = self::client()->checkout->sessions->retrieve($sessionId, []);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'tier' => 'That checkout session could not be confirmed.',
            ]);
        }

        $owner = (string) data_get($session, 'metadata.user_id', data_get($session, 'client_reference_id', ''));
        if ($owner !== (string) $user->id) {
            abort(403);
        }

        self::applyCheckoutSession($session);

        return (string) data_get($session, 'metadata.billing_return', 'onboarding');
    }

    public static function handleEvent(object $event): void
    {
        $type = (string) data_get($event, 'type', '');
        $object = data_get($event, 'data.object');
        if (! is_object($object)) {
            return;
        }

        if ($type === 'checkout.session.completed') {
            self::applyCheckoutSession($object);

            return;
        }

        if (in_array($type, ['customer.subscription.updated', 'customer.subscription.deleted'], true)) {
            self::applySubscriptionObject($object);
        }
    }

    /**
     * Cancel a live card subscription before a local downgrade to Free.
     * Accounts with no Stripe subscription return true.
     */
    public static function releasePaidSubscription(User $user): bool
    {
        if (! RegisterBillingSchema::ready() || ! filled($user->stripe_subscription_id)) {
            return true;
        }

        $status = (string) $user->subscription_status;
        if (in_array($status, ['canceled', 'incomplete_expired', 'unpaid'], true)) {
            return true;
        }

        if (! self::configured()) {
            return false;
        }

        if (! self::cancelRemote((string) $user->stripe_subscription_id)) {
            return false;
        }

        $user->forceFill([
            'subscription_status' => 'canceled',
            'trial_ends_at' => null,
        ])->save();

        return true;
    }

    private static function assertReady(): void
    {
        if (! RegisterBillingSchema::ensure()) {
            throw ValidationException::withMessages([
                'tier' => 'Card billing is not available on this server yet.',
            ]);
        }

        if (! self::configured()) {
            throw ValidationException::withMessages([
                'tier' => 'Card billing is not configured on this server.',
            ]);
        }
    }

    private static function applyCheckoutSession(object $session): void
    {
        if ((string) data_get($session, 'mode') !== 'subscription') {
            return;
        }

        if ((string) data_get($session, 'status') !== 'complete') {
            return;
        }

        $subscriptionId = data_get($session, 'subscription');
        if (is_object($subscriptionId)) {
            $subscriptionId = data_get($subscriptionId, 'id');
        }
        if (! is_string($subscriptionId) || $subscriptionId === '') {
            return;
        }

        $subscription = self::client()->subscriptions->retrieve($subscriptionId, []);
        self::applySubscriptionObject($subscription, (string) data_get($session, 'metadata.tier', ''));
    }

    private static function applySubscriptionObject(object $subscription, string $tierOverride = ''): void
    {
        $status = strtolower((string) data_get($subscription, 'status', ''));
        if (! in_array($status, ['active', 'trialing', 'past_due', 'canceled', 'unpaid', 'incomplete_expired'], true)) {
            return;
        }

        $subscriptionId = (string) data_get($subscription, 'id', '');
        $customerId = data_get($subscription, 'customer');
        if (is_object($customerId)) {
            $customerId = data_get($customerId, 'id');
        }

        $tier = (string) data_get($subscription, 'metadata.tier', '');
        if ($tier === '') {
            $tier = $tierOverride;
        }

        $userId = (int) data_get($subscription, 'metadata.user_id', 0);
        $user = $userId > 0 ? User::query()->whereKey($userId)->first() : null;
        if (! $user && is_string($customerId) && $customerId !== '' && RegisterBillingSchema::ready()) {
            $user = User::query()->where('stripe_customer_id', $customerId)->first();
        }
        if (! $user || $user->beta_invite_code_id) {
            return;
        }

        if (! RegisterBillingSchema::ensure()) {
            throw new \RuntimeException('Billing columns are missing.');
        }

        $known = (string) ($user->stripe_subscription_id ?? '');
        $closing = in_array($status, ['canceled', 'unpaid', 'incomplete_expired'], true);
        if ($known !== '' && $subscriptionId !== '' && $known !== $subscriptionId && $closing) {
            return;
        }

        $granting = in_array($status, ['active', 'trialing', 'past_due'], true);
        if ($granting && $tier !== '' && ! in_array($tier, TierPolicy::allowedTiersForProfession($user->profession), true)) {
            if ($subscriptionId !== '') {
                self::cancelRemote($subscriptionId);
            }

            return;
        }

        if ($granting && $known !== '' && $subscriptionId !== '' && $known !== $subscriptionId) {
            self::cancelRemote($known);
        }

        $next = StripeCheckoutPlan::tierAfterBillingEvent($status, $tier !== '' ? $tier : null, false, (string) $user->tier);
        $updates = [
            'tier' => $next,
            'subscription_status' => substr($status, 0, 32),
        ];
        if (is_string($customerId) && $customerId !== '') {
            $updates['stripe_customer_id'] = $customerId;
        }
        if ($subscriptionId !== '') {
            $updates['stripe_subscription_id'] = $subscriptionId;
        }

        $trialEnd = data_get($subscription, 'trial_end');
        if ($status === 'trialing' && is_numeric($trialEnd)) {
            $updates['trial_ends_at'] = Carbon::createFromTimestamp((int) $trialEnd);
        } elseif (in_array($status, ['active', 'canceled', 'unpaid', 'incomplete_expired'], true)) {
            $updates['trial_ends_at'] = null;
        }

        $user->forceFill($updates)->save();
    }

    private static function cancelRemote(string $subscriptionId): bool
    {
        if ($subscriptionId === '' || ! self::configured()) {
            return false;
        }

        try {
            self::client()->subscriptions->cancel($subscriptionId, []);
        } catch (InvalidRequestException $e) {
            $code = (string) $e->getStripeCode();
            $message = strtolower($e->getMessage());
            if ($code === 'resource_missing' || str_contains($message, 'canceled') || str_contains($message, 'cancelled')) {
                return true;
            }
            report($e);

            return false;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    private static function client(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }
}
