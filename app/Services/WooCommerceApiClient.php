<?php

declare(strict_types=1);

namespace App\Services;

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;
use Illuminate\Support\Facades\Log;
use Exception;

class WooCommerceApiClient
{
    private Client $client;

    private string $storeUrl;

    public function __construct()
    {
        $this->storeUrl = config('services.woocommerce.store_url');
        $consumerKey = config('services.woocommerce.consumer_key');
        $consumerSecret = config('services.woocommerce.consumer_secret');
        $version = config('services.woocommerce.version', 'wc/v3');

        $this->client = new Client(
            $this->storeUrl,
            $consumerKey,
            $consumerSecret,
            [
                'version' => $version,
                'timeout' => 30,
            ],
        );
    }

    /**
     * Get a single order by ID
     */
    public function getOrder(string $orderId): array
    {
        try {
            $response = $this->client->get("orders/{$orderId}");

            Log::info('WooCommerce API: Fetched order', [
                'order_id' => $orderId,
            ]);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (HttpClientException $e) {
            return $this->handleException($e, 'getOrder', ['order_id' => $orderId]);
        } catch (Exception $e) {
            return $this->handleException($e, 'getOrder', ['order_id' => $orderId]);
        }
    }

    /**
     * Get multiple orders with filters
     *
     * @param  array  $params  Query parameters (status, after, before, per_page, page, etc.)
     */
    public function getOrders(array $params = []): array
    {
        try {
            $response = $this->client->get('orders', $params);

            Log::info('WooCommerce API: Fetched orders', [
                'count' => count($response),
                'params' => $params,
            ]);

            return [
                'success' => true,
                'data' => $response,
                'count' => count($response),
            ];
        } catch (HttpClientException $e) {
            return $this->handleException($e, 'getOrders', $params);
        } catch (Exception $e) {
            return $this->handleException($e, 'getOrders', $params);
        }
    }

    /**
     * Get a single subscription by ID
     */
    public function getSubscription(string $subscriptionId): array
    {
        try {
            $response = $this->client->get("subscriptions/{$subscriptionId}");

            Log::info('WooCommerce API: Fetched subscription', [
                'subscription_id' => $subscriptionId,
            ]);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (HttpClientException $e) {
            return $this->handleException($e, 'getSubscription', ['subscription_id' => $subscriptionId]);
        } catch (Exception $e) {
            return $this->handleException($e, 'getSubscription', ['subscription_id' => $subscriptionId]);
        }
    }

    /**
     * Get multiple subscriptions with filters
     *
     * @param  array  $params  Query parameters (status, customer, after, before, per_page, page, etc.)
     */
    public function getSubscriptions(array $params = []): array
    {
        try {
            $response = $this->client->get('subscriptions', $params);

            Log::info('WooCommerce API: Fetched subscriptions', [
                'count' => count($response),
                'params' => $params,
            ]);

            return [
                'success' => true,
                'data' => $response,
                'count' => count($response),
            ];
        } catch (HttpClientException $e) {
            return $this->handleException($e, 'getSubscriptions', $params);
        } catch (Exception $e) {
            return $this->handleException($e, 'getSubscriptions', $params);
        }
    }

    /**
     * Add a note to an order
     */
    public function addOrderNote(string $orderId, string $note, bool $customerNote = false): array
    {
        try {
            $response = $this->client->post("orders/{$orderId}/notes", [
                'note' => $note,
                'customer_note' => $customerNote,
            ]);

            Log::info('WooCommerce API: Added order note', [
                'order_id' => $orderId,
                'customer_note' => $customerNote,
            ]);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (HttpClientException $e) {
            return $this->handleException($e, 'addOrderNote', ['order_id' => $orderId]);
        } catch (Exception $e) {
            return $this->handleException($e, 'addOrderNote', ['order_id' => $orderId]);
        }
    }

    /**
     * Add a note to a subscription
     */
    public function addSubscriptionNote(string $subscriptionId, string $note, bool $customerNote = false): array
    {
        try {
            $response = $this->client->post("subscriptions/{$subscriptionId}/notes", [
                'note' => $note,
                'customer_note' => $customerNote,
            ]);

            Log::info('WooCommerce API: Added subscription note', [
                'subscription_id' => $subscriptionId,
                'customer_note' => $customerNote,
            ]);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (HttpClientException $e) {
            return $this->handleException($e, 'addSubscriptionNote', ['subscription_id' => $subscriptionId]);
        } catch (Exception $e) {
            return $this->handleException($e, 'addSubscriptionNote', ['subscription_id' => $subscriptionId]);
        }
    }

    /**
     * Update an order
     */
    public function updateOrder(string $orderId, array $data): array
    {
        try {
            $response = $this->client->put("orders/{$orderId}", $data);

            Log::info('WooCommerce API: Updated order', [
                'order_id' => $orderId,
            ]);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (HttpClientException $e) {
            return $this->handleException($e, 'updateOrder', ['order_id' => $orderId]);
        } catch (Exception $e) {
            return $this->handleException($e, 'updateOrder', ['order_id' => $orderId]);
        }
    }

    /**
     * Handle exceptions from WooCommerce API
     */
    private function handleException(Exception $e, string $method, array $context = []): array
    {
        $errorData = [
            'method' => $method,
            'message' => $e->getMessage(),
            'context' => $context,
        ];

        // Extract more details from HttpClientException
        if ($e instanceof HttpClientException) {
            $errorData['response'] = $e->getResponse();
            $errorData['request'] = $e->getRequest();
        }

        Log::error('WooCommerce API error', $errorData);

        return [
            'success' => false,
            'error' => $e->getMessage(),
            'error_code' => $this->classifyError($e),
        ];
    }

    /**
     * Classify error type
     */
    private function classifyError(Exception $e): string
    {
        if ($e instanceof HttpClientException) {
            $statusCode = $e->getResponse()['code'] ?? 0;

            return match (true) {
                $statusCode === 401 => 'ERR_UNAUTHORIZED',
                $statusCode === 403 => 'ERR_FORBIDDEN',
                $statusCode === 404 => 'ERR_NOT_FOUND',
                $statusCode === 429 => 'ERR_RATE_LIMIT',
                $statusCode >= 500 => 'ERR_SERVER_ERROR',
                $statusCode >= 400 => 'ERR_CLIENT_ERROR',
                default => 'ERR_HTTP_ERROR',
            };
        }

        if (str_contains($e->getMessage(), 'timeout')) {
            return 'ERR_TIMEOUT';
        }

        if (str_contains($e->getMessage(), 'connect') || str_contains($e->getMessage(), 'connection')) {
            return 'ERR_CONNECTION';
        }

        return 'ERR_UNKNOWN';
    }

    /**
     * Check if WooCommerce API is accessible
     */
    public function testConnection(): array
    {
        try {
            // Try to fetch system status to test connection
            $response = $this->client->get('system_status');

            return [
                'success' => true,
                'message' => 'Connection successful',
                'data' => [
                    'environment' => $response['environment'] ?? null,
                ],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $this->classifyError($e),
            ];
        }
    }
}
