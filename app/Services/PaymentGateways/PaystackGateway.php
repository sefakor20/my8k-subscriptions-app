<?php

declare(strict_types=1);

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayContract;
use App\Enums\PaymentGateway;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\PaystackApiClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class PaystackGateway implements PaymentGatewayContract
{
    public function __construct(
        private PaystackApiClient $client,
    ) {}

    public function getIdentifier(): PaymentGateway
    {
        return PaymentGateway::Paystack;
    }

    public function getDisplayName(): string
    {
        return 'Paystack';
    }

    public function isAvailable(): bool
    {
        return ! empty(config('services.paystack.secret_key'))
            && ! empty(config('services.paystack.public_key'));
    }

    public function initiatePayment(User $user, Plan $plan, array $metadata = []): array
    {
        $reference = $this->generateReference();
        $callbackUrl = route('checkout.callback', ['gateway' => 'paystack']);

        // Convert amount to kobo (Paystack expects amount in smallest unit)
        $amountInKobo = (int) ($plan->price * 100);

        $response = $this->client->initializeTransaction([
            'email' => $user->email,
            'amount' => $amountInKobo,
            'currency' => $plan->currency ?? config('services.paystack.currency', 'GHS'),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => array_merge([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
            ], $metadata),
            'channels' => ['card', 'bank', 'ussd', 'mobile_money', 'bank_transfer'],
        ]);

        Log::info('Paystack payment initiated', [
            'reference' => $reference,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        return [
            'checkout_url' => $response['data']['authorization_url'],
            'reference' => $reference,
            'access_code' => $response['data']['access_code'],
        ];
    }

    public function verifyPayment(string $reference): array
    {
        try {
            $response = $this->client->verifyTransaction($reference);

            $success = $response['status'] === true
                && ($response['data']['status'] ?? '') === 'success';

            Log::info('Paystack payment verification', [
                'reference' => $reference,
                'success' => $success,
                'status' => $response['data']['status'] ?? 'unknown',
            ]);

            return [
                'success' => $success,
                'data' => $response['data'] ?? [],
            ];
        } catch (Exception $e) {
            Log::error('Paystack verification failed', [
                'reference' => $reference,
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
            $response = $this->client->verifyTransaction($reference);

            return $response['data']['status'] ?? 'unknown';
        } catch (Exception $e) {
            return 'error';
        }
    }

    public function parseWebhook(array $payload, array $headers): array
    {
        $event = $payload['event'] ?? 'unknown';
        $data = $payload['data'] ?? [];

        return [
            'event' => $event,
            'data' => $data,
            'valid' => true,
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.paystack.webhook_secret');

        if (empty($secret)) {
            Log::warning('Paystack webhook secret not configured');

            return app()->environment('local');
        }

        $computedSignature = hash_hmac('sha512', $payload, $secret);

        return hash_equals($computedSignature, $signature);
    }

    public function getSupportedCurrencies(): array
    {
        return ['GHS', 'GHS', 'ZAR', 'USD', 'KES'];
    }

    public function processRefund(Order $order, ?float $amount = null): array
    {
        try {
            $transactionId = $order->gateway_transaction_id;

            if (empty($transactionId)) {
                return [
                    'success' => false,
                    'error' => 'No transaction ID found for this order',
                ];
            }

            $refundData = [
                'transaction' => $transactionId,
            ];

            if ($amount !== null) {
                // Convert to kobo
                $refundData['amount'] = (int) ($amount * 100);
            }

            $response = $this->client->refund($refundData);

            return [
                'success' => $response['status'] === true,
                'refund_id' => $response['data']['id'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Paystack refund failed', [
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
     * Generate a unique payment reference.
     */
    private function generateReference(): string
    {
        return 'PS_' . Str::upper(Str::random(8)) . '_' . time();
    }

    /**
     * Convert amount from kobo to naira (or equivalent).
     */
    public function convertFromSmallestUnit(int $amount): float
    {
        return $amount / 100;
    }

    /**
     * Convert amount to kobo (or equivalent smallest unit).
     */
    public function convertToSmallestUnit(float $amount): int
    {
        return (int) ($amount * 100);
    }
}
