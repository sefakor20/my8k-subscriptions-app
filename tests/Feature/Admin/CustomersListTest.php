<?php

declare(strict_types=1);

use App\Livewire\Admin\CustomersList;
use App\Models\User;
use Livewire\Livewire;

test('admin can access customers list', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/customers');

    $response->assertSuccessful();
    $response->assertSee('Customers');
});

test('non-admin user gets 403 on customers list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/customers');

    $response->assertForbidden();
});

test('guest is redirected to login when accessing customers list', function () {
    $response = $this->get('/admin/customers');

    $response->assertRedirect(route('login'));
});

test('customers list displays customers correctly', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $response = $this->actingAs($admin)->get('/admin/customers');

    $response->assertSuccessful();
    $response->assertSee('John Doe');
    $response->assertSee('john@example.com');
});

test('can search customers by name', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'John Doe', 'email' => 'john@test.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@test.com']);

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

test('can search customers by email', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@test.com']);

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->set('search', 'john@example')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

test('can filter verified customers', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'Verified User', 'email_verified_at' => now()]);
    User::factory()->unverified()->create(['name' => 'Unverified User']);

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->set('verifiedFilter', 'verified')
        ->assertSee('Verified User')
        ->assertDontSee('Unverified User');
});

test('can filter unverified customers', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'Verified User', 'email_verified_at' => now()]);
    User::factory()->unverified()->create(['name' => 'Unverified User']);

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->set('verifiedFilter', 'unverified')
        ->assertSee('Unverified User')
        ->assertDontSee('Verified User');
});

test('can filter admin users', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin User']);
    User::factory()->create(['name' => 'Regular Customer']);

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->set('roleFilter', 'admin')
        ->assertSee('Admin User')
        ->assertDontSee('Regular Customer');
});

test('can filter customer users', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin User']);
    User::factory()->create(['name' => 'Regular Customer']);

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->set('roleFilter', 'customer')
        ->assertSee('Regular Customer')
        ->assertDontSee('Admin User');
});

test('can filter by date range', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create([
        'name' => 'Old User',
        'created_at' => now()->subMonths(3),
    ]);
    User::factory()->create([
        'name' => 'New User',
        'created_at' => now()->subDays(5),
    ]);

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->set('dateFrom', now()->subWeek()->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'))
        ->assertSee('New User')
        ->assertDontSee('Old User');
});

test('can reset filters', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'Jane Smith']);

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->set('search', 'John')
        ->call('resetFilters')
        ->assertSee('John Doe')
        ->assertSee('Jane Smith');
});

test('can toggle admin status', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create(['is_admin' => false]);

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->call('toggleAdmin', $customer->id);

    expect($customer->fresh()->is_admin)->toBeTrue();
});

test('cannot toggle own admin status', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->call('toggleAdmin', $admin->id);

    expect($admin->fresh()->is_admin)->toBeTrue();
});

test('view details dispatches modal event', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->call('showDetail', $customer->id)
        ->assertDispatched('open-customer-modal');
});

test('impersonate logs in as customer', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->call('impersonate', $customer->id)
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($customer->id);
});

test('cannot impersonate self', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->call('impersonate', $admin->id);

    expect(auth()->id())->toBe($admin->id);
});

test('shows admin badge for admin users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create(['name' => 'Another Admin']);

    $response = $this->actingAs($admin)->get('/admin/customers');

    $response->assertSuccessful();
    $response->assertSee('Admin');
});

test('shows verified badge for verified users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create([
        'name' => 'Verified User',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get('/admin/customers');

    $response->assertSuccessful();
    $response->assertSee('Verified');
});

test('shows unverified badge for unverified users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->unverified()->create(['name' => 'Unverified User']);

    $response = $this->actingAs($admin)->get('/admin/customers');

    $response->assertSuccessful();
    $response->assertSee('Unverified');
});

test('empty state displays when no customers match filters', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CustomersList::class)
        ->set('search', 'nonexistent-user-xyz-123')
        ->assertSee('No customers found');
});
