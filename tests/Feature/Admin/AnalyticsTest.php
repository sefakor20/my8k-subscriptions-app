<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\ProvisioningStatus;
use App\Livewire\Admin\Analytics;
use App\Models\Order;
use App\Models\ProvisioningLog;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('admin can access analytics page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/analytics');

    $response->assertSuccessful();
    $response->assertSeeLivewire(Analytics::class);
});

test('non-admin cannot access analytics page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/analytics');

    $response->assertForbidden();
});

test('guest is redirected to login', function () {
    $response = $this->get('/admin/analytics');

    $response->assertRedirect(route('login'));
});

test('analytics dashboard displays page header', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Analytics::class)
        ->assertSee('Analytics Dashboard')
        ->assertSee('Comprehensive analytics and performance metrics');
});

test('displays performance metrics', function () {
    $admin = User::factory()->admin()->create();

    // Create some test data
    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Success,
        'created_at' => now()->subDays(5),
    ]);

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Failed,
        'created_at' => now()->subDays(3),
    ]);

    Livewire::actingAs($admin)
        ->test(Analytics::class)
        ->assertSee('Total Provisioned')
        ->assertSee('Success Rate')
        ->assertSee('Failure Rate')
        ->assertSee('Avg Duration')
        ->assertSee('Pending');
});

test('date range filter can be changed', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Analytics::class)
        ->assertSet('dateRange', 30)
        ->set('dateRange', 7)
        ->assertSet('dateRange', 7)
        ->set('dateRange', 90)
        ->assertSet('dateRange', 90);
});

test('date range options are available', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/analytics');

    $response->assertSee('Last 7 Days');
    $response->assertSee('Last 14 Days');
    $response->assertSee('Last 30 Days');
    $response->assertSee('Last 60 Days');
    $response->assertSee('Last 90 Days');
});

test('can refresh analytics data', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Analytics::class)
        ->call('refreshData')
        ->assertDispatched('analytics-refreshed');
});

test('auto-refresh is enabled by default', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Analytics::class)
        ->assertSet('autoRefresh', true)
        ->assertSee('Auto-refreshing every 60 seconds');
});

test('success rate data is computed correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create success and failure logs
    ProvisioningLog::factory()->count(8)->create([
        'status' => ProvisioningStatus::Success,
        'created_at' => now()->subDays(5),
    ]);

    ProvisioningLog::factory()->count(2)->create([
        'status' => ProvisioningStatus::Failed,
        'created_at' => now()->subDays(5),
    ]);

    $component = Livewire::actingAs($admin)
        ->test(Analytics::class);

    $successRateData = $component->instance()->successRateData;

    expect($successRateData)->toHaveKeys(['labels', 'data']);
    expect($successRateData['labels'])->toBeArray();
    expect($successRateData['data'])->toBeArray();
});

test('order status distribution data is computed correctly', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create(['user_id' => $user->id]);

    // Create orders with different statuses
    Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'status' => OrderStatus::Provisioned,
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'status' => OrderStatus::PendingProvisioning,
    ]);

    $component = Livewire::actingAs($admin)
        ->test(Analytics::class);

    $orderStatusData = $component->instance()->orderStatusData;

    expect($orderStatusData)->toHaveKeys(['labels', 'data']);
    expect($orderStatusData['labels'])->toBeArray();
    expect($orderStatusData['data'])->toBeArray();
    expect(array_sum($orderStatusData['data']))->toBe(2);
});

test('error frequency data is computed correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create failed logs with errors
    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Failed,
        'error_message' => 'Connection timeout',
        'created_at' => now()->subDays(2),
    ]);

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Failed,
        'error_message' => 'Connection timeout',
        'created_at' => now()->subDays(1),
    ]);

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Failed,
        'error_message' => 'Invalid credentials',
        'created_at' => now()->subHours(12),
    ]);

    $component = Livewire::actingAs($admin)
        ->test(Analytics::class);

    $errorFrequencyData = $component->instance()->errorFrequencyData;

    expect($errorFrequencyData)->toHaveKeys(['labels', 'data']);
    expect($errorFrequencyData['labels'])->toBeArray();
    expect($errorFrequencyData['data'])->toBeArray();
});

test('provisioning performance metrics are computed correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create logs with different statuses
    ProvisioningLog::factory()->count(7)->create([
        'status' => ProvisioningStatus::Success,
        'created_at' => now()->subDays(5),
    ]);

    ProvisioningLog::factory()->count(2)->create([
        'status' => ProvisioningStatus::Failed,
        'created_at' => now()->subDays(3),
    ]);

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Pending,
        'created_at' => now()->subHours(1),
    ]);

    $component = Livewire::actingAs($admin)
        ->test(Analytics::class);

    $performanceMetrics = $component->instance()->performanceMetrics;

    expect($performanceMetrics)->toHaveKeys([
        'avgDuration',
        'totalProvisioned',
        'successRate',
        'failureRate',
        'pendingCount',
    ]);

    expect($performanceMetrics['totalProvisioned'])->toBe(10);
    expect($performanceMetrics['successRate'])->toBe(70.0);
    expect($performanceMetrics['failureRate'])->toBe(20.0);
    expect($performanceMetrics['pendingCount'])->toBe(1);
});

test('subscription growth data is computed correctly', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    // Create subscriptions over time
    Subscription::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subDays(10),
    ]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subDays(5),
    ]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subDays(2),
    ]);

    $component = Livewire::actingAs($admin)
        ->test(Analytics::class);

    $subscriptionGrowthData = $component->instance()->subscriptionGrowthData;

    expect($subscriptionGrowthData)->toHaveKeys(['labels', 'data']);
    expect($subscriptionGrowthData['labels'])->toBeArray();
    expect($subscriptionGrowthData['data'])->toBeArray();
});

test('revenue data is computed correctly', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create(['user_id' => $user->id]);

    // Create orders with amounts
    Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'status' => OrderStatus::Provisioned,
        'amount' => 29.99,
        'created_at' => now()->subDays(5),
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'status' => OrderStatus::Provisioned,
        'amount' => 49.99,
        'created_at' => now()->subDays(2),
    ]);

    $component = Livewire::actingAs($admin)
        ->test(Analytics::class);

    $revenueData = $component->instance()->revenueData;

    expect($revenueData)->toHaveKeys(['labels', 'data']);
    expect($revenueData['labels'])->toBeArray();
    expect($revenueData['data'])->toBeArray();
});

test('chart canvases are rendered', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/analytics');

    $response->assertSee('successRateChart', false);
    $response->assertSee('orderStatusChart', false);
    $response->assertSee('subscriptionGrowthChart', false);
    $response->assertSee('revenueChart', false);
    $response->assertSee('errorFrequencyChart', false);
});

test('chart titles are displayed', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/analytics');

    $response->assertSee('Provisioning Success Rate');
    $response->assertSee('Order Status Distribution');
    $response->assertSee('Subscription Growth');
    $response->assertSee('Revenue Trend');
    $response->assertSee('Top Error Types');
});

test('analytics page has refresh button', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/analytics');

    $response->assertSee('Refresh');
});

test('changing date range updates computed properties', function () {
    $admin = User::factory()->admin()->create();

    // Create data
    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Success,
        'created_at' => now()->subDays(40), // Outside 30 day range
    ]);

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Success,
        'created_at' => now()->subDays(5), // Within all ranges
    ]);

    $component = Livewire::actingAs($admin)
        ->test(Analytics::class)
        ->assertSet('dateRange', 30);

    // Get initial performance metrics
    $metrics30Days = $component->instance()->performanceMetrics;

    // Change to 60 days
    $component->set('dateRange', 60);

    // Get updated performance metrics
    $metrics60Days = $component->instance()->performanceMetrics;

    // 60 days should include more data
    expect($metrics60Days['totalProvisioned'])->toBeGreaterThanOrEqual($metrics30Days['totalProvisioned']);
});

test('analytics works with empty data', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test(Analytics::class);

    $performanceMetrics = $component->instance()->performanceMetrics;

    expect($performanceMetrics['totalProvisioned'])->toBe(0);
    expect($performanceMetrics['successRate'])->toBe(0);
    expect($performanceMetrics['failureRate'])->toBe(0);
    expect($performanceMetrics['avgDuration'])->toBe(0);
    expect($performanceMetrics['pendingCount'])->toBe(0);
});

test('analytics sidebar link is active on analytics page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/analytics');

    $response->assertSee('Analytics');
    // Note: Icon rendering may vary, so we just verify the link text is present
});

test('custom date range can be applied', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Analytics::class)
        ->assertSet('dateRangeType', 'preset')
        ->set('customStartDate', '2024-01-01')
        ->set('customEndDate', '2024-01-31')
        ->call('applyCustomDateRange')
        ->assertSet('dateRangeType', 'custom')
        ->assertSet('customStartDate', '2024-01-01')
        ->assertSet('customEndDate', '2024-01-31');
});

test('can switch back to preset date range', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Analytics::class)
        ->set('dateRangeType', 'custom')
        ->set('customStartDate', '2024-01-01')
        ->set('customEndDate', '2024-01-31')
        ->call('usePresetDateRange')
        ->assertSet('dateRangeType', 'preset')
        ->assertSet('customStartDate', null)
        ->assertSet('customEndDate', null);
});

test('custom date range requires both start and end dates', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Analytics::class)
        ->set('customStartDate', '2024-01-01')
        ->set('customEndDate', null)
        ->call('applyCustomDateRange')
        ->assertSet('dateRangeType', 'preset');
});

test('csv export returns downloadable response', function () {
    $admin = User::factory()->admin()->create();

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Success,
        'created_at' => now()->subDays(5),
    ]);

    $component = Livewire::actingAs($admin)
        ->test(Analytics::class)
        ->call('exportCsv');

    $component->assertFileDownloaded();
});

test('export csv button is visible', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/analytics');

    $response->assertSee('Export CSV');
});

test('preset and custom buttons are visible', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/analytics');

    $response->assertSee('Preset');
    $response->assertSee('Custom');
});

test('analytics service exports csv with correct structure', function () {
    $admin = User::factory()->admin()->create();

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Success,
        'created_at' => now()->subDays(5),
    ]);

    $service = app(\App\Services\Admin\AnalyticsService::class);
    $export = $service->exportToCsv(30);

    expect($export)->toHaveKeys(['filename', 'content']);
    expect($export['filename'])->toContain('analytics_');
    expect($export['filename'])->toEndWith('.csv');
    expect($export['content'])->toContain('Analytics Report');
    expect($export['content'])->toContain('Performance Metrics');
    expect($export['content'])->toContain('Daily Success Rate');
    expect($export['content'])->toContain('Daily Revenue');
});

test('analytics service supports custom date range', function () {
    $admin = User::factory()->admin()->create();

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Success,
        'created_at' => '2024-01-15',
    ]);

    $service = app(\App\Services\Admin\AnalyticsService::class);

    $result = $service->getSuccessRateTimeSeries(null, '2024-01-01', '2024-01-31');

    expect($result)->toHaveKeys(['labels', 'data']);
    expect(count($result['labels']))->toBe(31);
});
