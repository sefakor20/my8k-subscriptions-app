<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Models\SupportTicket;
use Livewire\Component;
use Livewire\WithPagination;

class SupportTicketsList extends Component
{
    use WithPagination;

    public string $filterStatus = 'open';

    public string $filterCategory = 'all';

    public string $filterPriority = 'all';

    public string $filterAssignment = 'all';

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

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPriority(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAssignment(): void
    {
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
            ->with(['user', 'assignedAdmin', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->latest();

        // Apply status filter
        if ($this->filterStatus === 'open') {
            $query->open();
        } elseif ($this->filterStatus === 'closed') {
            $query->closed();
        } elseif ($this->filterStatus === 'unassigned') {
            $query->unassigned()->open();
        } elseif ($this->filterStatus === 'needs_response') {
            $query->open()->whereNull('first_response_at');
        }

        // Apply category filter
        if ($this->filterCategory !== 'all') {
            $query->byCategory(TicketCategory::from($this->filterCategory));
        }

        // Apply priority filter
        if ($this->filterPriority !== 'all') {
            $query->byPriority(TicketPriority::from($this->filterPriority));
        }

        // Apply assignment filter
        if ($this->filterAssignment === 'mine') {
            $query->assignedTo(auth()->id());
        } elseif ($this->filterAssignment === 'unassigned') {
            $query->unassigned();
        }

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('subject', 'like', '%' . $this->search . '%')
                    ->orWhere('id', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $tickets = $query->paginate(20);

        return view('livewire.admin.support-tickets-list', [
            'tickets' => $tickets,
        ]);
    }
}
