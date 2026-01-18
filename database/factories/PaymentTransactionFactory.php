<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentGateway;
use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'payment_gateway' => fake()->randomElement(PaymentGateway::cases()),
            'reference' => 'TXN_' . Str::upper(Str::random(12)),
            'gateway_transaction_id' => Str::uuid()->toString(),
            'status' => PaymentTransactionStatus::Pending,
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency' => fake()->randomElement(['USD', 'GHS', 'GHS', 'EUR']),
            'gateway_response' => null,
            'webhook_payload' => null,
            'verified_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentTransactionStatus::Pending,
            'verified_at' => null,
        ]);
    }

    public function successful(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentTransactionStatus::Success,
            'verified_at' => now(),
            'gateway_response' => ['status' => 'success'],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PaymentTransactionStatus::Failed,
            'verified_at' => now(),
            'gateway_response' => ['status' => 'failed', 'message' => 'Payment declined'],
        ]);
    }

    public function paystack(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_gateway' => PaymentGateway::Paystack,
            'currency' => 'GHS',
            'reference' => 'PS_' . Str::upper(Str::random(12)),
        ]);
    }

    public function stripe(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_gateway' => PaymentGateway::Stripe,
            'currency' => 'USD',
            'reference' => 'cs_' . Str::random(24),
        ]);
    }
}
