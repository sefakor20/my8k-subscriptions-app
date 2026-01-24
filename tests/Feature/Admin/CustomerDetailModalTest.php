<?php

declare(strict_types=1);

use App\Livewire\Admin\CustomerDetailModal;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('modal opens when event is dispatched', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->assertSet('show', true)
        ->assertSet('customerId', $customer->id);
});

test('modal shows customer information', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->assertSee('Test Customer')
        ->assertSee('test@example.com')
        ->assertSee('Email Verified');
});

test('modal shows unverified badge for unverified customers', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->unverified()->create();

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->assertSee('Email Unverified');
});

test('modal shows admin badge for admin customers', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create(['name' => 'Other Admin']);

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $otherAdmin->id)
        ->assertSee('Admin');
});

test('modal shows customer statistics', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    Subscription::factory()->count(2)->create(['user_id' => $customer->id]);
    Order::factory()->count(3)->create(['user_id' => $customer->id]);

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->assertSee('Subscriptions')
        ->assertSee('Orders');
});

test('can close modal', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->assertSet('show', true)
        ->call('closeModal')
        ->assertSet('show', false)
        ->assertSet('customerId', null);
});

test('can toggle admin status from modal', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create(['is_admin' => false]);

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->call('toggleAdmin')
        ->assertDispatched('customer-updated');

    expect($customer->fresh()->is_admin)->toBeTrue();
});

test('cannot toggle own admin status from modal', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $admin->id)
        ->call('toggleAdmin');

    expect($admin->fresh()->is_admin)->toBeTrue();
});

test('can impersonate customer from modal', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->call('impersonate')
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($customer->id);
});

test('cannot impersonate self from modal', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $admin->id)
        ->call('impersonate');

    expect(auth()->id())->toBe($admin->id);
});

test('modal shows recent subscriptions', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $subscription = Subscription::factory()->create(['user_id' => $customer->id]);

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->assertSee('Recent Subscriptions')
        ->assertSee($subscription->plan->name);
});

test('modal shows recent orders', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    Order::factory()->create(['user_id' => $customer->id]);

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->assertSee('Recent Orders');
});

test('modal shows no subscriptions message when none exist', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->assertSee('No subscriptions yet');
});

test('modal shows no orders message when none exist', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->assertSee('No orders yet');
});

test('shows two factor status when enabled', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->withTwoFactor()->create();

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->assertSee('Two-Factor Auth')
        ->assertSee('Enabled');
});

test('shows two factor status when disabled', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->assertSee('Two-Factor Auth')
        ->assertSee('Disabled');
});
