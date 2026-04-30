@extends('layouts.app')

@section('alpineRoot', 'compare()')

@section('title', 'Compare — ' . config('app.name'))

@section('header')
<a href="{{ route('fonts.index') }}" class="text-xs text-muted hover:text-fg">
    ← All fonts
</a>
@endsection

@section('content')
<article class="mx-auto max-w-7xl space-y-6">

    <style>
        @foreach ($families as $family)
            @php $wghtAxis = collect($family->axes ?? [])->firstWhere('tag', 'wght'); @endphp
            @foreach ($family->fontFiles as $file)
            @php
                if ($file->is_variable) {
                    $weightDecl = $wghtAxis
                        ? $wghtAxis['min'] . ' ' . $wghtAxis['max']
                        : '100 900';
                    $format = 'truetype-variations';
                } else {
                    $weightDecl = $file->weight ?? 400;
                    $format = 'truetype';
                }
            @endphp
            @font-face {
                font-family: '{{ $family->family }}';
                src: url('{{ route('fonts.serve', $file) }}') format('{{ $format }}');
                font-weight: {{ $weightDecl }};
                font-style: {{ $file->style }};
                font-display: swap;
            }
            @endforeach
        @endforeach
    </style>

    <header class="border-b border-border-soft pb-4">
        <h1 class="text-xl font-medium tracking-tight">Comparing {{ $families->count() }} fonts</h1>
        <p class="mt-1 text-xs text-muted">
            {{ $families->pluck('family')->join(' · ') }}
        </p>
    </header>

    <section class="space-y-3">
        <textarea
            x-model="text"
            rows="2"
            class="w-full resize-none rounded-md border border-border px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
            placeholder="Type something..."
        ></textarea>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-muted">
            <label class="flex items-center gap-2">
                <span class="text-xs uppercase tracking-wide text-muted">Size</span>
                <input type="range" min="14" max="120" x-model.number="size" class="w-48">
                <span class="w-14 text-right tabular-nums" x-text="size + 'px'"></span>
            </label>
            <label class="flex items-center gap-2">
                <span class="text-xs uppercase tracking-wide text-muted">Weight</span>
                <input type="range" min="100" max="900" step="100" x-model.number="weight" class="w-48">
                <span class="w-14 text-right tabular-nums" x-text="weight"></span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" x-model="italic" class="h-4 w-4 rounded border-border">
                <span>Italic</span>
            </label>
        </div>
    </section>

    <div class="grid gap-4" :style="`grid-template-columns: repeat({{ $families->count() }}, minmax(0, 1fr));`">
        @foreach ($families as $family)
            <div class="rounded-lg border border-border-soft p-5">
                <div class="mb-4">
                    <a href="{{ route('fonts.show', strtolower(str_replace(' ', '-', $family->family))) }}" class="block">
                        <h2 class="text-base font-medium tracking-tight hover:underline">{{ $family->family }}</h2>
                    </a>
                    <p class="mt-0.5 text-xs text-muted">
                        {{ $family->category }} &middot; {{ $family->file_count }} {{ Str::plural('style', $family->file_count) }}
                        @if ($family->is_variable)
                            &middot; <span class="font-medium text-emerald-600">variable</span>
                        @endif
                    </p>
                </div>
                <p
                    class="break-words leading-snug text-fg"
                    :style="`font-family: '{{ $family->family }}', sans-serif; font-size: ${size}px; font-weight: ${weight}; font-style: ${italic ? 'italic' : 'normal'};`"
                    x-text="text || @js($family->family)"
                ></p>
            </div>
        @endforeach
    </div>

</article>

@push('head')
<style>[x-cloak]{display:none!important}</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('compare', () => ({
        text: 'The quick brown fox jumps over the lazy dog',
        size: 48,
        weight: 400,
        italic: false,
    }));
});
</script>
@endpush
@endsection
