<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\ProvisioningAction;
use App\Enums\ProvisioningStatus;
use App\Enums\ServiceAccountStatus;
use App\Enums\SubscriptionStatus;
use App\Mail\AccountCredentialsReady;
use App\Mail\ProvisioningFailed;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ProvisioningLog;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;
use App\Services\My8kApiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProvisionNewAccountJob extends BaseProvisioningJob
{
    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $orderId,
        public string $subscriptionId,
        public string $planId,
    ) {}

    /**
     * Get the provisioning action type
     */
    protected function getProvisioningAction(): ProvisioningAction
    {
        return ProvisioningAction::Create;
    }

    /**
     * Get related models for logging
     */
    protected function getRelatedModels(): array
    {
        return [
            'order_id' => $this->orderId,
            'subscription_id' => $this->subscriptionId,
            'service_account_id' => null,
        ];
    }

    /**
     * Perform the provisioning operation
     */
    protected function performProvisioning(): array
    {
        // IDEMPOTENCY CHECK 1: Already provisioned?
        $subscription = Subscription::findOrFail($this->subscriptionId);

        if ($subscription->service_account_id) {
            $serviceAccount = ServiceAccount::find($subscription->service_account_id);

            if ($serviceAccount) {
                Log::info('Provisioning skipped - ServiceAccount already exists', [
                    'subscription_id' => $subscription->id,
                    'service_account_id' => $serviceAccount->id,
                    'my8k_account_id' => $serviceAccount->my8k_account_id,
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'user_id' => $serviceAccount->my8k_account_id,
                        'username' => $serviceAccount->username,
                        'password' => $serviceAccount->password,
                    ],
                    'message' => 'Already provisioned',
                    'idempotent' => true,
                ];
            }
        }

        // IDEMPOTENCY CHECK 2: Check provisioning logs for previous successful My8K API call
        $previousSuccess = $this->checkPreviousSuccessfulProvisioning($subscription->id);

        if ($previousSuccess) {
            Log::info('Provisioning using previous My8K account from logs', [
                'subscription_id' => $subscription->id,
                'my8k_account_id' => $previousSuccess['user_id'],
                'from_attempt' => $previousSuccess['attempt'],
            ]);

            // NEW: Check if ServiceAccount already exists for THIS SUBSCRIPTION
            $existingServiceAccount = ServiceAccount::where('subscription_id', $subscription->id)->first();

            if ($existingServiceAccount) {
                // ServiceAccount already exists for this subscription - just update order status and resend email
                Log::info('ServiceAccount already exists for this subscription, updating order', [
                    'service_account_id' => $existingServiceAccount->id,
                    'subscription_id' => $subscription->id,
                ]);

                // Load order for updating
                $order = Order::findOrFail($this->orderId);

                // Update order status
                $order->update([
                    'status' => OrderStatus::Provisioned,
                    'provisioned_at' => $existingServiceAccount->activated_at ?? now(),
                ]);

                // Ensure subscription is linked and active (should already be, but just in case)
                if ($subscription->service_account_id !== $existingServiceAccount->id) {
                    $subscription->update([
                        'service_account_id' => $existingServiceAccount->id,
                        'status' => SubscriptionStatus::Active,
                    ]);
                }

                // Send credentials email (safe to call multiple times, it's queued)
                Mail::to($subscription->user->email)
                    ->send(new AccountCredentialsReady($existingServiceAccount));

                return [
                    'success' => true,
                    'data' => $previousSuccess['data'],
                    'message' => 'Linked existing ServiceAccount',
                    'idempotent' => true,
                ];
            }

            // ServiceAccount doesn't exist yet for this subscription, create it normally
            $order = Order::findOrFail($this->orderId);

            $this->onProvisioningSuccess($previousSuccess['data'], $order, $subscription);

            return [
                'success' => true,
                'data' => $previousSuccess['data'],
                'message' => 'Provisioned using previous My8K account',
                'idempotent' => true,
            ];
        }

        // Existing code starts here
        $order = Order::findOrFail($this->orderId);
        $plan = Plan::findOrFail($this->planId);

        // Convert duration from days to months for My8K API
        $durationMonths = (int) ceil($plan->duration_days / 30);

        // Call My8K API to create account
        $apiClient = app(My8kApiClient::class);
        $result = $apiClient->createM3uDevice(
            packId: $plan->my8k_plan_code,
            subMonths: $durationMonths,
            notes: 'Order #' . ($order->woocommerce_order_id ?: $order->id),
            country: 'ALL',
        );

        // Add request details for logging
        $result['request'] = [
            'action' => 'new',
            'type' => 'm3u',
            'pack' => $plan->my8k_plan_code,
            'sub' => $durationMonths,
            'notes' => 'Order #' . ($order->woocommerce_order_id ?: $order->id),
            'country' => 'ALL',
        ];

        if ($result['success']) {
            $this->onProvisioningSuccess($result['data'], $order, $subscription);
        }

        return $result;
    }

    /**
     * Handle successful provisioning
     */
    protected function onProvisioningSuccess(array $responseData, Order $order, Subscription $subscription): void
    {
        // Use transaction for database operations to ensure atomicity
        $serviceAccount = DB::transaction(function () use ($responseData, $order, $subscription) {
            // Extract M3U URL (API might return 'url' or 'm3u_url')
            $m3uUrl = $responseData['m3u_url'] ?? $responseData['url'] ?? null;

            // Extract credentials from response or URL
            $username = $responseData['username'] ?? null;
            $password = $responseData['password'] ?? null;

            // If credentials not in response, extract from URL
            if (! $username || ! $password) {
                $extracted = $this->extractCredentialsFromUrl($m3uUrl);
                $username = $username ?? $extracted['username'];
                $password = $password ?? $extracted['password'];
            }

            $my8kAccountId = $responseData['user_id'] ?? null;

            // Parse server URL from M3U URL if available
            $serverUrl = $this->extractServerUrl($m3uUrl);

            // Create ServiceAccount record
            $serviceAccount = ServiceAccount::create([
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'my8k_account_id' => $my8kAccountId,
                'username' => $username,
                'password' => $password,
                'server_url' => $serverUrl ?? 'http://server1.my8k.com:8080',
                'max_connections' => $subscription->plan->max_devices ?? 1,
                'status' => ServiceAccountStatus::Active,
                'activated_at' => now(),
                'expires_at' => $subscription->expires_at,
                'last_extended_at' => now(),
                'my8k_metadata' => $responseData,
            ]);

            // Update Order status
            $order->update([
                'status' => OrderStatus::Provisioned,
                'provisioned_at' => now(),
            ]);

            // Link ServiceAccount to Subscription and activate it
            $subscription->update([
                'service_account_id' => $serviceAccount->id,
                'status' => SubscriptionStatus::Active,
            ]);

            return $serviceAccount;
        });

        // Send credentials email to customer (outside transaction - it's queued anyway)
        Mail::to($subscription->user->email)
            ->send(new AccountCredentialsReady($serviceAccount));
    }

    /**
     * Extract server URL from M3U URL
     */
    protected function extractServerUrl(?string $m3uUrl): ?string
    {
        if (! $m3uUrl) {
            return null;
        }

        $parsed = parse_url($m3uUrl);

        if (! $parsed || ! isset($parsed['scheme'], $parsed['host'])) {
            return null;
        }

        $port = $parsed['port'] ?? 80;

        return "{$parsed['scheme']}://{$parsed['host']}:{$port}";
    }

    /**
     * Extract credentials from M3U URL query parameters
     */
    protected function extractCredentialsFromUrl(?string $url): array
    {
        if (! $url) {
            return ['username' => null, 'password' => null];
        }

        $parsed = parse_url($url);

        if (! isset($parsed['query'])) {
            return ['username' => null, 'password' => null];
        }

        parse_str($parsed['query'], $params);

        return [
            'username' => $params['username'] ?? null,
            'password' => $params['password'] ?? null,
        ];
    }

    /**
     * Check if a previous provisioning attempt succeeded at My8K API level
     * Returns the My8K response data if found, null otherwise
     */
    protected function checkPreviousSuccessfulProvisioning(string $subscriptionId): ?array
    {
        $log = ProvisioningLog::query()
            ->where('subscription_id', $subscriptionId)
            ->where('action', ProvisioningAction::Create)
            ->whereNotNull('my8k_response')
            ->where('status', '!=', ProvisioningStatus::Failed)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $log || ! $log->my8k_response) {
            return null;
        }

        $response = $log->my8k_response;

        // Check if My8K API call was successful
        $statusOk = isset($response['status']) && (
            $response['status'] === true
            || (is_string($response['status']) && in_array(mb_strtoupper($response['status']), ['OK', 'TRUE'], true))
        );

        if (! $statusOk) {
            return null;
        }

        // Verify it has user_id and credentials
        if (! isset($response['user_id'])) {
            return null;
        }

        return [
            'data' => $response,
            'user_id' => $response['user_id'],
            'attempt' => $log->attempt_number,
        ];
    }

    /**
     * Handle final failure after all retries
     */
    protected function onFinalFailure(array $result): void
    {
        $order = Order::find($this->orderId);

        if ($order) {
            $order->update([
                'status' => OrderStatus::ProvisioningFailed,
            ]);

            // Send failure alert to all admin users
            $admins = User::where('is_admin', true)->get();

            foreach ($admins as $admin) {
                Mail::to($admin->email)
                    ->send(new ProvisioningFailed(
                        order: $order,
                        errorMessage: $result['error'] ?? 'Unknown error',
                        errorCode: $result['error_code'] ?? 'ERR_UNKNOWN',
                    ));
            }
        }
    }
}
