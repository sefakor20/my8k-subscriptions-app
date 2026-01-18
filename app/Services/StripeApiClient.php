<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeApiClient
{
    private ?StripeClient $client = null;

    private string $secretKey;

    public function __construct(?string $secretKey = null)
    {
        $this->secretKey = $secretKey ?? config('services.stripe.secret_key') ?? '';
    }

    /**
     * Get the Stripe client (lazy initialization).
     */
    private function client(): StripeClient
    {
        if ($this->client === null) {
            if (empty($this->secretKey)) {
                throw new RuntimeException('Stripe secret key is not configured');
            }
            Stripe::setApiKey($this->secretKey);
            $this->client = new StripeClient($this->secretKey);
        }

        return $this->client;
    }

    /**
     * Create a Checkout Session for one-time payment.
     *
     * @param  array<string, mixed>  $params
     */
    public function createCheckoutSession(array $params): Session
    {
        return $this->client()->checkout->sessions->create($params);
    }

    /**
     * Retrieve a Checkout Session.
     */
    public function retrieveCheckoutSession(string $sessionId): Session
    {
        return $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['line_items', 'payment_intent', 'customer'],
        ]);
    }

    /**
     * Retrieve a Payment Intent.
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * Create a refund.
     *
     * @param  array<string, mixed>  $params
     */
    public function createRefund(array $params): Refund
    {
        return $this->client()->refunds->create($params);
    }

    /**
     * Create or get a customer.
     *
     * @param  array<string, mixed>  $params
     */
    public function createCustomer(array $params): Customer
    {
        return $this->client()->customers->create($params);
    }

    /**
     * Retrieve a customer.
     */
    public function retrieveCustomer(string $customerId): Customer
    {
        return $this->client()->customers->retrieve($customerId);
    }

    /**
     * Search customers by email.
     *
     * @param  array<string, mixed>  $params
     * @return \Stripe\SearchResult<Customer>
     */
    public function searchCustomers(array $params): \Stripe\SearchResult
    {
        return $this->client()->customers->search($params);
    }

    /**
     * Construct webhook event from payload.
     *
     * @throws SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, string $signature, string $secret): Event
    {
        return Webhook::constructEvent($payload, $signature, $secret);
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature, ?string $secret = null): bool
    {
        $webhookSecret = $secret ?? config('services.stripe.webhook_secret');

        if (empty($webhookSecret)) {
            Log::warning('Stripe webhook secret not configured');

            return app()->environment('local');
        }

        try {
            Webhook::constructEvent($payload, $signature, $webhookSecret);

            return true;
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the underlying Stripe client.
     */
    public function getClient(): StripeClient
    {
        return $this->client();
    }
}
