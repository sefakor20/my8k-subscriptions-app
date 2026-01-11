<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Admin\OrderManagementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersList extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    public int $perPage = 50;

    public ?string $selectedOrderId = null;

    /**
     * Get filtered and paginated orders
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        $service = app(OrderManagementService::class);

        return $service->getOrdersWithFilters([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], $this->perPage);
    }

    /**
     * Get all available order statuses
     */
    #[Computed]
    public function statuses(): array
    {
        return OrderStatus::cases();
    }

    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    /**
     * Retry provisioning for an order
     */
    public function retryProvisioning(string $orderId): void
    {
        $service = app(OrderManagementService::class);
        $service->retryProvisioning($orderId);

        $this->dispatch('order-retry-initiated', orderId: $orderId);
        session()->flash('success', 'Provisioning retry initiated successfully.');
    }

    /**
     * Show order detail modal
     */
    public function showDetail(string $orderId): void
    {
        $this->selectedOrderId = $orderId;
        $this->dispatch('open-order-modal', orderId: $orderId);
    }

    /**
     * Update search query and reset pagination
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Update status filter and reset pagination
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Update date from filter and reset pagination
     */
    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    /**
     * Update date to filter and reset pagination
     */
    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.orders-list')
            ->layout('components.layouts.app');
    }
}
