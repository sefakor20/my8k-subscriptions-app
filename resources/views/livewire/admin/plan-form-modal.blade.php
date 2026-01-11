<flux:modal wire:model.self="show" @close="closeModal" class="max-w-4xl">
    <div class="p-6">
        {{-- Header --}}
        <div class="mb-6">
            <flux:heading size="lg">{{ $mode === 'create' ? 'Create New Plan' : 'Edit Plan' }}</flux:heading>
            <flux:text variant="muted" class="mt-1">
                {{ $mode === 'create' ? 'Add a new IPTV subscription plan' : 'Update plan details' }}
            </flux:text>
        </div>

        {{-- Form --}}
        <form wire:submit="save">
            <div class="space-y-6">
                {{-- Basic Information --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Basic Information</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <flux:field>
                                <flux:label>Plan Name *</flux:label>
                                <flux:input wire:model="name" type="text" placeholder="e.g., Premium IPTV" />
                                <flux:error name="name" />
                            </flux:field>
                        </div>

                        <div class="md:col-span-2">
                            <flux:field>
                                <flux:label>Slug *</flux:label>
                                <flux:input wire:model="slug" type="text" placeholder="e.g., premium-iptv" />
                                <flux:error name="slug" />
                                <flux:text variant="muted" class="text-xs mt-1">
                                    URL-friendly identifier (lowercase, hyphens only)
                                </flux:text>
                            </flux:field>
                        </div>

                        <div class="md:col-span-2">
                            <flux:field>
                                <flux:label>Description</flux:label>
                                <flux:textarea wire:model="description" rows="3" placeholder="Plan description..." />
                                <flux:error name="description" />
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Pricing</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>Price *</flux:label>
                                <flux:input wire:model="price" type="number" step="0.01" min="0" placeholder="29.99" />
                                <flux:error name="price" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Currency *</flux:label>
                                <flux:select wire:model="currency">
                                    @foreach ($currencies as $curr)
                                        <option value="{{ $curr['value'] }}">{{ $curr['label'] }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="currency" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Billing Interval *</flux:label>
                                <flux:select wire:model="billing_interval">
                                    @foreach ($billingIntervals as $interval)
                                        <option value="{{ $interval['value'] }}">{{ $interval['label'] }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="billing_interval" />
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- Plan Details --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Plan Details</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>Duration (Days) *</flux:label>
                                <flux:input wire:model="duration_days" type="number" min="1" placeholder="30" />
                                <flux:error name="duration_days" />
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>Max Devices *</flux:label>
                                <flux:input wire:model="max_devices" type="number" min="1" placeholder="1" />
                                <flux:error name="max_devices" />
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- Integration IDs --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Integration IDs</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>WooCommerce Product ID *</flux:label>
                                <flux:input wire:model="woocommerce_id" type="text" placeholder="12345" />
                                <flux:error name="woocommerce_id" />
                                <flux:text variant="muted" class="text-xs mt-1">
                                    Must match WooCommerce product ID
                                </flux:text>
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>My8K Plan Code *</flux:label>
                                <flux:input wire:model="my8k_plan_code" type="text" placeholder="PLAN_BASIC_M" />
                                <flux:error name="my8k_plan_code" />
                                <flux:text variant="muted" class="text-xs mt-1">
                                    My8K API plan/bouquet code
                                </flux:text>
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- Features (JSON) --}}
                <div>
                    <flux:heading size="sm" class="mb-4">Features (JSON)</flux:heading>
                    <flux:field>
                        <flux:label>Features Array</flux:label>
                        <flux:textarea wire:model="features" rows="6" placeholder='["HD Quality", "24/7 Support", "Multi-device"]' />
                        <flux:error name="features" />
                        <flux:text variant="muted" class="text-xs mt-1">
                            JSON array of feature strings
                        </flux:text>
                    </flux:field>
                </div>

                {{-- Status --}}
                <div>
                    <flux:field>
                        <div class="flex items-center gap-3">
                            <flux:switch wire:model="is_active" />
                            <flux:label>Active Plan</flux:label>
                        </div>
                        <flux:text variant="muted" class="text-xs mt-1">
                            Only active plans are available for new subscriptions
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
                    {{ $mode === 'create' ? 'Create Plan' : 'Update Plan' }}
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
