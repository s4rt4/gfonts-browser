@extends('layouts.app')

@section('title', 'Not found — ' . config('app.name'))

@section('content')
<div class="mx-auto flex min-h-[60vh] max-w-md flex-col items-center justify-center text-center">
    <div class="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-full border border-border-soft bg-surface">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-8 w-8 text-muted">
            <circle cx="11" cy="11" r="7"/>
            <path stroke-linecap="round" d="M20 20l-3.5-3.5"/>
        </svg>
    </div>
    <h1 class="text-2xl font-medium tracking-tight">Page not found</h1>
    <p class="mt-2 text-sm text-muted">
        The page you're after doesn't exist. Maybe try the search bar?
    </p>
    <a
        href="{{ route('fonts.index') }}"
        class="focus-ring mt-6 inline-flex items-center gap-1.5 rounded-md border border-border px-4 py-2 text-sm hover:bg-surface"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5"><path d="M19 12H5M12 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Browse all fonts
    </a>
</div>
@endsection
