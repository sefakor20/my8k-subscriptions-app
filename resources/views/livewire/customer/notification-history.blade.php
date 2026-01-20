<div class="w-full">
    {{-- Page Header --}}
    <div class="mb-8">
        <flux:heading size="xl" class="font-bold">Notification History</flux:heading>
        <flux:text variant="muted" class="mt-2">
            View your email notification history
        </flux:text>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <flux:select wire:model.live="filterCategory" placeholder="All Categories">
                    <option value="">All Categories</option>
                    @foreach($this->categories as $category)
                        <option value="{{ $category->value }}">{{ $category->label() }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select wire:model.live="filterStatus" placeholder="All Statuses">
                    <option value="">All Statuses</option>
                    @foreach($this->statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="flex items-end">
                @if($filterCategory || $filterStatus)
                    <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                        Clear Filters
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    {{-- Notifications List --}}
    @if($this->notifications->isEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-12 text-center border border-zinc-200 dark:border-zinc-700">
            <flux:icon.bell-slash class="size-12 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
            <flux:heading size="lg" class="mb-2">No Notifications</flux:heading>
            <flux:text variant="muted">
                @if($filterCategory || $filterStatus)
                    No notifications match your filters.
                @else
                    You haven't received any notifications yet.
                @endif
            </flux:text>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow border border-zinc-200 dark:border-zinc-700">
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($this->notifications as $notification)
                    <div wire:key="notification-{{ $notification->id }}" class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:badge :color="$notification->status->color()" size="sm">
                                        {{ $notification->status->label() }}
                                    </flux:badge>
                                    <flux:badge :color="$notification->category->color()" size="sm" variant="outline">
                                        {{ $notification->category->label() }}
                                    </flux:badge>
                                </div>
                                <flux:heading size="sm" class="truncate">
                                    {{ $notification->subject ?? $notification->short_type }}
                                </flux:heading>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                                    {{ $notification->short_type }}
                                </flux:text>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $notification->created_at->diffForHumans() }}
                                </flux:text>
                                @if($notification->sent_at)
                                    <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">
                                        {{ $notification->sent_at->format('M d, Y H:i') }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>
                        @if($notification->failure_reason)
                            <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 rounded text-sm text-red-600 dark:text-red-400">
                                {{ $notification->failure_reason }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $this->notifications->links() }}
        </div>
    @endif
</div>
