<div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
    <flux:heading size="sm" class="font-semibold mb-3">Have a coupon code?</flux:heading>

    @if ($appliedCoupon)
        {{-- Applied Coupon Display --}}
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <flux:icon.check-circle class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <flux:text class="font-semibold text-green-800 dark:text-green-200">
                            {{ $appliedCoupon['code'] }}
                        </flux:text>
                        @if ($appliedCoupon['name'])
                            <flux:text variant="muted" class="text-sm">
                                {{ $appliedCoupon['name'] }}
                            </flux:text>
                        @endif
                        <div class="mt-2 space-y-1">
                            <flux:text class="text-sm text-green-700 dark:text-green-300">
                                {{ $appliedCoupon['formatted_discount'] }} discount applied
                            </flux:text>
                            @if ($appliedCoupon['trial_days'])
                                <flux:text class="text-sm text-green-700 dark:text-green-300">
                                    + {{ $appliedCoupon['trial_days'] }} extra trial days
                                </flux:text>
                            @endif
                            <div class="flex items-center gap-2 text-sm mt-2">
                                <span class="text-zinc-500 dark:text-zinc-400 line-through">
                                    {{ $appliedCoupon['currency'] }} {{ number_format($appliedCoupon['original_amount'], 2) }}
                                </span>
                                <span class="font-semibold text-green-700 dark:text-green-300">
                                    {{ $appliedCoupon['currency'] }} {{ number_format($appliedCoupon['final_amount'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <flux:button
                    variant="ghost"
                    size="sm"
                    wire:click="removeCoupon"
                    class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                >
                    Remove
                </flux:button>
            </div>
        </div>

        {{-- Hidden input for form submission --}}
        <input type="hidden" name="coupon_code" value="{{ $appliedCoupon['code'] }}">
    @else
        {{-- Coupon Input Form --}}
        <div class="flex gap-2">
            <div class="flex-1">
                <flux:input
                    wire:model="couponCode"
                    type="text"
                    placeholder="Enter coupon code"
                    :disabled="$isValidating"
                    wire:keydown.enter.prevent="applyCoupon"
                />
            </div>
            <flux:button
                variant="filled"
                wire:click="applyCoupon"
                wire:loading.attr="disabled"
                wire:target="applyCoupon"
                :disabled="$isValidating"
            >
                <span wire:loading.remove wire:target="applyCoupon">Apply</span>
                <span wire:loading wire:target="applyCoupon">
                    <flux:icon.arrow-path class="w-4 h-4 animate-spin" />
                </span>
            </flux:button>
        </div>

        {{-- Error Message --}}
        @if ($errorMessage)
            <div class="mt-3">
                <flux:text class="text-sm text-red-600 dark:text-red-400">
                    {{ $errorMessage }}
                </flux:text>
            </div>
        @endif
    @endif
</div>
