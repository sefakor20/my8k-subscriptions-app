<div>
    <flux:modal wire:model="showModal" class="md:w-xl lg:w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Change Subscription Plan</flux:heading>
                @if($this->subscription)
                    <flux:text class="mt-2">
                        Current plan: <strong>{{ $this->subscription->plan->name }}</strong>
                    </flux:text>
                @endif
            </div>

            @if($errorMessage)
                <flux:callout variant="danger">
                    {{ $errorMessage }}
                </flux:callout>
            @endif

            @if($this->subscription)
                {{-- Plan Selection --}}
                <div class="space-y-3">
                    <flux:heading size="sm">Select New Plan</flux:heading>
                    <div class="grid gap-3">
                        @forelse($this->availablePlans as $plan)
                            <label
                                class="flex items-center gap-4 p-4 border rounded-lg cursor-pointer transition-colors
                                    {{ $selectedPlanId === $plan->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300' }}"
                            >
                                <flux:radio
                                    wire:model.live="selectedPlanId"
                                    name="plan"
                                    :value="$plan->id"
                                />
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium">{{ $plan->name }}</span>
                                        <span class="text-sm font-semibold">{{ $plan->formattedPrice() }}/{{ $plan->duration_days }} days</span>
                                    </div>
                                    @if($plan->description)
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $plan->description }}</p>
                                    @endif
                                </div>
                            </label>
                        @empty
                            <flux:text>No other plans available.</flux:text>
                        @endforelse
                    </div>
                </div>

                {{-- Gateway Selection --}}
                @if($this->availableGateways->count() > 1 && $selectedPlanId)
                    <div class="space-y-3">
                        <flux:heading size="sm">Payment Method</flux:heading>
                        <flux:select wire:model.live="selectedGateway">
                            @foreach($this->availableGateways as $gateway)
                                <flux:select.option :value="$gateway->value">
                                    {{ $gateway->label() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @endif

                {{-- Proration Preview --}}
                @if($prorationPreview)
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg space-y-3">
                        <flux:heading size="sm">
                            {{ $prorationPreview['type']->isUpgrade() ? 'Upgrade' : 'Downgrade' }} Summary
                        </flux:heading>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Days remaining in current period</span>
                                <span>{{ $prorationPreview['days_remaining'] }} days</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Unused credit from current plan</span>
                                <span>{{ \Illuminate\Support\Number::currency($prorationPreview['unused_credit'], in: $prorationPreview['currency']) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Prorated cost of new plan</span>
                                <span>{{ \Illuminate\Support\Number::currency($prorationPreview['prorated_cost'], in: $prorationPreview['currency']) }}</span>
                            </div>

                            <flux:separator />

                            @if($prorationPreview['amount_due'] > 0)
                                <div class="flex justify-between font-semibold text-base">
                                    <span>Amount to Pay</span>
                                    <span class="text-green-600 dark:text-green-400">
                                        {{ \Illuminate\Support\Number::currency($prorationPreview['amount_due'], in: $prorationPreview['currency']) }}
                                    </span>
                                </div>
                            @else
                                <div class="flex justify-between font-semibold text-base">
                                    <span>Credit to Apply</span>
                                    <span class="text-blue-600 dark:text-blue-400">
                                        {{ \Illuminate\Support\Number::currency($prorationPreview['credit_to_apply'], in: $prorationPreview['currency']) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Execution Type Selection --}}
                    <div class="space-y-3">
                        <flux:heading size="sm">When to Apply</flux:heading>
                        <div class="space-y-2">
                            <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer
                                {{ $executionType === 'immediate' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                                <flux:radio wire:model.live="executionType" name="execution_type" value="immediate" />
                                <div>
                                    <span class="font-medium">Apply Immediately</span>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        @if($prorationPreview['amount_due'] > 0)
                                            Pay {{ \Illuminate\Support\Number::currency($prorationPreview['amount_due'], in: $prorationPreview['currency']) }} now and switch to the new plan immediately.
                                        @else
                                            Switch to the new plan immediately and receive a credit of {{ \Illuminate\Support\Number::currency($prorationPreview['credit_to_apply'], in: $prorationPreview['currency']) }}.
                                        @endif
                                    </p>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer
                                {{ $executionType === 'scheduled' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                                <flux:radio wire:model.live="executionType" name="execution_type" value="scheduled" />
                                <div>
                                    <span class="font-medium">At Next Renewal</span>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        Keep your current plan until {{ $this->subscription->expires_at->format('M j, Y') }}, then switch to the new plan.
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-4">
                    <flux:button wire:click="closeModal" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button
                        wire:click="initiatePlanChange"
                        variant="primary"
                        :disabled="!$selectedPlanId || $loading"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="initiatePlanChange">
                            @if($prorationPreview && $prorationPreview['amount_due'] > 0 && $executionType === 'immediate')
                                Pay & Switch Plan
                            @elseif($executionType === 'scheduled')
                                Schedule Change
                            @else
                                Switch Plan
                            @endif
                        </span>
                        <span wire:loading wire:target="initiatePlanChange">
                            Processing...
                        </span>
                    </flux:button>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
