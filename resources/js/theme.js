(() => {
    const KEY = "techbase_theme";
    const root = document.documentElement;

    function setTheme(theme) {
        root.setAttribute("data-theme", theme);
        localStorage.setItem(KEY, theme);

        const btn = document.getElementById("btnThemeToggle");
        if (btn) btn.textContent = theme === "light" ? "🌞" : "🌙";
    }

    function init() {
        // tenta recuperar
        const saved = localStorage.getItem(KEY);

        // fallback: seguir sistema
        const prefersLight = window.matchMedia?.("(prefers-color-scheme: light)")?.matches;
        const initial = saved || (prefersLight ? "light" : "dark");

        setTheme(initial);

        const btn = document.getElementById("btnThemeToggle");
        if (!btn) return;

        btn.addEventListener("click", () => {
            const cur = root.getAttribute("data-theme") || "dark";
            setTheme(cur === "dark" ? "light" : "dark");
        });
    }

    document.addEventListener("DOMContentLoaded", init);
})();
