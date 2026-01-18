<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Jobs\ProvisionNewAccountJob;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    config(['services.paystack.webhook_secret' => 'test-secret']);
    Queue::fake();
});

function signPaystackPayload(array $payload, string $secret = 'test-secret'): string
{
    return hash_hmac('sha512', json_encode($payload), $secret);
}

test('paystack webhook rejects request without signature', function (): void {
    $response = $this->postJson('/api/v1/webhooks/paystack', [
        'event' => 'charge.success',
        'data' => [],
    ]);

    $response->assertStatus(401)
        ->assertJson(['error' => 'Missing webhook signature']);
});

test('paystack webhook rejects request with invalid signature', function (): void {
    $payload = [
        'event' => 'charge.success',
        'data' => [],
    ];

    $response = $this->postJson('/api/v1/webhooks/paystack', $payload, [
        'X-Paystack-Signature' => 'invalid-signature',
    ]);

    $response->assertStatus(401)
        ->assertJson(['error' => 'Invalid webhook signature']);
});

test('paystack charge.success webhook creates order and dispatches provisioning', function (): void {
    $plan = Plan::factory()->create([
        'is_active' => true,
        'price' => 29.99,
        'currency' => 'GHS',
    ]);

    $payload = [
        'event' => 'charge.success',
        'data' => [
            'reference' => 'PS_TEST_' . time(),
            'amount' => 2999, // kobo
            'currency' => 'GHS',
            'channel' => 'card',
            'customer' => [
                'email' => 'customer@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
            'metadata' => [
                'plan_id' => $plan->id,
            ],
        ],
    ];

    $signature = signPaystackPayload($payload);

    $response = $this->postJson('/api/v1/webhooks/paystack', $payload, [
        'X-Paystack-Signature' => $signature,
    ]);

    $response->assertSuccessful();

    // Assert user was created
    $this->assertDatabaseHas('users', [
        'email' => 'customer@example.com',
        'name' => 'John Doe',
    ]);

    // Assert order was created
    $this->assertDatabaseHas('orders', [
        'payment_gateway' => PaymentGateway::Paystack->value,
        'status' => OrderStatus::PendingProvisioning->value,
    ]);

    // Assert provisioning job was dispatched
    Queue::assertPushed(ProvisionNewAccountJob::class);
});

test('paystack charge.success webhook handles duplicate transactions', function (): void {
    $user = User::factory()->create(['email' => 'existing@example.com']);
    $plan = Plan::factory()->create(['is_active' => true]);

    // Create existing order with the same reference
    $reference = 'PS_DUPLICATE_' . time();

    // First webhook call
    $payload = [
        'event' => 'charge.success',
        'data' => [
            'reference' => $reference,
            'amount' => 2999,
            'currency' => 'GHS',
            'channel' => 'card',
            'customer' => [
                'email' => 'existing@example.com',
            ],
            'metadata' => [
                'plan_id' => $plan->id,
            ],
        ],
    ];

    $signature = signPaystackPayload($payload);

    // First call should create order
    $this->postJson('/api/v1/webhooks/paystack', $payload, [
        'X-Paystack-Signature' => $signature,
    ])->assertSuccessful();

    $orderCount = Order::count();

    // Second call with same reference should not create duplicate
    $this->postJson('/api/v1/webhooks/paystack', $payload, [
        'X-Paystack-Signature' => $signature,
    ])->assertSuccessful();

    // Should still have same number of orders
    expect(Order::count())->toBe($orderCount);
});

test('paystack webhook acknowledges unknown events', function (): void {
    $payload = [
        'event' => 'some.unknown.event',
        'data' => [],
    ];

    $signature = signPaystackPayload($payload);

    $response = $this->postJson('/api/v1/webhooks/paystack', $payload, [
        'X-Paystack-Signature' => $signature,
    ]);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Event acknowledged']);
});

test('paystack charge.failed webhook logs failure', function (): void {
    $payload = [
        'event' => 'charge.failed',
        'data' => [
            'reference' => 'PS_FAILED_' . time(),
            'gateway_response' => 'Declined',
            'customer' => [
                'email' => 'customer@example.com',
            ],
        ],
    ];

    $signature = signPaystackPayload($payload);

    $response = $this->postJson('/api/v1/webhooks/paystack', $payload, [
        'X-Paystack-Signature' => $signature,
    ]);

    $response->assertSuccessful();
});
