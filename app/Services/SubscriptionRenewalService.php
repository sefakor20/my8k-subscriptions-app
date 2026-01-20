<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Enums\PlanChangeStatus;
use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionRenewed;
use App\Mail\SubscriptionRenewalFailed;
use App\Models\Order;
use App\Models\PlanChange;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SubscriptionRenewalService
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager,
        private InvoiceService $invoiceService,
        private PlanChangeService $planChangeService,
        private NotificationService $notificationService,
    ) {}

    /**
     * Renew a subscription by charging the stored payment method.
     *
     * @return array{success: bool, order?: Order, error?: string}
     */
    public function renewSubscription(Subscription $subscription): array
    {
        Log::info('Starting subscription renewal', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $subscription->plan_id,
        ]);

        // Check for scheduled plan change and execute it first
        $scheduledPlanChange = $this->getScheduledPlanChange($subscription);
        if ($scheduledPlanChange) {
            $this->executeScheduledPlanChange($scheduledPlanChange, $subscription);
            // Refresh subscription to get the new plan
            $subscription->refresh();
        }

        // Get the last successful order with authorization data
        $lastOrder = $this->getLastSuccessfulOrder($subscription);

        if (! $lastOrder) {
            $error = 'No previous successful order found with payment authorization';
            $this->handleRenewalFailure($subscription, $error);

            return ['success' => false, 'error' => $error];
        }

        // Check if we have the required authorization data
        $authData = $this->extractAuthorizationData($lastOrder);

        if (empty($authData)) {
            $error = 'No stored authorization data found for recurring payment';
            $this->handleRenewalFailure($subscription, $error);

            return ['success' => false, 'error' => $error];
        }

        try {
            // Charge the payment method (with credit applied if available)
            $chargeResult = $this->chargeForRenewal($subscription, $lastOrder, $authData);

            if (! $chargeResult['success']) {
                $this->handleRenewalFailure($subscription, $chargeResult['error'] ?? 'Payment charge failed');

                return $chargeResult;
            }

            // Create renewal order and extend subscription
            $order = $this->createRenewalOrder($subscription, $lastOrder, $chargeResult);
            $this->extendSubscription($subscription);

            // Clear any payment failure tracking after successful renewal
            if ($subscription->hasPaymentFailure()) {
                $subscription->clearPaymentFailure();
                Log::info('Cleared payment failure after successful renewal', [
                    'subscription_id' => $subscription->id,
                ]);
            }

            // Generate and send invoice
            try {
                $this->invoiceService->processOrderInvoice($order);
            } catch (Throwable $e) {
                Log::error('Failed to process invoice for renewal', [
                    'subscription_id' => $subscription->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Send success notification
            $this->sendRenewalSuccessNotification($subscription, $order);

            Log::info('Subscription renewed successfully', [
                'subscription_id' => $subscription->id,
                'order_id' => $order->id,
                'new_expires_at' => $subscription->expires_at,
            ]);

            return ['success' => true, 'order' => $order];
        } catch (Throwable $e) {
            Log::error('Subscription renewal exception', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->handleRenewalFailure($subscription, $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get a scheduled plan change for this subscription.
     */
    protected function getScheduledPlanChange(Subscription $subscription): ?PlanChange
    {
        return $subscription->planChanges()
            ->where('status', PlanChangeStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->first();
    }

    /**
     * Execute a scheduled plan change at renewal time.
     */
    protected function executeScheduledPlanChange(PlanChange $planChange, Subscription $subscription): void
    {
        Log::info('Executing scheduled plan change at renewal', [
            'plan_change_id' => $planChange->id,
            'subscription_id' => $subscription->id,
            'from_plan' => $planChange->from_plan_id,
            'to_plan' => $planChange->to_plan_id,
        ]);

        $this->planChangeService->executeScheduledChange($planChange);
    }

    /**
     * Get the last successful order with payment authorization.
     */
    public function getLastSuccessfulOrder(Subscription $subscription): ?Order
    {
        return $subscription->orders()
            ->where('status', OrderStatus::Provisioned)
            ->whereNotNull('gateway_metadata')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Extract authorization data from the order based on the gateway.
     *
     * @return array<string, mixed>
     */
    protected function extractAuthorizationData(Order $order): array
    {
        $metadata = $order->gateway_metadata ?? [];

        if ($order->payment_gateway === PaymentGateway::Paystack) {
            // Paystack uses authorization_code
            $authCode = $metadata['authorization']['authorization_code']
                ?? $metadata['authorization_code']
                ?? null;

            if ($authCode) {
                return [
                    'authorization_code' => $authCode,
                    'email' => $order->user->email,
                ];
            }
        }

        if ($order->payment_gateway === PaymentGateway::Stripe) {
            // Stripe uses customer ID
            $customerId = $metadata['customer']
                ?? $metadata['customer_id']
                ?? null;

            if ($customerId) {
                return [
                    'customer' => $customerId,
                    'payment_method' => $metadata['payment_method'] ?? null,
                ];
            }
        }

        return [];
    }

    /**
     * Charge the stored payment method for renewal.
     *
     * @param  array<string, mixed>  $authData
     * @return array{success: bool, reference?: string, transaction_id?: string, data?: array<string, mixed>, error?: string, credit_used?: float}
     */
    protected function chargeForRenewal(Subscription $subscription, Order $lastOrder, array $authData): array
    {
        $plan = $subscription->plan;
        $gateway = $lastOrder->payment_gateway;

        if (! $gateway) {
            return ['success' => false, 'error' => 'No payment gateway found on previous order'];
        }

        // Get the gateway instance
        $gatewayInstance = $this->gatewayManager->gateway($gateway);

        // Get the correct currency and amount for this gateway
        $currency = $plan->getCurrencyFor($gateway->value);
        $amount = $plan->getAmountFor($gateway->value, $currency);

        // Apply credit balance if available
        $creditUsed = 0.0;
        if ($subscription->hasCredit()) {
            $creditUsed = $subscription->useCredit($amount);
            $amount = max(0, $amount - $creditUsed);

            Log::info('Applied credit to renewal', [
                'subscription_id' => $subscription->id,
                'credit_used' => $creditUsed,
                'amount_after_credit' => $amount,
            ]);
        }

        // If credit covers the full amount, no payment needed
        if ($amount <= 0) {
            Log::info('Renewal fully covered by credit', [
                'subscription_id' => $subscription->id,
                'credit_used' => $creditUsed,
            ]);

            return [
                'success' => true,
                'reference' => 'credit_' . Str::uuid(),
                'transaction_id' => null,
                'data' => ['payment_method' => 'credit_balance', 'credit_used' => $creditUsed],
                'credit_used' => $creditUsed,
            ];
        }

        $metadata = [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'renewal' => true,
            'credit_applied' => $creditUsed,
        ];

        $result = $gatewayInstance->chargeRecurring($authData, $amount, $currency, $metadata);
        $result['credit_used'] = $creditUsed;

        return $result;
    }

    /**
     * Create a renewal order after successful payment.
     *
     * @param  array<string, mixed>  $chargeResult
     */
    protected function createRenewalOrder(Subscription $subscription, Order $lastOrder, array $chargeResult): Order
    {
        $plan = $subscription->plan;
        $currency = $plan->getCurrencyFor($lastOrder->payment_gateway->value);
        $fullAmount = $plan->getAmountFor($lastOrder->payment_gateway->value, $currency);
        $creditUsed = $chargeResult['credit_used'] ?? 0.0;
        $amountCharged = max(0, $fullAmount - $creditUsed);

        return DB::transaction(function () use ($subscription, $lastOrder, $chargeResult, $currency, $fullAmount, $creditUsed, $amountCharged) {
            return Order::create([
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'status' => OrderStatus::PendingProvisioning,
                'amount' => $amountCharged,
                'currency' => $currency,
                'payment_method' => $creditUsed >= $fullAmount ? 'credit_balance' : 'recurring',
                'payment_gateway' => $lastOrder->payment_gateway,
                'gateway_transaction_id' => $chargeResult['transaction_id'] ?? null,
                'gateway_session_id' => $chargeResult['reference'] ?? null,
                'gateway_metadata' => array_merge(
                    $lastOrder->gateway_metadata ?? [],
                    $chargeResult['data'] ?? [],
                    [
                        'renewal' => true,
                        'previous_order_id' => $lastOrder->id,
                        'credit_used' => $creditUsed,
                        'original_amount' => $fullAmount,
                    ],
                ),
                'paid_at' => now(),
                'idempotency_key' => 'renewal_' . $subscription->id . '_' . Str::uuid(),
            ]);
        });
    }

    /**
     * Extend the subscription dates after successful renewal.
     */
    public function extendSubscription(Subscription $subscription): void
    {
        $plan = $subscription->plan;
        $durationDays = $plan->duration_days;

        // Calculate new expiration from current expiration (not from now)
        $currentExpiry = $subscription->expires_at;
        $newExpiry = $currentExpiry->isPast()
            ? now()->addDays($durationDays)
            : $currentExpiry->addDays($durationDays);

        $subscription->update([
            'expires_at' => $newExpiry,
            'last_renewal_at' => now(),
            'next_renewal_at' => $newExpiry->subDay(), // Renew 1 day before expiry
            'status' => SubscriptionStatus::Active,
        ]);
    }

    /**
     * Handle renewal failure.
     */
    public function handleRenewalFailure(Subscription $subscription, string $reason): void
    {
        Log::warning('Subscription renewal failed', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'reason' => $reason,
        ]);

        // Update subscription metadata with failure info
        $metadata = $subscription->metadata ?? [];
        $metadata['last_renewal_failure'] = [
            'reason' => $reason,
            'attempted_at' => now()->toIso8601String(),
            'failure_count' => ($metadata['last_renewal_failure']['failure_count'] ?? 0) + 1,
        ];

        $subscription->update(['metadata' => $metadata]);

        // Send failure notification
        $this->sendRenewalFailureNotification($subscription, $reason);

        // Optionally disable auto-renew after multiple failures
        $failureCount = $metadata['last_renewal_failure']['failure_count'];
        if ($failureCount >= 3) {
            Log::info('Disabling auto-renew after 3 failures', ['subscription_id' => $subscription->id]);
            $subscription->update(['auto_renew' => false]);
        }
    }

    /**
     * Send success notification to the user.
     */
    protected function sendRenewalSuccessNotification(Subscription $subscription, Order $order): void
    {
        $this->notificationService->queueMail(
            $subscription->user,
            new SubscriptionRenewed($subscription, $order),
            ['subscription_id' => $subscription->id, 'order_id' => $order->id],
        );
    }

    /**
     * Send failure notification to the user.
     */
    protected function sendRenewalFailureNotification(Subscription $subscription, string $reason): void
    {
        $this->notificationService->queueMail(
            $subscription->user,
            new SubscriptionRenewalFailed($subscription, $reason),
            ['subscription_id' => $subscription->id, 'reason' => $reason],
        );
    }
}
