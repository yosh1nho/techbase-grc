// Previne flash de tema errado ao carregar a página
(function () {
    const saved = localStorage.getItem('techbase_theme');
    const prefersLight = window.matchMedia?.('(prefers-color-scheme: light)')?.matches;
    const initial = saved || (prefersLight ? 'light' : 'dark');
    document.documentElement.setAttribute('data-theme', initial);
})();

(() => {
    const KEY = 'techbase_theme';
    const root = document.documentElement;

    function setTheme(theme) {
        root.setAttribute('data-theme', theme);
        localStorage.setItem(KEY, theme);
        updateBtn(theme);
    }

    function updateBtn(theme) {
        const iconDark  = document.querySelector('.theme-icon-dark');
        const iconLight = document.querySelector('.theme-icon-light');
        if (!iconDark || !iconLight) return;

        if (theme === 'light') {
            iconDark.style.display  = 'none';
            iconLight.style.display = 'inline';
        } else {
            iconDark.style.display  = 'inline';
            iconLight.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const saved      = localStorage.getItem(KEY);
        const prefersLight = window.matchMedia?.('(prefers-color-scheme: light)')?.matches;
        const initial    = saved || (prefersLight ? 'light' : 'dark');

        setTheme(initial);

        const btn = document.getElementById('btnThemeToggle');
        if (!btn) return;

        btn.addEventListener('click', () => {
            const cur = root.getAttribute('data-theme') || 'dark';
            setTheme(cur === 'dark' ? 'light' : 'dark');
        });
    });
})();
