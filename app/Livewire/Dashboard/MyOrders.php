<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class MyOrders extends Component
{
    use WithPagination;

    public int $perPage = 10;

    /**
     * Get user's orders
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return auth()->user()->orders()
            ->with(['subscription.plan'])
            ->latest()
            ->paginate($this->perPage);
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.my-orders')
            ->layout('components.layouts.app');
    }
}
