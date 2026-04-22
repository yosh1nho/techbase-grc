// resources/js/pages/treatment.js
// Techbase GRC — Tratamento v2 — Integração API real

// ── Estado global ─────────────────────────────────────────────────────────────
let allPlans = [];      // cache dos planos vinda da API
let currentId = null;    // id_plan do modal de detalhe aberto
let currentTaskId = null;    // id_task do modal de tarefa aberto
let currentTaskPlanId = null; // id_plan da tarefa aberta
let pendingFiles = [];      // ficheiros staged no composer antes de enviar

let filterSearch = "";
let filterPriority = "all";
let filterOwner = "all";

// ── CSRF helper ───────────────────────────────────────────────────────────────
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? "";
}

// ── Permissões helper ────────────────────────────────────────────────────────
const hasPerm = (p) => window.TB_PERMISSIONS && window.TB_PERMISSIONS.includes(p);


//Helper para pegar as iniciais dos nome
function getInitials(name) {
    if (!name) return "U";
    const parts = name.trim().split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    return name.substring(0, 2).toUpperCase();
}
// ── Fetch helpers ─────────────────────────────────────────────────────────────
async function api(method, url, body = null) {
    const opts = {
        method,
        headers: { "X-CSRF-TOKEN": csrfToken(), "Accept": "application/json" },
    };
    if (body instanceof FormData) {
        opts.body = body; // multipart — não definir Content-Type (browser define boundary)
    } else if (body) {
        opts.headers["Content-Type"] = "application/json";
        opts.body = JSON.stringify(body);
    }
    const res = await fetch(url, opts);
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `HTTP ${res.status}`);
    }
    return res.json();
}

const GET = (url) => api("GET", url);
const POST = (url, body) => api("POST", url, body);
const PUT = (url, body) => api("PUT", url, body);
const DELETE = (url) => api("DELETE", url);

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
    const today = new Date(); today.setHours(0, 0, 0, 0);
    return Math.round((d - today) / 86400000);
}
function priorityClass(p) {
    if (p === "Alta") return "chip bad";
    if (p === "Média") return "chip warn";
    return "chip";
}
const STATUS_BAR = {
    "To do": "#94a3b8", "Em curso": "#60a5fa",
    "Concluído": "#34d399", "Em atraso": "#f87171",
};
function ownerInitials(owner) {
    if (!owner) return "?";
    return owner.split(/[\s-]+/).map(w => w[0]).join("").toUpperCase().slice(0, 2);
}
function dueBadge(due, status) {
    if (status === "Concluído") return "";
    const days = daysUntil(due);
    if (days === null) return "";
    if (days < 0) return `<span class="kcard-due-badge kcard-due-overdue"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>${Math.abs(days)}d atraso</span>`;
    if (days <= 5) return `<span class="kcard-due-badge kcard-due-soon"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>${days}d restantes</span>`;
    return `<span class="kcard-due-badge kcard-due-ok">${due}</span>`;
}
function taskStatusClass(s) {
    if (s === "Em curso") return "task-status-doing";
    if (s === "Concluído") return "task-status-done";
    return "task-status-todo";
}
function statusBadgeClass(status) {
    if (status === "Em curso") return "tsb-doing";
    if (status === "Concluído") return "tsb-done";
    if (status === "Em atraso") return "tsb-overdue";
    return "tsb-todo";
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
function openModal(id) { document.getElementById(id)?.classList.remove("is-hidden"); document.body.style.overflow = "hidden"; }
function closeModal(id) { document.getElementById(id)?.classList.add("is-hidden"); document.body.style.overflow = ""; }

// ── Filtros ───────────────────────────────────────────────────────────────────
function applyFilters(plans) {
    return plans.filter(p => {
        const q = filterSearch.toLowerCase();
        const matchQ = !q ||
            String(p.id).toLowerCase().includes(q) ||
            (p.asset_name || "").toLowerCase().includes(q) ||
            (p.owner_name || "").toLowerCase().includes(q) ||
            (p.risk_title || "").toLowerCase().includes(q);
        const matchP = filterPriority === "all" || (p.priority || "") === filterPriority;
        const matchO = filterOwner === "all" || (p.owner_name || "") === filterOwner;
        return matchQ && matchP && matchO;
    });
}
function populateOwnerFilter(plans) {
    const sel = document.getElementById("treatOwnerFilter");
    if (!sel) return;
    const owners = [...new Set(plans.map(p => p.owner_name).filter(Boolean))].sort();
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

        const summary = p.task_summary ?? {};
        const tasksDone = summary.done ?? 0;
        const tasksTotal = summary.total ?? 0;
        const barColor = STATUS_BAR[p.status] || "#94a3b8";
        const badge = dueBadge(p.due_date, p.status);
        const desc = (p.risk_description || "").slice(0, 100) || "<span class='muted'>Sem descrição</span>";

        const taskBadge = tasksTotal > 0
            ? `<span class="kcard-task-badge" title="${tasksDone}/${tasksTotal} tarefas concluídas">
                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                ${tasksDone}/${tasksTotal}
               </span>`
            : "";

        const card = document.createElement("div");
        card.className = "kcard";
        card.draggable = true;
        card.dataset.id = p.id;

        card.innerHTML = `
            <div class="kcard-urgency-bar" style="background:${barColor}"></div>
            <div style="padding-left:8px">
                <div class="kcard-top" style="align-items:flex-start">
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                            <b style="font-size:13px">TP-${p.id}</b>
                            ${taskBadge}
                        </div>
                        <div class="kmeta muted" style="font-size:11px;margin-top:2px">${p.asset_name || "—"} · ${p.strategy || "—"}</div>
                    </div>
                </div>
                <div class="kdesc" style="font-size:12px;margin:7px 0">${desc}</div>
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        ${badge}
                        <span class="kcard-owner">
                            <span class="kcard-owner-avatar">${ownerInitials(p.owner_name)}</span>
                            ${p.owner_name || "—"}
                        </span>
                    </div>
                    <button class="btn small" type="button" data-detail="${p.id}" style="font-size:11px">Ver detalhes</button>
                </div>
            </div>`;

        card.addEventListener("dragstart", e => {
            e.dataTransfer.setData("text/plain", String(p.id));
            e.dataTransfer.effectAllowed = "move";
        });
        col.appendChild(card);
    });

    // KPIs
    const by = s => allPlans.filter(p => p.status === s).length;
    const total = allPlans.length;
    const done = by("Concluído"), doing = by("Em curso");
    const todo = by("To do"), over = by("Em atraso");

    const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setText("kpiTotal", total); setText("kpiTodo", todo);
    setText("kpiDoing", doing); setText("kpiDone", done); setText("kpiOverdue", over);
    setText("countTodo", todo); setText("countDoing", doing);
    setText("countDone", done); setText("countOverdue", over);

    const pct = v => total > 0 ? `${Math.round(v / total * 100)}%` : "0%";
    const setW = (id, v) => { const el = document.getElementById(id); if (el) el.style.width = pct(v); };
    setW("treatProgDone", done); setW("treatProgDoing", doing);
    setW("treatProgTodo", todo); setW("treatProgOverdue", over);
    setText("kpiProgressPct", `${total > 0 ? Math.round(done / total * 100) : 0}%`);

    document.querySelectorAll("[data-detail]").forEach(btn => {
        btn.addEventListener("click", e => {
            e.stopPropagation();
            openDetail(Number(btn.getAttribute("data-detail")));
        });
    });
}

// ── Carregar users para selects de "Designado" ───────────────────────────────
async function loadUsers() {
    try {
        const users = await GET("/api/users");
        const options = '<option value="">Sem designado</option>' +
            users.map(u => `<option value="${u.id_user}">${u.name || u.email}</option>`).join("");
        // Popular todos os selects de "Designado" na página
        ["tf_assigned", "tkm_edit_assigned", "td_owner"].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = options;
        });
    } catch (e) {
        console.warn("Não foi possível carregar utilizadores:", e.message);
    }
}

// ── Carregar planos da API ────────────────────────────────────────────────────
async function loadPlans() {
    try {
        allPlans = await GET("/api/treatment-plans");
        render();
    } catch (e) {
        showToast("Erro ao carregar planos: " + e.message, "error");
    }
}

// ── DnD ───────────────────────────────────────────────────────────────────────
function wireDnD() {
    document.querySelectorAll(".kanban-col").forEach(col => {
        const status = col.dataset.status;
        col.addEventListener("dragover", e => {
            e.preventDefault(); e.dataTransfer.dropEffect = "move";
            col.querySelector(".kanban-drop")?.classList.add("drag-over");
        });
        col.addEventListener("dragleave", e => {
            if (!col.contains(e.relatedTarget))
                col.querySelector(".kanban-drop")?.classList.remove("drag-over");
        });
        col.addEventListener("drop", async e => {
            e.preventDefault();
            col.querySelector(".kanban-drop")?.classList.remove("drag-over");
            const id = Number(e.dataTransfer.getData("text/plain"));
            const plan = allPlans.find(p => p.id === id);
            if (!plan || plan.status === status) return;
            // Optimistic update
            plan.status = status;
            render();
            try {
                await PUT(`/api/treatment-plans/${id}`, { status });
            } catch (err) {
                showToast("Erro ao actualizar estado: " + err.message, "error");
                await loadPlans(); // revert
            }
        });
    });
}

// ── Modal de detalhe do plano ─────────────────────────────────────────────────
function renderAiSteps(aiActions) {
    const container = document.getElementById("td_ai_actions");
    if (!container) return;
    const steps = (aiActions || "").split("\n").map(s => s.trim()).filter(Boolean);
    container.innerHTML = steps.length
        ? steps.map((s, i) => `<div class="treat-ai-step"><div class="treat-ai-step-num">${i + 1}</div><div>${s}</div></div>`).join("")
        : `<span class="muted" style="font-size:12px">Sem sugestões registadas.</span>`;
}
function renderDeadlineBox(due, status) {
    const box = document.getElementById("td_deadline_box");
    const msg = document.getElementById("td_deadline_msg");
    if (!box || !msg) return;
    if (status === "Concluído" || !due) { box.style.display = "none"; return; }
    const days = daysUntil(due);
    box.style.display = "flex";
    box.className = "treat-deadline-box";
    if (days < 0) { box.classList.add("overdue"); msg.textContent = `Prazo ultrapassado há ${Math.abs(days)} dia(s)`; }
    else if (days === 0) { box.classList.add("soon"); msg.textContent = "Prazo termina hoje"; }
    else if (days <= 5) { box.classList.add("soon"); msg.textContent = `Prazo em ${days} dia(s)`; }
    else { box.classList.add("ok"); msg.textContent = `Prazo: ${due} (${days} dias)`; }
}
function switchTab(tab) {
    document.querySelectorAll(".treat-tab-btn").forEach(b => b.classList.toggle("active", b.dataset.tab === tab));
    document.querySelectorAll(".treat-tab-panel").forEach(p => p.classList.toggle("is-hidden", p.id !== `tabPanel_${tab}`));
}

function openDetail(id) {
    const p = allPlans.find(x => x.id === id);
    if (!p) return;
    currentId = p.id;

    document.getElementById("td_title").textContent = `Plano TP-${p.id}`;
    const badge = document.getElementById("td_status_badge");
    if (badge) { badge.textContent = p.status; badge.className = `treat-status-badge ${statusBadgeClass(p.status)}`; }

    const setText = (elId, v) => { const el = document.getElementById(elId); if (el) el.textContent = v || "—"; };
    setText("td_asset_disp", p.asset_name);
    setText("td_risk_disp", p.risk_title);
    setText("td_source_disp", p.risk_origin || "—");
    setText("td_created_disp", p.created_at ? new Date(p.created_at).toLocaleDateString("pt-PT") : "—");

    renderAiSteps(""); // campo ai_actions não existe na BD ainda

    const setVal = (elId, v) => { const el = document.getElementById(elId); if (el) el.value = v || ""; };
    setVal("td_desc", p.description);
    setVal("td_evidence", "");
    setVal("td_owner", p.owner_id);
    setVal("td_due", p.due_date);
    setVal("td_status", p.status);
    setVal("td_strategy", p.strategy);

    renderDeadlineBox(p.due_date, p.status);

    document.getElementById("td_due")?.addEventListener("input", () =>
        renderDeadlineBox(document.getElementById("td_due").value, document.getElementById("td_status").value));
    document.getElementById("td_status")?.addEventListener("change", () =>
        renderDeadlineBox(document.getElementById("td_due").value, document.getElementById("td_status").value));

    renderTasksTab(p.id);
    switchTab("details");
    // ── INÍCIO DO BLOQUEIO DE PERMISSÕES DO PLANO ──
    const canEditPlan = hasPerm('treatment.manage');

    //Bloquear inputs, selects e textareas do plano
    const planInputs = document.querySelectorAll('#treatDetailModal input, #treatDetailModal select, #treatDetailModal textarea');
    planInputs.forEach(input => {
        // Exceção: não bloquear a barra de pesquisa de tarefas, se houver
        if (input.id === 'treatTaskSearch') return;

        input.disabled = !canEditPlan;
        input.style.opacity = canEditPlan ? '1' : '0.7';
        input.style.cursor = canEditPlan ? '' : 'not-allowed';
    });

    //Mostrar/Esconder botão Guardar
    const tdSave = document.getElementById("td_save");
    if (tdSave) tdSave.style.display = canEditPlan ? 'inline-flex' : 'none';

    //Mostrar/Esconder botão de Apagar (se existir)
    const tdDelete = document.getElementById("td_delete");
    if (tdDelete) tdDelete.style.display = (canEditPlan && hasPerm('treatment.edit')) ? 'inline-flex' : 'none';
    // ── FIM DO BLOQUEIO ──
    openModal("treatDetailModal");
}

async function saveDetail() {
    if (!currentId) return;
    try {
        const updated = await PUT(`/api/treatment-plans/${currentId}`, {
            owner: document.getElementById("td_owner")?.value || null,
            due: document.getElementById("td_due")?.value || null,
            status: document.getElementById("td_status")?.value,
            strategy: document.getElementById("td_strategy")?.value,
            description: document.getElementById("td_desc")?.value || null,
        });
        // Actualizar cache local
        const idx = allPlans.findIndex(p => p.id === currentId);
        if (idx !== -1) allPlans[idx] = { ...allPlans[idx], ...updated.plan };
        closeModal("treatDetailModal");
        render();
        showToast("Plano guardado com sucesso.");
    } catch (e) {
        showToast("Erro ao guardar: " + e.message, "error");
    }
}

// ── Tasks tab ─────────────────────────────────────────────────────────────────
async function renderTasksTab(planId) {
    const btnAddTask = document.getElementById("td_addTask");
    if (btnAddTask) {
        btnAddTask.style.display = hasPerm("treatment.taskEdit") ? "inline-flex" : "none";
    }
    const list = document.getElementById("td_tasks_list");
    const countEl = document.getElementById("td_task_count");
    if (!list) return;

    list.innerHTML = `<div class="tasks-empty muted" style="font-size:12px;padding:16px">A carregar...</div>`;

    let tasks = [];
    try {
        tasks = await GET(`/api/treatment-plans/${planId}/tasks`);
    } catch (e) {
        list.innerHTML = `<div class="tasks-empty muted">Erro ao carregar tarefas.</div>`;
        return;
    }

    const done = tasks.filter(t => t.status === "Concluído").length;
    const total = tasks.length;
    const pct = total > 0 ? Math.round(done / total * 100) : 0;

    if (countEl) countEl.textContent = total;
    const fill = document.getElementById("td_tasks_progress_fill");
    const pctEl = document.getElementById("td_tasks_pct");
    if (fill) fill.style.width = `${pct}%`;
    if (pctEl) pctEl.textContent = `${pct}%`;

    if (!tasks.length) {
        list.innerHTML = `<div class="tasks-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:.3;margin-bottom:8px"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            <div>Sem tarefas criadas</div>
            <div class="muted" style="font-size:11px;margin-top:2px">Clica em "Nova tarefa" para começar</div>
        </div>`;
        return;
    }

    list.innerHTML = tasks.map(t => {
        const statusCls = taskStatusClass(t.status);
        return `
        <div class="task-row" data-task-id="${t.id}">
            <div class="task-row-status"><span class="task-status-dot ${statusCls}"></span></div>
            <div class="task-row-body">
                <div class="task-row-title">${t.title}</div>
                <div class="task-row-meta">
                    ${t.assignedTo ? `<span class="task-meta-chip"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>${t.assignedTo}</span>` : ""}
                    ${t.due ? `<span class="task-meta-chip ${isOverdue(t.due) && t.status !== "Concluído" ? "task-meta-chip-overdue" : ""}"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>${t.due}</span>` : ""}
                    ${t.comment_count > 0 ? `<span class="task-meta-chip"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>${t.comment_count}</span>` : ""}
                    ${t.attach_count > 0 ? `<span class="task-meta-chip"><svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>${t.attach_count}</span>` : ""}
                </div>
            </div>
            <div class="task-row-right">
                <span class="task-status-badge ${statusCls}">${t.status}</span>
                <button class="btn small" type="button" data-open-task="${t.id}" data-plan-id="${planId}">Abrir</button>
            </div>
        </div>`;
    }).join("");

    list.querySelectorAll("[data-open-task]").forEach(btn => {
        btn.addEventListener("click", e => {
            e.stopPropagation();
            openTaskModal(Number(btn.getAttribute("data-open-task")), Number(btn.getAttribute("data-plan-id")));
        });
    });
}

// ── New Task Modal ────────────────────────────────────────────────────────────
function openNewTaskModal() {
    const label = document.getElementById("ntm_plan_label");
    if (label) label.textContent = `TP-${currentId}`;
    clearTaskForm();
    openModal("newTaskModal");
    setTimeout(() => document.getElementById("tf_title")?.focus(), 80);
}
function closeNewTaskModal() { closeModal("newTaskModal"); clearTaskForm(); }
function clearTaskForm() {
    ["tf_title", "tf_desc", "tf_assigned", "tf_due"].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = "";
    });
    const sel = document.getElementById("tf_status");
    if (sel) sel.value = "To do";
}

function wireNewTaskForm() {
    document.getElementById("td_btn_new_task")?.addEventListener("click", openNewTaskModal);
    document.getElementById("ntm_close")?.addEventListener("click", closeNewTaskModal);
    document.getElementById("tf_cancel")?.addEventListener("click", closeNewTaskModal);
    document.getElementById("newTaskModal")?.addEventListener("click", e => {
        if (e.target.id === "newTaskModal") closeNewTaskModal();
    });

    document.getElementById("tf_save")?.addEventListener("click", async () => {
        const title = document.getElementById("tf_title")?.value.trim();
        if (!title) {
            document.getElementById("tf_title")?.focus();
            document.getElementById("tf_title")?.classList.add("input-error");
            setTimeout(() => document.getElementById("tf_title")?.classList.remove("input-error"), 1200);
            return;
        }
        try {
            await POST(`/api/treatment-plans/${currentId}/tasks`, {
                title,
                description: document.getElementById("tf_desc")?.value.trim() || null,
                status: document.getElementById("tf_status")?.value || "To do",
                assigned_to: document.getElementById("tf_assigned")?.value || null,
                due_date: document.getElementById("tf_due")?.value || null,
            });
            closeNewTaskModal();
            renderTasksTab(currentId);
            // Recarregar planos para actualizar o task_summary no kanban
            await loadPlans();
            showToast("Tarefa criada.");
        } catch (e) {
            showToast("Erro ao criar tarefa: " + e.message, "error");
        }
    });

    document.addEventListener("keydown", e => {
        if (e.key === "Escape" && !document.getElementById("newTaskModal")?.classList.contains("is-hidden"))
            closeNewTaskModal();
    });
}

// ── Task Detail Modal ─────────────────────────────────────────────────────────
async function openTaskModal(taskId, planId) {
    // Buscar tarefa directamente da lista já carregada se possível,
    // mas os dados completos vêm sempre frescos dos comentários
    currentTaskId = taskId;
    currentTaskPlanId = planId;
    pendingFiles = [];

    // Encontrar a tarefa no estado local (já carregada na tab)
    // Para preencher o modal imediatamente enquanto os comentários carregam
    const taskRow = document.querySelector(`[data-task-id="${taskId}"]`);
    const title = taskRow?.querySelector(".task-row-title")?.textContent ?? "Tarefa";

    document.getElementById("tkm_plan_ref").textContent = `TP-${planId}`;
    document.getElementById("tkm_title").textContent = title;
    const composerAvatar = document.getElementById("tkm_composer_avatar");
    if (composerAvatar) {
        composerAvatar.textContent = getInitials(window.TB_USER?.name || "Utilizador");
    }
    // Buscar detalhes completos da tarefa via API
    try {
        const tasks = await GET(`/api/treatment-plans/${planId}/tasks`);
        const task = tasks.find(t => t.id === taskId);
        if (task) {
            document.getElementById("tkm_title").textContent = task.title;
            document.getElementById("tkm_status").value = task.status;
            document.getElementById("tkm_assigned").textContent = task.assignedTo || "—";
            document.getElementById("tkm_due").textContent = task.due || "—";
            document.getElementById("tkm_created").textContent = task.createdAt
                ? new Date(task.createdAt).toLocaleDateString("pt-PT") : "—";
            document.getElementById("tkm_desc").textContent = task.description || "Sem descrição.";
            document.getElementById("tkm_edit_title").value = task.title;
            document.getElementById("tkm_edit_desc").value = task.description || "";
            document.getElementById("tkm_edit_assigned").value = task.assigned_to_id || "";
            document.getElementById("tkm_edit_due").value = task.due || "";
        }
    } catch (e) {
        showToast("Aviso: não foi possível carregar detalhes da tarefa.", "warn");
    }

    closeModal("treatDetailModal");
    // ── INÍCIO DO BLOQUEIO DE PERMISSÕES DA TAREFA ──
    const canEditTask = hasPerm('treatment.taskEdit');

    // 1. Bloquear campos
    const taskInputs = document.querySelectorAll('#taskDetailModal input, #taskDetailModal select, #taskDetailModal textarea');
    taskInputs.forEach(input => {
        input.disabled = !canEditTask;
        input.style.opacity = canEditTask ? '1' : '0.7';
        input.style.cursor = canEditTask ? '' : 'not-allowed';
    });

    // 2. Mostrar/Esconder Guardar e Apagar Tarefa
    const tkSave = document.getElementById("tk_save");
    if (tkSave) tkSave.style.display = canEditTask ? 'inline-flex' : 'none';

    const tkDelete = document.getElementById("tk_delete"); // Se tiveres botão de apagar tarefa
    if (tkDelete) tkDelete.style.display = canEditTask ? 'inline-flex' : 'none';
    // ── FIM DO BLOQUEIO ──

    const m = document.getElementById("taskDetailModal");
    m.classList.remove("is-hidden");
    openModal("taskDetailModal");
    await renderComments(taskId);
    clearAttachPreview();
    const inp = document.getElementById("tkm_comment_input");
    if (inp) inp.value = "";
}

// ── Comentários ───────────────────────────────────────────────────────────────
async function renderComments(taskId) {
    const list = document.getElementById("tkm_comments_list");
    const countEl = document.getElementById("tkm_comment_count");
    if (!list) return;

    list.innerHTML = `<div class="comments-empty muted" style="font-size:12px;padding:16px">A carregar comentários...</div>`;

    let comments = [];
    try {
        comments = await GET(`/api/tasks/${taskId}/comments`);
    } catch (e) {
        list.innerHTML = `<div class="comments-empty muted">Erro ao carregar comentários.</div>`;
        return;
    }

    if (countEl) countEl.textContent = comments.length;

    if (!comments.length) {
        list.innerHTML = `<div class="comments-empty">Sem comentários ainda. Sê o primeiro a comentar.</div>`;
        return;
    }

    list.innerHTML = comments.map((c, ci) => `
        <div class="comment-item" data-comment-id="${c.id}" data-comment-idx="${ci}">
            <div class="comment-avatar">${getInitials(c.author || "Utilizador")}</div>
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
            <div class="attach-chip" data-comment-idx="${commentIdx}" data-attach-idx="${ai}" data-attach-id="${a.id}">
                <span class="attach-icon">${fileIcon(a.mime)}</span>
                <span class="attach-name">${a.name}</span>
                ${a.size ? `<span class="attach-size">${formatFileSize(a.size)}</span>` : ""}
                <div class="attach-actions">
                    <button type="button" class="attach-action-btn" data-action="download"
                        data-attach-id="${a.id}" title="Descarregar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download
                    </button>
                    <button type="button" class="attach-action-btn attach-action-docs" data-action="send-docs"
                        data-attach-id="${a.id}" ${a.sentToDocs ? 'disabled title="Já enviado"' : 'title="Enviar para Documentos"'}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="12" y2="12"/><line x1="15" y1="15" x2="12" y2="12"/></svg>
                        ${a.sentToDocs ? "Em docs" : "Enviar para Docs"}
                    </button>
                    <button type="button" class="attach-action-btn attach-action-delete" data-action="delete"
                        data-attach-id="${a.id}" title="Eliminar anexo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                        Eliminar
                    </button>
                </div>
            </div>
        `).join("")}
    </div>`;
}

// ── Composer ──────────────────────────────────────────────────────────────────
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
        </div>`).join("");
    preview.querySelectorAll(".attach-preview-remove").forEach(btn => {
        btn.addEventListener("click", () => {
            pendingFiles.splice(Number(btn.dataset.idx), 1);
            renderAttachPreview();
        });
    });
}

async function sendComment() {
    const inp = document.getElementById("tkm_comment_input");
    const content = inp?.value.trim();
    if (!content && !pendingFiles.length) return;
    if (!currentTaskId) return;

    const btn = document.getElementById("tkm_send_comment");
    if (btn) btn.disabled = true;

    try {
        const fd = new FormData();
        if (content) fd.append("content", content);
        pendingFiles.forEach(f => fd.append("files[]", f));

        await POST(`/api/tasks/${currentTaskId}/comments`, fd);

        if (inp) inp.value = "";
        clearAttachPreview();
        await renderComments(currentTaskId);
        // Actualizar contadores na task row
        renderTasksTab(currentTaskPlanId);
        showToast("Comentário enviado.");
    } catch (e) {
        showToast("Erro ao enviar comentário: " + e.message, "error");
    } finally {
        if (btn) btn.disabled = false;
    }
}

async function handleAttachAction(action, attachId, attachName) {
    if (action === "download") {
        window.open(`/api/attachments/${attachId}/download`, "_blank");
    }

    if (action === "send-docs") {
        try {
            await POST(`/api/attachments/${attachId}/promote`, { title: attachName, type: "evidence" });
            await renderComments(currentTaskId);
            showToast(`"${attachName}" enviado para Documentos & Evidências para aprovação.`);
        } catch (e) {
            showToast("Erro: " + e.message, "error");
        }
    }

    if (action === "delete") {
        if (!confirm(`Eliminar o anexo "${attachName}"?`)) return;
        try {
            await DELETE(`/api/attachments/${attachId}`);
            await renderComments(currentTaskId);
            renderTasksTab(currentTaskPlanId);
            showToast("Anexo eliminado.", "warn");
        } catch (e) {
            showToast("Erro ao eliminar: " + e.message, "error");
        }
    }
}

// ── Wire Task Modal ───────────────────────────────────────────────────────────
function wireTaskModal() {
    const goBackToPlan = () => {
        closeModal("taskDetailModal");
        if (currentTaskPlanId) {
            openDetail(currentTaskPlanId);
            setTimeout(() => switchTab("tasks"), 0);
        }
    };
    document.getElementById("tkm_back")?.addEventListener("click", goBackToPlan);
    document.getElementById("tkm_close")?.addEventListener("click", goBackToPlan);
    document.getElementById("taskDetailModal")?.addEventListener("click", e => {
        if (e.target?.id === "taskDetailModal") goBackToPlan();
    });

    // Status change
    document.getElementById("tkm_status")?.addEventListener("change", async e => {
        if (!currentTaskId || !currentTaskPlanId) return;
        try {
            await PUT(`/api/treatment-plans/${currentTaskPlanId}/tasks/${currentTaskId}`, { status: e.target.value });
            renderTasksTab(currentTaskPlanId);
            await loadPlans(); // actualiza task_summary no kanban
        } catch (err) {
            showToast("Erro ao actualizar estado: " + err.message, "error");
        }
    });

    // Save meta
    document.getElementById("tkm_save_meta")?.addEventListener("click", async () => {
        if (!currentTaskId || !currentTaskPlanId) return;
        const title = document.getElementById("tkm_edit_title").value.trim();
        if (!title) return;
        try {
            const assignedId = document.getElementById("tkm_edit_assigned").value || null;
            const res = await PUT(`/api/treatment-plans/${currentTaskPlanId}/tasks/${currentTaskId}`, {
                title,
                description: document.getElementById("tkm_edit_desc").value.trim() || null,
                assigned_to: assignedId,
                due_date: document.getElementById("tkm_edit_due").value || null,
            });
            // Actualizar header do modal
            document.getElementById("tkm_title").textContent = res.task.title;
            document.getElementById("tkm_desc").textContent = res.task.description || "Sem descrição.";
            document.getElementById("tkm_due").textContent = res.task.due || "—";
            document.getElementById("tkm_assigned").textContent = res.task.assignedTo || "—";
            renderTasksTab(currentTaskPlanId);
            showToast("Tarefa guardada.");
        } catch (e) {
            showToast("Erro ao guardar: " + e.message, "error");
        }
    });

    // Delete task
    document.getElementById("tkm_delete_task")?.addEventListener("click", async () => {
        if (!currentTaskId || !currentTaskPlanId) return;
        if (!confirm("Eliminar esta tarefa e todos os seus comentários?")) return;
        try {
            await DELETE(`/api/treatment-plans/${currentTaskPlanId}/tasks/${currentTaskId}`);
            const planId = currentTaskPlanId;
            closeModal("taskDetailModal");
            await loadPlans();
            openDetail(planId);
            setTimeout(() => switchTab("tasks"), 0);
            showToast("Tarefa eliminada.", "warn");
        } catch (e) {
            showToast("Erro ao eliminar: " + e.message, "error");
        }
    });

    // File input
    document.getElementById("tkm_file_input")?.addEventListener("change", e => {
        pendingFiles = [...pendingFiles, ...Array.from(e.target.files || [])];
        renderAttachPreview();
        e.target.value = "";
    });

    // Send comment
    document.getElementById("tkm_send_comment")?.addEventListener("click", sendComment);
    document.getElementById("tkm_comment_input")?.addEventListener("keydown", e => {
        if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) sendComment();
    });

    // Attachment actions — delegated, agora usa data-attach-id em vez de índices
    document.getElementById("tkm_comments_list")?.addEventListener("click", e => {
        const btn = e.target.closest("[data-action]");
        if (!btn) return;
        const action = btn.dataset.action;
        const attachId = Number(btn.dataset.attachId);
        // Buscar o nome do anexo a partir do chip pai
        const chip = btn.closest(".attach-chip");
        const attachName = chip?.querySelector(".attach-name")?.textContent ?? "ficheiro";
        handleAttachAction(action, attachId, attachName);
    });
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", () => {
    loadUsers();
    loadPlans();
    wireDnD();
    wireNewTaskForm();
    wireTaskModal();

    // Esconder botão de Novo Plano se não tiver permissão
    const btnCreatePlan = document.getElementById("btnCreatePlan"); // Ajusta o ID se for diferente no teu Blade
    if (btnCreatePlan) {
        btnCreatePlan.style.display = hasPerm("treatment.create") ? "inline-flex" : "none";
    }

    // Esconder a Tab de Tarefas se não tiver permissão de sequer ver tarefas
    const tabTasksBtn = document.querySelector('.treat-tab-btn[data-tab="tasks"]');
    if (tabTasksBtn) {
        tabTasksBtn.style.display = hasPerm("treatment.task") ? "inline-flex" : "none";
    }
    // Filtros
    document.getElementById("treatSearch")?.addEventListener("input", e => {
        filterSearch = e.target.value; render();
    });
    document.getElementById("treatPriorityFilter")?.addEventListener("change", e => {
        filterPriority = e.target.value; render();
    });
    document.getElementById("treatOwnerFilter")?.addEventListener("change", e => {
        filterOwner = e.target.value; render();
    });

    // Detail modal
    document.getElementById("td_close")?.addEventListener("click", () => closeModal("treatDetailModal"));
    document.getElementById("td_close2")?.addEventListener("click", () => closeModal("treatDetailModal"));
    document.getElementById("td_save")?.addEventListener("click", saveDetail);
    document.getElementById("treatDetailModal")?.addEventListener("click", e => {
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
    document.addEventListener("keydown", e => {
        if (e.key !== "Escape") return;
        const taskModal = document.getElementById("taskDetailModal");
        const planModal = document.getElementById("treatDetailModal");
        if (taskModal && !taskModal.classList.contains("is-hidden")) {
            closeModal("taskDetailModal");
            if (currentTaskPlanId) { openDetail(currentTaskPlanId); setTimeout(() => switchTab("tasks"), 0); }
        } else if (planModal && !planModal.classList.contains("is-hidden")) {
            closeModal("treatDetailModal");
        }
    });
});