<section id="home" class="relative min-h-screen flex items-center pt-20">
    {{-- Background with Gradient Overlay --}}
    <div class="absolute inset-0 bg-gradient-to-b from-motv-bg via-motv-bg/95 to-motv-bg-overlay z-0"></div>

    {{-- Decorative Elements --}}
    <div class="absolute inset-0 z-0 overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-motv-primary/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-motv-secondary/5 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center max-w-4xl mx-auto">
            {{-- Main Headline --}}
            <h1 class="text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold leading-tight mb-6">
                <span class="text-white">Unlimited Entertainment</span>
                <br />
                <span class="text-motv-primary">Anytime, Anywhere</span>
            </h1>

            {{-- Subheading --}}
            <p class="text-lg md:text-xl lg:text-2xl text-motv-neutral mb-10 max-w-2xl mx-auto leading-relaxed">
                Stream 25,000+ live channels, 50,000+ movies, and 15,000+ TV series in stunning HD & 4K quality on any device.
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="#pricing"
                   class="inline-flex items-center justify-center px-8 py-4 bg-motv-primary hover:bg-motv-primary-hover text-white font-semibold rounded-full text-lg transition-all duration-200 shadow-lg shadow-motv-primary/25">
                    View Plans
                    <svg class="ml-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
                <a href="#features"
                   class="inline-flex items-center justify-center px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-full text-lg transition-all duration-200 border border-white/20">
                    Learn More
                </a>
            </div>

            {{-- Feature Badges --}}
            <div class="flex flex-wrap justify-center gap-3 md:gap-4">
                @foreach([
                    ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'HD & 4K Quality'],
                    ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'text' => 'No Buffering'],
                    ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'text' => 'Anti-Freeze Tech'],
                    ['icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z', 'text' => '24/7 Support'],
                    ['icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'text' => 'Multi-Device'],
                ] as $feature)
                    <div class="flex items-center gap-2 bg-white/5 border border-motv-neutral/20 rounded-full px-4 py-2">
                        <svg class="size-5 text-motv-secondary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}" />
                        </svg>
                        <span class="text-motv-neutral text-sm font-medium">{{ $feature['text'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Scroll Indicator --}}
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 animate-bounce">
        <svg class="size-6 text-motv-neutral" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
    </div>
</section>
