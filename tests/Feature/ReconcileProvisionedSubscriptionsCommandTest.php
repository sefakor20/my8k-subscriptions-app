<?php

declare(strict_types=1);

use App\Enums\ServiceAccountStatus;
use App\Enums\SubscriptionStatus;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;

test('no subscriptions need reconciliation returns success', function () {
    $this->artisan('subscriptions:reconcile-provisioned-status')
        ->expectsOutput('Starting subscription status reconciliation...')
        ->expectsOutput('✅ No subscriptions need reconciliation')
        ->assertSuccessful();
});

test('dry run does not modify subscriptions', function () {
    $serviceAccount = ServiceAccount::factory()->create([
        'status' => ServiceAccountStatus::Active,
    ]);

    $subscription = Subscription::factory()->create([
        'service_account_id' => $serviceAccount->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $this->artisan('subscriptions:reconcile-provisioned-status --dry-run')
        ->expectsOutput('Starting subscription status reconciliation...')
        ->expectsOutput('🔍 DRY RUN MODE - No changes will be made')
        ->expectsOutputToContain('Found 1 subscription(s) with inconsistent status')
        ->assertSuccessful();

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Pending);
});

test('reconciles subscriptions with pending status but active service account', function () {
    $user = User::factory()->create();

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $serviceAccount = ServiceAccount::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'status' => ServiceAccountStatus::Active,
        'expires_at' => now()->addDays(30),
    ]);

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    $this->artisan('subscriptions:reconcile-provisioned-status')
        ->assertSuccessful();

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Active);
});

test('respects limit option', function () {
    $user = User::factory()->create();

    // Create 3 subscriptions with inconsistent state
    foreach (range(1, 3) as $i) {
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::Pending,
        ]);

        $serviceAccount = ServiceAccount::factory()->create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'status' => ServiceAccountStatus::Active,
            'expires_at' => now()->addDays(30),
        ]);

        $subscription->update(['service_account_id' => $serviceAccount->id]);
    }

    $this->artisan('subscriptions:reconcile-provisioned-status --limit=2')
        ->assertSuccessful();

    // Verify only 2 were updated
    $activeCount = Subscription::where('status', SubscriptionStatus::Active)->count();
    $pendingCount = Subscription::where('status', SubscriptionStatus::Pending)->count();

    expect($activeCount)->toBe(2);
    expect($pendingCount)->toBe(1);
});

test('updates both subscription and service account if needed', function () {
    $user = User::factory()->create();

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $serviceAccount = ServiceAccount::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'status' => ServiceAccountStatus::Suspended,  // Not Active
        'expires_at' => now()->addDays(30),
    ]);

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    $this->artisan('subscriptions:reconcile-provisioned-status')
        ->assertSuccessful();

    $subscription->refresh();
    $serviceAccount->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Active);
    expect($serviceAccount->status)->toBe(ServiceAccountStatus::Active);
});

test('handles orphaned service_account_id gracefully', function () {
    $user = User::factory()->create();

    // Create a ServiceAccount, then delete it to create orphaned reference
    $serviceAccount = ServiceAccount::factory()->create([
        'user_id' => $user->id,
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'service_account_id' => $serviceAccount->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    // Delete the ServiceAccount to orphan the subscription
    $serviceAccount->delete();

    $this->artisan('subscriptions:reconcile-provisioned-status')
        ->assertSuccessful();

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Pending);  // Should not be updated
});

test('skips subscriptions with expired service accounts', function () {
    $user = User::factory()->create();

    $serviceAccount = ServiceAccount::factory()->create([
        'user_id' => $user->id,
        'status' => ServiceAccountStatus::Active,
        'expires_at' => now()->subDay(),  // Expired yesterday
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'service_account_id' => $serviceAccount->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    $this->artisan('subscriptions:reconcile-provisioned-status')
        ->assertSuccessful();

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Pending);  // Should not be updated
});

test('does not affect subscriptions without service_account_id', function () {
    $subscription = Subscription::factory()->create([
        'service_account_id' => null,
        'status' => SubscriptionStatus::Pending,
    ]);

    $this->artisan('subscriptions:reconcile-provisioned-status')
        ->expectsOutput('✅ No subscriptions need reconciliation')
        ->assertSuccessful();

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Pending);
});

test('does not affect active subscriptions', function () {
    $serviceAccount = ServiceAccount::factory()->create();

    $subscription = Subscription::factory()->create([
        'service_account_id' => $serviceAccount->id,
        'status' => SubscriptionStatus::Active,  // Already active
    ]);

    $this->artisan('subscriptions:reconcile-provisioned-status')
        ->expectsOutput('✅ No subscriptions need reconciliation')
        ->assertSuccessful();

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Active);
});

test('processes multiple subscriptions correctly', function () {
    $user = User::factory()->create();

    // Create 5 subscriptions with inconsistent state
    foreach (range(1, 5) as $i) {
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::Pending,
        ]);

        $serviceAccount = ServiceAccount::factory()->create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'status' => ServiceAccountStatus::Active,
            'expires_at' => now()->addDays(30),
        ]);

        $subscription->update(['service_account_id' => $serviceAccount->id]);
    }

    $this->artisan('subscriptions:reconcile-provisioned-status')
        ->assertSuccessful();

    $activeCount = Subscription::where('status', SubscriptionStatus::Active)->count();
    expect($activeCount)->toBe(5);
});
