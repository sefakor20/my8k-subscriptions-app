<flux:modal wire:model.self="show" @close="closeModal" class="max-w-4xl">
    <div class="p-6">
        {{-- Header --}}
        <div class="mb-6">
            <flux:heading size="lg">{{ $mode === 'create' ? 'Create New Coupon' : 'Edit Coupon' }}</flux:heading>
            <flux:text variant="muted" class="mt-1">
                {{ $mode === 'create' ? 'Add a new promotional discount code' : 'Update coupon details' }}
            </flux:text>
        </div>

        {{-- Form --}}
        <form wire:submit="save">
            <div class="space-y-6">
                {{-- Basic Information --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Basic Information</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>Coupon Code *</flux:label>
                                <div class="flex gap-2">
                                    <div class="flex-1">
                                        <flux:input wire:model="code" type="text" placeholder="e.g., SAVE20" class="uppercase" />
                                    </div>
                                    <flux:button type="button" wire:click="generateCode" variant="subtle" size="sm">
                                        Generate
                                    </flux:button>
                                </div>
                                <flux:error name="code" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Name *</flux:label>
                                <flux:input wire:model="name" type="text" placeholder="e.g., Summer Sale 20% Off" />
                                <flux:error name="name" />
                            </flux:field>
                        </div>

                        <div class="md:col-span-2">
                            <flux:field>
                                <flux:label>Description</flux:label>
                                <flux:textarea wire:model="description" rows="2" placeholder="Optional description for internal reference" />
                                <flux:error name="description" />
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- Discount Settings --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Discount Settings</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>Discount Type *</flux:label>
                                <flux:select wire:model.live="discount_type">
                                    @foreach ($discountTypes as $type)
                                        <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="discount_type" />
                                <flux:text variant="muted" class="text-xs mt-1">
                                    @foreach ($discountTypes as $type)
                                        @if ($type['value'] === $discount_type)
                                            {{ $type['description'] }}
                                        @endif
                                    @endforeach
                                </flux:text>
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>
                                    @if ($discount_type === 'percentage')
                                        Discount Percentage *
                                    @elseif ($discount_type === 'fixed_amount')
                                        Discount Amount *
                                    @else
                                        Discount Value
                                    @endif
                                </flux:label>
                                <flux:input
                                    wire:model="discount_value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="$discount_type === 'percentage' ? 100 : null"
                                    :placeholder="$discount_type === 'percentage' ? '20' : '10.00'"
                                    :disabled="$discount_type === 'trial_extension'"
                                />
                                <flux:error name="discount_value" />
                            </flux:field>
                        </div>

                        @if ($discount_type === 'fixed_amount')
                            <div>
                                <flux:field>
                                    <flux:label>Currency *</flux:label>
                                    <flux:select wire:model="currency">
                                        <option value="">Select currency</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                        <option value="GBP">GBP</option>
                                        <option value="GHS">GHS</option>
                                    </flux:select>
                                    <flux:error name="currency" />
                                </flux:field>
                            </div>
                        @endif

                        @if ($discount_type === 'trial_extension')
                            <div>
                                <flux:field>
                                    <flux:label>Trial Extension Days *</flux:label>
                                    <flux:input wire:model="trial_extension_days" type="number" min="1" placeholder="7" />
                                    <flux:error name="trial_extension_days" />
                                </flux:field>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Usage Limits --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Usage Limits</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>Max Total Redemptions</flux:label>
                                <flux:input wire:model="max_redemptions" type="number" min="1" placeholder="Unlimited" />
                                <flux:error name="max_redemptions" />
                                <flux:text variant="muted" class="text-xs mt-1">
                                    Leave empty for unlimited
                                </flux:text>
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Max Per User *</flux:label>
                                <flux:input wire:model="max_redemptions_per_user" type="number" min="1" placeholder="1" />
                                <flux:error name="max_redemptions_per_user" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Minimum Order Amount</flux:label>
                                <flux:input wire:model="minimum_order_amount" type="number" step="0.01" min="0" placeholder="No minimum" />
                                <flux:error name="minimum_order_amount" />
                            </flux:field>
                        </div>
                    </div>

                    <div class="mt-4">
                        <flux:field>
                            <div class="flex items-center gap-3">
                                <flux:switch wire:model="first_time_customer_only" />
                                <flux:label>First-time customers only</flux:label>
                            </div>
                            <flux:text variant="muted" class="text-xs mt-1">
                                Only users with no previous orders can use this coupon
                            </flux:text>
                        </flux:field>
                    </div>
                </div>

                {{-- Validity Period --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Validity Period</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>Valid From</flux:label>
                                <flux:input wire:model="valid_from" type="date" />
                                <flux:error name="valid_from" />
                                <flux:text variant="muted" class="text-xs mt-1">
                                    Leave empty for immediate start
                                </flux:text>
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Valid Until</flux:label>
                                <flux:input wire:model="valid_until" type="date" />
                                <flux:error name="valid_until" />
                                <flux:text variant="muted" class="text-xs mt-1">
                                    Leave empty for no expiration
                                </flux:text>
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- Plan Restrictions --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Plan Restrictions</flux:heading>
                    <flux:field>
                        <flux:label>Applicable Plans</flux:label>
                        @if ($plans->count() > 0)
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-2">
                                @foreach ($plans as $plan)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <flux:checkbox
                                            wire:model="selected_plans"
                                            value="{{ $plan->id }}"
                                        />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $plan->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <flux:text variant="muted" class="text-sm">
                                No active plans available
                            </flux:text>
                        @endif
                        <flux:error name="selected_plans" />
                        <flux:text variant="muted" class="text-xs mt-2">
                            Leave unchecked to apply to all plans
                        </flux:text>
                    </flux:field>
                </div>

                {{-- Status --}}
                <div>
                    <flux:field>
                        <div class="flex items-center gap-3">
                            <flux:switch wire:model="is_active" />
                            <flux:label>Active Coupon</flux:label>
                        </div>
                        <flux:text variant="muted" class="text-xs mt-1">
                            Only active coupons can be used during checkout
                        </flux:text>
                        <flux:error name="is_active" />
                    </flux:field>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button wire:click="closeModal" type="button" variant="ghost">
                    Cancel
                </flux:button>

                <flux:button type="submit" variant="primary">
                    {{ $mode === 'create' ? 'Create Coupon' : 'Update Coupon' }}
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
