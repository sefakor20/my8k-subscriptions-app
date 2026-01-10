<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-3 months', 'now');
        $plan = Plan::factory()->create();

        return [
            'user_id' => User::factory(),
            'plan_id' => $plan->id,
            'service_account_id' => null,
            'status' => SubscriptionStatus::Active,
            'woocommerce_subscription_id' => (string) fake()->unique()->numberBetween(10000, 99999),
            'starts_at' => $startsAt,
            'expires_at' => (clone $startsAt)->modify("+{$plan->duration_days} days"),
            'cancelled_at' => null,
            'last_renewal_at' => $startsAt,
            'next_renewal_at' => (clone $startsAt)->modify("+{$plan->duration_days} days"),
            'auto_renew' => true,
            'metadata' => [],
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDays(30),
            'next_renewal_at' => now()->addDays(30),
            'cancelled_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => SubscriptionStatus::Pending,
            'starts_at' => now()->addDays(1),
            'expires_at' => now()->addDays(31),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => SubscriptionStatus::Expired,
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->subDays(5),
            'last_renewal_at' => now()->subMonths(2),
            'next_renewal_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now()->subDays(3),
            'auto_renew' => false,
            'next_renewal_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => SubscriptionStatus::Suspended,
            'auto_renew' => false,
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDays(3),
            'next_renewal_at' => now()->addDays(3),
        ]);
    }

    public function withoutAutoRenew(): static
    {
        return $this->state(fn(array $attributes) => [
            'auto_renew' => false,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forPlan(Plan $plan): static
    {
        return $this->state(fn(array $attributes) => [
            'plan_id' => $plan->id,
            'expires_at' => now()->addDays($plan->duration_days),
            'next_renewal_at' => now()->addDays($plan->duration_days),
        ]);
    }
}
