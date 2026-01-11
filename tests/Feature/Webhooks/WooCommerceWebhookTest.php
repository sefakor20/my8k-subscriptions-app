<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\ExtendAccountJob;
use App\Jobs\ProvisionNewAccountJob;
use App\Jobs\SuspendAccountJob;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\postJson;

beforeEach(function () {
    config([
        'services.woocommerce.webhook_secret' => 'test-webhook-secret',
    ]);

    Queue::fake();
});

// Helper function to sign webhook payload
function signWebhookPayload(array $payload, string $secret = 'test-webhook-secret'): string
{
    $jsonPayload = json_encode($payload);

    return base64_encode(hash_hmac('sha256', $jsonPayload, $secret, true));
}

test('order completed webhook processes successfully and dispatches provisioning job', function () {
    $plan = Plan::factory()->create([
        'woocommerce_id' => '12345',
        'is_active' => true,
    ]);

    $orderData = [
        'id' => 1001,
        'status' => 'completed',
        'total' => '29.99',
        'currency' => 'USD',
        'date_paid' => '2024-01-15T10:00:00',
        'payment_method_title' => 'Credit Card',
        'billing' => [
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
        'line_items' => [
            [
                'product_id' => 12345,
                'name' => 'Basic Plan',
                'quantity' => 1,
            ],
        ],
    ];

    $signature = signWebhookPayload($orderData);

    $response = postJson('/api/v1/webhooks/woocommerce/order-completed', $orderData, [
        'X-WC-Webhook-Signature' => $signature,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Order processed successfully',
        ]);

    // Verify user was created
    expect(User::where('email', 'customer@example.com')->exists())->toBeTrue();

    $user = User::where('email', 'customer@example.com')->first();
    expect($user->name)->toBe('John Doe');

    // Verify subscription was created
    expect(Subscription::where('user_id', $user->id)->exists())->toBeTrue();

    $subscription = Subscription::where('user_id', $user->id)->first();
    expect($subscription->status)->toBe(SubscriptionStatus::Pending)
        ->and($subscription->plan_id)->toBe($plan->id);

    // Verify order was created
    expect(Order::where('woocommerce_order_id', '1001')->exists())->toBeTrue();

    $order = Order::where('woocommerce_order_id', '1001')->first();
    expect($order->status)->toBe(OrderStatus::PendingProvisioning)
        ->and($order->amount)->toBe('29.99')
        ->and($order->subscription_id)->toBe($subscription->id);

    // Verify provisioning job was dispatched
    Queue::assertPushed(ProvisionNewAccountJob::class, function ($job) use ($order, $subscription, $plan) {
        return $job->orderId === $order->id
            && $job->subscriptionId === $subscription->id
            && $job->planId === $plan->id;
    });
});

test('order completed webhook handles duplicate orders with idempotency', function () {
    $plan = Plan::factory()->create([
        'woocommerce_id' => '12345',
        'is_active' => true,
    ]);

    $user = User::factory()->create(['email' => 'customer@example.com']);
    $subscription = Subscription::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);

    Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'woocommerce_order_id' => '1001',
        'amount' => '29.99',
    ]);

    $orderData = [
        'id' => 1001,
        'status' => 'completed',
        'total' => '29.99',
        'currency' => 'USD',
        'date_paid' => '2024-01-15T10:00:00',
        'billing' => [
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
        'line_items' => [
            ['product_id' => 12345],
        ],
    ];

    $signature = signWebhookPayload($orderData);

    $response = postJson('/api/v1/webhooks/woocommerce/order-completed', $orderData, [
        'X-WC-Webhook-Signature' => $signature,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Order already processed',
        ]);

    // Verify no duplicate provisioning job was dispatched
    Queue::assertNothingPushed();
});

test('order completed webhook rejects invalid signature', function () {
    $orderData = [
        'id' => 1001,
        'status' => 'completed',
    ];

    $response = postJson('/api/v1/webhooks/woocommerce/order-completed', $orderData, [
        'X-WC-Webhook-Signature' => 'invalid-signature',
    ]);

    $response->assertUnauthorized()
        ->assertJson([
            'error' => 'Invalid webhook signature',
        ]);

    Queue::assertNothingPushed();
});

test('order completed webhook rejects missing signature', function () {
    $orderData = [
        'id' => 1001,
        'status' => 'completed',
    ];

    $response = postJson('/api/v1/webhooks/woocommerce/order-completed', $orderData);

    $response->assertUnauthorized()
        ->assertJson([
            'error' => 'Missing webhook signature',
        ]);

    Queue::assertNothingPushed();
});

test('order completed webhook returns error when plan not found', function () {
    $orderData = [
        'id' => 1001,
        'status' => 'completed',
        'total' => '29.99',
        'currency' => 'USD',
        'date_paid' => '2024-01-15T10:00:00',
        'billing' => [
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
        'line_items' => [
            ['product_id' => 99999], // Non-existent product
        ],
    ];

    $signature = signWebhookPayload($orderData);

    $response = postJson('/api/v1/webhooks/woocommerce/order-completed', $orderData, [
        'X-WC-Webhook-Signature' => $signature,
    ]);

    $response->assertStatus(500)
        ->assertJson([
            'success' => false,
            'error' => 'Failed to process order',
        ]);

    Queue::assertNothingPushed();
});

test('subscription renewed webhook dispatches extend job', function () {
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'woocommerce_subscription_id' => 'sub_123',
    ]);

    $serviceAccount = ServiceAccount::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
    ]);

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    $subscriptionData = [
        'id' => 'sub_123',
        'status' => 'active',
    ];

    $signature = signWebhookPayload($subscriptionData);

    $response = postJson('/api/v1/webhooks/woocommerce/subscription-renewed', $subscriptionData, [
        'X-WC-Webhook-Signature' => $signature,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Renewal processed successfully',
        ]);

    Queue::assertPushed(ExtendAccountJob::class, function ($job) use ($subscription, $serviceAccount, $plan) {
        return $job->subscriptionId === $subscription->id
            && $job->serviceAccountId === $serviceAccount->id
            && $job->durationDays === $plan->duration_days;
    });
});

test('subscription renewed webhook returns 404 when subscription not found', function () {
    $subscriptionData = [
        'id' => 'sub_nonexistent',
        'status' => 'active',
    ];

    $signature = signWebhookPayload($subscriptionData);

    $response = postJson('/api/v1/webhooks/woocommerce/subscription-renewed', $subscriptionData, [
        'X-WC-Webhook-Signature' => $signature,
    ]);

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'error' => 'Subscription not found',
        ]);

    Queue::assertNothingPushed();
});

test('subscription renewed webhook returns 400 when no service account linked', function () {
    $plan = Plan::factory()->create();
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'woocommerce_subscription_id' => 'sub_123',
        'service_account_id' => null,
    ]);

    $subscriptionData = [
        'id' => 'sub_123',
        'status' => 'active',
    ];

    $signature = signWebhookPayload($subscriptionData);

    $response = postJson('/api/v1/webhooks/woocommerce/subscription-renewed', $subscriptionData, [
        'X-WC-Webhook-Signature' => $signature,
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => 'No service account linked',
        ]);

    Queue::assertNothingPushed();
});

test('subscription cancelled webhook dispatches suspend job', function () {
    $plan = Plan::factory()->create();
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'woocommerce_subscription_id' => 'sub_123',
        'status' => SubscriptionStatus::Active,
    ]);

    $serviceAccount = ServiceAccount::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
    ]);

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    $subscriptionData = [
        'id' => 'sub_123',
        'status' => 'cancelled',
    ];

    $signature = signWebhookPayload($subscriptionData);

    $response = postJson('/api/v1/webhooks/woocommerce/subscription-cancelled', $subscriptionData, [
        'X-WC-Webhook-Signature' => $signature,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Cancellation processed successfully',
        ]);

    // Verify subscription status updated
    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($subscription->auto_renew)->toBeFalse();

    // Verify suspend job was dispatched
    Queue::assertPushed(SuspendAccountJob::class, function ($job) use ($subscription, $serviceAccount) {
        return $job->subscriptionId === $subscription->id
            && $job->serviceAccountId === $serviceAccount->id;
    });
});

test('subscription cancelled webhook handles subscription without service account', function () {
    $plan = Plan::factory()->create();
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'woocommerce_subscription_id' => 'sub_123',
        'status' => SubscriptionStatus::Active,
        'service_account_id' => null,
    ]);

    $subscriptionData = [
        'id' => 'sub_123',
        'status' => 'cancelled',
    ];

    $signature = signWebhookPayload($subscriptionData);

    $response = postJson('/api/v1/webhooks/woocommerce/subscription-cancelled', $subscriptionData, [
        'X-WC-Webhook-Signature' => $signature,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Cancellation processed successfully',
        ]);

    // Verify subscription status updated
    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled);

    // Verify no suspend job dispatched
    Queue::assertNothingPushed();
});

test('payment failed webhook logs error for admin review', function () {
    $plan = Plan::factory()->create();
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'woocommerce_subscription_id' => 'sub_123',
    ]);

    $subscriptionData = [
        'id' => 'sub_123',
        'status' => 'on-hold',
        'billing' => [
            'email' => 'customer@example.com',
        ],
        'payment_method_title' => 'Credit Card',
    ];

    $signature = signWebhookPayload($subscriptionData);

    $response = postJson('/api/v1/webhooks/woocommerce/payment-failed', $subscriptionData, [
        'X-WC-Webhook-Signature' => $signature,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Payment failure logged for review',
        ]);

    // No jobs should be dispatched for payment failures
    Queue::assertNothingPushed();
});

test('payment failed webhook returns 404 when subscription not found', function () {
    $subscriptionData = [
        'id' => 'sub_nonexistent',
        'status' => 'on-hold',
        'billing' => [
            'email' => 'customer@example.com',
        ],
    ];

    $signature = signWebhookPayload($subscriptionData);

    $response = postJson('/api/v1/webhooks/woocommerce/payment-failed', $subscriptionData, [
        'X-WC-Webhook-Signature' => $signature,
    ]);

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'error' => 'Subscription not found',
        ]);
});
