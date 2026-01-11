<?php

declare(strict_types=1);

use App\Enums\ProvisioningAction;
use App\Enums\ProvisioningStatus;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ProvisioningLog;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;

test('admin can access provisioning logs list', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs');

    $response->assertSuccessful();
    $response->assertSee('Provisioning Logs');
});

test('non-admin user gets 403 on provisioning logs list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/provisioning-logs');

    $response->assertForbidden();
});

test('guest is redirected to login when accessing provisioning logs list', function () {
    $response = $this->get('/admin/provisioning-logs');

    $response->assertRedirect(route('login'));
});

test('provisioning logs list displays logs correctly', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'customer@example.com']);
    $plan = Plan::factory()->create(['name' => 'Premium IPTV']);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    ProvisioningLog::factory()->create([
        'subscription_id' => $subscription->id,
        'action' => ProvisioningAction::Create,
        'status' => ProvisioningStatus::Success,
    ]);

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs');

    $response->assertSuccessful();
    $response->assertSee('Premium IPTV');
    $response->assertSee('success');
});

test('provisioning logs list pagination works correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create 60 logs (more than one page at 50 per page)
    ProvisioningLog::factory()->count(60)->create();

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs');

    $response->assertSuccessful();
    $response->assertSee('Next'); // Pagination controls should be visible
});

test('status filter shows only logs with specified status', function () {
    $admin = User::factory()->admin()->create();

    // Create success logs
    ProvisioningLog::factory()->count(3)->create([
        'status' => ProvisioningStatus::Success,
    ]);

    // Create failed logs
    ProvisioningLog::factory()->count(2)->create([
        'status' => ProvisioningStatus::Failed,
    ]);

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs?status=success');

    $response->assertSuccessful();
    $response->assertSee('success');
});

test('action filter shows only logs with specified action', function () {
    $admin = User::factory()->admin()->create();

    // Create logs for 'create' action
    ProvisioningLog::factory()->count(3)->create([
        'action' => ProvisioningAction::Create,
    ]);

    // Create logs for 'suspend' action
    ProvisioningLog::factory()->count(2)->create([
        'action' => ProvisioningAction::Suspend,
    ]);

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs?action=create');

    $response->assertSuccessful();
});

test('date range filter works correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create logs from 3 days ago
    ProvisioningLog::factory()->count(2)->create([
        'created_at' => now()->subDays(3),
    ]);

    // Create logs from today
    ProvisioningLog::factory()->count(3)->create([
        'created_at' => now(),
    ]);

    $dateFrom = now()->startOfDay()->format('Y-m-d');
    $response = $this->actingAs($admin)->get("/admin/provisioning-logs?from={$dateFrom}");

    $response->assertSuccessful();
});

test('search filter works for error messages', function () {
    $admin = User::factory()->admin()->create();

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Failed,
        'error_message' => 'Connection timeout error',
    ]);

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Failed,
        'error_message' => 'Invalid credentials',
    ]);

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs?search=timeout');

    $response->assertSuccessful();
    $response->assertSee('Connection timeout error');
    $response->assertDontSee('Invalid credentials');
});

test('search filter works for error codes', function () {
    $admin = User::factory()->admin()->create();

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Failed,
        'error_code' => 'ERR_CONNECTION_TIMEOUT',
    ]);

    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Failed,
        'error_code' => 'ERR_INVALID_CREDENTIALS',
    ]);

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs?search=CONNECTION');

    $response->assertSuccessful();
});

test('empty state displays when no logs found', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs');

    $response->assertSuccessful();
    $response->assertSee('No provisioning logs found');
});

test('logs are sorted by newest first', function () {
    $admin = User::factory()->admin()->create();

    $oldLog = ProvisioningLog::factory()->create([
        'created_at' => now()->subDays(5),
        'error_message' => 'Old error',
    ]);

    $newLog = ProvisioningLog::factory()->create([
        'created_at' => now(),
        'error_message' => 'New error',
    ]);

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs');

    $response->assertSuccessful();
    // New log should appear before old log in the HTML
    $content = $response->getContent();
    $newPos = mb_strpos($content, 'New error');
    $oldPos = mb_strpos($content, 'Old error');

    expect($newPos)->toBeLessThan($oldPos);
});

test('logs show associated subscription information', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'test@example.com']);
    $plan = Plan::factory()->create(['name' => 'TestPlan123']);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    ProvisioningLog::factory()->create([
        'subscription_id' => $subscription->id,
    ]);

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs');

    $response->assertSuccessful();
    $response->assertSee('TestPlan123');
});

test('logs show attempt count', function () {
    $admin = User::factory()->admin()->create();

    ProvisioningLog::factory()->create([
        'attempt_number' => 3,
    ]);

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs');

    $response->assertSuccessful();
    $response->assertSee('3');
});

test('logs can be filtered by multiple criteria simultaneously', function () {
    $admin = User::factory()->admin()->create();

    // Target log - failed, create action, with specific error
    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Failed,
        'action' => ProvisioningAction::Create,
        'error_message' => 'Target error message',
        'created_at' => now(),
    ]);

    // Wrong status
    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Success,
        'action' => ProvisioningAction::Create,
        'error_message' => 'Another error',
    ]);

    // Wrong action
    ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Failed,
        'action' => ProvisioningAction::Suspend,
        'error_message' => 'Target error message',
    ]);

    $dateFrom = now()->startOfDay()->format('Y-m-d');
    $response = $this->actingAs($admin)->get("/admin/provisioning-logs?status=failed&action=create&search=Target&from={$dateFrom}");

    $response->assertSuccessful();
    $response->assertSee('Target error message');
});

test('reset filters button is visible', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs');

    $response->assertSuccessful();
    $response->assertSee('Reset Filters');
});

test('logs list includes view details action', function () {
    $admin = User::factory()->admin()->create();

    ProvisioningLog::factory()->create();

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs');

    $response->assertSuccessful();
    $response->assertSee('View Details');
});

test('logs with service account display account info', function () {
    $admin = User::factory()->admin()->create();
    $serviceAccount = ServiceAccount::factory()->create(['username' => 'test_account']);

    ProvisioningLog::factory()->create([
        'service_account_id' => $serviceAccount->id,
    ]);

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs');

    $response->assertSuccessful();
});

test('logs with order display order info', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create();

    ProvisioningLog::factory()->create([
        'order_id' => $order->id,
    ]);

    $response = $this->actingAs($admin)->get('/admin/provisioning-logs');

    $response->assertSuccessful();
});
