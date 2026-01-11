<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Admin\PlansService;

beforeEach(function () {
    $this->service = app(PlansService::class);
});

test('can get all plans with subscription counts', function () {
    $plan1 = Plan::factory()->create(['name' => 'Plan 1']);
    $plan2 = Plan::factory()->create(['name' => 'Plan 2']);

    Subscription::factory()->count(3)->create(['plan_id' => $plan1->id]);
    Subscription::factory()->count(5)->create(['plan_id' => $plan2->id]);

    $plans = $this->service->getPlans();

    expect($plans->count())->toBeGreaterThanOrEqual(2);

    // Find our created plans
    $foundPlan1 = $plans->firstWhere('name', 'Plan 1');
    $foundPlan2 = $plans->firstWhere('name', 'Plan 2');

    expect($foundPlan1)->not->toBeNull();
    expect($foundPlan2)->not->toBeNull();
    expect($foundPlan1->subscriptions_count)->toBe(3);
    expect($foundPlan2->subscriptions_count)->toBe(5);
});

test('can filter active plans only', function () {
    Plan::factory()->create(['name' => 'Unique Active Plan', 'is_active' => true]);
    Plan::factory()->create(['name' => 'Unique Inactive Plan', 'is_active' => false]);

    $plans = $this->service->getPlans(activeOnly: true);

    // Should include our active plan and not our inactive plan
    expect($plans->pluck('name')->contains('Unique Active Plan'))->toBeTrue();
    expect($plans->pluck('name')->contains('Unique Inactive Plan'))->toBeFalse();
});

test('can filter inactive plans only', function () {
    Plan::factory()->create(['name' => 'Active Plan', 'is_active' => true]);
    Plan::factory()->create(['name' => 'Inactive Plan', 'is_active' => false]);

    $plans = $this->service->getPlans(activeOnly: false);

    expect($plans)->toHaveCount(1);
    expect($plans->first()->name)->toBe('Inactive Plan');
});

test('can create plan with valid data', function () {
    $data = [
        'name' => 'New Plan',
        'slug' => 'new-plan',
        'description' => 'Test description',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'max_devices' => 2,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
        'features' => '["hd", "4k"]',
        'is_active' => true,
    ];

    $plan = $this->service->createPlan($data);

    expect($plan->name)->toBe('New Plan');
    expect($plan->slug)->toBe('new-plan');
    expect((float) $plan->price)->toBe(29.99);
    expect($plan->features)->toBeArray();
});

test('can create plan with features as array', function () {
    $data = [
        'name' => 'New Plan',
        'slug' => 'new-plan',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
        'features' => ['hd', '4k'],
    ];

    $plan = $this->service->createPlan($data);

    expect($plan->features)->toBeArray();
    expect($plan->features)->toBe(['hd', '4k']);
});

test('can update plan', function () {
    $plan = Plan::factory()->create([
        'name' => 'Original Name',
        'slug' => 'original-slug',
        'price' => 1999,
    ]);

    $data = [
        'name' => 'Updated Name',
        'slug' => 'updated-slug',
        'price' => '39.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
    ];

    $updated = $this->service->updatePlan($plan->id, $data);

    expect($updated->name)->toBe('Updated Name');
    expect($updated->slug)->toBe('updated-slug');
    expect((float) $updated->price)->toBe(39.99);
});

test('can toggle plan active status from true to false', function () {
    $plan = Plan::factory()->create(['is_active' => true]);

    $updated = $this->service->toggleActive($plan->id);

    expect($updated->is_active)->toBeFalse();
});

test('can toggle plan active status from false to true', function () {
    $plan = Plan::factory()->create(['is_active' => false]);

    $updated = $this->service->toggleActive($plan->id);

    expect($updated->is_active)->toBeTrue();
});

test('can delete plan without subscriptions', function () {
    $plan = Plan::factory()->create();

    $result = $this->service->deletePlan($plan->id);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
});

test('cannot delete plan with subscriptions', function () {
    $plan = Plan::factory()->create();
    Subscription::factory()->create(['plan_id' => $plan->id]);

    expect(fn() => $this->service->deletePlan($plan->id))
        ->toThrow(Exception::class);

    $this->assertDatabaseHas('plans', ['id' => $plan->id]);
});

test('can check if plan can be deleted', function () {
    $planWithoutSubs = Plan::factory()->create();
    $planWithSubs = Plan::factory()->create();
    Subscription::factory()->create(['plan_id' => $planWithSubs->id]);

    expect($this->service->canDelete($planWithoutSubs->id))->toBeTrue();
    expect($this->service->canDelete($planWithSubs->id))->toBeFalse();
});

test('validation passes with valid data', function () {
    $data = [
        'name' => 'Test Plan',
        'slug' => 'test-plan',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
    ];

    $validated = $this->service->validatePlanData($data);

    expect($validated)->toBeArray();
    expect($validated['name'])->toBe('Test Plan');
});

test('validation fails when name is missing', function () {
    $data = [
        'slug' => 'test-plan',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
    ];

    expect(fn() => $this->service->validatePlanData($data))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('validation fails when slug is not unique', function () {
    Plan::factory()->create(['slug' => 'existing-slug']);

    $data = [
        'name' => 'Test Plan',
        'slug' => 'existing-slug',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
    ];

    expect(fn() => $this->service->validatePlanData($data))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('validation passes when slug is same as existing plan being updated', function () {
    $plan = Plan::factory()->create(['slug' => 'test-slug']);

    $data = [
        'name' => 'Updated Plan',
        'slug' => 'test-slug',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
    ];

    $validated = $this->service->validatePlanData($data, $plan->id);

    expect($validated)->toBeArray();
});

test('validation fails when woocommerce_id is not unique', function () {
    Plan::factory()->create(['woocommerce_id' => 'wc_existing']);

    $data = [
        'name' => 'Test Plan',
        'slug' => 'test-plan',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_existing',
        'my8k_plan_code' => 'PREMIUM',
    ];

    expect(fn() => $this->service->validatePlanData($data))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('validation fails when price is negative', function () {
    $data = [
        'name' => 'Test Plan',
        'slug' => 'test-plan',
        'price' => '-10.00',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
    ];

    expect(fn() => $this->service->validatePlanData($data))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('validation fails when currency is invalid', function () {
    $data = [
        'name' => 'Test Plan',
        'slug' => 'test-plan',
        'price' => '29.99',
        'currency' => 'INVALID',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
    ];

    expect(fn() => $this->service->validatePlanData($data))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('validation fails when features is invalid json', function () {
    $data = [
        'name' => 'Test Plan',
        'slug' => 'test-plan',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
        'features' => 'not valid json',
    ];

    expect(fn() => $this->service->validatePlanData($data))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('validation passes with empty features', function () {
    $data = [
        'name' => 'Test Plan',
        'slug' => 'test-plan',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
        'features' => '',
    ];

    $validated = $this->service->validatePlanData($data);

    expect($validated)->toBeArray();
});

test('get billing intervals returns correct values', function () {
    $intervals = $this->service->getBillingIntervals();

    expect($intervals)->toBeArray();
    expect($intervals)->toHaveCount(3);
    expect($intervals[0])->toHaveKey('value');
    expect($intervals[0])->toHaveKey('label');
});

test('get currencies returns correct values', function () {
    $currencies = $this->service->getCurrencies();

    expect($currencies)->toBeArray();
    expect($currencies)->toHaveCount(3);
    expect($currencies[0])->toHaveKey('value');
    expect($currencies[0])->toHaveKey('label');
});

test('price is stored as decimal', function () {
    $data = [
        'name' => 'Test Plan',
        'slug' => 'test-plan',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
    ];

    $plan = $this->service->createPlan($data);

    expect((float) $plan->price)->toBe(29.99);
});

test('can create plan with minimal required fields', function () {
    $data = [
        'name' => 'Minimal Plan',
        'slug' => 'minimal-plan',
        'price' => '9.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_minimal',
        'my8k_plan_code' => 'MIN',
    ];

    $plan = $this->service->createPlan($data);

    expect($plan->name)->toBe('Minimal Plan');
    expect($plan->description)->toBeNull();
    expect($plan->features)->toBeNull();
});

test('slug validation allows hyphens and lowercase letters', function () {
    $data = [
        'name' => 'Test Plan',
        'slug' => 'valid-slug-123',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
    ];

    $validated = $this->service->validatePlanData($data);

    expect($validated['slug'])->toBe('valid-slug-123');
});

test('validation fails when slug contains uppercase or special characters', function () {
    $data = [
        'name' => 'Test Plan',
        'slug' => 'Invalid_Slug!',
        'price' => '29.99',
        'currency' => 'USD',
        'billing_interval' => 'monthly',
        'duration_days' => 30,
        'woocommerce_id' => 'wc_123',
        'my8k_plan_code' => 'PREMIUM',
    ];

    expect(fn() => $this->service->validatePlanData($data))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});
