const LS_KEY = "tb_mock_treatments";

function loadPlans() {
    try { return JSON.parse(localStorage.getItem(LS_KEY) || "[]"); }
    catch { return []; }
}

function seedIfEmpty() {
    if (loadPlans().length > 0) return;
    savePlans([
        {
            id: "TP-1001", source: "acronis", alertId: "WZ-1001",
            asset: "SRV-DB-01",
            risk: "Comprometimento do ativo / execução maliciosa",
            aiActions: [
                "Isolar o host e recolher indicadores (hash/processos).",
                "Executar varredura completa + confirmar integridade.",
                "Rever allowlist/EDR e bloquear execução não assinada.",
            ].join("\n"),
            planDescription: "Isolar SRV-DB-01, validar processos suspeitos e aplicar hardening (allowlist). Gerar evidência com relatório EDR + logs.",
            strategy: "Mitigar", due: "2026-02-28", owner: "IT Ops",
            priority: "Alta", status: "Em curso",
            createdAt: "2026-02-18T10:15:00.000Z", evidence: ""
        },
        {
            id: "TP-1002", source: "acronis", alertId: "WZ-1002",
            asset: "FW-EDGE-01",
            risk: "Acesso não autorizado por força bruta",
            aiActions: [
                "Bloquear IPs e ajustar rate-limit / fail2ban.",
                "Rever MFA e endurecer política de passwords.",
                "Auditar contas com tentativas e logs de autenticação.",
            ].join("\n"),
            planDescription: "Aplicar bloqueio por IP e rate-limit no perímetro, revisar MFA para acessos remotos.",
            strategy: "Mitigar", due: "2026-02-20", owner: "Network",
            priority: "Alta", status: "To do",
            createdAt: "2026-02-18T09:50:00.000Z", evidence: ""
        },
        {
            id: "TP-1003", source: "assessment", alertId: "R-003",
            asset: "NAS-BKP-01",
            risk: "Falha de backup testado",
            aiActions: "Agendar testes de restore e definir evidências mínimas (relatório + logs).",
            planDescription: "Executar teste de restore semanal e documentar. Garantir que backups críticos têm teste aprovado.",
            strategy: "Mitigar", due: "2026-02-10", owner: "SOC",
            priority: "Média", status: "Em atraso",
            createdAt: "2026-02-01T12:00:00.000Z", evidence: ""
        },
        {
            id: "TP-1004", source: "acronis", alertId: "WZ-1004",
            asset: "APP-GRC",
            risk: "Tentativas repetidas de autenticação",
            aiActions: "Rever logs de autenticação e aplicar políticas de bloqueio por tentativa.",
            planDescription: "Ativar bloqueio por tentativas, revisar IPs suspeitos e adicionar alerta de threshold.",
            strategy: "Mitigar", due: "2026-02-18", owner: "AppSec",
            priority: "Baixa", status: "Concluído",
            createdAt: "2026-02-17T15:10:00.000Z",
            evidence: "Relatório interno + screenshot de configuração"
        }
    ]);
}

function savePlans(plans) {
    localStorage.setItem(LS_KEY, JSON.stringify(plans));
}

function isOverdue(due) {
    if (!due) return false;
    const d = new Date(due + "T00:00:00");
    const now = new Date();
    return d < new Date(now.getFullYear(), now.getMonth(), now.getDate());
}

function daysUntil(due) {
    if (!due) return null;
    const d = new Date(due + "T00:00:00");
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    return Math.round((d - today) / 86400000);
}

function normalizePlan(p) {
    return { ...p, status: p.status || "To do" };
}

function priorityClass(p) {
    if (p === "Alta")  return "chip bad";
    if (p === "Média") return "chip warn";
    return "chip";
}

// Cores da barra lateral do card por status
const STATUS_BAR = {
    "To do":     "#94a3b8",
    "Em curso":  "#60a5fa",
    "Concluído": "#34d399",
    "Em atraso": "#f87171",
};

// Abreviatura do owner para avatar
function ownerInitials(owner) {
    if (!owner) return "?";
    return owner.split(/[\s-]+/).map(w => w[0]).join("").toUpperCase().slice(0, 2);
}

// Badge de prazo
function dueBadge(due, status) {
    if (status === "Concluído") return "";
    const days = daysUntil(due);
    if (days === null) return "";
    if (days < 0)  return `<span class="kcard-due-badge kcard-due-overdue">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        ${Math.abs(days)}d atraso</span>`;
    if (days <= 5) return `<span class="kcard-due-badge kcard-due-soon">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        ${days}d restantes</span>`;
    return `<span class="kcard-due-badge kcard-due-ok">${due}</span>`;
}

// ── Filtros activos ──
let filterSearch   = "";
let filterPriority = "all";
let filterOwner    = "all";

function applyFilters(plans) {
    return plans.filter(p => {
        const q = filterSearch.toLowerCase();
        const matchQ = !q ||
            (p.id || "").toLowerCase().includes(q) ||
            (p.asset || "").toLowerCase().includes(q) ||
            (p.owner || "").toLowerCase().includes(q) ||
            (p.risk || "").toLowerCase().includes(q) ||
            (p.planDescription || "").toLowerCase().includes(q);
        const matchP = filterPriority === "all" || p.priority === filterPriority;
        const matchO = filterOwner === "all" || p.owner === filterOwner;
        return matchQ && matchP && matchO;
    });
}

function populateOwnerFilter(plans) {
    const sel = document.getElementById("treatOwnerFilter");
    if (!sel) return;
    const owners = [...new Set(plans.map(p => p.owner).filter(Boolean))].sort();
    const current = sel.value;
    sel.innerHTML = `<option value="all">Todos os owners</option>`;
    owners.forEach(o => {
        const opt = document.createElement("option");
        opt.value = o; opt.textContent = o;
        if (o === current) opt.selected = true;
        sel.appendChild(opt);
    });
}

function render() {
    const allPlans = loadPlans().map(normalizePlan);
    savePlans(allPlans);

    populateOwnerFilter(allPlans);

    const plans = applyFilters(allPlans);

    const cols = {
        "To do":     document.getElementById("colTodo"),
        "Em curso":  document.getElementById("colDoing"),
        "Concluído": document.getElementById("colDone"),
        "Em atraso": document.getElementById("colOverdue"),
    };
    Object.values(cols).forEach(c => { if (c) c.innerHTML = ""; });

    plans.forEach(p => {
        const col = cols[p.status];
        if (!col) return;

        const card = document.createElement("div");
        card.className = "kcard";
        card.draggable = true;
        card.dataset.id = p.id;

        const barColor = STATUS_BAR[p.status] || "#94a3b8";
        const badge    = dueBadge(p.due, p.status);
        const desc     = (p.planDescription || "").slice(0, 100) || "<span class='muted'>Sem descrição</span>";

        card.innerHTML = `
            <div class="kcard-urgency-bar" style="background:${barColor}"></div>
            <div style="padding-left:8px">
                <div class="kcard-top" style="align-items:flex-start">
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                            <b style="font-size:13px">${p.id}</b>
                            <span class="${priorityClass(p.priority || "Média")}" style="font-size:10px">${p.priority || "Média"}</span>
                        </div>
                        <div class="kmeta muted" style="font-size:11px;margin-top:2px">${p.asset || "—"} · ${p.strategy || "—"}</div>
                    </div>
                </div>

                <div class="kdesc" style="font-size:12px;margin:7px 0">${desc}</div>

                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        ${badge}
                        <span class="kcard-owner">
                            <span class="kcard-owner-avatar">${ownerInitials(p.owner)}</span>
                            ${p.owner || "—"}
                        </span>
                    </div>
                    <button class="btn small" type="button" data-detail="${p.id}" style="font-size:11px">
                        Ver detalhes
                    </button>
                </div>
            </div>
        `;

        card.addEventListener("dragstart", (e) => {
            e.dataTransfer.setData("text/plain", p.id);
            e.dataTransfer.effectAllowed = "move";
        });

        col.appendChild(card);
    });

    // KPIs — sempre sobre allPlans (não filtrados)
    const by = (s) => allPlans.filter(p => p.status === s).length;
    const total   = allPlans.length;
    const done    = by("Concluído");
    const doing   = by("Em curso");
    const todo    = by("To do");
    const overdue = by("Em atraso");

    const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setText("kpiTotal",   total);
    setText("kpiTodo",    todo);
    setText("kpiDoing",   doing);
    setText("kpiDone",    done);
    setText("kpiOverdue", overdue);
    setText("countTodo",    todo);
    setText("countDoing",   doing);
    setText("countDone",    done);
    setText("countOverdue", overdue);

    // barra de progresso
    const pct = v => total > 0 ? `${Math.round(v / total * 100)}%` : "0%";
    const setW = (id, v) => { const el = document.getElementById(id); if (el) el.style.width = pct(v); };
    setW("treatProgDone",    done);
    setW("treatProgDoing",   doing);
    setW("treatProgTodo",    todo);
    setW("treatProgOverdue", overdue);

    const pctDone = total > 0 ? Math.round(done / total * 100) : 0;
    setText("kpiProgressPct", `${pctDone}%`);

    // botões detalhes
    document.querySelectorAll("[data-detail]").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            openDetail(btn.getAttribute("data-detail"));
        });
    });
}

function wireDnD() {
    document.querySelectorAll(".kanban-col").forEach(col => {
        const status = col.dataset.status;

        col.addEventListener("dragover", (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = "move";
            col.querySelector(".kanban-drop")?.classList.add("drag-over");
        });

        col.addEventListener("dragleave", (e) => {
            if (!col.contains(e.relatedTarget)) {
                col.querySelector(".kanban-drop")?.classList.remove("drag-over");
            }
        });

        col.addEventListener("drop", (e) => {
            e.preventDefault();
            col.querySelector(".kanban-drop")?.classList.remove("drag-over");

            const id = e.dataTransfer.getData("text/plain");
            const plans = loadPlans().map(normalizePlan);
            const idx = plans.findIndex(x => String(x.id) === String(id));
            if (idx === -1) return;

            plans[idx].status = status;
            savePlans(plans);
            render();
        });
    });
}

// ── Modal ──
let currentId = null;

function openModal(id) {
    const el = document.getElementById(id);
    el?.classList.remove("is-hidden");
    document.body.style.overflow = "hidden";
}
function closeModal(id) {
    const el = document.getElementById(id);
    el?.classList.add("is-hidden");
    document.body.style.overflow = "";
}

// Badge do status para o modal header
function statusBadgeClass(status) {
    if (status === "Em curso")  return "tsb-doing";
    if (status === "Concluído") return "tsb-done";
    if (status === "Em atraso") return "tsb-overdue";
    return "tsb-todo";
}

// Renderiza passos das ações IA
function renderAiSteps(aiActions) {
    const container = document.getElementById("td_ai_actions");
    if (!container) return;
    const steps = (aiActions || "").split("\n").map(s => s.trim()).filter(Boolean);
    if (!steps.length) {
        container.innerHTML = `<span class="muted" style="font-size:12px">Sem sugestões registadas.</span>`;
        return;
    }
    container.innerHTML = steps.map((s, i) => `
        <div class="treat-ai-step">
            <div class="treat-ai-step-num">${i + 1}</div>
            <div>${s}</div>
        </div>
    `).join("");
}

// Indicador de prazo no modal
function renderDeadlineBox(due, status) {
    const box = document.getElementById("td_deadline_box");
    const msg = document.getElementById("td_deadline_msg");
    if (!box || !msg) return;

    if (status === "Concluído" || !due) { box.style.display = "none"; return; }

    const days = daysUntil(due);
    box.style.display = "flex";
    box.className = "treat-deadline-box";

    if (days < 0) {
        box.classList.add("overdue");
        msg.textContent = `Prazo ultrapassado há ${Math.abs(days)} dia${Math.abs(days) !== 1 ? "s" : ""}`;
    } else if (days === 0) {
        box.classList.add("soon");
        msg.textContent = "Prazo termina hoje";
    } else if (days <= 5) {
        box.classList.add("soon");
        msg.textContent = `Prazo em ${days} dia${days !== 1 ? "s" : ""}`;
    } else {
        box.classList.add("ok");
        msg.textContent = `Prazo: ${due} (${days} dias)`;
    }
}

function openDetail(id) {
    const plans = loadPlans().map(normalizePlan);
    const p = plans.find(x => String(x.id) === String(id));
    if (!p) return;

    currentId = p.id;

    // Header
    document.getElementById("td_title").textContent = `Plano ${p.id}`;
    const badge = document.getElementById("td_status_badge");
    if (badge) {
        badge.textContent = p.status;
        badge.className = `treat-status-badge ${statusBadgeClass(p.status)}`;
    }

    // Context strip
    const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || "—"; };
    setText("td_asset_disp",   p.asset);
    setText("td_risk_disp",    p.risk);
    setText("td_source_disp",  `${p.alertId || "—"} (${p.source || "—"})`);
    setText("td_alert_ref",    `Ref: ${p.alertId || "—"}`);

    // Data criação
    const created = p.createdAt ? new Date(p.createdAt).toLocaleDateString("pt-PT") : "—";
    setText("td_created_disp", created);

    // IA steps
    renderAiSteps(p.aiActions);

    // Campos editáveis
    const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ""; };
    setVal("td_desc",     p.planDescription);
    setVal("td_evidence", p.evidence);
    setVal("td_owner",    p.owner);
    setVal("td_due",      p.due);
    setVal("td_priority", p.priority);
    setVal("td_status",   p.status);
    setVal("td_strategy", p.strategy);

    // Deadline box
    renderDeadlineBox(p.due, p.status);

    // Update deadline box when date changes
    document.getElementById("td_due")?.addEventListener("input", () => {
        renderDeadlineBox(
            document.getElementById("td_due").value,
            document.getElementById("td_status").value
        );
    });
    document.getElementById("td_status")?.addEventListener("change", () => {
        renderDeadlineBox(
            document.getElementById("td_due").value,
            document.getElementById("td_status").value
        );
    });

    openModal("treatDetailModal");
}

function saveDetail() {
    const plans = loadPlans().map(normalizePlan);
    const idx = plans.findIndex(x => String(x.id) === String(currentId));
    if (idx === -1) return;

    plans[idx].owner           = document.getElementById("td_owner").value.trim();
    plans[idx].due             = document.getElementById("td_due").value.trim();
    plans[idx].priority        = document.getElementById("td_priority").value;
    plans[idx].status          = document.getElementById("td_status").value;
    plans[idx].strategy        = document.getElementById("td_strategy").value;
    plans[idx].planDescription = document.getElementById("td_desc").value.trim();
    plans[idx].evidence        = document.getElementById("td_evidence").value.trim();

    savePlans(plans);
    closeModal("treatDetailModal");
    render();
}

document.addEventListener("DOMContentLoaded", () => {
    seedIfEmpty();
    render();
    wireDnD();

    // Filtros
    document.getElementById("treatSearch")?.addEventListener("input", (e) => {
        filterSearch = e.target.value;
        render();
    });
    document.getElementById("treatPriorityFilter")?.addEventListener("change", (e) => {
        filterPriority = e.target.value;
        render();
    });
    document.getElementById("treatOwnerFilter")?.addEventListener("change", (e) => {
        filterOwner = e.target.value;
        render();
    });

    // Modal
    document.getElementById("td_close")?.addEventListener("click",  () => closeModal("treatDetailModal"));
    document.getElementById("td_close2")?.addEventListener("click", () => closeModal("treatDetailModal"));
    document.getElementById("td_save")?.addEventListener("click",   saveDetail);

    document.getElementById("treatDetailModal")?.addEventListener("click", (e) => {
        if (e.target?.id === "treatDetailModal") closeModal("treatDetailModal");
    });
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeModal("treatDetailModal");
    });
});
