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
        $baseSuccessUrl = $metadata['callback_url'] ?? route('checkout.callback', ['gateway' => 'stripe']);
        $successUrl = $baseSuccessUrl . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('checkout.cancel');
        unset($metadata['callback_url']);

        // Get gateway-specific currency and price (or use override for discounted amounts)
        $currency = mb_strtolower($plan->getCurrencyFor('stripe'));
        $price = $metadata['override_amount'] ?? $plan->getAmountFor('stripe', mb_strtoupper($currency));
        unset($metadata['override_amount'], $metadata['coupon_data']);

        // Convert amount to cents (Stripe expects amount in smallest unit)
        $amountInCents = (int) ($price * 100);

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
        return ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'GHS', 'GHS', 'ZAR', 'KES'];
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

    /**
     * Charge a recurring payment using stored customer ID.
     *
     * @param  array<string, mixed>  $authorizationData  Must contain 'customer' (Stripe customer ID)
     * @param  array<string, mixed>  $metadata
     * @return array{success: bool, reference?: string, transaction_id?: string, data?: array<string, mixed>, error?: string}
     */
    public function chargeRecurring(array $authorizationData, float $amount, string $currency, array $metadata = []): array
    {
        try {
            $customerId = $authorizationData['customer'] ?? null;
            $paymentMethodId = $authorizationData['payment_method'] ?? null;

            if (empty($customerId)) {
                return [
                    'success' => false,
                    'error' => 'Missing customer ID for recurring charge',
                ];
            }

            $amountInCents = $this->convertToSmallestUnit($amount);

            $paymentIntentParams = [
                'customer' => $customerId,
                'amount' => $amountInCents,
                'currency' => mb_strtolower($currency),
                'confirm' => true,
                'off_session' => true,
                'metadata' => $metadata,
            ];

            // Use specific payment method if provided
            if ($paymentMethodId) {
                $paymentIntentParams['payment_method'] = $paymentMethodId;
            }

            $paymentIntent = $this->client->createPaymentIntent($paymentIntentParams);

            $success = $paymentIntent->status === 'succeeded';

            Log::info('Stripe recurring charge', [
                'payment_intent_id' => $paymentIntent->id,
                'success' => $success,
                'status' => $paymentIntent->status,
            ]);

            return [
                'success' => $success,
                'reference' => $paymentIntent->id,
                'transaction_id' => $paymentIntent->id,
                'data' => [
                    'payment_intent' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                    'customer' => $customerId,
                ],
            ];
        } catch (Exception $e) {
            Log::error('Stripe recurring charge failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency,
                'customer' => $authorizationData['customer'] ?? null,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
