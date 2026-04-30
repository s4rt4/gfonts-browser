@extends('layouts.app')

@section('title', 'Be right back — ' . config('app.name'))

@section('content')
<div class="mx-auto flex min-h-[60vh] max-w-md flex-col items-center justify-center text-center">
    <div class="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-full border border-border-soft bg-surface">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-8 w-8 text-muted">
            <circle cx="12" cy="12" r="9"/>
            <path stroke-linecap="round" d="M12 7v5l3 2"/>
        </svg>
    </div>
    <h1 class="text-2xl font-medium tracking-tight">Down for maintenance</h1>
    <p class="mt-2 text-sm text-muted">
        We'll be back in a moment.
    </p>
</div>
@endsection
