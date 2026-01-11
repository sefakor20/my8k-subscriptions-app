<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Jobs\ProvisionNewAccountJob;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OrderManagementService
{
    /**
     * Get orders with filters and pagination
     */
    public function getOrdersWithFilters(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = Order::query()
            ->with(['user', 'subscription.plan'])
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

        // Apply date range filters
        if (! empty($filters['date_from'])) {
            $query->whereDate('paid_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('paid_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Retry provisioning for an order
     */
    public function retryProvisioning(string $orderId): void
    {
        $order = Order::findOrFail($orderId);

        if ($order->subscription) {
            ProvisionNewAccountJob::dispatch(
                $order->id,
                $order->subscription->id,
                $order->subscription->plan_id,
            );
        }
    }
}
