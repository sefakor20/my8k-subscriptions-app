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

class PaystackWebhookHandler
{
    /**
     * Process charge.success webhook event.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handleChargeSuccess(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $reference = $data['reference'] ?? '';
            $metadata = $data['metadata'] ?? [];

            // Check idempotency - see if we already processed this reference
            if ($this->checkIdempotency($reference)) {
                Log::info('Paystack charge.success already processed', ['reference' => $reference]);

                return [
                    'success' => true,
                    'message' => 'Transaction already processed',
                    'duplicate' => true,
                ];
            }

            // Get or create user
            $email = $data['customer']['email'] ?? null;

            if (! $email) {
                throw new InvalidArgumentException('No customer email in webhook data');
            }

            $userData = $this->findOrCreateUser($email, $data['customer'] ?? []);
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
            $this->updatePaymentTransaction($reference, $order, $data);

            Log::info('Paystack charge.success processed', [
                'reference' => $reference,
                'user_id' => $user->id,
                'order_id' => $order->id,
            ]);

            return [
                'success' => true,
                'message' => 'Charge processed successfully',
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

    /**
     * Find or create user from Paystack customer data.
     *
     * @param  array<string, mixed>  $customerData
     * @return array{user: User, was_created: bool}
     */
    private function findOrCreateUser(string $email, array $customerData): array
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            return ['user' => $user, 'was_created' => false];
        }

        $firstName = $customerData['first_name'] ?? '';
        $lastName = $customerData['last_name'] ?? '';
        $name = mb_trim("{$firstName} {$lastName}") ?: 'Customer';

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
        ]);

        return ['user' => $user, 'was_created' => true];
    }

    /**
     * Check if transaction has already been processed.
     */
    private function checkIdempotency(string $reference): bool
    {
        return Order::where('gateway_transaction_id', $reference)
            ->where('payment_gateway', PaymentGateway::Paystack)
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
            'metadata' => ['source' => 'paystack'],
        ]);
    }

    /**
     * Create order record.
     *
     * @param  array<string, mixed>  $webhookData
     */
    private function createOrder(User $user, Subscription $subscription, array $webhookData, Plan $plan): Order
    {
        // Paystack sends amount in kobo, convert to main unit
        $amount = ($webhookData['amount'] ?? 0) / 100;
        $reference = $webhookData['reference'] ?? '';

        return Order::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'woocommerce_order_id' => null,
            'status' => OrderStatus::PendingProvisioning,
            'amount' => $amount,
            'currency' => mb_strtoupper($webhookData['currency'] ?? $plan->currency ?? 'NGN'),
            'payment_method' => $webhookData['channel'] ?? 'paystack',
            'payment_gateway' => PaymentGateway::Paystack,
            'gateway_transaction_id' => $reference,
            'gateway_metadata' => [
                'authorization' => $webhookData['authorization'] ?? null,
                'channel' => $webhookData['channel'] ?? null,
                'ip_address' => $webhookData['ip_address'] ?? null,
            ],
            'paid_at' => now(),
            'provisioned_at' => null,
            'idempotency_key' => hash('sha256', "paystack:{$reference}"),
            'webhook_payload' => $webhookData,
        ]);
    }

    /**
     * Update or create payment transaction record.
     *
     * @param  array<string, mixed>  $webhookData
     */
    private function updatePaymentTransaction(string $reference, Order $order, array $webhookData): void
    {
        $transaction = PaymentTransaction::where('reference', $reference)->first();

        if ($transaction) {
            $transaction->update([
                'order_id' => $order->id,
                'status' => PaymentTransactionStatus::Success,
                'gateway_transaction_id' => $reference,
                'gateway_response' => $webhookData,
                'webhook_payload' => $webhookData,
                'verified_at' => now(),
            ]);
        } else {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'payment_gateway' => PaymentGateway::Paystack,
                'reference' => $reference,
                'gateway_transaction_id' => $reference,
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
