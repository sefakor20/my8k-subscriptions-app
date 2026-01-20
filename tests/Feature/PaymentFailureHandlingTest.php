<?php

declare(strict_types=1);

use App\Console\Commands\SendSuspensionWarningsCommand;
use App\Console\Commands\SuspendFailedPaymentsCommand;
use App\Enums\SubscriptionStatus;
use App\Jobs\SuspendAccountJob;
use App\Mail\SuspensionWarning;
use App\Models\Plan;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Mail::fake();
    Queue::fake();
});

describe('Subscription payment failure tracking', function () {
    it('records a payment failure', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(7),
            ]);

        $subscription->recordPaymentFailure();

        expect($subscription->payment_failed_at)->not->toBeNull()
            ->and($subscription->payment_failure_count)->toBe(1);
    });

    it('increments failure count on subsequent failures', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(7),
                'payment_failed_at' => now()->subDays(2),
                'payment_failure_count' => 2,
            ]);

        $subscription->recordPaymentFailure();

        expect($subscription->payment_failure_count)->toBe(3);
    });

    it('clears payment failure tracking', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'payment_failed_at' => now()->subDays(5),
                'payment_failure_count' => 3,
                'suspension_warning_sent' => true,
            ]);

        $subscription->clearPaymentFailure();

        expect($subscription->payment_failed_at)->toBeNull()
            ->and($subscription->payment_failure_count)->toBe(0)
            ->and($subscription->suspension_warning_sent)->toBeFalse();
    });

    it('identifies subscription in grace period', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(3),
                'payment_failed_at' => now()->subDays(1),
            ]);

        expect($subscription->isInGracePeriod())->toBeTrue()
            ->and($subscription->gracePeriodExpired())->toBeFalse();
    });

    it('identifies expired grace period', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->subDays(1),
                'payment_failed_at' => now()->subDays(7),
            ]);

        expect($subscription->isInGracePeriod())->toBeFalse()
            ->and($subscription->gracePeriodExpired())->toBeTrue();
    });
});

describe('SuspendFailedPaymentsCommand', function () {
    it('suspends subscriptions with expired grace period', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->subDays(1),
                'payment_failed_at' => now()->subDays(7),
            ]);

        // Create service account linked to subscription
        $serviceAccount = ServiceAccount::factory()->create([
            'subscription_id' => $subscription->id,
        ]);

        $this->artisan(SuspendFailedPaymentsCommand::class)
            ->assertSuccessful();

        Queue::assertPushed(SuspendAccountJob::class, function ($job) use ($subscription, $serviceAccount) {
            return $job->subscriptionId === $subscription->id
                && $job->serviceAccountId === $serviceAccount->id;
        });
    });

    it('does not suspend subscriptions still in grace period', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(3),
                'payment_failed_at' => now()->subDays(1),
            ]);

        ServiceAccount::factory()->create([
            'subscription_id' => $subscription->id,
        ]);

        $this->artisan(SuspendFailedPaymentsCommand::class)
            ->assertSuccessful();

        Queue::assertNotPushed(SuspendAccountJob::class);
    });

    it('supports dry-run mode', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->subDays(1),
                'payment_failed_at' => now()->subDays(7),
            ]);

        ServiceAccount::factory()->create([
            'subscription_id' => $subscription->id,
        ]);

        $this->artisan(SuspendFailedPaymentsCommand::class, ['--dry-run' => true])
            ->assertSuccessful();

        Queue::assertNotPushed(SuspendAccountJob::class);
    });
});

describe('SendSuspensionWarningsCommand', function () {
    it('sends warning emails to subscriptions expiring soon with failed payments', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(1),
                'payment_failed_at' => now()->subDays(5),
                'suspension_warning_sent' => false,
            ]);

        $this->artisan(SendSuspensionWarningsCommand::class, ['--days' => 2])
            ->assertSuccessful();

        Mail::assertQueued(SuspensionWarning::class);
    });

    it('does not send duplicate warnings', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(1),
                'payment_failed_at' => now()->subDays(5),
                'suspension_warning_sent' => true,
            ]);

        $this->artisan(SendSuspensionWarningsCommand::class, ['--days' => 2])
            ->assertSuccessful();

        Mail::assertNotQueued(SuspensionWarning::class);
    });

    it('does not warn subscriptions without payment failures', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(1),
                'payment_failed_at' => null,
                'suspension_warning_sent' => false,
            ]);

        $this->artisan(SendSuspensionWarningsCommand::class, ['--days' => 2])
            ->assertSuccessful();

        Mail::assertNotQueued(SuspensionWarning::class);
    });

    it('marks warning as sent after sending', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(1),
                'payment_failed_at' => now()->subDays(5),
                'suspension_warning_sent' => false,
            ]);

        $this->artisan(SendSuspensionWarningsCommand::class, ['--days' => 2])
            ->assertSuccessful();

        $subscription->refresh();
        expect($subscription->suspension_warning_sent)->toBeTrue();
    });
});

describe('Subscription scopes', function () {
    it('finds subscriptions ready for suspension', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        // Ready for suspension
        $toSuspend = Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->subDays(1),
                'payment_failed_at' => now()->subDays(7),
            ]);

        // Not ready - still in grace period
        Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(3),
                'payment_failed_at' => now()->subDays(1),
            ]);

        // Not ready - no payment failure
        Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->subDays(1),
                'payment_failed_at' => null,
            ]);

        $readyForSuspension = Subscription::readyForSuspension()->get();

        expect($readyForSuspension)->toHaveCount(1)
            ->and($readyForSuspension->first()->id)->toBe($toSuspend->id);
    });

    it('finds subscriptions needing suspension warning', function () {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        // Needs warning
        $needsWarning = Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(1),
                'payment_failed_at' => now()->subDays(5),
                'suspension_warning_sent' => false,
            ]);

        // Already warned
        Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(1),
                'payment_failed_at' => now()->subDays(5),
                'suspension_warning_sent' => true,
            ]);

        // No payment failure
        Subscription::factory()
            ->forUser($user)
            ->forPlan($plan)
            ->create([
                'status' => SubscriptionStatus::Active,
                'expires_at' => now()->addDays(1),
                'payment_failed_at' => null,
                'suspension_warning_sent' => false,
            ]);

        $needingWarning = Subscription::needingSuspensionWarning(2)->get();

        expect($needingWarning)->toHaveCount(1)
            ->and($needingWarning->first()->id)->toBe($needsWarning->id);
    });
});
