<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupportTicket>
 */
class SupportTicketFactory extends Factory
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
            'subscription_id' => null,
            'order_id' => null,
            'subject' => fake()->sentence(),
            'category' => fake()->randomElement(TicketCategory::cases()),
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'status' => TicketStatus::Open,
            'assigned_to' => null,
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }

    public function withSubscription(): static
    {
        return $this->state(fn(array $attributes) => [
            'subscription_id' => Subscription::factory(),
        ]);
    }

    public function withOrder(): static
    {
        return $this->state(fn(array $attributes) => [
            'order_id' => Order::factory(),
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn(array $attributes) => [
            'assigned_to' => User::factory()->admin(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => TicketStatus::InProgress,
            'assigned_to' => User::factory()->admin(),
            'first_response_at' => now()->subHours(2),
        ]);
    }

    public function waitingCustomer(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => TicketStatus::WaitingCustomer,
            'assigned_to' => User::factory()->admin(),
            'first_response_at' => now()->subHours(3),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => TicketStatus::Resolved,
            'assigned_to' => User::factory()->admin(),
            'first_response_at' => now()->subDays(2),
            'resolved_at' => now()->subHours(1),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => TicketStatus::Closed,
            'assigned_to' => User::factory()->admin(),
            'first_response_at' => now()->subDays(3),
            'resolved_at' => now()->subDays(1),
            'closed_at' => now(),
        ]);
    }

    public function technical(): static
    {
        return $this->state(fn(array $attributes) => [
            'category' => TicketCategory::Technical,
        ]);
    }

    public function billing(): static
    {
        return $this->state(fn(array $attributes) => [
            'category' => TicketCategory::Billing,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn(array $attributes) => [
            'priority' => TicketPriority::Urgent,
        ]);
    }

    public function high(): static
    {
        return $this->state(fn(array $attributes) => [
            'priority' => TicketPriority::High,
        ]);
    }

    public function low(): static
    {
        return $this->state(fn(array $attributes) => [
            'priority' => TicketPriority::Low,
        ]);
    }
}
