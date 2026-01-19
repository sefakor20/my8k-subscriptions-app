<div class="w-full">
    {{-- Page Header --}}
    <div class="mb-8">
        <flux:heading size="xl" class="font-bold">My Invoices</flux:heading>
        <flux:text variant="muted" class="mt-2">
            View and download your payment invoices
        </flux:text>
    </div>

    {{-- Invoices List --}}
    @if ($this->invoices->isEmpty())
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <flux:icon.document-text class="w-16 h-16 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
            <flux:heading size="lg" class="mb-2">No invoices found</flux:heading>
            <flux:text variant="muted">
                You don't have any invoices yet. Invoices are generated after successful payments.
            </flux:text>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Invoice Number
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Issue Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->invoices as $invoice)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:text class="font-mono font-medium">{{ $invoice->invoice_number }}</flux:text>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($invoice->status === \App\Enums\InvoiceStatus::Paid)
                                        <flux:badge variant="success" icon="check-circle">{{ $invoice->status->label() }}</flux:badge>
                                    @elseif ($invoice->status === \App\Enums\InvoiceStatus::Issued)
                                        <flux:badge variant="primary" icon="document-text">{{ $invoice->status->label() }}</flux:badge>
                                    @elseif ($invoice->status === \App\Enums\InvoiceStatus::Void)
                                        <flux:badge variant="danger" icon="x-circle">{{ $invoice->status->label() }}</flux:badge>
                                    @else
                                        <flux:badge>{{ $invoice->status->label() }}</flux:badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <flux:text>{{ $invoice->issued_at?->format('M d, Y') ?? '-' }}</flux:text>
                                        @if ($invoice->issued_at)
                                            <flux:text variant="muted" class="text-xs">{{ $invoice->issued_at->format('h:i A') }}</flux:text>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:text class="font-semibold">
                                        {{ $invoice->formattedTotal() }}
                                    </flux:text>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <flux:button
                                        wire:click="download('{{ $invoice->id }}')"
                                        variant="ghost"
                                        size="sm"
                                        icon="arrow-down-tray"
                                    >
                                        Download PDF
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $this->invoices->links() }}
        </div>
    @endif
</div>
