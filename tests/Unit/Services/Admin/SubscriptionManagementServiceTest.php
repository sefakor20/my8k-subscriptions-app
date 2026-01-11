<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Jobs\ProvisionNewAccountJob;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Admin\SubscriptionManagementService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->service = new SubscriptionManagementService();
});

test('getSubscriptionsWithFilters returns paginated subscriptions', function () {
    $plan = Plan::factory()->create();
    Subscription::factory()->count(10)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $result = $this->service->getSubscriptionsWithFilters([], 5);

    expect($result)->toHaveCount(5);
    expect($result->total())->toBe(10);
});

test('getSubscriptionsWithFilters filters by search term in email', function () {
    $plan = Plan::factory()->create();
    $targetUser = User::factory()->create(['email' => 'target@example.com']);
    $otherUser = User::factory()->create(['email' => 'other@example.com']);

    Subscription::factory()->create([
        'user_id' => $targetUser->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->create([
        'user_id' => $otherUser->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $result = $this->service->getSubscriptionsWithFilters(['search' => 'target'], 50);

    expect($result->total())->toBe(1);
    expect($result->first()->user->email)->toBe('target@example.com');
});

test('getSubscriptionsWithFilters filters by search term in name', function () {
    $plan = Plan::factory()->create();
    $targetUser = User::factory()->create(['name' => 'Target User']);
    $otherUser = User::factory()->create(['name' => 'Other User']);

    Subscription::factory()->create([
        'user_id' => $targetUser->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->create([
        'user_id' => $otherUser->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $result = $this->service->getSubscriptionsWithFilters(['search' => 'Target'], 50);

    expect($result->total())->toBe(1);
    expect($result->first()->user->name)->toBe('Target User');
});

test('getSubscriptionsWithFilters filters by status', function () {
    $plan = Plan::factory()->create();

    Subscription::factory()->count(3)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->count(2)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Expired,
    ]);

    $result = $this->service->getSubscriptionsWithFilters(['status' => 'active'], 50);

    expect($result->total())->toBe(3);
    expect($result->every(fn($sub) => $sub->status === SubscriptionStatus::Active))->toBeTrue();
});

test('getSubscriptionsWithFilters filters by plan', function () {
    $plan1 = Plan::factory()->create();
    $plan2 = Plan::factory()->create();

    Subscription::factory()->count(4)->create([
        'plan_id' => $plan1->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->count(3)->create([
        'plan_id' => $plan2->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $result = $this->service->getSubscriptionsWithFilters(['plan_id' => $plan1->id], 50);

    expect($result->total())->toBe(4);
    expect($result->every(fn($sub) => $sub->plan_id === $plan1->id))->toBeTrue();
});

test('getSubscriptionsWithFilters filters by date range', function () {
    $plan = Plan::factory()->create();

    // Old subscriptions
    Subscription::factory()->count(2)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'created_at' => now()->subDays(10),
    ]);

    // Recent subscriptions
    Subscription::factory()->count(3)->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'created_at' => now(),
    ]);

    $dateFrom = now()->startOfDay()->format('Y-m-d');
    $result = $this->service->getSubscriptionsWithFilters([
        'date_from' => $dateFrom,
    ], 50);

    expect($result->total())->toBe(3);
});

test('getSubscriptionsWithFilters eager loads relationships', function () {
    $plan = Plan::factory()->create();
    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $result = $this->service->getSubscriptionsWithFilters([], 50);

    expect($result->first()->relationLoaded('user'))->toBeTrue();
    expect($result->first()->relationLoaded('plan'))->toBeTrue();
    expect($result->first()->relationLoaded('serviceAccount'))->toBeTrue();
});

test('extendSubscription adds days to expires_at', function () {
    $plan = Plan::factory()->create();
    $originalExpiresAt = now()->addDays(30);

    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => $originalExpiresAt,
    ]);

    $updated = $this->service->extendSubscription($subscription->id, 15);

    // Check that the expiry date has been extended by at least 14 days (allowing for time drift)
    expect($updated->expires_at->greaterThan($originalExpiresAt->copy()->addDays(14)))->toBeTrue();
});

test('suspendSubscription changes status and sets suspended_at', function () {
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $updated = $this->service->suspendSubscription($subscription->id);

    expect($updated->status)->toBe(SubscriptionStatus::Suspended);
    expect($updated->suspended_at)->not->toBeNull();
});

test('suspendSubscription can include a reason', function () {
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $updated = $this->service->suspendSubscription($subscription->id, 'Payment failed');

    expect($updated->suspension_reason)->toBe('Payment failed');
});

test('reactivateSubscription changes status to active and clears suspension data', function () {
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Suspended,
        'suspended_at' => now(),
        'suspension_reason' => 'Test reason',
    ]);

    $updated = $this->service->reactivateSubscription($subscription->id);

    expect($updated->status)->toBe(SubscriptionStatus::Active);
    expect($updated->suspended_at)->toBeNull();
    expect($updated->suspension_reason)->toBeNull();
});

test('cancelSubscription changes status to cancelled and sets cancelled_at', function () {
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $updated = $this->service->cancelSubscription($subscription->id);

    expect($updated->status)->toBe(SubscriptionStatus::Cancelled);
    expect($updated->cancelled_at)->not->toBeNull();
});

test('retryProvisioning dispatches provision job', function () {
    Queue::fake();

    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    // Create an order for the subscription
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
    ]);

    $this->service->retryProvisioning($subscription->id);

    Queue::assertPushed(ProvisionNewAccountJob::class, function ($job) use ($order, $subscription) {
        return $job->orderId === $order->id && $job->subscriptionId === $subscription->id;
    });
});
