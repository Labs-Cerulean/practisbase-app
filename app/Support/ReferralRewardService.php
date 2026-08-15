<?php

namespace App\Support;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Affiliate referral wallet credits.
 * Placeholder until Stripe billing confirms the referred user's first payment.
 */
class ReferralRewardService
{
    public function defaultRewardAmount(?User $referred = null): float
    {
        // Practice list price is the public affiliate credit until Stripe tiers go live.
        return (float) TierPolicy::PRICE_PRACTICE;
    }

    public function attachPending(User $referrer, User $referred): Referral
    {
        if ((int) $referrer->id === (int) $referred->id) {
            throw new RuntimeException('Users cannot refer themselves.');
        }

        return Referral::firstOrCreate(
            ['referred_id' => $referred->id],
            [
                'referrer_id' => $referrer->id,
                'status' => Referral::STATUS_PENDING,
            ]
        );
    }

    /**
     * Mark a pending referral as rewarded and credit the referrer wallet.
     */
    public function reward(Referral $referral, ?float $amount = null): Referral
    {
        return DB::transaction(function () use ($referral, $amount) {
            $locked = Referral::query()->where('id', $referral->id)->lockForUpdate()->firstOrFail();

            if ($locked->isRewarded()) {
                return $locked;
            }

            if (! $locked->isPending()) {
                throw new RuntimeException('Referral is not pending payment.');
            }

            $credit = $amount ?? $this->defaultRewardAmount($locked->referred);
            $credit = round(max(0, $credit), 2);

            $referrer = User::query()->where('id', $locked->referrer_id)->lockForUpdate()->firstOrFail();
            $referrer->credit_balance = round(((float) $referrer->credit_balance) + $credit, 2);
            $referrer->save();

            $locked->status = Referral::STATUS_REWARDED;
            $locked->reward_amount = $credit;
            $locked->rewarded_at = now();
            $locked->save();

            return $locked;
        });
    }

    /**
     * Convenience hook for future Stripe webhooks / billing jobs.
     */
    public function rewardForReferredUser(User $referred, ?float $amount = null): ?Referral
    {
        $referral = Referral::query()
            ->where('referred_id', $referred->id)
            ->where('status', Referral::STATUS_PENDING)
            ->first();

        if (! $referral) {
            return null;
        }

        return $this->reward($referral, $amount);
    }
}
