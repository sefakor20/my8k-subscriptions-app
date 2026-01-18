<flux:modal wire:model.self="show" @close="close" class="md:w-[1000px] lg:w-[1200px]">
    <div class="p-6">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-4 mb-6">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
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
                <flux:heading size="lg" class="truncate">{{ $ticket->subject }}</flux:heading>
            </div>
            <flux:button icon="x-mark" size="sm" variant="ghost" wire:click="close" />
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3">
                <div class="flex items-center gap-2">
                    <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400 shrink-0" />
                    <flux:text class="text-green-800 dark:text-green-200 text-sm">
                        {{ session('success') }}
                    </flux:text>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            {{-- Main Content (3 columns) --}}
            <div class="lg:col-span-3 space-y-6">
                {{-- Customer Info --}}
                <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm" class="mb-3">Customer Information</flux:heading>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text variant="muted" size="sm" class="mb-1">Name</flux:text>
                            <flux:text size="sm" class="font-medium">{{ $ticket->user->name }}</flux:text>
                        </div>
                        <div>
                            <flux:text variant="muted" size="sm" class="mb-1">Email</flux:text>
                            <flux:text size="sm">{{ $ticket->user->email }}</flux:text>
                        </div>
                        @if($ticket->subscription)
                            <div>
                                <flux:text variant="muted" size="sm" class="mb-1">Subscription</flux:text>
                                <flux:text size="sm" class="truncate">{{ $ticket->subscription->domain }}</flux:text>
                            </div>
                        @endif
                        @if($ticket->order)
                            <div>
                                <flux:text variant="muted" size="sm" class="mb-1">Order</flux:text>
                                <flux:text size="sm">#{{ substr($ticket->order->id, 0, 8) }}</flux:text>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Messages Thread --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Conversation</flux:heading>
                    <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2">
                        @forelse($ticket->messages as $message)
                            @if($message->is_internal_note)
                                {{-- Internal Note --}}
                                <div wire:key="message-{{ $message->id }}" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <flux:icon.lock-closed class="size-4 text-yellow-600 dark:text-yellow-400" />
                                        <flux:text size="sm" class="font-medium text-yellow-800 dark:text-yellow-200">
                                            Internal Note by {{ $message->user->name }}
                                        </flux:text>
                                        <flux:text variant="muted" size="xs">
                                            {{ $message->created_at->format('M d, Y H:i') }}
                                        </flux:text>
                                    </div>
                                    <div class="text-sm text-yellow-900 dark:text-yellow-100 whitespace-pre-wrap">
                                        {{ $message->message }}
                                    </div>
                                </div>
                            @else
                                {{-- Regular Message --}}
                                <div wire:key="message-{{ $message->id }}" class="flex gap-3 {{ $message->isFromAdmin() ? 'flex-row' : 'flex-row-reverse' }}">
                                    {{-- Avatar --}}
                                    <div class="flex-shrink-0">
                                        <div class="size-10 rounded-full {{ $message->isFromAdmin() ? 'bg-blue-100 dark:bg-blue-900' : 'bg-zinc-200 dark:bg-zinc-700' }} flex items-center justify-center">
                                            <flux:icon.{{ $message->isFromAdmin() ? 'user-circle' : 'user' }}
                                                class="size-6 {{ $message->isFromAdmin() ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-600 dark:text-zinc-400' }}"
                                            />
                                        </div>
                                    </div>

                                    {{-- Message Content --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1 {{ $message->isFromAdmin() ? '' : 'justify-end' }}">
                                            <flux:text size="sm" class="font-medium">
                                                {{ $message->user->name }}
                                                @if($message->isFromAdmin())
                                                    <flux:badge color="blue" size="xs" class="ml-1">Support</flux:badge>
                                                @else
                                                    <flux:badge color="zinc" size="xs" class="ml-1">Customer</flux:badge>
                                                @endif
                                            </flux:text>
                                            <flux:text variant="muted" size="xs">
                                                {{ $message->created_at->format('M d, Y H:i') }}
                                            </flux:text>
                                        </div>

                                        <div class="{{ $message->isFromAdmin() ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-zinc-100 dark:bg-zinc-800' }} rounded-lg p-4 {{ $message->isFromAdmin() ? 'rounded-tl-none' : 'rounded-tr-none' }}">
                                            <div class="text-sm text-zinc-900 dark:text-zinc-100 whitespace-pre-wrap break-words">
                                                {{ $message->message }}
                                            </div>

                                            @if($message->hasAttachments())
                                                <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                                    <flux:text variant="muted" size="xs" class="mb-2">Attachments:</flux:text>
                                                    <div class="space-y-1">
                                                        @foreach($message->attachments as $attachment)
                                                            <div class="flex items-center gap-2">
                                                                <flux:icon.paper-clip class="size-3" />
                                                                <flux:text size="xs">{{ $attachment }}</flux:text>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="text-center py-8 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                                <flux:text variant="muted">No messages yet</flux:text>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Reply Form --}}
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6">
                    <form wire:submit="sendReply">
                        <flux:field>
                            <flux:label>Reply</flux:label>
                            <flux:textarea
                                wire:model="replyMessage"
                                placeholder="Type your reply here..."
                                rows="4"
                            />
                            <flux:error name="replyMessage" />
                        </flux:field>

                        <div class="flex items-center justify-between mt-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <flux:checkbox wire:model="isInternalNote" />
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                    Internal note (not visible to customer)
                                </span>
                            </label>

                            <flux:button type="submit" variant="primary" icon="paper-airplane">
                                {{ $isInternalNote ? 'Add Note' : 'Send Reply' }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Sidebar (1 column) --}}
            <div class="space-y-5">
                {{-- Ticket Details & Status --}}
                <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-5 border border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm" class="mb-4">Ticket Details</flux:heading>
                    <div class="space-y-4">
                        <div>
                            <flux:text variant="muted" size="sm" class="mb-1">Category</flux:text>
                            <div class="flex items-center gap-1.5">
                                <flux:icon.{{ $ticket->category->icon() }} class="size-4 shrink-0" />
                                <flux:text size="sm" class="whitespace-nowrap">{{ $ticket->category->label() }}</flux:text>
                            </div>
                        </div>
                        <div>
                            <flux:text variant="muted" size="sm" class="mb-1">Created</flux:text>
                            <flux:text size="sm" class="whitespace-nowrap">{{ $ticket->created_at->format('M d, Y H:i') }}</flux:text>
                        </div>
                        @if($ticket->first_response_at)
                            <div>
                                <flux:text variant="muted" size="sm" class="mb-1">First Response</flux:text>
                                <flux:text size="sm" class="whitespace-nowrap">{{ $ticket->first_response_at->format('M d, Y H:i') }}</flux:text>
                            </div>
                        @else
                            <div>
                                <flux:text variant="muted" size="sm" class="mb-1">First Response</flux:text>
                                <flux:badge color="yellow" size="sm">Awaiting</flux:badge>
                            </div>
                        @endif
                        @if($ticket->resolved_at)
                            <div>
                                <flux:text variant="muted" size="sm" class="mb-1">Resolved</flux:text>
                                <flux:text size="sm" class="whitespace-nowrap">{{ $ticket->resolved_at->format('M d, Y H:i') }}</flux:text>
                            </div>
                        @endif
                    </div>

                    <flux:separator class="my-4" />

                    <flux:heading size="sm" class="mb-3">Update Status</flux:heading>
                    <div class="space-y-3">
                        <flux:select wire:model="newStatus" class="w-full">
                            @foreach($statuses as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </flux:select>
                        <flux:button
                            wire:click="updateStatus"
                            variant="primary"
                            class="w-full"
                            size="sm"
                        >
                            Update Status
                        </flux:button>
                    </div>
                </div>

                {{-- Assignment & Quick Actions --}}
                <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-5 border border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm" class="mb-3">Assignment</flux:heading>
                    <div class="space-y-3">
                        <flux:select wire:model="assignToUserId" class="w-full">
                            <option value="">Unassigned</option>
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:button
                            wire:click="assignTicket"
                            variant="primary"
                            class="w-full"
                            size="sm"
                        >
                            {{ $assignToUserId ? 'Assign Ticket' : 'Unassign Ticket' }}
                        </flux:button>
                    </div>

                    <flux:separator class="my-4" />

                    <flux:heading size="sm" class="mb-3">Quick Actions</flux:heading>
                    <div class="space-y-2">
                        @if($ticket->isOpen())
                            <flux:button
                                wire:click="$set('newStatus', 'resolved'); updateStatus()"
                                variant="ghost"
                                class="w-full justify-start"
                                size="sm"
                                icon="check-circle"
                            >
                                Mark as Resolved
                            </flux:button>
                            <flux:button
                                wire:click="$set('newStatus', 'closed'); updateStatus()"
                                variant="ghost"
                                class="w-full justify-start"
                                size="sm"
                                icon="x-circle"
                            >
                                Close Ticket
                            </flux:button>
                        @else
                            <flux:button
                                wire:click="$set('newStatus', 'open'); updateStatus()"
                                variant="ghost"
                                class="w-full justify-start"
                                size="sm"
                                icon="arrow-path"
                            >
                                Reopen Ticket
                            </flux:button>
                        @endif
                        @if(!$ticket->assigned_to)
                            <flux:button
                                wire:click="$set('assignToUserId', {{ auth()->id() }}); assignTicket()"
                                variant="ghost"
                                class="w-full justify-start"
                                size="sm"
                                icon="user-plus"
                            >
                                Assign to Me
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</flux:modal>
