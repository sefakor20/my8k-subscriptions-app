<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponRedemption>
 */
class CouponRedemptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalAmount = $this->faker->randomFloat(2, 20, 100);
        $discountAmount = $this->faker->randomFloat(2, 5, 20);

        return [
            'coupon_id' => Coupon::factory(),
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'discount_amount' => $discountAmount,
            'original_amount' => $originalAmount,
            'final_amount' => max(0, $originalAmount - $discountAmount),
            'currency' => 'USD',
            'trial_days_added' => null,
        ];
    }

    /**
     * Configure redemption for a specific coupon.
     */
    public function forCoupon(Coupon $coupon): static
    {
        return $this->state(fn() => [
            'coupon_id' => $coupon->id,
        ]);
    }

    /**
     * Configure redemption for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn() => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Configure redemption for a specific order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn() => [
            'order_id' => $order->id,
        ]);
    }

    /**
     * Configure redemption with trial extension.
     */
    public function withTrialExtension(int $days = 7): static
    {
        return $this->state(fn() => [
            'trial_days_added' => $days,
        ]);
    }
}
