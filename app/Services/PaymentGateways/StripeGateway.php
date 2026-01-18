<?php

declare(strict_types=1);

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayContract;
use App\Enums\PaymentGateway;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\StripeApiClient;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Exception;

class StripeGateway implements PaymentGatewayContract
{
    public function __construct(
        private StripeApiClient $client,
    ) {}

    public function getIdentifier(): PaymentGateway
    {
        return PaymentGateway::Stripe;
    }

    public function getDisplayName(): string
    {
        return 'Stripe';
    }

    public function isAvailable(): bool
    {
        return ! empty(config('services.stripe.secret_key'))
            && ! empty(config('services.stripe.public_key'));
    }

    public function initiatePayment(User $user, Plan $plan, array $metadata = []): array
    {
        $successUrl = route('checkout.callback', ['gateway' => 'stripe']) . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('checkout.cancel');

        // Convert amount to cents (Stripe expects amount in smallest unit)
        $amountInCents = (int) ($plan->price * 100);
        $currency = mb_strtolower($plan->currency ?? config('services.stripe.currency', 'USD'));

        $sessionParams = [
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $plan->name,
                            'description' => $plan->description ?? "Subscription to {$plan->name}",
                        ],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $user->email,
            'metadata' => array_merge([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
            ], $metadata),
            'payment_intent_data' => [
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                ],
            ],
        ];

        $session = $this->client->createCheckoutSession($sessionParams);

        Log::info('Stripe checkout session created', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        return [
            'checkout_url' => $session->url,
            'reference' => $session->id,
            'session_id' => $session->id,
        ];
    }

    public function verifyPayment(string $reference): array
    {
        try {
            $session = $this->client->retrieveCheckoutSession($reference);

            $success = $session->payment_status === 'paid';

            Log::info('Stripe payment verification', [
                'session_id' => $reference,
                'success' => $success,
                'payment_status' => $session->payment_status,
            ]);

            return [
                'success' => $success,
                'data' => [
                    'session_id' => $session->id,
                    'payment_status' => $session->payment_status,
                    'payment_intent' => $session->payment_intent,
                    'customer_email' => $session->customer_email ?? $session->customer_details?->email,
                    'amount_total' => $session->amount_total,
                    'currency' => $session->currency,
                    'metadata' => $session->metadata?->toArray() ?? [],
                ],
            ];
        } catch (Exception $e) {
            Log::error('Stripe verification failed', [
                'session_id' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getTransactionStatus(string $reference): string
    {
        try {
            $session = $this->client->retrieveCheckoutSession($reference);

            return $session->payment_status ?? 'unknown';
        } catch (Exception $e) {
            return 'error';
        }
    }

    public function parseWebhook(array $payload, array $headers): array
    {
        $event = $payload['type'] ?? 'unknown';
        $data = $payload['data']['object'] ?? [];

        return [
            'event' => $event,
            'data' => $data,
            'valid' => true,
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        return $this->client->verifyWebhookSignature($payload, $signature);
    }

    public function getSupportedCurrencies(): array
    {
        // Stripe supports 135+ currencies
        return ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'NGN', 'GHS', 'ZAR', 'KES'];
    }

    public function processRefund(Order $order, ?float $amount = null): array
    {
        try {
            $paymentIntentId = $order->gateway_metadata['payment_intent'] ?? null;

            if (empty($paymentIntentId)) {
                return [
                    'success' => false,
                    'error' => 'No payment intent found for this order',
                ];
            }

            $refundParams = [
                'payment_intent' => $paymentIntentId,
            ];

            if ($amount !== null) {
                // Convert to cents
                $refundParams['amount'] = (int) ($amount * 100);
            }

            $refund = $this->client->createRefund($refundParams);

            return [
                'success' => $refund->status === 'succeeded',
                'refund_id' => $refund->id,
            ];
        } catch (Exception $e) {
            Log::error('Stripe refund failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convert amount from cents to main currency unit.
     */
    public function convertFromSmallestUnit(int $amount): float
    {
        return $amount / 100;
    }

    /**
     * Convert amount to cents (smallest unit).
     */
    public function convertToSmallestUnit(float $amount): int
    {
        return (int) ($amount * 100);
    }

    /**
     * Get Checkout Session details.
     */
    public function getCheckoutSession(string $sessionId): Session
    {
        return $this->client->retrieveCheckoutSession($sessionId);
    }
}
