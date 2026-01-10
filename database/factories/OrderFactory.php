<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subscription = Subscription::factory()->create();
        $paidAt = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'woocommerce_order_id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'status' => OrderStatus::Provisioned,
            'amount' => $subscription->plan->price,
            'currency' => $subscription->plan->currency,
            'payment_method' => fake()->randomElement(['stripe', 'paypal', 'card']),
            'paid_at' => $paidAt,
            'provisioned_at' => (clone $paidAt)->modify('+2 minutes'),
            'idempotency_key' => Str::uuid()->toString(),
            'webhook_payload' => [
                'event' => 'order.completed',
                'timestamp' => $paidAt->format('c'),
            ],
        ];
    }

    public function pendingProvisioning(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => OrderStatus::PendingProvisioning,
            'provisioned_at' => null,
        ]);
    }

    public function provisioned(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => OrderStatus::Provisioned,
            'provisioned_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => OrderStatus::ProvisioningFailed,
            'provisioned_at' => null,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => OrderStatus::Refunded,
        ]);
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn(array $attributes) => [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'amount' => $subscription->plan->price,
            'currency' => $subscription->plan->currency,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
