<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

test('admin can access subscriptions list', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/subscriptions');

    $response->assertSuccessful();
    $response->assertSee('Subscriptions Management');
});

test('non-admin user gets 403 on subscriptions list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/subscriptions');

    $response->assertForbidden();
});

test('guest is redirected to login when accessing subscriptions list', function () {
    $response = $this->get('/admin/subscriptions');

    $response->assertRedirect(route('login'));
});

test('subscriptions list displays subscriptions correctly', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create(['name' => 'Premium IPTV']);
    $user = User::factory()->create(['email' => 'customer@example.com']);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($admin)->get('/admin/subscriptions');

    $response->assertSuccessful();
    $response->assertSee('customer@example.com');
    $response->assertSee('Premium IPTV');
    $response->assertSee('active');
});

test('subscriptions list pagination works correctly', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    // Create 60 subscriptions (more than one page at 50 per page)
    Subscription::factory()->count(60)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($admin)->get('/admin/subscriptions');

    $response->assertSuccessful();
    $response->assertSee('Next'); // Pagination controls should be visible
});

test('search filter works for user email', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $targetUser = User::factory()->create(['email' => 'findme@example.com']);
    $otherUser = User::factory()->create(['email' => 'other@example.com']);

    Subscription::factory()->create([
        'user_id' => $targetUser->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->create([
        'user_id' => $otherUser->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($admin)->get('/admin/subscriptions?search=findme');

    $response->assertSuccessful();
    $response->assertSee('findme@example.com');
    $response->assertDontSee('other@example.com');
});

test('search filter works for user name', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    $targetUser = User::factory()->create(['name' => 'John Smith']);
    $otherUser = User::factory()->create(['name' => 'Jane Doe']);

    Subscription::factory()->create([
        'user_id' => $targetUser->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->create([
        'user_id' => $otherUser->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($admin)->get('/admin/subscriptions?search=John');

    $response->assertSuccessful();
    $response->assertSee('John Smith');
    $response->assertDontSee('Jane Doe');
});

test('status filter shows only subscriptions with specified status', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    // Create active subscriptions
    Subscription::factory()->count(3)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    // Create expired subscriptions
    Subscription::factory()->count(2)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Expired,
    ]);

    $response = $this->actingAs($admin)->get('/admin/subscriptions?status=active');

    $response->assertSuccessful();
    // Should show 3 active subscriptions, not the 2 expired ones
    $response->assertSee('active');
});

test('plan filter shows only subscriptions for specified plan', function () {
    $admin = User::factory()->admin()->create();
    $plan1 = Plan::factory()->create(['name' => 'Basic Plan']);
    $plan2 = Plan::factory()->create(['name' => 'Premium Plan']);
    $user1 = User::factory()->create(['email' => 'basic@example.com']);
    $user2 = User::factory()->create(['email' => 'premium@example.com']);

    // Create subscriptions for plan1
    Subscription::factory()->count(3)->create([
        'user_id' => $user1->id,
        'plan_id' => $plan1->id,
        'status' => SubscriptionStatus::Active,
    ]);

    // Create subscriptions for plan2
    Subscription::factory()->count(2)->create([
        'user_id' => $user2->id,
        'plan_id' => $plan2->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($admin)->get("/admin/subscriptions?plan={$plan1->id}");

    $response->assertSuccessful();
    $response->assertSee('basic@example.com');
    $response->assertDontSee('premium@example.com');
});

test('date range filter works correctly', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    // Create subscriptions from 3 days ago
    Subscription::factory()->count(2)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'created_at' => now()->subDays(3),
    ]);

    // Create subscriptions from today
    Subscription::factory()->count(3)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'created_at' => now(),
    ]);

    $dateFrom = now()->startOfDay()->format('Y-m-d');
    $response = $this->actingAs($admin)->get("/admin/subscriptions?from={$dateFrom}");

    $response->assertSuccessful();
    // Should only show today's 3 subscriptions
});

test('empty state displays when no subscriptions found', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/subscriptions');

    $response->assertSuccessful();
    $response->assertSee('No subscriptions found');
});

test('subscriptions list includes action dropdown', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($admin)->get('/admin/subscriptions');

    $response->assertSuccessful();
    $response->assertSee('View Details');
    $response->assertSee('Retry Provisioning');
});

test('active subscription shows suspend action', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($admin)->get('/admin/subscriptions');

    $response->assertSuccessful();
    $response->assertSee('Suspend');
});

test('suspended subscription shows reactivate action', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Suspended,
    ]);

    $response = $this->actingAs($admin)->get('/admin/subscriptions');

    $response->assertSuccessful();
    $response->assertSee('Reactivate');
});

test('all subscriptions show cancel action', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($admin)->get('/admin/subscriptions');

    $response->assertSuccessful();
    $response->assertSee('Cancel Subscription');
});
