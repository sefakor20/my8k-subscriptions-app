@props(['plan'])

@php
    $features = $plan->features ?? [];
    $featureLabels = [
        'channels' => 'Live Channels',
        'vod_movies' => 'Movies',
        'vod_series' => 'TV Series',
        'hd_quality' => 'HD Quality',
        '4k_quality' => '4K Ultra HD',
        'epg' => 'TV Guide (EPG)',
        'catch_up' => 'Catch Up TV',
        'anti_freeze' => 'Anti-Freeze Technology',
    ];
    $isPremium = str_contains(strtolower($plan->name), 'premium');
@endphp

<div class="relative bg-gradient-to-b from-white/5 to-transparent border {{ $isPremium ? 'border-motv-primary/50' : 'border-motv-neutral/20' }} rounded-2xl p-8 hover:border-motv-primary/50 transition-all duration-300 group {{ $isPremium ? 'ring-2 ring-motv-primary/20' : '' }}">
    {{-- Popular Badge (for Premium) --}}
    @if($isPremium)
        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
            <span class="bg-motv-secondary text-motv-bg text-xs font-bold px-4 py-1 rounded-full uppercase tracking-wide">
                Most Popular
            </span>
        </div>
    @endif

    {{-- Plan Name --}}
    <h3 class="text-xl font-bold text-white mb-2">{{ $plan->name }}</h3>

    @if($plan->description)
        <p class="text-motv-text-muted text-sm mb-6 line-clamp-2">{{ $plan->description }}</p>
    @else
        <div class="mb-6"></div>
    @endif

    {{-- Price --}}
    <div class="mb-6">
        <span class="text-4xl font-bold text-white">{{ $plan->formattedPrice() }}</span>
        <span class="text-motv-neutral">/ {{ $plan->billing_interval->label() }}</span>
    </div>

    {{-- Devices --}}
    @if($plan->max_devices)
        <div class="flex items-center gap-2 mb-6 pb-6 border-b border-white/10">
            <svg class="size-5 text-motv-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span class="text-white font-medium">{{ $plan->max_devices }} Device{{ $plan->max_devices > 1 ? 's' : '' }} Simultaneous</span>
        </div>
    @endif

    {{-- Features --}}
    <ul class="space-y-3 mb-8">
        @foreach($features as $key => $value)
            @if(isset($featureLabels[$key]))
                <li class="flex items-center gap-3">
                    @if(is_bool($value))
                        @if($value)
                            <svg class="size-5 text-motv-secondary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-motv-neutral">{{ $featureLabels[$key] }}</span>
                        @else
                            <svg class="size-5 text-red-500/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="text-motv-neutral/50 line-through">{{ $featureLabels[$key] }}</span>
                        @endif
                    @else
                        <svg class="size-5 text-motv-secondary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-motv-neutral">{{ number_format($value) }}+ {{ $featureLabels[$key] }}</span>
                    @endif
                </li>
            @endif
        @endforeach
    </ul>

    {{-- CTA Button --}}
    @auth
        <a href="{{ route('checkout.gateway', $plan) }}"
           class="block w-full text-center px-6 py-3.5 {{ $isPremium ? 'bg-motv-primary hover:bg-motv-primary-hover' : 'bg-white/10 hover:bg-white/20 border border-white/20' }} text-white font-semibold rounded-full transition-all duration-200">
            Get Started
        </a>
    @else
        <a href="{{ route('register') }}"
           class="block w-full text-center px-6 py-3.5 {{ $isPremium ? 'bg-motv-primary hover:bg-motv-primary-hover' : 'bg-white/10 hover:bg-white/20 border border-white/20' }} text-white font-semibold rounded-full transition-all duration-200">
            Get Started
        </a>
    @endauth
</div>
