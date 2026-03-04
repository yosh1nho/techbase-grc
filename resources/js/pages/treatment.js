const LS_KEY = "tb_mock_treatments";

function loadPlans() {
    try { return JSON.parse(localStorage.getItem(LS_KEY) || "[]"); }
    catch { return []; }
}

// ✅ Seed: cria exemplos se estiver vazio
function seedIfEmpty() {
    const existing = loadPlans();
    if (existing.length > 0) return;

    const seed = [
        {
            id: "TP-1001",
            source: "wazuh",
            alertId: "WZ-1001",
            asset: "SRV-DB-01",
            risk: "Comprometimento do ativo / execução maliciosa",
            aiActions: [
                "Isolar o host e recolher indicadores (hash/processos).",
                "Executar varredura completa + confirmar integridade.",
                "Rever allowlist/EDR e bloquear execução não assinada.",
            ].join("\n"),
            planDescription: "Isolar SRV-DB-01, validar processos suspeitos e aplicar hardening (allowlist). Gerar evidência com relatório EDR + logs Wazuh.",
            strategy: "Mitigar",
            due: "2026-02-28",
            owner: "IT Ops",
            priority: "Alta",
            status: "Em curso",
            createdAt: "2026-02-18T10:15:00.000Z",
            evidence: ""
        },
        {
            id: "TP-1002",
            source: "wazuh",
            alertId: "WZ-1002",
            asset: "FW-EDGE-01",
            risk: "Acesso não autorizado por força bruta",
            aiActions: [
                "Bloquear IPs e ajustar rate-limit / fail2ban.",
                "Rever MFA e endurecer política de passwords.",
                "Auditar contas com tentativas e logs de autenticação.",
            ].join("\n"),
            planDescription: "Aplicar bloqueio por IP e rate-limit no perímetro, revisar MFA para acessos remotos e anexar evidência (prints/config + logs).",
            strategy: "Mitigar",
            due: "2026-02-20",
            owner: "Network",
            priority: "Alta",
            status: "To do",
            createdAt: "2026-02-18T09:50:00.000Z",
            evidence: ""
        },
        {
            id: "TP-1003",
            source: "assessment",
            alertId: "R-003",
            asset: "NAS-BKP-01",
            risk: "Falha de backup testado",
            aiActions: "Agendar testes de restore e definir evidências mínimas (relatório + logs).",
            planDescription: "Executar teste de restore semanal e documentar (relatório). Garantir que backups críticos têm teste aprovado.",
            strategy: "Mitigar",
            due: "2026-02-10",
            owner: "SOC",
            priority: "Média",
            status: "Em atraso",
            createdAt: "2026-02-01T12:00:00.000Z",
            evidence: ""
        },
        {
            id: "TP-1004",
            source: "wazuh",
            alertId: "WZ-1004",
            asset: "APP-GRC",
            risk: "Tentativas repetidas de autenticação",
            aiActions: "Rever logs de autenticação e aplicar políticas de bloqueio por tentativa.",
            planDescription: "Ativar bloqueio por tentativas, revisar IPs suspeitos e adicionar alerta de threshold. Evidência: logs + print configuração.",
            strategy: "Mitigar",
            due: "2026-02-18",
            owner: "AppSec",
            priority: "Baixa",
            status: "Concluído",
            createdAt: "2026-02-17T15:10:00.000Z",
            evidence: "Relatório interno + screenshot de configuração"
        }
    ];

    savePlans(seed);
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

function normalizePlan(p) {
    const status = p.status || "To do";
    return { ...p, status };
}

function chipForPriority(p) {
    if (p === "Alta") return "chip bad";
    if (p === "Média") return "chip warn";
    return "chip";
}

function render() {
    const plans = loadPlans().map(normalizePlan);
    // salva normalizado (pra persistir overdue)
    savePlans(plans);

    const cols = {
        "To do": document.getElementById("colTodo"),
        "Em curso": document.getElementById("colDoing"),
        "Concluído": document.getElementById("colDone"),
        "Em atraso": document.getElementById("colOverdue"),
    };

    Object.values(cols).forEach(c => c.innerHTML = "");

    plans.forEach(p => {
        const card = document.createElement("div");
        card.className = "kcard";
        card.draggable = true;
        card.dataset.id = p.id;

        card.innerHTML = `
      <div class="kcard-top">
        <div>
          <b>${p.alertId || "—"}</b>
          <div class="kmeta muted">${p.asset || "—"} • ${p.strategy || "—"}</div>
        </div>
        <span class="${chipForPriority(p.priority || "Média")}">${p.priority || "Média"}</span>
      </div>

      <div class="kdesc">${(p.planDescription || "").slice(0, 120) || "<span class='muted'>Sem descrição</span>"}</div>

      <div class="krow">
        <span class="chip">${p.status}</span>
        <span class="chip">Owner: ${p.owner || "—"}</span>
        <span class="chip">Prazo: ${p.due || "—"}</span>
        <button class="btn" type="button" data-detail="${p.id}">Detalhes</button>
      </div>
    `;

        card.addEventListener("dragstart", (e) => {
            e.dataTransfer.setData("text/plain", p.id);
            e.dataTransfer.effectAllowed = "move";
        });

        cols[p.status]?.appendChild(card);
    });

    // counts / kpis
    const by = (s) => plans.filter(p => p.status === s).length;
    document.getElementById("countTodo").textContent = by("To do");
    document.getElementById("countDoing").textContent = by("Em curso");
    document.getElementById("countDone").textContent = by("Concluído");
    document.getElementById("countOverdue").textContent = by("Em atraso");

    document.getElementById("kpiTotal").textContent = plans.length;
    document.getElementById("kpiTodo").textContent = by("To do");
    document.getElementById("kpiDoing").textContent = by("Em curso");
    document.getElementById("kpiDone").textContent = by("Concluído");
    document.getElementById("kpiOverdue").textContent = by("Em atraso");

    // detalhes
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

            const drop = col.querySelector(".kanban-drop");
            if (drop) drop.style.background = "rgba(255,255,255,.03)";
        });

        col.addEventListener("dragleave", (e) => {
            // só limpa quando realmente sai da coluna
            if (!col.contains(e.relatedTarget)) {
                const drop = col.querySelector(".kanban-drop");
                if (drop) drop.style.background = "";
            }
        });

        col.addEventListener("drop", (e) => {
            e.preventDefault();
            const drop = col.querySelector(".kanban-drop");
            if (drop) drop.style.background = "";

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

// modal simples
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

function openDetail(id) {
    const plans = loadPlans().map(normalizePlan);
    const p = plans.find(x => String(x.id) === String(id));
    if (!p) return;

    currentId = p.id;
    document.getElementById("td_title").textContent = `Plano ${p.id}`;
    document.getElementById("td_alert").value = `${p.alertId || "—"} (${p.source || "—"})`;
    document.getElementById("td_asset").value = p.asset || "—";
    document.getElementById("td_owner").value = p.owner || "";
    document.getElementById("td_due").value = p.due || "";
    document.getElementById("td_priority").value = p.priority || "Média";
    document.getElementById("td_status").value = p.status || "To do";
    document.getElementById("td_desc").value = p.planDescription || "";
    document.getElementById("td_evidence").value = (p.evidence || "");

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
    plans[idx].planDescription = document.getElementById("td_desc").value.trim();
    plans[idx].evidence = document.getElementById("td_evidence").value.trim();

    savePlans(plans);
    closeModal("treatDetailModal");
    render();
}

document.addEventListener("DOMContentLoaded", () => {
    seedIfEmpty();
    render();
    wireDnD();

    document.getElementById("td_close")?.addEventListener("click", () => closeModal("treatDetailModal"));
    document.getElementById("td_save")?.addEventListener("click", saveDetail);

    document.getElementById("treatDetailModal")?.addEventListener("click", (e) => {
        if (e.target?.id === "treatDetailModal") closeModal("treatDetailModal");
    });
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeModal("treatDetailModal");
    });
});
