<section class="py-24 bg-motv-bg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">
                The MoTv <span class="text-motv-secondary">Advantage</span>
            </h2>
            <p class="text-motv-neutral text-lg max-w-2xl mx-auto">
                We go above and beyond to ensure your streaming experience is nothing short of exceptional.
            </p>
        </div>

        {{-- Benefits Grid --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach([
                [
                    'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                    'title' => 'Lightning Fast Servers',
                    'description' => 'Our globally distributed servers ensure minimal latency and maximum speed for buffer-free streaming.',
                    'highlight' => 'motv-primary',
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'title' => 'Regular Updates',
                    'description' => 'Our content library is updated daily with new channels, movies, and series from around the world.',
                    'highlight' => 'motv-secondary',
                ],
                [
                    'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
                    'title' => '24/7 Expert Support',
                    'description' => 'Our dedicated support team is available around the clock to help with any questions or issues.',
                    'highlight' => 'motv-primary',
                ],
                [
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'title' => 'Affordable Pricing',
                    'description' => 'Premium entertainment at competitive prices with flexible plans to suit every budget.',
                    'highlight' => 'motv-secondary',
                ],
                [
                    'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
                    'title' => 'Easy Setup',
                    'description' => 'Get started in minutes with our simple setup guides for all major devices and platforms.',
                    'highlight' => 'motv-primary',
                ],
                [
                    'icon' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z',
                    'title' => 'Cloud DVR',
                    'description' => 'Record your favorite shows and watch them later at your convenience.',
                    'highlight' => 'motv-secondary',
                ],
            ] as $benefit)
                <div class="group relative bg-white/5 border border-white/10 rounded-2xl p-8 hover:border-{{ $benefit['highlight'] }}/30 transition-all duration-300 overflow-hidden">
                    {{-- Background Glow --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-{{ $benefit['highlight'] }}/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                    <div class="relative">
                        <div class="p-3 bg-{{ $benefit['highlight'] }}/10 rounded-xl w-fit mb-6 group-hover:bg-{{ $benefit['highlight'] }}/20 transition-colors">
                            <svg class="size-6 text-{{ $benefit['highlight'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $benefit['icon'] }}" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-3">{{ $benefit['title'] }}</h3>
                        <p class="text-motv-text-muted">{{ $benefit['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
