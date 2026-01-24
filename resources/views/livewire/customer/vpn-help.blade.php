<div class="w-full">
    {{-- Page Header --}}
    <div class="mb-8">
        <flux:heading size="xl" class="font-bold">VPN Troubleshooting Guide</flux:heading>
        <flux:text variant="muted" class="mt-2">
            Solutions for connection issues when using VPN applications
        </flux:text>
    </div>

    {{-- Introduction --}}
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-6">
        <div class="flex gap-3">
            <flux:icon.exclamation-triangle class="size-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
            <div>
                <flux:heading size="sm" class="text-amber-800 dark:text-amber-200 mb-1">Why am I seeing connection errors?</flux:heading>
                <flux:text class="text-amber-700 dark:text-amber-300 text-sm">
                    If you're using a VPN application (like NordVPN, Surfshark, ExpressVPN, etc.), you may experience 403 errors or connection issues. This happens because some VPN IP addresses are blocked by our service for security reasons. Choose one of the solutions below to resolve this.
                </flux:text>
            </div>
        </div>
    </div>

    {{-- Solutions Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Option 1: Share IP for Whitelisting --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-start gap-4">
                <div class="flex items-center justify-center size-10 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 shrink-0">
                    <flux:icon.shield-check class="size-5" />
                </div>
                <div class="flex-1">
                    <flux:heading size="base" class="mb-2">Option 1: Request IP Whitelisting</flux:heading>
                    <flux:text variant="muted" class="text-sm mb-4">
                        Share the IP address displayed on your VPN application when connected to any country. Submit a support ticket with your VPN IP, and we will whitelist it for you.
                    </flux:text>
                    <flux:button href="{{ route('support.my-tickets') }}" variant="subtle" size="sm" icon="ticket">
                        Open Support Ticket
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Option 2: Find Your IP --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-start gap-4">
                <div class="flex items-center justify-center size-10 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 shrink-0">
                    <flux:icon.globe-alt class="size-5" />
                </div>
                <div class="flex-1">
                    <flux:heading size="base" class="mb-2">Option 2: Find Your VPN IP Address</flux:heading>
                    <flux:text variant="muted" class="text-sm mb-4">
                        If your VPN app doesn't show the IP address, you can find it by visiting the link below on your TV or box via browser while connected to your VPN.
                    </flux:text>
                    <flux:button href="http://showmyip.com" target="_blank" variant="subtle" size="sm" icon="arrow-top-right-on-square">
                        Visit showmyip.com
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Option 3: CLOUD PRO App --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-start gap-4">
                <div class="flex items-center justify-center size-10 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 shrink-0">
                    <flux:icon.device-phone-mobile class="size-5" />
                </div>
                <div class="flex-1">
                    <flux:heading size="base" class="mb-2">Option 3: Use CLOUD PRO App (Android)</flux:heading>
                    <flux:text variant="muted" class="text-sm mb-4">
                        For Android devices, we recommend the CLOUD PRO app with All 8K player. It's configured to use a dedicated domain that bypasses blocking rules for users who connect through VPNs such as NordVPN, Surfshark, and ExpressVPN.
                    </flux:text>
                </div>
            </div>
        </div>

        {{-- Option 4: Spain Users --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-start gap-4">
                <div class="flex items-center justify-center size-10 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 shrink-0">
                    <flux:icon.flag class="size-5" />
                </div>
                <div class="flex-1">
                    <flux:heading size="base" class="mb-2">Option 4: VPN Recommended for Spain</flux:heading>
                    <flux:text variant="muted" class="text-sm mb-4">
                        We recommend that all customers in Spain use a VPN application to help avoid potential issues. Surfshark is our recommended VPN provider.
                    </flux:text>
                    <div class="flex flex-wrap gap-3">
                        <flux:button href="https://surfshark.com/pricing" target="_blank" variant="subtle" size="sm" icon="arrow-top-right-on-square">
                            Get Surfshark VPN
                        </flux:button>
                        <flux:button href="https://www.youtube.com/watch?v=Ok9_UZsVqgU" target="_blank" variant="subtle" size="sm" icon="play">
                            Installation Guide
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Support Response Notice --}}
    <div class="bg-zinc-100 dark:bg-zinc-800 rounded-lg p-4">
        <div class="flex gap-3">
            <flux:icon.clock class="size-5 text-zinc-500 dark:text-zinc-400 shrink-0 mt-0.5" />
            <div>
                <flux:heading size="sm" class="mb-1">Support Response Time</flux:heading>
                <flux:text variant="muted" class="text-sm">
                    If you submit a support ticket for IP whitelisting, please allow 2-5 business days for a response. Send your message with a clear and detailed explanation, and avoid sending repeated messages about the same request as this may delay the response process.
                </flux:text>
            </div>
        </div>
    </div>
</div>
