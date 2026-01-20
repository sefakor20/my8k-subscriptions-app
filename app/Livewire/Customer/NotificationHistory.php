<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Enums\NotificationCategory;
use App\Enums\NotificationLogStatus;
use App\Models\NotificationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationHistory extends Component
{
    use WithPagination;

    public string $filterCategory = '';

    public string $filterStatus = '';

    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        $query = NotificationLog::query()
            ->where('user_id', Auth::id())
            ->latest();

        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return $query->paginate(20);
    }

    #[Computed]
    public function categories(): array
    {
        return NotificationCategory::cases();
    }

    #[Computed]
    public function statuses(): array
    {
        return NotificationLogStatus::cases();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterCategory = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.customer.notification-history')
            ->layout('components.layouts.app');
    }
}
