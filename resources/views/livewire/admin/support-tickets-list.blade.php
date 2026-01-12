<div class="w-full">
    {{-- Page Header --}}
    <div class="mb-8">
        <flux:heading size="xl" class="font-bold">Support Tickets</flux:heading>
        <flux:text variant="muted" class="mt-2">
            Manage and respond to customer support tickets
        </flux:text>
    </div>

    {{-- Filters --}}
    <div class="mb-6 space-y-4">
        {{-- Search --}}
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search by ticket ID, subject, customer name, or email..."
                icon="magnifying-glass"
            />
        </div>

        {{-- Filter Row --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <flux:select wire:model.live="filterStatus">
                <option value="all">All Statuses</option>
                <option value="open">Open Tickets</option>
                <option value="closed">Closed Tickets</option>
                <option value="unassigned">Unassigned</option>
                <option value="needs_response">Needs First Response</option>
            </flux:select>

            <flux:select wire:model.live="filterCategory">
                <option value="all">All Categories</option>
                <option value="technical">Technical</option>
                <option value="billing">Billing</option>
                <option value="account">Account</option>
                <option value="general">General</option>
            </flux:select>

            <flux:select wire:model.live="filterPriority">
                <option value="all">All Priorities</option>
                <option value="urgent">Urgent</option>
                <option value="high">High</option>
                <option value="normal">Normal</option>
                <option value="low">Low</option>
            </flux:select>

            <flux:select wire:model.live="filterAssignment">
                <option value="all">All Assignments</option>
                <option value="mine">Assigned to Me</option>
                <option value="unassigned">Unassigned</option>
            </flux:select>
        </div>
    </div>

    {{-- Tickets List --}}
    @if($tickets->isEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-12 text-center border border-zinc-200 dark:border-zinc-700">
            <flux:icon.chat-bubble-left-right class="size-12 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
            <flux:heading size="lg" class="mb-2">No Tickets Found</flux:heading>
            <flux:text variant="muted">
                @if($search || $filterStatus !== 'all' || $filterCategory !== 'all' || $filterPriority !== 'all' || $filterAssignment !== 'all')
                    No tickets match your current filters. Try adjusting your search or filters.
                @else
                    No support tickets have been created yet.
                @endif
            </flux:text>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow border border-zinc-200 dark:border-zinc-700">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Ticket</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Assigned</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Updated</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($tickets as $ticket)
                            <tr wire:key="ticket-{{ $ticket->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <flux:text class="font-medium truncate max-w-xs">
                                            {{ $ticket->subject }}
                                        </flux:text>
                                        <flux:text variant="muted" size="xs">
                                            #{{ substr($ticket->id, 0, 8) }}
                                        </flux:text>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <flux:text size="sm">{{ $ticket->user->name }}</flux:text>
                                        <flux:text variant="muted" size="xs">{{ $ticket->user->email }}</flux:text>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon.{{ $ticket->category->icon() }} class="size-4" />
                                        <flux:text size="sm">{{ $ticket->category->label() }}</flux:text>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:badge :color="$ticket->status->color()" size="sm">
                                        {{ $ticket->status->label() }}
                                    </flux:badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:badge :color="$ticket->priority->color()" size="sm">
                                        {{ $ticket->priority->label() }}
                                    </flux:badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($ticket->assignedAdmin)
                                        <flux:text size="sm">{{ $ticket->assignedAdmin->name }}</flux:text>
                                    @else
                                        <flux:text variant="muted" size="sm">Unassigned</flux:text>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:text size="sm" variant="muted">
                                        {{ $ticket->updated_at->diffForHumans() }}
                                    </flux:text>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <flux:button
                                        wire:click="viewTicket('{{ $ticket->id }}')"
                                        size="sm"
                                        variant="ghost"
                                    >
                                        View
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="p-6 border-t border-zinc-200 dark:border-zinc-700">
                {{ $tickets->links() }}
            </div>
        </div>
    @endif

    {{-- Ticket Detail Modal --}}
    @if($selectedTicketId)
        <livewire:admin.ticket-detail-modal
            wire:key="admin-ticket-detail-{{ $selectedTicketId }}"
            :ticket-id="$selectedTicketId"
            @close="closeTicketDetail"
        />
    @endif
</div>
