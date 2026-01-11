<?php

declare(strict_types=1);

use App\Livewire\Admin\PlanFormModal;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

test('modal opens in create mode', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->assertSet('show', true)
        ->assertSet('mode', 'create')
        ->assertSet('planId', null);
});

test('modal opens in edit mode and loads plan data', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create([
        'name' => 'Test Plan',
        'slug' => 'test-plan',
        'description' => 'Test Description',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'max_devices' => 3,
        'woocommerce_id' => 'wc_12345',
        'my8k_plan_code' => 'PREMIUM',
        'features' => ['hd', '4k'],
        'is_active' => true,
    ]);

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'edit', planId: $plan->id)
        ->assertSet('show', true)
        ->assertSet('mode', 'edit')
        ->assertSet('planId', $plan->id)
        ->assertSet('name', 'Test Plan')
        ->assertSet('slug', 'test-plan')
        ->assertSet('description', 'Test Description')
        ->assertSet('price', '29.99')
        ->assertSet('currency', 'USD')
        ->assertSet('billing_interval', 'monthly')
        ->assertSet('duration_days', '30')
        ->assertSet('max_devices', '3')
        ->assertSet('woocommerce_id', 'wc_12345')
        ->assertSet('my8k_plan_code', 'PREMIUM')
        ->assertSet('is_active', true);
});

test('can create new plan with valid data', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->set('name', 'New Premium Plan')
        ->set('slug', 'new-premium-plan')
        ->set('description', 'Premium IPTV service')
        ->set('price', '29.99')
        ->set('currency', 'USD')
        ->set('billing_interval', 'monthly')
        ->set('duration_days', '30')
        ->set('max_devices', '2')
        ->set('woocommerce_id', 'wc_new_123')
        ->set('my8k_plan_code', 'PREMIUM_NEW')
        ->set('features', '["hd", "4k"]')
        ->set('is_active', true)
        ->call('save')
        ->assertDispatched('plan-saved')
        ->assertSet('show', false);

    $this->assertDatabaseHas('plans', [
        'name' => 'New Premium Plan',
        'slug' => 'new-premium-plan',
        'price' => '29.99',
        'currency' => 'USD',
    ]);
});

test('can update existing plan', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create([
        'name' => 'Original Name',
        'slug' => 'original-slug',
        'price' => 1999,
    ]);

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'edit', planId: $plan->id)
        ->set('name', 'Updated Name')
        ->set('slug', 'updated-slug')
        ->set('price', '39.99')
        ->call('save')
        ->assertDispatched('plan-saved')
        ->assertSet('show', false);

    $this->assertDatabaseHas('plans', [
        'id' => $plan->id,
        'name' => 'Updated Name',
        'slug' => 'updated-slug',
        'price' => '39.99',
    ]);
});

test('validation fails when name is missing', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->set('name', '')
        ->set('slug', 'test-plan')
        ->set('price', '29.99')
        ->set('currency', 'USD')
        ->set('billing_interval', 'monthly')
        ->set('duration_days', '30')
        ->set('woocommerce_id', 'wc_123')
        ->set('my8k_plan_code', 'TEST')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('validation fails when slug is missing', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->set('name', 'Test Plan')
        ->set('slug', '')
        ->set('price', '29.99')
        ->set('currency', 'USD')
        ->set('billing_interval', 'monthly')
        ->set('duration_days', '30')
        ->set('woocommerce_id', 'wc_123')
        ->set('my8k_plan_code', 'TEST')
        ->call('save')
        ->assertHasErrors(['slug']);
});

test('validation fails when price is missing', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->set('name', 'Test Plan')
        ->set('slug', 'test-plan')
        ->set('price', '')
        ->set('currency', 'USD')
        ->set('billing_interval', 'monthly')
        ->set('duration_days', '30')
        ->set('woocommerce_id', 'wc_123')
        ->set('my8k_plan_code', 'TEST')
        ->call('save')
        ->assertHasErrors(['price']);
});

test('validation fails when price is negative', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->set('name', 'Test Plan')
        ->set('slug', 'test-plan')
        ->set('price', '-10.00')
        ->set('currency', 'USD')
        ->set('billing_interval', 'monthly')
        ->set('duration_days', '30')
        ->set('woocommerce_id', 'wc_123')
        ->set('my8k_plan_code', 'TEST')
        ->call('save')
        ->assertHasErrors(['price']);
});

test('validation fails when slug is not unique', function () {
    $admin = User::factory()->admin()->create();
    Plan::factory()->create(['slug' => 'existing-slug']);

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->set('name', 'Test Plan')
        ->set('slug', 'existing-slug')
        ->set('price', '29.99')
        ->set('currency', 'USD')
        ->set('billing_interval', 'monthly')
        ->set('duration_days', '30')
        ->set('woocommerce_id', 'wc_123')
        ->set('my8k_plan_code', 'TEST')
        ->call('save')
        ->assertHasErrors(['slug']);
});

test('validation fails when woocommerce_id is not unique', function () {
    $admin = User::factory()->admin()->create();
    Plan::factory()->create(['woocommerce_id' => 'wc_existing']);

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->set('name', 'Test Plan')
        ->set('slug', 'test-plan')
        ->set('price', '29.99')
        ->set('currency', 'USD')
        ->set('billing_interval', 'monthly')
        ->set('duration_days', '30')
        ->set('woocommerce_id', 'wc_existing')
        ->set('my8k_plan_code', 'TEST')
        ->call('save')
        ->assertHasErrors(['woocommerce_id']);
});

test('validation fails when currency is invalid', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->set('name', 'Test Plan')
        ->set('slug', 'test-plan')
        ->set('price', '29.99')
        ->set('currency', 'INVALID')
        ->set('billing_interval', 'monthly')
        ->set('duration_days', '30')
        ->set('woocommerce_id', 'wc_123')
        ->set('my8k_plan_code', 'TEST')
        ->call('save')
        ->assertHasErrors(['currency']);
});

test('validation fails when features is invalid json', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->set('name', 'Test Plan')
        ->set('slug', 'test-plan')
        ->set('price', '29.99')
        ->set('currency', 'USD')
        ->set('billing_interval', 'monthly')
        ->set('duration_days', '30')
        ->set('woocommerce_id', 'wc_123')
        ->set('my8k_plan_code', 'TEST')
        ->set('features', 'not valid json')
        ->call('save')
        ->assertHasErrors(['features']);
});

test('can save plan with empty features', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->set('name', 'Test Plan')
        ->set('slug', 'test-plan')
        ->set('price', '29.99')
        ->set('currency', 'USD')
        ->set('billing_interval', 'monthly')
        ->set('duration_days', '30')
        ->set('woocommerce_id', 'wc_123')
        ->set('my8k_plan_code', 'TEST')
        ->set('features', '')
        ->call('save')
        ->assertDispatched('plan-saved');
});

test('modal closes when closeModal is called', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->assertSet('show', true)
        ->call('closeModal')
        ->assertSet('show', false);
});

test('modal displays correct title in create mode', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->assertSee('Create New Plan');
});

test('modal displays correct title in edit mode', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'edit', planId: $plan->id)
        ->assertSee('Edit Plan');
});

test('form resets after successful save', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test(PlanFormModal::class)
        ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->set('name', 'Test Plan')
        ->set('slug', 'test-plan')
        ->set('price', '29.99')
        ->set('currency', 'USD')
        ->set('billing_interval', 'monthly')
        ->set('duration_days', '30')
        ->set('woocommerce_id', 'wc_123')
        ->set('my8k_plan_code', 'TEST')
        ->call('save')
        ->assertSet('show', false);

    // Open modal again and verify fields are empty
    $component->dispatch('open-plan-form-modal', mode: 'create', planId: null)
        ->assertSet('name', '')
        ->assertSet('slug', '')
        ->assertSet('price', '');
});

test('can save plan with all billing intervals', function () {
    $admin = User::factory()->admin()->create();

    foreach (['monthly', 'quarterly', 'yearly'] as $interval) {
        Livewire::actingAs($admin)
            ->test(PlanFormModal::class)
            ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
            ->set('name', "Plan {$interval}")
            ->set('slug', "plan-{$interval}")
            ->set('price', '29.99')
            ->set('currency', 'USD')
            ->set('billing_interval', $interval)
            ->set('duration_days', '30')
            ->set('woocommerce_id', "wc_{$interval}")
            ->set('my8k_plan_code', "TEST_{$interval}")
            ->call('save')
            ->assertDispatched('plan-saved');

        $this->assertDatabaseHas('plans', [
            'slug' => "plan-{$interval}",
            'billing_interval' => $interval,
        ]);
    }
});

test('can save plan with all supported currencies', function () {
    $admin = User::factory()->admin()->create();

    foreach (['USD', 'EUR', 'GBP'] as $currency) {
        Livewire::actingAs($admin)
            ->test(PlanFormModal::class)
            ->dispatch('open-plan-form-modal', mode: 'create', planId: null)
            ->set('name', "Plan {$currency}")
            ->set('slug', "plan-" . mb_strtolower($currency))
            ->set('price', '29.99')
            ->set('currency', $currency)
            ->set('billing_interval', 'monthly')
            ->set('duration_days', '30')
            ->set('woocommerce_id', "wc_{$currency}")
            ->set('my8k_plan_code', "TEST_{$currency}")
            ->call('save')
            ->assertDispatched('plan-saved');

        $this->assertDatabaseHas('plans', [
            'slug' => "plan-" . mb_strtolower($currency),
            'currency' => $currency,
        ]);
    }
});
