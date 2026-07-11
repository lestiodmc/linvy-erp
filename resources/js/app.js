import './bootstrap';

import Alpine from 'alpinejs';

const themes = ['linvy-emerald', 'ocean-blue', 'royal-purple', 'amber-gold', 'rose-red', 'slate-dark'];

window.LinvyTheme = {
    themes,
    current: () => themes.includes(document.documentElement.dataset.theme) ? document.documentElement.dataset.theme : themes[0],
    apply(theme) {
        const selected = themes.includes(theme) ? theme : themes[0];
        document.documentElement.dataset.theme = selected;
        localStorage.setItem('linvy_theme', selected);
        window.dispatchEvent(new CustomEvent('linvy-theme-changed', { detail: { theme: selected } }));
        return selected;
    },
};

window.Alpine = Alpine;

Alpine.start();
