<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Enums\PlanChangeExecutionType;
use App\Enums\PlanChangeStatus;
use App\Enums\PlanChangeType;
use App\Jobs\ProcessPlanChangeJob;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanChange;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanChangeService
{
    public function __construct(
        private ProrationCalculator $prorationCalculator,
        private PaymentGatewayManager $paymentGatewayManager,
    ) {}

    /**
     * Calculate proration for a plan change.
     *
     * @return array{
     *     type: PlanChangeType,
     *     days_remaining: int,
     *     total_days: int,
     *     current_plan_price: float,
     *     new_plan_price: float,
     *     unused_credit: float,
     *     prorated_cost: float,
     *     amount_due: float,
     *     credit_to_apply: float,
     *     currency: string
     * }
     */
    public function calculateProration(
        Subscription $subscription,
        Plan $newPlan,
        ?string $gateway = null,
    ): array {
        return $this->prorationCalculator->calculate($subscription, $newPlan, $gateway);
    }

    /**
     * Initiate an immediate plan change (upgrade).
     * Creates a pending plan change and payment session.
     *
     * @return array{
     *     plan_change: PlanChange,
     *     requires_payment: bool,
     *     checkout_url?: string,
     *     reference?: string
     * }
     */
    public function initiateImmediateChange(
        Subscription $subscription,
        Plan $newPlan,
        PaymentGateway $gateway,
    ): array {
        $proration = $this->calculateProration($subscription, $newPlan, $gateway->value);

        return DB::transaction(function () use ($subscription, $newPlan, $gateway, $proration) {
            // Cancel any existing pending plan changes
            $this->cancelPendingChanges($subscription);

            // Create the plan change record
            $planChange = PlanChange::create([
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'from_plan_id' => $subscription->plan_id,
                'to_plan_id' => $newPlan->id,
                'type' => $proration['type'],
                'status' => PlanChangeStatus::Pending,
                'execution_type' => PlanChangeExecutionType::Immediate,
                'proration_amount' => $proration['amount_due'],
                'credit_amount' => $proration['credit_to_apply'],
                'currency' => $proration['currency'],
                'calculation_details' => $proration,
            ]);

            Log::info('Plan change initiated', [
                'plan_change_id' => $planChange->id,
                'subscription_id' => $subscription->id,
                'from_plan' => $subscription->plan_id,
                'to_plan' => $newPlan->id,
                'type' => $proration['type']->value,
                'amount_due' => $proration['amount_due'],
            ]);

            // If no payment required (downgrade or zero cost), apply immediately
            if ($proration['amount_due'] <= 0) {
                $this->executeImmediateChange($planChange);

                return [
                    'plan_change' => $planChange->fresh(),
                    'requires_payment' => false,
                ];
            }

            // Initiate payment for upgrade
            $paymentResult = $this->initiatePayment($planChange, $subscription->user, $newPlan, $gateway);

            return [
                'plan_change' => $planChange,
                'requires_payment' => true,
                'checkout_url' => $paymentResult['checkout_url'],
                'reference' => $paymentResult['reference'],
            ];
        });
    }

    /**
     * Schedule a plan change for the next renewal.
     */
    public function scheduleChange(
        Subscription $subscription,
        Plan $newPlan,
        ?string $gateway = null,
    ): PlanChange {
        $proration = $this->calculateProration($subscription, $newPlan, $gateway);

        return DB::transaction(function () use ($subscription, $newPlan, $proration) {
            // Cancel any existing pending plan changes
            $this->cancelPendingChanges($subscription);

            // Create the scheduled plan change
            $planChange = PlanChange::create([
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'from_plan_id' => $subscription->plan_id,
                'to_plan_id' => $newPlan->id,
                'type' => $proration['type'],
                'status' => PlanChangeStatus::Scheduled,
                'execution_type' => PlanChangeExecutionType::Scheduled,
                'proration_amount' => 0,
                'credit_amount' => $proration['credit_to_apply'],
                'currency' => $proration['currency'],
                'calculation_details' => $proration,
                'scheduled_at' => $subscription->expires_at,
            ]);

            // Update subscription with scheduled plan change
            $subscription->update([
                'scheduled_plan_id' => $newPlan->id,
                'plan_change_scheduled_at' => $subscription->expires_at,
            ]);

            Log::info('Plan change scheduled', [
                'plan_change_id' => $planChange->id,
                'subscription_id' => $subscription->id,
                'scheduled_at' => $subscription->expires_at,
            ]);

            return $planChange;
        });
    }

    /**
     * Execute a plan change immediately (after payment success or for free changes).
     */
    public function executeImmediateChange(PlanChange $planChange): bool
    {
        if (! $planChange->isPending() && ! $planChange->isScheduled()) {
            Log::warning('Cannot execute plan change - invalid status', [
                'plan_change_id' => $planChange->id,
                'status' => $planChange->status->value,
            ]);

            return false;
        }

        return DB::transaction(function () use ($planChange) {
            $subscription = $planChange->subscription;
            $newPlan = $planChange->toPlan;

            // Update subscription plan
            $subscription->update([
                'plan_id' => $newPlan->id,
                'scheduled_plan_id' => null,
                'plan_change_scheduled_at' => null,
            ]);

            // Add credit to subscription if downgrade
            if ($planChange->credit_amount > 0) {
                $subscription->addCredit((float) $planChange->credit_amount);
            }

            // Mark plan change as completed
            $planChange->markAsCompleted();

            Log::info('Plan change executed', [
                'plan_change_id' => $planChange->id,
                'subscription_id' => $subscription->id,
                'new_plan_id' => $newPlan->id,
            ]);

            // Dispatch job to update My8K service account
            ProcessPlanChangeJob::dispatch($planChange);

            return true;
        });
    }

    /**
     * Execute a scheduled plan change at renewal time.
     */
    public function executeScheduledChange(PlanChange $planChange): bool
    {
        if (! $planChange->isScheduled()) {
            return false;
        }

        return $this->executeImmediateChange($planChange);
    }

    /**
     * Cancel a pending or scheduled plan change.
     */
    public function cancelChange(PlanChange $planChange): bool
    {
        if (! $planChange->canBeCancelled()) {
            Log::warning('Cannot cancel plan change', [
                'plan_change_id' => $planChange->id,
                'status' => $planChange->status->value,
            ]);

            return false;
        }

        return DB::transaction(function () use ($planChange) {
            $subscription = $planChange->subscription;

            // Clear scheduled plan from subscription if it was scheduled
            if ($planChange->isScheduled()) {
                $subscription->clearScheduledPlanChange();
            }

            $planChange->markAsCancelled();

            Log::info('Plan change cancelled', [
                'plan_change_id' => $planChange->id,
                'subscription_id' => $subscription->id,
            ]);

            return true;
        });
    }

    /**
     * Handle successful payment for a plan change.
     *
     * @param  array<string, mixed>  $verificationData
     */
    public function handlePaymentSuccess(
        PlanChange $planChange,
        array $verificationData,
    ): bool {
        if (! $planChange->isPending()) {
            return false;
        }

        return DB::transaction(function () use ($planChange, $verificationData) {
            // Create order for the payment
            $gateway = PaymentGateway::from($planChange->payment_gateway);
            $order = $this->createOrderForPlanChange(
                $planChange,
                $gateway,
                $planChange->payment_reference,
                $verificationData,
            );

            // Link order to plan change
            $planChange->update(['order_id' => $order->id]);

            // Execute the plan change
            return $this->executeImmediateChange($planChange);
        });
    }

    /**
     * Handle failed payment for a plan change.
     */
    public function handlePaymentFailure(PlanChange $planChange, string $reason): bool
    {
        return $planChange->markAsFailed($reason);
    }

    /**
     * Get available plans for switching (excluding current plan).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Plan>
     */
    public function getAvailablePlans(Subscription $subscription): \Illuminate\Database\Eloquent\Collection
    {
        return Plan::active()
            ->where('id', '!=', $subscription->plan_id)
            ->orderBy('price')
            ->get();
    }

    /**
     * Check if subscription can change plan.
     */
    public function canChangePlan(Subscription $subscription): bool
    {
        // Must be active
        if (! $subscription->isActive()) {
            return false;
        }

        // Must have time remaining
        if ($subscription->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Cancel all pending changes for a subscription.
     */
    private function cancelPendingChanges(Subscription $subscription): void
    {
        $pendingChanges = $subscription->planChanges()
            ->whereIn('status', [PlanChangeStatus::Pending, PlanChangeStatus::Scheduled])
            ->get();

        foreach ($pendingChanges as $change) {
            $change->markAsCancelled();
        }

        // Clear scheduled plan from subscription
        $subscription->clearScheduledPlanChange();
    }

    /**
     * Initiate payment session for plan change.
     *
     * @return array{checkout_url: string, reference: string}
     */
    private function initiatePayment(
        PlanChange $planChange,
        User $user,
        Plan $newPlan,
        PaymentGateway $gateway,
    ): array {
        $gatewayService = $this->paymentGatewayManager->gateway($gateway);

        // Create a custom payment session for the plan change
        $metadata = [
            'type' => 'plan_change',
            'plan_change_id' => $planChange->id,
            'subscription_id' => $planChange->subscription_id,
            'from_plan_id' => $planChange->from_plan_id,
            'to_plan_id' => $planChange->to_plan_id,
            'callback_url' => route('plan-change.callback', ['gateway' => $gateway->value]),
        ];

        $result = $gatewayService->initiatePayment($user, $newPlan, $metadata);

        // Store the reference in plan change
        $planChange->update([
            'payment_reference' => $result['reference'],
            'payment_gateway' => $gateway->value,
        ]);

        return $result;
    }

    /**
     * Create order for completed plan change payment.
     */
    public function createOrderForPlanChange(
        PlanChange $planChange,
        PaymentGateway $gateway,
        string $reference,
        array $paymentData,
    ): Order {
        $subscription = $planChange->subscription;
        $user = $planChange->user;

        return Order::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'status' => OrderStatus::PendingProvisioning,
            'amount' => $planChange->proration_amount,
            'currency' => $planChange->currency,
            'payment_method' => $gateway->value,
            'payment_gateway' => $gateway,
            'gateway_transaction_id' => $reference,
            'gateway_metadata' => $paymentData,
            'paid_at' => now(),
            'idempotency_key' => hash('sha256', "plan_change:{$planChange->id}:{$reference}"),
            'webhook_payload' => $paymentData,
        ]);
    }
}
