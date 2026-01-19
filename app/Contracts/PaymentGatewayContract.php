<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\PaymentGateway;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;

interface PaymentGatewayContract
{
    /**
     * Get the gateway identifier enum.
     */
    public function getIdentifier(): PaymentGateway;

    /**
     * Get the display name for the gateway.
     */
    public function getDisplayName(): string;

    /**
     * Check if the gateway is properly configured and available.
     */
    public function isAvailable(): bool;

    /**
     * Initiate a payment/checkout session.
     *
     * @param  array<string, mixed>  $metadata
     * @return array{checkout_url: string, reference: string, session_id?: string}
     */
    public function initiatePayment(User $user, Plan $plan, array $metadata = []): array;

    /**
     * Verify a payment by reference/transaction ID.
     *
     * @return array{success: bool, data?: array<string, mixed>, error?: string}
     */
    public function verifyPayment(string $reference): array;

    /**
     * Get the status of a transaction.
     */
    public function getTransactionStatus(string $reference): string;

    /**
     * Handle incoming webhook payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     * @return array{event: string, data: array<string, mixed>, valid: bool}
     */
    public function parseWebhook(array $payload, array $headers): array;

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Get supported currencies.
     *
     * @return array<string>
     */
    public function getSupportedCurrencies(): array;

    /**
     * Process refund for an order.
     *
     * @return array{success: bool, refund_id?: string, error?: string}
     */
    public function processRefund(Order $order, ?float $amount = null): array;

    /**
     * Charge a recurring payment using stored authorization.
     *
     * @param  array<string, mixed>  $authorizationData  Gateway-specific authorization data (e.g., authorization_code for Paystack, customer ID for Stripe)
     * @param  array<string, mixed>  $metadata
     * @return array{success: bool, reference?: string, transaction_id?: string, data?: array<string, mixed>, error?: string}
     */
    public function chargeRecurring(array $authorizationData, float $amount, string $currency, array $metadata = []): array;
}
