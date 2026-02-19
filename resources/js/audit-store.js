(() => {
    const KEY = "tb_audit_v1";

    function load() {
        try { return JSON.parse(localStorage.getItem(KEY) || "[]"); }
        catch { return []; }
    }

    function save(list) {
        localStorage.setItem(KEY, JSON.stringify(list.slice(0, 1500))); // corta pra não crescer infinito
    }

    function now() {
        const d = new Date();
        const pad = (n) => String(n).padStart(2, "0");
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }

    function log(evt) {
        const list = load();
        list.unshift({
            id: crypto?.randomUUID?.() || String(Date.now() + Math.random()),
            at: now(),
            user: window.APP_USER?.email || window.APP_USER?.name || "—",
            role: window.APP_USER?.role || "—",
            action: evt.action || "—",
            entity: evt.entity || "—",
            target: evt.target || "—",
            detail: evt.detail || "",
            meta: evt.meta || {}
        });
        save(list);
    }

    function all() { return load(); }
    function clear() { save([]); }

    window.Audit = { log, all, clear };
})();
