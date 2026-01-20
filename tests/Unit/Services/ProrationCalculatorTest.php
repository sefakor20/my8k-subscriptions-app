<?php

declare(strict_types=1);

use App\Enums\PlanChangeType;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ProrationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->calculator = new ProrationCalculator();
});

it('calculates days remaining in billing period', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDays(15),
        ]);

    $daysRemaining = $this->calculator->getDaysRemaining($subscription);

    // Allow for potential off-by-one due to timing
    expect($daysRemaining)->toBeGreaterThanOrEqual(14)
        ->and($daysRemaining)->toBeLessThanOrEqual(15);
});

it('returns zero days remaining for expired subscription', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'status' => SubscriptionStatus::Expired,
            'expires_at' => now()->subDays(5),
        ]);

    $daysRemaining = $this->calculator->getDaysRemaining($subscription);

    expect($daysRemaining)->toBe(0);
});

it('calculates unused credit correctly', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'duration_days' => 30,
        'price' => 30.00,
        'currency' => 'USD',
    ]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDays(15),
        ]);

    // With approximately 15 days remaining out of 30, and a $30 plan, unused credit should be around $15
    $unusedCredit = $this->calculator->calculateUnusedCredit($subscription);

    expect($unusedCredit)->toBeGreaterThanOrEqual(14.0)
        ->and($unusedCredit)->toBeLessThanOrEqual(16.0);
});

it('calculates prorated cost for new plan', function () {
    $newPlan = Plan::factory()->create([
        'duration_days' => 30,
        'price' => 60.00,
        'currency' => 'USD',
    ]);

    // With 15 days remaining and a $60 plan, prorated cost should be $30
    $proratedCost = $this->calculator->calculateProratedCost($newPlan, 15, null, 'USD');

    expect($proratedCost)->toBe(30.00);
});

it('calculates upgrade proration correctly', function () {
    $user = User::factory()->create();
    $currentPlan = Plan::factory()->create([
        'name' => 'Basic',
        'duration_days' => 30,
        'price' => 30.00,
        'currency' => 'USD',
    ]);
    $newPlan = Plan::factory()->create([
        'name' => 'Pro',
        'duration_days' => 30,
        'price' => 60.00,
        'currency' => 'USD',
    ]);

    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($currentPlan)
        ->create([
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDays(15),
        ]);

    $result = $this->calculator->calculate($subscription, $newPlan);

    expect($result['type'])->toBe(PlanChangeType::Upgrade)
        ->and($result['days_remaining'])->toBeGreaterThanOrEqual(14)
        ->and($result['unused_credit'])->toBeGreaterThan(0)
        ->and($result['prorated_cost'])->toBeGreaterThan(0)
        ->and($result['amount_due'])->toBeGreaterThan(0)
        ->and($result['credit_to_apply'])->toEqual(0);
});

it('calculates downgrade proration correctly', function () {
    $user = User::factory()->create();
    $currentPlan = Plan::factory()->create([
        'name' => 'Pro',
        'duration_days' => 30,
        'price' => 60.00,
        'currency' => 'USD',
    ]);
    $newPlan = Plan::factory()->create([
        'name' => 'Basic',
        'duration_days' => 30,
        'price' => 30.00,
        'currency' => 'USD',
    ]);

    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($currentPlan)
        ->create([
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDays(15),
        ]);

    $result = $this->calculator->calculate($subscription, $newPlan);

    expect($result['type'])->toBe(PlanChangeType::Downgrade)
        ->and($result['days_remaining'])->toBeGreaterThanOrEqual(14)
        ->and($result['unused_credit'])->toBeGreaterThan(0)
        ->and($result['prorated_cost'])->toBeGreaterThan(0)
        ->and($result['amount_due'])->toEqual(0)
        ->and($result['credit_to_apply'])->toBeGreaterThan(0);
});

it('identifies upgrade correctly', function () {
    $user = User::factory()->create();
    $currentPlan = Plan::factory()->create([
        'price' => 30.00,
        'currency' => 'USD',
    ]);
    $newPlan = Plan::factory()->create([
        'price' => 60.00,
        'currency' => 'USD',
    ]);

    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($currentPlan)
        ->create([
            'status' => SubscriptionStatus::Active,
        ]);

    expect($this->calculator->isUpgrade($subscription, $newPlan))->toBeTrue()
        ->and($this->calculator->isDowngrade($subscription, $newPlan))->toBeFalse();
});

it('identifies downgrade correctly', function () {
    $user = User::factory()->create();
    $currentPlan = Plan::factory()->create([
        'price' => 60.00,
        'currency' => 'USD',
    ]);
    $newPlan = Plan::factory()->create([
        'price' => 30.00,
        'currency' => 'USD',
    ]);

    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($currentPlan)
        ->create([
            'status' => SubscriptionStatus::Active,
        ]);

    expect($this->calculator->isDowngrade($subscription, $newPlan))->toBeTrue()
        ->and($this->calculator->isUpgrade($subscription, $newPlan))->toBeFalse();
});

it('handles zero days remaining', function () {
    $user = User::factory()->create();
    $currentPlan = Plan::factory()->create([
        'duration_days' => 30,
        'price' => 30.00,
        'currency' => 'USD',
    ]);
    $newPlan = Plan::factory()->create([
        'duration_days' => 30,
        'price' => 60.00,
        'currency' => 'USD',
    ]);

    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($currentPlan)
        ->create([
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->subHour(), // Already expired
        ]);

    $result = $this->calculator->calculate($subscription, $newPlan);

    expect($result['days_remaining'])->toBe(0)
        ->and($result['unused_credit'])->toEqual(0)
        ->and($result['prorated_cost'])->toEqual(0)
        ->and($result['amount_due'])->toEqual(0);
});
