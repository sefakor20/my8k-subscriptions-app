<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CouponService
{
    /**
     * Validate a coupon code for a given user and plan.
     *
     * @return array{valid: bool, coupon?: Coupon, error?: string, discount?: float, original_amount?: float, final_amount?: float, currency?: string, trial_days?: int|null}
     */
    public function validateCoupon(
        string $code,
        User $user,
        Plan $plan,
        string $gateway,
    ): array {
        $coupon = Coupon::byCode($code)->first();

        if (! $coupon) {
            return ['valid' => false, 'error' => 'Coupon code not found'];
        }

        // Check if coupon is active and within date range
        if (! $coupon->isValid()) {
            if ($coupon->isExpired()) {
                return ['valid' => false, 'error' => 'This coupon has expired'];
            }
            if ($coupon->isExhausted()) {
                return ['valid' => false, 'error' => 'This coupon has reached its maximum redemptions'];
            }

            return ['valid' => false, 'error' => 'This coupon is not currently active'];
        }

        // Check if coupon applies to this plan
        if (! $coupon->appliesToPlan($plan)) {
            return ['valid' => false, 'error' => 'This coupon is not valid for the selected plan'];
        }

        // Check per-user redemption limit
        if (! $coupon->canBeUsedBy($user)) {
            return ['valid' => false, 'error' => 'You have already used this coupon'];
        }

        // Check first-time customer restriction
        if ($coupon->first_time_customer_only && ! $user->isFirstTimeCustomer()) {
            return ['valid' => false, 'error' => 'This coupon is only valid for first-time customers'];
        }

        // Get price for this gateway
        $currency = $plan->getCurrencyFor($gateway);
        $originalAmount = $plan->getAmountFor($gateway, $currency);

        // Check minimum order amount
        if ($coupon->minimum_order_amount !== null && $originalAmount < floatval($coupon->minimum_order_amount)) {
            return [
                'valid' => false,
                'error' => 'Minimum order amount of '
                    . number_format(floatval($coupon->minimum_order_amount), 2)
                    . ' ' . ($coupon->currency ?? $currency) . ' required',
            ];
        }

        // Check currency match for fixed amount discounts
        if ($coupon->discount_type === CouponDiscountType::FixedAmount) {
            if ($coupon->currency !== null && $coupon->currency !== $currency) {
                return [
                    'valid' => false,
                    'error' => 'This coupon is not valid for payments in ' . $currency,
                ];
            }
        }

        // Calculate discount
        $discountAmount = $coupon->calculateDiscount($originalAmount, $currency);
        $finalAmount = max(0, $originalAmount - $discountAmount);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discountAmount,
            'original_amount' => $originalAmount,
            'final_amount' => $finalAmount,
            'currency' => $currency,
            'trial_days' => $coupon->discount_type === CouponDiscountType::TrialExtension
                ? $coupon->trial_extension_days
                : null,
        ];
    }

    /**
     * Redeem a coupon for an order.
     */
    public function redeemCoupon(
        Coupon $coupon,
        User $user,
        Order $order,
        float $originalAmount,
        float $discountAmount,
        string $currency,
    ): CouponRedemption {
        return DB::transaction(function () use ($coupon, $user, $order, $originalAmount, $discountAmount, $currency) {
            $redemption = CouponRedemption::create([
                'coupon_id' => $coupon->id,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'discount_amount' => $discountAmount,
                'original_amount' => $originalAmount,
                'final_amount' => $originalAmount - $discountAmount,
                'currency' => $currency,
                'trial_days_added' => $coupon->discount_type === CouponDiscountType::TrialExtension
                    ? $coupon->trial_extension_days
                    : null,
            ]);

            Log::info('Coupon redeemed', [
                'coupon_id' => $coupon->id,
                'coupon_code' => $coupon->code,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'discount_amount' => $discountAmount,
            ]);

            return $redemption;
        });
    }

    /**
     * Generate a unique coupon code.
     */
    public function generateCode(int $length = 8): string
    {
        do {
            $code = mb_strtoupper(Str::random($length));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }

    /**
     * Create a new coupon.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCoupon(array $data): Coupon
    {
        $data['code'] = mb_strtoupper(trim($data['code']));

        $coupon = Coupon::create($data);

        if (isset($data['plan_ids']) && is_array($data['plan_ids'])) {
            $coupon->plans()->sync($data['plan_ids']);
        }

        return $coupon;
    }

    /**
     * Update an existing coupon.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCoupon(Coupon $coupon, array $data): Coupon
    {
        if (isset($data['code'])) {
            $data['code'] = mb_strtoupper(trim($data['code']));
        }

        $coupon->update($data);

        if (isset($data['plan_ids'])) {
            $coupon->plans()->sync($data['plan_ids']);
        }

        return $coupon->fresh();
    }

    /**
     * Toggle a coupon's active status.
     */
    public function toggleActive(Coupon $coupon): Coupon
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return $coupon->fresh();
    }
}
