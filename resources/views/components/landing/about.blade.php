<section id="about" class="py-24 bg-motv-bg-overlay">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            {{-- Content Side --}}
            <div>
                <span class="inline-block text-motv-primary font-semibold text-sm uppercase tracking-wider mb-4">About Us</span>
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-6">
                    Your Gateway to <span class="text-motv-primary">Premium</span> Entertainment
                </h2>
                <p class="text-motv-neutral text-lg mb-8 leading-relaxed">
                    MoTv is a leading IPTV service provider dedicated to delivering exceptional streaming experiences to viewers worldwide. With years of expertise in the industry, we've built a platform that combines reliability, quality, and variety.
                </p>

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-6 mb-8">
                    @foreach([
                        ['value' => '50K+', 'label' => 'Active Users'],
                        ['value' => '99.9%', 'label' => 'Uptime'],
                        ['value' => '24/7', 'label' => 'Support'],
                    ] as $stat)
                        <div class="text-center">
                            <div class="text-2xl md:text-3xl font-bold text-motv-secondary mb-1">{{ $stat['value'] }}</div>
                            <div class="text-motv-text-muted text-sm">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- CTA --}}
                <a href="#pricing"
                   class="inline-flex items-center gap-2 text-motv-primary hover:text-motv-primary-hover font-semibold transition-colors">
                    Explore Our Plans
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>

            {{-- Visual Side --}}
            <div class="relative">
                <div class="bg-gradient-to-br from-motv-primary/20 to-motv-secondary/10 rounded-3xl p-8 lg:p-12">
                    {{-- Feature Cards --}}
                    <div class="space-y-4">
                        @foreach([
                            [
                                'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                                'title' => 'Global Coverage',
                                'description' => 'Channels from 100+ countries worldwide',
                            ],
                            [
                                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                                'title' => 'Secure & Private',
                                'description' => 'Your data is protected with encryption',
                            ],
                            [
                                'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
                                'title' => 'Customer First',
                                'description' => 'Dedicated support team at your service',
                            ],
                        ] as $card)
                            <div class="bg-motv-bg/80 backdrop-blur-sm rounded-xl p-5 flex items-start gap-4">
                                <div class="p-2 bg-motv-primary/20 rounded-lg shrink-0">
                                    <svg class="size-5 text-motv-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-white font-semibold mb-1">{{ $card['title'] }}</h4>
                                    <p class="text-motv-text-muted text-sm">{{ $card['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Decorative Elements --}}
                <div class="absolute -top-4 -right-4 w-24 h-24 bg-motv-primary/20 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-motv-secondary/10 rounded-full blur-2xl"></div>
            </div>
        </div>
    </div>
</section>
