<?php

declare(strict_types=1);

use App\Enums\ServiceAccountStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;

test('expires subscriptions that have passed their expiration date', function (): void {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Create an expired subscription with service account
    $expiredSubscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->subDays(5),
    ]);

    $serviceAccount = ServiceAccount::factory()->create([
        'subscription_id' => $expiredSubscription->id,
        'user_id' => $user->id,
        'status' => ServiceAccountStatus::Active,
    ]);

    $expiredSubscription->update(['service_account_id' => $serviceAccount->id]);

    // Create an active subscription (not expired)
    $activeSubscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(30),
    ]);

    // Run command
    $this->artisan('subscriptions:expire')
        ->assertExitCode(0);

    // Verify expired subscription was updated
    $expiredSubscription->refresh();
    expect($expiredSubscription->status)->toBe(SubscriptionStatus::Expired)
        ->and($expiredSubscription->auto_renew)->toBeFalse();

    // Verify service account was marked as expired
    $serviceAccount->refresh();
    expect($serviceAccount->status)->toBe(ServiceAccountStatus::Expired);

    // Verify active subscription was not touched
    $activeSubscription->refresh();
    expect($activeSubscription->status)->toBe(SubscriptionStatus::Active);
});

test('handles expired subscriptions without service account', function (): void {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Create expired subscription without service account
    $expiredSubscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->subDay(),
        'service_account_id' => null,
    ]);

    // Run command
    $this->artisan('subscriptions:expire')
        ->expectsOutput('Found 1 expired subscription(s)')
        ->assertExitCode(0);

    // Verify subscription was marked as expired
    $expiredSubscription->refresh();
    expect($expiredSubscription->status)->toBe(SubscriptionStatus::Expired)
        ->and($expiredSubscription->auto_renew)->toBeFalse();
});

test('dry run mode does not make changes', function (): void {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $expiredSubscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->subDays(3),
    ]);

    // Run command with dry-run flag
    $this->artisan('subscriptions:expire --dry-run')
        ->expectsOutputToContain('DRY RUN MODE')
        ->expectsOutput('Found 1 expired subscription(s)')
        ->assertExitCode(0);

    // Verify subscription was NOT updated
    $expiredSubscription->refresh();
    expect($expiredSubscription->status)->toBe(SubscriptionStatus::Active);
});

test('limit option restricts number of processed subscriptions', function (): void {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Create 5 expired subscriptions
    for ($i = 0; $i < 5; $i++) {
        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->subDays($i + 1),
        ]);
    }

    // Run command with limit of 2
    $this->artisan('subscriptions:expire --limit=2')
        ->expectsOutput('Found 2 expired subscription(s)')
        ->assertExitCode(0);

    // Verify only 2 were expired
    $expiredCount = Subscription::where('status', SubscriptionStatus::Expired)->count();
    expect($expiredCount)->toBe(2);
});

test('returns success when no expired subscriptions found', function (): void {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Create only active subscriptions
    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(30),
    ]);

    // Run command
    $this->artisan('subscriptions:expire')
        ->expectsOutputToContain('No expired subscriptions found')
        ->assertExitCode(0);
});
