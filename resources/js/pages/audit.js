(() => {
    const KEY = "tb_audit_v1";

    // Classificação de risco automática por tipo de ação.
    // "high"  → destrutivo ou expõe dados para fora do sistema
    // "med"   → altera estado persistente
    // "low"   → apenas leitura / navegação
    const RISK_MAP = {
        Delete:    "high",
        Export:    "high",
        Purge:     "high",
        Restore:   "high",
        Upload:    "med",
        Edit:      "med",
        Avaliação: "med",
        Approve:   "med",
        Reject:    "med",
        Chat:      "low",
        Login:     "low",
        Logout:    "low",
        View:      "low",
    };

    function inferRisk(action) {
        return RISK_MAP[action] || "low";
    }

    function load() {
        try { return JSON.parse(localStorage.getItem(KEY) || "[]"); }
        catch { return []; }
    }

    function save(list) {
        localStorage.setItem(KEY, JSON.stringify(list.slice(0, 1500)));
    }

    function now() {
        const d = new Date();
        const pad = (n) => String(n).padStart(2, "0");
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }

    function log(evt) {
        const list = load();
        const action = evt.action || "—";
        list.unshift({
            id:     crypto?.randomUUID?.() || String(Date.now() + Math.random()),
            at:     now(),
            user:   window.APP_USER?.email || window.APP_USER?.name || "—",
            role:   window.APP_USER?.role || "—",
            action,
            entity: evt.entity || "—",
            target: evt.target || "—",
            detail: evt.detail || "",
            risk:   evt.risk || inferRisk(action), // permite override manual se necessário
            meta:   evt.meta || {}
        });
        save(list);
    }

    function all() { return load(); }
    function clear() { save([]); }

    window.Audit = { log, all, clear, inferRisk };
})();
