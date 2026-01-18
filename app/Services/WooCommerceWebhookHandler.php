<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class WooCommerceWebhookHandler
{
    /**
     * Find or create user from WooCommerce order data
     *
     * @return array{user: User, was_created: bool}
     */
    public function findOrCreateUser(array $orderData): array
    {
        $billing = $orderData['billing'] ?? [];
        $email = $billing['email'] ?? $orderData['customer_email'] ?? null;

        if (! $email) {
            throw new InvalidArgumentException('No email found in order data');
        }

        // Try to find existing user by email
        $user = User::where('email', $email)->first();

        if ($user) {
            return [
                'user' => $user,
                'was_created' => false,
            ];
        }

        // Create new user
        $firstName = $billing['first_name'] ?? '';
        $lastName = $billing['last_name'] ?? '';
        $name = mb_trim("{$firstName} {$lastName}") ?: 'Customer';

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)), // Random password, user will reset
            'email_verified_at' => now(), // Auto-verify since payment succeeded
        ]);

        return [
            'user' => $user,
            'was_created' => true,
        ];
    }

    /**
     * Find plan by WooCommerce product ID
     */
    public function findPlanByWooCommerceId(string $woocommerceProductId): ?Plan
    {
        return Plan::where('woocommerce_id', $woocommerceProductId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Extract plan from WooCommerce line items
     */
    public function extractPlanFromLineItems(array $lineItems): ?Plan
    {
        foreach ($lineItems as $item) {
            $productId = (string) ($item['product_id'] ?? '');

            if ($productId !== '' && $productId !== '0') {
                $plan = $this->findPlanByWooCommerceId($productId);

                if ($plan instanceof \App\Models\Plan) {
                    return $plan;
                }
            }
        }

        return null;
    }

    /**
     * Check if order has already been processed (idempotency check)
     */
    public function checkIdempotency(string $woocommerceOrderId, string $idempotencyKey): bool
    {
        return Order::where('woocommerce_order_id', $woocommerceOrderId)
            ->orWhere('idempotency_key', $idempotencyKey)
            ->exists();
    }

    /**
     * Create subscription record
     */
    public function createSubscription(User $user, Plan $plan, ?string $woocommerceSubscriptionId = null): Subscription
    {
        $startsAt = now();
        $expiresAt = now()->addDays($plan->duration_days);

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending,
            'woocommerce_subscription_id' => $woocommerceSubscriptionId,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'last_renewal_at' => $startsAt,
            'next_renewal_at' => $expiresAt,
            'auto_renew' => (bool) $woocommerceSubscriptionId,
            'metadata' => [],
        ]);
    }

    /**
     * Create order record
     */
    public function createOrder(
        User $user,
        Subscription $subscription,
        array $orderData,
    ): Order {
        $woocommerceOrderId = (string) $orderData['id'];
        $idempotencyKey = $this->generateIdempotencyKey($orderData);

        return Order::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'woocommerce_order_id' => $woocommerceOrderId,
            'status' => OrderStatus::PendingProvisioning,
            'amount' => $orderData['total'] ?? 0,
            'currency' => mb_strtoupper($orderData['currency'] ?? 'USD'),
            'payment_method' => $orderData['payment_method_title'] ?? null,
            'paid_at' => $this->parseWooCommerceDate($orderData['date_paid'] ?? null) ?? now(),
            'provisioned_at' => null,
            'idempotency_key' => $idempotencyKey,
            'webhook_payload' => $orderData,
        ]);
    }

    /**
     * Generate idempotency key from order data
     */
    public function generateIdempotencyKey(array $orderData): string
    {
        $orderId = $orderData['id'] ?? '';
        $total = $orderData['total'] ?? '';
        $datePaid = $orderData['date_paid'] ?? '';

        return hash('sha256', "{$orderId}:{$total}:{$datePaid}");
    }

    /**
     * Parse WooCommerce datetime string
     */
    public function parseWooCommerceDate(?string $date): ?\Carbon\Carbon
    {
        if (! $date) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Find subscription by WooCommerce subscription ID
     */
    public function findSubscriptionByWooCommerceId(string $woocommerceSubscriptionId): ?Subscription
    {
        return Subscription::where('woocommerce_subscription_id', $woocommerceSubscriptionId)
            ->first();
    }

    /**
     * Process order completion webhook
     */
    public function processOrderCompleted(array $orderData): array
    {
        return DB::transaction(function () use ($orderData): array {
            $woocommerceOrderId = (string) $orderData['id'];
            $idempotencyKey = $this->generateIdempotencyKey($orderData);

            // Check idempotency
            if ($this->checkIdempotency($woocommerceOrderId, $idempotencyKey)) {
                return [
                    'success' => true,
                    'message' => 'Order already processed',
                    'duplicate' => true,
                ];
            }

            // Find or create user
            $userData = $this->findOrCreateUser($orderData);
            $user = $userData['user'];
            $userWasCreated = $userData['was_created'];

            // Extract plan from line items
            $plan = $this->extractPlanFromLineItems($orderData['line_items'] ?? []);

            if (!$plan instanceof \App\Models\Plan) {
                throw new RuntimeException('No matching plan found in order line items');
            }

            // Check if this is tied to a WooCommerce subscription
            $woocommerceSubscriptionId = $this->extractSubscriptionId($orderData);

            // Create or find subscription
            if ($woocommerceSubscriptionId) {
                $subscription = $this->findSubscriptionByWooCommerceId($woocommerceSubscriptionId)
                    ?? $this->createSubscription($user, $plan, $woocommerceSubscriptionId);
            } else {
                $subscription = $this->createSubscription($user, $plan);
            }

            // Create order
            $order = $this->createOrder($user, $subscription, $orderData);

            return [
                'success' => true,
                'message' => 'Order processed successfully',
                'duplicate' => false,
                'user_id' => $user->id,
                'user_was_created' => $userWasCreated,
                'subscription_id' => $subscription->id,
                'order_id' => $order->id,
                'plan_id' => $plan->id,
            ];
        });
    }

    /**
     * Extract WooCommerce subscription ID from order data
     */
    private function extractSubscriptionId(array $orderData): ?string
    {
        // Check meta_data for subscription reference
        $metaData = $orderData['meta_data'] ?? [];

        foreach ($metaData as $meta) {
            if (($meta['key'] ?? '') === '_subscription_renewal') {
                return (string) ($meta['value'] ?? '');
            }
        }

        // Check line items for subscription product
        $lineItems = $orderData['line_items'] ?? [];

        foreach ($lineItems as $item) {
            $metaData = $item['meta_data'] ?? [];

            foreach ($metaData as $meta) {
                if (($meta['key'] ?? '') === '_subscription_id') {
                    return (string) ($meta['value'] ?? '');
                }
            }
        }

        return null;
    }
}
