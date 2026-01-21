<div class="w-full">
    {{-- Page Header --}}
    <div class="mb-8">
        <flux:heading size="xl" class="font-bold">My Orders</flux:heading>
        <flux:text variant="muted" class="mt-2">
            View your order history and details
        </flux:text>
    </div>

    {{-- Orders List --}}
    @if ($this->orders->isEmpty())
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <flux:icon.inbox class="w-16 h-16 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
            <flux:heading size="lg" class="mb-2">No orders found</flux:heading>
            <flux:text variant="muted">
                You don't have any orders yet.
            </flux:text>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Order ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Plan
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->orders as $order)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:text class="font-mono">#{{ $order->woocommerce_order_id }}</flux:text>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($order->subscription && $order->subscription->plan)
                                        <div>
                                            <flux:text class="font-medium">{{ $order->subscription->plan->name }}</flux:text>
                                            @if ($order->subscription->plan->description)
                                                <flux:text variant="muted" class="text-xs mt-0.5">
                                                    {{ Str::limit($order->subscription->plan->description, 50) }}
                                                </flux:text>
                                            @endif
                                        </div>
                                    @else
                                        <flux:text variant="muted">-</flux:text>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($order->status === \App\Enums\OrderStatus::Provisioned)
                                        <flux:badge color="green" icon="check-circle">Provisioned</flux:badge>
                                    @elseif ($order->status === \App\Enums\OrderStatus::PendingProvisioning)
                                        <flux:badge color="yellow" icon="clock">Pending Provisioning</flux:badge>
                                    @elseif ($order->status === \App\Enums\OrderStatus::ProvisioningFailed)
                                        <flux:badge color="red" icon="x-circle">Provisioning Failed</flux:badge>
                                    @elseif ($order->status === \App\Enums\OrderStatus::Refunded)
                                        <flux:badge color="yellow" icon="arrow-uturn-left">Refunded</flux:badge>
                                    @else
                                        <flux:badge>{{ $order->status->label() }}</flux:badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <flux:text>{{ $order->created_at->format('M d, Y') }}</flux:text>
                                        <flux:text variant="muted" class="text-xs">{{ $order->created_at->format('h:i A') }}</flux:text>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($order->amount)
                                        <flux:text class="font-semibold">
                                            {{ $order->currency }} {{ number_format($order->amount, 2) }}
                                        </flux:text>
                                    @else
                                        <flux:text variant="muted">-</flux:text>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $this->orders->links() }}
        </div>
    @endif
</div>
