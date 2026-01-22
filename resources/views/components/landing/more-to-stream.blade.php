<section class="py-24 bg-motv-bg relative overflow-hidden">
    {{-- Decorative shapes (top-left) --}}
    <div class="absolute top-0 left-0 hidden md:block">
        <div class="w-20 h-20 bg-motv-secondary/80 rotate-45 -translate-x-1/2 -translate-y-1/4"></div>
        <div class="w-0 h-0 border-l-[30px] border-l-transparent border-b-[50px] border-b-motv-primary border-r-[30px] border-r-transparent absolute top-12 left-16"></div>
    </div>

    {{-- Decorative grid pattern (right side) --}}
    <div class="absolute right-0 bottom-0 w-64 h-64 opacity-20 hidden lg:block">
        <div class="grid grid-cols-6 gap-4">
            @foreach(range(1, 36) as $dot)
                <div class="w-1.5 h-1.5 bg-motv-neutral/50 rounded-full"></div>
            @endforeach
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            {{-- Left: Image --}}
            <div class="relative">
                <img src="https://picsum.photos/seed/streaming/800/600"
                     alt="People enjoying streaming content"
                     class="w-full rounded-2xl shadow-2xl" />
                {{-- Streaming device overlay --}}
                <div class="absolute -bottom-6 -right-6 bg-motv-bg-overlay p-4 rounded-xl shadow-xl hidden md:block">
                    <div class="w-24 h-16 bg-zinc-800 rounded-lg flex items-center justify-center">
                        <div class="w-4 h-4 rounded-full border-2 border-motv-primary"></div>
                    </div>
                </div>
            </div>

            {{-- Right: Content --}}
            <div class="lg:pl-8">
                <span class="text-motv-primary font-semibold text-sm uppercase tracking-wider">
                    Our Content
                </span>
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mt-4 mb-6">
                    More to Stream
                </h2>
                <p class="text-motv-neutral text-lg mb-8 leading-relaxed">
                    With our large and comprehensive collection of TV channels, never miss your favorite sports games and TV shows. You can be the first to see your new episode.
                </p>

                {{-- Highlighted callout box (muted teal, extended) --}}
                <div class="bg-teal-800/70 rounded-xl p-6 mb-8 lg:-mr-16 xl:-mr-24">
                    <p class="text-white/90 font-medium leading-relaxed">
                        Watch exclusive sports channels every week. Get top-rated TV customer service. Bundle your favorite services.
                    </p>
                </div>

                {{-- Feature list --}}
                <ul class="space-y-4 mb-8">
                    <li class="flex items-center gap-3 text-motv-neutral">
                        <svg class="size-5 text-motv-secondary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>25,000 worldwide channels 4K</span>
                    </li>
                    <li class="flex items-center gap-3 text-motv-neutral">
                        <svg class="size-5 text-motv-secondary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>500K new and old movies</span>
                    </li>
                </ul>

                {{-- CTA Button --}}
                <a href="#pricing"
                   class="inline-flex items-center justify-center px-8 py-4 bg-motv-primary hover:bg-motv-primary-hover text-white font-semibold rounded-lg text-lg transition-all duration-200">
                    View Content List
                </a>
            </div>
        </div>
    </div>
</section>
