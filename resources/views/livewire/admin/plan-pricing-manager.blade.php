<div class="space-y-4">
    {{-- Success Message --}}
    @if (session()->has('pricing-success'))
        <div class="p-3 text-sm text-green-700 bg-green-50 dark:bg-green-900/20 dark:text-green-400 rounded-lg">
            {{ session('pricing-success') }}
        </div>
    @endif

    {{-- Header with Add Button --}}
    <div class="flex items-center justify-between">
        <flux:text variant="muted" class="text-sm">
            Configure different prices for each payment gateway and currency.
        </flux:text>

        @if (!$showForm)
            <flux:button wire:click="showAddForm" size="sm" variant="primary">
                Add Price
            </flux:button>
        @endif
    </div>

    {{-- Add/Edit Form --}}
    @if ($showForm)
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="mb-4">
                {{ $editingPriceId ? 'Edit Price' : 'Add New Price' }}
            </flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <flux:field>
                        <flux:label>Gateway</flux:label>
                        <flux:select wire:model="gateway">
                            @foreach ($gatewayOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="gateway" />
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>Currency *</flux:label>
                        <flux:select wire:model="currency">
                            @foreach ($currencyOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="currency" />
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>Price *</flux:label>
                        <flux:input wire:model="price" type="number" step="0.01" min="0" placeholder="0.00" />
                        <flux:error name="price" />
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <div class="flex items-center gap-2 mt-2">
                            <flux:switch wire:model="isActive" />
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">Active</span>
                        </div>
                    </flux:field>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-4">
                <flux:button wire:click="savePrice" size="sm" variant="primary">
                    {{ $editingPriceId ? 'Update' : 'Add' }}
                </flux:button>
                <flux:button wire:click="cancelForm" size="sm" variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Prices Table --}}
    @if ($prices->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="text-left py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">Gateway</th>
                        <th class="text-left py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">Currency</th>
                        <th class="text-right py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">Price</th>
                        <th class="text-center py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">Status</th>
                        <th class="text-right py-2 px-3 font-medium text-zinc-600 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prices as $priceItem)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50" wire:key="price-{{ $priceItem->id }}">
                            <td class="py-2 px-3">
                                @if ($priceItem->gateway)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        @if ($priceItem->gateway === 'paystack') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                        @elseif ($priceItem->gateway === 'stripe') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                        @else bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300
                                        @endif">
                                        {{ ucfirst($priceItem->gateway) }}
                                    </span>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">Default</span>
                                @endif
                            </td>
                            <td class="py-2 px-3 font-mono">{{ $priceItem->currency }}</td>
                            <td class="py-2 px-3 text-right font-mono">{{ number_format((float) $priceItem->price, 2) }}</td>
                            <td class="py-2 px-3 text-center">
                                <button type="button" wire:click="toggleActive('{{ $priceItem->id }}')" class="focus:outline-none">
                                    @if ($priceItem->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                                            Inactive
                                        </span>
                                    @endif
                                </button>
                            </td>
                            <td class="py-2 px-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" wire:click="editPrice('{{ $priceItem->id }}')" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button type="button" wire:click="deletePrice('{{ $priceItem->id }}')" wire:confirm="Are you sure you want to delete this price?" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-6 text-zinc-500 dark:text-zinc-400">
            <p>No gateway-specific prices configured.</p>
            <p class="text-sm mt-1">The base plan price will be used for all gateways.</p>
        </div>
    @endif
</div>
