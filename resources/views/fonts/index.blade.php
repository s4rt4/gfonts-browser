@extends('layouts.app')

@section('alpineRoot', 'browser()')

@section('header')
    <div class="relative w-full max-w-2xl">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-muted">
            <circle cx="11" cy="11" r="7"/>
            <path stroke-linecap="round" d="M20 20l-3.5-3.5"/>
        </svg>
        <input
            type="search"
            x-model.debounce.150ms="search"
            placeholder="Search {{ number_format($totalCount) }} fonts"
            class="focus-ring w-full rounded-full border border-border bg-bg py-2 pl-10 pr-16 text-sm theme-aware"
        >
        <kbd
            x-show="!search"
            class="pointer-events-none absolute right-4 top-1/2 hidden -translate-y-1/2 rounded border border-border-soft bg-surface px-1.5 py-0.5 font-mono text-[10px] text-muted md:block"
        >/</kbd>
        <button
            x-show="search"
            type="button"
            @click="search = ''"
            class="focus-ring absolute right-3 top-1/2 -translate-y-1/2 rounded-full p-1 text-muted hover:bg-surface hover:text-fg"
            aria-label="Clear search"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3 w-3">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>
    </div>
@endsection

@section('content')
<div>
    <div class="flex flex-col gap-8 lg:flex-row">
        {{-- Sidebar --}}
        <aside class="w-full shrink-0 lg:w-56">
            <div class="thin-scrollbar space-y-6 lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto lg:pr-2">
                <div>
                    <label class="block text-xs font-medium uppercase tracking-wide text-muted mb-1.5">
                        Preview text
                    </label>
                    <textarea
                        x-model="previewText"
                        rows="3"
                        @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                        x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'; })"
                        class="focus-ring block w-full resize-none overflow-hidden rounded-md border border-border bg-bg px-3 py-2 text-sm leading-snug theme-aware"
                    ></textarea>
                    <div class="mt-2 flex flex-wrap gap-1">
                        <template x-for="preset in samplePresets" :key="preset.label">
                            <button
                                type="button"
                                @click="previewText = preset.value"
                                :class="previewText === preset.value ? 'border-fg bg-fg text-bg' : 'border-border-soft text-muted hover:bg-surface hover:text-fg'"
                                class="focus-ring rounded-full border px-2 py-0.5 text-[11px] theme-aware"
                                x-text="preset.label"
                            ></button>
                        </template>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-xs text-muted">Size</span>
                        <input
                            type="range"
                            min="14"
                            max="96"
                            x-model.number="previewSize"
                            class="flex-1"
                        >
                        <span class="tabular w-10 text-right text-xs text-muted" x-text="previewSize + 'px'"></span>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wide text-muted mb-2">Categories</h3>
                    <ul class="space-y-1">
                        <template x-for="cat in categories" :key="cat">
                            <li>
                                <label class="flex cursor-pointer items-center gap-2 rounded px-1 py-0.5 text-sm hover:bg-surface">
                                    <input
                                        type="checkbox"
                                        :value="cat"
                                        x-model="selectedCategories"
                                        class="h-3.5 w-3.5 rounded border-border text-fg focus:ring-accent"
                                    >
                                    <span x-text="cat"></span>
                                    <span class="ml-auto text-xs text-muted" x-text="categoryCount(cat)"></span>
                                </label>
                            </li>
                        </template>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wide text-muted mb-2">Sort by</h3>
                    <ul class="space-y-1">
                        <template x-for="opt in sortOptions" :key="opt.value">
                            <li>
                                <label class="flex cursor-pointer items-center gap-2 rounded px-1 py-0.5 text-sm hover:bg-surface">
                                    <input
                                        type="radio"
                                        :value="opt.value"
                                        x-model="sort"
                                        class="h-3.5 w-3.5 border-border text-fg focus:ring-accent"
                                    >
                                    <span x-text="opt.label"></span>
                                </label>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="space-y-2">
                    <h3 class="text-xs font-medium uppercase tracking-wide text-muted">Quick</h3>
                    <label class="flex cursor-pointer items-center gap-2 rounded px-1 py-0.5 text-sm hover:bg-surface">
                        <input type="checkbox" x-model="showFavoritesOnly" class="h-3.5 w-3.5 rounded border-border accent-rose-500 focus:ring-rose-500">
                        <span>Favorites only</span>
                        <span class="ml-auto text-xs text-muted" x-text="favorites.length"></span>
                    </label>
                    <button
                        type="button"
                        @click="compareMode = !compareMode; if (!compareMode) compareSelection = [];"
                        :class="compareMode ? 'border-fg bg-fg text-bg' : 'border-border text-fg hover:bg-surface'"
                        class="w-full rounded-md border px-2 py-1 text-xs"
                        x-text="compareMode ? 'Cancel compare' : 'Compare fonts'"
                    ></button>
                </div>

                {{-- Tags (notes-derived) --}}
                <div class="space-y-2" x-show="$store.notes.allTags().length">
                    <h3 class="text-xs font-medium uppercase tracking-wide text-muted">Tags</h3>
                    <div class="flex flex-wrap gap-1">
                        <template x-for="[tag, count] in $store.notes.allTags()" :key="tag">
                            <button
                                type="button"
                                @click="search = (search + ' #' + tag).trim()"
                                class="focus-ring inline-flex items-center gap-1 rounded-full border border-border-soft px-2 py-0.5 text-[11px] text-muted hover:border-accent/40 hover:bg-accent/5 hover:text-accent"
                                :title="`${count} font${count === 1 ? '' : 's'} tagged`"
                            >
                                <span x-text="`#${tag}`"></span>
                                <span class="text-muted/70" x-text="count"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Collections --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-medium uppercase tracking-wide text-muted">Collections</h3>
                        <button
                            type="button"
                            @click="promptNewCollection()"
                            class="text-xs text-muted hover:text-fg"
                            title="New collection"
                        >+ New</button>
                    </div>
                    <ul class="space-y-1" x-show="collections.length">
                        <template x-for="col in collections" :key="col.id">
                            <li class="group flex items-center gap-1 rounded px-1 py-0.5 text-sm hover:bg-surface">
                                <button
                                    type="button"
                                    @click="setActiveCollection(col.id)"
                                    :class="activeCollection === col.id ? 'font-medium text-accent' : 'text-fg/85'"
                                    class="flex min-w-0 flex-1 items-center gap-2 text-left"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-3.5 w-3.5 shrink-0">
                                        <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16l-7-3-7 3z"/>
                                    </svg>
                                    <span class="truncate" x-text="col.name"></span>
                                    <span class="ml-auto shrink-0 text-xs text-muted" x-text="col.fonts.length"></span>
                                </button>
                                <button
                                    type="button"
                                    @click.stop="renameCollection(col.id)"
                                    class="hidden p-0.5 text-muted hover:text-fg group-hover:block"
                                    title="Rename"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-3 w-3"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                </button>
                                <button
                                    type="button"
                                    @click.stop="deleteCollection(col.id)"
                                    class="hidden p-0.5 text-muted hover:text-rose-500 group-hover:block"
                                    title="Delete"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-3 w-3"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14z"/></svg>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <p x-show="!collections.length" class="text-xs text-muted/70">
                        Click the bookmark on any font to start a collection.
                    </p>
                </div>

                <button
                    type="button"
                    @click="resetFilters"
                    x-show="search || selectedCategories.length || sort !== 'popularity' || showFavoritesOnly || activeCollection"
                    class="text-xs text-muted underline-offset-2 hover:text-fg hover:underline"
                >
                    Reset filters
                </button>
            </div>
        </aside>

        {{-- Main grid --}}
        <section class="min-w-0 flex-1">
            <style id="dynamic-fonts"></style>

            <div class="mb-3 flex items-center justify-between gap-3 text-xs text-muted">
                <span class="tabular min-w-0">
                    <template x-if="filtered.length === families.length">
                        <span>Showing all {{ number_format($totalCount) }} fonts</span>
                    </template>
                    <template x-if="filtered.length !== families.length">
                        <span><span x-text="filtered.length.toLocaleString()"></span> of {{ number_format($totalCount) }} fonts</span>
                    </template>
                </span>
                <div class="flex items-center gap-3">
                    <span x-show="pageCount > 1" class="tabular">
                        page <span x-text="page"></span> / <span x-text="pageCount"></span>
                    </span>
                    <div class="flex overflow-hidden rounded-md border border-border-soft">
                        <button
                            type="button"
                            @click="setViewMode('grid')"
                            :class="viewMode === 'grid' ? 'bg-fg text-bg' : 'text-muted hover:bg-surface'"
                            class="focus-ring p-1.5 transition-colors"
                            aria-label="Grid view"
                            title="Grid view"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5">
                                <rect x="3" y="3" width="7" height="7" rx="1"/>
                                <rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/>
                                <rect x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="setViewMode('list')"
                            :class="viewMode === 'list' ? 'bg-fg text-bg' : 'text-muted hover:bg-surface'"
                            class="focus-ring p-1.5 transition-colors"
                            aria-label="List view"
                            title="List view"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5">
                                <line x1="3" y1="6" x2="21" y2="6" stroke-linecap="round"/>
                                <line x1="3" y1="12" x2="21" y2="12" stroke-linecap="round"/>
                                <line x1="3" y1="18" x2="21" y2="18" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Recently viewed bar ─────────────────────────────── --}}
            <div
                x-show="$store.recent.items.length > 0 && !search.trim() && !selectedCategories.length && !showFavoritesOnly && !activeCollection"
                x-cloak
                class="mb-4"
            >
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-xs font-medium uppercase tracking-wide text-muted">Recently viewed</h3>
                    <button
                        @click="$store.recent.clear()"
                        class="focus-ring rounded text-xs text-muted hover:text-fg"
                    >Clear</button>
                </div>
                <div class="thin-scrollbar flex gap-2 overflow-x-auto pb-1">
                    <template x-for="name in $store.recent.items" :key="name">
                        <a
                            :href="`/fonts/${slug(name)}`"
                            class="card-hover focus-ring shrink-0 rounded-full border border-border-soft bg-bg px-3 py-1.5 text-xs text-fg theme-aware hover:border-border"
                            x-text="name"
                        ></a>
                    </template>
                </div>
            </div>

            {{-- Filter chips ─────────────────────────────────── --}}
            <div
                x-show="search || selectedCategories.length || showFavoritesOnly || activeCollection || sort !== 'popularity'"
                x-cloak
                class="mb-4 flex flex-wrap items-center gap-1.5"
            >
                <template x-if="search">
                    <button @click="search = ''" class="focus-ring group inline-flex items-center gap-1.5 rounded-full border border-border-soft bg-surface px-2.5 py-1 text-xs text-fg hover:border-border">
                        <span class="text-muted">Search:</span>
                        <span class="font-medium" x-text="`&ldquo;${search}&rdquo;`"></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3 w-3 text-muted group-hover:text-fg"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </template>
                <template x-for="cat in selectedCategories" :key="cat">
                    <button
                        @click="selectedCategories = selectedCategories.filter(c => c !== cat)"
                        class="focus-ring group inline-flex items-center gap-1.5 rounded-full border border-border-soft bg-surface px-2.5 py-1 text-xs text-fg hover:border-border"
                    >
                        <span x-text="cat"></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3 w-3 text-muted group-hover:text-fg"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </template>
                <template x-if="showFavoritesOnly">
                    <button @click="showFavoritesOnly = false" class="focus-ring group inline-flex items-center gap-1.5 rounded-full border border-rose-500/40 bg-rose-500/5 px-2.5 py-1 text-xs text-fg hover:border-rose-500/60">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="h-3 w-3 text-rose-500"><path d="M12 21s-7-4.5-9-8.7C1.6 9.7 3.5 6 7 6c2 0 3.5 1.2 4.5 2.5C13 7.2 14.5 6 16.5 6c3.5 0 5.4 3.7 4 6.3-2 4.2-9 8.7-9 8.7z"/></svg>
                        <span>Favorites</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3 w-3 text-muted group-hover:text-fg"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </template>
                <template x-if="activeCollection">
                    <button @click="activeCollection = null" class="focus-ring group inline-flex items-center gap-1.5 rounded-full border border-accent/40 bg-accent/5 px-2.5 py-1 text-xs text-fg hover:border-accent/60">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="h-3 w-3 text-accent"><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16l-7-3-7 3z"/></svg>
                        <span x-text="collections.find(c => c.id === activeCollection)?.name"></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3 w-3 text-muted group-hover:text-fg"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </template>
                <template x-if="sort !== 'popularity'">
                    <button @click="sort = 'popularity'" class="focus-ring group inline-flex items-center gap-1.5 rounded-full border border-border-soft bg-surface px-2.5 py-1 text-xs text-fg hover:border-border">
                        <span class="text-muted">Sort:</span>
                        <span x-text="sortOptions.find(o => o.value === sort)?.label"></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3 w-3 text-muted group-hover:text-fg"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </template>
                <button
                    @click="resetFilters"
                    class="focus-ring rounded-full px-2 py-1 text-xs text-muted underline-offset-2 hover:text-fg hover:underline"
                >Clear all</button>
            </div>

            {{-- Loading skeleton (shown while bundle is fetching) --}}
            <div x-show="loading" x-cloak class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                @for ($i = 0; $i < 8; $i++)
                    <div class="rounded-lg border border-border-soft p-5">
                        <div class="skeleton h-4 w-32"></div>
                        <div class="skeleton mt-2 h-3 w-48"></div>
                        <div class="skeleton mt-5 h-9 w-full"></div>
                    </div>
                @endfor
            </div>

            <div
                :class="viewMode === 'grid' ? 'grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4' : 'flex flex-col gap-3'"
                x-show="!loading && pageItems.length > 0"
            >
                <template x-for="family in pageItems" :key="family.id">
                    <a
                        :href="`/fonts/${slug(family.family)}`"
                        class="font-card card-hover focus-ring group relative block rounded-lg border border-border-soft bg-bg shadow-card hover:border-border"
                        :class="[
                            compareMode && compareSelection.includes(family.family) ? 'ring-2 ring-accent ring-offset-2' : '',
                            viewMode === 'list' ? 'p-6' : 'p-5'
                        ]"
                        :data-family-id="family.id"
                        @click="if (compareMode) { $event.preventDefault(); toggleCompare(family.family); }"
                    >
                        <div class="absolute right-3 top-3 z-10 flex flex-col gap-1">
                            <button
                                type="button"
                                @click.stop.prevent="toggleFavorite(family.family)"
                                class="rounded p-1 text-muted/60 hover:bg-surface hover:text-rose-500"
                                :class="isFavorite(family.family) ? 'text-rose-500' : ''"
                                :aria-label="isFavorite(family.family) ? 'Remove from favorites' : 'Add to favorites'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" :fill="isFavorite(family.family) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.5-9-8.7C1.6 9.7 3.5 6 7 6c2 0 3.5 1.2 4.5 2.5C13 7.2 14.5 6 16.5 6c3.5 0 5.4 3.7 4 6.3-2 4.2-9 8.7-9 8.7z"/>
                                </svg>
                            </button>
                            <button
                                type="button"
                                @click.stop.prevent="openCollectionModal(family.family)"
                                class="rounded p-1 text-muted/60 hover:bg-surface hover:text-accent"
                                :class="familyCollectionCount(family.family) > 0 ? 'text-accent' : ''"
                                :aria-label="'Save to collection'"
                                :title="familyCollectionCount(family.family) > 0 ? `In ${familyCollectionCount(family.family)} collection${familyCollectionCount(family.family) === 1 ? '' : 's'}` : 'Save to collection'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" :fill="familyCollectionCount(family.family) > 0 ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16l-7-3-7 3z"/>
                                </svg>
                            </button>
                        </div>

                        <template x-if="compareMode">
                            <div class="absolute left-3 top-3 z-10">
                                <span
                                    class="flex h-5 w-5 items-center justify-center rounded border bg-bg text-xs"
                                    :class="compareSelection.includes(family.family) ? 'border-fg bg-fg text-bg' : 'border-border'"
                                >
                                    <span x-show="compareSelection.includes(family.family)">✓</span>
                                </span>
                            </div>
                        </template>

                        <header
                            :class="viewMode === 'list' ? 'mb-2 flex items-baseline gap-3 pr-7' : 'mb-4 flex items-start justify-between gap-3 pr-7'"
                        >
                            <div class="min-w-0">
                                <h2 class="truncate text-base font-medium tracking-tight text-fg" x-text="family.family"></h2>
                                <p class="mt-0.5 text-xs text-muted">
                                    <span x-text="family.category"></span>
                                    &middot;
                                    <span x-text="family.file_count + ' ' + (family.file_count === 1 ? 'style' : 'styles')"></span>
                                    <template x-if="family.is_variable">
                                        <span>&middot; <span class="font-medium text-emerald-600">variable</span></span>
                                    </template>
                                    <template x-if="viewMode === 'list' && family.designers && family.designers.length">
                                        <span>&middot; <span x-text="family.designers.join(', ')"></span></span>
                                    </template>
                                </p>
                            </div>
                        </header>

                        <p
                            class="break-words leading-snug text-fg"
                            :style="`font-family: '${family.family}', sans-serif; font-size: ${viewMode === 'list' ? Math.max(previewSize * 1.6, 56) : previewSize}px;`"
                            x-text="previewText"
                        ></p>
                    </a>
                </template>
            </div>

            {{-- Empty states: search no-match / favorites empty / collection empty / generic --}}
            <div x-show="!loading && filtered.length === 0" x-cloak class="rounded-lg border border-dashed border-border-soft px-6 py-16">
                <template x-if="search.trim() && !showFavoritesOnly && !activeCollection">
                    <div class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto h-10 w-10 text-muted/50">
                            <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/>
                        </svg>
                        <p class="mt-4 text-sm font-medium text-fg">No fonts found for &ldquo;<span x-text="search"></span>&rdquo;</p>
                        <p class="mt-1 text-sm text-muted">Try a shorter query or different spelling.</p>
                        <button @click="resetFilters" class="focus-ring mt-4 rounded-md border border-border px-3 py-1.5 text-sm hover:bg-surface">Clear filters</button>
                    </div>
                </template>
                <template x-if="!search.trim() && showFavoritesOnly">
                    <div class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto h-10 w-10 text-rose-500/40">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.5-9-8.7C1.6 9.7 3.5 6 7 6c2 0 3.5 1.2 4.5 2.5C13 7.2 14.5 6 16.5 6c3.5 0 5.4 3.7 4 6.3-2 4.2-9 8.7-9 8.7z"/>
                        </svg>
                        <p class="mt-4 text-sm font-medium text-fg">No favorites yet</p>
                        <p class="mt-1 text-sm text-muted">Click the heart on any font card to add it here.</p>
                        <button @click="showFavoritesOnly = false" class="focus-ring mt-4 rounded-md border border-border px-3 py-1.5 text-sm hover:bg-surface">Browse all fonts</button>
                    </div>
                </template>
                <template x-if="!search.trim() && activeCollection">
                    <div class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto h-10 w-10 text-accent/40">
                            <path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16l-7-3-7 3z"/>
                        </svg>
                        <p class="mt-4 text-sm font-medium text-fg">
                            &ldquo;<span x-text="collections.find(c => c.id === activeCollection)?.name"></span>&rdquo; is empty
                        </p>
                        <p class="mt-1 text-sm text-muted">Click the bookmark on any font to add it to this collection.</p>
                        <button @click="activeCollection = null" class="focus-ring mt-4 rounded-md border border-border px-3 py-1.5 text-sm hover:bg-surface">Browse all fonts</button>
                    </div>
                </template>
                <template x-if="!search.trim() && !showFavoritesOnly && !activeCollection">
                    <div class="text-center text-sm text-muted">No families match these filters.</div>
                </template>
            </div>

            <div class="mt-8 flex items-center justify-center gap-2" x-show="pageCount > 1">
                <button
                    type="button"
                    @click="prevPage"
                    :disabled="page === 1"
                    class="focus-ring inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-sm hover:bg-surface disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Prev
                </button>
                <span class="tabular px-2 text-sm text-muted">
                    <span x-text="page"></span> / <span x-text="pageCount"></span>
                </span>
                <button
                    type="button"
                    @click="nextPage"
                    :disabled="page === pageCount"
                    class="focus-ring inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-sm hover:bg-surface disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Next
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5"><path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </section>
    </div>

    {{-- Generic branded dialog (replaces window.prompt / window.confirm) --}}
    <div
        x-show="dialog.open"
        x-cloak
        @keydown.escape.window="if (dialog.open) cancelDialog()"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-fg/40 p-4"
        @click.self="cancelDialog()"
    >
        <div class="w-full max-w-sm overflow-hidden rounded-lg border border-border-soft bg-bg shadow-xl">
            <div class="flex items-center gap-2 border-b border-border-soft px-5 py-3">
                <img src="{{ asset('img/google-fonts-logo.svg') }}" alt="" class="h-5 w-5">
                <span class="text-sm font-semibold tracking-tight">{{ config('app.name') }}</span>
            </div>
            <div class="px-5 py-4">
                <p class="text-sm font-medium text-fg" x-text="dialog.title"></p>
                <p x-show="dialog.message" class="mt-1 text-sm text-muted" x-text="dialog.message"></p>
                <input
                    x-show="dialog.type === 'prompt'"
                    id="gfonts-dialog-input"
                    type="text"
                    x-model="dialog.inputValue"
                    :placeholder="dialog.placeholder"
                    @keydown.enter.prevent="confirmDialog()"
                    class="mt-3 w-full rounded-md border border-border px-3 py-2 text-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
                >
            </div>
            <div class="flex justify-end gap-2 border-t border-border-soft bg-surface/50 px-5 py-3">
                <button
                    type="button"
                    @click="cancelDialog()"
                    class="rounded-md border border-border px-3 py-1.5 text-sm hover:bg-surface"
                    x-text="dialog.cancelLabel"
                ></button>
                <button
                    type="button"
                    @click="confirmDialog()"
                    :class="dialog.danger ? 'bg-rose-600 hover:bg-rose-700' : 'bg-accent hover:bg-accent/90'"
                    class="rounded-md px-3 py-1.5 text-sm text-white"
                    x-text="dialog.confirmLabel"
                ></button>
            </div>
        </div>
    </div>

    {{-- Collection picker modal --}}
    <div
        x-show="collectionModal.open"
        x-cloak
        @keydown.escape.window="closeCollectionModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-fg/40 p-4"
        @click.self="closeCollectionModal()"
    >
        <div
            x-show="collectionModal.open"
            x-transition.opacity
            class="w-full max-w-md rounded-lg border border-border-soft bg-bg p-6 shadow-xl"
        >
            <h2 class="text-base font-medium">Save to collection</h2>
            <p class="mt-0.5 truncate text-sm text-muted" x-text="collectionModal.family"></p>

            <ul x-show="collections.length" class="mt-4 max-h-64 space-y-1 overflow-y-auto">
                <template x-for="col in collections" :key="col.id">
                    <li>
                        <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-surface">
                            <input
                                type="checkbox"
                                :checked="isInCollection(col.id, collectionModal.family)"
                                @change="toggleInCollection(col.id, collectionModal.family)"
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
                    @click="closeCollectionModal()"
                    class="rounded-md border border-border px-3 py-1.5 text-sm hover:bg-surface"
                >Done</button>
            </div>
        </div>
    </div>

    {{-- Floating compare bar --}}
    <div
        x-show="compareMode && compareSelection.length > 0"
        x-cloak
        x-transition.opacity.duration.200ms
        class="fixed bottom-4 left-1/2 z-40 flex -translate-x-1/2 items-center gap-3 rounded-full border border-border-soft bg-bg px-4 py-2 shadow-popover"
    >
        <span class="tabular text-xs text-muted">
            <span x-text="compareSelection.length"></span> / 4 selected
        </span>
        <span class="hidden text-xs text-fg md:inline" x-text="compareSelection.join(' · ')"></span>
        <button
            type="button"
            @click="goToCompare()"
            :disabled="compareSelection.length < 2"
            class="focus-ring rounded-md bg-fg px-3 py-1 text-xs text-bg hover:bg-fg/90 disabled:cursor-not-allowed disabled:opacity-40"
        >Compare</button>
        <button
            type="button"
            @click="compareSelection = []"
            class="focus-ring text-xs text-muted hover:text-fg"
        >Clear</button>
    </div>
</div>

@push('head')
<script>
window.__BUNDLE_VERSION__ = @json($bundleVersion);

document.addEventListener('alpine:init', () => {
    Alpine.data('browser', () => ({
        families: [],
        categories: @json($categories),
        loading: true,
        search: '',
        selectedCategories: [],
        sort: 'popularity',
        page: 1,
        pageSize: 48,
        previewText: 'The quick brown fox jumps over the lazy dog',
        previewSize: 36,
        favorites: JSON.parse(localStorage.getItem('gfonts.favorites') || '[]'),
        showFavoritesOnly: false,
        compareMode: false,
        compareSelection: [],
        viewMode: localStorage.getItem('gfonts.viewMode') === 'list' ? 'list' : 'grid',
        collections: JSON.parse(localStorage.getItem('gfonts.collections') || '[]'),
        activeCollection: null,
        collectionModal: { open: false, family: null },
        newCollectionName: '',
        dialog: { open: false, type: null, title: '', message: '', confirmLabel: 'OK', cancelLabel: 'Cancel', danger: false, inputValue: '', resolve: null },

        // Memoization for filtered getter
        _filterKey: null,
        _filteredCache: [],

        // De-dupe @font-face injection across page changes
        _loadedFamilies: new Set(),

        sortOptions: [
            { value: 'popularity', label: 'Popularity' },
            { value: 'trending',   label: 'Trending' },
            { value: 'alpha',      label: 'A → Z' },
            { value: 'newest',     label: 'Newest' },
        ],

        samplePresets: [
            { label: 'Pangram', value: 'The quick brown fox jumps over the lazy dog' },
            { label: 'Numbers', value: '0 1 2 3 4 5 6 7 8 9' },
            { label: 'Caps',    value: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' },
            { label: 'Symbols', value: '! ? @ # $ % & * ( ) — “ ” ' },
            { label: 'Custom',  value: 'Type your own preview' },
        ],

        async init() {
            // Restore state from URL query params (filter inputs + page)
            this.readUrlState();

            // Watchers
            const resetPage = () => { this.page = 1; };
            this.$watch('search', resetPage);
            this.$watch('selectedCategories', resetPage);
            this.$watch('sort', resetPage);
            this.$watch('showFavoritesOnly', resetPage);
            this.$watch('activeCollection', resetPage);
            this.$watch('favorites', (v) => {
                localStorage.setItem('gfonts.favorites', JSON.stringify(v));
            });

            // Inject @font-face for current page whenever it changes.
            // Browser handles actual TTF fetching lazily via font-display: swap
            // — declaring 48 @font-face is cheap; the network requests only
            // happen when the cards are actually painted.
            this.$watch('pageItems', () => this.$nextTick(() => this._loadFonts()));

            // URL state sync
            ['search', 'selectedCategories', 'sort', 'page', 'viewMode',
             'showFavoritesOnly', 'activeCollection'].forEach(key => {
                this.$watch(key, () => this.syncUrlState());
            });

            // Fetch bundle. URL includes ?v=<hash> so browser auto-busts when
            // the catalog version changes (re-index, matcher, sync).
            try {
                const v = window.__BUNDLE_VERSION__ || Date.now();
                const res = await fetch(`/api/fonts.json?v=${encodeURIComponent(v)}`, { cache: 'default' });
                const data = await res.json();
                this.families = data.families;
                if (data.categories) this.categories = data.categories;
                this.loading = false;
                this.$nextTick(() => this._loadFonts());
            } catch (e) {
                console.error('Failed to load font bundle:', e);
                this.loading = false;
                this.$store.toast.error('Failed to load font catalog. Check the server.');
            }
        },

        // Inject @font-face for every family on the current page (de-duped).
        _loadFonts() {
            const styleEl = document.getElementById('dynamic-fonts');
            if (!styleEl) return;
            const fresh = [];
            for (const family of this.pageItems) {
                if (this._loadedFamilies.has(family.family)) continue;
                this._loadedFamilies.add(family.family);
                for (const f of family.files) {
                    const weight = f.variable ? '100 900' : (f.weight ?? 400);
                    const fmt = f.variable ? 'truetype-variations' : 'truetype';
                    fresh.push(`@font-face{font-family:"${family.family}";src:url("/font-file/${f.id}.ttf") format("${fmt}");font-weight:${weight};font-style:${f.style};font-display:swap;}`);
                }
            }
            if (fresh.length) {
                styleEl.appendChild(document.createTextNode(fresh.join('\n') + '\n'));
            }
        },

        readUrlState() {
            try {
                const p = new URLSearchParams(window.location.search);
                if (p.get('q'))    this.search = p.get('q');
                if (p.get('cat'))  this.selectedCategories = p.get('cat').split(',').filter(Boolean);
                if (p.get('sort')) this.sort = p.get('sort');
                if (p.get('page')) this.page = Math.max(1, parseInt(p.get('page'), 10) || 1);
                if (p.get('view') === 'list') this.viewMode = 'list';
                if (p.get('fav') === '1')     this.showFavoritesOnly = true;
                if (p.get('col'))  this.activeCollection = p.get('col');
            } catch (e) { /* ignore */ }
        },

        syncUrlState() {
            const p = new URLSearchParams();
            if (this.search.trim())                  p.set('q', this.search);
            if (this.selectedCategories.length)      p.set('cat', this.selectedCategories.join(','));
            if (this.sort !== 'popularity')          p.set('sort', this.sort);
            if (this.page > 1)                        p.set('page', String(this.page));
            if (this.viewMode === 'list')            p.set('view', 'list');
            if (this.showFavoritesOnly)              p.set('fav', '1');
            if (this.activeCollection)               p.set('col', this.activeCollection);
            const qs = p.toString();
            const url = qs ? `?${qs}` : window.location.pathname;
            window.history.replaceState({}, '', url);
        },

        get filtered() {
            // Build a cheap cache key from all dependencies. Alpine reactivity
            // tracks each access here so when any input changes the getter
            // re-runs, computes a new key, misses cache, recomputes.
            const tagInQuery = /#[\w-]+/.test(this.search);
            const key = JSON.stringify([
                this.families.length,
                this.search.trim(),
                [...this.selectedCategories].sort(),
                this.sort,
                this.showFavoritesOnly ? this.favorites.length + ':' + this.favorites.join(',') : 0,
                this.activeCollection || null,
                this.activeCollection ? this.collections.find(c => c.id === this.activeCollection)?.fonts.join(',') : '',
                // Only invalidate on notes if a tag is actively in the query
                tagInQuery ? Object.keys(this.$store.notes.data).length + ':' + JSON.stringify(this.$store.notes.data) : 0,
            ]);
            if (key === this._filterKey) {
                return this._filteredCache;
            }
            this._filterKey = key;
            this._filteredCache = this._computeFiltered();
            return this._filteredCache;
        },

        _computeFiltered() {
            let out = this.families;
            let raw = this.search.trim();

            // Extract #tag tokens from search text
            const tagMatches = raw.match(/#[\w-]+/g) || [];
            const tags = tagMatches.map(t => t.slice(1).toLowerCase());
            raw = raw.replace(/#[\w-]+/g, '').trim();

            if (tags.length) {
                const noteData = this.$store.notes.data;
                out = out.filter(x => {
                    const familyTags = (noteData[x.family]?.tags || []).map(t => t.toLowerCase());
                    return tags.every(tag => familyTags.includes(tag));
                });
            }

            const q = raw.toLowerCase();
            if (q) {
                out = out.filter(x => x.family.toLowerCase().includes(q));
            }
            if (this.selectedCategories.length) {
                out = out.filter(x => this.selectedCategories.includes(x.category));
            }
            if (this.showFavoritesOnly) {
                out = out.filter(x => this.favorites.includes(x.family));
            }
            if (this.activeCollection) {
                const col = this.collections.find(c => c.id === this.activeCollection);
                if (col) out = out.filter(x => col.fonts.includes(x.family));
            }
            const cmp = (() => {
                switch (this.sort) {
                    case 'popularity': return (a, b) => (a.popularity ?? 1e9) - (b.popularity ?? 1e9);
                    case 'trending':   return (a, b) => (a.trending   ?? 1e9) - (b.trending   ?? 1e9);
                    case 'alpha':      return (a, b) => a.family.localeCompare(b.family);
                    case 'newest':     return (a, b) => (b.date_added || '').localeCompare(a.date_added || '');
                    default:           return () => 0;
                }
            })();
            return [...out].sort(cmp);
        },

        get pageCount() {
            return Math.max(1, Math.ceil(this.filtered.length / this.pageSize));
        },

        get pageItems() {
            const start = (this.page - 1) * this.pageSize;
            return this.filtered.slice(start, start + this.pageSize);
        },

        categoryCount(cat) {
            return this.families.filter(f => f.category === cat).length;
        },

        prevPage() { if (this.page > 1) { this.page--; window.scrollTo({ top: 0, behavior: 'smooth' }); } },
        nextPage() { if (this.page < this.pageCount) { this.page++; window.scrollTo({ top: 0, behavior: 'smooth' }); } },

        resetFilters() {
            this.search = '';
            this.selectedCategories = [];
            this.sort = 'popularity';
            this.showFavoritesOnly = false;
            this.activeCollection = null;
        },

        slug(family) {
            return family.toLowerCase().replace(/ /g, '-');
        },

        setViewMode(mode) {
            this.viewMode = mode;
            localStorage.setItem('gfonts.viewMode', mode);
        },

        // ─── Collections ───────────────────────────────────────
        saveCollections() {
            localStorage.setItem('gfonts.collections', JSON.stringify(this.collections));
        },

        createCollection(name) {
            name = (name || '').trim();
            if (!name) return null;
            const id = 'col_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 6);
            this.collections.push({ id, name, fonts: [] });
            this.saveCollections();
            this.$store.toast.success(`Collection "${name}" created`);
            return id;
        },

        async deleteCollection(id) {
            const col = this.collections.find(c => c.id === id);
            if (!col) return;
            const ok = await this.showConfirm({
                title: 'Delete collection?',
                message: `"${col.name}" will be removed. This can't be undone.`,
                confirmLabel: 'Delete',
                danger: true,
            });
            if (!ok) return;
            this.collections = this.collections.filter(c => c.id !== id);
            if (this.activeCollection === id) this.activeCollection = null;
            this.saveCollections();
            this.$store.toast.info(`Deleted "${col.name}"`);
        },

        async renameCollection(id) {
            const col = this.collections.find(c => c.id === id);
            if (!col) return;
            const next = await this.showPrompt({
                title: 'Rename collection',
                defaultValue: col.name,
                confirmLabel: 'Save',
            });
            if (next !== null && next.trim() && next.trim() !== col.name) {
                col.name = next.trim();
                this.saveCollections();
            }
        },

        async promptNewCollection() {
            const name = await this.showPrompt({
                title: 'New collection',
                placeholder: 'e.g. Headings, Project Alpha',
                confirmLabel: 'Create',
            });
            if (name && name.trim()) this.createCollection(name);
        },

        // ─── Generic dialog ─────────────────────────────────
        showPrompt({ title, message = '', defaultValue = '', placeholder = '', confirmLabel = 'OK', cancelLabel = 'Cancel' }) {
            return new Promise(resolve => {
                this.dialog = {
                    open: true, type: 'prompt',
                    title, message,
                    confirmLabel, cancelLabel,
                    danger: false,
                    placeholder,
                    inputValue: defaultValue,
                    resolve,
                };
                this.$nextTick(() => {
                    const el = document.getElementById('gfonts-dialog-input');
                    if (el) { el.focus(); el.select(); }
                });
            });
        },

        showConfirm({ title, message = '', confirmLabel = 'OK', cancelLabel = 'Cancel', danger = false }) {
            return new Promise(resolve => {
                this.dialog = {
                    open: true, type: 'confirm',
                    title, message,
                    confirmLabel, cancelLabel,
                    danger,
                    placeholder: '',
                    inputValue: '',
                    resolve,
                };
            });
        },

        confirmDialog() {
            const value = this.dialog.type === 'prompt' ? this.dialog.inputValue : true;
            const r = this.dialog.resolve;
            this._closeDialog();
            r?.(value);
        },

        cancelDialog() {
            const r = this.dialog.resolve;
            const fallback = this.dialog.type === 'confirm' ? false : null;
            this._closeDialog();
            r?.(fallback);
        },

        _closeDialog() {
            this.dialog = { open: false, type: null, title: '', message: '', confirmLabel: 'OK', cancelLabel: 'Cancel', danger: false, placeholder: '', inputValue: '', resolve: null };
        },

        isInCollection(colId, family) {
            const col = this.collections.find(c => c.id === colId);
            return col ? col.fonts.includes(family) : false;
        },

        toggleInCollection(colId, family) {
            const col = this.collections.find(c => c.id === colId);
            if (!col) return;
            const i = col.fonts.indexOf(family);
            if (i >= 0) col.fonts.splice(i, 1);
            else col.fonts.push(family);
            this.saveCollections();
        },

        openCollectionModal(family) {
            this.collectionModal = { open: true, family };
            this.newCollectionName = '';
        },

        closeCollectionModal() {
            this.collectionModal = { open: false, family: null };
            this.newCollectionName = '';
        },

        createAndAdd() {
            const name = this.newCollectionName.trim();
            if (!name || !this.collectionModal.family) return;
            const id = this.createCollection(name);
            if (id) this.toggleInCollection(id, this.collectionModal.family);
            this.newCollectionName = '';
        },

        setActiveCollection(id) {
            this.activeCollection = (this.activeCollection === id) ? null : id;
        },

        familyCollectionCount(family) {
            return this.collections.filter(c => c.fonts.includes(family)).length;
        },

        isFavorite(family) {
            return this.favorites.includes(family);
        },

        toggleFavorite(family) {
            const i = this.favorites.indexOf(family);
            if (i >= 0) this.favorites.splice(i, 1);
            else this.favorites.push(family);
        },

        toggleCompare(family) {
            const i = this.compareSelection.indexOf(family);
            if (i >= 0) {
                this.compareSelection.splice(i, 1);
            } else if (this.compareSelection.length < 4) {
                this.compareSelection.push(family);
            }
        },

        goToCompare() {
            if (this.compareSelection.length < 2) return;
            window.location.href = '/compare?fonts=' + encodeURIComponent(this.compareSelection.join(','));
        },
    }));
});
</script>
@endpush
@endsection
