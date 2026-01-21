<header class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
        x-data="{ scrolled: false, mobileMenuOpen: false }"
        x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 50 })"
        :class="scrolled ? 'bg-motv-bg/95 backdrop-blur-sm shadow-lg' : 'bg-transparent'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="text-2xl font-bold text-white">Mo<span class="text-motv-primary">Tv</span></span>
            </a>

            {{-- Desktop Navigation --}}
            <nav class="hidden lg:flex items-center gap-8">
                <a href="#home" class="text-motv-neutral hover:text-white transition-colors font-medium">Home</a>
                <a href="#features" class="text-motv-neutral hover:text-white transition-colors font-medium">Features</a>
                <a href="#pricing" class="text-motv-neutral hover:text-white transition-colors font-medium">Pricing Plans</a>
                <a href="#about" class="text-motv-neutral hover:text-white transition-colors font-medium">About</a>
            </nav>

            {{-- Right Side Actions --}}
            <div class="flex items-center gap-4">
                {{-- Phone (hidden on mobile) --}}
                <a href="tel:+1234567890" class="hidden md:flex items-center gap-2 text-motv-neutral hover:text-white transition-colors">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </a>

                {{-- CTA Buttons --}}
                @guest
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex text-motv-neutral hover:text-white transition-colors font-medium">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center px-6 py-2.5 bg-motv-primary hover:bg-motv-primary-hover text-white font-semibold rounded-full transition-all duration-200">
                        Register Now
                    </a>
                @else
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center justify-center px-6 py-2.5 bg-motv-primary hover:bg-motv-primary-hover text-white font-semibold rounded-full transition-all duration-200">
                        Dashboard
                    </a>
                @endguest

                {{-- Mobile Menu Button --}}
                <button type="button"
                        class="lg:hidden text-motv-neutral hover:text-white"
                        @click="mobileMenuOpen = !mobileMenuOpen">
                    <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile Navigation --}}
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-4"
             class="lg:hidden pb-6">
            <nav class="flex flex-col gap-4">
                <a href="#home" class="text-motv-neutral hover:text-white transition-colors font-medium py-2" @click="mobileMenuOpen = false">Home</a>
                <a href="#features" class="text-motv-neutral hover:text-white transition-colors font-medium py-2" @click="mobileMenuOpen = false">Features</a>
                <a href="#pricing" class="text-motv-neutral hover:text-white transition-colors font-medium py-2" @click="mobileMenuOpen = false">Pricing Plans</a>
                <a href="#about" class="text-motv-neutral hover:text-white transition-colors font-medium py-2" @click="mobileMenuOpen = false">About</a>
                @guest
                    <a href="{{ route('login') }}" class="text-motv-neutral hover:text-white transition-colors font-medium py-2">Sign In</a>
                @endguest
            </nav>
        </div>
    </div>
</header>
