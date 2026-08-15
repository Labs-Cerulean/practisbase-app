<?php

namespace App\Support;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromotionEngine
{
    public function findRedeemable(string $rawCode): ?Promotion
    {
        $code = Promotion::normalizeCode($rawCode);
        if ($code === '') {
            return null;
        }

        $promo = Promotion::query()->where('code', $code)->first();

        return ($promo && $promo->isRedeemable()) ? $promo : null;
    }

    /**
     * Lock, validate capacity, increment uses, and apply benefit onto the user.
     */
    public function redeemForUser(User $user, Promotion $promotion): void
    {
        DB::transaction(function () use ($user, $promotion) {
            $locked = Promotion::query()->where('id', $promotion->id)->lockForUpdate()->first();
            if (! $locked || ! $locked->isRedeemable()) {
                throw ValidationException::withMessages([
                    'promo_code' => 'That promo code is invalid, expired, inactive, or fully used.',
                ]);
            }

            $locked->current_uses = $locked->current_uses + 1;
            $locked->save();

            $user->applied_promotion_id = $locked->id;

            if ($locked->type === Promotion::TYPE_FREE_MONTHS) {
                $months = max(1, (int) $locked->value);
                $base = $user->trial_ends_at && $user->trial_ends_at->isFuture()
                    ? $user->trial_ends_at->copy()
                    : now();
                $user->trial_ends_at = $base->addMonthsNoOverflow($months);
            }

            $user->save();
        });
    }
}
