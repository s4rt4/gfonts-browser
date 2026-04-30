@extends('layouts.app')

@section('alpineRoot', 'detail()')

@section('title', $family->family . ' — ' . config('app.name'))

@section('header')
<a href="{{ route('fonts.index') }}" class="text-xs text-muted hover:text-fg">
    ← All fonts
</a>
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
<article class="mx-auto max-w-6xl space-y-8" @keydown.escape.window="window.location.href = '{{ route('fonts.index') }}'">

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
                class="rounded p-1.5 hover:bg-surface"
                :aria-label="'Save to collection'"
                :title="familyCollectionCount() > 0 ? `In ${familyCollectionCount()} collection${familyCollectionCount() === 1 ? '' : 's'}` : 'Save to collection'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" :fill="familyCollectionCount() > 0 ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16l-7-3-7 3z"/>
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

    {{-- Sample text editor --}}
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
                <input type="range" min="14" max="200" x-model.number="size" class="w-48">
                <span class="w-14 text-right tabular-nums" x-text="size + 'px'"></span>
            </label>
            @if ($hasItalicFile || ($hasSlntAxis && $variableFiles->isNotEmpty()))
                <label class="flex items-center gap-2">
                    <input type="checkbox" x-model="italic" class="h-4 w-4 rounded border-border">
                    <span>Italic</span>
                </label>
            @endif
            <button type="button" @click="reset" class="text-xs text-muted underline-offset-2 hover:text-fg hover:underline">
                Reset
            </button>
        </div>
    </section>

    @if ($variableFiles->isNotEmpty() && !empty($family->axes))
        {{-- Variable preview with axis sliders --}}
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
                            class="rounded border px-2 py-1 text-xs disabled:cursor-not-allowed disabled:opacity-70"
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

            <div class="mb-6 grid gap-4 md:grid-cols-2">
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
                        <span class="w-14 text-right text-xs tabular-nums text-muted" x-text="axes['{{ $axis['tag'] }}']"></span>
                    </label>
                @endforeach
            </div>

            @php
                $useItalicSrc = $variableItalic ? 'true' : 'false';
            @endphp
            <p
                class="break-words leading-snug text-fg"
                :style="`font-family: '{{ $family->family }}', sans-serif; font-size: ${size}px; font-style: ${italic && {{ $useItalicSrc }} ? 'italic' : 'normal'}; font-variation-settings: ${variationSettings};`"
                x-text="text || @js($family->family)"
            ></p>
        </section>
    @endif

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
        pangrams: [
            'The quick brown fox jumps over the lazy dog.',
            'Sphinx of black quartz, judge my vows.',
            'Pack my box with five dozen liquor jugs. How vexingly quick daft zebras jump!',
        ],

        init() {
            const favs = JSON.parse(localStorage.getItem('gfonts.favorites') || '[]');
            this.favorited = favs.includes(this.familyName);
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
                } else {
                    this.installError = { ...this.installError, [fileId]: data.error || `HTTP ${res.status}` };
                }
            } catch (e) {
                this.installError = { ...this.installError, [fileId]: e.message };
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
