<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Enums\PaymentTransactionStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class StripeWebhookHandler
{
    /**
     * Process checkout.session.completed webhook event.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handleCheckoutSessionCompleted(array $data): array
    {
        return DB::transaction(function () use ($data): array {
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

            // Check idempotency
            if ($this->checkIdempotency($sessionId)) {
                Log::info('Stripe checkout.session.completed already processed', [
                    'session_id' => $sessionId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Session already processed',
                    'duplicate' => true,
                ];
            }

            // Get user email
            $email = $data['customer_email'] ?? $data['customer_details']['email'] ?? null;

            if (! $email) {
                throw new InvalidArgumentException('No customer email in webhook data');
            }

            // Find or create user
            $userData = $this->findOrCreateUser($email, $data['customer_details'] ?? []);
            $user = $userData['user'];

            // Find plan from metadata
            $planId = $metadata['plan_id'] ?? null;
            $plan = $planId ? Plan::find($planId) : null;

            if (! $plan) {
                throw new RuntimeException('No valid plan found in webhook metadata');
            }

            // Create subscription
            $subscription = $this->createSubscription($user, $plan);

            // Create order
            $order = $this->createOrder($user, $subscription, $data, $plan);

            // Update or create payment transaction
            $this->updatePaymentTransaction($sessionId, $order, $data);

            Log::info('Stripe checkout.session.completed processed', [
                'session_id' => $sessionId,
                'user_id' => $user->id,
                'order_id' => $order->id,
            ]);

            return [
                'success' => true,
                'message' => 'Checkout session processed successfully',
                'duplicate' => false,
                'user_id' => $user->id,
                'user_was_created' => $userData['was_created'],
                'subscription_id' => $subscription->id,
                'order_id' => $order->id,
                'plan_id' => $plan->id,
            ];
        });
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

    /**
     * Find or create user from Stripe customer data.
     *
     * @param  array<string, mixed>  $customerDetails
     * @return array{user: User, was_created: bool}
     */
    private function findOrCreateUser(string $email, array $customerDetails): array
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            return ['user' => $user, 'was_created' => false];
        }

        $name = $customerDetails['name'] ?? 'Customer';

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
        ]);

        return ['user' => $user, 'was_created' => true];
    }

    /**
     * Check if session has already been processed.
     */
    private function checkIdempotency(string $sessionId): bool
    {
        return Order::where('gateway_session_id', $sessionId)
            ->where('payment_gateway', PaymentGateway::Stripe)
            ->exists();
    }

    /**
     * Create subscription record.
     */
    private function createSubscription(User $user, Plan $plan): Subscription
    {
        $startsAt = now();
        $expiresAt = now()->addDays($plan->duration_days);

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'last_renewal_at' => $startsAt,
            'next_renewal_at' => $expiresAt,
            'auto_renew' => false,
            'metadata' => ['source' => 'stripe'],
        ]);
    }

    /**
     * Create order record.
     *
     * @param  array<string, mixed>  $webhookData
     */
    private function createOrder(User $user, Subscription $subscription, array $webhookData, Plan $plan): Order
    {
        // Stripe sends amount_total in cents
        $amount = ($webhookData['amount_total'] ?? 0) / 100;
        $sessionId = $webhookData['id'] ?? '';
        $paymentIntent = $webhookData['payment_intent'] ?? null;

        return Order::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'woocommerce_order_id' => null,
            'status' => OrderStatus::PendingProvisioning,
            'amount' => $amount,
            'currency' => mb_strtoupper($webhookData['currency'] ?? $plan->currency ?? 'USD'),
            'payment_method' => 'stripe',
            'payment_gateway' => PaymentGateway::Stripe,
            'gateway_transaction_id' => $paymentIntent,
            'gateway_session_id' => $sessionId,
            'gateway_metadata' => [
                'payment_intent' => $paymentIntent,
                'customer' => $webhookData['customer'] ?? null,
                'mode' => $webhookData['mode'] ?? 'payment',
            ],
            'paid_at' => now(),
            'provisioned_at' => null,
            'idempotency_key' => hash('sha256', "stripe:{$sessionId}"),
            'webhook_payload' => $webhookData,
        ]);
    }

    /**
     * Update or create payment transaction record.
     *
     * @param  array<string, mixed>  $webhookData
     */
    private function updatePaymentTransaction(string $sessionId, Order $order, array $webhookData): void
    {
        $transaction = PaymentTransaction::where('reference', $sessionId)->first();

        if ($transaction) {
            $transaction->update([
                'order_id' => $order->id,
                'status' => PaymentTransactionStatus::Success,
                'gateway_transaction_id' => $webhookData['payment_intent'] ?? null,
                'gateway_response' => $webhookData,
                'webhook_payload' => $webhookData,
                'verified_at' => now(),
            ]);
        } else {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'payment_gateway' => PaymentGateway::Stripe,
                'reference' => $sessionId,
                'gateway_transaction_id' => $webhookData['payment_intent'] ?? null,
                'status' => PaymentTransactionStatus::Success,
                'amount' => $order->amount,
                'currency' => $order->currency,
                'gateway_response' => $webhookData,
                'webhook_payload' => $webhookData,
                'verified_at' => now(),
            ]);
        }
    }
}
