<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <meta name="description" content="Local Google Fonts browser — preview, install, compare your offline TTF collection.">
    <meta name="theme-color" content="#f5f1e8" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#151515"  media="(prefers-color-scheme: dark)">

    <meta property="og:title"       content="@yield('title', config('app.name'))">
    <meta property="og:description" content="Local Google Fonts browser for your offline TTF collection.">
    <meta property="og:type"        content="website">
    <meta property="og:image"       content="{{ url('/img/google-fonts-logo.png') }}">
    <meta name="twitter:card"       content="summary">

    <link rel="icon" type="image/svg+xml" href="{{ asset('img/google-fonts-logo.svg') }}">
    <link rel="icon" type="image/x-icon"  href="{{ asset('img/google-fonts-logo.ico') }}">
    <link rel="apple-touch-icon"          href="{{ asset('img/google-fonts-logo.png') }}">

    {{-- Inter for the UI itself, fonts.bunny is a privacy-respecting Google Fonts mirror --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

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
    <header class="sticky top-0 z-30 border-b border-border-soft bg-bg/90 backdrop-blur theme-aware">
        <div class="flex items-center gap-4 px-6 py-3">
            <a href="{{ route('fonts.index') }}" class="focus-ring flex shrink-0 items-center gap-2 rounded">
                <img src="{{ asset('img/google-fonts-logo.svg') }}" alt="" class="h-7 w-7">
                <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
            </a>
            <div class="flex flex-1 items-center justify-center">
                @yield('header')
            </div>
            <button
                type="button"
                @click="$store.keys.toggleHelp()"
                class="focus-ring shrink-0 rounded-md p-1.5 text-muted hover:text-fg"
                aria-label="Keyboard shortcuts"
                title="Keyboard shortcuts (?)"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                    <circle cx="12" cy="12" r="9"/>
                    <path stroke-linecap="round" d="M9.5 9a2.5 2.5 0 1 1 4 2c-1 .8-1.5 1.2-1.5 2.5"/>
                    <circle cx="12" cy="17" r="0.7" fill="currentColor"/>
                </svg>
            </button>
            <button
                type="button"
                @click="$store.theme.toggle()"
                class="focus-ring shrink-0 rounded-md border border-border-soft p-1.5 text-muted hover:border-border hover:text-fg theme-aware"
                :aria-label="$store.theme.value === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
                :title="$store.theme.value === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
            >
                <svg x-show="$store.theme.value === 'light'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
                </svg>
                <svg x-show="$store.theme.value === 'dark'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                    <circle cx="12" cy="12" r="4"/>
                    <path stroke-linecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                </svg>
            </button>
        </div>
    </header>

    <main x-cloak class="px-6 py-6 animate-fade-in">
        @yield('content')
    </main>

    {{-- Toast container ─ global, top-level ─────────────────── --}}
    <div class="pointer-events-none fixed bottom-4 right-4 z-[70] flex w-80 max-w-[calc(100vw-2rem)] flex-col-reverse gap-2">
        <template x-for="t in $store.toast.items" :key="t.id">
            <div
                @click="$store.toast.dismiss(t.id)"
                class="pointer-events-auto flex animate-toast-in cursor-pointer items-start gap-3 rounded-lg border bg-bg px-4 py-3 text-sm shadow-popover"
                :class="{
                    'border-emerald-500/50': t.tone === 'success',
                    'border-rose-500/50':    t.tone === 'error',
                    'border-border':         t.tone === 'info',
                }"
            >
                <svg x-show="t.tone === 'success'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7"/>
                </svg>
                <svg x-show="t.tone === 'error'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0 text-rose-600">
                    <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v5M12 16.5v.01"/>
                </svg>
                <svg x-show="t.tone === 'info'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0 text-fg">
                    <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v.01M12 11v5"/>
                </svg>
                <span class="flex-1 text-fg" x-text="t.message"></span>
                <button class="text-muted hover:text-fg" @click.stop="$store.toast.dismiss(t.id)" aria-label="Dismiss">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3 w-3">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Keyboard help modal ─────────────────────────────────── --}}
    <div
        x-show="$store.keys.helpOpen"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="$store.keys.helpOpen = false"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-fg/40 p-4"
        @click.self="$store.keys.helpOpen = false"
    >
        <div class="w-full max-w-md overflow-hidden rounded-lg border border-border-soft bg-bg shadow-popover">
            <div class="flex items-center gap-2 border-b border-border-soft px-5 py-3">
                <img src="{{ asset('img/google-fonts-logo.svg') }}" alt="" class="h-5 w-5">
                <span class="text-sm font-semibold tracking-tight">Keyboard shortcuts</span>
            </div>
            <dl class="grid grid-cols-1 gap-2 px-5 py-4 text-sm">
                @foreach ([
                    ['/', 'Focus search'],
                    ['?', 'Show keyboard shortcuts'],
                    ['Esc', 'Close modal / blur input'],
                    ['←  →', 'Previous / next family (detail page)'],
                ] as [$key, $label])
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-muted">{{ $label }}</span>
                        <kbd class="rounded border border-border-soft bg-surface px-2 py-0.5 font-mono text-xs text-fg">{{ $key }}</kbd>
                    </div>
                @endforeach
            </dl>
            <div class="flex justify-end border-t border-border-soft bg-surface/50 px-5 py-3">
                <button
                    type="button"
                    @click="$store.keys.helpOpen = false"
                    class="focus-ring rounded-md border border-border px-3 py-1.5 text-sm hover:bg-surface"
                >Close</button>
            </div>
        </div>
    </div>
</body>
</html>
