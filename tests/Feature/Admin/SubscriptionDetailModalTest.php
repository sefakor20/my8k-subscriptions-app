<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Jobs\ProvisionNewAccountJob;
use App\Livewire\Admin\SubscriptionDetailModal;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ProvisioningLog;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('modal opens when dispatched open event', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->assertSet('show', true)
        ->assertSet('subscriptionId', $subscription->id);
});

test('modal displays subscription details correctly', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create(['name' => 'Premium Plan']);
    $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->assertSee('John Doe')
        ->assertSee('john@example.com')
        ->assertSee('Premium Plan');
});

test('modal displays service account credentials when available', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    ServiceAccount::factory()->create([
        'subscription_id' => $subscription->id,
        'username' => 'testuser123',
        'password' => 'secret123',
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->assertSee('testuser123')
        ->assertSee('Service Account Credentials');
});

test('modal hides password by default', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    ServiceAccount::factory()->create([
        'subscription_id' => $subscription->id,
        'username' => 'testuser123',
        'password' => 'secret123',
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->assertDontSee('secret123')
        ->assertSee('••••••••••••');
});

test('modal can toggle password visibility', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    ServiceAccount::factory()->create([
        'subscription_id' => $subscription->id,
        'username' => 'testuser123',
        'password' => 'secret123',
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->assertSet('showPassword', false)
        ->call('togglePassword')
        ->assertSet('showPassword', true)
        ->assertSee('secret123');
});

test('modal displays recent orders', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Order::factory()->count(3)->create([
        'subscription_id' => $subscription->id,
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->assertSee('Recent Orders');
});

test('modal displays recent provisioning logs', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    ProvisioningLog::factory()->count(3)->create([
        'subscription_id' => $subscription->id,
        'status' => 'success',
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->assertSee('Recent Provisioning Activity');
});

test('retry provisioning dispatches job', function () {
    Queue::fake();

    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    // Create an order for the subscription
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->call('retryProvisioning');

    Queue::assertPushed(ProvisionNewAccountJob::class, function ($job) use ($order, $subscription) {
        return $job->orderId === $order->id && $job->subscriptionId === $subscription->id;
    });
});

test('extend subscription updates expires_at date', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $originalExpiresAt = now()->addDays(30);
    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => $originalExpiresAt,
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->set('extendDays', 15)
        ->call('extend');

    // Check that the expiry date has been extended by at least 14 days (allowing for time drift)
    expect($subscription->fresh()->expires_at->greaterThan($originalExpiresAt->copy()->addDays(14)))->toBeTrue();
});

test('suspend subscription changes status to suspended', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->call('suspend');

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Suspended);
    expect($subscription->fresh()->suspended_at)->not->toBeNull();
});

test('reactivate subscription changes status to active', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Suspended,
        'suspended_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->call('reactivate');

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
    expect($subscription->fresh()->suspended_at)->toBeNull();
});

test('cancel subscription changes status to cancelled', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->call('cancel');

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Cancelled);
    expect($subscription->fresh()->cancelled_at)->not->toBeNull();
});

test('modal closes after action is performed', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->assertSet('show', true)
        ->call('suspend')
        ->assertSet('show', false);
});

test('close modal resets state', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($admin)
        ->test(SubscriptionDetailModal::class)
        ->dispatch('open-subscription-modal', subscriptionId: $subscription->id)
        ->set('showPassword', true)
        ->set('extendDays', 60)
        ->call('closeModal')
        ->assertSet('show', false)
        ->assertSet('subscriptionId', null)
        ->assertSet('showPassword', false)
        ->assertSet('extendDays', 30);
});
