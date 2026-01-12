<div class="w-full">
    {{-- Success Message --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex items-center gap-2">
                <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400 shrink-0" />
                <flux:text class="text-green-800 dark:text-green-200">
                    {{ session('success') }}
                </flux:text>
            </div>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="mb-8 flex items-start justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">My Support Tickets</flux:heading>
            <flux:text variant="muted" class="mt-2">
                View and manage your support tickets
            </flux:text>
        </div>
        <flux:button wire:click="openCreateModal" icon="plus">
            New Ticket
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-3">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search tickets by subject or ID..."
                    icon="magnifying-glass"
                />
            </div>
            <div>
                <flux:select wire:model.live="filterStatus">
                    <option value="all">All Tickets</option>
                    <option value="open">Open Tickets</option>
                    <option value="closed">Closed Tickets</option>
                </flux:select>
            </div>
        </div>
    </div>

    {{-- Tickets List --}}
    @if($tickets->isEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-12 text-center border border-zinc-200 dark:border-zinc-700">
            <flux:icon.chat-bubble-left-right class="size-12 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
            <flux:heading size="lg" class="mb-2">No Support Tickets</flux:heading>
            <flux:text variant="muted" class="mb-6">
                @if($search || $filterStatus !== 'all')
                    No tickets match your filters. Try adjusting your search or filters.
                @else
                    You haven't created any support tickets yet.
                @endif
            </flux:text>
            @if(!$search && $filterStatus === 'all')
                <flux:button wire:click="openCreateModal" icon="plus">
                    Create Your First Ticket
                </flux:button>
            @endif
        </div>
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow border border-zinc-200 dark:border-zinc-700">
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($tickets as $ticket)
                    <div
                        wire:key="ticket-{{ $ticket->id }}"
                        wire:click="viewTicket('{{ $ticket->id }}')"
                        class="p-6 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 cursor-pointer transition"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                {{-- Ticket Header --}}
                                <div class="flex items-center gap-3 mb-2">
                                    <flux:badge :color="$ticket->status->color()" size="sm">
                                        {{ $ticket->status->label() }}
                                    </flux:badge>
                                    <flux:badge :color="$ticket->priority->color()" size="sm">
                                        {{ $ticket->priority->label() }}
                                    </flux:badge>
                                    <flux:text variant="muted" size="sm">
                                        #{{ substr($ticket->id, 0, 8) }}
                                    </flux:text>
                                </div>

                                {{-- Subject --}}
                                <flux:heading size="base" class="mb-2 truncate">
                                    {{ $ticket->subject }}
                                </flux:heading>

                                {{-- Category & Date --}}
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap mr-26">
                                        <flux:icon.{{ $ticket->category->icon() }} class="size-4" />
                                        {{ $ticket->category->label() }}
                                    </span>
                                    <span class="mx-2 text-zinc-300 dark:text-zinc-600">&middot;</span>
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                                        <flux:icon.clock class="size-4" />
                                        {{ $ticket->created_at->diffForHumans() }}
                                    </span>
                                    @if($ticket->messages->isNotEmpty())
                                        <span class="mx-2 text-zinc-300 dark:text-zinc-600">&middot;</span>
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                                            <flux:icon.chat-bubble-left class="size-4" />
                                            Last reply {{ $ticket->messages->first()->created_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Arrow Icon --}}
                            <flux:icon.chevron-right class="size-5 text-zinc-400 dark:text-zinc-600 flex-shrink-0" />
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="p-6 border-t border-zinc-200 dark:border-zinc-700">
                {{ $tickets->links() }}
            </div>
        </div>
    @endif

    {{-- Create Ticket Modal --}}
    <livewire:customer.create-ticket-modal wire:key="create-ticket-modal" />

    {{-- Ticket Detail Modal --}}
    @if($selectedTicketId)
        <livewire:customer.ticket-detail-modal
            wire:key="ticket-detail-{{ $selectedTicketId }}"
            :ticket-id="$selectedTicketId"
            @close="closeTicketDetail"
        />
    @endif
</div>
