<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-bold">Invoices Management</flux:heading>
            <flux:text variant="muted" class="mt-2">
                View and manage all customer invoices
            </flux:text>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-6">
            <flux:callout variant="success" icon="check-circle">
                {{ session('success') }}
            </flux:callout>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6">
            <flux:callout variant="danger" icon="x-circle">
                {{ session('error') }}
            </flux:callout>
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Search --}}
            <div>
                <flux:field>
                    <flux:label>Search</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Invoice number, email, or name..."
                    />
                </flux:field>
            </div>

            {{-- Status Filter --}}
            <div>
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">All Statuses</option>
                        @foreach ($this->statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Date From --}}
            <div>
                <flux:field>
                    <flux:label>From Date</flux:label>
                    <flux:input
                        wire:model.live="dateFrom"
                        type="date"
                    />
                </flux:field>
            </div>

            {{-- Date To --}}
            <div>
                <flux:field>
                    <flux:label>To Date</flux:label>
                    <flux:input
                        wire:model.live="dateTo"
                        type="date"
                    />
                </flux:field>
            </div>
        </div>

        {{-- Reset Button --}}
        <div class="flex items-end mt-4">
            <flux:button wire:click="resetFilters" variant="subtle" icon="arrow-path">
                Reset Filters
            </flux:button>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Invoice Number
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Customer
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Issue Date
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Amount
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->invoices as $invoice)
                        <tr wire:key="invoice-{{ $invoice->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                <span class="font-mono">{{ $invoice->invoice_number }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $invoice->user->name }}
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $invoice->user->email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($invoice->status === \App\Enums\InvoiceStatus::Paid)
                                    <flux:badge variant="success" icon="check-circle">{{ $invoice->status->label() }}</flux:badge>
                                @elseif ($invoice->status === \App\Enums\InvoiceStatus::Issued)
                                    <flux:badge variant="primary" icon="document-text">{{ $invoice->status->label() }}</flux:badge>
                                @elseif ($invoice->status === \App\Enums\InvoiceStatus::Void)
                                    <flux:badge variant="danger" icon="x-circle">{{ $invoice->status->label() }}</flux:badge>
                                @elseif ($invoice->status === \App\Enums\InvoiceStatus::Draft)
                                    <flux:badge variant="warning" icon="pencil-square">{{ $invoice->status->label() }}</flux:badge>
                                @else
                                    <flux:badge>{{ $invoice->status->label() }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $invoice->issued_at?->format('M d, Y H:i') ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $invoice->formattedTotal() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                    <flux:menu>
                                        <flux:menu.item wire:click="download('{{ $invoice->id }}')" icon="arrow-down-tray">
                                            Download PDF
                                        </flux:menu.item>

                                        @if ($invoice->status->canBeVoided())
                                            <flux:menu.item
                                                wire:click="voidInvoice('{{ $invoice->id }}')"
                                                wire:confirm="Are you sure you want to void this invoice? This action cannot be undone."
                                                icon="x-circle"
                                            >
                                                Void Invoice
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <flux:icon.document-text class="size-12 text-zinc-400 dark:text-zinc-600 mb-4" />
                                    <flux:heading size="lg">No invoices found</flux:heading>
                                    <flux:text variant="muted" class="mt-2">
                                        Try adjusting your filters or search criteria
                                    </flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($this->invoices->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->invoices->links() }}
            </div>
        @endif
    </div>
</div>
