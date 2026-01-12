<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupportMessage>
 */
class SupportMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_ticket_id' => SupportTicket::factory(),
            'user_id' => User::factory(),
            'message' => fake()->paragraphs(3, true),
            'is_internal_note' => false,
            'attachments' => null,
        ];
    }

    public function fromAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => User::factory()->admin(),
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_internal_note' => true,
            'user_id' => User::factory()->admin(),
        ]);
    }

    public function withAttachments(): static
    {
        return $this->state(fn(array $attributes) => [
            'attachments' => [
                'ticket-screenshot.png',
                'error-log.txt',
            ],
        ]);
    }
}
