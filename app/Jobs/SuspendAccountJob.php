<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProvisioningAction;
use App\Enums\ServiceAccountStatus;
use App\Enums\SubscriptionStatus;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Services\My8kApiClient;

class SuspendAccountJob extends BaseProvisioningJob
{
    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $subscriptionId,
        public string $serviceAccountId,
        public string $reason = '',
    ) {}

    /**
     * Get the provisioning action type
     */
    protected function getProvisioningAction(): ProvisioningAction
    {
        return ProvisioningAction::Suspend;
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

        // Call My8K API to suspend device
        $apiClient = app(My8kApiClient::class);
        $result = $apiClient->suspendDevice(
            userId: $serviceAccount->my8k_account_id,
        );

        // Add request details for logging
        $result['request'] = [
            'action' => 'device_status',
            'id' => $serviceAccount->my8k_account_id,
            'status' => 'disable',
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
        // Update ServiceAccount status
        $serviceAccount->update([
            'status' => ServiceAccountStatus::Suspended,
        ]);

        // Update Subscription status if not already suspended/cancelled
        if ($subscription->status !== SubscriptionStatus::Cancelled) {
            $subscription->update([
                'status' => SubscriptionStatus::Suspended,
            ]);
        }
    }

    /**
     * Handle final failure after all retries
     */
    protected function onFinalFailure(array $result): void
    {
        // Log the failure - admin will need to manually suspend
        // or the account will remain active until expiration
    }
}
