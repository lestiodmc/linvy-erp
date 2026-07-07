@props([
    'name',
    'url',
    'placeholder' => 'Search...',
    'selectedId' => null,
    'selectedText' => '',
    'unitTarget' => null,
    'descriptionTarget' => null,
    'onSelect' => null,
    'inputClass' => 'w-full',
    'extraParams' => [],
])

@once
    <script>
        window.linvySearchableSelect = function (config) {
            return {
                name: config.name,
                url: config.url,
                placeholder: config.placeholder,
                selectedId: config.selectedId || '',
                query: config.selectedText || '',
                unitTarget: config.unitTarget || null,
                descriptionTarget: config.descriptionTarget || null,
                onSelect: config.onSelect || null,
                extraParams: config.extraParams || {},
                results: [],
                open: false,
                loading: false,
                searched: false,
                selectingOption: false,
                isDropdownMouseDown: false,
                timer: null,
                dropdownStyle: 'display: none;',

                init() {
                    const close = (event) => {
                        const dropdown = this.$root.querySelector('[data-searchable-dropdown]');

                        if (
                            this.selectingOption
                            || this.isDropdownMouseDown
                            || (dropdown && event?.target && dropdown.contains(event.target))
                        ) {
                            return;
                        }

                        this.open = false;
                    };

                    window.addEventListener('scroll', close, true);
                    window.addEventListener('resize', close);
                },

                positionDropdown() {
                    const input = this.$root.querySelector('[data-searchable-input]');

                    if (!input) {
                        return;
                    }

                    const rect = input.getBoundingClientRect();
                    const gap = 4;
                    const maxHeight = Math.min(240, window.innerHeight - rect.bottom - gap - 12);
                    const fallbackTop = Math.max(12, rect.top - 244);
                    const top = maxHeight >= 96 ? rect.bottom + gap : fallbackTop;
                    const height = maxHeight >= 96 ? maxHeight : Math.min(240, rect.top - 16);

                    this.dropdownStyle = [
                        'position: fixed',
                        'z-index: 9999',
                        `top: ${top}px`,
                        `left: ${rect.left}px`,
                        `width: ${rect.width}px`,
                        `max-height: ${Math.max(96, height)}px`,
                    ].join('; ');
                },

                search() {
                    const hadSelection = Boolean(this.selectedId);
                    this.selectedId = '';
                    if (hadSelection) {
                        this.$root.dispatchEvent(new CustomEvent('linvy-searchable-cleared', {
                            bubbles: true,
                            detail: { name: this.name },
                        }));
                    }
                    clearTimeout(this.timer);

                    if (this.query.trim().length < 2) {
                        this.results = [];
                        this.open = false;
                        this.searched = false;
                        return;
                    }

                    this.positionDropdown();
                    this.timer = setTimeout(() => this.fetchResults(), 250);
                },

                fetchResults() {
                    this.positionDropdown();
                    this.loading = true;
                    this.open = true;
                    this.searched = true;

                    const url = new URL(this.url, window.location.origin);
                    url.searchParams.set('q', this.query);

                    Object.entries(this.extraParams).forEach(([param, selector]) => {
                        const target = this.$root.closest('form')?.querySelector(selector)
                            || this.$root.closest('tr')?.querySelector(selector)
                            || document.querySelector(selector);

                        if (target?.value) {
                            url.searchParams.set(param, target.value);
                        }
                    });

                    fetch(url, {
                        headers: { Accept: 'application/json' },
                    })
                        .then((response) => response.ok ? response.json() : [])
                        .then((data) => {
                            this.results = Array.isArray(data) ? data : [];
                            this.$nextTick(() => this.positionDropdown());
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },

                select(option) {
                    const previousId = this.selectedId;
                    const itemChanged = String(previousId || '') !== String(option.id || '');

                    this.selectingOption = true;
                    this.isDropdownMouseDown = true;
                    this.selectedId = option.id;
                    this.query = option.text;
                    this.results = [];
                    this.open = false;

                    if (this.unitTarget && option.unit_id) {
                        const target = this.$root.closest('tr')?.querySelector(this.unitTarget);
                        if (target && (itemChanged || !target.value)) {
                            target.value = option.unit_id;
                        }
                    }

                    if (this.descriptionTarget && option.description) {
                        const target = this.$root.closest('tr')?.querySelector(this.descriptionTarget);
                        const previousDescription = target?.dataset.itemDescription || '';
                        const currentDescription = target?.value?.trim() || '';

                        if (target && (currentDescription === '' || currentDescription === previousDescription)) {
                            target.value = option.description;
                        }

                        if (target) {
                            target.dataset.itemDescription = option.description;
                        }
                    }

                    if (this.onSelect) {
                        const callback = new Function('option', this.onSelect);
                        callback.call(this, option);
                    }

                    setTimeout(() => {
                        this.selectingOption = false;
                        this.isDropdownMouseDown = false;
                    }, 150);
                },

                clearIfBlank() {
                    setTimeout(() => {
                        if (this.selectingOption || this.isDropdownMouseDown) {
                            return;
                        }

                        if (this.query.trim() === '') {
                            this.selectedId = '';
                            this.results = [];
                            this.open = false;
                        }
                    }, 120);
                },
            };
        };
    </script>
@endonce

<div
    class="relative {{ $inputClass }}"
    x-data="linvySearchableSelect({
        name: @js($name),
        url: @js($url),
        placeholder: @js($placeholder),
        selectedId: @js((string) $selectedId),
        selectedText: @js($selectedText),
        unitTarget: @js($unitTarget),
        descriptionTarget: @js($descriptionTarget),
        onSelect: @js($onSelect),
        extraParams: @js($extraParams),
    })"
    @click.outside="if (!selectingOption) open = false"
>
    <input type="hidden" name="{{ $name }}" x-model="selectedId" {{ $attributes }}>

    <div class="relative">
        <input
            type="search"
            data-searchable-input
            x-model="query"
            @input="search()"
            @focus="if (query.trim().length >= 2) { positionDropdown(); open = true }"
            @blur="clearIfBlank()"
            @keydown.escape.prevent="open = false"
            placeholder="{{ $placeholder }}"
            class="block {{ $inputClass }} rounded-lg border-slate-200 pr-9 text-sm focus:border-emerald-500 focus:ring-emerald-500"
            {{ $attributes }}
        >
        <span x-show="loading" class="absolute inset-y-0 right-3 flex items-center text-xs font-bold text-slate-400">...</span>
    </div>

    <div
        data-searchable-dropdown
        x-show="open"
        x-cloak
        :style="dropdownStyle"
        class="overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-2xl ring-1 ring-slate-900/5"
        @pointerdown.stop="isDropdownMouseDown = true"
        @mousedown.prevent.stop="isDropdownMouseDown = true"
        @mouseup.stop="setTimeout(() => isDropdownMouseDown = false, 150)"
        @wheel.stop
        @touchmove.stop
        @scroll.stop
    >
        <template x-if="loading">
            <div class="px-3 py-2 font-medium text-slate-500">Loading...</div>
        </template>

        <template x-if="!loading && results.length === 0 && searched">
            <div class="px-3 py-2 font-medium text-slate-500">No results found</div>
        </template>

        <template x-for="option in results" :key="option.id">
            <button
                type="button"
                class="block w-full px-3 py-2 text-left font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800"
                @pointerdown.prevent.stop="select(option)"
                @mousedown.prevent.stop
                @click.prevent.stop
            >
                <span class="block" x-text="option.text"></span>
                <span x-show="option.meta_text" class="mt-0.5 block text-xs font-semibold text-slate-500" x-text="option.meta_text"></span>
            </button>
        </template>
    </div>
</div>
