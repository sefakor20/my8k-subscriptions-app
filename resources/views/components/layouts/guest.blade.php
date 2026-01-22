<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-motv-bg text-white antialiased">
        <x-landing.header />

        <main>
            {{ $slot }}
        </main>

        <x-landing.footer />

        @fluxScripts
    </body>
</html>
