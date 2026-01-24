<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StreamingAppPlatform;
use App\Enums\StreamingAppType;
use App\Models\StreamingApp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StreamingApp>
 */
class StreamingAppFactory extends Factory
{
    protected $model = StreamingApp::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true) . ' Player',
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(StreamingAppType::cases()),
            'platform' => fake()->randomElement(StreamingAppPlatform::cases()),
            'version' => 'v' . fake()->semver(),
            'download_url' => fake()->url(),
            'downloader_code' => null,
            'short_url' => null,
            'is_recommended' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function recommended(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_recommended' => true,
        ]);
    }

    public function android(): static
    {
        return $this->state(fn(array $attributes) => [
            'platform' => StreamingAppPlatform::Android,
        ]);
    }

    public function windows(): static
    {
        return $this->state(fn(array $attributes) => [
            'platform' => StreamingAppPlatform::Windows,
        ]);
    }

    public function smartTv(): static
    {
        return $this->state(fn(array $attributes) => [
            'platform' => StreamingAppPlatform::SmartTV,
        ]);
    }

    public function mag(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => StreamingAppType::MAG,
        ]);
    }

    public function m3u(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => StreamingAppType::M3U,
        ]);
    }

    public function withDownloaderCode(): static
    {
        return $this->state(fn(array $attributes) => [
            'downloader_code' => (string) fake()->numberBetween(100000, 999999),
            'short_url' => '2u.pw/' . fake()->lexify('???'),
        ]);
    }
}
