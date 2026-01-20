<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
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
            'category' => fake()->randomElement(NotificationCategory::configurable()),
            'channel' => 'mail',
            'is_enabled' => true,
        ];
    }

    /**
     * Indicate the preference is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_enabled' => false,
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
