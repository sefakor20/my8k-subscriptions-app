<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
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

    public bool $isInternalNote = false;

    public ?string $newStatus = null;

    public ?string $assignToUserId = null;

    public function mount(string $ticketId): void
    {
        $this->ticketId = $ticketId;
        $this->loadTicket();
        $this->authorize('view', $this->ticket);

        $this->newStatus = $this->ticket->status->value;
        $this->assignToUserId = $this->ticket->assigned_to;
    }

    #[On('ticket-updated')]
    public function loadTicket(): void
    {
        $this->ticket = SupportTicket::with([
            'user',
            'messages.user',
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
            $this->isInternalNote,
        );

        $this->replyMessage = '';
        $this->isInternalNote = false;
        $this->loadTicket();

        session()->flash('success', 'Your reply has been sent successfully!');
    }

    public function updateStatus(SupportTicketService $ticketService): void
    {
        $this->authorize('update', $this->ticket);

        if ($this->newStatus && $this->newStatus !== $this->ticket->status->value) {
            $ticketService->updateStatus(
                $this->ticket,
                TicketStatus::from($this->newStatus),
                auth()->user(),
            );

            $this->loadTicket();
            session()->flash('success', 'Ticket status updated successfully!');
        }
    }

    public function assignTicket(SupportTicketService $ticketService): void
    {
        $this->authorize('update', $this->ticket);

        if ($this->assignToUserId && $this->assignToUserId !== $this->ticket->assigned_to) {
            $admin = User::findOrFail($this->assignToUserId);

            $ticketService->assignTicket(
                $this->ticket,
                $admin,
                auth()->user(),
            );

            $this->loadTicket();
            session()->flash('success', 'Ticket assigned successfully!');
        } elseif (!$this->assignToUserId && $this->ticket->assigned_to) {
            $ticketService->unassignTicket($this->ticket);
            $this->loadTicket();
            session()->flash('success', 'Ticket unassigned successfully!');
        }
    }

    public function close(): void
    {
        $this->dispatch('close');
    }

    public function render()
    {
        $admins = User::where('is_admin', true)->get();

        return view('livewire.admin.ticket-detail-modal', [
            'admins' => $admins,
            'statuses' => TicketStatus::cases(),
        ]);
    }
}
