<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServiceAccountStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceAccount>
 */
class ServiceAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subscription = Subscription::factory()->create();
        $activatedAt = now()->subDays(fake()->numberBetween(1, 30));
        $expiresAt = $activatedAt->copy()->addDays($subscription->plan->duration_days);

        return [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'my8k_account_id' => 'MY8K_' . fake()->unique()->numerify('######'),
            'username' => 'user_' . fake()->unique()->userName(),
            'password' => fake()->password(12, 16),
            'server_url' => fake()->randomElement([
                'http://server1.my8k.com:8080',
                'http://server2.my8k.com:8080',
                'http://server3.my8k.com:8080',
            ]),
            'max_connections' => $subscription->plan->max_devices ?? 1,
            'status' => ServiceAccountStatus::Active,
            'activated_at' => $activatedAt,
            'expires_at' => $expiresAt,
            'last_extended_at' => $activatedAt,
            'my8k_metadata' => [
                'created_ip' => fake()->ipv4(),
                'last_connection' => now()->subHours(fake()->numberBetween(1, 48))->toIso8601String(),
                'connection_count' => fake()->numberBetween(0, 100),
            ],
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ServiceAccountStatus::Active,
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ServiceAccountStatus::Suspended,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ServiceAccountStatus::Expired,
            'expires_at' => now()->subDays(5),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ServiceAccountStatus::Active,
            'expires_at' => now()->addDays(3),
        ]);
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn(array $attributes) => [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'max_connections' => $subscription->plan->max_devices ?? 1,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
