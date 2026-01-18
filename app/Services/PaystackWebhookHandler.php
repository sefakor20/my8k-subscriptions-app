<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class PaystackWebhookHandler
{
    public function __construct(
        private SubscriptionOrderService $subscriptionService,
    ) {}

    /**
     * Process charge.success webhook event.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handleChargeSuccess(array $data): array
    {
        $reference = $data['reference'] ?? '';
        $metadata = $data['metadata'] ?? [];

        // Get customer email
        $email = $data['customer']['email'] ?? null;

        if (! $email) {
            throw new InvalidArgumentException('No customer email in webhook data');
        }

        // Find plan from metadata
        $planId = $metadata['plan_id'] ?? null;
        $plan = $planId ? Plan::find($planId) : null;

        if (! $plan) {
            throw new RuntimeException('No valid plan found in webhook metadata');
        }

        // Prepare payment data
        $paymentData = [
            'amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? 'GHS',
            'channel' => $data['channel'] ?? 'card',
            'customer' => $data['customer'] ?? [],
            'authorization' => $data['authorization'] ?? null,
            'metadata' => $metadata,
            'ip_address' => $data['ip_address'] ?? null,
        ];

        // Use shared service (idempotent)
        $result = $this->subscriptionService->createSubscriptionAndOrder(
            PaymentGateway::Paystack,
            $reference,
            $email,
            $plan,
            $paymentData,
        );

        if ($result['duplicate']) {
            Log::info('Paystack charge.success already processed', ['reference' => $reference]);
        } else {
            Log::info('Paystack charge.success processed', [
                'reference' => $reference,
                'user_id' => $result['user']->id,
                'order_id' => $result['order']->id,
            ]);
        }

        return [
            'success' => $result['success'],
            'message' => $result['duplicate'] ? 'Transaction already processed' : 'Charge processed successfully',
            'duplicate' => $result['duplicate'],
            'user_id' => $result['user']?->id,
            'user_was_created' => $result['user_was_created'] ?? false,
            'subscription_id' => $result['subscription']?->id,
            'order_id' => $result['order']?->id,
            'plan_id' => $plan->id,
        ];
    }

    /**
     * Process subscription.create webhook event.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handleSubscriptionCreate(array $data): array
    {
        Log::info('Paystack subscription.create received', ['data' => $data]);

        // Subscription creation is typically handled in charge.success
        // This webhook is mainly for logging/auditing purposes
        return [
            'success' => true,
            'message' => 'Subscription creation logged',
        ];
    }

    /**
     * Process subscription.not_renew webhook event.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handleSubscriptionNotRenew(array $data): array
    {
        $email = $data['customer']['email'] ?? null;

        if (! $email) {
            Log::warning('Paystack subscription.not_renew missing customer email');

            return ['success' => false, 'message' => 'Missing customer email'];
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            Log::warning('Paystack subscription.not_renew user not found', ['email' => $email]);

            return ['success' => false, 'message' => 'User not found'];
        }

        // Find active subscription for this user
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', SubscriptionStatus::Active)
            ->latest()
            ->first();

        if ($subscription) {
            $subscription->update([
                'auto_renew' => false,
                'cancelled_at' => now(),
            ]);

            Log::info('Paystack subscription renewal cancelled', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Subscription renewal cancelled',
        ];
    }

    /**
     * Process charge.failed webhook event.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handleChargeFailed(array $data): array
    {
        $reference = $data['reference'] ?? '';
        $email = $data['customer']['email'] ?? null;

        Log::warning('Paystack charge.failed', [
            'reference' => $reference,
            'email' => $email,
            'message' => $data['gateway_response'] ?? 'Unknown error',
        ]);

        // Update payment transaction if it exists
        $transaction = PaymentTransaction::where('reference', $reference)->first();

        if ($transaction) {
            $transaction->markAsFailed([
                'gateway_response' => $data['gateway_response'] ?? null,
                'message' => $data['message'] ?? 'Payment failed',
            ]);
        }

        return [
            'success' => true,
            'message' => 'Charge failure logged',
        ];
    }

    /**
     * Process refund.processed webhook event.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handleRefundProcessed(array $data): array
    {
        $transactionReference = $data['transaction_reference'] ?? '';

        $order = Order::where('gateway_transaction_id', $transactionReference)
            ->where('payment_gateway', PaymentGateway::Paystack)
            ->first();

        if ($order) {
            $order->update(['status' => OrderStatus::Refunded]);

            Log::info('Paystack refund processed', [
                'order_id' => $order->id,
                'reference' => $transactionReference,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Refund processed',
        ];
    }
}
