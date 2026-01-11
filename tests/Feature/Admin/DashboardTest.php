<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ProvisioningLog;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('admin dashboard displays correctly for admin', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertSuccessful();
    $response->assertSee('Admin Dashboard');
    $response->assertSee('Active Subscriptions');
    $response->assertSee('Orders Today');
    $response->assertSee('Success Rate');
    $response->assertSee('Failed Jobs');
});

test('dashboard shows correct active subscriptions count', function () {
    $admin = User::factory()->admin()->create();
    $plan = Plan::factory()->create();

    // Create 5 active subscriptions
    Subscription::factory()->count(5)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    // Create 2 expired subscriptions (should not be counted)
    Subscription::factory()->count(2)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Expired,
    ]);

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertSuccessful();
    $response->assertSee('5'); // Should show 5 active subscriptions
});

test('dashboard shows correct orders today count', function () {
    $admin = User::factory()->admin()->create();

    // Create 3 orders today
    Order::factory()->count(3)->create([
        'created_at' => now(),
    ]);

    // Create 2 orders yesterday (should not be counted)
    Order::factory()->count(2)->create([
        'created_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertSuccessful();
    $response->assertSee('3'); // Should show 3 orders today
});

test('dashboard shows 100% success rate when no provisioning logs exist', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertSuccessful();
    $response->assertSee('100.0%'); // Default to 100% when no logs
});

test('dashboard shows correct provisioning success rate', function () {
    $admin = User::factory()->admin()->create();

    // Create 8 successful provisioning logs (recent)
    ProvisioningLog::factory()->count(8)->create([
        'status' => 'success',
        'created_at' => now()->subHours(12),
    ]);

    // Create 2 failed provisioning logs (recent)
    ProvisioningLog::factory()->count(2)->create([
        'status' => 'failed',
        'created_at' => now()->subHours(12),
    ]);

    // Create old logs (should not be counted)
    ProvisioningLog::factory()->count(5)->create([
        'status' => 'failed',
        'created_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertSuccessful();
    $response->assertSee('80.0%'); // 8/10 = 80%
});

test('dashboard shows correct failed jobs count', function () {
    $admin = User::factory()->admin()->create();

    // Add some failed jobs to the database
    DB::table('failed_jobs')->insert([
        [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'data']),
            'exception' => 'Test exception',
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
    ]);

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertSuccessful();
    $response->assertSee('2'); // Should show 2 failed jobs
});

test('dashboard page refreshes with livewire polling', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertSuccessful();
    $response->assertSee('wire:poll.60s'); // Verify polling is enabled
});
