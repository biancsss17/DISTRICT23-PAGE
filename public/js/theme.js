(() => {
    'use strict';
    const root = document.documentElement;
    let dark = false;
    try {
        dark = localStorage.getItem('uecfi-theme') === 'dark';
    } catch {}
    function apply(value) {
        dark = value;
        root.dataset.theme = dark ? 'dark' : 'light';
        root.style.colorScheme = dark ? 'dark' : 'light';
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.hidden = false;
            button.setAttribute('aria-pressed', String(dark));
            button.querySelector('[data-theme-icon]').textContent = dark ? '☀' : '☾';
            button.querySelector('[data-theme-label]').textContent = dark
                ? 'Light mode'
                : 'Dark mode';
        });
    }
    apply(dark);
    document.addEventListener('DOMContentLoaded', () => {
        apply(dark);
        document.querySelectorAll('[data-theme-toggle]').forEach((button) =>
            button.addEventListener('click', () => {
                apply(!dark);
                try {
                    localStorage.setItem('uecfi-theme', dark ? 'dark' : 'light');
                } catch {}
            }),
        );
    });
    window.addEventListener('storage', (event) => {
        if (event.key === 'uecfi-theme' || event.key === null) apply(event.newValue === 'dark');
    });
})();
