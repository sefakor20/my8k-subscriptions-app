<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\ProvisioningAction;
use App\Enums\ProvisioningStatus;
use App\Enums\ServiceAccountStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\ProvisionNewAccountJob;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ProvisioningLog;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'services.my8k.base_url' => 'https://my8k.me/api/api.php',
        'services.my8k.api_key' => 'test-api-key',
        'services.my8k.timeout' => 30,
    ]);
});

test('provision new account job successfully creates service account', function (): void {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'my8k_plan_code' => 'PLAN_BASIC_M',
        'duration_days' => 30,
    ]);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Pending,
    ]);
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'woocommerce_order_id' => '1001',
        'status' => OrderStatus::PendingProvisioning,
    ]);

    // Mock HTTP response from My8K API
    Http::fake([
        '*' => Http::response([
            'status' => 'OK',
            'user_id' => 'MY8K_123456',
            'username' => 'test_user',
            'password' => 'test_pass',
            'm3u_url' => 'http://server1.my8k.com:8080/get.php?username=test_user&password=test_pass&type=m3u_plus&output=ts',
        ], 200),
    ]);

    // Execute job
    $job = new ProvisionNewAccountJob(
        orderId: $order->id,
        subscriptionId: $subscription->id,
        planId: $plan->id,
    );

    $job->handle();

    // Verify service account was created
    expect(ServiceAccount::count())->toBe(1);

    $serviceAccount = ServiceAccount::first();
    expect($serviceAccount->my8k_account_id)->toBe('MY8K_123456')
        ->and($serviceAccount->username)->toBe('test_user')
        ->and($serviceAccount->password)->toBe('test_pass')
        ->and($serviceAccount->status)->toBe(ServiceAccountStatus::Active)
        ->and($serviceAccount->subscription_id)->toBe($subscription->id)
        ->and($serviceAccount->user_id)->toBe($user->id);

    // Verify order status updated
    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Provisioned)
        ->and($order->provisioned_at)->not->toBeNull();

    // Verify subscription linked to service account
    $subscription->refresh();
    expect($subscription->service_account_id)->toBe($serviceAccount->id);

    // Verify provisioning log was created
    expect(ProvisioningLog::count())->toBe(1);

    $log = ProvisioningLog::first();
    expect($log->action)->toBe(ProvisioningAction::Create)
        ->and($log->status)->toBe(ProvisioningStatus::Success)
        ->and($log->order_id)->toBe($order->id)
        ->and($log->subscription_id)->toBe($subscription->id);
});

test('provision new account job handles api failure and retries', function (): void {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'my8k_plan_code' => 'PLAN_BASIC_M',
        'duration_days' => 30,
    ]);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'status' => OrderStatus::PendingProvisioning,
    ]);

    // Mock HTTP response from My8K API with error
    Http::fake([
        '*' => Http::response([
            'status' => 'ERROR',
            'error' => 'Insufficient credits',
        ], 200),
    ]);

    // Execute job
    $job = new ProvisionNewAccountJob(
        orderId: $order->id,
        subscriptionId: $subscription->id,
        planId: $plan->id,
    );

    $job->handle();

    // Verify no service account was created
    expect(ServiceAccount::count())->toBe(0);

    // Verify provisioning log was created with failure status
    expect(ProvisioningLog::count())->toBe(1);

    $log = ProvisioningLog::first();
    expect($log->action)->toBe(ProvisioningAction::Create)
        ->and($log->status)->toBe(ProvisioningStatus::Failed)
        ->and($log->error_message)->toContain('Insufficient credits');
});

test('provision new account job handles network timeout and throws for retry', function (): void {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'my8k_plan_code' => 'PLAN_BASIC_M',
        'duration_days' => 30,
    ]);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'woocommerce_order_id' => '1002',
    ]);

    // Mock HTTP connection timeout
    Http::fake(function (): void {
        throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
    });

    // Execute job
    $job = new ProvisionNewAccountJob(
        orderId: $order->id,
        subscriptionId: $subscription->id,
        planId: $plan->id,
    );

    // Job should throw exception for retryable errors to trigger queue retry
    expect(fn() => $job->handle())->toThrow(Exception::class);

    // Verify provisioning log indicates retryable error with "Retrying" status
    $log = ProvisioningLog::first();
    expect($log->status)->toBe(ProvisioningStatus::Retrying)
        ->and($log->error_code)->toBe('ERR_TIMEOUT')
        ->and($log->attempt_number)->toBe(1);
});

test('provision new account job handles non-retryable error', function (): void {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'my8k_plan_code' => 'PLAN_BASIC_M',
        'duration_days' => 30,
    ]);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'woocommerce_order_id' => '1003',
        'status' => OrderStatus::PendingProvisioning,
    ]);

    // Mock HTTP response with non-retryable error
    Http::fake([
        '*' => Http::response([
            'status' => 'ERROR',
            'error' => 'Invalid plan code',
        ], 200),
    ]);

    // Execute job
    $job = new ProvisionNewAccountJob(
        orderId: $order->id,
        subscriptionId: $subscription->id,
        planId: $plan->id,
    );

    $job->handle();

    // Verify log indicates non-retryable error
    $log = ProvisioningLog::first();
    expect($log->status)->toBe(ProvisioningStatus::Failed)
        ->and($log->error_message)->toContain('Invalid plan code');
});

test('provision new account job extracts server url from m3u url', function (): void {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'my8k_plan_code' => 'PLAN_BASIC_M',
        'duration_days' => 30,
    ]);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
    ]);

    // Mock HTTP response with custom server URL
    Http::fake([
        '*' => Http::response([
            'status' => 'OK',
            'user_id' => 'MY8K_123456',
            'username' => 'test_user',
            'password' => 'test_pass',
            'm3u_url' => 'http://server2.my8k.com:8081/get.php?username=test_user&password=test_pass',
        ], 200),
    ]);

    // Execute job
    $job = new ProvisionNewAccountJob(
        orderId: $order->id,
        subscriptionId: $subscription->id,
        planId: $plan->id,
    );

    $job->handle();

    // Verify service account has correct server URL
    $serviceAccount = ServiceAccount::first();
    expect($serviceAccount->server_url)->toBe('http://server2.my8k.com:8081');
});

test('provision new account job handles production API response format with credentials in URL', function (): void {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'my8k_plan_code' => 'PLAN_BASIC_M',
        'duration_days' => 30,
    ]);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Pending,
    ]);
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'woocommerce_order_id' => '2001',
        'status' => OrderStatus::PendingProvisioning,
    ]);

    // Mock production-like response (credentials in URL, not separate fields)
    Http::fake([
        '*' => Http::response([
            'status' => 'true',
            'user_id' => '7215981',
            'url' => 'http://server1.my8k.com:8080/get.php?username=fc842f49e0&password=c0a1a42ffe&type=m3u_plus&output=ts',
            'message' => 'Add M3U successful',
            'country' => 'ALL',
        ], 200),
    ]);

    // Execute job
    $job = new ProvisionNewAccountJob(
        orderId: $order->id,
        subscriptionId: $subscription->id,
        planId: $plan->id,
    );

    $job->handle();

    // Verify service account was created with extracted credentials
    expect(ServiceAccount::count())->toBe(1);

    $serviceAccount = ServiceAccount::first();
    expect($serviceAccount->my8k_account_id)->toBe('7215981')
        ->and($serviceAccount->username)->toBe('fc842f49e0')
        ->and($serviceAccount->password)->toBe('c0a1a42ffe')
        ->and($serviceAccount->status)->toBe(ServiceAccountStatus::Active)
        ->and($serviceAccount->subscription_id)->toBe($subscription->id)
        ->and($serviceAccount->user_id)->toBe($user->id)
        ->and($serviceAccount->server_url)->toBe('http://server1.my8k.com:8080');

    // Verify order status updated
    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Provisioned);

    // Verify subscription linked to service account
    $subscription->refresh();
    expect($subscription->service_account_id)->toBe($serviceAccount->id);
});
