<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResellerCreditLog>
 */
class ResellerCreditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $balance = fake()->randomFloat(2, 0, 10000);
        $previousBalance = fake()->randomFloat(2, 0, 10000);
        $changeAmount = abs($balance - $previousBalance);

        return [
            'balance' => $balance,
            'previous_balance' => $previousBalance,
            'change_amount' => $changeAmount,
            'change_type' => fake()->randomElement(['debit', 'credit', 'adjustment', 'snapshot']),
            'reason' => fake()->optional()->sentence(),
            'related_provisioning_log_id' => null,
            'api_response' => fake()->optional()->passthrough([
                'success' => true,
                'credits' => $balance,
                'message' => 'Balance retrieved successfully',
            ]),
        ];
    }

    /**
     * Indicate this is a debit transaction
     */
    public function debit(): static
    {
        return $this->state(fn(array $attributes) => [
            'change_type' => 'debit',
            'change_amount' => abs($attributes['change_amount']),
            'balance' => $attributes['previous_balance'] - abs($attributes['change_amount']),
        ]);
    }

    /**
     * Indicate this is a credit transaction
     */
    public function credit(): static
    {
        return $this->state(fn(array $attributes) => [
            'change_type' => 'credit',
            'change_amount' => abs($attributes['change_amount']),
            'balance' => $attributes['previous_balance'] + abs($attributes['change_amount']),
        ]);
    }

    /**
     * Indicate this is a snapshot
     */
    public function snapshot(): static
    {
        return $this->state(fn(array $attributes) => [
            'change_type' => 'snapshot',
            'change_amount' => null,
            'previous_balance' => null,
            'reason' => 'Scheduled balance check',
        ]);
    }
}
