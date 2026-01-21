<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\SubscriptionDetail;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('modal opens when event is dispatched', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSet('show', true)
        ->assertSet('subscriptionId', $subscription->id);
});

test('modal loads subscription data correctly', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->withServiceAccount()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSee($subscription->plan->name);
});

test('modal displays subscription details', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSee($subscription->plan->name)
        ->assertSee('Subscription Details');
});

test('user cannot view other users subscriptions', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $subscription = Subscription::factory()->create(['user_id' => $user2->id]);

    Livewire::actingAs($user1)
        ->test(SubscriptionDetail::class)
        ->call('openModal', subscriptionId: $subscription->id)
        ->assertForbidden();
});

test('credentials are locked by default', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->withServiceAccount()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSet('credentialsUnlocked', false)
        ->assertSee('Credentials Locked');
});

test('can unlock credentials with password confirmation', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->withServiceAccount()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->call('unlockCredentials')
        ->assertSet('credentialsUnlocked', true);
});

test('displays service account credentials after unlocking', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->withServiceAccount()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->call('unlockCredentials')
        ->assertSet('credentialsUnlocked', true);

    $component->assertSee($subscription->serviceAccount->username);
});

test('can toggle password visibility', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->withServiceAccount()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->call('unlockCredentials')
        ->assertSet('showPassword', false)
        ->call('togglePassword')
        ->assertSet('showPassword', true)
        ->call('togglePassword')
        ->assertSet('showPassword', false);
});

test('generates correct M3U URL', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->withServiceAccount()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id);

    $m3uUrl = $component->instance()->getM3uUrl();

    expect($m3uUrl)->toContain('get.php')
        ->toContain('username=' . $subscription->serviceAccount->username)
        ->toContain('password=' . $subscription->serviceAccount->password)
        ->toContain('type=m3u_plus');
});

test('generates correct EPG URL', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->withServiceAccount()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id);

    $epgUrl = $component->instance()->getEpgUrl();

    expect($epgUrl)->toContain('xmltv.php')
        ->toContain('username=' . $subscription->serviceAccount->username)
        ->toContain('password=' . $subscription->serviceAccount->password);
});

test('modal closes when closeModal is called', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSet('show', true)
        ->call('closeModal')
        ->assertSet('show', false);
});

test('shows pending message for pending subscriptions', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSee('Provisioning in Progress');
});

test('shows expired message for expired subscriptions', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Expired,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSee('Subscription Expired');
});

test('displays plan features when available', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    $subscription->plan->update(['features' => ['HD Quality', '4K Support']]);
    $subscription->refresh();

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSee('HD Quality')
        ->assertSee('4K Support');
});

test('displays order information when available', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    // Create an order for the subscription
    Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSee('Order Information')
        ->assertSee($subscription->orders->first()->woocommerce_order_id);
});

test('modal resets state when closed', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->withServiceAccount()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->call('unlockCredentials')
        ->call('togglePassword')
        ->call('closeModal')
        ->assertSet('show', false)
        ->assertSet('subscriptionId', null)
        ->assertSet('subscription', null)
        ->assertSet('credentialsUnlocked', false)
        ->assertSet('showPassword', false);
});

test('does not display credentials for non-active subscriptions', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->withServiceAccount()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Suspended,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertDontSee('Unlock Credentials');
});

test('customer can toggle auto renewal on', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
        'auto_renew' => false,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->call('toggleAutoRenewal');

    expect($subscription->fresh()->auto_renew)->toBeTrue();
});

test('customer can toggle auto renewal off', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
        'auto_renew' => true,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->call('toggleAutoRenewal');

    expect($subscription->fresh()->auto_renew)->toBeFalse();
});

test('auto renewal toggle requires active subscription', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Expired,
        'auto_renew' => false,
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->call('toggleAutoRenewal');

    // Should not change since subscription is expired
    expect($subscription->fresh()->auto_renew)->toBeFalse();
});

test('user cannot toggle auto renewal for other users subscription', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user2->id,
        'status' => SubscriptionStatus::Active,
        'auto_renew' => true,
    ]);

    $component = Livewire::actingAs($user1)
        ->test(SubscriptionDetail::class);

    // Manually set subscription to bypass openModal authorization
    $component->set('subscriptionId', $subscription->id);
    $component->set('subscription', $subscription);

    $component->call('toggleAutoRenewal')
        ->assertForbidden();
});

test('renewal status displays correctly for active subscriptions', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
        'auto_renew' => true,
        'expires_at' => now()->addDays(30),
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSee('Renewal Settings')
        ->assertSee('Auto-Renewal')
        ->assertSee('Your subscription will renew automatically');
});

test('activity timeline shows subscription events', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
    ]);

    // Create an order with payment
    Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'paid_at' => now()->subDays(5),
        'provisioned_at' => now()->subDays(5),
    ]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id);

    $timeline = $component->instance()->activityTimeline;

    expect($timeline)->not->toBeEmpty();
    expect($timeline->pluck('type')->toArray())->toContain('created');
});

test('renew now button appears for expired subscriptions', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Expired,
        'expires_at' => now()->subDays(5),
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSee('Renew Now')
        ->assertSee('Your subscription has expired');
});

test('renew now button appears for expiring soon subscriptions', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(2),
    ]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id);

    expect($component->instance()->canRenew())->toBeTrue();
});

test('renewal url is generated correctly', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Expired,
    ]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id);

    $renewalUrl = $component->instance()->getRenewalUrl();

    expect($renewalUrl)->toContain('checkout/plan')
        ->toContain($subscription->plan->id);
});

test('displays last renewal date when available', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
        'last_renewal_at' => now()->subDays(30),
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSee('Last Renewed');
});

test('displays credit balance when available', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::Active,
        'credit_balance' => 25.50,
        'currency' => 'GHS',
    ]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->dispatch('open-subscription-detail', subscriptionId: $subscription->id)
        ->assertSee('Credit Balance')
        ->assertSee('25.50');
});
