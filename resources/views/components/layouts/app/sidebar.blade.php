<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @if(!auth()->user()?->isAdmin())
                    <flux:sidebar.group :heading="__('My Account')" class="grid">
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('My Subscriptions') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="shopping-bag" :href="route('orders.index')" :current="request()->routeIs('orders.*')" wire:navigate>
                            {{ __('Orders') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="document-text" :href="route('invoices.index')" :current="request()->routeIs('invoices.*')" wire:navigate>
                            {{ __('Invoices') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="credit-card" :href="route('checkout.index')" :current="request()->routeIs('checkout.*')" wire:navigate>
                            {{ __('Subscribe') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="chat-bubble-left-right" :href="route('support.my-tickets')" :current="request()->routeIs('support.*')" wire:navigate>
                            {{ __('Support Tickets') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="device-phone-mobile" :href="route('streaming-apps')" :current="request()->routeIs('streaming-apps')" wire:navigate>
                            {{ __('Streaming Apps') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group :heading="__('Help')" class="grid">
                        <flux:sidebar.item icon="shield-check" :href="route('help.vpn')" :current="request()->routeIs('help.vpn')" wire:navigate>
                            {{ __('VPN Troubleshooting') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                @if(auth()->user()?->isAdmin())
                    <flux:sidebar.group :heading="__('Administration')" class="grid">
                        <flux:sidebar.item icon="squares-2x2" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="chart-bar" :href="route('admin.analytics')" :current="request()->routeIs('admin.analytics')" wire:navigate>
                            {{ __('Analytics') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="users" :href="route('admin.analytics.cohorts')" :current="request()->routeIs('admin.analytics.cohorts')" wire:navigate>
                            {{ __('Cohort Analysis') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="credit-card" :href="route('admin.credits')" :current="request()->routeIs('admin.credits')" wire:navigate>
                            {{ __('Credits') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="rectangle-stack" :href="route('admin.subscriptions.index')" :current="request()->routeIs('admin.subscriptions.*')" wire:navigate>
                            {{ __('Subscriptions') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="arrows-right-left" :href="route('admin.plan-changes.index')" :current="request()->routeIs('admin.plan-changes.*')" wire:navigate>
                            {{ __('Plan Changes') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="shopping-cart" :href="route('admin.orders.index')" :current="request()->routeIs('admin.orders.*')" wire:navigate>
                            {{ __('Orders') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="receipt-percent" :href="route('admin.invoices.index')" :current="request()->routeIs('admin.invoices.*')" wire:navigate>
                            {{ __('Invoices') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="exclamation-triangle" :href="route('admin.failed-jobs.index')" :current="request()->routeIs('admin.failed-jobs.*')" wire:navigate>
                            {{ __('Failed Jobs') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="document-text" :href="route('admin.provisioning-logs.index')" :current="request()->routeIs('admin.provisioning-logs.*')" wire:navigate>
                            {{ __('Provisioning Logs') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="tag" :href="route('admin.plans.index')" :current="request()->routeIs('admin.plans.*')" wire:navigate>
                            {{ __('Plans') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="ticket" :href="route('admin.coupons.index')" :current="request()->routeIs('admin.coupons.*')" wire:navigate>
                            {{ __('Coupons') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="chat-bubble-left-right" :href="route('admin.support.tickets')" :current="request()->routeIs('admin.support.*')" wire:navigate>
                            {{ __('Support Tickets') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="device-phone-mobile" :href="route('admin.streaming-apps.index')" :current="request()->routeIs('admin.streaming-apps.*')" wire:navigate>
                            {{ __('Streaming Apps') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
