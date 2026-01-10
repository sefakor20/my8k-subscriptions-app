<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BillingInterval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $interval = fake()->randomElement(BillingInterval::cases());
        $basePrice = fake()->randomElement([9.99, 14.99, 19.99, 24.99, 29.99, 39.99, 49.99]);

        return [
            'name' => fake()->words(3, true) . ' IPTV Plan',
            'slug' => fake()->unique()->slug(3),
            'description' => fake()->sentence(12),
            'price' => $basePrice,
            'currency' => 'USD',
            'billing_interval' => $interval,
            'duration_days' => $interval->days(),
            'max_devices' => fake()->randomElement([1, 2, 3, 5]),
            'features' => [
                'channels' => fake()->numberBetween(5000, 25000),
                'vod_movies' => fake()->numberBetween(10000, 50000),
                'vod_series' => fake()->numberBetween(5000, 15000),
                'hd_quality' => fake()->boolean(80),
                '4k_quality' => fake()->boolean(50),
                'epg' => true,
                'catch_up' => fake()->boolean(70),
                'anti_freeze' => true,
            ],
            'is_active' => true,
            'woocommerce_id' => (string) fake()->unique()->numberBetween(1000, 9999),
            'my8k_plan_code' => 'PLAN_' . mb_strtoupper(fake()->unique()->bothify('??###')),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn(array $attributes) => [
            'billing_interval' => BillingInterval::Monthly,
            'duration_days' => 30,
        ]);
    }

    public function quarterly(): static
    {
        return $this->state(fn(array $attributes) => [
            'billing_interval' => BillingInterval::Quarterly,
            'duration_days' => 90,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn(array $attributes) => [
            'billing_interval' => BillingInterval::Yearly,
            'duration_days' => 365,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn(array $attributes) => [
            'price' => fake()->randomFloat(2, 40, 60),
            'max_devices' => 5,
            'features' => [
                'channels' => 25000,
                'vod_movies' => 50000,
                'vod_series' => 15000,
                'hd_quality' => true,
                '4k_quality' => true,
                'epg' => true,
                'catch_up' => true,
                'anti_freeze' => true,
            ],
        ]);
    }

    public function basic(): static
    {
        return $this->state(fn(array $attributes) => [
            'price' => fake()->randomFloat(2, 9, 15),
            'max_devices' => 1,
            'features' => [
                'channels' => 5000,
                'vod_movies' => 10000,
                'vod_series' => 5000,
                'hd_quality' => true,
                '4k_quality' => false,
                'epg' => true,
                'catch_up' => false,
                'anti_freeze' => true,
            ],
        ]);
    }
}
