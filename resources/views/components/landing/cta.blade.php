<section class="py-24 bg-motv-bg relative overflow-hidden">
    {{-- Background Elements --}}
    <div class="absolute inset-0">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-motv-primary/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-motv-secondary/10 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="bg-gradient-to-r from-motv-primary/20 via-motv-bg-overlay to-motv-secondary/10 rounded-3xl p-12 md:p-16 border border-white/10">
            <div class="max-w-3xl mx-auto text-center">
                {{-- Badge --}}
                <span class="inline-flex items-center gap-2 bg-motv-secondary/10 text-motv-secondary text-sm font-semibold px-4 py-1.5 rounded-full mb-6">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Start Streaming Today
                </span>

                {{-- Headline --}}
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-6">
                    Ready to Transform Your <span class="text-motv-primary">Entertainment</span> Experience?
                </h2>

                {{-- Description --}}
                <p class="text-motv-neutral text-lg mb-10 max-w-2xl mx-auto">
                    Join thousands of satisfied customers enjoying premium streaming. No contracts, cancel anytime.
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#pricing"
                       class="inline-flex items-center justify-center px-8 py-4 bg-motv-primary hover:bg-motv-primary-hover text-white font-semibold rounded-full text-lg transition-all duration-200 shadow-lg shadow-motv-primary/25">
                        Get Started Now
                        <svg class="ml-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    @guest
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center justify-center px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-full text-lg transition-all duration-200 border border-white/20">
                            Create Free Account
                        </a>
                    @endguest
                </div>

                {{-- Trust Badges --}}
                <div class="mt-12 flex flex-wrap items-center justify-center gap-6 text-motv-text-muted text-sm">
                    <div class="flex items-center gap-2">
                        <svg class="size-5 text-motv-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>30-Day Money Back</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="size-5 text-motv-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>Secure Payment</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="size-5 text-motv-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
