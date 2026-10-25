<?php

namespace App\Support;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

class StripeBilling
{
    public static function configured(): bool
    {
        return StripeKeyMode::allowed(
            (string) config('app.env'),
            Deployment::isStaging(),
            config('services.stripe.secret'),
        );
    }

    public static function sandboxLinked(): bool
    {
        return Deployment::isStaging()
            && StripeKeyMode::mode(config('services.stripe.secret')) === 'test';
    }

    public static function acceptsLivemode(bool $livemode): bool
    {
        return StripeKeyMode::acceptsLivemode(
            (string) config('app.env'),
            Deployment::isStaging(),
            $livemode,
        );
    }

    public static function livemodeIgnoreMessage(): string
    {
        return StripeKeyMode::ignoreMessage((string) config('app.env'), Deployment::isStaging());
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

        try {
            $thisCustomer = self::ensureCustomer($user);
            $payload = StripeCheckoutPlan::sessionPayload([
                'userId' => $user->id,
                'email' => (string) $user->email,
                'tier' => $tier,
                'label' => TierPolicy::label($tier),
                'trialDays' => $trialDays,
                'successUrl' => url('/onboarding/billing/success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancelUrl' => $returnTo === 'settings' ? url('/settings').'#plan' : url('/onboarding/plans'),
                'priceId' => self::priceIdForTier($tier),
                'customerId' => $thisCustomer,
                'returnTo' => $returnTo,
                'includeVat' => (bool) $user->include_billing_vat,
            ]);
            $session = self::client()->checkout->sessions->create($payload);
        } catch (ValidationException $e) {
            throw $e;
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
        if (! self::acceptsLivemode((bool) data_get($event, 'livemode', false))) {
            return;
        }

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

            return;
        }

        if (in_array($type, ['invoice.created', 'invoice.finalized', 'invoice.paid'], true)) {
            try {
                self::brandStripeInvoice($object);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($type === 'invoice.paid') {
            CeruleanSubscriptionBooks::recordPaidInvoice($object);
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

        $secret = config('services.stripe.secret');
        if (! StripeKeyMode::allowed((string) config('app.env'), Deployment::isStaging(), $secret)) {
            throw ValidationException::withMessages([
                'tier' => StripeKeyMode::refusal((string) config('app.env'), Deployment::isStaging(), $secret),
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

    public static function syncSavedVat(User $user): void
    {
        if (! self::configured() || ! filled($user->stripe_customer_id) || ! RegisterBillingSchema::ready()) {
            return;
        }

        self::syncCustomerVat($user);
    }

    private static function ensureCustomer(User $user): string
    {
        if (! filled($user->stripe_customer_id)) {
            $customer = self::client()->customers->create([
                'email' => (string) $user->email,
                'name' => (string) $user->name,
                'metadata' => [
                    'user_id' => (string) $user->id,
                ],
            ]);
            $user->forceFill([
                'stripe_customer_id' => (string) data_get($customer, 'id'),
            ])->save();
        }

        self::syncCustomerVat($user);

        return (string) $user->stripe_customer_id;
    }

    private static function syncCustomerVat(User $user): void
    {
        $customerId = (string) $user->stripe_customer_id;
        if ($customerId === '') {
            return;
        }

        $wanted = $user->include_billing_vat
            ? BillingVatChoice::normalize((string) $user->billing_vat_number)
            : '';

        try {
            $existing = self::client()->customers->allTaxIds($customerId, ['limit' => 10]);
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        $already = false;
        foreach (data_get($existing, 'data', []) as $tax) {
            $value = BillingVatChoice::normalize((string) data_get($tax, 'value', ''));
            $id = (string) data_get($tax, 'id', '');
            if ($wanted !== '' && $value === $wanted) {
                $already = true;

                continue;
            }
            if ($id !== '' && strtolower((string) data_get($tax, 'type', '')) === 'eu_vat') {
                try {
                    self::client()->customers->deleteTaxId($customerId, $id, []);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        if ($wanted === '' || $already) {
            return;
        }

        try {
            self::client()->customers->createTaxId($customerId, [
                'type' => 'eu_vat',
                'value' => $wanted,
            ]);
        } catch (InvalidRequestException $e) {
            throw ValidationException::withMessages([
                'billing_vat_number' => 'Stripe did not accept that VAT number. Check it and try again.',
            ]);
        }
    }

    private static function brandStripeInvoice(object $invoice): void
    {
        $id = (string) data_get($invoice, 'id', '');
        if (! str_starts_with($id, 'in_') || ! self::configured()) {
            return;
        }

        $status = (string) data_get($invoice, 'status', '');
        if (in_array($status, ['paid', 'void', 'uncollectible'], true)) {
            return;
        }

        $footer = CompanyBooks::DEFAULT_LEGAL_NAME."\n"
            .'Company Reg. No. '.CompanyBooks::DEFAULT_REGISTRATION_NUMBER."\n"
            .CompanyBooks::DEFAULT_REGISTERED_OFFICE;

        $operator = CeruleanSubscriptionBooks::operator();
        if ($operator) {
            $vat = CompanyProfile::query()->where('user_id', $operator->id)->value('vat_number');
            if (is_string($vat) && $vat !== '') {
                $footer .= "\nVAT No. ".$vat;
            }
        }

        self::client()->invoices->update($id, [
            'account_name' => CompanyBooks::DEFAULT_LEGAL_NAME,
            'footer' => $footer,
        ]);
    }
}
