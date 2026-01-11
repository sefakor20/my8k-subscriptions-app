<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\MySubscriptions;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('user can access my subscriptions page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSeeLivewire(MySubscriptions::class);
});

test('guest is redirected to login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect(route('login'));
});

test('my subscriptions displays user subscriptions', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['name' => 'Premium IPTV']);

    Subscription::factory()->count(3)->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    Livewire::actingAs($user)
        ->test(MySubscriptions::class)
        ->assertSee('Premium IPTV')
        ->assertSee('My Subscriptions');
});

test('my subscriptions shows statistics', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Create active subscription
    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    // Create expired subscription
    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Expired,
    ]);

    Livewire::actingAs($user)
        ->test(MySubscriptions::class)
        ->assertSee('Active')
        ->assertSee('Expired')
        ->assertSee('Total');
});

test('my subscriptions only shows user own subscriptions', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $plan = Plan::factory()->create(['name' => 'User1 Plan']);

    Subscription::factory()->create([
        'user_id' => $user1->id,
        'plan_id' => $plan->id,
    ]);

    $plan2 = Plan::factory()->create(['name' => 'User2 Plan']);
    Subscription::factory()->create([
        'user_id' => $user2->id,
        'plan_id' => $plan2->id,
    ]);

    Livewire::actingAs($user1)
        ->test(MySubscriptions::class)
        ->assertSee('User1 Plan')
        ->assertDontSee('User2 Plan');
});

test('can filter subscriptions by status', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Expired,
    ]);

    Livewire::actingAs($user)
        ->test(MySubscriptions::class)
        ->set('statusFilter', SubscriptionStatus::Active->value)
        ->assertSee('active');
});

test('can reset filters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(MySubscriptions::class)
        ->set('statusFilter', SubscriptionStatus::Active->value)
        ->call('resetFilters')
        ->assertSet('statusFilter', '');
});

test('shows empty state when no subscriptions', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(MySubscriptions::class)
        ->assertSee('No subscriptions found');
});

test('can open subscription detail modal', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(MySubscriptions::class)
        ->call('showDetail', $subscription->id)
        ->assertDispatched('open-subscription-detail');
});

test('displays subscription expiry information', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['name' => 'Test Plan']);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'expires_at' => now()->addDays(5),
    ]);

    Livewire::actingAs($user)
        ->test(MySubscriptions::class)
        ->assertSee('Test Plan')
        ->assertSee('Expires');
});

test('shows expiring soon badge for subscriptions expiring within 7 days', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(5),
    ]);

    Livewire::actingAs($user)
        ->test(MySubscriptions::class)
        ->assertSee('Expiring Soon');
});

test('pagination works correctly', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Create 15 subscriptions (more than one page at 10 per page)
    Subscription::factory()->count(15)->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    $component = Livewire::actingAs($user)
        ->test(MySubscriptions::class);

    expect($component->get('subscriptions')->count())->toBe(10);
});

test('displays plan features', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'name' => 'Premium Plan',
        'features' => ['HD Quality', '4K Streaming', '24/7 Support'],
    ]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    Livewire::actingAs($user)
        ->test(MySubscriptions::class)
        ->assertSee('HD Quality')
        ->assertSee('4K Streaming')
        ->assertSee('24/7 Support');
});

test('displays service account username when available', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->withServiceAccount()->create([
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(MySubscriptions::class)
        ->assertSee($subscription->serviceAccount->username);
});
