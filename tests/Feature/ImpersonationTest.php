<?php

declare(strict_types=1);

use App\Livewire\Admin\CustomerDetailModal;
use App\Livewire\Admin\CustomersList;
use App\Livewire\StopImpersonation;
use App\Models\User;
use Livewire\Livewire;

test('admin can impersonate customer from customers list', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->call('impersonate', $customer->id)
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($customer->id);
    expect(session('impersonator_id'))->toBe($admin->id);
});

test('admin can impersonate customer from detail modal', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CustomerDetailModal::class)
        ->dispatch('open-customer-modal', customerId: $customer->id)
        ->call('impersonate')
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($customer->id);
    expect(session('impersonator_id'))->toBe($admin->id);
});

test('admin cannot impersonate self', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->call('impersonate', $admin->id);

    expect(auth()->id())->toBe($admin->id);
    expect(session('impersonator_id'))->toBeNull();
});

test('stop impersonation component renders when impersonating', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    session()->put('impersonator_id', $admin->id);

    Livewire::actingAs($customer)
        ->test(StopImpersonation::class)
        ->assertSee('Impersonating')
        ->assertSee($customer->email)
        ->assertSee('Stop Impersonation');
});

test('admin can stop impersonation and return to admin session', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    session()->put('impersonator_id', $admin->id);

    Livewire::actingAs($customer)
        ->test(StopImpersonation::class)
        ->call('stop')
        ->assertRedirect(route('admin.customers.index'));

    expect(auth()->id())->toBe($admin->id);
    expect(session('impersonator_id'))->toBeNull();
});

test('stop impersonation does nothing when not impersonating', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(StopImpersonation::class)
        ->call('stop')
        ->assertNoRedirect();

    expect(auth()->id())->toBe($user->id);
});

test('stop impersonation does nothing if original admin no longer exists', function () {
    $customer = User::factory()->create();

    session()->put('impersonator_id', 'non-existent-id');

    Livewire::actingAs($customer)
        ->test(StopImpersonation::class)
        ->call('stop')
        ->assertNoRedirect();

    expect(auth()->id())->toBe($customer->id);
});

test('stop impersonation does nothing if original user is not admin', function () {
    $regularUser = User::factory()->create(['is_admin' => false]);
    $customer = User::factory()->create();

    session()->put('impersonator_id', $regularUser->id);

    Livewire::actingAs($customer)
        ->test(StopImpersonation::class)
        ->call('stop')
        ->assertNoRedirect();

    expect(auth()->id())->toBe($customer->id);
});

test('impersonation banner shows in sidebar when impersonating', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    session()->put('impersonator_id', $admin->id);

    $response = $this->actingAs($customer)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertSeeLivewire(StopImpersonation::class);
});

test('impersonation banner does not show when not impersonating', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertDontSeeLivewire(StopImpersonation::class);
});

test('impersonation flow works end to end', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    // Admin impersonates customer
    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->call('impersonate', $customer->id)
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($customer->id);
    expect(session('impersonator_id'))->toBe($admin->id);

    // Customer (impersonated) can see the stop button and use it
    // Session is already set from the impersonate call above
    Livewire::actingAs($customer)
        ->test(StopImpersonation::class)
        ->call('stop')
        ->assertRedirect(route('admin.customers.index'));

    expect(auth()->id())->toBe($admin->id);
    expect(session('impersonator_id'))->toBeNull();
});
