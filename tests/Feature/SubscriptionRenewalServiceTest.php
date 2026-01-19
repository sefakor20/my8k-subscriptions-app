<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionRenewed;
use App\Mail\SubscriptionRenewalFailed;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentGatewayManager;
use App\Services\PaymentGateways\PaystackGateway;
use App\Services\SubscriptionRenewalService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('does not renew subscription without previous successful order', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDay(),
        ]);

    $service = app(SubscriptionRenewalService::class);
    $result = $service->renewSubscription($subscription);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('No previous successful order');

    Mail::assertQueued(SubscriptionRenewalFailed::class);
});

it('does not renew subscription without stored authorization data', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDay(),
        ]);

    // Create order without authorization data
    Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'status' => OrderStatus::Provisioned,
        'payment_gateway' => PaymentGateway::Paystack,
        'gateway_metadata' => [], // No authorization data
    ]);

    $service = app(SubscriptionRenewalService::class);
    $result = $service->renewSubscription($subscription);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('No stored authorization data');

    Mail::assertQueued(SubscriptionRenewalFailed::class);
});

it('extracts paystack authorization data correctly', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDay(),
        ]);

    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'status' => OrderStatus::Provisioned,
        'payment_gateway' => PaymentGateway::Paystack,
        'gateway_metadata' => [
            'authorization' => [
                'authorization_code' => 'AUTH_test123',
                'card_type' => 'visa',
                'last4' => '1234',
            ],
        ],
    ]);

    $service = app(SubscriptionRenewalService::class);
    $lastOrder = $service->getLastSuccessfulOrder($subscription);

    expect($lastOrder)->not->toBeNull()
        ->and($lastOrder->id)->toBe($order->id);
});

it('extracts stripe authorization data correctly', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDay(),
        ]);

    Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'status' => OrderStatus::Provisioned,
        'payment_gateway' => PaymentGateway::Stripe,
        'gateway_metadata' => [
            'customer' => 'cus_test123',
            'payment_intent' => 'pi_test123',
        ],
    ]);

    $service = app(SubscriptionRenewalService::class);
    $lastOrder = $service->getLastSuccessfulOrder($subscription);

    expect($lastOrder)->not->toBeNull()
        ->and($lastOrder->gateway_metadata['customer'])->toBe('cus_test123');
});

it('extends subscription dates after successful renewal', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDay(),
        ]);

    $originalExpiry = $subscription->expires_at->copy();

    $service = app(SubscriptionRenewalService::class);
    $service->extendSubscription($subscription);

    $subscription->refresh();

    expect($subscription->expires_at->gt($originalExpiry))->toBeTrue()
        ->and($subscription->last_renewal_at)->not->toBeNull()
        ->and($subscription->next_renewal_at)->not->toBeNull()
        ->and($subscription->status)->toBe(SubscriptionStatus::Active);
});

it('handles renewal failure and tracks failure count', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDay(),
            'metadata' => [],
        ]);

    $service = app(SubscriptionRenewalService::class);

    // First failure
    $service->handleRenewalFailure($subscription, 'Payment declined');
    $subscription->refresh();

    expect($subscription->metadata['last_renewal_failure']['failure_count'])->toBe(1)
        ->and($subscription->auto_renew)->toBeTrue();

    // Second failure
    $service->handleRenewalFailure($subscription, 'Card expired');
    $subscription->refresh();

    expect($subscription->metadata['last_renewal_failure']['failure_count'])->toBe(2)
        ->and($subscription->auto_renew)->toBeTrue();

    // Third failure - should disable auto_renew
    $service->handleRenewalFailure($subscription, 'Insufficient funds');
    $subscription->refresh();

    expect($subscription->metadata['last_renewal_failure']['failure_count'])->toBe(3)
        ->and($subscription->auto_renew)->toBeFalse();

    Mail::assertQueued(SubscriptionRenewalFailed::class, 3);
});

it('successfully renews subscription with paystack authorization', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'duration_days' => 30,
        'price' => 10.00,
        'currency' => 'GHS',
    ]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'auto_renew' => true,
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDay(),
        ]);

    Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'status' => OrderStatus::Provisioned,
        'payment_gateway' => PaymentGateway::Paystack,
        'gateway_metadata' => [
            'authorization' => [
                'authorization_code' => 'AUTH_test123',
            ],
        ],
    ]);

    // Mock the gateway to return success
    $mockGateway = Mockery::mock(PaystackGateway::class);
    $mockGateway->shouldReceive('chargeRecurring')
        ->once()
        ->andReturn([
            'success' => true,
            'reference' => 'REF_123',
            'transaction_id' => 'TXN_123',
            'data' => ['status' => 'success'],
        ]);

    $mockManager = Mockery::mock(PaymentGatewayManager::class);
    $mockManager->shouldReceive('gateway')
        ->with(PaymentGateway::Paystack)
        ->andReturn($mockGateway);

    $service = new SubscriptionRenewalService($mockManager);
    $originalExpiry = $subscription->expires_at->copy();

    $result = $service->renewSubscription($subscription);

    expect($result['success'])->toBeTrue()
        ->and($result['order'])->not->toBeNull()
        ->and($result['order']->payment_gateway)->toBe(PaymentGateway::Paystack);

    $subscription->refresh();
    expect($subscription->expires_at->gt($originalExpiry))->toBeTrue();

    Mail::assertQueued(SubscriptionRenewed::class);
});

it('gets last successful order correctly when multiple orders exist', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create();

    // Create older order
    Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'status' => OrderStatus::Provisioned,
        'payment_gateway' => PaymentGateway::Paystack,
        'gateway_metadata' => ['authorization_code' => 'OLD_AUTH'],
        'created_at' => now()->subDays(30),
    ]);

    // Create newer order
    $newerOrder = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'status' => OrderStatus::Provisioned,
        'payment_gateway' => PaymentGateway::Paystack,
        'gateway_metadata' => ['authorization_code' => 'NEW_AUTH'],
        'created_at' => now(),
    ]);

    $service = app(SubscriptionRenewalService::class);
    $lastOrder = $service->getLastSuccessfulOrder($subscription);

    expect($lastOrder->id)->toBe($newerOrder->id)
        ->and($lastOrder->gateway_metadata['authorization_code'])->toBe('NEW_AUTH');
});
