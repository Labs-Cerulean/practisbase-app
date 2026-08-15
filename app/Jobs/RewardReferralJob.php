<?php

namespace App\Jobs;

use App\Models\Referral;
use App\Support\ReferralRewardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Placeholder job: credit the referrer wallet once the referred user's payment clears.
 * Dispatch from Stripe webhooks / billing when first payment succeeds.
 */
class RewardReferralJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $referralId,
        public ?float $amount = null,
    ) {}

    public function handle(ReferralRewardService $rewards): void
    {
        $referral = Referral::query()->find($this->referralId);
        if (! $referral || $referral->isRewarded()) {
            return;
        }

        $rewards->reward($referral, $this->amount);
    }
}
