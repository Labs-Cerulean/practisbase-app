<?php

namespace App\Support;

/**
 * Pure Checkout payload and trial rules. No Stripe SDK and no database.
 */
class StripeCheckoutPlan
{
    public const TRIAL_DAYS = 14;

    public const TRIAL_CAP_DAYS = 730;

    public static function grossCents(string $exVat, string $vatPercent = '18'): int
    {
        $gross = round((float) $exVat * (1 + ((float) $vatPercent / 100)), 2);

        return (int) round($gross * 100);
    }

    public static function trialPeriodDays(bool $alreadySubscribed, ?int $promoEndsAt, ?int $now = null): int
    {
        $now ??= time();
        $days = $alreadySubscribed ? 0 : self::TRIAL_DAYS;

        if ($promoEndsAt !== null && $promoEndsAt > $now) {
            $remaining = (int) ceil(($promoEndsAt - $now) / 86400);
            $days = max($days, $remaining);
        }

        return min(self::TRIAL_CAP_DAYS, max(0, $days));
    }

    public static function exVatForTier(string $tier): ?string
    {
        return match (TierPolicy::normalize($tier)) {
            TierPolicy::TIER_STANDARD => TierPolicy::PRICE_STANDARD,
            TierPolicy::TIER_PRACTICE_MED, TierPolicy::TIER_PRACTICE_ARCH, TierPolicy::TIER_PRACTICE_ENG => TierPolicy::PRICE_PRACTICE,
            TierPolicy::TIER_PRO_MED, TierPolicy::TIER_PRO_ARCH, TierPolicy::TIER_PRO_ENG => TierPolicy::PRICE_PRO,
            default => null,
        };
    }

    public static function tierAfterBillingEvent(string $status, ?string $requestedTier, bool $accessCode, string $currentTier): string
    {
        if ($accessCode) {
            return $currentTier;
        }

        $status = strtolower($status);

        if (in_array($status, ['active', 'trialing', 'past_due'], true)) {
            if ($requestedTier === null || $requestedTier === '' || $requestedTier === TierPolicy::TIER_FREE) {
                return $currentTier;
            }
            if (! in_array($requestedTier, TierPolicy::allTiers(), true)) {
                return $currentTier;
            }

            return $requestedTier;
        }

        if (in_array($status, ['canceled', 'unpaid', 'incomplete_expired'], true)) {
            return TierPolicy::TIER_FREE;
        }

        return $currentTier;
    }

    /**
     * @param  array{
     *     userId: int|string,
     *     email: string,
     *     tier: string,
     *     label: string,
     *     trialDays: int,
     *     successUrl: string,
     *     cancelUrl: string,
     *     priceId?: ?string,
     *     customerId?: ?string,
     *     returnTo?: string
     * }  $input
     * @return array<string, mixed>
     */
    public static function sessionPayload(array $input): array
    {
        $line = ['quantity' => 1];
        $priceId = $input['priceId'] ?? null;
        if (is_string($priceId) && $priceId !== '') {
            $line['price'] = $priceId;
        } else {
            $exVat = self::exVatForTier($input['tier']);
            if ($exVat === null) {
                throw new \InvalidArgumentException('Paid tier is required for card checkout.');
            }
            $line['price_data'] = [
                'currency' => 'eur',
                'unit_amount' => self::grossCents($exVat),
                'recurring' => ['interval' => 'month'],
                'product_data' => ['name' => 'PractisBase '.$input['label']],
            ];
        }

        $subscription = [
            'metadata' => [
                'user_id' => (string) $input['userId'],
                'tier' => (string) $input['tier'],
            ],
        ];
        $trialDays = (int) ($input['trialDays'] ?? 0);
        if ($trialDays > 0) {
            $subscription['trial_period_days'] = $trialDays;
        }

        $payload = [
            'mode' => 'subscription',
            'client_reference_id' => (string) $input['userId'],
            'payment_method_collection' => 'always',
            'line_items' => [$line],
            'subscription_data' => $subscription,
            'metadata' => [
                'user_id' => (string) $input['userId'],
                'tier' => (string) $input['tier'],
                'billing_return' => (string) ($input['returnTo'] ?? 'onboarding'),
            ],
            'success_url' => $input['successUrl'],
            'cancel_url' => $input['cancelUrl'],
        ];

        $customerId = $input['customerId'] ?? null;
        if (is_string($customerId) && $customerId !== '') {
            $payload['customer'] = $customerId;
        } else {
            $payload['customer_email'] = $input['email'];
        }

        return $payload;
    }
}
