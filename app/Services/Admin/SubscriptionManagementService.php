<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\SubscriptionStatus;
use App\Jobs\ProvisionNewAccountJob;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionManagementService
{
    /**
     * Get subscriptions with filters and pagination
     */
    public function getSubscriptionsWithFilters(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = Subscription::query()
            ->with(['user', 'plan', 'serviceAccount'])
            ->orderBy('created_at', 'desc');

        // Apply search filter
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function (Builder $q) use ($search): void {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply plan filter
        if (! empty($filters['plan_id'])) {
            $query->where('plan_id', $filters['plan_id']);
        }

        // Apply date range filters
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Extend subscription by specified number of days
     */
    public function extendSubscription(string $subscriptionId, int $days): Subscription
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        $subscription->update([
            'expires_at' => $subscription->expires_at->addDays($days),
        ]);

        return $subscription->fresh();
    }

    /**
     * Suspend a subscription
     */
    public function suspendSubscription(string $subscriptionId, ?string $reason = null): Subscription
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        $subscription->update([
            'status' => SubscriptionStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        return $subscription->fresh();
    }

    /**
     * Reactivate a suspended subscription
     */
    public function reactivateSubscription(string $subscriptionId): Subscription
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return $subscription->fresh();
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): Subscription
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $subscription->fresh();
    }

    /**
     * Retry provisioning for a subscription
     */
    public function retryProvisioning(string $subscriptionId): void
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        // Get the most recent order for this subscription to retry provisioning
        $order = $subscription->orders()->latest()->first();

        if ($order) {
            ProvisionNewAccountJob::dispatch(
                $order->id,
                $subscription->id,
                $subscription->plan_id,
            );
        }
    }
}
