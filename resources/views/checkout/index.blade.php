<x-layouts.app>
    <div class="max-w-4xl mx-auto py-8 px-4">
        {{-- Page Header --}}
        <div class="mb-8 text-center">
            <flux:heading size="xl" class="font-bold">Choose a Plan</flux:heading>
            <flux:text variant="muted" class="mt-2">
                Select the subscription plan that best fits your needs
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

        {{-- Plans Grid --}}
        @if ($plans->isEmpty())
            <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
                <flux:icon.inbox class="w-16 h-16 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
                <flux:heading size="lg" class="mb-2">No plans available</flux:heading>
                <flux:text variant="muted">
                    There are no subscription plans available at this time.
                </flux:text>
            </div>
        @else
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($plans as $plan)
                    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 flex flex-col">
                        <div class="flex-1">
                            <flux:heading size="lg" class="font-bold">{{ $plan->name }}</flux:heading>

                            @if ($plan->description)
                                <flux:text variant="muted" class="mt-2">
                                    {{ $plan->description }}
                                </flux:text>
                            @endif

                            <div class="mt-4">
                                <span class="text-3xl font-bold text-zinc-900 dark:text-white">
                                    {{ $plan->formattedPrice() }}
                                </span>
                                <span class="text-zinc-500 dark:text-zinc-400">
                                    / {{ $plan->billing_interval->label() }}
                                </span>
                            </div>

                            @if ($plan->features)
                                <ul class="mt-4 space-y-2">
                                    @foreach ($plan->features as $feature)
                                        <li class="flex items-center gap-2">
                                            <flux:icon.check class="w-4 h-4 text-green-500" />
                                            <flux:text>{{ $feature }}</flux:text>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="mt-4">
                                <flux:text variant="muted" class="text-sm">
                                    Duration: {{ $plan->duration_days }} days
                                </flux:text>
                                @if ($plan->max_devices)
                                    <flux:text variant="muted" class="text-sm">
                                        Max devices: {{ $plan->max_devices }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6">
                            <flux:button
                                variant="primary"
                                class="w-full"
                                href="{{ route('checkout.gateway', $plan) }}"
                            >
                                Select Plan
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
