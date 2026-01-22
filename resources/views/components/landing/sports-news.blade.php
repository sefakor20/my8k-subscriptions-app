<section class="py-24 bg-motv-bg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-6 lg:gap-8">

            {{-- Left: Sports Section --}}
            <div class="relative bg-zinc-900/80 rounded-2xl p-8 lg:p-10 overflow-hidden min-h-[550px] flex flex-col">
                {{-- Dot grid pattern --}}
                <div class="absolute inset-0 pointer-events-none">
                    <div class="absolute top-6 right-6 grid grid-cols-5 gap-8">
                        @foreach(range(1, 20) as $dot)
                            <div class="w-1 h-1 bg-motv-neutral/30 rounded-full"></div>
                        @endforeach
                    </div>
                    <div class="absolute bottom-6 left-6 grid grid-cols-5 gap-8">
                        @foreach(range(1, 20) as $dot)
                            <div class="w-1 h-1 bg-motv-neutral/30 rounded-full"></div>
                        @endforeach
                    </div>
                </div>

                {{-- Content (top-left) --}}
                <div class="relative z-10 mb-8">
                    <h3 class="text-3xl lg:text-4xl font-bold text-white italic mb-6">
                        Don't Miss Your<br />Favorite Match
                    </h3>
                    <a href="#pricing"
                       class="inline-flex items-center justify-center px-6 py-3 bg-motv-secondary text-motv-bg font-semibold rounded-lg transition-all duration-200 hover:bg-motv-secondary/90">
                        View All Fotball Leagues
                    </a>
                </div>

                {{-- Puzzle Image Grid (bottom, offset right) --}}
                <div class="relative z-10 mt-auto ml-auto max-w-[90%] grid grid-cols-5 gap-1.5">
                    {{-- Row 1 --}}
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                    <div class="aspect-square overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/sunset-stadium/200/200" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="col-span-2 row-span-2 overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/stadium-lights/400/400" alt="" class="w-full h-full object-cover" />
                    </div>
                    {{-- Row 2 --}}
                    <div class="aspect-square overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/soccer-net/200/200" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="col-span-2 row-span-2 overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/soccer-tackle/400/400" alt="" class="w-full h-full object-cover" />
                    </div>
                    {{-- Row 3 --}}
                    <div class="aspect-square overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/soccer-ball/200/200" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                    {{-- Row 4 --}}
                    <div class="aspect-square overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/grass/200/200" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="aspect-square overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/cleats/200/200" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="aspect-square overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/field/200/200" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                </div>
            </div>

            {{-- Right: News Section --}}
            <div class="relative bg-zinc-900/80 rounded-2xl p-8 lg:p-10 overflow-hidden min-h-[550px] flex flex-col">
                {{-- Dot grid pattern --}}
                <div class="absolute inset-0 pointer-events-none">
                    <div class="absolute top-6 left-6 grid grid-cols-5 gap-8">
                        @foreach(range(1, 20) as $dot)
                            <div class="w-1 h-1 bg-motv-neutral/30 rounded-full"></div>
                        @endforeach
                    </div>
                    <div class="absolute bottom-6 right-6 grid grid-cols-5 gap-8">
                        @foreach(range(1, 20) as $dot)
                            <div class="w-1 h-1 bg-motv-neutral/30 rounded-full"></div>
                        @endforeach
                    </div>
                </div>

                {{-- Puzzle Image Grid (top) --}}
                <div class="relative z-10 max-w-[90%] grid grid-cols-5 gap-1.5 mb-8">
                    {{-- Row 1 --}}
                    <div class="aspect-square overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/news-screen/200/200" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="col-span-2 row-span-2 overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/breaking-news/400/400" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="aspect-square overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/world-map/200/200" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                    {{-- Row 2 --}}
                    <div class="aspect-square overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/globe/200/200" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                    {{-- Row 3 --}}
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                    <div class="col-span-2 row-span-2 overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/newsroom-desk/400/400" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="aspect-square overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/anchor/200/200" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="aspect-square overflow-hidden rounded">
                        <img src="https://picsum.photos/seed/studio/200/200" alt="" class="w-full h-full object-cover" />
                    </div>
                    {{-- Row 4 --}}
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                    <div class="aspect-square border border-motv-neutral/20 rounded"></div>
                </div>

                {{-- Content (bottom-left) --}}
                <div class="relative z-10 mt-auto">
                    <h3 class="text-3xl lg:text-4xl font-bold text-white italic mb-6">
                        Don't Miss Any<br />Updated News
                    </h3>
                    <a href="#pricing"
                       class="inline-flex items-center justify-center px-6 py-3 bg-motv-secondary text-motv-bg font-semibold rounded-lg transition-all duration-200 hover:bg-motv-secondary/90">
                        View All News Channel
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>
