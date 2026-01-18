<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Http\Middleware\VerifyStripeWebhook;
use App\Jobs\ProvisionNewAccountJob;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Queue::fake();
});

test('stripe webhook rejects request without signature when secret is configured', function (): void {
    config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

    $response = $this->postJson('/api/v1/webhooks/stripe', [
        'type' => 'checkout.session.completed',
        'data' => ['object' => []],
    ]);

    $response->assertStatus(401)
        ->assertJson(['error' => 'Missing webhook signature']);
});

test('stripe checkout.session.completed webhook creates order and dispatches provisioning', function (): void {
    $plan = Plan::factory()->create([
        'is_active' => true,
        'price' => 29.99,
        'currency' => 'USD',
    ]);

    $sessionId = 'cs_test_' . time();

    $payload = [
        'id' => 'evt_test_' . time(),
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $sessionId,
                'payment_status' => 'paid',
                'amount_total' => 2999, // cents
                'currency' => 'usd',
                'customer_email' => 'stripe-customer@example.com',
                'customer_details' => [
                    'email' => 'stripe-customer@example.com',
                    'name' => 'Jane Smith',
                ],
                'payment_intent' => 'pi_test_' . time(),
                'metadata' => [
                    'plan_id' => $plan->id,
                ],
                'mode' => 'payment',
            ],
        ],
    ];

    $response = $this->withoutMiddleware(VerifyStripeWebhook::class)
        ->postJson('/api/v1/webhooks/stripe', $payload);

    $response->assertSuccessful();

    // Assert user was created
    $this->assertDatabaseHas('users', [
        'email' => 'stripe-customer@example.com',
    ]);

    // Assert order was created
    $this->assertDatabaseHas('orders', [
        'payment_gateway' => PaymentGateway::Stripe->value,
        'status' => OrderStatus::PendingProvisioning->value,
        'gateway_session_id' => $sessionId,
    ]);

    // Assert provisioning job was dispatched
    Queue::assertPushed(ProvisionNewAccountJob::class);
});

test('stripe checkout.session.completed webhook handles duplicate sessions', function (): void {
    $user = User::factory()->create(['email' => 'existing-stripe@example.com']);
    $plan = Plan::factory()->create(['is_active' => true]);

    $sessionId = 'cs_duplicate_' . time();

    $payload = [
        'id' => 'evt_test_' . time(),
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $sessionId,
                'payment_status' => 'paid',
                'amount_total' => 2999,
                'currency' => 'usd',
                'customer_email' => 'existing-stripe@example.com',
                'payment_intent' => 'pi_test_' . time(),
                'metadata' => [
                    'plan_id' => $plan->id,
                ],
            ],
        ],
    ];

    // First call should create order
    $this->withoutMiddleware(VerifyStripeWebhook::class)
        ->postJson('/api/v1/webhooks/stripe', $payload)->assertSuccessful();

    $orderCount = Order::count();

    // Second call with same session should not create duplicate
    $this->withoutMiddleware(VerifyStripeWebhook::class)
        ->postJson('/api/v1/webhooks/stripe', $payload)->assertSuccessful();

    // Should still have same number of orders
    expect(Order::count())->toBe($orderCount);
});

test('stripe checkout.session.completed webhook skips unpaid sessions', function (): void {
    $plan = Plan::factory()->create(['is_active' => true]);

    $sessionId = 'cs_unpaid_' . time();

    $payload = [
        'id' => 'evt_test_' . time(),
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $sessionId,
                'payment_status' => 'unpaid', // Not paid
                'amount_total' => 2999,
                'currency' => 'usd',
                'customer_email' => 'unpaid@example.com',
                'metadata' => [
                    'plan_id' => $plan->id,
                ],
            ],
        ],
    ];

    $response = $this->withoutMiddleware(VerifyStripeWebhook::class)
        ->postJson('/api/v1/webhooks/stripe', $payload);

    $response->assertSuccessful();

    // No order should be created for unpaid session
    $this->assertDatabaseMissing('orders', [
        'gateway_session_id' => $sessionId,
    ]);

    // No provisioning job should be dispatched
    Queue::assertNotPushed(ProvisionNewAccountJob::class);
});

test('stripe webhook acknowledges unknown events', function (): void {
    $payload = [
        'id' => 'evt_test_' . time(),
        'type' => 'some.unknown.event',
        'data' => ['object' => []],
    ];

    $response = $this->withoutMiddleware(VerifyStripeWebhook::class)
        ->postJson('/api/v1/webhooks/stripe', $payload);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Event acknowledged']);
});

test('stripe payment_intent.succeeded webhook logs success', function (): void {
    $payload = [
        'id' => 'evt_test_' . time(),
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_' . time(),
                'amount' => 2999,
                'currency' => 'usd',
            ],
        ],
    ];

    $response = $this->withoutMiddleware(VerifyStripeWebhook::class)
        ->postJson('/api/v1/webhooks/stripe', $payload);

    $response->assertSuccessful();
});
