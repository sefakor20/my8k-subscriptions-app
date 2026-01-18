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

class SubscriptionOrderService
{
    /**
     * Create or retrieve subscription and order using idempotency.
     *
     * @param  array<string, mixed>  $paymentData
     * @return array{success: bool, duplicate: bool, subscription?: Subscription, order?: Order, user?: User, user_was_created?: bool, message: string}
     */
    public function createSubscriptionAndOrder(
        PaymentGateway $gateway,
        string $reference,
        string $email,
        Plan $plan,
        array $paymentData,
    ): array {
        $idempotencyKey = $this->generateIdempotencyKey($gateway, $reference);

        // Quick check before transaction
        $existingOrder = $this->checkIdempotency($idempotencyKey);

        if ($existingOrder) {
            Log::info('Subscription/Order already exists (idempotency check)', [
                'gateway' => $gateway->value,
                'reference' => $reference,
                'order_id' => $existingOrder->id,
            ]);

            return [
                'success' => true,
                'duplicate' => true,
                'order' => $existingOrder,
                'subscription' => $existingOrder->subscription,
                'user' => $existingOrder->user,
                'message' => 'Already processed',
            ];
        }

        return DB::transaction(function () use ($gateway, $reference, $email, $plan, $paymentData, $idempotencyKey): array {
            // Double-check inside transaction (race condition protection)
            $existingOrder = $this->checkIdempotency($idempotencyKey);

            if ($existingOrder) {
                return [
                    'success' => true,
                    'duplicate' => true,
                    'order' => $existingOrder,
                    'subscription' => $existingOrder->subscription,
                    'user' => $existingOrder->user,
                    'message' => 'Already processed',
                ];
            }

            // Find or create user
            $userData = $this->findOrCreateUser($email, $paymentData['customer'] ?? []);
            $user = $userData['user'];

            // Create subscription
            $subscription = $this->createSubscription($user, $plan, $gateway);

            // Create order
            $order = $this->createOrder(
                $user,
                $subscription,
                $plan,
                $gateway,
                $reference,
                $idempotencyKey,
                $paymentData,
            );

            // Update payment transaction
            $this->updatePaymentTransaction($reference, $order, $gateway, $paymentData);

            Log::info('Subscription and order created', [
                'gateway' => $gateway->value,
                'reference' => $reference,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'order_id' => $order->id,
            ]);

            return [
                'success' => true,
                'duplicate' => false,
                'order' => $order,
                'subscription' => $subscription,
                'user' => $user,
                'user_was_created' => $userData['was_created'],
                'message' => 'Created successfully',
            ];
        });
    }

    /**
     * Generate idempotency key for gateway:reference combination.
     */
    public function generateIdempotencyKey(PaymentGateway $gateway, string $reference): string
    {
        return hash('sha256', "{$gateway->value}:{$reference}");
    }

    /**
     * Check if order already exists by idempotency key.
     */
    public function checkIdempotency(string $idempotencyKey): ?Order
    {
        return Order::where('idempotency_key', $idempotencyKey)->first();
    }

    /**
     * Find or create user from payment data.
     *
     * @param  array<string, mixed>  $customerData
     * @return array{user: User, was_created: bool}
     */
    public function findOrCreateUser(string $email, array $customerData = []): array
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            return ['user' => $user, 'was_created' => false];
        }

        // Try to extract name from customer data
        $name = $this->extractName($customerData);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
        ]);

        return ['user' => $user, 'was_created' => true];
    }

    /**
     * Create subscription record.
     */
    public function createSubscription(User $user, Plan $plan, PaymentGateway $gateway): Subscription
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
            'metadata' => ['source' => $gateway->value],
        ]);
    }

    /**
     * Create order record.
     *
     * @param  array<string, mixed>  $paymentData
     */
    public function createOrder(
        User $user,
        Subscription $subscription,
        Plan $plan,
        PaymentGateway $gateway,
        string $reference,
        string $idempotencyKey,
        array $paymentData,
    ): Order {
        $amount = $this->extractAmount($gateway, $paymentData, $plan);
        $currency = $this->extractCurrency($gateway, $paymentData, $plan);
        $transactionId = $this->extractTransactionId($gateway, $reference, $paymentData);

        return Order::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'woocommerce_order_id' => null,
            'status' => OrderStatus::PendingProvisioning,
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $gateway->value,
            'payment_gateway' => $gateway,
            'gateway_transaction_id' => $transactionId,
            'gateway_session_id' => $gateway === PaymentGateway::Stripe ? $reference : null,
            'gateway_metadata' => $this->extractMetadata($gateway, $paymentData),
            'paid_at' => now(),
            'provisioned_at' => null,
            'idempotency_key' => $idempotencyKey,
            'webhook_payload' => $paymentData,
        ]);
    }

    /**
     * Update or create payment transaction record.
     *
     * @param  array<string, mixed>  $paymentData
     */
    public function updatePaymentTransaction(
        string $reference,
        Order $order,
        PaymentGateway $gateway,
        array $paymentData,
    ): void {
        $transactionId = $this->extractTransactionId($gateway, $reference, $paymentData);
        $transaction = PaymentTransaction::where('reference', $reference)->first();

        if ($transaction) {
            $transaction->update([
                'order_id' => $order->id,
                'status' => PaymentTransactionStatus::Success,
                'gateway_transaction_id' => $transactionId,
                'gateway_response' => $paymentData,
                'webhook_payload' => $paymentData,
                'verified_at' => now(),
            ]);
        } else {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'payment_gateway' => $gateway,
                'reference' => $reference,
                'gateway_transaction_id' => $transactionId,
                'status' => PaymentTransactionStatus::Success,
                'amount' => $order->amount,
                'currency' => $order->currency,
                'gateway_response' => $paymentData,
                'webhook_payload' => $paymentData,
                'verified_at' => now(),
            ]);
        }
    }

    /**
     * Extract customer name from payment data.
     *
     * @param  array<string, mixed>  $customerData
     */
    private function extractName(array $customerData): string
    {
        // Paystack format
        if (isset($customerData['first_name']) || isset($customerData['last_name'])) {
            $firstName = $customerData['first_name'] ?? '';
            $lastName = $customerData['last_name'] ?? '';

            return mb_trim("{$firstName} {$lastName}") ?: 'Customer';
        }

        // Stripe format
        if (isset($customerData['name'])) {
            return $customerData['name'];
        }

        return 'Customer';
    }

    /**
     * Extract amount from payment data.
     *
     * @param  array<string, mixed>  $paymentData
     */
    private function extractAmount(PaymentGateway $gateway, array $paymentData, Plan $plan): float
    {
        return match ($gateway) {
            PaymentGateway::Paystack => ($paymentData['amount'] ?? 0) / 100, // Kobo to Naira
            PaymentGateway::Stripe => ($paymentData['amount_total'] ?? 0) / 100, // Cents to dollars
            default => (float) $plan->price,
        };
    }

    /**
     * Extract currency from payment data.
     *
     * @param  array<string, mixed>  $paymentData
     */
    private function extractCurrency(PaymentGateway $gateway, array $paymentData, Plan $plan): string
    {
        $currency = match ($gateway) {
            PaymentGateway::Paystack => $paymentData['currency'] ?? $plan->currency ?? 'GHS',
            PaymentGateway::Stripe => $paymentData['currency'] ?? $plan->currency ?? 'USD',
            default => $plan->currency ?? 'USD',
        };

        return mb_strtoupper($currency);
    }

    /**
     * Extract transaction ID from payment data.
     *
     * @param  array<string, mixed>  $paymentData
     */
    private function extractTransactionId(PaymentGateway $gateway, string $reference, array $paymentData): ?string
    {
        return match ($gateway) {
            PaymentGateway::Paystack => $reference,
            PaymentGateway::Stripe => $paymentData['payment_intent'] ?? null,
            default => $reference,
        };
    }

    /**
     * Extract gateway-specific metadata.
     *
     * @param  array<string, mixed>  $paymentData
     * @return array<string, mixed>
     */
    private function extractMetadata(PaymentGateway $gateway, array $paymentData): array
    {
        return match ($gateway) {
            PaymentGateway::Paystack => [
                'authorization' => $paymentData['authorization'] ?? null,
                'channel' => $paymentData['channel'] ?? null,
                'ip_address' => $paymentData['ip_address'] ?? null,
            ],
            PaymentGateway::Stripe => [
                'payment_intent' => $paymentData['payment_intent'] ?? null,
                'customer' => $paymentData['customer'] ?? null,
                'mode' => $paymentData['mode'] ?? 'payment',
            ],
            default => [],
        };
    }
}
