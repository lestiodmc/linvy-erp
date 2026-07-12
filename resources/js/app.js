import './bootstrap';

import Alpine from 'alpinejs';

const appearanceValues = {
    mode: ['light', 'dark', 'system'],
    accent: ['emerald', 'blue', 'purple', 'rose', 'amber', 'teal', 'slate'],
    density: ['comfortable', 'compact'],
    sidebar: ['expanded', 'compact'],
};
const appearanceKeys = {
    mode: 'linvy_appearance_mode', accent: 'linvy_accent', density: 'linvy_density', sidebar: 'linvy_sidebar_mode',
};
const legacyThemes = {
    'linvy-emerald': ['light', 'emerald'], 'ocean-blue': ['light', 'blue'], 'royal-purple': ['light', 'purple'],
    'amber-gold': ['light', 'amber'], 'rose-red': ['light', 'rose'], 'slate-dark': ['dark', 'blue'],
};
const legacyFor = (mode, accent) => mode === 'dark' ? 'slate-dark' : ({ emerald: 'linvy-emerald', blue: 'ocean-blue', purple: 'royal-purple', amber: 'amber-gold', rose: 'rose-red' }[accent] || 'linvy-emerald');

window.LinvyAppearance = {
    values: appearanceValues,
    current() {
        const root = document.documentElement;
        const storedMode = localStorage.getItem(appearanceKeys.mode);
        return {
            mode: appearanceValues.mode.includes(storedMode) ? storedMode : 'system',
            accent: root.dataset.accent || 'emerald', density: root.dataset.density || 'comfortable', sidebar: root.dataset.sidebar || 'expanded',
        };
    },
    apply(preference, value, persist = true) {
        if (!appearanceValues[preference]?.includes(value)) return this.current()[preference];
        const root = document.documentElement;
        if (preference === 'mode') root.dataset.mode = value === 'system' ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : value;
        else root.dataset[preference] = value;
        if (persist) localStorage.setItem(appearanceKeys[preference], value);
        const state = this.current();
        root.dataset.theme = legacyFor(root.dataset.mode, state.accent);
        localStorage.setItem('linvy_theme', root.dataset.theme);
        window.dispatchEvent(new CustomEvent('linvy-appearance-changed', { detail: { ...state, resolvedMode: root.dataset.mode } }));
        window.dispatchEvent(new CustomEvent('linvy-theme-changed', { detail: { theme: root.dataset.theme } }));
        return value;
    },
};

// Compatibility facade for pages or extensions still using the original theme API.
window.LinvyTheme = {
    themes: Object.keys(legacyThemes),
    current: () => document.documentElement.dataset.theme,
    apply(theme) {
        const [mode, accent] = legacyThemes[theme] || legacyThemes['linvy-emerald'];
        window.LinvyAppearance.apply('mode', mode);
        window.LinvyAppearance.apply('accent', accent);
        return document.documentElement.dataset.theme;
    },
};

matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (localStorage.getItem(appearanceKeys.mode) === 'system') window.LinvyAppearance.apply('mode', 'system', false);
});

Alpine.data('appearancePanel', () => ({
    open: false, ...window.LinvyAppearance.current(),
    choose(preference, value) { this[preference] = window.LinvyAppearance.apply(preference, value); },
    close(restoreFocus = false) { this.open = false; if (restoreFocus) this.$nextTick(() => this.$refs.trigger?.focus()); },
}));

Alpine.data('commandPalette', (config) => ({
    open: false,
    query: '',
    groups: { 'Quick Access': config.quickAccess || [] },
    loading: false,
    error: '',
    selectedIndex: 0,
    timer: null,
    controller: null,
    requestSequence: 0,

    init() {
        window.addEventListener('keydown', (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                this.openPalette();
            }
        });
    },

    get flatResults() {
        return Object.entries(this.groups).flatMap(([group, results]) => results.map((result) => ({ ...result, group })));
    },

    openPalette() {
        if (this.open) return this.$nextTick(() => this.$refs.search?.focus());
        this.open = true;
        this.query = '';
        this.groups = { 'Quick Access': config.quickAccess || [] };
        this.selectedIndex = 0;
        this.error = '';
        document.body.classList.add('overflow-hidden');
        this.$nextTick(() => this.$refs.search?.focus());
    },

    closePalette(restoreFocus = true) {
        this.controller?.abort();
        clearTimeout(this.timer);
        this.open = false;
        this.query = '';
        this.loading = false;
        this.error = '';
        document.body.classList.remove('overflow-hidden');
        if (restoreFocus) this.$nextTick(() => (this.$refs.trigger?.offsetParent ? this.$refs.trigger : this.$refs.mobileTrigger)?.focus());
    },

    queueSearch() {
        clearTimeout(this.timer);
        this.error = '';
        this.selectedIndex = 0;
        if (this.query.trim().length < 2) {
            this.controller?.abort();
            this.loading = false;
            this.groups = { 'Quick Access': config.quickAccess || [] };
            return;
        }
        this.timer = setTimeout(() => this.fetchResults(), 250);
    },

    async fetchResults() {
        this.controller?.abort();
        this.controller = new AbortController();
        const sequence = ++this.requestSequence;
        this.loading = true;
        try {
            const url = new URL(config.endpoint, window.location.origin);
            url.searchParams.set('q', this.query.trim());
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: this.controller.signal });
            if (!response.ok) throw new Error('Search is temporarily unavailable.');
            const payload = await response.json();
            if (sequence !== this.requestSequence) return;
            this.groups = payload.groups || {};
            this.selectedIndex = 0;
        } catch (error) {
            if (error.name !== 'AbortError' && sequence === this.requestSequence) {
                this.groups = {};
                this.error = 'Search is temporarily unavailable. Please try again.';
            }
        } finally {
            if (sequence === this.requestSequence) this.loading = false;
        }
    },

    resultIndex(result) {
        return this.flatResults.findIndex((candidate) => candidate.url === result.url && candidate.type === result.type);
    },

    move(step) {
        const count = this.flatResults.length;
        if (!count) return;
        this.selectedIndex = (this.selectedIndex + step + count) % count;
        this.$nextTick(() => document.querySelector(`[data-palette-result="${this.selectedIndex}"]`)?.scrollIntoView({ block: 'nearest' }));
    },

    openSelected() {
        const result = this.flatResults[this.selectedIndex];
        if (result?.url) this.openResult(result);
    },

    openResult(result) {
        if (!result?.url) return;
        this.closePalette(false);
        window.location.assign(result.url);
    },

    statusClass(status) {
        const value = String(status || '').toLowerCase().replaceAll('-', '_');
        if (['approved', 'posted', 'fully_received', 'in_stock'].includes(value)) return 'status-success';
        if (['pending', 'partially_received', 'near_expiry', 'low_stock'].includes(value)) return 'status-warning';
        if (['rejected', 'expired', 'negative_stock'].includes(value)) return 'status-danger';
        if (['submitted'].includes(value)) return 'status-info';
        return 'status-neutral';
    },
}));

window.Alpine = Alpine;

Alpine.start();
