<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProvisioningAction;
use App\Enums\ProvisioningStatus;
use App\Models\Order;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProvisioningLog>
 */
class ProvisioningLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->randomElement(ProvisioningAction::cases());
        $status = fake()->randomElement([ProvisioningStatus::Success, ProvisioningStatus::Success, ProvisioningStatus::Failed]);
        $durationMs = fake()->numberBetween(100, 5000);

        return [
            'subscription_id' => Subscription::factory(),
            'order_id' => Order::factory(),
            'service_account_id' => null,
            'action' => $action,
            'status' => $status,
            'attempt_number' => 1,
            'job_id' => Str::uuid()->toString(),
            'my8k_request' => [
                'action' => $action->value,
                'plan_code' => 'PLAN_' . fake()->bothify('??###'),
                'timestamp' => now()->toIso8601String(),
            ],
            'my8k_response' => $status === ProvisioningStatus::Success ? [
                'success' => true,
                'account_id' => 'MY8K_' . fake()->numerify('######'),
                'message' => 'Account created successfully',
            ] : [
                'success' => false,
                'error' => fake()->randomElement([
                    'Invalid plan code',
                    'Insufficient credits',
                    'Server unavailable',
                    'API rate limit exceeded',
                ]),
            ],
            'error_message' => $status === ProvisioningStatus::Failed ? fake()->sentence() : null,
            'error_code' => $status === ProvisioningStatus::Failed ? fake()->randomElement(['ERR_001', 'ERR_002', 'ERR_003', 'ERR_TIMEOUT']) : null,
            'duration_ms' => $durationMs,
        ];
    }

    public function success(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ProvisioningStatus::Success,
            'my8k_response' => [
                'success' => true,
                'account_id' => 'MY8K_' . fake()->numerify('######'),
                'message' => 'Operation successful',
            ],
            'error_message' => null,
            'error_code' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ProvisioningStatus::Failed,
            'my8k_response' => [
                'success' => false,
                'error' => 'Operation failed',
            ],
            'error_message' => fake()->sentence(),
            'error_code' => 'ERR_' . fake()->randomNumber(3),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ProvisioningStatus::Pending,
            'my8k_response' => null,
            'error_message' => null,
            'error_code' => null,
            'duration_ms' => null,
        ]);
    }

    public function retrying(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ProvisioningStatus::Retrying,
            'attempt_number' => fake()->numberBetween(2, 5),
        ]);
    }

    public function forAction(ProvisioningAction $action): static
    {
        return $this->state(fn(array $attributes) => [
            'action' => $action,
        ]);
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn(array $attributes) => [
            'subscription_id' => $subscription->id,
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn(array $attributes) => [
            'order_id' => $order->id,
            'subscription_id' => $order->subscription_id,
        ]);
    }

    public function forServiceAccount(ServiceAccount $serviceAccount): static
    {
        return $this->state(fn(array $attributes) => [
            'service_account_id' => $serviceAccount->id,
            'subscription_id' => $serviceAccount->subscription_id,
        ]);
    }
}
