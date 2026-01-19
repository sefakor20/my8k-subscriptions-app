<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

it('shows no subscriptions due when none are due', function () {
    $this->artisan('subscriptions:renew')
        ->expectsOutput('Finding subscriptions due for renewal...')
        ->expectsOutput('No subscriptions due for renewal.')
        ->assertSuccessful();
});

it('lists subscriptions due for renewal in dry-run mode', function () {
    $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
    $plan = Plan::factory()->create(['name' => 'Premium Plan', 'duration_days' => 30]);
    Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addHours(12), // Due within 1 day
            'next_renewal_at' => now()->subHour(), // Past due
        ]);

    $this->artisan('subscriptions:renew --dry-run')
        ->expectsOutput('Running in dry-run mode - no changes will be made')
        ->expectsOutput('Finding subscriptions due for renewal...')
        ->expectsOutput('Found 1 subscription(s) to process.')
        ->assertSuccessful();
});

it('does not pick up subscriptions with auto_renew disabled', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => false, // Disabled
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addHours(12),
            'next_renewal_at' => now()->subHour(),
        ]);

    $this->artisan('subscriptions:renew')
        ->expectsOutput('No subscriptions due for renewal.')
        ->assertSuccessful();
});

it('does not pick up inactive subscriptions', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Cancelled, // Not active
            'expires_at' => now()->addHours(12),
            'next_renewal_at' => now()->subHour(),
        ]);

    $this->artisan('subscriptions:renew')
        ->expectsOutput('No subscriptions due for renewal.')
        ->assertSuccessful();
});

it('does not pick up subscriptions not yet due', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDays(10), // Not due yet
            'next_renewal_at' => now()->addDays(9),
        ]);

    $this->artisan('subscriptions:renew')
        ->expectsOutput('No subscriptions due for renewal.')
        ->assertSuccessful();
});

it('can renew a specific subscription by id', function () {
    $user = User::factory()->create(['name' => 'Specific User']);
    $plan = Plan::factory()->create(['name' => 'Test Plan', 'duration_days' => 30]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addHours(12),
            'next_renewal_at' => now()->subHour(),
        ]);

    // Without authorization data, it will fail but we can verify the specific subscription was targeted
    $this->artisan("subscriptions:renew --subscription={$subscription->id}")
        ->expectsOutput('Found 1 subscription(s) to process.')
        ->assertFailed(); // Will fail because no order with auth data exists
});

it('respects the limit option', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);

    // Create 5 subscriptions due for renewal
    for ($i = 0; $i < 5; $i++) {
        Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'auto_renew' => true,
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addHours(12),
                'next_renewal_at' => now()->subHour(),
            ]);
    }

    $this->artisan('subscriptions:renew --dry-run --limit=2')
        ->expectsOutput('Found 2 subscription(s) to process.')
        ->assertSuccessful();
});

it('picks up subscriptions expiring within 1 day', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addHours(20), // Within 1 day
            'next_renewal_at' => now()->addDays(5), // Next renewal not yet due, but expiry is
        ]);

    $this->artisan('subscriptions:renew --dry-run')
        ->expectsOutput('Found 1 subscription(s) to process.')
        ->assertSuccessful();
});
