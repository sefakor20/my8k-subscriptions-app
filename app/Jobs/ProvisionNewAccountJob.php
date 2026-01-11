<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\ProvisioningAction;
use App\Enums\ServiceAccountStatus;
use App\Mail\AccountCredentialsReady;
use App\Mail\ProvisioningFailed;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;
use App\Services\My8kApiClient;
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
        $order = Order::findOrFail($this->orderId);
        $subscription = Subscription::findOrFail($this->subscriptionId);
        $plan = Plan::findOrFail($this->planId);

        // Convert duration from days to months for My8K API
        $durationMonths = (int) ceil($plan->duration_days / 30);

        // Call My8K API to create account
        $apiClient = app(My8kApiClient::class);
        $result = $apiClient->createM3uDevice(
            packId: $plan->my8k_plan_code,
            subMonths: $durationMonths,
            notes: "Order #{$order->woocommerce_order_id}",
            country: 'ALL',
        );

        // Add request details for logging
        $result['request'] = [
            'action' => 'new',
            'type' => 'm3u',
            'pack' => $plan->my8k_plan_code,
            'sub' => $durationMonths,
            'notes' => "Order #{$order->woocommerce_order_id}",
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
        // Extract credentials from My8K response
        $my8kAccountId = $responseData['user_id'] ?? null;
        $username = $responseData['username'] ?? null;
        $password = $responseData['password'] ?? null;
        $m3uUrl = $responseData['m3u_url'] ?? null;

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

        // Link ServiceAccount to Subscription
        $subscription->update([
            'service_account_id' => $serviceAccount->id,
        ]);

        // Send credentials email to customer
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
