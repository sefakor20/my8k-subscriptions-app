<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Jobs\ProvisionNewAccountJob;
use App\Livewire\Admin\OrderDetailModal;
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
    $order = Order::factory()->create();

    Livewire::actingAs($admin)
        ->test(OrderDetailModal::class)
        ->dispatch('open-order-modal', orderId: $order->id)
        ->assertSet('show', true)
        ->assertSet('orderId', $order->id);
});

test('modal displays order details correctly', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $plan = Plan::factory()->create(['name' => 'Premium Plan']);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'amount' => 2999,
        'currency' => 'USD',
    ]);

    Livewire::actingAs($admin)
        ->test(OrderDetailModal::class)
        ->dispatch('open-order-modal', orderId: $order->id)
        ->assertSee('John Doe')
        ->assertSee('john@example.com')
        ->assertSee('Premium Plan')
        ->assertSee('29.99')
        ->assertSee('USD');
});

test('modal displays subscription information when available', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create(['name' => 'Premium Plan']);
    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
    ]);

    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
    ]);

    Livewire::actingAs($admin)
        ->test(OrderDetailModal::class)
        ->dispatch('open-order-modal', orderId: $order->id)
        ->assertSee('Subscription Information')
        ->assertSee('Premium Plan');
});

test('modal displays service account when available', function () {
    $admin = User::factory()->admin()->create();
    $subscription = Subscription::factory()->create();

    ServiceAccount::factory()->create([
        'subscription_id' => $subscription->id,
        'username' => 'testuser123',
    ]);

    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
    ]);

    Livewire::actingAs($admin)
        ->test(OrderDetailModal::class)
        ->dispatch('open-order-modal', orderId: $order->id)
        ->assertSee('Service Account')
        ->assertSee('testuser123');
});

test('modal displays recent provisioning activity', function () {
    $admin = User::factory()->admin()->create();
    $subscription = Subscription::factory()->create();

    ProvisioningLog::factory()->count(3)->create([
        'subscription_id' => $subscription->id,
        'status' => 'success',
    ]);

    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
    ]);

    Livewire::actingAs($admin)
        ->test(OrderDetailModal::class)
        ->dispatch('open-order-modal', orderId: $order->id)
        ->assertSee('Recent Provisioning Activity');
});

test('modal displays webhook payload', function () {
    $admin = User::factory()->admin()->create();

    $order = Order::factory()->create([
        'webhook_payload' => [
            'event' => 'order.completed',
            'order_id' => '12345',
        ],
    ]);

    Livewire::actingAs($admin)
        ->test(OrderDetailModal::class)
        ->dispatch('open-order-modal', orderId: $order->id)
        ->assertSee('Webhook Payload')
        ->assertSee('order.completed');
});

test('retry provisioning dispatches job when subscription exists', function () {
    Queue::fake();

    $admin = User::factory()->admin()->create();
    $subscription = Subscription::factory()->create();
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'status' => OrderStatus::ProvisioningFailed,
    ]);

    Livewire::actingAs($admin)
        ->test(OrderDetailModal::class)
        ->dispatch('open-order-modal', orderId: $order->id)
        ->call('retryProvisioning');

    Queue::assertPushed(ProvisionNewAccountJob::class, function ($job) use ($order, $subscription) {
        return $job->orderId === $order->id && $job->subscriptionId === $subscription->id;
    });
});

// Note: subscription_id is required in the orders table, so we cannot test null scenario

test('modal closes after retry provisioning', function () {
    $admin = User::factory()->admin()->create();
    $subscription = Subscription::factory()->create();
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'status' => OrderStatus::ProvisioningFailed,
    ]);

    Livewire::actingAs($admin)
        ->test(OrderDetailModal::class)
        ->dispatch('open-order-modal', orderId: $order->id)
        ->assertSet('show', true)
        ->call('retryProvisioning')
        ->assertSet('show', false);
});

test('close modal resets state', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create();

    Livewire::actingAs($admin)
        ->test(OrderDetailModal::class)
        ->dispatch('open-order-modal', orderId: $order->id)
        ->call('closeModal')
        ->assertSet('show', false)
        ->assertSet('orderId', null);
});
