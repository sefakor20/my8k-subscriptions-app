<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProvisioningAction;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Services\My8kApiClient;
use Carbon\Carbon;

class ExtendAccountJob extends BaseProvisioningJob
{
    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $subscriptionId,
        public string $serviceAccountId,
        public int $durationDays,
    ) {}

    /**
     * Get the provisioning action type
     */
    protected function getProvisioningAction(): ProvisioningAction
    {
        return ProvisioningAction::Extend;
    }

    /**
     * Get related models for logging
     */
    protected function getRelatedModels(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'order_id' => null,
            'service_account_id' => $this->serviceAccountId,
        ];
    }

    /**
     * Perform the provisioning operation
     */
    protected function performProvisioning(): array
    {
        $subscription = Subscription::findOrFail($this->subscriptionId);
        $serviceAccount = ServiceAccount::findOrFail($this->serviceAccountId);

        // Convert duration from days to months for My8K API
        $durationMonths = (int) ceil($this->durationDays / 30);

        // Call My8K API to extend account
        $apiClient = app(My8kApiClient::class);
        $result = $apiClient->renewM3uDevice(
            username: $serviceAccount->username,
            password: $serviceAccount->password,
            subMonths: $durationMonths,
        );

        // Add request details for logging
        $result['request'] = [
            'action' => 'renew',
            'type' => 'm3u',
            'username' => $serviceAccount->username,
            'sub' => $durationMonths,
        ];

        if ($result['success']) {
            $this->onProvisioningSuccess($serviceAccount, $subscription);
        }

        return $result;
    }

    /**
     * Handle successful provisioning
     */
    protected function onProvisioningSuccess(ServiceAccount $serviceAccount, Subscription $subscription): void
    {
        $newExpiresAt = Carbon::parse($serviceAccount->expires_at)->addDays($this->durationDays);

        // Update ServiceAccount
        $serviceAccount->update([
            'expires_at' => $newExpiresAt,
            'last_extended_at' => now(),
        ]);

        // Update Subscription if needed
        if ($subscription->expires_at < $newExpiresAt) {
            $subscription->update([
                'expires_at' => $newExpiresAt,
                'last_renewal_at' => now(),
                'next_renewal_at' => $newExpiresAt,
            ]);
        }
    }

    /**
     * Handle final failure after all retries
     */
    protected function onFinalFailure(array $result): void
    {
        // Log the failure - subscription remains active until natural expiration
        // Admin will be notified via dashboard and can manually retry
    }
}
