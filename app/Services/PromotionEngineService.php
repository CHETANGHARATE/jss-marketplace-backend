<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\LoyaltyPoint;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class PromotionEngineService
{
    /**
     * Validate and apply coupon code to cart total.
     */
    public function applyCoupon(string $code, float $cartTotal, ?User $user = null): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon || !$coupon->is_active) {
            throw new Exception("Invalid or inactive coupon code.");
        }

        $now = now();
        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            throw new Exception("Coupon is not yet active.");
        }

        if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
            throw new Exception("Coupon code has expired.");
        }

        if ($cartTotal < $coupon->min_order_amount) {
            throw new Exception("Minimum order amount of {$coupon->min_order_amount} required for this coupon.");
        }

        if ($coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit) {
            throw new Exception("Global usage limit reached for this coupon.");
        }

        if ($user && $coupon->user_limit !== null) {
            $userUsage = CouponUsage::where('coupon_id', $coupon->id)->where('user_id', $user->id)->count();
            if ($userUsage >= $coupon->user_limit) {
                throw new Exception("You have reached your maximum usage limit for this coupon.");
            }
        }

        // Calculate Discount
        if ($coupon->discount_type === 'percentage') {
            $discount = round(($cartTotal * $coupon->discount_value) / 100, 2);
            if ($coupon->max_discount_amount !== null && $discount > $coupon->max_discount_amount) {
                $discount = (float) $coupon->max_discount_amount;
            }
        } else {
            $discount = (float) $coupon->discount_value;
        }

        $discount = min($discount, $cartTotal);
        $finalTotal = max(0.00, round($cartTotal - $discount, 2));

        return [
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => (float) $coupon->discount_value,
            'discount_amount' => $discount,
            'final_total' => $finalTotal,
        ];
    }

    /**
     * Award loyalty points to user on order completion (1 point per 10 currency spent).
     */
    public function earnLoyaltyPoints(User $user, float $orderTotal): LoyaltyPoint
    {
        $earned = max(1, (int) floor($orderTotal / 10));

        return DB::transaction(function () use ($user, $earned) {
            $loyalty = LoyaltyPoint::firstOrCreate(
                ['user_id' => $user->id],
                ['points_balance' => 0, 'total_earned' => 0]
            );

            $loyalty->increment('points_balance', $earned);
            $loyalty->increment('total_earned', $earned);

            return $loyalty->fresh();
        });
    }

    /**
     * Reward referrer on referee's first purchase.
     */
    public function processReferralReward(User $referee): void
    {
        $referral = Referral::where('referee_id', $referee->id)->where('status', 'pending')->first();

        if ($referral) {
            DB::transaction(function () use ($referral) {
                $referrerLoyalty = LoyaltyPoint::firstOrCreate(
                    ['user_id' => $referral->referrer_id],
                    ['points_balance' => 0, 'total_earned' => 0]
                );

                $referrerLoyalty->increment('points_balance', $referral->reward_points);
                $referrerLoyalty->increment('total_earned', $referral->reward_points);

                $referral->update(['status' => 'rewarded']);
            });
        }
    }
}
