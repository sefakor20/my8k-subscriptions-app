<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Services\Admin\OrderManagementService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class OrderDetailModal extends Component
{
    public bool $show = false;

    public ?string $orderId = null;

    /**
     * Get order with all relationships
     */
    #[Computed]
    public function order(): ?Order
    {
        if (! $this->orderId) {
            return null;
        }

        return Order::with([
            'user',
            'subscription.plan',
            'subscription.serviceAccount',
            'subscription.provisioningLogs' => fn($query) => $query->latest()->limit(5),
        ])->find($this->orderId);
    }

    /**
     * Open the modal
     */
    #[On('open-order-modal')]
    public function openModal(string $orderId): void
    {
        $this->orderId = $orderId;
        $this->show = true;
    }

    /**
     * Close the modal
     */
    public function closeModal(): void
    {
        $this->show = false;
        $this->orderId = null;
    }

    /**
     * Retry provisioning
     */
    public function retryProvisioning(): void
    {
        if (! $this->orderId) {
            return;
        }

        $service = app(OrderManagementService::class);
        $service->retryProvisioning($this->orderId);

        $this->dispatch('order-retry-initiated', orderId: $this->orderId);
        session()->flash('success', 'Provisioning retry initiated successfully.');
        $this->closeModal();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.order-detail-modal');
    }
}
