<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Jobs\ProvisionNewAccountJob;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

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
    public function retryProvisioning(string $orderId): array
    {
        $order = Order::with(['subscription.serviceAccount'])->findOrFail($orderId);

        if ($order->status !== OrderStatus::ProvisioningFailed) {
            throw new InvalidArgumentException('Can only retry failed provisioning orders');
        }

        // Check if already provisioned
        if ($order->subscription && $order->subscription->service_account_id) {
            $serviceAccount = $order->subscription->serviceAccount;

            if ($serviceAccount) {
                Log::warning('Attempted to retry already provisioned order', [
                    'order_id' => $orderId,
                    'service_account_id' => $serviceAccount->id,
                ]);

                // Update order status to Provisioned since it already has ServiceAccount
                $order->update([
                    'status' => OrderStatus::Provisioned,
                    'provisioned_at' => $serviceAccount->activated_at ?? now(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Order already provisioned',
                    'order' => $order,
                ];
            }
        }

        if ($order->subscription) {
            ProvisionNewAccountJob::dispatch(
                $order->id,
                $order->subscription->id,
                $order->subscription->plan_id,
            );
        }

        return [
            'success' => true,
            'message' => 'Provisioning retry dispatched',
        ];
    }
}
