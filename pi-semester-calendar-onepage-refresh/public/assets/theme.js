const root = document.documentElement;
const toggle = document.querySelector('[data-theme-toggle]');

let theme =
    window.matchMedia &&
    window.matchMedia('(prefers-color-scheme: light)').matches
        ? 'light'
        : 'dark';

root.setAttribute('data-theme', theme);

if (toggle) {
    toggle.addEventListener('click', () => {
        theme = theme === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', theme);
    });
}
