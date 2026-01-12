<flux:modal name="ticket-detail" class="md:w-[800px]">
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

        <div class="space-y-6">
        {{-- Ticket Info --}}
        <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <flux:text variant="muted" size="sm" class="mb-1">Category</flux:text>
                    <div class="flex items-center gap-1.5">
                        <flux:icon.{{ $ticket->category->icon() }} class="size-4" />
                        <flux:text size="sm">{{ $ticket->category->label() }}</flux:text>
                    </div>
                </div>
                <div>
                    <flux:text variant="muted" size="sm" class="mb-1">Created</flux:text>
                    <flux:text size="sm">{{ $ticket->created_at->format('M d, Y') }}</flux:text>
                </div>
                @if($ticket->subscription)
                    <div>
                        <flux:text variant="muted" size="sm" class="mb-1">Subscription</flux:text>
                        <flux:text size="sm" class="truncate">{{ $ticket->subscription->domain }}</flux:text>
                    </div>
                @endif
                @if($ticket->assignedAdmin)
                    <div>
                        <flux:text variant="muted" size="sm" class="mb-1">Assigned To</flux:text>
                        <flux:text size="sm">{{ $ticket->assignedAdmin->name }}</flux:text>
                    </div>
                @endif
            </div>
        </div>

        {{-- Messages --}}
        <div class="space-y-4 max-h-[500px] overflow-y-auto">
            @forelse($ticket->messages as $message)
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
                                    <flux:badge color="blue" size="xs" class="ml-1">Support Team</flux:badge>
                                @endif
                            </flux:text>
                            <flux:text variant="muted" size="xs">
                                {{ $message->created_at->format('M d, Y H:i') }}
                            </flux:text>
                        </div>

                        <div class="bg-{{ $message->isFromAdmin() ? 'blue-50 dark:bg-blue-900/20' : 'zinc-100 dark:bg-zinc-800' }} rounded-lg p-4 {{ $message->isFromAdmin() ? 'rounded-tl-none' : 'rounded-tr-none' }}">
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

        {{-- Reply Form --}}
        @if($ticket->isOpen())
            <form wire:submit="sendReply" class="border-t border-zinc-200 dark:border-zinc-700 pt-6">
                <flux:field>
                    <flux:label>Your Reply</flux:label>
                    <flux:textarea
                        wire:model="replyMessage"
                        placeholder="Type your reply here..."
                        rows="4"
                        required
                    />
                    <flux:error name="replyMessage" />
                    <flux:text variant="muted" size="sm">
                        Minimum 10 characters
                    </flux:text>
                </flux:field>

                <div class="flex justify-end mt-4">
                    <flux:button type="submit" variant="primary" icon="paper-airplane">
                        Send Reply
                    </flux:button>
                </div>
            </form>
        @else
            <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-4 text-center border border-zinc-200 dark:border-zinc-700">
                <flux:text variant="muted">
                    This ticket is {{ $ticket->status->label() }}. You cannot add new replies.
                </flux:text>
            </div>
        @endif
        </div>
    </div>
</flux:modal>
