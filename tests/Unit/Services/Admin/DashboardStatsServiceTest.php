<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ProvisioningLog;
use App\Models\Subscription;
use App\Services\Admin\DashboardStatsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->service = new DashboardStatsService();
});

test('getActiveSubscriptionsCount returns correct count', function () {
    $plan = Plan::factory()->create();

    // Create 3 active subscriptions
    Subscription::factory()->count(3)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    // Create 2 non-active subscriptions (should not be counted)
    Subscription::factory()->count(2)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Expired,
    ]);

    $count = $this->service->getActiveSubscriptionsCount();

    expect($count)->toBe(3);
});

test('getOrdersTodayCount returns correct count', function () {
    // Create 5 orders today
    Order::factory()->count(5)->create([
        'created_at' => now(),
    ]);

    // Create 3 orders yesterday (should not be counted)
    Order::factory()->count(3)->create([
        'created_at' => now()->subDay(),
    ]);

    $count = $this->service->getOrdersTodayCount();

    expect($count)->toBe(5);
});

test('getProvisioningSuccessRate returns 100% when no logs exist', function () {
    $rate = $this->service->getProvisioningSuccessRate();

    expect($rate)->toBe(100.0);
});

test('getProvisioningSuccessRate calculates percentage correctly', function () {
    // Create 7 successful logs in last 24 hours
    ProvisioningLog::factory()->count(7)->create([
        'status' => 'success',
        'created_at' => now()->subHours(12),
    ]);

    // Create 3 failed logs in last 24 hours
    ProvisioningLog::factory()->count(3)->create([
        'status' => 'failed',
        'created_at' => now()->subHours(12),
    ]);

    // Create old logs (should not be counted)
    ProvisioningLog::factory()->count(10)->create([
        'status' => 'failed',
        'created_at' => now()->subDays(2),
    ]);

    $rate = $this->service->getProvisioningSuccessRate();

    // 7 successful out of 10 total = 70%
    expect($rate)->toBe(70.0);
});

test('getFailedJobsCount returns correct count', function () {
    // Add 4 failed jobs
    DB::table('failed_jobs')->insert([
        [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'data1']),
            'exception' => 'Test exception 1',
            'failed_at' => now(),
        ],
        [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'data2']),
            'exception' => 'Test exception 2',
            'failed_at' => now(),
        ],
        [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'data3']),
            'exception' => 'Test exception 3',
            'failed_at' => now(),
        ],
        [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'data4']),
            'exception' => 'Test exception 4',
            'failed_at' => now(),
        ],
    ]);

    $count = $this->service->getFailedJobsCount();

    expect($count)->toBe(4);
});

test('statistics are cached for 60 seconds', function () {
    $plan = Plan::factory()->create();

    // Create 2 active subscriptions
    Subscription::factory()->count(2)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    // First call - should query database and cache
    $count1 = $this->service->getActiveSubscriptionsCount();
    expect($count1)->toBe(2);

    // Create 3 more active subscriptions
    Subscription::factory()->count(3)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    // Second call - should return cached value (still 2)
    $count2 = $this->service->getActiveSubscriptionsCount();
    expect($count2)->toBe(2);

    // Clear cache
    Cache::forget('admin.stats.active_subscriptions');

    // Third call - should query database again and return updated count
    $count3 = $this->service->getActiveSubscriptionsCount();
    expect($count3)->toBe(5);
});

test('clearCache clears all dashboard statistics cache', function () {
    $plan = Plan::factory()->create();

    // Create test data and populate cache
    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);
    Order::factory()->create(['created_at' => now()]);

    // Populate cache by calling methods
    $this->service->getActiveSubscriptionsCount();
    $this->service->getOrdersTodayCount();
    $this->service->getProvisioningSuccessRate();
    $this->service->getFailedJobsCount();

    // Verify cache exists
    expect(Cache::has('admin.stats.active_subscriptions'))->toBeTrue();
    expect(Cache::has('admin.stats.orders_today'))->toBeTrue();
    expect(Cache::has('admin.stats.provisioning_success_rate'))->toBeTrue();
    expect(Cache::has('admin.stats.failed_jobs'))->toBeTrue();

    // Clear cache
    $this->service->clearCache();

    // Verify cache is cleared
    expect(Cache::has('admin.stats.active_subscriptions'))->toBeFalse();
    expect(Cache::has('admin.stats.orders_today'))->toBeFalse();
    expect(Cache::has('admin.stats.provisioning_success_rate'))->toBeFalse();
    expect(Cache::has('admin.stats.failed_jobs'))->toBeFalse();
});
