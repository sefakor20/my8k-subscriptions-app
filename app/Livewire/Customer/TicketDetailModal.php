<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class TicketDetailModal extends Component
{
    public SupportTicket $ticket;

    public string $ticketId;

    #[Validate('required|string|min:10')]
    public string $replyMessage = '';

    public function mount(string $ticketId): void
    {
        $this->ticketId = $ticketId;
        $this->loadTicket();
        $this->authorize('view', $this->ticket);
    }

    #[On('ticket-updated')]
    public function loadTicket(): void
    {
        $this->ticket = SupportTicket::with([
            'messages' => fn($query) => $query->public()->with('user'),
            'assignedAdmin',
            'subscription',
            'order',
        ])->findOrFail($this->ticketId);
    }

    public function sendReply(SupportTicketService $ticketService): void
    {
        $this->authorize('update', $this->ticket);

        $this->validate();

        $ticketService->addMessage(
            $this->ticket,
            auth()->user(),
            $this->replyMessage,
        );

        $this->replyMessage = '';
        $this->loadTicket();

        session()->flash('success', 'Your reply has been sent successfully!');
    }

    public function close(): void
    {
        $this->dispatch('close');
    }

    public function render()
    {
        return view('livewire.customer.ticket-detail-modal');
    }
}
