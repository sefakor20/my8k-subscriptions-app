<?php

declare(strict_types=1);

use App\Livewire\Admin\CohortAnalysis;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Admin\CohortAnalyticsService;
use Livewire\Livewire;

test('admin can access cohort analysis page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/analytics/cohorts');

    $response->assertSuccessful();
    $response->assertSeeLivewire(CohortAnalysis::class);
});

test('non-admin cannot access cohort analysis page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/analytics/cohorts');

    $response->assertForbidden();
});

test('guest is redirected to login', function () {
    $response = $this->get('/admin/analytics/cohorts');

    $response->assertRedirect(route('login'));
});

test('cohort analysis displays page header', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CohortAnalysis::class)
        ->assertSee('Cohort Analysis')
        ->assertSee('Analyze subscription retention rates');
});

test('displays summary metrics cards', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CohortAnalysis::class)
        ->assertSee('Avg 3-Month Retention')
        ->assertSee('Best Performing Plan')
        ->assertSee('Avg Time to Churn')
        ->assertSee('Active Cohorts');
});

test('displays cohort retention matrix', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create(['name' => 'Premium Plan']);

    // Create a subscription for a cohort
    Subscription::factory()
        ->forPlan($plan)
        ->active()
        ->create([
            'created_at' => now()->subMonths(2),
        ]);

    Livewire::actingAs($admin)
        ->test(CohortAnalysis::class)
        ->assertSee('Cohort Retention Matrix')
        ->assertSee('Premium Plan');
});

test('can filter by plan', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    Livewire::actingAs($admin)
        ->test(CohortAnalysis::class)
        ->assertSet('selectedPlanId', null)
        ->call('setSelectedPlan', $plan->id)
        ->assertSet('selectedPlanId', $plan->id)
        ->call('setSelectedPlan', null)
        ->assertSet('selectedPlanId', null);
});

test('plan filter dropdown shows available plans', function () {
    $admin = User::factory()->admin()->create();
    Plan::factory()->create(['name' => 'Basic Plan', 'is_active' => true]);
    Plan::factory()->create(['name' => 'Premium Plan', 'is_active' => true]);

    $response = $this->actingAs($admin)->get('/admin/analytics/cohorts');

    $response->assertSee('All Plans');
    $response->assertSee('Basic Plan');
    $response->assertSee('Premium Plan');
});

test('can refresh cohort data', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CohortAnalysis::class)
        ->call('refreshData')
        ->assertDispatched('cohort-refreshed');
});

test('can export cohort data as csv', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CohortAnalysis::class)
        ->call('exportCsv')
        ->assertFileDownloaded();
});

test('retention color class returns correct class for high retention', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test(CohortAnalysis::class);

    expect($component->instance()->getRetentionColorClass(85))
        ->toContain('emerald');
});

test('retention color class returns correct class for medium retention', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test(CohortAnalysis::class);

    expect($component->instance()->getRetentionColorClass(50))
        ->toContain('yellow');
});

test('retention color class returns correct class for low retention', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test(CohortAnalysis::class);

    expect($component->instance()->getRetentionColorClass(15))
        ->toContain('red');
});

test('retention color class returns correct class for null value', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test(CohortAnalysis::class);

    expect($component->instance()->getRetentionColorClass(null))
        ->toContain('zinc');
});

test('cohort matrix computes correctly with active subscriptions', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    // Create subscriptions in different cohorts
    Subscription::factory()
        ->forPlan($plan)
        ->active()
        ->create([
            'created_at' => now()->subMonths(3),
            'expires_at' => now()->addMonths(1),
        ]);

    Subscription::factory()
        ->forPlan($plan)
        ->active()
        ->create([
            'created_at' => now()->subMonths(2),
            'expires_at' => now()->addMonths(2),
        ]);

    $component = Livewire::actingAs($admin)
        ->test(CohortAnalysis::class);

    $matrix = $component->get('cohortMatrix');

    expect($matrix)->toHaveKey('cohorts')
        ->and($matrix)->toHaveKey('plans')
        ->and($matrix['cohorts'])->toBeArray();
});

test('handles empty data gracefully', function () {
    $admin = User::factory()->admin()->create();

    // No subscriptions exist
    Livewire::actingAs($admin)
        ->test(CohortAnalysis::class)
        ->assertSee('No cohort data available yet');
});

test('churn analysis computes correctly', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    // Create a cancelled subscription
    Subscription::factory()
        ->forPlan($plan)
        ->cancelled()
        ->create([
            'created_at' => now()->subMonths(4),
            'cancelled_at' => now()->subMonths(2),
        ]);

    $component = Livewire::actingAs($admin)
        ->test(CohortAnalysis::class);

    $churnAnalysis = $component->get('churnAnalysis');

    expect($churnAnalysis)->toHaveKey('labels')
        ->and($churnAnalysis)->toHaveKey('datasets');
});

test('retention by plan computes correctly', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    Subscription::factory()
        ->forPlan($plan)
        ->active()
        ->create([
            'created_at' => now()->subMonths(3),
        ]);

    $component = Livewire::actingAs($admin)
        ->test(CohortAnalysis::class);

    $retentionByPlan = $component->get('retentionByPlan');

    expect($retentionByPlan)->toHaveKey('labels')
        ->and($retentionByPlan)->toHaveKey('datasets');
});

test('plan summary includes best performing plan', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create(['name' => 'Premium']);

    Subscription::factory()
        ->forPlan($plan)
        ->active()
        ->create([
            'created_at' => now()->subMonths(2),
        ]);

    $component = Livewire::actingAs($admin)
        ->test(CohortAnalysis::class);

    $summary = $component->get('planSummary');

    expect($summary)->toHaveKey('average_retention')
        ->and($summary)->toHaveKey('best_plan')
        ->and($summary)->toHaveKey('avg_churn_months')
        ->and($summary)->toHaveKey('total_cohorts');
});

test('service can clear cache', function () {
    $service = app(CohortAnalyticsService::class);

    // This should not throw any exceptions
    $service->clearCache();

    expect(true)->toBeTrue();
});

test('auto refresh is enabled by default', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CohortAnalysis::class)
        ->assertSet('autoRefresh', true);
});

test('displays retention chart sections', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CohortAnalysis::class)
        ->assertSee('Retention by Plan')
        ->assertSee('Churn Timeline');
});
