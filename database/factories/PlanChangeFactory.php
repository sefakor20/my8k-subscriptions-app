<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlanChangeExecutionType;
use App\Enums\PlanChangeStatus;
use App\Enums\PlanChangeType;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlanChange>
 */
class PlanChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subscription = Subscription::factory()->create();
        $fromPlan = $subscription->plan;
        $toPlan = Plan::factory()->create();

        return [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'from_plan_id' => $fromPlan->id,
            'to_plan_id' => $toPlan->id,
            'type' => PlanChangeType::Upgrade,
            'status' => PlanChangeStatus::Pending,
            'execution_type' => PlanChangeExecutionType::Immediate,
            'proration_amount' => fake()->randomFloat(2, 0, 100),
            'credit_amount' => 0,
            'currency' => 'USD',
            'calculation_details' => [
                'days_remaining' => 15,
                'unused_credit' => 50.00,
                'new_plan_cost' => 100.00,
            ],
        ];
    }

    public function upgrade(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => PlanChangeType::Upgrade,
        ]);
    }

    public function downgrade(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => PlanChangeType::Downgrade,
            'proration_amount' => 0,
            'credit_amount' => fake()->randomFloat(2, 10, 50),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PlanChangeStatus::Pending,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PlanChangeStatus::Scheduled,
            'execution_type' => PlanChangeExecutionType::Scheduled,
            'scheduled_at' => now()->addDays(7),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PlanChangeStatus::Completed,
            'executed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PlanChangeStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => 'Payment failed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PlanChangeStatus::Cancelled,
        ]);
    }

    public function immediate(): static
    {
        return $this->state(fn(array $attributes) => [
            'execution_type' => PlanChangeExecutionType::Immediate,
        ]);
    }

    public function scheduledExecution(): static
    {
        return $this->state(fn(array $attributes) => [
            'execution_type' => PlanChangeExecutionType::Scheduled,
            'scheduled_at' => now()->addDays(7),
        ]);
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn(array $attributes) => [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'from_plan_id' => $subscription->plan_id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function toPlan(Plan $plan): static
    {
        return $this->state(fn(array $attributes) => [
            'to_plan_id' => $plan->id,
        ]);
    }

    public function fromPlan(Plan $plan): static
    {
        return $this->state(fn(array $attributes) => [
            'from_plan_id' => $plan->id,
        ]);
    }
}
