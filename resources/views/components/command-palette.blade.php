@php
    $quickAccess = app(\App\Support\CommandPaletteMenuRegistry::class)->quickAccess(Auth::user());
@endphp

<div class="w-auto lg:w-full lg:max-w-sm" x-data="commandPalette({ endpoint: @js(route('global-search')), quickAccess: @js($quickAccess) })">
    <label class="relative hidden lg:block">
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center theme-muted" aria-hidden="true"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" /></svg></span>
        <input x-ref="trigger" type="search" readonly value="" placeholder="Search menu, document, item...  Ctrl + K" class="theme-surface-secondary block w-full cursor-pointer rounded-xl py-2 pl-9 pr-20 text-sm font-medium shadow-sm" @click="openPalette()" @focus="openPalette()" aria-label="Open global search command palette">
        <kbd class="pointer-events-none absolute inset-y-0 right-2 my-auto flex h-6 items-center rounded-md border px-1.5 text-[10px] font-bold theme-muted" style="border-color:var(--theme-border)">Ctrl K</kbd>
    </label>
    <button x-ref="mobileTrigger" type="button" class="theme-surface theme-focus grid h-10 w-10 place-items-center rounded-xl border shadow-sm lg:hidden" @click="openPalette()" aria-label="Open global search"><svg class="h-5 w-5 theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" /></svg></button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="command-palette fixed inset-0 z-[100] flex items-start justify-center px-3 pt-[8vh] sm:px-6 sm:pt-[12vh]" @keydown.escape.window="closePalette()" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="openSelected()">
            <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-[2px]" @click="closePalette()"></div>
            <section class="theme-surface relative flex max-h-[78vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl border shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="command-palette-title">
                <h2 id="command-palette-title" class="sr-only">Global search</h2>
                <div class="relative border-b" style="border-color:var(--theme-border)">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center theme-muted" aria-hidden="true"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" /></svg></span>
                    <input x-ref="search" type="search" x-model="query" @input="queueSearch()" placeholder="Search menu, document, item..." class="h-14 w-full border-0 bg-transparent pl-12 pr-14 text-base font-semibold shadow-none focus:ring-0" aria-label="Search menus, documents, and master data" autocomplete="off">
                    <button type="button" class="theme-option theme-focus absolute inset-y-0 right-3 my-auto h-8 rounded-md px-2 text-xs font-bold theme-muted" @click="closePalette()" aria-label="Close global search">Esc</button>
                </div>

                <div class="min-h-48 flex-1 overflow-y-auto p-2" role="listbox" aria-label="Search results">
                    <div x-show="loading" class="flex items-center gap-2 px-3 py-8 text-sm theme-muted"><span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent"></span>Searching...</div>
                    <div x-show="error" x-text="error" class="px-3 py-8 text-center text-sm status-danger"></div>
                    <div x-show="!loading && !error && query.length > 0 && query.length < 2" class="px-3 py-8 text-center text-sm theme-muted">Type at least 2 characters to search records.</div>
                    <div x-show="!loading && !error && flatResults.length === 0 && query.length >= 2" class="px-3 py-8 text-center"><p class="text-sm font-bold theme-text">No results found.</p><p class="mt-1 text-xs theme-muted">Try a document number, SKU, supplier, customer, or menu name.</p></div>

                    <template x-for="(results, group) in groups" :key="group">
                        <div x-show="results.length" class="mb-2 last:mb-0">
                            <div class="px-3 py-1.5 text-[10px] font-black uppercase tracking-[.12em] theme-muted" x-text="group"></div>
                            <template x-for="result in results" :key="result.type + result.url">
                                <div>
                                    <button type="button" class="command-result theme-focus flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left" :class="resultIndex(result) === selectedIndex ? 'theme-primary-soft is-selected' : ''" :data-palette-result="resultIndex(result)" :aria-selected="resultIndex(result) === selectedIndex" role="option" @mouseenter="selectedIndex = resultIndex(result)" @click="openResult(result)">
                                        <span class="theme-surface-secondary grid h-9 w-9 shrink-0 place-items-center rounded-lg border text-xs font-black" style="border-color:var(--theme-border)" x-text="result.type.slice(0, 1)" aria-hidden="true"></span>
                                        <span class="min-w-0 flex-1"><span class="block text-[10px] font-black uppercase tracking-wide theme-muted" x-text="result.type"></span><span class="block truncate text-sm font-bold theme-text" x-text="result.title"></span><span class="block truncate text-xs theme-muted" x-text="result.description"></span></span>
                                        <span x-show="result.status" class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-[10px] font-black capitalize ring-1" :class="statusClass(result.status)" x-text="String(result.status || '').replaceAll('_', ' ')"></span>
                                    </button>
                                    <div x-show="resultIndex(result) === selectedIndex && result.actions?.length" class="ml-14 flex flex-wrap gap-1 px-3 pb-2">
                                        <template x-for="action in result.actions" :key="action.url"><a :href="action.url" class="theme-option theme-focus rounded-md border px-2 py-1 text-[10px] font-bold theme-link" style="border-color:var(--theme-border)" x-text="action.label" @click.stop></a></template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <footer class="theme-surface-secondary hidden items-center justify-end gap-4 border-t px-4 py-2 text-[10px] font-bold theme-muted sm:flex" style="border-color:var(--theme-border)"><span>↑ ↓ Navigate</span><span>Enter Open</span><span>Esc Close</span></footer>
            </section>
        </div>
    </template>
</div>
