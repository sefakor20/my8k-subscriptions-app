<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="close">
    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        {{-- Header --}}
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-4">
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
        </div>

        {{-- Content --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            {{-- Ticket Info --}}
            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <flux:text variant="muted" size="sm" class="mb-1">Category</flux:text>
                        <div class="flex items-center gap-1.5">
                            <flux:icon :name="$ticket->category->icon()" class="size-4" />
                            <flux:text size="sm">{{ $ticket->category->label() }}</flux:text>
                        </div>
                    </div>
                    <div>
                        <flux:text variant="muted" size="sm" class="mb-1">Created</flux:text>
                        <flux:text size="sm">{{ $ticket->created_at->format('M d, Y H:i') }}</flux:text>
                    </div>
                    <div>
                        <flux:text variant="muted" size="sm" class="mb-1">Customer</flux:text>
                        <flux:text size="sm">{{ $ticket->user->name }}</flux:text>
                        <flux:text variant="muted" size="xs">{{ $ticket->user->email }}</flux:text>
                    </div>
                    @if($ticket->first_response_at)
                        <div>
                            <flux:text variant="muted" size="sm" class="mb-1">First Response</flux:text>
                            <flux:text size="sm">{{ $ticket->first_response_at->diffForHumans() }}</flux:text>
                        </div>
                    @else
                        <div>
                            <flux:text variant="muted" size="sm" class="mb-1">First Response</flux:text>
                            <flux:badge color="orange" size="sm">Awaiting</flux:badge>
                        </div>
                    @endif
                </div>

                @if($ticket->subscription || $ticket->order)
                    <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 grid grid-cols-2 gap-4">
                        @if($ticket->subscription)
                            <div>
                                <flux:text variant="muted" size="sm" class="mb-1">Related Subscription</flux:text>
                                <flux:text size="sm" class="truncate">{{ $ticket->subscription->domain }}</flux:text>
                            </div>
                        @endif
                        @if($ticket->order)
                            <div>
                                <flux:text variant="muted" size="sm" class="mb-1">Related Order</flux:text>
                                <flux:text size="sm">#{{ $ticket->order->id }}</flux:text>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Admin Actions --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                <flux:heading size="sm" class="mb-4">Admin Actions</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Status Change --}}
                    <div>
                        <flux:field>
                            <flux:label>Status</flux:label>
                            <div class="flex gap-2">
                                <flux:select wire:model="newStatus" class="flex-1">
                                    @foreach($statuses as $status)
                                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:button wire:click="updateStatus" size="sm" variant="primary">
                                    Update
                                </flux:button>
                            </div>
                        </flux:field>
                    </div>

                    {{-- Assignment --}}
                    <div>
                        <flux:field>
                            <flux:label>Assigned To</flux:label>
                            <div class="flex gap-2">
                                <flux:select wire:model="assignToUserId" class="flex-1">
                                    <option value="">Unassigned</option>
                                    @foreach($admins as $admin)
                                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:button wire:click="assignTicket" size="sm" variant="primary">
                                    Update
                                </flux:button>
                            </div>
                        </flux:field>
                    </div>
                </div>
            </div>

            {{-- Messages --}}
            <div>
                <flux:heading size="sm" class="mb-4">Conversation</flux:heading>
                <div class="space-y-4 max-h-[400px] overflow-y-auto">
                    @forelse($ticket->messages as $message)
                        <div wire:key="message-{{ $message->id }}" class="flex gap-3">
                            {{-- Avatar --}}
                            <div class="flex-shrink-0">
                                @if($message->is_internal_note)
                                    <div class="size-10 rounded-full bg-yellow-100 dark:bg-yellow-900 flex items-center justify-center">
                                        <flux:icon.lock-closed class="size-5 text-yellow-600 dark:text-yellow-400" />
                                    </div>
                                @elseif($message->isFromAdmin())
                                    <div class="size-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                        <flux:icon.user-circle class="size-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                @else
                                    <div class="size-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                        <flux:icon.user class="size-6 text-zinc-600 dark:text-zinc-400" />
                                    </div>
                                @endif
                            </div>

                            {{-- Message Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:text size="sm" class="font-medium">
                                        {{ $message->user->name }}
                                    </flux:text>
                                    @if($message->is_internal_note)
                                        <flux:badge color="yellow" size="xs">Internal Note</flux:badge>
                                    @elseif($message->isFromAdmin())
                                        <flux:badge color="blue" size="xs">Support Team</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="xs">Customer</flux:badge>
                                    @endif
                                    <flux:text variant="muted" size="xs">
                                        {{ $message->created_at->format('M d, Y H:i') }}
                                    </flux:text>
                                </div>

                                <div class="@if($message->is_internal_note) bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 @elseif($message->isFromAdmin()) bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 @else bg-zinc-100 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 @endif rounded-lg p-4 border">
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
                    @empty
                        <div class="text-center py-8">
                            <flux:text variant="muted">No messages yet</flux:text>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Reply Form --}}
            @if($ticket->isOpen())
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
                            <flux:checkbox wire:model="isInternalNote" label="Internal Note (not visible to customer)" />
                            <flux:button type="submit" variant="primary" icon="paper-airplane">
                                Send Reply
                            </flux:button>
                        </div>
                    </form>
                </div>
            @else
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 text-center border border-zinc-200 dark:border-zinc-700">
                    <flux:text variant="muted">
                        This ticket is {{ $ticket->status->label() }}. You cannot add new replies.
                    </flux:text>
                </div>
            @endif
        </div>
    </div>
</div>
