<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('img/google-fonts-logo.ico') }}">

    {{-- Set theme before paint to avoid flash --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('gfonts.theme');
                var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-bg text-fg antialiased" x-data="@yield('alpineRoot', '{}')">
    <header class="sticky top-0 z-30 border-b border-border-soft bg-bg/90 backdrop-blur">
        <div class="flex items-center gap-4 px-6 py-3">
            <a href="{{ route('fonts.index') }}" class="flex shrink-0 items-center gap-2">
                <img src="{{ asset('img/google-fonts-logo.svg') }}" alt="" class="h-7 w-7">
                <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
            </a>
            <div class="flex flex-1 items-center justify-center">
                @yield('header')
            </div>
            <button
                type="button"
                @click="$store.theme.toggle()"
                class="ml-2 shrink-0 rounded-md border border-border-soft p-1.5 text-muted hover:border-border hover:text-fg"
                :aria-label="$store.theme.value === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
                :title="$store.theme.value === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
                x-data
            >
                <svg x-show="$store.theme.value === 'light'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
                </svg>
                <svg x-show="$store.theme.value === 'dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                    <circle cx="12" cy="12" r="4"/>
                    <path stroke-linecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                </svg>
            </button>
        </div>
    </header>

    <main x-cloak class="px-6 py-6">
        @yield('content')
    </main>
</body>
</html>
