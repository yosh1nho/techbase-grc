// resources/js/pages/treatment.js
// Techbase GRC — Tratamento v2 (+ Tasks, Comentários, Anexos)

const LS_KEY = "tb_mock_treatments";
const LS_TASKS_KEY = "tb_mock_tasks";       // { [planId]: Task[] }
const LS_COMMENTS_KEY = "tb_mock_comments";  // { [taskId]: Comment[] }

// ── Storage helpers ───────────────────────────────────────────────────────────
function loadPlans() { try { return JSON.parse(localStorage.getItem(LS_KEY) || "[]"); } catch { return []; } }
function savePlans(p) { localStorage.setItem(LS_KEY, JSON.stringify(p)); }

function loadAllTasks() { try { return JSON.parse(localStorage.getItem(LS_TASKS_KEY) || "{}"); } catch { return {}; } }
function saveAllTasks(t) { localStorage.setItem(LS_TASKS_KEY, JSON.stringify(t)); }
function loadTasksForPlan(planId) { return loadAllTasks()[planId] || []; }
function saveTasksForPlan(planId, tasks) {
    const all = loadAllTasks();
    all[planId] = tasks;
    saveAllTasks(all);
}

function loadAllComments() { try { return JSON.parse(localStorage.getItem(LS_COMMENTS_KEY) || "{}"); } catch { return {}; } }
function saveAllComments(c) { localStorage.setItem(LS_COMMENTS_KEY, JSON.stringify(c)); }
function loadCommentsForTask(taskId) { return loadAllComments()[taskId] || []; }
function saveCommentsForTask(taskId, comments) {
    const all = loadAllComments();
    all[taskId] = comments;
    saveAllComments(all);
}

// ── Seed ─────────────────────────────────────────────────────────────────────
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

    // Seed tasks para TP-1001
    saveTasksForPlan("TP-1001", [
        {
            id: "TK-001", planId: "TP-1001",
            title: "Isolar host SRV-DB-01 da rede",
            description: "Remover da VLAN de produção e mover para VLAN de quarentena. Documentar hora e responsável.",
            status: "Concluído", assignedTo: "IT Ops", due: "2026-02-19",
            createdAt: new Date().toISOString()
        },
        {
            id: "TK-002", planId: "TP-1001",
            title: "Executar varredura EDR completa",
            description: "Correr scan completo com Acronis EDR e exportar relatório de findings.",
            status: "Em curso", assignedTo: "SOC", due: "2026-02-22",
            createdAt: new Date().toISOString()
        },
        {
            id: "TK-003", planId: "TP-1001",
            title: "Rever e atualizar allowlist de execução",
            description: "Comparar processos autorizados vs encontrados. Bloquear executáveis não assinados.",
            status: "To do", assignedTo: "AppSec", due: "2026-02-28",
            createdAt: new Date().toISOString()
        }
    ]);
}

// ── Helpers de UI ─────────────────────────────────────────────────────────────
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
function normalizePlan(p) { return { ...p, status: p.status || "To do" }; }
function priorityClass(p) {
    if (p === "Alta") return "chip bad";
    if (p === "Média") return "chip warn";
    return "chip";
}
const STATUS_BAR = {
    "To do": "#94a3b8",
    "Em curso": "#60a5fa",
    "Concluído": "#34d399",
    "Em atraso": "#f87171",
};
function ownerInitials(owner) {
    if (!owner) return "?";
    return owner.split(/[\s-]+/).map(w => w[0]).join("").toUpperCase().slice(0, 2);
}
function dueBadge(due, status) {
    if (status === "Concluído") return "";
    const days = daysUntil(due);
    if (days === null) return "";
    if (days < 0) return `<span class="kcard-due-badge kcard-due-overdue">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        ${Math.abs(days)}d atraso</span>`;
    if (days <= 5) return `<span class="kcard-due-badge kcard-due-soon">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        ${days}d restantes</span>`;
    return `<span class="kcard-due-badge kcard-due-ok">${due}</span>`;
}
function taskStatusClass(s) {
    if (s === "Em curso") return "task-status-doing";
    if (s === "Concluído") return "task-status-done";
    return "task-status-todo";
}
function fileIcon(mime) {
    if (!mime) return "📎";
    if (mime.startsWith("image/")) return "🖼️";
    if (mime === "application/pdf") return "📄";
    if (mime.includes("word")) return "📝";
    if (mime.includes("excel") || mime.includes("sheet")) return "📊";
    return "📎";
}
function formatFileSize(bytes) {
    if (!bytes) return "";
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
function showToast(msg, type = "success") {
    const el = document.getElementById("treat_toast");
    if (!el) return;
    el.textContent = msg;
    el.className = `treat-toast treat-toast-${type}`;
    el.classList.remove("is-hidden");
    clearTimeout(el._timer);
    el._timer = setTimeout(() => el.classList.add("is-hidden"), 3000);
}

// ── Filters ──────────────────────────────────────────────────────────────────
let filterSearch = "";
let filterPriority = "all";
let filterOwner = "all";

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

// ── Render Kanban ─────────────────────────────────────────────────────────────
function render() {
    const allPlans = loadPlans().map(normalizePlan);
    savePlans(allPlans);
    populateOwnerFilter(allPlans);
    const plans = applyFilters(allPlans);

    const cols = {
        "To do": document.getElementById("colTodo"),
        "Em curso": document.getElementById("colDoing"),
        "Concluído": document.getElementById("colDone"),
        "Em atraso": document.getElementById("colOverdue"),
    };
    Object.values(cols).forEach(c => { if (c) c.innerHTML = ""; });

    plans.forEach(p => {
        const col = cols[p.status];
        if (!col) return;

        const tasks = loadTasksForPlan(p.id);
        const tasksDone = tasks.filter(t => t.status === "Concluído").length;
        const tasksTotal = tasks.length;

        const card = document.createElement("div");
        card.className = "kcard";
        card.draggable = true;
        card.dataset.id = p.id;

        const barColor = STATUS_BAR[p.status] || "#94a3b8";
        const badge = dueBadge(p.due, p.status);
        const desc = (p.planDescription || "").slice(0, 100) || "<span class='muted'>Sem descrição</span>";
        const taskBadge = tasksTotal > 0
            ? `<span class="kcard-task-badge" title="${tasksDone}/${tasksTotal} tarefas concluídas">
                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                ${tasksDone}/${tasksTotal}
               </span>`
            : "";

        card.innerHTML = `
            <div class="kcard-urgency-bar" style="background:${barColor}"></div>
            <div style="padding-left:8px">
                <div class="kcard-top" style="align-items:flex-start">
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                            <b style="font-size:13px">${p.id}</b>
                            <span class="${priorityClass(p.priority || "Média")}" style="font-size:10px">${p.priority || "Média"}</span>
                            ${taskBadge}
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

    // KPIs
    const by = (s) => allPlans.filter(p => p.status === s).length;
    const total = allPlans.length;
    const done = by("Concluído");
    const doing = by("Em curso");
    const todo = by("To do");
    const overdue = by("Em atraso");

    const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setText("kpiTotal", total);
    setText("kpiTodo", todo);
    setText("kpiDoing", doing);
    setText("kpiDone", done);
    setText("kpiOverdue", overdue);
    setText("countTodo", todo);
    setText("countDoing", doing);
    setText("countDone", done);
    setText("countOverdue", overdue);

    const pct = v => total > 0 ? `${Math.round(v / total * 100)}%` : "0%";
    const setW = (id, v) => { const el = document.getElementById(id); if (el) el.style.width = pct(v); };
    setW("treatProgDone", done);
    setW("treatProgDoing", doing);
    setW("treatProgTodo", todo);
    setW("treatProgOverdue", overdue);

    const pctDone = total > 0 ? Math.round(done / total * 100) : 0;
    setText("kpiProgressPct", `${pctDone}%`);

    document.querySelectorAll("[data-detail]").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            openDetail(btn.getAttribute("data-detail"));
        });
    });
}

// ── DnD ───────────────────────────────────────────────────────────────────────
function wireDnD() {
    document.querySelectorAll(".kanban-col").forEach(col => {
        const status = col.dataset.status;
        col.addEventListener("dragover", (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = "move";
            col.querySelector(".kanban-drop")?.classList.add("drag-over");
        });
        col.addEventListener("dragleave", (e) => {
            if (!col.contains(e.relatedTarget))
                col.querySelector(".kanban-drop")?.classList.remove("drag-over");
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

// ── Modal helpers ─────────────────────────────────────────────────────────────
function openModal(id) { document.getElementById(id)?.classList.remove("is-hidden"); document.body.style.overflow = "hidden"; }
function closeModal(id) { document.getElementById(id)?.classList.add("is-hidden"); document.body.style.overflow = ""; }
function statusBadgeClass(status) {
    if (status === "Em curso") return "tsb-doing";
    if (status === "Concluído") return "tsb-done";
    if (status === "Em atraso") return "tsb-overdue";
    return "tsb-todo";
}
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

// ── Tabs ──────────────────────────────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll(".treat-tab-btn").forEach(b => b.classList.toggle("active", b.dataset.tab === tab));
    document.querySelectorAll(".treat-tab-panel").forEach(p => {
        p.classList.toggle("is-hidden", p.id !== `tabPanel_${tab}`);
    });
}

// ── Plan detail modal ─────────────────────────────────────────────────────────
let currentId = null;
let pendingFiles = [];  // files staged in composer

function openDetail(id) {
    const plans = loadPlans().map(normalizePlan);
    const p = plans.find(x => String(x.id) === String(id));
    if (!p) return;

    currentId = p.id;

    document.getElementById("td_title").textContent = `Plano ${p.id}`;
    const badge = document.getElementById("td_status_badge");
    if (badge) {
        badge.textContent = p.status;
        badge.className = `treat-status-badge ${statusBadgeClass(p.status)}`;
    }

    const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || "—"; };
    setText("td_asset_disp", p.asset);
    setText("td_risk_disp", p.risk);
    setText("td_source_disp", `${p.alertId || "—"} (${p.source || "—"})`);
    setText("td_alert_ref", `Ref: ${p.alertId || "—"}`);

    const created = p.createdAt ? new Date(p.createdAt).toLocaleDateString("pt-PT") : "—";
    setText("td_created_disp", created);

    renderAiSteps(p.aiActions);

    const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ""; };
    setVal("td_desc", p.planDescription);
    setVal("td_evidence", p.evidence);
    setVal("td_owner", p.owner);
    setVal("td_due", p.due);
    setVal("td_priority", p.priority);
    setVal("td_status", p.status);
    setVal("td_strategy", p.strategy);

    renderDeadlineBox(p.due, p.status);

    document.getElementById("td_due")?.addEventListener("input", () => {
        renderDeadlineBox(document.getElementById("td_due").value, document.getElementById("td_status").value);
    });
    document.getElementById("td_status")?.addEventListener("change", () => {
        renderDeadlineBox(document.getElementById("td_due").value, document.getElementById("td_status").value);
    });

    // Render tasks tab count
    renderTasksTab(p.id);

    // Reset to details tab
    switchTab("details");

    openModal("treatDetailModal");
}

function saveDetail() {
    const plans = loadPlans().map(normalizePlan);
    const idx = plans.findIndex(x => String(x.id) === String(currentId));
    if (idx === -1) return;

    plans[idx].owner = document.getElementById("td_owner").value.trim();
    plans[idx].due = document.getElementById("td_due").value.trim();
    plans[idx].priority = document.getElementById("td_priority").value;
    plans[idx].status = document.getElementById("td_status").value;
    plans[idx].strategy = document.getElementById("td_strategy").value;
    plans[idx].planDescription = document.getElementById("td_desc").value.trim();
    plans[idx].evidence = document.getElementById("td_evidence").value.trim();

    savePlans(plans);
    closeModal("treatDetailModal");
    render();
    showToast("Plano guardado com sucesso.");
}

// ── Tasks Tab ─────────────────────────────────────────────────────────────────
function renderTasksTab(planId) {
    const tasks = loadTasksForPlan(planId);
    const done = tasks.filter(t => t.status === "Concluído").length;
    const total = tasks.length;
    const pct = total > 0 ? Math.round(done / total * 100) : 0;

    // Count badge on tab
    const countEl = document.getElementById("td_task_count");
    if (countEl) countEl.textContent = total;

    // Progress bar
    const fill = document.getElementById("td_tasks_progress_fill");
    const pctEl = document.getElementById("td_tasks_pct");
    if (fill) fill.style.width = `${pct}%`;
    if (pctEl) pctEl.textContent = `${pct}%`;

    // List
    const list = document.getElementById("td_tasks_list");
    if (!list) return;

    if (!tasks.length) {
        list.innerHTML = `<div class="tasks-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:.3;margin-bottom:8px"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            <div>Sem tarefas criadas</div>
            <div class="muted" style="font-size:11px;margin-top:2px">Clica em "Nova tarefa" para começar</div>
        </div>`;
        return;
    }

    list.innerHTML = tasks.map(t => {
        const comments = loadCommentsForTask(t.id);
        const commentCount = comments.length;
        const attachCount = comments.reduce((acc, c) => acc + (c.attachments?.length || 0), 0);
        const statusCls = taskStatusClass(t.status);

        return `
        <div class="task-row" data-task-id="${t.id}">
            <div class="task-row-status">
                <span class="task-status-dot ${statusCls}"></span>
            </div>
            <div class="task-row-body">
                <div class="task-row-title">${t.title}</div>
                <div class="task-row-meta">
                    ${t.assignedTo ? `<span class="task-meta-chip"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>${t.assignedTo}</span>` : ""}
                    ${t.due ? `<span class="task-meta-chip ${isOverdue(t.due) && t.status !== "Concluído" ? "task-meta-chip-overdue" : ""}"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>${t.due}</span>` : ""}
                    ${commentCount > 0 ? `<span class="task-meta-chip"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>${commentCount}</span>` : ""}
                    ${attachCount > 0 ? `<span class="task-meta-chip"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>${attachCount}</span>` : ""}
                </div>
            </div>
            <div class="task-row-right">
                <span class="task-status-badge ${statusCls}">${t.status}</span>
                <button class="btn small" type="button" data-open-task="${t.id}">Abrir</button>
            </div>
        </div>`;
    }).join("");

    // Wire open buttons
    list.querySelectorAll("[data-open-task]").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            openTaskModal(btn.getAttribute("data-open-task"));
        });
    });
}

// ── New Task Modal ────────────────────────────────────────────────────────────
function openNewTaskModal() {
    // Set plan label
    const plans = loadPlans();
    const plan = plans.find(p => p.id === currentId);
    const label = document.getElementById("ntm_plan_label");
    if (label) label.textContent = plan ? plan.id : currentId;

    clearTaskForm();
    openModal("newTaskModal");
    setTimeout(() => document.getElementById("tf_title")?.focus(), 80);
}

function closeNewTaskModal() {
    closeModal("newTaskModal");
    clearTaskForm();
}

function wireNewTaskForm() {
    document.getElementById("td_btn_new_task")?.addEventListener("click", openNewTaskModal);

    document.getElementById("ntm_close")?.addEventListener("click", closeNewTaskModal);
    document.getElementById("tf_cancel")?.addEventListener("click", closeNewTaskModal);

    // Click outside closes
    document.getElementById("newTaskModal")?.addEventListener("click", (e) => {
        if (e.target.id === "newTaskModal") closeNewTaskModal();
    });

    document.getElementById("tf_save")?.addEventListener("click", () => {
        const title = document.getElementById("tf_title")?.value.trim();
        if (!title) {
            document.getElementById("tf_title")?.focus();
            document.getElementById("tf_title")?.classList.add("input-error");
            setTimeout(() => document.getElementById("tf_title")?.classList.remove("input-error"), 1200);
            return;
        }
        const task = {
            id: `TK-${Date.now()}`,
            planId: currentId,
            title,
            description: document.getElementById("tf_desc")?.value.trim() || "",
            status: document.getElementById("tf_status")?.value || "To do",
            assignedTo: document.getElementById("tf_assigned")?.value.trim() || "",
            due: document.getElementById("tf_due")?.value || "",
            createdAt: new Date().toISOString(),
        };

        const tasks = loadTasksForPlan(currentId);
        tasks.push(task);
        saveTasksForPlan(currentId, tasks);

        closeNewTaskModal();
        renderTasksTab(currentId);
        render();
        showToast("Tarefa criada.");
    });

    // Esc closes
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !document.getElementById("newTaskModal")?.classList.contains("is-hidden")) {
            closeNewTaskModal();
        }
    });
}

function clearTaskForm() {
    ["tf_title", "tf_desc", "tf_assigned", "tf_due"].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = "";
    });
    const sel = document.getElementById("tf_status");
    if (sel) sel.value = "To do";
}

// ── Task Detail Modal ─────────────────────────────────────────────────────────
let currentTaskId = null;
let currentTaskPlanId = null;
pendingFiles = [];

function openTaskModal(taskId) {
    const allTasks = loadAllTasks();
    let task = null;
    let planId = null;

    for (const [pid, tasks] of Object.entries(allTasks)) {
        const found = tasks.find(t => t.id === taskId);
        if (found) { task = found; planId = pid; break; }
    }
    if (!task) return;

    currentTaskId = task.id;
    currentTaskPlanId = planId;
    pendingFiles = [];

    // Header
    document.getElementById("tkm_plan_ref").textContent = planId;
    document.getElementById("tkm_title").textContent = task.title;
    document.getElementById("tkm_status").value = task.status;

    // Meta
    document.getElementById("tkm_assigned").textContent = task.assignedTo || "—";
    document.getElementById("tkm_due").textContent = task.due || "—";
    document.getElementById("tkm_created").textContent = task.createdAt
        ? new Date(task.createdAt).toLocaleDateString("pt-PT") : "—";

    // Desc
    document.getElementById("tkm_desc").textContent = task.description || "Sem descrição.";

    // Edit sidebar
    document.getElementById("tkm_edit_title").value = task.title;
    document.getElementById("tkm_edit_desc").value = task.description || "";
    document.getElementById("tkm_edit_assigned").value = task.assignedTo || "";
    document.getElementById("tkm_edit_due").value = task.due || "";

    // Comments
    renderComments(task.id);

    // Clear composer
    const inp = document.getElementById("tkm_comment_input");
    if (inp) inp.value = "";
    clearAttachPreview();

    // Close plan modal first, then open task modal alone (bigger, cleaner)
    closeModal("treatDetailModal");
    openModal("taskDetailModal");
}

function renderComments(taskId) {
    const comments = loadCommentsForTask(taskId);
    const list = document.getElementById("tkm_comments_list");
    const countEl = document.getElementById("tkm_comment_count");
    if (countEl) countEl.textContent = comments.length;
    if (!list) return;

    if (!comments.length) {
        list.innerHTML = `<div class="comments-empty">Sem comentários ainda. Sê o primeiro a comentar.</div>`;
        return;
    }

    list.innerHTML = comments.map((c, ci) => `
        <div class="comment-item" data-comment-idx="${ci}">
            <div class="comment-avatar">${ownerInitials(c.author)}</div>
            <div class="comment-body">
                <div class="comment-header">
                    <span class="comment-author">${c.author || "Utilizador"}</span>
                    <span class="comment-ts">${c.createdAt ? new Date(c.createdAt).toLocaleString("pt-PT") : "—"}</span>
                </div>
                ${c.content ? `<div class="comment-text">${c.content}</div>` : ""}
                ${(c.attachments || []).length ? renderAttachments(c.attachments, ci) : ""}
            </div>
        </div>
    `).join("");
}

function renderAttachments(attachments, commentIdx) {
    return `<div class="comment-attachments">
        ${attachments.map((a, ai) => `
            <div class="attach-chip" data-comment-idx="${commentIdx}" data-attach-idx="${ai}">
                <span class="attach-icon">${fileIcon(a.mime)}</span>
                <span class="attach-name">${a.name}</span>
                ${a.size ? `<span class="attach-size">${formatFileSize(a.size)}</span>` : ""}

                <div class="attach-actions">
                    <button type="button" class="attach-action-btn" data-action="download"
                        data-comment-idx="${commentIdx}" data-attach-idx="${ai}"
                        title="Descarregar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download
                    </button>
                    <button type="button" class="attach-action-btn attach-action-docs" data-action="send-docs"
                        data-comment-idx="${commentIdx}" data-attach-idx="${ai}"
                        title="Enviar para Documentos & Evidências">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="12" y2="12" /><line x1="15" y1="15" x2="12" y2="12"/></svg>
                        Enviar para Docs
                    </button>
                    <button type="button" class="attach-action-btn attach-action-delete" data-action="delete"
                        data-comment-idx="${commentIdx}" data-attach-idx="${ai}"
                        title="Eliminar anexo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                        Eliminar
                    </button>
                </div>
            </div>
        `).join("")}
    </div>`;
}

// ── Composer / file attachment ────────────────────────────────────────────────
function clearAttachPreview() {
    pendingFiles = [];
    const preview = document.getElementById("tkm_attach_preview");
    if (preview) { preview.innerHTML = ""; preview.classList.add("is-hidden"); }
}

function renderAttachPreview() {
    const preview = document.getElementById("tkm_attach_preview");
    if (!preview) return;
    if (!pendingFiles.length) { preview.innerHTML = ""; preview.classList.add("is-hidden"); return; }
    preview.classList.remove("is-hidden");
    preview.innerHTML = pendingFiles.map((f, i) => `
        <div class="attach-preview-chip">
            <span class="attach-icon">${fileIcon(f.type)}</span>
            <span class="attach-name">${f.name}</span>
            <span class="attach-size">${formatFileSize(f.size)}</span>
            <button type="button" class="attach-preview-remove" data-idx="${i}" title="Remover">×</button>
        </div>
    `).join("");

    preview.querySelectorAll(".attach-preview-remove").forEach(btn => {
        btn.addEventListener("click", () => {
            pendingFiles.splice(Number(btn.dataset.idx), 1);
            renderAttachPreview();
        });
    });
}

function wireTaskModal() {
    // Back / Close — reopen plan modal on the tasks tab
    const goBackToPlan = () => {
        closeModal("taskDetailModal");
        if (currentTaskPlanId) {
            openDetail(currentTaskPlanId);
            setTimeout(() => switchTab("tasks"), 0);
        }
    };
    document.getElementById("tkm_back")?.addEventListener("click", goBackToPlan);
    document.getElementById("tkm_close")?.addEventListener("click", goBackToPlan);
    document.getElementById("taskDetailModal")?.addEventListener("click", (e) => {
        if (e.target?.id === "taskDetailModal") goBackToPlan();
    });

    // Status change
    document.getElementById("tkm_status")?.addEventListener("change", (e) => {
        if (!currentTaskId || !currentTaskPlanId) return;
        const tasks = loadTasksForPlan(currentTaskPlanId);
        const idx = tasks.findIndex(t => t.id === currentTaskId);
        if (idx === -1) return;
        tasks[idx].status = e.target.value;
        saveTasksForPlan(currentTaskPlanId, tasks);
        renderTasksTab(currentTaskPlanId);
        render();
    });

    // Save meta
    document.getElementById("tkm_save_meta")?.addEventListener("click", () => {
        if (!currentTaskId || !currentTaskPlanId) return;
        const tasks = loadTasksForPlan(currentTaskPlanId);
        const idx = tasks.findIndex(t => t.id === currentTaskId);
        if (idx === -1) return;
        tasks[idx].title = document.getElementById("tkm_edit_title").value.trim() || tasks[idx].title;
        tasks[idx].description = document.getElementById("tkm_edit_desc").value.trim();
        tasks[idx].assignedTo = document.getElementById("tkm_edit_assigned").value.trim();
        tasks[idx].due = document.getElementById("tkm_edit_due").value;
        saveTasksForPlan(currentTaskPlanId, tasks);

        // Refresh header
        document.getElementById("tkm_title").textContent = tasks[idx].title;
        document.getElementById("tkm_assigned").textContent = tasks[idx].assignedTo || "—";
        document.getElementById("tkm_due").textContent = tasks[idx].due || "—";
        document.getElementById("tkm_desc").textContent = tasks[idx].description || "Sem descrição.";

        renderTasksTab(currentTaskPlanId);
        render();
        showToast("Tarefa guardada.");
    });

    // Delete task
    document.getElementById("tkm_delete_task")?.addEventListener("click", () => {
        if (!currentTaskId || !currentTaskPlanId) return;
        if (!confirm("Eliminar esta tarefa e todos os seus comentários?")) return;
        const tasks = loadTasksForPlan(currentTaskPlanId).filter(t => t.id !== currentTaskId);
        saveTasksForPlan(currentTaskPlanId, tasks);
        const allC = loadAllComments();
        delete allC[currentTaskId];
        saveAllComments(allC);
        const planId = currentTaskPlanId;
        closeModal("taskDetailModal");
        render();
        openDetail(planId);
        setTimeout(() => switchTab("tasks"), 0);
        showToast("Tarefa eliminada.", "warn");
    });

    // File input
    document.getElementById("tkm_file_input")?.addEventListener("change", (e) => {
        const files = Array.from(e.target.files || []);
        pendingFiles = [...pendingFiles, ...files];
        renderAttachPreview();
        e.target.value = ""; // reset input
    });

    // Send comment
    document.getElementById("tkm_send_comment")?.addEventListener("click", sendComment);
    document.getElementById("tkm_comment_input")?.addEventListener("keydown", (e) => {
        if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) sendComment();
    });

    // Attachment actions (delegated on comments list)
    document.getElementById("tkm_comments_list")?.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-action]");
        if (!btn) return;
        const action = btn.dataset.action;
        const commentIdx = Number(btn.dataset.commentIdx);
        const attachIdx = Number(btn.dataset.attachIdx);
        handleAttachAction(action, commentIdx, attachIdx);
    });
}

function sendComment() {
    const inp = document.getElementById("tkm_comment_input");
    const content = inp?.value.trim();

    if (!content && !pendingFiles.length) return;
    if (!currentTaskId) return;

    // Serialize file metadata (in real app you'd upload; here we store name/size/mime)
    const attachments = pendingFiles.map(f => ({
        id: `ATT-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
        name: f.name,
        size: f.size,
        mime: f.type,
        // In production: url from upload response
        dataUrl: null, // we don't store binary in localStorage
    }));

    const comment = {
        id: `CMT-${Date.now()}`,
        taskId: currentTaskId,
        author: "Utilizador",
        content,
        attachments,
        createdAt: new Date().toISOString(),
    };

    const comments = loadCommentsForTask(currentTaskId);
    comments.push(comment);
    saveCommentsForTask(currentTaskId, comments);

    if (inp) inp.value = "";
    clearAttachPreview();
    renderComments(currentTaskId);
    showToast("Comentário enviado.");
}

function handleAttachAction(action, commentIdx, attachIdx) {
    const comments = loadCommentsForTask(currentTaskId);
    const comment = comments[commentIdx];
    if (!comment) return;
    const attachment = comment.attachments?.[attachIdx];
    if (!attachment && action !== "delete") return;

    if (action === "download") {
        // In production: window.open(attachment.url)
        // Mock: show toast
        showToast(`A descarregar "${attachment.name}"…`);
    }

    if (action === "send-docs") {
        // In production: call API to create a pending doc in the Docs & Evidence module
        // Mark attachment as "sent to docs"
        comment.attachments[attachIdx].sentToDocs = true;
        comment.attachments[attachIdx].sentToDocsAt = new Date().toISOString();
        saveCommentsForTask(currentTaskId, comments);
        renderComments(currentTaskId);
        showToast(`"${attachment.name}" enviado para Documentos & Evidências para aprovação.`);
    }

    if (action === "delete") {
        if (!confirm(`Eliminar o anexo "${attachment.name}"?`)) return;
        comment.attachments.splice(attachIdx, 1);
        saveCommentsForTask(currentTaskId, comments);
        renderComments(currentTaskId);
        showToast("Anexo eliminado.", "warn");
    }
}

// ── Wire everything ──────────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", () => {
    seedIfEmpty();
    render();
    wireDnD();
    wireNewTaskForm();
    wireTaskModal();

    // Filters
    document.getElementById("treatSearch")?.addEventListener("input", (e) => {
        filterSearch = e.target.value; render();
    });
    document.getElementById("treatPriorityFilter")?.addEventListener("change", (e) => {
        filterPriority = e.target.value; render();
    });
    document.getElementById("treatOwnerFilter")?.addEventListener("change", (e) => {
        filterOwner = e.target.value; render();
    });

    // Detail modal
    document.getElementById("td_close")?.addEventListener("click", () => closeModal("treatDetailModal"));
    document.getElementById("td_close2")?.addEventListener("click", () => closeModal("treatDetailModal"));
    document.getElementById("td_save")?.addEventListener("click", saveDetail);
    document.getElementById("treatDetailModal")?.addEventListener("click", (e) => {
        if (e.target?.id === "treatDetailModal") closeModal("treatDetailModal");
    });

    // Tabs
    document.querySelectorAll(".treat-tab-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            switchTab(btn.dataset.tab);
            if (btn.dataset.tab === "tasks" && currentId) renderTasksTab(currentId);
        });
    });

    // ESC
    document.addEventListener("keydown", (e) => {
        if (e.key !== "Escape") return;
        const taskModal = document.getElementById("taskDetailModal");
        const planModal = document.getElementById("treatDetailModal");
        if (taskModal && !taskModal.classList.contains("is-hidden")) {
            // Go back to plan modal on tasks tab
            closeModal("taskDetailModal");
            if (currentTaskPlanId) {
                openDetail(currentTaskPlanId);
                setTimeout(() => switchTab("tasks"), 0);
            }
        } else if (planModal && !planModal.classList.contains("is-hidden")) {
            closeModal("treatDetailModal");
        }
    });
});
