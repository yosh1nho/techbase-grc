(() => {
    const $ = (s) => document.querySelector(s);

    // Mock de ativos (por agora). Depois trocamos para vir do backend.
    const ASSETS = [
        { id: "A1", name: "SRV-DB-01", subtitle: "PostgreSQL • Produção", type: "Servidor", owner: "TI • João" },
        { id: "A2", name: "APP-GRC", subtitle: "Laravel • Web", type: "Aplicação", owner: "SecOps • Ana" },
    ];

    const input = $("#assetSearch");
    const dd = $("#assetDropdown");
    const selectedId = $("#assetSelectedId");
    const selectedLabel = $("#assetSelectedLabel");

    if (!input || !dd || !selectedId) return;

    const year = document.getElementById("periodYear");
    const quarter = document.getElementById("periodQuarter");
    const periodValue = document.getElementById("periodValue");

    function syncPeriod() {
        if (!year || !quarter || !periodValue) return;
        periodValue.value = `${quarter.value}-${year.value}`;
    }

    year?.addEventListener("change", syncPeriod);
    quarter?.addEventListener("change", syncPeriod);
    syncPeriod();



    function norm(v) { return String(v ?? "").toLowerCase(); }

    function renderDropdown(items) {
        if (!items.length) {
            dd.innerHTML = `<div class="muted" style="padding:8px 10px;">Sem resultados.</div>`;
            dd.style.display = "block";
            return;
        }

        dd.innerHTML = items.map(a => `
      <button type="button"
              class="btn"
              style="width:100%; text-align:left; justify-content:flex-start; gap:10px; margin:4px 0;"
              data-asset-id="${a.id}">
        <div>
          <div><b>${a.name}</b> <span class="muted">(${a.type})</span></div>
          <div class="muted" style="font-size:12px;">${a.subtitle} • ${a.owner}</div>
        </div>
      </button>
    `).join("");

        dd.style.display = "block";

        dd.querySelectorAll("[data-asset-id]").forEach(b => {
            b.addEventListener("click", () => {
                const id = b.getAttribute("data-asset-id");
                const a = ASSETS.find(x => x.id === id);
                if (!a) return;

                selectedId.value = a.id;
                selectedId.value = a.id;
                input.value = a.name;
                dd.style.display = "none";
            });
        });
    }

    function filterAssets(q) {
        const t = norm(q).trim();
        if (!t) return ASSETS.slice(0, 8);

        return ASSETS.filter(a => {
            const hay = `${a.id} ${a.name} ${a.subtitle} ${a.type} ${a.owner}`.toLowerCase();
            return hay.includes(t);
        }).slice(0, 12);
    }

    input.addEventListener("focus", () => renderDropdown(filterAssets(input.value)));
    input.addEventListener("input", () => renderDropdown(filterAssets(input.value)));

    document.addEventListener("click", (e) => {
        const inside = dd.contains(e.target) || input.contains(e.target);
        if (!inside) dd.style.display = "none";
    });
})();
