<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationCategory;
use App\Enums\NotificationLogStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationLog>
 */
class NotificationLogFactory extends Factory
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
            'notification_type' => 'App\\Mail\\SubscriptionRenewed',
            'category' => fake()->randomElement(NotificationCategory::cases()),
            'channel' => 'mail',
            'subject' => fake()->sentence(),
            'metadata' => [],
            'status' => NotificationLogStatus::Sent,
            'sent_at' => now(),
        ];
    }

    /**
     * Indicate the notification was blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => NotificationLogStatus::Blocked,
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate the notification failed.
     */
    public function failed(string $reason = 'Email delivery failed'): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => NotificationLogStatus::Failed,
            'failure_reason' => $reason,
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate the notification is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => NotificationLogStatus::Pending,
            'sent_at' => null,
        ]);
    }

    /**
     * Set a specific category.
     */
    public function forCategory(NotificationCategory $category): static
    {
        return $this->state(fn(array $attributes) => [
            'category' => $category,
        ]);
    }
}
