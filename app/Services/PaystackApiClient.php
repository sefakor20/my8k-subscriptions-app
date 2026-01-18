<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaystackApiClient
{
    private string $secretKey;

    private string $baseUrl;

    public function __construct(?string $secretKey = null, ?string $baseUrl = null)
    {
        $this->secretKey = $secretKey ?? config('services.paystack.secret_key') ?? '';
        $this->baseUrl = $baseUrl ?? config('services.paystack.base_url') ?? 'https://api.paystack.co';
    }

    /**
     * Initialize a transaction.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function initializeTransaction(array $data): array
    {
        $response = $this->client()->post('/transaction/initialize', $data);

        return $this->handleResponse($response, 'initialize_transaction');
    }

    /**
     * Verify a transaction by reference.
     *
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array
    {
        $response = $this->client()->get("/transaction/verify/{$reference}");

        return $this->handleResponse($response, 'verify_transaction');
    }

    /**
     * Charge an authorization (for recurring payments).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function chargeAuthorization(array $data): array
    {
        $response = $this->client()->post('/transaction/charge_authorization', $data);

        return $this->handleResponse($response, 'charge_authorization');
    }

    /**
     * Create a subscription plan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createPlan(array $data): array
    {
        $response = $this->client()->post('/plan', $data);

        return $this->handleResponse($response, 'create_plan');
    }

    /**
     * Get a plan by code.
     *
     * @return array<string, mixed>
     */
    public function getPlan(string $planCode): array
    {
        $response = $this->client()->get("/plan/{$planCode}");

        return $this->handleResponse($response, 'get_plan');
    }

    /**
     * Create a subscription.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createSubscription(array $data): array
    {
        $response = $this->client()->post('/subscription', $data);

        return $this->handleResponse($response, 'create_subscription');
    }

    /**
     * Get a subscription by code.
     *
     * @return array<string, mixed>
     */
    public function getSubscription(string $subscriptionCode): array
    {
        $response = $this->client()->get("/subscription/{$subscriptionCode}");

        return $this->handleResponse($response, 'get_subscription');
    }

    /**
     * Disable a subscription.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function disableSubscription(array $data): array
    {
        $response = $this->client()->post('/subscription/disable', $data);

        return $this->handleResponse($response, 'disable_subscription');
    }

    /**
     * Create a refund.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function refund(array $data): array
    {
        $response = $this->client()->post('/refund', $data);

        return $this->handleResponse($response, 'refund');
    }

    /**
     * Get a customer by email.
     *
     * @return array<string, mixed>
     */
    public function getCustomer(string $email): array
    {
        $response = $this->client()->get("/customer/{$email}");

        return $this->handleResponse($response, 'get_customer');
    }

    /**
     * Create a customer.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createCustomer(array $data): array
    {
        $response = $this->client()->post('/customer', $data);

        return $this->handleResponse($response, 'create_customer');
    }

    /**
     * Get configured HTTP client.
     */
    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30);
    }

    /**
     * Handle API response.
     *
     * @return array<string, mixed>
     */
    private function handleResponse(Response $response, string $operation): array
    {
        $data = $response->json();

        if ($response->failed()) {
            Log::error("Paystack API error: {$operation}", [
                'status' => $response->status(),
                'response' => $data,
            ]);

            throw new RuntimeException(
                $data['message'] ?? "Paystack API {$operation} failed",
                $response->status(),
            );
        }

        if (! ($data['status'] ?? false)) {
            Log::warning("Paystack API returned unsuccessful status: {$operation}", [
                'response' => $data,
            ]);
        }

        return $data;
    }
}
