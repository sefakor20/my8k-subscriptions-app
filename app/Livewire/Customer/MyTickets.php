<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\SupportTicket;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MyTickets extends Component
{
    use WithPagination;

    public string $filterStatus = 'all';

    public string $search = '';

    public ?string $selectedTicketId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', SupportTicket::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->dispatch('open-create-ticket-modal');
    }

    #[On('ticket-created')]
    public function refreshTickets(): void
    {
        // Reset to first page to show the new ticket
        $this->resetPage();
    }

    public function viewTicket(string $ticketId): void
    {
        $this->selectedTicketId = $ticketId;
    }

    public function closeTicketDetail(): void
    {
        $this->selectedTicketId = null;
    }

    public function render()
    {
        $query = SupportTicket::query()
            ->where('user_id', auth()->id())
            ->with(['messages' => fn($q) => $q->latest()->limit(1)])
            ->latest();

        // Apply status filter
        if ($this->filterStatus === 'open') {
            $query->open();
        } elseif ($this->filterStatus === 'closed') {
            $query->closed();
        }

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('subject', 'like', '%' . $this->search . '%')
                    ->orWhere('id', 'like', '%' . $this->search . '%');
            });
        }

        $tickets = $query->paginate(15);

        return view('livewire.customer.my-tickets', [
            'tickets' => $tickets,
        ])->layout('components.layouts.app');
    }
}
