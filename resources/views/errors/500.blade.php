@extends('layouts.app')

@section('title', 'Something went wrong — ' . config('app.name'))

@section('content')
<div class="mx-auto flex min-h-[60vh] max-w-md flex-col items-center justify-center text-center">
    <div class="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-full border border-rose-500/30 bg-rose-500/5">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-8 w-8 text-rose-500">
            <circle cx="12" cy="12" r="9"/>
            <path stroke-linecap="round" d="M12 8v5M12 16.5v.01"/>
        </svg>
    </div>
    <h1 class="text-2xl font-medium tracking-tight">Something went wrong</h1>
    <p class="mt-2 text-sm text-muted">
        The server hit an unexpected error. Try refreshing — and if it keeps happening, check <code class="rounded bg-surface px-1.5 py-0.5 font-mono text-xs">storage/logs/laravel.log</code>.
    </p>
    <div class="mt-6 flex gap-2">
        <button
            type="button"
            onclick="window.location.reload()"
            class="focus-ring rounded-md border border-border px-4 py-2 text-sm hover:bg-surface"
        >Reload page</button>
        <a
            href="{{ route('fonts.index') }}"
            class="focus-ring rounded-md border border-border px-4 py-2 text-sm hover:bg-surface"
        >Browse all fonts</a>
    </div>
</div>
@endsection
