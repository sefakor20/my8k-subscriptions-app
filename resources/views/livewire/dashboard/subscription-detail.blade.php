<flux:modal wire:model.self="show" @close="closeModal" class="max-w-4xl">
    @if ($this->subscription)
        <div class="p-6">
            {{-- Header --}}
            <div class="mb-6">
                <flux:heading size="lg">{{ $this->subscription->plan->name }}</flux:heading>
                <flux:text variant="muted" class="mt-1">
                    View your subscription details and access credentials
                </flux:text>
            </div>

            <div class="space-y-6">
                {{-- Status Overview --}}
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:text variant="muted" class="text-sm">Status</flux:text>
                            <div class="mt-1">
                                @if ($this->subscription->status === \App\Enums\SubscriptionStatus::Active)
                                    @if ($this->subscription->expires_at && now()->diffInDays($this->subscription->expires_at) <= 7 && $this->subscription->expires_at->isFuture())
                                        <flux:badge color="yellow" icon="exclamation-triangle">Expiring Soon</flux:badge>
                                    @else
                                        <flux:badge color="green" icon="check-circle">Active</flux:badge>
                                    @endif
                                @elseif ($this->subscription->status === \App\Enums\SubscriptionStatus::Expired)
                                    <flux:badge color="red" icon="x-circle">Expired</flux:badge>
                                @elseif ($this->subscription->status === \App\Enums\SubscriptionStatus::Suspended)
                                    <flux:badge color="yellow" icon="pause">Suspended</flux:badge>
                                @elseif ($this->subscription->status === \App\Enums\SubscriptionStatus::Cancelled)
                                    <flux:badge color="red" icon="x-mark">Cancelled</flux:badge>
                                @elseif ($this->subscription->status === \App\Enums\SubscriptionStatus::Pending)
                                    <flux:badge color="blue" icon="clock">Pending</flux:badge>
                                @endif
                            </div>
                        </div>
                        @if ($this->subscription->expires_at)
                            <div class="text-right">
                                <flux:text variant="muted" class="text-sm">
                                    @if ($this->subscription->expires_at->isFuture())
                                        Expires
                                    @else
                                        Expired
                                    @endif
                                </flux:text>
                                <flux:text class="mt-1 block font-semibold">
                                    {{ $this->subscription->expires_at->format('M d, Y') }}
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400 font-normal">
                                        ({{ $this->subscription->expires_at->diffForHumans() }})
                                    </span>
                                </flux:text>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Subscription Details --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Subscription Details</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                        @if ($this->subscription->plan->description)
                            <div class="flex justify-between">
                                <flux:text variant="muted">Plan</flux:text>
                                <flux:text>{{ $this->subscription->plan->name }}</flux:text>
                            </div>
                            <div class="pb-2 border-b border-zinc-200 dark:border-zinc-700">
                                <flux:text variant="muted" class="text-sm">{{ $this->subscription->plan->description }}</flux:text>
                            </div>
                        @endif

                        <div class="flex justify-between">
                            <flux:text variant="muted">Started</flux:text>
                            <flux:text>{{ $this->subscription->starts_at->format('M d, Y') }}</flux:text>
                        </div>

                        @if ($this->subscription->expires_at)
                            <div class="flex justify-between">
                                <flux:text variant="muted">
                                    @if ($this->subscription->expires_at->isFuture())
                                        Expires
                                    @else
                                        Expired
                                    @endif
                                </flux:text>
                                <flux:text>{{ $this->subscription->expires_at->format('M d, Y') }}</flux:text>
                            </div>
                        @endif

                        @if ($this->subscription->plan->duration_days)
                            <div class="flex justify-between">
                                <flux:text variant="muted">Duration</flux:text>
                                <flux:text>{{ $this->subscription->plan->duration_days }} days</flux:text>
                            </div>
                        @endif

                        @if ($this->subscription->plan->max_devices)
                            <div class="flex justify-between">
                                <flux:text variant="muted">Max Devices</flux:text>
                                <flux:text>{{ $this->subscription->plan->max_devices }}</flux:text>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Plan Features --}}
                @if ($this->subscription->plan->features)
                    <div>
                        <flux:heading size="sm" class="mb-3">Included Features</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @foreach ($this->subscription->plan->features as $feature)
                                    <div class="flex items-center gap-2">
                                        <flux:icon.check class="w-4 h-4 text-green-500" />
                                        <flux:text>{{ $feature }}</flux:text>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Service Account Credentials --}}
                @if ($this->subscription->serviceAccount && $this->subscription->status === \App\Enums\SubscriptionStatus::Active)
                    <div>
                        <flux:heading size="sm" class="mb-3">IPTV Service Credentials</flux:heading>

                        @if (!$credentialsUnlocked)
                            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-6 text-center">
                                <flux:icon.lock-closed class="w-12 h-12 mx-auto text-amber-500 dark:text-amber-400 mb-3" />
                                <flux:heading size="sm" class="mb-2">Credentials Locked</flux:heading>
                                <flux:text variant="muted" class="mb-4">
                                    For security, please confirm your password to view your service credentials
                                </flux:text>
                                <flux:button
                                    wire:click="unlockCredentials"
                                    variant="primary"
                                    icon="lock-open"
                                >
                                    Unlock Credentials
                                </flux:button>
                            </div>
                        @else
                            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-3">
                                {{-- Username --}}
                                <div>
                                    <flux:text variant="muted" class="text-sm mb-1">Username</flux:text>
                                    <div class="flex items-center gap-2 bg-white dark:bg-zinc-900 p-3 rounded border border-zinc-200 dark:border-zinc-700">
                                        <flux:text class="font-mono flex-1">{{ $this->subscription->serviceAccount->username }}</flux:text>
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            icon="clipboard"
                                            onclick="navigator.clipboard.writeText('{{ $this->subscription->serviceAccount->username }}'); alert('Username copied to clipboard!');"
                                            title="Copy username"
                                        />
                                    </div>
                                </div>

                                {{-- Password --}}
                                <div>
                                    <flux:text variant="muted" class="text-sm mb-1">Password</flux:text>
                                    <div class="flex items-center gap-2 bg-white dark:bg-zinc-900 p-3 rounded border border-zinc-200 dark:border-zinc-700">
                                        <flux:text class="font-mono flex-1">
                                            @if ($showPassword)
                                                {{ $this->subscription->serviceAccount->password }}
                                            @else
                                                ••••••••••••••••
                                            @endif
                                        </flux:text>
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            :icon="$showPassword ? 'eye-slash' : 'eye'"
                                            wire:click="togglePassword"
                                            title="Toggle password visibility"
                                        />
                                        @if ($showPassword)
                                            <flux:button
                                                size="xs"
                                                variant="ghost"
                                                icon="clipboard"
                                                onclick="navigator.clipboard.writeText('{{ $this->subscription->serviceAccount->password }}'); alert('Password copied to clipboard!');"
                                                title="Copy password"
                                            />
                                        @endif
                                    </div>
                                </div>

                                {{-- Server URL --}}
                                <div>
                                    <flux:text variant="muted" class="text-sm mb-1">Server URL</flux:text>
                                    <div class="flex items-center gap-2 bg-white dark:bg-zinc-900 p-3 rounded border border-zinc-200 dark:border-zinc-700">
                                        <flux:text class="font-mono flex-1 break-all">{{ $this->subscription->serviceAccount->server_url }}</flux:text>
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            icon="clipboard"
                                            onclick="navigator.clipboard.writeText('{{ $this->subscription->serviceAccount->server_url }}'); alert('Server URL copied to clipboard!');"
                                            title="Copy server URL"
                                        />
                                    </div>
                                </div>

                                {{-- M3U URL --}}
                                @if ($this->getM3uUrl())
                                    <div>
                                        <flux:text variant="muted" class="text-sm mb-1">M3U Playlist URL</flux:text>
                                        <div class="flex items-center gap-2 bg-white dark:bg-zinc-900 p-3 rounded border border-zinc-200 dark:border-zinc-700">
                                            <flux:text class="font-mono flex-1 text-xs break-all">{{ $this->getM3uUrl() }}</flux:text>
                                            <flux:button
                                                size="xs"
                                                variant="ghost"
                                                icon="clipboard"
                                                onclick="navigator.clipboard.writeText('{{ $this->getM3uUrl() }}'); alert('M3U URL copied to clipboard!');"
                                                title="Copy M3U URL"
                                            />
                                            <a href="{{ $this->getM3uUrl() }}" download="playlist.m3u">
                                                <flux:button
                                                    size="xs"
                                                    variant="ghost"
                                                    icon="arrow-down-tray"
                                                    title="Download M3U file"
                                                />
                                            </a>
                                            <flux:button
                                                size="xs"
                                                variant="ghost"
                                                icon="qr-code"
                                                wire:click="toggleM3uQrCode"
                                                title="{{ $showM3uQrCode ? 'Hide QR Code' : 'Show QR Code' }}"
                                            />
                                        </div>
                                        @if ($showM3uQrCode && $this->m3uQrCodeSvg)
                                            <div class="mt-3 flex flex-col items-center">
                                                <div
                                                    class="bg-white p-3 rounded border border-zinc-200 dark:border-zinc-700"
                                                    x-data
                                                    :style="($flux.appearance === 'dark' || ($flux.appearance === 'system' && $flux.dark)) ? 'filter: invert(1) brightness(1.5)' : ''"
                                                >
                                                    {!! $this->m3uQrCodeSvg !!}
                                                </div>
                                                <flux:text variant="muted" class="text-xs text-center mt-2">
                                                    Scan with your IPTV app to auto-configure
                                                </flux:text>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- EPG URL --}}
                                @if ($this->getEpgUrl())
                                    <div>
                                        <flux:text variant="muted" class="text-sm mb-1">EPG URL</flux:text>
                                        <div class="flex items-center gap-2 bg-white dark:bg-zinc-900 p-3 rounded border border-zinc-200 dark:border-zinc-700">
                                            <flux:text class="font-mono flex-1 text-xs break-all">{{ $this->getEpgUrl() }}</flux:text>
                                            <flux:button
                                                size="xs"
                                                variant="ghost"
                                                icon="clipboard"
                                                onclick="navigator.clipboard.writeText('{{ $this->getEpgUrl() }}'); alert('EPG URL copied to clipboard!');"
                                                title="Copy EPG URL"
                                            />
                                            <flux:button
                                                size="xs"
                                                variant="ghost"
                                                icon="qr-code"
                                                wire:click="toggleEpgQrCode"
                                                title="{{ $showEpgQrCode ? 'Hide QR Code' : 'Show QR Code' }}"
                                            />
                                        </div>
                                        @if ($showEpgQrCode && $this->epgQrCodeSvg)
                                            <div class="mt-3 flex flex-col items-center">
                                                <div
                                                    class="bg-white p-3 rounded border border-zinc-200 dark:border-zinc-700"
                                                    x-data
                                                    :style="($flux.appearance === 'dark' || ($flux.appearance === 'system' && $flux.dark)) ? 'filter: invert(1) brightness(1.5)' : ''"
                                                >
                                                    {!! $this->epgQrCodeSvg !!}
                                                </div>
                                                <flux:text variant="muted" class="text-xs text-center mt-2">
                                                    Scan with your IPTV app for EPG guide
                                                </flux:text>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Setup Instructions --}}
                                <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                    <flux:callout variant="info" icon="information-circle">
                                        <flux:text class="text-sm">
                                            Use these credentials to connect to the IPTV service using your preferred player (IPTV Smarters, VLC, etc.)
                                        </flux:text>
                                    </flux:callout>
                                </div>
                            </div>
                        @endif
                    </div>
                @elseif ($this->subscription->status === \App\Enums\SubscriptionStatus::Pending)
                    <div>
                        <flux:heading size="sm" class="mb-3">Service Account</flux:heading>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 text-center">
                            <flux:icon.clock class="w-12 h-12 mx-auto text-blue-500 dark:text-blue-400 mb-3" />
                            <flux:heading size="sm" class="mb-2">Provisioning in Progress</flux:heading>
                            <flux:text variant="muted">
                                Your IPTV service is being set up. This usually takes a few minutes. You'll receive an email once your account is ready.
                            </flux:text>
                        </div>
                    </div>
                @elseif ($this->subscription->status === \App\Enums\SubscriptionStatus::Expired)
                    <div>
                        <flux:heading size="sm" class="mb-3">Service Account</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-6 text-center">
                            <flux:icon.x-circle class="w-12 h-12 mx-auto text-zinc-400 dark:text-zinc-600 mb-3" />
                            <flux:heading size="sm" class="mb-2">Subscription Expired</flux:heading>
                            <flux:text variant="muted">
                                This subscription has expired. Renew your subscription to regain access to the service.
                            </flux:text>
                        </div>
                    </div>
                @endif

                {{-- Renewal Settings --}}
                @if ($this->subscription->status === \App\Enums\SubscriptionStatus::Active || $this->subscription->status === \App\Enums\SubscriptionStatus::Expired)
                    <div>
                        <flux:heading size="sm" class="mb-3">Renewal Settings</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-3">
                            @if ($this->subscription->status === \App\Enums\SubscriptionStatus::Active)
                                {{-- Auto-Renewal Toggle --}}
                                <div class="flex items-center justify-between">
                                    <div>
                                        <flux:text class="font-medium">Auto-Renewal</flux:text>
                                        <flux:text variant="muted" class="text-sm">
                                            @if ($this->subscription->auto_renew)
                                                Your subscription will renew automatically
                                            @else
                                                Your subscription will not renew automatically
                                            @endif
                                        </flux:text>
                                    </div>
                                    <flux:switch
                                        wire:click="toggleAutoRenewal"
                                        :checked="$this->subscription->auto_renew"
                                    />
                                </div>

                                @if ($this->subscription->auto_renew)
                                    {{-- Next Renewal Date --}}
                                    @if ($this->subscription->next_renewal_at || $this->subscription->expires_at)
                                        <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                            <flux:text variant="muted">Next Renewal</flux:text>
                                            <flux:text>
                                                {{ ($this->subscription->next_renewal_at ?? $this->subscription->expires_at->subDay())->format('M d, Y') }}
                                            </flux:text>
                                        </div>
                                    @endif
                                @endif
                            @endif

                            {{-- Last Renewal --}}
                            @if ($this->subscription->last_renewal_at)
                                <div class="flex justify-between @if($this->subscription->status === \App\Enums\SubscriptionStatus::Active && !$this->subscription->auto_renew) pt-2 border-t border-zinc-200 dark:border-zinc-700 @endif">
                                    <flux:text variant="muted">Last Renewed</flux:text>
                                    <flux:text>{{ $this->subscription->last_renewal_at->format('M d, Y') }}</flux:text>
                                </div>
                            @endif

                            {{-- Payment Method --}}
                            @php $paymentInfo = $this->getPaymentMethodInfo(); @endphp
                            @if ($paymentInfo)
                                <div class="flex justify-between">
                                    <flux:text variant="muted">Payment Method</flux:text>
                                    <flux:text>
                                        {{ $paymentInfo['type'] }} •••• {{ $paymentInfo['last4'] }}
                                        <span class="text-zinc-500">({{ $paymentInfo['gateway'] }})</span>
                                    </flux:text>
                                </div>
                            @endif

                            {{-- Credit Balance --}}
                            @if ($this->subscription->credit_balance > 0)
                                <div class="flex justify-between">
                                    <flux:text variant="muted">Credit Balance</flux:text>
                                    <flux:text class="text-green-600 dark:text-green-400">
                                        {{ $this->subscription->currency ?? 'GHS' }} {{ number_format($this->subscription->credit_balance, 2) }}
                                    </flux:text>
                                </div>
                            @endif

                            {{-- Renew Now Button --}}
                            @if ($this->canRenew())
                                <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                    @if ($this->subscription->status === \App\Enums\SubscriptionStatus::Expired)
                                        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-3">
                                            <flux:text class="text-sm">
                                                Your subscription has expired. Renew now to restore access.
                                            </flux:text>
                                        </flux:callout>
                                    @elseif ($this->subscription->daysUntilExpiry() <= 3)
                                        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-3">
                                            <flux:text class="text-sm">
                                                Your subscription expires in {{ $this->subscription->daysUntilExpiry() }} {{ $this->subscription->daysUntilExpiry() === 1 ? 'day' : 'days' }}.
                                            </flux:text>
                                        </flux:callout>
                                    @endif
                                    <a href="{{ $this->getRenewalUrl() }}" class="block">
                                        <flux:button variant="primary" icon="arrow-path" class="w-full">
                                            Renew Now
                                        </flux:button>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Order Information --}}
                @if ($this->subscription->orders->isNotEmpty())
                    <div>
                        <flux:heading size="sm" class="mb-3">Order Information</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between">
                                <flux:text variant="muted">Order ID</flux:text>
                                <flux:text class="font-mono">{{ $this->subscription->orders->first()->woocommerce_order_id ?? $this->subscription->orders->first()->id }}</flux:text>
                            </div>
                            <div class="flex justify-between">
                                <flux:text variant="muted">Order Date</flux:text>
                                <flux:text>{{ $this->subscription->orders->first()->created_at->format('M d, Y') }}</flux:text>
                            </div>
                            <div class="flex justify-between">
                                <flux:text variant="muted">Status</flux:text>
                                <flux:badge color="green">{{ $this->subscription->orders->first()->status->value }}</flux:badge>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Activity Timeline --}}
                @if ($this->activityTimeline->isNotEmpty())
                    <div>
                        <flux:heading size="sm" class="mb-3">Activity Timeline</flux:heading>
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                            <div class="relative">
                                {{-- Timeline line --}}
                                <div class="absolute left-3 top-2 bottom-2 w-px bg-zinc-300 dark:bg-zinc-600"></div>

                                <div class="space-y-4">
                                    @foreach ($this->activityTimeline as $event)
                                        <div class="relative flex items-start gap-3 pl-8">
                                            {{-- Timeline dot --}}
                                            <div class="absolute left-0 w-6 h-6 rounded-full flex items-center justify-center
                                                @if($event['color'] === 'green') bg-green-100 dark:bg-green-900/30
                                                @elseif($event['color'] === 'blue') bg-blue-100 dark:bg-blue-900/30
                                                @elseif($event['color'] === 'red') bg-red-100 dark:bg-red-900/30
                                                @elseif($event['color'] === 'amber') bg-amber-100 dark:bg-amber-900/30
                                                @elseif($event['color'] === 'purple') bg-purple-100 dark:bg-purple-900/30
                                                @else bg-zinc-100 dark:bg-zinc-700
                                                @endif
                                            ">
                                                @if($event['icon'] === 'plus-circle')
                                                    <flux:icon.plus-circle class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                                @elseif($event['icon'] === 'credit-card')
                                                    <flux:icon.credit-card class="w-3.5 h-3.5 text-green-600 dark:text-green-400" />
                                                @elseif($event['icon'] === 'check-circle')
                                                    <flux:icon.check-circle class="w-3.5 h-3.5 text-green-600 dark:text-green-400" />
                                                @elseif($event['icon'] === 'arrow-path')
                                                    <flux:icon.arrow-path class="w-3.5 h-3.5 text-green-600 dark:text-green-400" />
                                                @elseif($event['icon'] === 'arrows-right-left')
                                                    <flux:icon.arrows-right-left class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" />
                                                @elseif($event['icon'] === 'x-circle')
                                                    <flux:icon.x-circle class="w-3.5 h-3.5 text-red-600 dark:text-red-400" />
                                                @elseif($event['icon'] === 'pause-circle')
                                                    <flux:icon.pause-circle class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                                                @else
                                                    <flux:icon.information-circle class="w-3.5 h-3.5 text-zinc-600 dark:text-zinc-400" />
                                                @endif
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <flux:text class="text-sm">{{ $event['description'] }}</flux:text>
                                                <flux:text variant="muted" class="text-xs">
                                                    {{ $event['date']->format('M d, Y \a\t g:i A') }}
                                                </flux:text>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                @if ($this->subscription->status === \App\Enums\SubscriptionStatus::Active && $this->subscription->expires_at?->isFuture())
                    <flux:button wire:click="openChangePlanModal" variant="subtle" icon="arrows-right-left">
                        Change Plan
                    </flux:button>
                @endif
                <flux:button wire:click="closeModal" variant="primary">
                    Close
                </flux:button>
            </div>
        </div>
    @endif
</flux:modal>
