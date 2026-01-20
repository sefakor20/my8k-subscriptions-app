<?php

declare(strict_types=1);

use App\Enums\PaymentGateway;
use App\Enums\PlanChangeExecutionType;
use App\Enums\PlanChangeStatus;
use App\Enums\PlanChangeType;
use App\Enums\SubscriptionStatus;
use App\Mail\PlanChangeConfirmed;
use App\Models\Plan;
use App\Models\PlanChange;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PlanChangeService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('schedules a plan change for next renewal', function () {
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
            'currency' => 'USD',
        ]);

    $service = app(PlanChangeService::class);
    $planChange = $service->scheduleChange($subscription, $newPlan);

    expect($planChange)->toBeInstanceOf(PlanChange::class)
        ->and($planChange->status)->toBe(PlanChangeStatus::Scheduled)
        ->and($planChange->execution_type)->toBe(PlanChangeExecutionType::Scheduled)
        ->and($planChange->type)->toBe(PlanChangeType::Upgrade)
        ->and($planChange->from_plan_id)->toBe($currentPlan->id)
        ->and($planChange->to_plan_id)->toBe($newPlan->id)
        ->and($planChange->scheduled_at->toDateString())->toBe($subscription->expires_at->toDateString());

    // Check subscription is updated with scheduled plan
    $subscription->refresh();
    expect($subscription->scheduled_plan_id)->toBe($newPlan->id)
        ->and($subscription->plan_change_scheduled_at)->not->toBeNull();
});

it('cancels a scheduled plan change', function () {
    $user = User::factory()->create();
    $currentPlan = Plan::factory()->create(['price' => 30.00]);
    $newPlan = Plan::factory()->create(['price' => 60.00]);

    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($currentPlan)
        ->create([
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDays(15),
            'scheduled_plan_id' => $newPlan->id,
            'plan_change_scheduled_at' => now()->addDays(15),
        ]);

    $planChange = PlanChange::factory()
        ->forSubscription($subscription)
        ->forUser($user)
        ->scheduled()
        ->create([
            'from_plan_id' => $currentPlan->id,
            'to_plan_id' => $newPlan->id,
        ]);

    $service = app(PlanChangeService::class);
    $result = $service->cancelChange($planChange);

    expect($result)->toBeTrue();

    $planChange->refresh();
    expect($planChange->status)->toBe(PlanChangeStatus::Cancelled);

    $subscription->refresh();
    expect($subscription->scheduled_plan_id)->toBeNull()
        ->and($subscription->plan_change_scheduled_at)->toBeNull();
});

it('cannot cancel a completed plan change', function () {
    $user = User::factory()->create();
    $currentPlan = Plan::factory()->create(['price' => 30.00]);
    $newPlan = Plan::factory()->create(['price' => 60.00]);

    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($currentPlan)
        ->create([
            'status' => SubscriptionStatus::Active,
        ]);

    $planChange = PlanChange::factory()
        ->forSubscription($subscription)
        ->forUser($user)
        ->completed()
        ->create([
            'from_plan_id' => $currentPlan->id,
            'to_plan_id' => $newPlan->id,
        ]);

    $service = app(PlanChangeService::class);
    $result = $service->cancelChange($planChange);

    expect($result)->toBeFalse();

    $planChange->refresh();
    expect($planChange->status)->toBe(PlanChangeStatus::Completed);
});

it('executes an immediate plan change without payment for downgrade', function () {
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
            'currency' => 'USD',
        ]);

    $service = app(PlanChangeService::class);
    $result = $service->initiateImmediateChange(
        $subscription,
        $newPlan,
        PaymentGateway::Paystack,
    );

    expect($result['requires_payment'])->toBeFalse();

    $planChange = $result['plan_change'];
    expect($planChange->status)->toBe(PlanChangeStatus::Completed)
        ->and($planChange->type)->toBe(PlanChangeType::Downgrade)
        ->and($planChange->credit_amount)->toBeGreaterThan(0);

    // Subscription should be updated to new plan
    $subscription->refresh();
    expect($subscription->plan_id)->toBe($newPlan->id);

    // Credit should be added to subscription
    expect($subscription->credit_balance)->toBeGreaterThan(0);

    Mail::assertQueued(PlanChangeConfirmed::class);
});

it('gets available plans excluding current plan', function () {
    $user = User::factory()->create();
    $currentPlan = Plan::factory()->create(['price' => 30.00, 'is_active' => true]);
    $plan2 = Plan::factory()->create(['price' => 60.00, 'is_active' => true]);
    $plan3 = Plan::factory()->create(['price' => 90.00, 'is_active' => true]);
    $inactivePlan = Plan::factory()->create(['price' => 120.00, 'is_active' => false]); // Inactive plan

    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($currentPlan)
        ->create(['status' => SubscriptionStatus::Active]);

    $service = app(PlanChangeService::class);
    $availablePlans = $service->getAvailablePlans($subscription);

    // Should not contain the current plan or inactive plan
    expect($availablePlans->pluck('id')->toArray())->not->toContain($currentPlan->id)
        ->and($availablePlans->pluck('id')->toArray())->not->toContain($inactivePlan->id)
        ->and($availablePlans->pluck('id')->toArray())->toContain($plan2->id, $plan3->id);
});

it('prevents plan change for expired subscription', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'status' => SubscriptionStatus::Expired,
            'expires_at' => now()->subDays(5),
        ]);

    $service = app(PlanChangeService::class);

    expect($service->canChangePlan($subscription))->toBeFalse();
});

it('allows plan change for active subscription with time remaining', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create([
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDays(15),
        ]);

    $service = app(PlanChangeService::class);

    expect($service->canChangePlan($subscription))->toBeTrue();
});

it('cancels existing pending changes when scheduling new one', function () {
    $user = User::factory()->create();
    $currentPlan = Plan::factory()->create(['price' => 30.00]);
    $plan1 = Plan::factory()->create(['price' => 60.00]);
    $plan2 = Plan::factory()->create(['price' => 90.00]);

    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($currentPlan)
        ->create([
            'status' => SubscriptionStatus::Active,
            'expires_at' => now()->addDays(15),
        ]);

    // Create an existing scheduled change
    $existingChange = PlanChange::factory()
        ->forSubscription($subscription)
        ->forUser($user)
        ->scheduled()
        ->create([
            'from_plan_id' => $currentPlan->id,
            'to_plan_id' => $plan1->id,
        ]);

    $service = app(PlanChangeService::class);
    $newPlanChange = $service->scheduleChange($subscription, $plan2);

    // Old change should be cancelled
    $existingChange->refresh();
    expect($existingChange->status)->toBe(PlanChangeStatus::Cancelled);

    // New change should be scheduled
    expect($newPlanChange->status)->toBe(PlanChangeStatus::Scheduled)
        ->and($newPlanChange->to_plan_id)->toBe($plan2->id);
});
