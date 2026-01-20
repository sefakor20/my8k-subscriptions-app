<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use DateTimeInterface;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => mb_strtoupper($this->faker->unique()->lexify('????') . $this->faker->numerify('##')),
            'name' => $this->faker->words(3, true) . ' Discount',
            'description' => $this->faker->optional()->sentence(),
            'discount_type' => CouponDiscountType::Percentage,
            'discount_value' => $this->faker->randomElement([10, 15, 20, 25, 30]),
            'trial_extension_days' => null,
            'max_redemptions' => null,
            'max_redemptions_per_user' => 1,
            'minimum_order_amount' => null,
            'first_time_customer_only' => false,
            'currency' => null,
            'valid_from' => null,
            'valid_until' => null,
            'is_active' => true,
            'metadata' => null,
        ];
    }

    /**
     * Configure coupon as percentage discount.
     */
    public function percentage(int $percent = 20): static
    {
        return $this->state(fn() => [
            'discount_type' => CouponDiscountType::Percentage,
            'discount_value' => $percent,
        ]);
    }

    /**
     * Configure coupon as fixed amount discount.
     */
    public function fixedAmount(float $amount = 10.00, string $currency = 'USD'): static
    {
        return $this->state(fn() => [
            'discount_type' => CouponDiscountType::FixedAmount,
            'discount_value' => $amount,
            'currency' => $currency,
        ]);
    }

    /**
     * Configure coupon as trial extension.
     */
    public function trialExtension(int $days = 7): static
    {
        return $this->state(fn() => [
            'discount_type' => CouponDiscountType::TrialExtension,
            'discount_value' => 0,
            'trial_extension_days' => $days,
        ]);
    }

    /**
     * Configure coupon as expired.
     */
    public function expired(): static
    {
        return $this->state(fn() => [
            'valid_until' => now()->subDay(),
        ]);
    }

    /**
     * Configure coupon as not yet valid.
     */
    public function notYetValid(): static
    {
        return $this->state(fn() => [
            'valid_from' => now()->addDay(),
        ]);
    }

    /**
     * Configure coupon as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn() => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure coupon as exhausted (max redemptions reached).
     */
    public function exhausted(): static
    {
        return $this->state(fn() => [
            'max_redemptions' => 0,
        ]);
    }

    /**
     * Configure coupon for first-time customers only.
     */
    public function firstTimeOnly(): static
    {
        return $this->state(fn() => [
            'first_time_customer_only' => true,
        ]);
    }

    /**
     * Configure coupon with limited total redemptions.
     */
    public function limitedRedemptions(int $max = 100): static
    {
        return $this->state(fn() => [
            'max_redemptions' => $max,
        ]);
    }

    /**
     * Configure coupon with minimum order amount.
     */
    public function minimumOrder(float $amount = 50.00): static
    {
        return $this->state(fn() => [
            'minimum_order_amount' => $amount,
        ]);
    }

    /**
     * Configure coupon with validity period.
     */
    public function validBetween(DateTimeInterface $from, DateTimeInterface $until): static
    {
        return $this->state(fn() => [
            'valid_from' => $from,
            'valid_until' => $until,
        ]);
    }
}
