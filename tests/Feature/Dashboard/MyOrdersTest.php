<?php

declare(strict_types=1);

use App\Livewire\Dashboard\MyOrders;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

test('user can access my orders page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/orders');

    $response->assertSuccessful();
    $response->assertSeeLivewire(MyOrders::class);
});

test('guest is redirected to login', function () {
    $response = $this->get('/orders');

    $response->assertRedirect(route('login'));
});

test('my orders displays user orders', function () {
    $user = User::factory()->create();

    Order::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(MyOrders::class)
        ->assertSee('My Orders');
});

test('my orders only shows user own orders', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $order1 = Order::factory()->create([
        'user_id' => $user1->id,
        'woocommerce_order_id' => '12345',
    ]);

    $order2 = Order::factory()->create([
        'user_id' => $user2->id,
        'woocommerce_order_id' => '67890',
    ]);

    Livewire::actingAs($user1)
        ->test(MyOrders::class)
        ->assertSee('12345')
        ->assertDontSee('67890');
});

test('shows empty state when no orders', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(MyOrders::class)
        ->assertSee('No orders found');
});

test('displays order information correctly', function () {
    $user = User::factory()->create();
    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'woocommerce_order_id' => 'WC-12345',
        'status' => \App\Enums\OrderStatus::Provisioned,
        'amount' => 29.99,
        'currency' => 'USD',
    ]);

    Livewire::actingAs($user)
        ->test(MyOrders::class)
        ->assertSee('WC-12345')
        ->assertSee('29.99')
        ->assertSee('USD')
        ->assertSee('Provisioned');
});

test('displays associated plan information', function () {
    $user = User::factory()->create();
    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    Livewire::actingAs($user)
        ->test(MyOrders::class)
        ->assertSee($order->subscription->plan->name);
});

test('pagination works correctly', function () {
    $user = User::factory()->create();
    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    // Create 15 orders (more than one page at 10 per page)
    Order::factory()->count(15)->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    $component = Livewire::actingAs($user)
        ->test(MyOrders::class);

    expect($component->get('orders')->count())->toBe(10);
});

test('displays correct status badge for provisioned orders', function () {
    $user = User::factory()->create();
    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'status' => \App\Enums\OrderStatus::Provisioned,
    ]);

    Livewire::actingAs($user)
        ->test(MyOrders::class)
        ->assertSee('Provisioned');
});

test('displays correct status badge for pending provisioning orders', function () {
    $user = User::factory()->create();
    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'status' => \App\Enums\OrderStatus::PendingProvisioning,
    ]);

    Livewire::actingAs($user)
        ->test(MyOrders::class)
        ->assertSee('Pending Provisioning');
});

test('displays correct status badge for provisioning failed orders', function () {
    $user = User::factory()->create();
    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'status' => \App\Enums\OrderStatus::ProvisioningFailed,
    ]);

    Livewire::actingAs($user)
        ->test(MyOrders::class)
        ->assertSee('Provisioning Failed');
});

test('orders are sorted by newest first', function () {
    $user = User::factory()->create();
    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    $oldOrder = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'woocommerce_order_id' => 'OLD-123',
        'created_at' => now()->subDays(5),
    ]);

    $newOrder = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'woocommerce_order_id' => 'NEW-456',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/orders');

    $content = $response->getContent();
    $newPos = mb_strpos($content, 'NEW-456');
    $oldPos = mb_strpos($content, 'OLD-123');

    expect($newPos)->toBeLessThan($oldPos);
});

test('displays order date correctly', function () {
    $user = User::factory()->create();
    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'created_at' => now()->setDate(2024, 1, 15),
    ]);

    Livewire::actingAs($user)
        ->test(MyOrders::class)
        ->assertSee('Jan 15, 2024');
});

test('handles orders without subscriptions gracefully', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => null,
    ])->skip();
})->skip('Orders must have subscriptions in this application');

test('displays orders with amount correctly', function () {
    $user = User::factory()->create();
    $subscription = \App\Models\Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'amount' => 49.99,
    ]);

    Livewire::actingAs($user)
        ->test(MyOrders::class)
        ->assertSee('49.99')
        ->assertSuccessful();
});
