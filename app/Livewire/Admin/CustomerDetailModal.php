<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CustomerDetailModal extends Component
{
    public bool $show = false;

    public ?string $customerId = null;

    #[Computed]
    public function customer(): ?User
    {
        if (! $this->customerId) {
            return null;
        }

        return User::with([
            'subscriptions' => fn($query) => $query->with('plan')->latest()->limit(5),
            'orders' => fn($query) => $query->latest()->limit(5),
        ])
            ->withCount(['subscriptions', 'orders', 'invoices'])
            ->withSum('orders', 'amount')
            ->find($this->customerId);
    }

    #[On('open-customer-modal')]
    public function openModal(string $customerId): void
    {
        $this->customerId = $customerId;
        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->customerId = null;
    }

    public function toggleAdmin(): void
    {
        if (! $this->customerId) {
            return;
        }

        $customer = User::find($this->customerId);

        if (! $customer) {
            session()->flash('error', 'Customer not found.');
            $this->closeModal();

            return;
        }

        if ($customer->id === Auth::id()) {
            session()->flash('error', 'You cannot change your own admin status.');
            $this->closeModal();

            return;
        }

        $customer->update(['is_admin' => ! $customer->is_admin]);

        $status = $customer->is_admin ? 'granted' : 'revoked';
        session()->flash('success', "Admin access {$status} for {$customer->name}.");

        $this->dispatch('customer-updated');
        $this->closeModal();
    }

    public function impersonate(): void
    {
        if (! $this->customerId) {
            return;
        }

        $customer = User::find($this->customerId);

        if (! $customer) {
            session()->flash('error', 'Customer not found.');
            $this->closeModal();

            return;
        }

        if ($customer->id === Auth::id()) {
            session()->flash('error', 'You cannot impersonate yourself.');
            $this->closeModal();

            return;
        }

        session()->put('impersonator_id', Auth::id());
        Auth::login($customer);

        $this->closeModal();
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.customer-detail-modal');
    }
}
