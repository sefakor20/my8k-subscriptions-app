<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class My8kApiClient
{
    private string $baseUrl;

    private string $apiKey;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.my8k.base_url', 'https://my8k.me/api/api.php');
        $this->apiKey = config('services.my8k.api_key');
        $this->timeout = (int) config('services.my8k.timeout', 30);
    }

    /**
     * Create a new M3U device (IPTV account)
     *
     * @param  string  $packId  Package/bouquet ID
     * @param  int  $subMonths  Subscription months (1,3,6,12)
     * @param  string|null  $notes  Optional notes
     * @param  string|null  $country  2-letter country code or "ALL"
     */
    public function createM3uDevice(string $packId, int $subMonths, ?string $notes = null, ?string $country = null): array
    {
        $params = [
            'action' => 'new',
            'type' => 'm3u',
            'pack' => $packId,
            'sub' => $subMonths,
        ];

        if ($notes) {
            $params['notes'] = $notes;
        }

        if ($country) {
            $params['country'] = $country;
        }

        return $this->makeRequest($params);
    }

    /**
     * Renew an existing M3U device
     *
     * @param  string  $username  Device username
     * @param  string  $password  Device password
     * @param  int  $subMonths  Subscription months (1,3,6,12)
     */
    public function renewM3uDevice(string $username, string $password, int $subMonths): array
    {
        $params = [
            'action' => 'renew',
            'type' => 'm3u',
            'username' => $username,
            'password' => $password,
            'sub' => $subMonths,
        ];

        return $this->makeRequest($params);
    }

    /**
     * Get device information for M3U account
     *
     * @param  string  $username  Device username
     * @param  string  $password  Device password
     */
    public function getM3uDeviceInfo(string $username, string $password): array
    {
        $params = [
            'action' => 'device_info',
            'username' => $username,
            'password' => $password,
        ];

        return $this->makeRequest($params);
    }

    /**
     * Enable or disable a device
     *
     * @param  string  $userId  User ID from My8K
     * @param  string  $status  'enable' or 'disable'
     */
    public function setDeviceStatus(string $userId, string $status): array
    {
        $params = [
            'action' => 'device_status',
            'id' => $userId,
            'status' => $status,
        ];

        return $this->makeRequest($params);
    }

    /**
     * Suspend a device (helper method using setDeviceStatus)
     */
    public function suspendDevice(string $userId): array
    {
        return $this->setDeviceStatus($userId, 'disable');
    }

    /**
     * Reactivate a device (helper method using setDeviceStatus)
     */
    public function reactivateDevice(string $userId): array
    {
        return $this->setDeviceStatus($userId, 'enable');
    }

    /**
     * List available bouquets/packages
     */
    public function listBouquets(): array
    {
        $params = [
            'action' => 'bouquet',
        ];

        return $this->makeRequest($params);
    }

    /**
     * Get reseller account information and credits
     */
    public function getResellerInfo(): array
    {
        $params = [
            'action' => 'reseller',
        ];

        return $this->makeRequest($params);
    }

    /**
     * Make an HTTP GET request to the My8K API
     * Note: My8K API uses GET with query parameters for all operations
     */
    private function makeRequest(array $params): array
    {
        $startTime = microtime(true);

        // Always add API key to params
        $params['api_key'] = $this->apiKey;

        try {
            $response = $this->buildHttpClient()
                ->get('', $params);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $result = $this->handleResponse($response, $durationMs);

            $this->logRequest('GET', $this->baseUrl, $params, $result, $durationMs);

            return $result;
        } catch (Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $error = [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $this->classifyError($e),
                'duration_ms' => $durationMs,
                'retryable' => $this->isRetryable($e),
            ];

            $this->logRequest('GET', $this->baseUrl, $params, $error, $durationMs, $e);

            return $error;
        }
    }

    /**
     * Build HTTP client with timeout and retry logic
     */
    private function buildHttpClient(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->retry(3, 1000, function (Exception $exception): bool {
                return $this->isRetryable($exception);
            });
    }

    /**
     * Handle the API response from My8K
     * My8K returns JSON with 'status' field indicating success/failure
     */
    private function handleResponse(Response $response, int $durationMs): array
    {
        $body = $response->json();

        // Check HTTP status first
        if (! $response->successful()) {
            $errorCode = $this->classifyHttpError($response->status());

            return [
                'success' => false,
                'error' => $body['error'] ?? $body['message'] ?? 'HTTP error occurred',
                'error_code' => $errorCode,
                'duration_ms' => $durationMs,
                'retryable' => $this->isHttpErrorRetryable($response->status()),
                'response_body' => $body,
            ];
        }

        // My8K API returns 'status' field to indicate success
        // Successful responses have 'status' => 'OK' or similar
        if (isset($body['status']) && mb_strtoupper($body['status']) === 'OK') {
            return [
                'success' => true,
                'data' => $body,
                'duration_ms' => $durationMs,
                'retryable' => false,
            ];
        }

        // If response contains error or status is not OK
        return [
            'success' => false,
            'error' => $body['error'] ?? $body['message'] ?? 'Unknown API error',
            'error_code' => 'ERR_API_RESPONSE',
            'duration_ms' => $durationMs,
            'retryable' => false,
            'response_body' => $body,
        ];
    }

    /**
     * Classify exception into error codes
     */
    private function classifyError(Exception $exception): string
    {
        $message = mb_strtolower($exception->getMessage());

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return 'ERR_TIMEOUT';
        }

        if (str_contains($message, 'connection') || str_contains($message, 'network')) {
            return 'ERR_NETWORK';
        }

        if (str_contains($message, 'dns') || str_contains($message, 'resolve')) {
            return 'ERR_DNS';
        }

        return 'ERR_UNKNOWN';
    }

    /**
     * Classify HTTP errors based on status code
     */
    private function classifyHttpError(int $statusCode): string
    {
        return match (true) {
            $statusCode === 400 => 'ERR_BAD_REQUEST',
            $statusCode === 401 => 'ERR_UNAUTHORIZED',
            $statusCode === 403 => 'ERR_FORBIDDEN',
            $statusCode === 404 => 'ERR_NOT_FOUND',
            $statusCode === 429 => 'ERR_RATE_LIMIT',
            $statusCode >= 500 => 'ERR_SERVER_ERROR',
            default => 'ERR_HTTP_' . $statusCode,
        };
    }

    /**
     * Determine if an error is retryable
     */
    private function isRetryable(Exception $exception): bool
    {
        $errorCode = $this->classifyError($exception);

        return in_array($errorCode, [
            'ERR_TIMEOUT',
            'ERR_NETWORK',
            'ERR_DNS',
        ], true);
    }

    /**
     * Determine if HTTP error is retryable
     */
    private function isHttpErrorRetryable(int $statusCode): bool
    {
        return in_array($statusCode, [408, 429, 500, 502, 503, 504], true);
    }

    /**
     * Log API request details
     */
    private function logRequest(string $method, string $endpoint, array $payload, array $result, int $durationMs, ?Exception $exception = null): void
    {
        $context = [
            'method' => $method,
            'endpoint' => $endpoint,
            'payload' => $payload,
            'duration_ms' => $durationMs,
            'success' => $result['success'] ?? false,
        ];

        if (! $result['success']) {
            $context['error'] = $result['error'] ?? 'Unknown error';
            $context['error_code'] = $result['error_code'] ?? 'ERR_UNKNOWN';
        }

        if ($exception instanceof Exception) {
            $context['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        if ($result['success']) {
            Log::info('My8K API request successful', $context);
        } else {
            Log::warning('My8K API request failed', $context);
        }
    }
}
