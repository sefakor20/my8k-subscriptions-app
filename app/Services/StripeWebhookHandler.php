<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Models\Order;
use App\Models\Plan;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class StripeWebhookHandler
{
    public function __construct(
        private SubscriptionOrderService $subscriptionService,
    ) {}

    /**
     * Process checkout.session.completed webhook event.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handleCheckoutSessionCompleted(array $data): array
    {
        $sessionId = $data['id'] ?? '';
        $metadata = $data['metadata'] ?? [];
        $paymentStatus = $data['payment_status'] ?? '';

        // Only process paid sessions
        if ($paymentStatus !== 'paid') {
            Log::info('Stripe checkout session not paid, skipping', [
                'session_id' => $sessionId,
                'payment_status' => $paymentStatus,
            ]);

            return [
                'success' => true,
                'message' => 'Session not paid, skipping',
            ];
        }

        // Get user email
        $email = $data['customer_email'] ?? $data['customer_details']['email'] ?? null;

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
            'amount_total' => $data['amount_total'] ?? 0,
            'currency' => $data['currency'] ?? 'usd',
            'payment_intent' => $data['payment_intent'] ?? null,
            'customer' => $data['customer'] ?? null,
            'customer_details' => $data['customer_details'] ?? [],
            'customer_email' => $email,
            'metadata' => $metadata,
            'mode' => $data['mode'] ?? 'payment',
        ];

        // Use shared service (idempotent)
        $result = $this->subscriptionService->createSubscriptionAndOrder(
            PaymentGateway::Stripe,
            $sessionId,
            $email,
            $plan,
            $paymentData,
        );

        if ($result['duplicate']) {
            Log::info('Stripe checkout.session.completed already processed', [
                'session_id' => $sessionId,
            ]);
        } else {
            Log::info('Stripe checkout.session.completed processed', [
                'session_id' => $sessionId,
                'user_id' => $result['user']->id,
                'order_id' => $result['order']->id,
            ]);
        }

        return [
            'success' => $result['success'],
            'message' => $result['duplicate'] ? 'Session already processed' : 'Checkout session processed successfully',
            'duplicate' => $result['duplicate'],
            'user_id' => $result['user']?->id,
            'user_was_created' => $result['user_was_created'] ?? false,
            'subscription_id' => $result['subscription']?->id,
            'order_id' => $result['order']?->id,
            'plan_id' => $plan->id,
        ];
    }

    /**
     * Process payment_intent.succeeded webhook event.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handlePaymentIntentSucceeded(array $data): array
    {
        // Payment intent success is typically handled by checkout.session.completed
        // This is mainly for logging purposes
        Log::info('Stripe payment_intent.succeeded received', [
            'payment_intent_id' => $data['id'] ?? null,
        ]);

        return [
            'success' => true,
            'message' => 'Payment intent success logged',
        ];
    }

    /**
     * Process invoice.paid webhook event (for subscription renewals).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handleInvoicePaid(array $data): array
    {
        $customerId = $data['customer'] ?? null;
        $subscriptionId = $data['subscription'] ?? null;

        Log::info('Stripe invoice.paid received', [
            'invoice_id' => $data['id'] ?? null,
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId,
        ]);

        // Find user by Stripe customer ID if we stored it
        // For now, log the event
        return [
            'success' => true,
            'message' => 'Invoice payment logged',
        ];
    }

    /**
     * Process invoice.payment_failed webhook event.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handleInvoicePaymentFailed(array $data): array
    {
        $customerEmail = $data['customer_email'] ?? null;

        Log::warning('Stripe invoice.payment_failed', [
            'invoice_id' => $data['id'] ?? null,
            'customer_email' => $customerEmail,
        ]);

        return [
            'success' => true,
            'message' => 'Payment failure logged',
        ];
    }

    /**
     * Process charge.refunded webhook event.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handleChargeRefunded(array $data): array
    {
        $paymentIntentId = $data['payment_intent'] ?? '';

        $order = Order::where('payment_gateway', PaymentGateway::Stripe)
            ->whereJsonContains('gateway_metadata->payment_intent', $paymentIntentId)
            ->first();

        if ($order) {
            $order->update(['status' => OrderStatus::Refunded]);

            Log::info('Stripe refund processed', [
                'order_id' => $order->id,
                'payment_intent' => $paymentIntentId,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Refund processed',
        ];
    }
}
