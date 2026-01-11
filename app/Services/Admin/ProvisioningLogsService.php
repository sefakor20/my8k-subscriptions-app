<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\ProvisioningAction;
use App\Enums\ProvisioningStatus;
use App\Models\ProvisioningLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProvisioningLogsService
{
    /**
     * Get provisioning logs with filters and pagination
     */
    public function getLogsWithFilters(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = ProvisioningLog::query()
            ->with(['subscription.plan', 'subscription.user', 'order', 'serviceAccount'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by action
        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Search in error messages
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('error_message', 'like', "%{$search}%")
                    ->orWhere('error_code', 'like', "%{$search}%")
                    ->orWhere('subscription_id', 'like', "%{$search}%")
                    ->orWhere('order_id', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Get a single provisioning log by ID
     */
    public function getLog(string $logId): ?ProvisioningLog
    {
        return ProvisioningLog::with([
            'subscription.plan',
            'subscription.user',
            'order',
            'serviceAccount',
        ])->find($logId);
    }

    /**
     * Get distinct statuses for filter dropdown
     */
    public function getDistinctStatuses(): array
    {
        return array_map(
            fn(ProvisioningStatus $status) => [
                'value' => $status->value,
                'label' => ucfirst($status->value),
            ],
            ProvisioningStatus::cases(),
        );
    }

    /**
     * Get distinct actions for filter dropdown
     */
    public function getDistinctActions(): array
    {
        return array_map(
            fn(ProvisioningAction $action) => [
                'value' => $action->value,
                'label' => ucfirst($action->value),
            ],
            ProvisioningAction::cases(),
        );
    }

    /**
     * Get summary statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => ProvisioningLog::count(),
            'success' => ProvisioningLog::where('status', ProvisioningStatus::Success)->count(),
            'failed' => ProvisioningLog::where('status', ProvisioningStatus::Failed)->count(),
            'pending' => ProvisioningLog::where('status', ProvisioningStatus::Pending)->count(),
            'retrying' => ProvisioningLog::where('status', ProvisioningStatus::Retrying)->count(),
            'recent_24h' => ProvisioningLog::recent(24)->count(),
        ];
    }
}
