<section id="features" class="py-24 bg-motv-bg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">
                Why Choose <span class="text-motv-primary">MoTv</span>?
            </h2>
            <p class="text-motv-neutral text-lg max-w-2xl mx-auto">
                Experience premium entertainment with cutting-edge technology and unmatched reliability.
            </p>
        </div>

        {{-- Features Grid --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach([
                [
                    'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
                    'title' => '25,000+ Live Channels',
                    'description' => 'Access thousands of live TV channels from around the world in one place.',
                    'stat' => '25K+',
                ],
                [
                    'icon' => 'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z',
                    'title' => '50,000+ Movies',
                    'description' => 'Massive library of movies including latest releases and timeless classics.',
                    'stat' => '50K+',
                ],
                [
                    'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                    'title' => 'HD & 4K Quality',
                    'description' => 'Crystal clear streaming in HD and 4K Ultra HD for the best viewing experience.',
                    'stat' => '4K',
                ],
                [
                    'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
                    'title' => 'Multi-Device Support',
                    'description' => 'Watch on Smart TV, mobile, tablet, or any device with internet connection.',
                    'stat' => '5+',
                ],
            ] as $feature)
                <div class="group bg-white/5 border border-white/10 rounded-2xl p-6 hover:border-motv-primary/30 hover:bg-white/[0.07] transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-motv-primary/10 rounded-xl group-hover:bg-motv-primary/20 transition-colors">
                            <svg class="size-6 text-motv-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}" />
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-motv-secondary">{{ $feature['stat'] }}</span>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">{{ $feature['title'] }}</h3>
                    <p class="text-motv-text-muted text-sm">{{ $feature['description'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Additional Features Row --}}
        <div class="mt-12 grid md:grid-cols-3 gap-6">
            @foreach([
                [
                    'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                    'title' => 'Anti-Freeze Technology',
                    'description' => 'Our advanced servers ensure smooth, buffer-free streaming 24/7.',
                ],
                [
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'title' => '7-Day Catch Up',
                    'description' => 'Missed your favorite show? Watch it anytime within 7 days.',
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'title' => 'Electronic Program Guide',
                    'description' => 'Easy-to-use EPG to browse and schedule your viewing.',
                ],
            ] as $feature)
                <div class="flex items-start gap-4 p-6 bg-white/5 rounded-2xl border border-white/10">
                    <div class="p-2 bg-motv-secondary/10 rounded-lg shrink-0">
                        <svg class="size-5 text-motv-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-1">{{ $feature['title'] }}</h4>
                        <p class="text-motv-text-muted text-sm">{{ $feature['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
