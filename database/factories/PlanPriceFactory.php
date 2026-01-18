<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlanPrice>
 */
class PlanPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'gateway' => null,
            'currency' => 'USD',
            'price' => fake()->randomFloat(2, 5, 100),
            'is_active' => true,
        ];
    }

    public function forPaystack(): static
    {
        return $this->state(fn(array $attributes) => [
            'gateway' => 'paystack',
            'currency' => 'GHS',
        ]);
    }

    public function forStripe(): static
    {
        return $this->state(fn(array $attributes) => [
            'gateway' => 'stripe',
            'currency' => 'USD',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withCurrency(string $currency): static
    {
        return $this->state(fn(array $attributes) => [
            'currency' => $currency,
        ]);
    }

    public function withGateway(?string $gateway): static
    {
        return $this->state(fn(array $attributes) => [
            'gateway' => $gateway,
        ]);
    }
}
