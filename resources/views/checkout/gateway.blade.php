<x-layouts.app>
    <div class="max-w-2xl mx-auto py-8 px-4">
        {{-- Page Header --}}
        <div class="mb-8 text-center">
            <flux:heading size="xl" class="font-bold">Select Payment Method</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Choose how you would like to pay for your subscription
            </flux:text>
        </div>

        {{-- Error Message --}}
        @if (session('error'))
            <div class="mb-6">
                <flux:callout variant="danger" icon="exclamation-triangle">
                    {{ session('error') }}
                </flux:callout>
            </div>
        @endif

        {{-- Selected Plan Summary --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <flux:heading size="lg">{{ $plan->name }}</flux:heading>
                    @if ($plan->description)
                        <flux:text variant="muted" class="mt-1">{{ $plan->description }}</flux:text>
                    @endif
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                        {{ $plan->formattedPrice() }}
                    </div>
                    <flux:text variant="muted" class="text-sm">
                        {{ $plan->billing_interval->label() }}
                    </flux:text>
                </div>
            </div>
        </div>

        {{-- Payment Methods --}}
        @if (count($gateways) === 0)
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
                <flux:icon.credit-card class="w-16 h-16 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
                <flux:heading size="lg" class="mb-2">No payment methods available</flux:heading>
                <flux:text variant="muted">
                    Payment methods are not configured. Please contact support.
                </flux:text>
            </div>
        @else
            <form action="{{ route('checkout.initiate') }}" method="POST" x-data="{ selectedGateway: '{{ $gateways[0]->getIdentifier()->value }}' }">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                <div class="space-y-4">
                    @foreach ($gateways as $gateway)
                        <label class="block cursor-pointer">
                            <input
                                type="radio"
                                name="gateway"
                                value="{{ $gateway->getIdentifier()->value }}"
                                class="peer sr-only"
                                x-model="selectedGateway"
                                {{ $loop->first ? 'checked' : '' }}
                            >
                            <div class="bg-white dark:bg-zinc-900 rounded-lg border-2 border-zinc-200 dark:border-zinc-700 p-4 peer-checked:border-blue-500 peer-checked:ring-1 peer-checked:ring-blue-500 transition-all">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0">
                                        @if ($gateway->getIdentifier()->value === 'paystack')
                                            <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-900 rounded-lg flex items-center justify-center">
                                                <flux:icon.credit-card class="w-6 h-6 text-cyan-600 dark:text-cyan-400" />
                                            </div>
                                        @elseif ($gateway->getIdentifier()->value === 'stripe')
                                            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
                                                <flux:icon.credit-card class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                                            </div>
                                        @else
                                            <div class="w-12 h-12 bg-zinc-100 dark:bg-zinc-800 rounded-lg flex items-center justify-center">
                                                <flux:icon.credit-card class="w-6 h-6 text-zinc-600 dark:text-zinc-400" />
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <flux:heading size="base" class="font-semibold">
                                            {{ $gateway->getDisplayName() }}
                                        </flux:heading>
                                        <flux:text variant="muted" class="text-sm">
                                            {{ $gateway->getIdentifier()->description() }}
                                        </flux:text>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="w-5 h-5 rounded-full border-2 border-zinc-300 dark:border-zinc-600 peer-checked:border-blue-500 peer-checked:bg-blue-500 flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-white hidden peer-checked:block"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Coupon Code Input --}}
                <div class="mt-6">
                    <livewire:checkout.coupon-input :plan-id="$plan->id" :gateway="$gateways[0]->getIdentifier()->value" />
                </div>

                <div class="mt-8 flex gap-4">
                    <flux:button variant="ghost" href="{{ route('checkout.index') }}" class="flex-1">
                        Back
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="flex-1">
                        Proceed to Payment
                    </flux:button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>
