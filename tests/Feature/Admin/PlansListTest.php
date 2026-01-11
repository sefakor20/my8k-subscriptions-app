<?php

declare(strict_types=1);

use App\Livewire\Admin\PlansList;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('admin can access plans list', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('Plans Management');
});

test('non-admin user gets 403 on plans list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/plans');

    $response->assertForbidden();
});

test('guest is redirected to login when accessing plans list', function () {
    $response = $this->get('/admin/plans');

    $response->assertRedirect(route('login'));
});

test('plans list displays plans correctly', function () {
    $admin = User::factory()->admin()->create();

    Plan::factory()->create([
        'name' => 'Premium IPTV Plan',
        'price' => 2999,
        'currency' => 'USD',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('Premium IPTV Plan');
    $response->assertSee('29.99');
    $response->assertSee('USD');
});

test('plans list shows subscription count for each plan', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create(['name' => 'Test Plan']);

    // Create 3 subscriptions for this plan
    Subscription::factory()->count(3)->create(['plan_id' => $plan->id]);

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('3'); // Subscription count
});

test('empty state displays when no plans exist', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('No plans found');
});

test('create plan button is visible', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('Create Plan');
});

test('active plans show active badge', function () {
    $admin = User::factory()->admin()->create();

    Plan::factory()->create([
        'name' => 'Active Plan',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('Active');
});

test('inactive plans show inactive badge', function () {
    $admin = User::factory()->admin()->create();

    Plan::factory()->create([
        'name' => 'Inactive Plan',
        'is_active' => false,
    ]);

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('Inactive');
});

test('can filter active plans only', function () {
    $admin = User::factory()->admin()->create();

    Plan::factory()->create(['name' => 'Active Plan', 'is_active' => true]);
    Plan::factory()->create(['name' => 'Inactive Plan', 'is_active' => false]);

    Livewire::actingAs($admin)
        ->test(PlansList::class)
        ->call('filterActive', true)
        ->assertSee('Active Plan')
        ->assertDontSee('Inactive Plan');
});

test('can filter inactive plans only', function () {
    $admin = User::factory()->admin()->create();

    Plan::factory()->create(['name' => 'Active Plan', 'is_active' => true]);
    Plan::factory()->create(['name' => 'Inactive Plan', 'is_active' => false]);

    Livewire::actingAs($admin)
        ->test(PlansList::class)
        ->call('filterActive', false)
        ->assertSee('Inactive Plan')
        ->assertDontSee('Active Plan');
});

test('can reset filter to show all plans', function () {
    $admin = User::factory()->admin()->create();

    Plan::factory()->create(['name' => 'Active Plan', 'is_active' => true]);
    Plan::factory()->create(['name' => 'Inactive Plan', 'is_active' => false]);

    Livewire::actingAs($admin)
        ->test(PlansList::class)
        ->call('filterActive', true)
        ->call('filterActive', null)
        ->assertSee('Active Plan')
        ->assertSee('Inactive Plan');
});

test('can toggle plan active status', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create(['is_active' => true]);

    Livewire::actingAs($admin)
        ->test(PlansList::class)
        ->call('toggleActive', $plan->id);

    expect($plan->fresh()->is_active)->toBeFalse();
});

test('can delete plan without subscriptions', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create(['name' => 'Deletable Plan']);

    Livewire::actingAs($admin)
        ->test(PlansList::class)
        ->call('deletePlan', $plan->id);

    $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
});

test('cannot delete plan with active subscriptions', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create(['name' => 'Plan With Subscriptions']);

    // Create subscription for this plan
    Subscription::factory()->create(['plan_id' => $plan->id]);

    Livewire::actingAs($admin)
        ->test(PlansList::class)
        ->call('deletePlan', $plan->id);

    // Plan should still exist
    $this->assertDatabaseHas('plans', ['id' => $plan->id]);
});

test('plans with subscriptions cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    Subscription::factory()->create(['plan_id' => $plan->id]);

    $component = Livewire::actingAs($admin)->test(PlansList::class);

    expect($component->instance()->canDelete($plan->id))->toBeFalse();
});

test('edit plan dispatches modal event', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    Livewire::actingAs($admin)
        ->test(PlansList::class)
        ->call('editPlan', $plan->id)
        ->assertDispatched('open-plan-form-modal');
});

test('create plan dispatches modal event', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlansList::class)
        ->call('createPlan')
        ->assertDispatched('open-plan-form-modal');
});

test('plans show all required fields', function () {
    $admin = User::factory()->admin()->create();

    Plan::factory()->create([
        'name' => 'Test Plan',
        'slug' => 'test-plan',
        'price' => 1999,
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_12345',
    ]);

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('Test Plan');
    $response->assertSee('test-plan');
    $response->assertSee('monthly');
    $response->assertSee('wc_12345');
});

test('plans list includes action dropdown', function () {
    $admin = User::factory()->admin()->create();

    Plan::factory()->create();

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('Edit');
    $response->assertSee('Delete');
});

test('active plan shows deactivate action', function () {
    $admin = User::factory()->admin()->create();

    Plan::factory()->create(['is_active' => true]);

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('Deactivate');
});

test('inactive plan shows activate action', function () {
    $admin = User::factory()->admin()->create();

    Plan::factory()->create(['is_active' => false]);

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('Activate');
});

test('component refreshes after plan saved event', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create(['name' => 'Original Name']);

    $component = Livewire::actingAs($admin)
        ->test(PlansList::class);

    // Update the plan
    $plan->update(['name' => 'Updated Name']);

    // Trigger refresh
    $component->dispatch('plan-saved')
        ->assertSee('Updated Name');
});

test('plans display correct currency symbols', function () {
    $admin = User::factory()->admin()->create();

    Plan::factory()->create(['price' => 2999, 'currency' => 'USD']);
    Plan::factory()->create(['price' => 2999, 'currency' => 'EUR']);
    Plan::factory()->create(['price' => 2999, 'currency' => 'GBP']);

    $response = $this->actingAs($admin)->get('/admin/plans');

    $response->assertSuccessful();
    $response->assertSee('USD');
    $response->assertSee('EUR');
    $response->assertSee('GBP');
});
