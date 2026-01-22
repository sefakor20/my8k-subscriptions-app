<section class="py-24 bg-motv-bg-overlay">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-12">
            <span class="text-motv-primary font-semibold text-sm uppercase tracking-wider">
                Enjoy Your Time
            </span>
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mt-4">
                Enjoy Watching Now Movies, Series,<br class="hidden md:block" />
                Shows, Anime and More
            </h2>
        </div>

        {{-- Swiper Carousel --}}
        <div class="relative" x-data x-init="
            new Swiper($refs.swiper, {
                slidesPerView: 2,
                spaceBetween: 16,
                loop: true,
                autoplay: {
                    delay: 4000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                },
                navigation: {
                    nextEl: $refs.next,
                    prevEl: $refs.prev
                },
                breakpoints: {
                    640: { slidesPerView: 3 },
                    768: { slidesPerView: 4 },
                    1024: { slidesPerView: 5 },
                    1280: { slidesPerView: 6 }
                }
            })
        ">
            {{-- Navigation Arrows --}}
            <button x-ref="prev"
                    class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-10 p-3 bg-motv-bg/80 hover:bg-motv-primary rounded-full text-white transition-colors">
                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button x-ref="next"
                    class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-10 p-3 bg-motv-bg/80 hover:bg-motv-primary rounded-full text-white transition-colors">
                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            {{-- Swiper Container --}}
            <div class="swiper" x-ref="swiper">
                <div class="swiper-wrapper">
                    @foreach(range(1, 8) as $i)
                        <div class="swiper-slide">
                            <img src="https://picsum.photos/seed/movie{{ $i }}/300/450"
                                 alt="Movie poster {{ $i }}"
                                 class="w-full aspect-[2/3] object-cover rounded-2xl hover:scale-105 transition-transform duration-300" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-12">
            <p class="text-motv-neutral text-lg mb-6">
                Everything you need to enjoy your time
            </p>
            <a href="#pricing"
               class="inline-flex items-center justify-center px-8 py-4 bg-motv-primary hover:bg-motv-primary-hover text-white font-semibold rounded-full text-lg transition-all duration-200 shadow-lg shadow-motv-primary/25">
                Get It Now
            </a>
        </div>
    </div>
</section>
