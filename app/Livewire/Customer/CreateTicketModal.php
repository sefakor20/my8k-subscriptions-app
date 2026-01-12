<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\Subscription;
use App\Services\SupportTicketService;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CreateTicketModal extends Component
{
    public bool $show = false;

    #[Validate('required|string|max:255')]
    public string $subject = '';

    #[Validate('required')]
    public string $category = '';

    #[Validate('required')]
    public string $priority = 'normal';

    #[Validate('required|string|min:10')]
    public string $message = '';

    #[Validate('nullable|exists:subscriptions,id')]
    public ?string $subscription_id = null;

    #[Validate('nullable|exists:orders,id')]
    public ?string $order_id = null;

    #[On('open-create-ticket-modal')]
    public function openModal(): void
    {
        $this->authorize('create', SupportTicket::class);
        $this->show = true;
    }

    public function submit(SupportTicketService $ticketService): void
    {
        $this->authorize('create', SupportTicket::class);

        $this->validate();

        $ticket = $ticketService->createTicket(
            auth()->user(),
            [
                'subject' => $this->subject,
                'category' => TicketCategory::from($this->category),
                'priority' => TicketPriority::from($this->priority),
                'subscription_id' => $this->subscription_id,
                'order_id' => $this->order_id,
            ],
            $this->message,
        );

        session()->flash('success', 'Your support ticket has been created successfully!');

        $this->dispatch('ticket-created', ticketId: $ticket->id);
        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->reset(['subject', 'category', 'priority', 'message', 'subscription_id', 'order_id']);
        $this->resetValidation();
    }

    public function close(): void
    {
        $this->closeModal();
    }

    public function render()
    {
        $subscriptions = Subscription::where('user_id', auth()->id())
            ->latest()
            ->get();

        $orders = Order::where('user_id', auth()->id())
            ->latest()
            ->limit(20)
            ->get();

        return view('livewire.customer.create-ticket-modal', [
            'subscriptions' => $subscriptions,
            'orders' => $orders,
            'categories' => TicketCategory::cases(),
            'priorities' => TicketPriority::cases(),
        ]);
    }
}
