<section id="pricing" class="py-24 bg-motv-bg-overlay">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">
                Choose Your <span class="text-motv-primary">Perfect Plan</span>
            </h2>
            <p class="text-motv-neutral text-lg max-w-2xl mx-auto">
                Flexible pricing options to suit every viewing preference. Cancel anytime.
            </p>
        </div>

        {{-- Interval Tabs --}}
        <div class="flex justify-center mb-12">
            <div class="inline-flex bg-white/5 rounded-full p-1.5 border border-white/10">
                @foreach($this->intervals as $interval)
                    <button wire:click="setInterval('{{ $interval->value }}')"
                            wire:key="interval-{{ $interval->value }}"
                            class="px-6 py-2.5 rounded-full text-sm font-semibold transition-all duration-200
                                   {{ $selectedInterval === $interval->value
                                      ? 'bg-motv-primary text-white shadow-lg shadow-motv-primary/25'
                                      : 'text-motv-neutral hover:text-white' }}">
                        {{ $interval->label() }}
                        @if($interval->value === 'yearly')
                            <span class="ml-1 text-xs text-motv-secondary">Save 30%</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Plans Grid --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8" wire:key="plans-{{ $selectedInterval }}">
            @forelse($this->plans as $plan)
                <x-landing.pricing-card :plan="$plan" wire:key="plan-{{ $plan->id }}" />
            @empty
                <div class="col-span-full text-center py-12">
                    <div class="bg-white/5 rounded-2xl p-12 border border-white/10">
                        <svg class="size-16 text-motv-neutral mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="text-motv-neutral text-lg">No plans available for this billing period.</p>
                        <p class="text-motv-text-muted text-sm mt-2">Please check back later or try a different billing interval.</p>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Money Back Guarantee --}}
        <div class="mt-12 text-center">
            <div class="inline-flex items-center gap-3 bg-motv-secondary/10 border border-motv-secondary/20 rounded-full px-6 py-3">
                <svg class="size-6 text-motv-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="text-motv-secondary font-medium">30-Day Money Back Guarantee</span>
            </div>
        </div>
    </div>
</section>
