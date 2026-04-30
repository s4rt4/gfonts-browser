@extends('layouts.app')

@section('alpineRoot', 'detail()')

@section('title', $family->family . ' — ' . config('app.name'))

@section('header')
<div class="flex items-center gap-3 text-xs">
    <a href="{{ route('fonts.index') }}" class="focus-ring rounded inline-flex items-center gap-1 text-muted hover:text-fg">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5"><path d="M19 12H5M12 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        All fonts
    </a>
    @if ($prevSlug || $nextSlug)
        <span class="text-muted/40">·</span>
        <div class="flex items-center gap-1">
            @if ($prevSlug)
                <a href="{{ route('fonts.show', $prevSlug) }}"
                   class="focus-ring rounded-md border border-border-soft p-1 text-muted hover:border-border hover:text-fg"
                   title="Previous: {{ $prevName }}"
                   aria-label="Previous family">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @endif
            @if ($nextSlug)
                <a href="{{ route('fonts.show', $nextSlug) }}"
                   class="focus-ring rounded-md border border-border-soft p-1 text-muted hover:border-border hover:text-fg"
                   title="Next: {{ $nextName }}"
                   aria-label="Next family">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5"><path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @endif
        </div>
    @endif
</div>
@endsection

@php
    $variableFiles = $family->fontFiles->where('is_variable', true)->values();
    $staticFiles   = $family->fontFiles->where('is_variable', false)->sortBy([['weight','asc'],['style','asc']])->values();
    $hasItalicFile = $family->fontFiles->contains('style', 'italic');
    $variableItalic = $variableFiles->firstWhere('style', 'italic');
    $hasSlntAxis   = collect($family->axes ?? [])->contains('tag', 'slnt');
    $wghtAxis      = collect($family->axes ?? [])->firstWhere('tag', 'wght');

    $axesInit = collect($family->axes ?? [])
        ->mapWithKeys(fn ($a) => [$a['tag'] => $a['defaultValue']])
        ->all();
@endphp

@section('content')
<article
    class="mx-auto max-w-6xl space-y-8"
    @keydown.escape.window="window.location.href = '{{ route('fonts.index') }}'"
    @keydown.arrow-left.window="if (!['INPUT','TEXTAREA'].includes(document.activeElement?.tagName)) { @if($prevSlug) window.location.href = '{{ route('fonts.show', $prevSlug) }}'; @endif }"
    @keydown.arrow-right.window="if (!['INPUT','TEXTAREA'].includes(document.activeElement?.tagName)) { @if($nextSlug) window.location.href = '{{ route('fonts.show', $nextSlug) }}'; @endif }"
>

    <style>
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
    </style>

    {{-- Header --}}
    <header class="border-b border-border-soft pb-6">
        <div class="flex flex-wrap items-end gap-3">
            <h1 class="text-4xl font-medium tracking-tight">{{ $family->family }}</h1>
            @if ($variableFiles->isNotEmpty())
                <span class="rounded bg-emerald-500/15 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">variable</span>
            @endif
            <button
                type="button"
                @click="toggleFavorite()"
                :class="favorited ? 'text-rose-500' : 'text-muted/60 hover:text-rose-500'"
                class="ml-auto rounded p-1.5 hover:bg-surface"
                :aria-label="favorited ? 'Remove from favorites' : 'Add to favorites'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" :fill="favorited ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.5-9-8.7C1.6 9.7 3.5 6 7 6c2 0 3.5 1.2 4.5 2.5C13 7.2 14.5 6 16.5 6c3.5 0 5.4 3.7 4 6.3-2 4.2-9 8.7-9 8.7z"/>
                </svg>
            </button>
            <button
                type="button"
                @click="collectionModalOpen = true"
                :class="familyCollectionCount() > 0 ? 'text-accent' : 'text-muted/60 hover:text-accent'"
                class="focus-ring rounded p-1.5 hover:bg-surface"
                :aria-label="'Save to collection'"
                :title="familyCollectionCount() > 0 ? `In ${familyCollectionCount()} collection${familyCollectionCount() === 1 ? '' : 's'}` : 'Save to collection'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" :fill="familyCollectionCount() > 0 ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16l-7-3-7 3z"/>
                </svg>
            </button>
            <button
                type="button"
                @click="notesOpen = !notesOpen"
                :class="$store.notes.hasAnything(familyName) ? 'text-accent' : 'text-muted/60 hover:text-accent'"
                class="focus-ring rounded p-1.5 hover:bg-surface"
                aria-label="Notes & tags"
                :title="$store.notes.hasAnything(familyName) ? 'Has notes / tags' : 'Add notes / tags'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.5 3.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 8.5-8.5z"/>
                </svg>
            </button>
        </div>
        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
            <span>{{ $family->category }}</span>
            <span>{{ $family->file_count }} {{ Str::plural('style', $family->file_count) }}</span>
            @if (!empty($family->designers))
                <span>By {{ implode(', ', $family->designers) }}</span>
            @endif
            @if ($family->date_added)
                <span>Added {{ $family->date_added->format('Y') }}</span>
            @endif
        </div>
        @if (!empty($family->subsets))
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach ($family->subsets as $subset)
                    <span class="rounded-full bg-surface px-2 py-0.5 text-xs text-muted">{{ $subset }}</span>
                @endforeach
            </div>
        @endif
    </header>

    {{-- Notes & tags panel (collapsible) --}}
    <section x-show="notesOpen" x-cloak x-transition.opacity class="rounded-lg border border-border-soft bg-surface/50 p-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-medium uppercase tracking-wide text-muted">Notes &amp; tags</h2>
            <button @click="notesOpen = false" class="focus-ring rounded text-xs text-muted hover:text-fg">Close</button>
        </div>
        <textarea
            :value="$store.notes.getNote(familyName)"
            @input="$store.notes.setNote(familyName, $event.target.value)"
            rows="3"
            placeholder="e.g. Client A approved · use for editorial body · avoid for headlines"
            class="focus-ring block w-full resize-y rounded-md border border-border bg-bg px-3 py-2 text-sm leading-snug theme-aware"
        ></textarea>

        <div class="mt-3">
            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-muted">Tags</label>
            <div class="flex flex-wrap items-center gap-1.5">
                <template x-for="tag in $store.notes.getTags(familyName)" :key="tag">
                    <span class="group inline-flex items-center gap-1 rounded-full bg-accent/10 px-2 py-0.5 text-xs text-accent">
                        <span x-text="`#${tag}`"></span>
                        <button @click="$store.notes.removeTag(familyName, tag)" class="text-accent/70 hover:text-accent" aria-label="Remove">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3 w-3"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </span>
                </template>
                <input
                    type="text"
                    x-model="newTag"
                    @keydown.enter.prevent="$store.notes.addTag(familyName, newTag); newTag = '';"
                    @keydown.comma.prevent="$store.notes.addTag(familyName, newTag); newTag = '';"
                    placeholder="Add tag + Enter"
                    class="focus-ring rounded-full border border-border-soft bg-bg px-2.5 py-0.5 text-xs theme-aware"
                >
            </div>
            <p class="mt-2 text-xs text-muted/70">Search any font with <code class="rounded bg-bg px-1 font-mono text-[10px]">#tag-name</code> in the search bar.</p>
        </div>
    </section>

    {{-- Sample / mockup contexts (sticky while scrolling through styles) --}}
    <section class="sticky top-16 z-20 -mx-6 space-y-3 border-b border-border-soft bg-bg/95 px-6 py-3 backdrop-blur theme-aware">
        {{-- Mockup tabs --}}
        <div class="flex flex-wrap items-center gap-1">
            <template x-for="m in mockupOptions" :key="m.id">
                <button
                    type="button"
                    @click="mockup = m.id"
                    :class="mockup === m.id ? 'border-fg bg-fg text-bg' : 'border-border-soft text-muted hover:bg-surface hover:text-fg'"
                    class="focus-ring rounded-full border px-2.5 py-1 text-[11px] theme-aware"
                    x-text="m.label"
                ></button>
            </template>
            <span class="ml-auto text-xs text-muted/60">
                <span class="hidden md:inline">Tab</span> to switch
            </span>
        </div>

        {{-- Sample input (only for "Sample" mode, otherwise mockup uses preset content) --}}
        <textarea
            x-show="mockup === 'sample'"
            x-model="text"
            rows="2"
            @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
            x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'; })"
            class="focus-ring block w-full resize-none overflow-hidden rounded-md border border-border bg-bg px-3 py-2 text-sm leading-snug theme-aware"
            placeholder="Type something to preview..."
        ></textarea>
        <div x-show="mockup === 'sample'" class="flex flex-wrap items-center gap-2">
            <template x-for="preset in samplePresets" :key="preset.label">
                <button
                    type="button"
                    @click="text = preset.value"
                    :class="text === preset.value ? 'border-fg bg-fg text-bg' : 'border-border-soft text-muted hover:bg-surface hover:text-fg'"
                    class="focus-ring rounded-full border px-2.5 py-0.5 text-[11px] theme-aware"
                    x-text="preset.label"
                ></button>
            </template>
        </div>

        {{-- Universal controls --}}
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-muted">
            <label class="flex items-center gap-2" x-show="mockup === 'sample'">
                <span class="text-xs uppercase tracking-wide text-muted">Size</span>
                <input type="range" min="14" max="200" x-model.number="size" class="w-48">
                <span class="tabular w-14 text-right text-xs" x-text="size + 'px'"></span>
            </label>
            @if ($hasItalicFile || ($hasSlntAxis && $variableFiles->isNotEmpty()))
                <label class="flex items-center gap-2">
                    <input type="checkbox" x-model="italic" class="h-4 w-4 rounded border-border">
                    <span>Italic</span>
                </label>
            @endif
            <button
                type="button"
                @click="otOpen = !otOpen"
                :class="otOpen || hasActiveOt ? 'text-fg' : 'text-muted hover:text-fg'"
                class="focus-ring rounded text-xs underline-offset-2 hover:underline"
            >
                <span x-show="!hasActiveOt">OpenType features</span>
                <span x-show="hasActiveOt" x-text="`OpenType (${activeOtCount})`"></span>
            </button>
            <button type="button" @click="reset" class="focus-ring rounded text-xs text-muted underline-offset-2 hover:text-fg hover:underline">
                Reset
            </button>
        </div>

        {{-- OpenType features panel --}}
        <div x-show="otOpen" x-cloak x-transition.opacity class="rounded-md border border-border-soft bg-bg p-3">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wide text-muted">OpenType features</span>
                <button @click="resetOt()" class="focus-ring text-xs text-muted hover:text-fg">Reset</button>
            </div>
            <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-3 md:grid-cols-4">
                <template x-for="feat in otFeatureList" :key="feat.tag">
                    <label class="flex cursor-pointer items-center gap-2 rounded px-1.5 py-1 text-xs hover:bg-surface" :title="feat.description">
                        <input type="checkbox" :checked="otFeatures[feat.tag]" @change="otFeatures[feat.tag] = $event.target.checked" class="h-3.5 w-3.5 rounded border-border text-accent focus:ring-accent">
                        <span class="font-mono text-[10px] text-muted" x-text="feat.tag"></span>
                        <span x-text="feat.label"></span>
                    </label>
                </template>
            </div>
            <p class="mt-2 text-[10px] text-muted/70">Browser ignores features the font doesn't support.</p>
        </div>
    </section>

    {{-- Context-aware preview ────────────────────────────── --}}
    @php
        $useItalicSrc = $variableItalic ? 'true' : 'false';
        $previewStyleBase = "font-family: '{$family->family}', sans-serif; font-style: \${italic && {$useItalicSrc} ? 'italic' : 'normal'}; font-variation-settings: \${variationSettings}; font-feature-settings: \${otFeaturesString};";
    @endphp

    <section class="rounded-lg border border-border-soft p-6">
        {{-- Sample (free text + size slider) --}}
        <p
            x-show="mockup === 'sample'"
            class="break-words leading-snug text-fg"
            :style="`{{ $previewStyleBase }} font-size: ${size}px;`"
            x-text="text || @js($family->family)"
        ></p>

        {{-- Article: headline + body paragraphs + caption --}}
        <div x-show="mockup === 'article'" x-cloak class="space-y-5" :style="`{{ $previewStyleBase }}`">
            <p class="text-xs uppercase tracking-widest text-muted" :style="`font-feature-settings: ${otFeaturesString};`">Long read · 8 min</p>
            <h1 class="text-fg" style="font-size: 56px; line-height: 1.05; letter-spacing: -0.015em;">{{ Str::limit('How typography becomes a quiet conversation between writer and reader.', 90) }}</h1>
            <p class="text-fg" style="font-size: 18px; line-height: 1.6;">
                Reading is more than recognising shapes. Each glyph carries voice — its weight, its width, the subtle tension of its curves. A well-chosen typeface fades into the page until only meaning remains, and the words begin to sound like the writer intended.
            </p>
            <p class="text-fg" style="font-size: 18px; line-height: 1.6;">
                The best typography rewards close attention without demanding it. Spacing settles into rhythm, characters behave as a family, and small details — proper italic, real small caps, restrained ligatures — gather into something quietly assured.
            </p>
            <p class="text-muted" style="font-size: 13px; letter-spacing: 0.02em;">— By a designer, sometime in 2026</p>
        </div>

        {{-- Poster: massive single line --}}
        <div x-show="mockup === 'poster'" x-cloak class="flex flex-col items-start gap-2" :style="`{{ $previewStyleBase }}`">
            <p class="text-fg" style="font-size: clamp(80px, 14vw, 200px); line-height: 0.95; letter-spacing: -0.04em; font-weight: 800;">Type.</p>
            <p class="text-muted" style="font-size: 18px; letter-spacing: 0.04em; text-transform: uppercase;">{{ $family->family }} · all year, every weight</p>
        </div>

        {{-- UI: small label + button + form --}}
        <div x-show="mockup === 'ui'" x-cloak class="space-y-4" :style="`{{ $previewStyleBase }}`">
            <div class="rounded-lg border border-border-soft p-5" style="font-size: 14px;">
                <p class="mb-1 font-medium text-fg" style="font-size: 18px;">Sign in to your account</p>
                <p class="text-muted" style="font-size: 13px;">Welcome back. Enter your email to continue.</p>
                <label class="mt-4 block text-xs font-medium uppercase tracking-wide text-muted">Email</label>
                <input type="email" placeholder="you@example.com" readonly class="mt-1 w-full rounded-md border border-border bg-bg px-3 py-2 text-sm theme-aware">
                <button type="button" class="mt-3 w-full rounded-md bg-fg px-3 py-2 text-sm font-medium text-bg">Continue</button>
                <p class="mt-3 text-center text-xs text-muted">By continuing you agree to our <span class="underline">terms</span>.</p>
            </div>
        </div>

        {{-- Code: monospace pseudo-code --}}
        <div x-show="mockup === 'code'" x-cloak :style="`{{ $previewStyleBase }}`">
            <pre class="overflow-x-auto rounded-lg bg-surface p-5 text-fg" style="font-size: 14px; line-height: 1.65;"><code>{{ "function findFont(query) {
    const matches = families
        .filter(f => f.name.includes(query))
        .sort((a, b) => a.popularity - b.popularity)
        .slice(0, 10);

    return matches.length > 0
        ? matches
        : { error: 'No fonts found', code: 404 };
}

// Try it: findFont('Sans')" }}</code></pre>
        </div>

        {{-- Logo: brand mark --}}
        <div x-show="mockup === 'logo'" x-cloak class="flex flex-col items-start gap-3 py-6" :style="`{{ $previewStyleBase }}`">
            <p class="text-fg" style="font-size: clamp(56px, 10vw, 120px); line-height: 1; letter-spacing: -0.02em; font-weight: 700;">Acme &amp; Co.</p>
            <p class="text-muted" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.3em;">Est. 2026 · Type-driven brand</p>
        </div>
    </section>

    @if ($variableFiles->isNotEmpty() && !empty($family->axes))
        {{-- Variable axes (sliders + install) --}}
        <section class="rounded-lg border border-border-soft bg-surface/50 p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-medium uppercase tracking-wide text-muted">Variable axes</h2>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($variableFiles as $vf)
                        <button
                            type="button"
                            @click="installFont({{ $vf->id }})"
                            :disabled="installing[{{ $vf->id }}] || installed[{{ $vf->id }}]"
                            :class="installed[{{ $vf->id }}] ? 'border-emerald-500/60 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'border-border bg-bg hover:bg-surface'"
                            class="focus-ring rounded border px-2 py-1 text-xs disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <span x-show="!installing[{{ $vf->id }}] && !installed[{{ $vf->id }}]">
                                Install {{ $vf->style === 'italic' ? 'italic' : 'variable' }} to Windows
                            </span>
                            <span x-show="installing[{{ $vf->id }}]">Installing…</span>
                            <span x-show="installed[{{ $vf->id }}]">✓ Installed</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($family->axes as $axis)
                    @php $reg = $axisRegistry[$axis['tag']] ?? null; @endphp
                    <label class="flex items-center gap-3 text-sm">
                        <div class="w-28 shrink-0">
                            <div class="font-medium" @if($reg && !empty($reg['description'])) title="{{ $reg['description'] }}" @endif>
                                {{ $reg['displayName'] ?? $axis['tag'] }}
                            </div>
                            <div class="text-xs uppercase tracking-wide text-muted">{{ $axis['tag'] }}</div>
                        </div>
                        <input
                            type="range"
                            min="{{ $axis['min'] }}"
                            max="{{ $axis['max'] }}"
                            step="1"
                            x-model.number="axes['{{ $axis['tag'] }}']"
                            class="flex-1"
                        >
                        <span class="tabular w-14 text-right text-xs text-muted" x-text="axes['{{ $axis['tag'] }}']"></span>
                    </label>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Pairings ─────────────────────────────────────────── --}}
    @if (!empty($pairings))
        <section class="rounded-lg border border-border-soft p-6">
            <h2 class="mb-4 text-sm font-medium uppercase tracking-wide text-muted">Pairs well with</h2>
            <div class="space-y-5">
                @foreach ($pairings as $label => $list)
                    @if ($list->isNotEmpty())
                        <div>
                            <h3 class="mb-2 text-xs font-medium text-muted">{{ $label }}</h3>
                            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                                @foreach ($list as $sug)
                                    <a
                                        href="{{ route('fonts.show', strtolower(str_replace(' ', '-', $sug->family))) }}"
                                        class="card-hover focus-ring rounded-lg border border-border-soft bg-bg p-4 hover:border-border"
                                    >
                                        <p class="text-sm font-medium text-fg">{{ $sug->family }}</p>
                                        <p class="mt-0.5 text-xs text-muted">{{ $sug->category }}</p>
                                        <p class="mt-3 break-words leading-tight text-fg" style="font-family: '{{ $sug->family }}', sans-serif; font-size: 24px;">Aa Bb 123</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            {{-- Inject @font-face for pairing samples so they render correctly --}}
            <style>
                @foreach (collect($pairings)->flatten(1) as $sug)
                    @foreach ($sug->fontFiles->take(1) as $sf)
                    @font-face {
                        font-family: '{{ $sug->family }}';
                        src: url('{{ route('fonts.serve', $sf) }}') format('truetype{{ $sf->is_variable ? '-variations' : '' }}');
                        font-weight: {{ $sf->is_variable ? '100 900' : ($sf->weight ?? 400) }};
                        font-style: {{ $sf->style }};
                        font-display: swap;
                    }
                    @endforeach
                @endforeach
            </style>
        </section>
    @endif

    {{-- Glyphs ───────────────────────────────────────────── --}}
    @php
        $glyphGroups = [
            'Uppercase'           => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'Lowercase'           => 'abcdefghijklmnopqrstuvwxyz',
            'Numbers'             => '0123456789',
            'Indonesian / Latin'  => 'áàâäãéèêëíìîïóòôöõúùûüñçÁÀÂÄÃÉÈÊËÍÌÎÏÓÒÔÖÕÚÙÛÜÑÇ',
            'Punctuation'         => '. , ; : ! ? \' " ` ´ ‘ ’ “ ” « » ‹ › — – … • · ¡ ¿',
            'Currency'            => '$ € £ ¥ ₹ ₪ ₫ ₱ ₿ ¢ ¤ ƒ',
            'Math'                => '+ − × ÷ = ≠ ≤ ≥ ± ≈ ∞ √ ∫ ∑ ∏ ∂ ∆ µ',
            'Symbols'             => '© ® ™ § ¶ † ‡ ° % ‰ ★ ♥ ♦ ♣ ♠ ☆ ✓ ✗ ←  → ↑ ↓',
        ];
    @endphp
    <section class="rounded-lg border border-border-soft p-6">
        <h2 class="mb-4 text-sm font-medium uppercase tracking-wide text-muted">Glyphs</h2>
        <div class="space-y-5" :style="`font-family: '{{ $family->family }}', sans-serif; font-feature-settings: ${otFeaturesString};`">
            @foreach ($glyphGroups as $group => $chars)
                <div>
                    <h3 class="mb-2 text-xs font-medium text-muted">{{ $group }}</h3>
                    <div class="flex flex-wrap gap-3 text-fg" style="font-size: 28px; line-height: 1.4;">{{ $chars }}</div>
                </div>
            @endforeach
        </div>
        <p class="mt-4 text-xs text-muted/70">Glyphs missing from this family render in the fallback font (and look out of place).</p>
    </section>

    {{-- Specimen --}}
    <section x-show="showSpecimen" x-cloak class="rounded-lg border border-border-soft p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-medium uppercase tracking-wide text-muted">Type specimen</h2>
            <button type="button" @click="showSpecimen = false" class="text-xs text-muted hover:text-fg">Hide</button>
        </div>
        <div class="space-y-3 leading-tight" :style="`font-family: '{{ $family->family }}', sans-serif;`">
            <div :style="`font-size: ${Math.max(28, size * 0.9)}px;`">ABCDEFGHIJKLMNOPQRSTUVWXYZ</div>
            <div :style="`font-size: ${Math.max(28, size * 0.9)}px;`">abcdefghijklmnopqrstuvwxyz</div>
            <div :style="`font-size: ${Math.max(28, size * 0.9)}px;`">0123456789</div>
            <div :style="`font-size: ${Math.max(20, size * 0.6)}px;`">!@#$%^&amp;*()_+-=[]{}|;':",./&lt;&gt;?~`</div>
            <div class="pt-4 border-t border-border-soft" :style="`font-size: ${size}px;`" x-text="pangrams[0]"></div>
            <div :style="`font-size: ${Math.round(size * 0.65)}px;`" x-text="pangrams[1]"></div>
            <div :style="`font-size: ${Math.round(size * 0.45)}px;`" x-text="pangrams[2]"></div>
        </div>
    </section>
    <button type="button" x-show="!showSpecimen" @click="showSpecimen = true" class="text-xs text-muted underline-offset-2 hover:text-fg hover:underline">
        Show type specimen
    </button>

    {{-- Collection picker modal --}}
    <div
        x-show="collectionModalOpen"
        x-cloak
        @keydown.escape.window="collectionModalOpen = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-fg/40 p-4"
        @click.self="collectionModalOpen = false"
    >
        <div class="w-full max-w-md rounded-lg border border-border-soft bg-bg p-6 shadow-xl">
            <h2 class="text-base font-medium">Save to collection</h2>
            <p class="mt-0.5 truncate text-sm text-muted" x-text="familyName"></p>

            <ul x-show="collections.length" class="mt-4 max-h-64 space-y-1 overflow-y-auto">
                <template x-for="col in collections" :key="col.id">
                    <li>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-surface">
                            <input
                                type="checkbox"
                                :checked="isInCollection(col.id)"
                                @change="toggleInCollection(col.id)"
                                class="h-4 w-4 rounded border-border text-accent focus:ring-accent"
                            >
                            <span class="truncate" x-text="col.name"></span>
                            <span class="ml-auto text-xs text-muted" x-text="col.fonts.length"></span>
                        </label>
                    </li>
                </template>
            </ul>
            <p x-show="!collections.length" class="mt-4 text-sm text-muted">
                No collections yet. Create one below.
            </p>

            <div class="mt-4 border-t border-border-soft pt-4">
                <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-muted">
                    New collection
                </label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        x-model="newCollectionName"
                        @keydown.enter.prevent="createAndAdd()"
                        placeholder="Collection name"
                        class="flex-1 rounded-md border border-border px-3 py-1.5 text-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                    >
                    <button
                        type="button"
                        @click="createAndAdd()"
                        :disabled="!newCollectionName.trim()"
                        class="rounded-md bg-fg px-3 py-1.5 text-xs text-bg hover:bg-fg/90 disabled:cursor-not-allowed disabled:opacity-40"
                    >Create</button>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    @click="collectionModalOpen = false"
                    class="rounded-md border border-border px-3 py-1.5 text-sm hover:bg-surface"
                >Done</button>
            </div>
        </div>
    </div>

    @if ($staticFiles->isNotEmpty())
        {{-- Static styles --}}
        <section>
            <h2 class="mb-3 text-sm font-medium uppercase tracking-wide text-muted">
                @if ($variableFiles->isNotEmpty()) Static styles @else Styles @endif
            </h2>
            <div class="divide-y divide-border-soft">
                @foreach ($staticFiles as $file)
                    <div
                        class="grid grid-cols-12 items-baseline gap-4 py-5"
                        x-show="italic ? '{{ $file->style }}' === 'italic' : '{{ $file->style }}' === 'normal'"
                    >
                        <div class="col-span-12 md:col-span-2">
                            <div class="text-sm font-medium">{{ $file->subfamily ?: 'Regular' }}</div>
                            <div class="text-xs text-muted">
                                {{ $file->weight ?? '?' }}{{ $file->style === 'italic' ? ' italic' : '' }}
                            </div>
                            <div class="mt-2 flex flex-col items-start gap-1">
                                <button
                                    type="button"
                                    @click="installFont({{ $file->id }})"
                                    :disabled="installing[{{ $file->id }}] || installed[{{ $file->id }}]"
                                    :class="installed[{{ $file->id }}] ? 'border-emerald-500/60 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'border-border hover:bg-surface'"
                                    class="rounded border px-2 py-0.5 text-xs disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <span x-show="!installing[{{ $file->id }}] && !installed[{{ $file->id }}]">Install to Windows</span>
                                    <span x-show="installing[{{ $file->id }}]">Installing…</span>
                                    <span x-show="installed[{{ $file->id }}]">✓ Installed</span>
                                </button>
                                <span x-show="installError[{{ $file->id }}]" x-text="installError[{{ $file->id }}]" class="text-xs text-rose-600"></span>
                                <a
                                    href="{{ route('fonts.serve', $file) }}"
                                    download="{{ $file->filename }}"
                                    class="text-xs text-muted underline-offset-2 hover:text-fg hover:underline"
                                >
                                    Download .ttf
                                </a>
                            </div>
                        </div>
                        <p
                            class="col-span-12 break-words leading-snug text-fg md:col-span-10"
                            :style="`font-family: '{{ $family->family }}', sans-serif; font-size: ${size}px; font-weight: {{ $file->weight ?? 400 }}; font-style: {{ $file->style }};`"
                            x-text="text || @js($family->family)"
                        ></p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

</article>

@push('head')
<style>[x-cloak]{display:none!important}</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('detail', () => ({
        text: 'The quick brown fox jumps over the lazy dog',
        size: 64,
        italic: false,
        axes: @json((object) $axesInit),
        showSpecimen: false,
        familyName: @json($family->family),
        favorited: false,
        installing: {},
        installed: @json((object) $installedFiles),
        installError: {},
        collections: JSON.parse(localStorage.getItem('gfonts.collections') || '[]'),
        collectionModalOpen: false,
        newCollectionName: '',
        notesOpen: false,
        newTag: '',
        mockup: 'sample',
        mockupOptions: [
            { id: 'sample',  label: 'Sample' },
            { id: 'article', label: 'Article' },
            { id: 'poster',  label: 'Poster' },
            { id: 'ui',      label: 'UI' },
            @if (str_contains(strtolower($family->category), 'mono'))
            { id: 'code',    label: 'Code' },
            @endif
            { id: 'logo',    label: 'Logo' },
        ],
        otOpen: false,
        otFeatures: {},
        otFeatureList: [
            { tag: 'liga', label: 'Standard ligatures', description: 'Common letter combinations like fi, fl' },
            { tag: 'dlig', label: 'Discretionary ligatures', description: 'Decorative optional ligatures' },
            { tag: 'smcp', label: 'Small caps',          description: 'Convert lowercase to small caps' },
            { tag: 'c2sc', label: 'Caps to small caps',  description: 'Convert UPPERCASE to small caps' },
            { tag: 'salt', label: 'Stylistic alternates', description: 'Alternate glyph shapes' },
            { tag: 'ss01', label: 'Stylistic set 1',     description: 'Designer\'s alt set 1' },
            { tag: 'ss02', label: 'Stylistic set 2',     description: 'Designer\'s alt set 2' },
            { tag: 'ss03', label: 'Stylistic set 3',     description: 'Designer\'s alt set 3' },
            { tag: 'tnum', label: 'Tabular numerals',    description: 'Same-width digits, good for tables' },
            { tag: 'onum', label: 'Old-style figures',   description: 'Lowercase-style numerals' },
            { tag: 'lnum', label: 'Lining figures',      description: 'Uppercase-height numerals' },
            { tag: 'frac', label: 'Fractions',           description: 'Replace 1/2 with proper fraction glyph' },
            { tag: 'sups', label: 'Superscript',         description: '' },
            { tag: 'sinf', label: 'Subscript',           description: '' },
            { tag: 'kern', label: 'Kerning',             description: 'Pair-specific spacing (usually on by default)' },
        ],
        samplePresets: [
            { label: 'Pangram', value: 'The quick brown fox jumps over the lazy dog' },
            { label: 'Numbers', value: '0 1 2 3 4 5 6 7 8 9' },
            { label: 'Caps',    value: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' },
            { label: 'Symbols', value: '! ? @ # $ % & * ( ) — “ ” ' },
        ],

        get hasActiveOt() {
            return Object.values(this.otFeatures).some(v => v);
        },
        get activeOtCount() {
            return Object.values(this.otFeatures).filter(v => v).length;
        },
        get otFeaturesString() {
            const enabled = Object.entries(this.otFeatures)
                .filter(([_, on]) => on)
                .map(([feat]) => `"${feat}"`)
                .join(', ');
            return enabled || 'normal';
        },
        resetOt() {
            for (const k in this.otFeatures) this.otFeatures[k] = false;
            this.otFeatureList.forEach(f => { this.otFeatures[f.tag] = false; });
        },
        pangrams: [
            'The quick brown fox jumps over the lazy dog.',
            'Sphinx of black quartz, judge my vows.',
            'Pack my box with five dozen liquor jugs. How vexingly quick daft zebras jump!',
        ],

        init() {
            const favs = JSON.parse(localStorage.getItem('gfonts.favorites') || '[]');
            this.favorited = favs.includes(this.familyName);

            // Initialize OT feature flags from list
            this.otFeatureList.forEach(f => { this.otFeatures[f.tag] = false; });

            // Push to recently viewed
            this.$store.recent.push(this.familyName);
        },

        toggleFavorite() {
            const favs = JSON.parse(localStorage.getItem('gfonts.favorites') || '[]');
            const i = favs.indexOf(this.familyName);
            if (i >= 0) favs.splice(i, 1);
            else favs.push(this.familyName);
            localStorage.setItem('gfonts.favorites', JSON.stringify(favs));
            this.favorited = !this.favorited;
        },

        // ─── Collections (this family only) ──────────────────
        saveCollections() {
            localStorage.setItem('gfonts.collections', JSON.stringify(this.collections));
        },
        isInCollection(colId) {
            const c = this.collections.find(x => x.id === colId);
            return c ? c.fonts.includes(this.familyName) : false;
        },
        toggleInCollection(colId) {
            const c = this.collections.find(x => x.id === colId);
            if (!c) return;
            const i = c.fonts.indexOf(this.familyName);
            if (i >= 0) c.fonts.splice(i, 1);
            else c.fonts.push(this.familyName);
            this.saveCollections();
        },
        createAndAdd() {
            const name = this.newCollectionName.trim();
            if (!name) return;
            const id = 'col_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 6);
            this.collections.push({ id, name, fonts: [this.familyName] });
            this.saveCollections();
            this.newCollectionName = '';
        },
        familyCollectionCount() {
            return this.collections.filter(c => c.fonts.includes(this.familyName)).length;
        },

        async installFont(fileId) {
            this.installing = { ...this.installing, [fileId]: true };
            this.installError = { ...this.installError, [fileId]: null };
            try {
                const token = document.querySelector('meta[name=csrf-token]').content;
                const res = await fetch(`/font-file/${fileId}/install`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (res.ok && (data.status === 'installed' || data.status === 'already_installed')) {
                    this.installed = { ...this.installed, [fileId]: true };
                    const msg = data.status === 'already_installed'
                        ? `${data.filename} was already installed`
                        : `${data.filename} installed`;
                    this.$store.toast.success(msg);
                } else {
                    const errMsg = data.error || `HTTP ${res.status}`;
                    this.installError = { ...this.installError, [fileId]: errMsg };
                    this.$store.toast.error(`Install failed: ${errMsg}`);
                }
            } catch (e) {
                this.installError = { ...this.installError, [fileId]: e.message };
                this.$store.toast.error(`Install failed: ${e.message}`);
            } finally {
                const next = { ...this.installing };
                delete next[fileId];
                this.installing = next;
            }
        },

        get variationSettings() {
            const parts = Object.entries(this.axes).map(([tag, val]) => `"${tag}" ${val}`);
            // If italic toggle is on but family has no italic file (only slnt axis),
            // override slnt to its min (typically -10) to simulate italic.
            @if ($hasSlntAxis && !$variableItalic)
            if (this.italic) {
                const slntAxis = @json(collect($family->axes)->firstWhere('tag','slnt'));
                if (slntAxis) {
                    const i = parts.findIndex(p => p.startsWith('"slnt"'));
                    if (i >= 0) parts[i] = `"slnt" ${slntAxis.min}`;
                }
            }
            @endif
            return parts.join(', ');
        },

        reset() {
            this.text = 'The quick brown fox jumps over the lazy dog';
            this.size = 64;
            this.italic = false;
            this.axes = @json((object) $axesInit);
        },
    }));
});
</script>
@endpush
@endsection
