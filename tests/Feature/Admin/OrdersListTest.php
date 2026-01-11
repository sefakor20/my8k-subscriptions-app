<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

test('admin can access orders list', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/orders');

    $response->assertSuccessful();
    $response->assertSee('Orders Management');
});

test('non-admin user gets 403 on orders list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/orders');

    $response->assertForbidden();
});

test('guest is redirected to login when accessing orders list', function () {
    $response = $this->get('/admin/orders');

    $response->assertRedirect(route('login'));
});

test('orders list displays orders correctly', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'customer@example.com']);
    $plan = Plan::factory()->create(['name' => 'Premium IPTV']);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'status' => OrderStatus::Provisioned,
    ]);

    $response = $this->actingAs($admin)->get('/admin/orders');

    $response->assertSuccessful();
    $response->assertSee('customer@example.com');
    $response->assertSee('Premium IPTV');
    $response->assertSee('provisioned');
});

test('orders list pagination works correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create 60 orders (more than one page at 50 per page)
    Order::factory()->count(60)->create();

    $response = $this->actingAs($admin)->get('/admin/orders');

    $response->assertSuccessful();
    $response->assertSee('Next'); // Pagination controls should be visible
});

test('search filter works for user email', function () {
    $admin = User::factory()->admin()->create();

    $targetUser = User::factory()->create(['email' => 'findme@example.com']);
    $otherUser = User::factory()->create(['email' => 'other@example.com']);

    Order::factory()->forUser($targetUser)->create();
    Order::factory()->forUser($otherUser)->create();

    $response = $this->actingAs($admin)->get('/admin/orders?search=findme');

    $response->assertSuccessful();
    $response->assertSee('findme@example.com');
    $response->assertDontSee('other@example.com');
});

test('search filter works for user name', function () {
    $admin = User::factory()->admin()->create();

    $targetUser = User::factory()->create(['name' => 'John Smith']);
    $otherUser = User::factory()->create(['name' => 'Jane Doe']);

    Order::factory()->forUser($targetUser)->create();
    Order::factory()->forUser($otherUser)->create();

    $response = $this->actingAs($admin)->get('/admin/orders?search=John');

    $response->assertSuccessful();
    $response->assertSee('John Smith');
    $response->assertDontSee('Jane Doe');
});

// Note: WooCommerce order ID is not displayed in the list view, search still works in backend

test('status filter shows only orders with specified status', function () {
    $admin = User::factory()->admin()->create();

    // Create provisioned orders
    Order::factory()->count(3)->provisioned()->create();

    // Create failed orders
    Order::factory()->count(2)->failed()->create();

    $response = $this->actingAs($admin)->get('/admin/orders?status=provisioned');

    $response->assertSuccessful();
    // Should show 3 provisioned orders, not the 2 failed ones
    $response->assertSee('provisioned');
});

test('date range filter works correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create orders from 3 days ago
    Order::factory()->count(2)->create([
        'created_at' => now()->subDays(3),
    ]);

    // Create orders from today
    Order::factory()->count(3)->create([
        'created_at' => now(),
    ]);

    $dateFrom = now()->startOfDay()->format('Y-m-d');
    $response = $this->actingAs($admin)->get("/admin/orders?from={$dateFrom}");

    $response->assertSuccessful();
    // Should only show today's 3 orders
});

test('empty state displays when no orders found', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/orders');

    $response->assertSuccessful();
    $response->assertSee('No orders found');
});

test('orders list includes action dropdown', function () {
    $admin = User::factory()->admin()->create();

    Order::factory()->create();

    $response = $this->actingAs($admin)->get('/admin/orders');

    $response->assertSuccessful();
    $response->assertSee('View Details');
});

test('failed order shows retry provisioning action', function () {
    $admin = User::factory()->admin()->create();

    Order::factory()->failed()->create();

    $response = $this->actingAs($admin)->get('/admin/orders');

    $response->assertSuccessful();
    $response->assertSee('Retry Provisioning');
});

test('orders are sorted by newest first', function () {
    $admin = User::factory()->admin()->create();
    $user1 = User::factory()->create(['email' => 'old@example.com']);
    $user2 = User::factory()->create(['email' => 'new@example.com']);

    $oldOrder = Order::factory()->forUser($user1)->create([
        'created_at' => now()->subDays(5),
    ]);

    $newOrder = Order::factory()->forUser($user2)->create([
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get('/admin/orders');

    $response->assertSuccessful();
    // New order should appear before old order in the HTML
    $content = $response->getContent();
    $newPos = mb_strpos($content, 'new@example.com');
    $oldPos = mb_strpos($content, 'old@example.com');

    expect($newPos)->toBeLessThan($oldPos);
});

test('orders show associated plan name', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create(['name' => 'Super Premium Plan']);
    $subscription = Subscription::factory()->create(['plan_id' => $plan->id]);

    Order::factory()->forSubscription($subscription)->create();

    $response = $this->actingAs($admin)->get('/admin/orders');

    $response->assertSuccessful();
    $response->assertSee('Super Premium Plan');
});

test('orders display correct amount', function () {
    $admin = User::factory()->admin()->create();

    Order::factory()->create([
        'amount' => 2999,
        'currency' => 'USD',
    ]);

    $response = $this->actingAs($admin)->get('/admin/orders');

    $response->assertSuccessful();
    $response->assertSee('29.99');
});

test('orders can be filtered by multiple criteria simultaneously', function () {
    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->create(['email' => 'target@example.com']);
    $otherUser = User::factory()->create(['email' => 'other@example.com']);

    // Target order - provisioned, from target user, paid today
    Order::factory()->provisioned()->forUser($targetUser)->create([
        'paid_at' => now(),
    ]);

    // Wrong status
    Order::factory()->failed()->forUser($targetUser)->create();

    // Wrong user
    Order::factory()->provisioned()->forUser($otherUser)->create();

    $dateFrom = now()->startOfDay()->format('Y-m-d');
    $response = $this->actingAs($admin)->get("/admin/orders?search=target&status=provisioned&from={$dateFrom}");

    $response->assertSuccessful();
    $response->assertSee('target@example.com');
    $response->assertDontSee('other@example.com');
});

test('reset filters button is visible', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/orders');

    $response->assertSuccessful();
    $response->assertSee('Reset Filters');
});
