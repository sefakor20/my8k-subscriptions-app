<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CustomersList extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'verified')]
    public string $verifiedFilter = '';

    #[Url(as: 'role')]
    public string $roleFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    public int $perPage = 50;

    public ?string $selectedCustomerId = null;

    #[Computed]
    public function customers(): LengthAwarePaginator
    {
        $query = User::query()
            ->withCount(['subscriptions', 'orders', 'invoices'])
            ->withSum('orders', 'amount')
            ->orderBy('created_at', 'desc');

        if ($this->search !== '') {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($this->verifiedFilter !== '') {
            if ($this->verifiedFilter === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($this->verifiedFilter === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        if ($this->roleFilter !== '') {
            if ($this->roleFilter === 'admin') {
                $query->where('is_admin', true);
            } elseif ($this->roleFilter === 'customer') {
                $query->where('is_admin', false);
            }
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->paginate($this->perPage);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->verifiedFilter = '';
        $this->roleFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function showDetail(string $customerId): void
    {
        $this->selectedCustomerId = $customerId;
        $this->dispatch('open-customer-modal', customerId: $customerId);
    }

    public function toggleAdmin(string $customerId): void
    {
        $customer = User::find($customerId);

        if (! $customer) {
            session()->flash('error', 'Customer not found.');

            return;
        }

        if ($customer->id === Auth::id()) {
            session()->flash('error', 'You cannot change your own admin status.');

            return;
        }

        $customer->update(['is_admin' => ! $customer->is_admin]);

        $status = $customer->is_admin ? 'granted' : 'revoked';
        session()->flash('success', "Admin access {$status} for {$customer->name}.");

        unset($this->customers);
    }

    public function impersonate(string $customerId): void
    {
        $customer = User::find($customerId);

        if (! $customer) {
            session()->flash('error', 'Customer not found.');

            return;
        }

        if ($customer->id === Auth::id()) {
            session()->flash('error', 'You cannot impersonate yourself.');

            return;
        }

        session()->put('impersonator_id', Auth::id());
        Auth::login($customer);

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedVerifiedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin.customers-list')
            ->layout('components.layouts.app');
    }
}
