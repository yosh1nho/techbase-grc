// resources/js/pages/risks.js

const MOCK_ALERTS = (JSON.parse(localStorage.getItem("tb_alerts") || "[]"))
  .map(a => ({
    id: a.id,
    date: a.ts,
    severity: a.sev,
    asset: a.asset,
    category: a.cat,
    summary: a.msg
  }));

const MOCK_ASSETS = {
  "SRV-DB-01": { criticality: "Crítico", owner: "IT Ops", notes: "Servidor de base de dados (PII)" },
  "FW-EDGE-01": { criticality: "Alto", owner: "Network", notes: "Firewall perímetro" }
};

// ── Helpers ──────────────────────────────────────────────
function esc(s) {
  return String(s || "")
    .replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;").replaceAll("'", "&#039;");
}
function openModal(id) { const el = document.getElementById(id); el?.classList.remove("is-hidden"); document.body.style.overflow = "hidden"; }
function closeModal(id) { const el = document.getElementById(id); el?.classList.add("is-hidden"); document.body.style.overflow = ""; }
function setText(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
function setVal(id, v) { const el = document.getElementById(id); if (el) el.value = v || ""; }


function setSelectVal(id, v) {
  const el = document.getElementById(id);
  if (el) el.value = String(v ?? "");
}

function setSelectByText(id, text) {
  const el = document.getElementById(id);
  if (!el || !text) return;
  const val = String(text);
  // 1. match exacto por value
  const byValue = [...el.options].find(o => o.value === val);
  if (byValue) { el.value = byValue.value; return; }
  // 2. match por texto visível
  const byText = [...el.options].find(o => o.text === val);
  if (byText) { el.value = byText.value; return; }
  // 3. match parcial — "Luisa - IT" contém "luisa", email contém "luisa"
  const lower = val.toLowerCase();
  const byPartial = [...el.options].find(o =>
    o.text.toLowerCase().includes(lower) || lower.includes(o.text.toLowerCase().split("@")[0])
  );
  if (byPartial) { el.value = byPartial.value; return; }
}
// ── AI Recommendation ─────────────────────────────────────
function mockAIRecommendation(alert) {
  const asset = MOCK_ASSETS[alert.asset] || { criticality: "—", owner: "—", notes: "" };
  let risk = "Risco operacional", actions = [], confidence = 0.72;

  const cat = (alert.category || "").toLowerCase();
  if (cat.includes("malware") || cat.includes("ransomware")) {
    risk = "Comprometimento do ativo / execução maliciosa";
    actions = [
      "Isolar o host e recolher indicadores (hash/processos).",
      "Executar varredura completa + confirmar integridade.",
      "Rever controlos de execução (allowlist) e EDR.",
      "Validar backups e preparar restauração se necessário."
    ];
    confidence = 0.89;
  } else if (cat.includes("brute") || cat.includes("login")) {
    risk = "Acesso não autorizado por força bruta";
    actions = [
      "Bloquear IPs e ajustar rate-limit / fail2ban.",
      "Rever MFA e endurecer política de passwords.",
      "Auditar contas com tentativas e logs de autenticação."
    ];
    confidence = 0.77;
  } else if (cat.includes("backup") || cat.includes("quota")) {
    risk = "Falha de continuidade / perda de dados";
    actions = [
      "Verificar estado do job de backup e alertas.",
      "Rever espaço disponível e política de retenção.",
      "Testar restore e documentar resultado."
    ];
    confidence = 0.81;
  } else if (cat.includes("offline") || cat.includes("agent")) {
    risk = "Perda de visibilidade / ativo sem monitorização";
    actions = [
      "Verificar conectividade do agente no ativo.",
      "Reiniciar serviço de monitorização se aplicável.",
      "Escalar se o ativo for crítico e inacessível."
    ];
    confidence = 0.74;
  }

  const riskClass = alert.severity === "critical" ? "Alto" : (alert.severity === "high" ? "Médio" : "Baixo");
  return { risk, actions, actionsText: actions.join("\n"), confidence, riskClass, asset };
}

// ── Severity styles ───────────────────────────────────────
const SEV_STYLES = {
  critical: { iconBg: "rgba(248,113,113,.12)", dot: "#f87171", label: "Crítico", barColor: "#f87171" },
  high: { iconBg: "rgba(251,146,60,.1)", dot: "#fb923c", label: "Alto", barColor: "#fb923c" },
  medium: { iconBg: "rgba(251,191,36,.1)", dot: "#fbbf24", label: "Médio", barColor: "#fbbf24" },
  low: { iconBg: "rgba(52,211,153,.08)", dot: "#34d399", label: "Baixo", barColor: "#34d399" },
};
const ALERT_ICONS = {
  BackupFailed: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><line x1="2" y1="2" x2="22" y2="22"/></svg>',
  BackupSuccessful: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
  UpdateApplied: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg>',
  QuotaWarning: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
  AgentOffline: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.56 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>',
  MalwareDetected: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
  RansomwareBehavior: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
  UserLoggedIn: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
  default: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
};

// ── State ─────────────────────────────────────────────────
let selectedAlert = null;
let selectedAI = null;
let selectedRiskIds = new Set();
let riskSearch = { q: "", level: "all", status: "all" };

// ── Data ──────────────────────────────────────────────────
async function loadRisks() {

  const res = await fetch("/api/risks");
  const data = await res.json();

  return data.map(r => ({
    id: r.id_risk,
    asset: r.hostname,
    description: r.description,

    threat: r.threat,
    vulnerability: r.vulnerability,
    riskOwner: r.risk_owner,
    riskOwnerId: r.risk_owner_id,
    actions: r.actions,
    due: r.due,

    status: r.status,
    prob: r.probability ?? 1,
    impact: r.impact ?? 1,
    score: r.score ?? 1,
    strategy: r.origin,
    createdAt: r.createdat
  }));
}
function loadTreatments() { try { return JSON.parse(localStorage.getItem("tb_mock_treatments") || "[]"); } catch { return []; } }

async function createRisk(data) {

  window.location.href = `${ROUTES.treatment || '/tratamento'}?from=alert&alert_id=${alert.id}`;

}

function saveTreatment(plan) {
  const arr = loadTreatments();
  arr.unshift(plan);
  localStorage.setItem("tb_mock_treatments", JSON.stringify(arr));
}
async function deleteRisk(id) {
  await fetch(`/api/risks/${id}`, {
    method: "DELETE",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
    }
  });
}
async function saveRisk(data) {

  // UPDATE
  if (data.id && !String(data.id).startsWith("RK-")) {

    const res = await fetch(`/api/risks/${data.id}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(data)
    });

    return await res.json();
  }

  // CREATE
  return await createRisk(data);
}


async function clearRisks() {
  const risks = await loadRisks();
  for (const r of risks) {
    await deleteRisk(r.id);
  }
}



// ── Risk scoring ──────────────────────────────────────────
function riskLevelFromScore(score) {
  if (score >= 17) return { label: "Muito Alta", color: "#f87171", cls: "rk-score-critical", chipBg: "rgba(248,113,113,.12)", chipColor: "#f87171" };
  if (score >= 10) return { label: "Alta", color: "#fb923c", cls: "rk-score-high", chipBg: "rgba(251,146,60,.1)", chipColor: "#fb923c" };
  if (score >= 5) return { label: "Média", color: "#fbbf24", cls: "rk-score-med", chipBg: "rgba(251,191,36,.1)", chipColor: "#fbbf24" };
  return { label: "Baixa", color: "#34d399", cls: "rk-score-low", chipBg: "rgba(52,211,153,.08)", chipColor: "#34d399" };
}

// ── KPIs ──────────────────────────────────────────────────
async function renderKpis() {
  const risks = await loadRisks();
  setText("rkKpiTotal", risks.length);
  setText("rkKpiCritical", risks.filter(r => riskLevelFromScore(r.score).label === "Muito Alta").length);
  setText("rkKpiHigh", risks.filter(r => riskLevelFromScore(r.score).label === "Alta").length);
  setText("rkKpiMed", risks.filter(r => riskLevelFromScore(r.score).label === "Média").length);
  setText("rkKpiLow", risks.filter(r => riskLevelFromScore(r.score).label === "Baixa").length);
  setText("rkKpiOpen", risks.filter(r => r.status === "Aberto").length);
  setText("rkKpiTreat", risks.filter(r => r.status === "Em tratamento").length);
}

// ── Render Alerts as cards ────────────────────────────────
async function renderAlerts() {
  const container = document.getElementById("alertCardsList");
  if (!container) return;

  if (!MOCK_ALERTS.length) {
    container.innerHTML = `<div class="muted" style="font-size:13px;padding:20px 0;text-align:center">
      Nenhum alerta disponível. Sincroniza os dados Acronis no Dashboard.
    </div>`;
    return;
  }

  container.innerHTML = "";
  MOCK_ALERTS.forEach(a => {
    const s = SEV_STYLES[a.severity] || SEV_STYLES.low;
    const icon = ALERT_ICONS[a.category] || ALERT_ICONS.default;
    const card = document.createElement("div");
    card.className = "rk-alert-card";
    card.dataset.alertId = a.id;

    card.innerHTML = `
      <div class="rk-alert-card-bar" style="background:${s.barColor}"></div>
      <div class="rk-alert-icon" style="background:${s.iconBg};color:${s.dot}">${icon}</div>
      <div class="rk-alert-body">
        <div class="rk-alert-top">
          <span class="rk-alert-sev" style="background:${s.iconBg};color:${s.dot}">${s.label}</span>
          <span class="rk-alert-asset">${a.asset}</span>
          <span class="rk-alert-ts">${a.date}</span>
        </div>
        <div class="rk-alert-msg">${a.summary}</div>
      </div>
    `;

    card.addEventListener("click", () => selectAlert(a.id));
    container.appendChild(card);
  });
}

function selectAlert(alertId, openPlan = false) {
  selectedAlert = MOCK_ALERTS.find(x => x.id === alertId);
  if (!selectedAlert) return;
  selectedAI = mockAIRecommendation(selectedAlert);

  // highlight selected card
  document.querySelectorAll(".rk-alert-card").forEach(c => c.classList.remove("selected"));
  document.querySelector(`.rk-alert-card[data-alert-id="${alertId}"]`)?.classList.add("selected");

  // enable action buttons
  ["btnCreateRiskFromAlert", "btnCNCS24h"].forEach(id => {
    const btn = document.getElementById(id);
    if (btn) btn.disabled = false;
  });

  // populate context panel
  const empty = document.getElementById("alertContextEmpty");
  const content = document.getElementById("alertContextContent");
  if (empty) empty.style.display = "none";
  if (content) content.style.display = "block";

  const s = SEV_STYLES[selectedAlert.severity] || SEV_STYLES.low;
  setText("ctxTitle", selectedAlert.summary);
  const badge = document.getElementById("ctxSevBadge");
  if (badge) {
    badge.textContent = s.label;
    badge.style.cssText = `background:${s.iconBg};color:${s.dot};`;
  }
  setText("ctxAsset", selectedAlert.asset);
  setText("ctxCriticality", selectedAI.asset.criticality);
  setText("ctxOwner", selectedAI.asset.owner);
  setText("ctxConfidence", `${Math.round(selectedAI.confidence * 100)}%`);
  setText("ctxRisk", selectedAI.risk);

  // AI steps in context panel
  const stepsEl = document.getElementById("ctxActions");
  if (stepsEl) {
    stepsEl.innerHTML = selectedAI.actions.map((s, i) => `
      <div class="treat-ai-step">
        <div class="treat-ai-step-num">${i + 1}</div>
        <div>${s}</div>
      </div>
    `).join("");
  }

  if (openPlan) openTreatmentModalPrefilled();
}

// ── Render Risks as cards ─────────────────────────────────
function filterRisks(risks) {
  const q = riskSearch.q.toLowerCase();
  return risks.filter(r => {
    const matchQ = !q ||
      (r.id || "").toLowerCase().includes(q) ||
      (r.asset || "").toLowerCase().includes(q) ||
      (r.description || "").toLowerCase().includes(q);
    const lvl = riskLevelFromScore(r.score).label;
    const matchL = riskSearch.level === "all" || lvl === riskSearch.level;
    const matchS = riskSearch.status === "all" || r.status === riskSearch.status;
    return matchQ && matchL && matchS;
  });
}

async function renderRisks() {
  const container = document.getElementById("riskCardsContainer");
  if (!container) return;

  const allRisks = await loadRisks();
  const risks = filterRisks(allRisks);

  if (!risks.length) {
    container.innerHTML = `<div class="muted" style="font-size:13px;padding:24px 0;text-align:center">
      ${allRisks.length ? "Nenhum risco com os filtros aplicados." : "Sem riscos registados. Seleciona um alerta e clica em 'Registar risco'."}
    </div>`;
    return;
  }

  container.innerHTML = "";
  risks.forEach(r => {
    const lvl = riskLevelFromScore(r.score);
    const card = document.createElement("div");
    card.className = "rk-risk-card";

    card.innerHTML = `
      <div class="rk-risk-bar" style="background:${lvl.color}"></div>
      <div class="rk-risk-check">
        <input type="checkbox" data-risk-check="${r.id}" ${selectedRiskIds.has(r.id) ? "checked" : ""}
          style="width:15px;height:15px" />
      </div>
      <div class="rk-risk-id" style="padding-left:8px">${r.id}</div>
      <div class="rk-risk-main" style="padding-left:4px">
        <div class="rk-risk-desc">${r.description}</div>
        <div class="rk-risk-meta">${r.asset} · ${r.strategy} · ${r.sourceLabel || "—"}</div>
      </div>
      <div class="rk-risk-score-badge" style="color:${lvl.color}">${r.score}</div>
      <div class="rk-risk-level" style="background:${lvl.chipBg};color:${lvl.color}">${lvl.label}</div>
      <div class="rk-risk-status">${r.status}</div>
      <button class="btn small" type="button" data-view-risk="${r.id}" style="font-size:11px;flex-shrink:0">
        Editar
      </button>
      <button class="btn small primary" type="button" data-create-treatment="${r.id}" style="font-size:11px;flex-shrink:0">
        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Criar plano
      </button>
    `;

    card.querySelector("[data-risk-check]")?.addEventListener("change", (e) => {
      e.stopPropagation();
      const id = Number(e.target.getAttribute("data-risk-check"));
      e.target.checked ? selectedRiskIds.add(id) : selectedRiskIds.delete(id);
      document.getElementById("btnRemoveRisks").disabled = selectedRiskIds.size === 0;
    });

    card.querySelector("[data-view-risk]")?.addEventListener("click", async (e) => {
      e.stopPropagation();
      const rFull = (await loadRisks()).find(x => x.id === r.id);
      if (rFull) openRiskModalFromRisk(rFull);
    });

    card.querySelector("[data-create-treatment]")?.addEventListener("click", async (e) => {
      e.stopPropagation();
      const rFull = (await loadRisks()).find(x => x.id === r.id);
      if (rFull) openTreatmentModalFromRisk(rFull);
    });

    container.appendChild(card);
  });
}

// ── Matrix 5×5 ───────────────────────────────────────────

function matrixColor(score) {
  if (score >= 17) return "#f87171";   // vermelho
  if (score >= 10) return "#fb923c";   // laranja
  if (score >= 5) return "#fbbf24";   // amarelo
  return "#34d399";                   // verde
}


function renderMatrix(prob, impact) {
  const matrix = document.getElementById("rk_matrix");
  if (!matrix) return;
  matrix.innerHTML = "";

  // rows: prob 5→1 (top = highest), cols: impact 1→5
  for (let p = 5; p >= 1; p--) {
    for (let i = 1; i <= 5; i++) {
      const score = p * i;
      const color = matrixColor(score);
      const cell = document.createElement("div");
      cell.className = "rk-matrix-cell" + (p === prob && i === impact ? " active" : "");
      cell.style.background = color;
      cell.textContent = score;
      matrix.appendChild(cell);
    }
  }
}

function updateRiskScoreUI() {
  const prob = Number(document.getElementById("ra_prob")?.value || 1);
  const impact = Number(document.getElementById("ra_impact")?.value || 1);
  const score = prob * impact;
  const lvl = riskLevelFromScore(score);

  setText("ra_score", score);
  setText("ra_level", lvl.label);

  const box = document.getElementById("rk_score_box");
  if (box) box.className = `rk-score-box ${lvl.cls}`;

  const badge = document.getElementById("rk_score_badge");
  if (badge) {
    badge.textContent = `Score: ${score} — ${lvl.label}`;
    badge.style.cssText = `background:${lvl.chipBg};color:${lvl.color};`;
  }

  renderMatrix(prob, impact);
}

// ── Open Modals ───────────────────────────────────────────
function openTreatmentModalFromRisk(r) {
  const lvl = riskLevelFromScore(r.score);

  // título do modal
  const titleEl = document.getElementById("ta_modal_title");
  if (titleEl) titleEl.textContent = `Plano para: ${r.description || r.id}`;

  // context strip
  setText("ta_risk_id_disp", r.id);
  setText("ta_asset_disp", r.asset || "—");
  setText("ta_score_disp", `${r.score} — ${lvl.label}`);
  setText("ta_status_disp", r.status || "—");

  // painel esquerdo — contexto do risco
  setText("ta_risk_desc_disp", r.description || "—");
  setText("ta_threat_disp", r.threat || "—");
  setText("ta_vuln_disp", r.vulnerability || "—");
  setText("ta_actions_disp", r.actions || "—");

  // hidden fields
  setVal("ta_risk_id", r.id);
  setVal("ta_risk_desc", r.description);
  setVal("ta_asset", r.asset);
  setVal("ta_actions", r.actions);

  // footer ref
  setText("ta_ref", `Risco: ${r.id}`);

  // limpar campos editáveis
  setVal("ta_plan_desc", "");
  setVal("ta_due", r.due || "");
  setSelectVal("ta_owner", r.riskOwner);

  openModal("treatmentAlertModal");
}

function openRiskModalPrefilled() {
  if (!selectedAlert || !selectedAI) return;
  document.getElementById("ra_alertId").value = selectedAlert.id;

  const id = `RK-${Date.now()}`;
  setText("riskAlertTitle", `${selectedAlert.asset} · ${selectedAI.risk}`);

  // context strip
  setText("rk_id_disp", id);
  setText("rk_asset_disp", selectedAlert.asset);
  setText("rk_alert_disp", `${selectedAlert.id} (${selectedAlert.severity})`);
  setText("rk_created_disp", new Date().toLocaleDateString("pt-PT"));
  setText("rk_id_ref", `Novo risco · ${id}`);

  // hidden compat fields
  setVal("ra_id", id);
  setVal("ra_alert", `${selectedAlert.id} (${selectedAlert.severity})`);
  setVal("ra_asset", selectedAlert.asset);

  // form fields
  setVal("ra_desc", selectedAI.risk);
  setVal("ra_actions", selectedAI.actionsText);
  setVal("ra_threat", selectedAlert.category || "—");
  setVal("ra_vuln", "");
  setSelectVal("ra_owner", null);
  setVal("ra_due", "");

  document.getElementById("ra_c").checked = true;
  document.getElementById("ra_i").checked = true;
  document.getElementById("ra_a").checked = false;

  setVal("ra_prob", "3");
  setVal("ra_impact", selectedAlert.severity === "critical" ? "4" : "3");
  setVal("ra_status", "Aberto");

  // alert preview
  const prev = document.getElementById("ra_alertPreview");
  if (prev) prev.innerHTML = `
    <b>${selectedAlert.id}</b> — ${selectedAlert.summary}<br>
    <span class="muted">Ativo: ${selectedAlert.asset} · Categoria: ${selectedAlert.category} · Severidade: ${selectedAlert.severity}</span>
  `;

  updateRiskScoreUI();
  openModal("riskAlertModal");
}

function openRiskModalFromRisk(r) {
  setText("riskAlertTitle", `${r.asset} · ${r.description}`);
  setText("rk_id_disp", r.id);
  setText("rk_asset_disp", r.asset);
  setText("rk_alert_disp", "—");
  setText("rk_created_disp", r.createdAt ? new Date(r.createdAt).toLocaleDateString("pt-PT") : "—");
  setText("rk_id_ref", `Editando: ${r.id}`);

  setVal("ra_id", r.id);
  setVal("ra_asset", r.asset);
  setVal("ra_desc", r.description);

  setVal("ra_prob", r.prob || 1);
  setVal("ra_impact", r.impact || 1);

  setVal("ra_status", r.status || "Aberto");

  // limpar campos que não vêm do backend
  setSelectByText("ra_owner", r.riskOwner);
  setVal("ra_threat", r.threat || "");
  setVal("ra_vuln", r.vulnerability || "");
  setVal("ra_actions", r.actions || "");
  setVal("ra_due", r.due || "");

  document.getElementById("ra_c").checked = false;
  document.getElementById("ra_i").checked = false;
  document.getElementById("ra_a").checked = false;

  const prev = document.getElementById("ra_alertPreview");
  if (prev) prev.textContent = "—";
  updateRiskScoreUI();
  openModal("riskAlertModal");
}

// ── CNCS Modal ────────────────────────────────────────────
function inferIncidentNature(alert) {
  const cat = (alert.category || "").toLowerCase();
  if (cat.includes("malware") || cat.includes("ransomware")) return "Malware";
  if (cat.includes("brute") || cat.includes("login")) return "Acesso Não Autorizado";
  return "Outro";
}
function inferAffectedSystems(alert, assetNotes) {
  const out = [];
  if ((assetNotes || "").toLowerCase().includes("base de dados")) out.push("Bases de dados");
  if ((alert.category || "").toLowerCase().includes("brute")) out.push("Redes de comunicação");
  if (!out.length) out.push("Sistemas de informação essenciais");
  return out;
}
function inferImpactLevel(alert, assetCrit) {
  if (alert.severity === "critical") return "Muito Alto";
  if (alert.severity === "high") return "Alto";
  if (assetCrit && assetCrit.toLowerCase().includes("crít")) return "Alto";
  return "Médio";
}

function buildCNCS24hDraftHtml(alert, ai) {
  const org = { name: "Clínica Exemplo", type: "Importante (Anexo II)", sector: "Saúde", nif: "—" };
  const nature = inferIncidentNature(alert);
  const impact = inferImpactLevel(alert, ai.asset.criticality);
  const systems = inferAffectedSystems(alert, ai.asset.notes);
  const pii = (ai.asset.notes || "").toLowerCase().includes("pii") ? "Sim" : "Ainda desconhecido";
  const immediate = ai.actions.slice(0, 3);
  const ioc = [];
  const summary = `${alert.summary} (Ativo: ${alert.asset})`.slice(0, 500);

  const section = (num, title, body) => `
    <div style="border:1px solid var(--border);border-radius:12px;padding:14px 16px;margin-bottom:10px">
      <div style="font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);margin-bottom:8px">${num}) ${title}</div>
      ${body}
    </div>`;
  const row2 = (items) => `<div style="display:grid;grid-template-columns:repeat(${items.length},1fr);gap:8px;margin-top:8px">${items.map(([lbl, val]) => `<div><div style="font-size:10px;color:var(--muted);margin-bottom:2px">${lbl}</div><div style="font-weight:600;font-size:13px">${val}</div></div>`).join("")
    }</div>`;
  const field = (id, lbl, type = "input", placeholder = "", val = "") => `
    <div style="margin-top:8px">
      <div style="font-size:10px;color:var(--muted);margin-bottom:4px">${lbl}</div>
      ${type === "textarea"
      ? `<textarea id="${id}" rows="2" style="width:100%;resize:vertical;padding:7px 10px;border-radius:8px;border:1px solid var(--border);background:var(--input-bg);color:inherit;font-size:13px" placeholder="${placeholder}">${val}</textarea>`
      : `<input id="${id}" style="width:100%;padding:7px 10px;border-radius:8px;border:1px solid var(--border);background:var(--input-bg);color:inherit;font-size:13px" placeholder="${placeholder}" value="${val}" />`
    }
    </div>`;

  return `
    ${section("1", "Informações da Entidade",
    row2([["Entidade", org.name], ["Tipo", org.type], ["Setor", org.sector], ["NIF/NIPC", org.nif]]) +
    `<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">` +
    field("cncs_phone", "Telefone", "input", "ex.: +351 ...") +
    field("cncs_email", "Email", "input", "ex.: soc@empresa.pt") +
    field("cncs_contact", "Pessoa de contacto", "input", "Nome / cargo") +
    field("cncs_address", "Endereço", "input", "Morada") +
    `</div>`
  )}
    ${section("2", "Informações do Incidente",
    row2([["Deteção", alert.date], ["Natureza", nature], ["Impacto inicial", impact]]) +
    `<div style="margin-top:8px"><div style="font-size:10px;color:var(--muted);margin-bottom:4px">Descrição sumária (≤500 car.)</div>
       <div style="font-weight:600;font-size:13px">${summary}</div></div>` +
    `<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px">
        <div><div style="font-size:10px;color:var(--muted);margin-bottom:4px">Estado atual</div>
          <select id="cncs_status" style="width:100%;padding:7px;border-radius:8px;border:1px solid var(--border);background:var(--input-bg);color:inherit;font-size:13px">
            <option>Em Investigação</option><option>Ativo</option><option>Contido</option><option>Resolvido</option>
          </select></div>
        <div><div style="font-size:10px;color:var(--muted);margin-bottom:4px">Incidente ativo?</div>
          <select id="cncs_active" style="width:100%;padding:7px;border-radius:8px;border:1px solid var(--border);background:var(--input-bg);color:inherit;font-size:13px">
            <option>Sim</option><option>Não</option><option>Desconhecido</option>
          </select></div>
      </div>`
  )}
    ${section("3", "Sistemas e Serviços Afetados",
    `<div style="margin-bottom:8px">${systems.map(s => `<span style="display:inline-flex;align-items:center;gap:5px;margin-right:8px;font-size:13px;font-weight:600">· ${s}</span>`).join("")}</div>` +
    `<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">` +
    field("cncs_users", "Utilizadores afetados (estimativa)", "input", "ex.: 120") +
    `<div style="margin-top:8px"><div style="font-size:10px;color:var(--muted);margin-bottom:4px">Âmbito geográfico</div>
          <select id="cncs_geo" style="width:100%;padding:7px;border-radius:8px;border:1px solid var(--border);background:var(--input-bg);color:inherit;font-size:13px">
            <option>Local</option><option>Nacional</option><option>Transfronteiriço</option>
          </select></div>` +
    `</div>` +
    field("cncs_countries", "Países (se transfronteiriço)", "input", "ex.: ES, FR")
  )}
    ${section("4", "Avaliação Preliminar de Impacto",
    row2([["Impacto inicial", impact], ["Dados pessoais comprometidos?", pii]]) +
    `<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">` +
    field("cncs_records", "Registos comprometidos (estimativa)", "input", "ex.: 5000") +
    field("cncs_duration", "Duração interrupção (estimativa)", "input", "ex.: 2h") +
    `</div>`
  )}
    ${section("5", "Medidas Imediatas Tomadas",
    `<div style="display:flex;flex-direction:column;gap:6px;margin-bottom:8px">${immediate.map((x, i) => `
        <div style="display:flex;gap:8px;font-size:13px">
          <div style="flex-shrink:0;width:18px;height:18px;border-radius:50%;background:rgba(96,165,250,.15);color:#60a5fa;font-size:9px;font-weight:800;display:flex;align-items:center;justify-content:center">${i + 1}</div>
          <div>${x}</div>
        </div>`).join("")}</div>` +
    field("cncs_extra_measures", "Medidas adicionais", "textarea", "O que já foi feito nas próximas horas")
  )}
    ${section("7", "Informações Adicionais",
    field("cncs_iocs", "IoCs (indicadores de compromisso)", "textarea", "ex.: IPs, hashes, domínios", ioc.join("\n")) +
    field("cncs_notes", "Observações", "textarea", "", "Origem: Acronis (mock). Evidências a anexar via módulo Documentos.")
  )}
    ${section("8", "Declaração",
    `<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">` +
    field("cncs_decl_name", "Nome do declarante", "input", "Nome completo") +
    field("cncs_decl_role", "Cargo", "input", "ex.: CISO / DPO") +
    `</div>` +
    `<div style="margin-top:8px;font-size:12px;color:var(--muted)">Data: <b>${new Date().toISOString().slice(0, 10)}</b></div>`
  )}const MOCK_ALERTS = (JSON.parse(localStorage.getItem("tb_acronis_alerts") || "[]"))
  .map(a => ({
    id: a.id, date: a.ts, severity: a.sev,
    asset: a.asset, category: a.cat, summary: a.msg
  }));
  `;
}

function val(id) { const el = document.getElementById(id); return (el?.value || "").trim(); }

function buildCNCS24hPdfHtml(alert, ai, p) {
  const detection = alert.date;
  const nature = inferIncidentNature(alert);
  const impact = inferImpactLevel(alert, ai.asset.criticality);
  const systems = inferAffectedSystems(alert, ai.asset.notes);
  const pii = (ai.asset.notes || "").toLowerCase().includes("pii") ? "Sim" : "Ainda desconhecido";
  const immediate = ai.actions.slice(0, 3);
  const summary = `${alert.summary} (Ativo: ${alert.asset})`.slice(0, 500);
  return `<html><head><meta charset="utf-8"><title>Notificação CNCS 24h (Draft)</title>
  <style>body{font-family:Arial,sans-serif;padding:22px}.box{border:1px solid #ddd;border-radius:10px;padding:12px 14px;margin:10px 0}.two{display:grid;grid-template-columns:1fr 1fr;gap:10px}.lbl{font-size:12px;color:#555}.val{font-weight:700}.pre{white-space:pre-wrap}</style>
  </head><body>
  <h2>Notificação Inicial (24h) — Draft · ${new Date().toISOString().slice(0, 10)}</h2>
  <div style="color:#444;margin-bottom:12px">Gerado automaticamente a partir de ${esc(alert.id)} (mock Acronis)</div>
  <div class="box"><div class="val">1) Entidade</div>
    <div class="two" style="margin-top:8px">
      <div><div class="lbl">Entidade</div><div class="val">Clínica Exemplo</div></div>
      <div><div class="lbl">Tipo</div><div class="val">Importante (Anexo II)</div></div>
    </div>
    <div class="two" style="margin-top:8px">
      <div><div class="lbl">Telefone</div><div class="val">${esc(p.phone) || "—"}</div></div>
      <div><div class="lbl">Email</div><div class="val">${esc(p.email) || "—"}</div></div>
    </div>
    <div class="two" style="margin-top:8px">
      <div><div class="lbl">Contacto</div><div class="val">${esc(p.contact) || "—"}</div></div>
      <div><div class="lbl">Endereço</div><div class="val">${esc(p.address) || "—"}</div></div>
    </div></div>
  <div class="box"><div class="val">2) Incidente</div>
    <div class="two" style="margin-top:8px">
      <div><div class="lbl">Deteção</div><div class="val">${esc(detection)}</div></div>
      <div><div class="lbl">Natureza</div><div class="val">${esc(nature)}</div></div>
    </div>
    <div style="margin-top:8px"><div class="lbl">Descrição sumária</div><div class="val">${esc(summary)}</div></div>
    <div class="two" style="margin-top:8px">
      <div><div class="lbl">Estado</div><div class="val">${esc(p.status) || "Em Investigação"}</div></div>
      <div><div class="lbl">Ativo?</div><div class="val">${esc(p.active) || "Sim"}</div></div>
    </div></div>
  <div class="box"><div class="val">3) Sistemas Afetados</div>
    <div style="margin-top:8px" class="pre">${systems.map(s => `• ${s}`).join("\n")}</div>
    <div class="two" style="margin-top:8px">
      <div><div class="lbl">Utilizadores afetados</div><div class="val">${esc(p.users) || "—"}</div></div>
      <div><div class="lbl">Âmbito</div><div class="val">${esc(p.geo) || "Local"}</div></div>
    </div></div>
  <div class="box"><div class="val">4) Impacto</div>
    <div class="two" style="margin-top:8px">
      <div><div class="lbl">Nível impacto</div><div class="val">${esc(impact)}</div></div>
      <div><div class="lbl">Dados pessoais?</div><div class="val">${esc(pii)}</div></div>
    </div>
    <div class="two" style="margin-top:8px">
      <div><div class="lbl">Registos comprometidos</div><div class="val">${esc(p.records) || "—"}</div></div>
      <div><div class="lbl">Duração interrupção</div><div class="val">${esc(p.duration) || "—"}</div></div>
    </div></div>
  <div class="box"><div class="val">5) Medidas Imediatas</div>
    <div style="margin-top:8px" class="pre">${immediate.map(x => `• ${x}`).join("\n")}</div>
    <div style="margin-top:8px"><div class="lbl">Medidas adicionais</div><div class="val pre">${esc(p.extra) || "—"}</div></div></div>
  <div class="box"><div class="val">7) Info Adicionais</div>
    <div style="margin-top:8px"><div class="lbl">IoCs</div><div class="val pre">${esc(p.iocs) || "—"}</div></div>
    <div style="margin-top:8px"><div class="lbl">Observações</div><div class="val pre">${esc(p.notes) || "—"}</div></div></div>
  <div class="box"><div class="val">Declaração</div>
    <div class="two" style="margin-top:8px">
      <div><div class="lbl">Nome</div><div class="val">${esc(p.declName) || "—"}</div></div>
      <div><div class="lbl">Cargo</div><div class="val">${esc(p.declRole) || "—"}</div></div>
    </div>
    <div style="margin-top:8px"><div class="lbl">Data</div><div class="val">${new Date().toISOString().slice(0, 10)}</div></div></div>
  <script>window.onload=()=>window.print();</script></body></html>`;
}

function openCNCSModal() {
  if (!selectedAlert || !selectedAI) return;
  const body = document.getElementById("cncs24hBody");
  if (body) body.innerHTML = buildCNCS24hDraftHtml(selectedAlert, selectedAI);
  openModal("cncs24hModal");
}

function printCNCS() {
  if (!selectedAlert || !selectedAI) return;
  const p = {
    phone: val("cncs_phone"), email: val("cncs_email"),
    contact: val("cncs_contact"), address: val("cncs_address"),
    status: val("cncs_status"), active: val("cncs_active"),
    users: val("cncs_users"), geo: val("cncs_geo"), countries: val("cncs_countries"),
    records: val("cncs_records"), duration: val("cncs_duration"),
    extra: val("cncs_extra_measures"), iocs: val("cncs_iocs"), notes: val("cncs_notes"),
    declName: val("cncs_decl_name"), declRole: val("cncs_decl_role"),
  };
  const w = window.open("", "_blank");
  if (!w) return;
  w.document.open(); w.document.write(buildCNCS24hPdfHtml(selectedAlert, selectedAI, p)); w.document.close();
}

async function loadUsers() {
  const res = await fetch("/api/users");
  const users = await res.json();
  console.log("users API:", users);
  const selects = [
    document.getElementById("ta_owner"),
    document.getElementById("ra_owner"),
    document.getElementById("ra_action_owner")
  ];

  selects.forEach(select => {
    if (!select) return;
    select.innerHTML =
      '<option value="">Selecionar responsável...</option>' +
      users.map(u =>
        `<option value="${u.id_user}" data-name="${u.name || u.email}">${u.name || u.email}</option>`
      ).join("");
  });
}

// ── Wire UI ───────────────────────────────────────────────
function wireUI() {
  // Alert action buttons
  document.getElementById("btnCNCS24h")?.addEventListener("click", openCNCSModal);
  document.getElementById("btnCloseCNCS")?.addEventListener("click", () => closeModal("cncs24hModal"));
  document.getElementById("btnCloseCNCS2")?.addEventListener("click", () => closeModal("cncs24hModal"));
  document.getElementById("btnPrintCNCS")?.addEventListener("click", printCNCS);

  document.getElementById("btnCreateRiskFromAlert")?.addEventListener("click", openRiskModalPrefilled);

  // Risk modal
  document.getElementById("riskAlertClose")?.addEventListener("click", () => closeModal("riskAlertModal"));
  document.getElementById("riskAlertClose2")?.addEventListener("click", () => closeModal("riskAlertModal"));
  // fechar ao clicar no overlay (fora do modal-card)
  document.getElementById("riskAlertModal")?.addEventListener("click", (e) => {
    if (e.target === document.getElementById("riskAlertModal")) closeModal("riskAlertModal");
  });

  document.getElementById("ra_prob")?.addEventListener("change", updateRiskScoreUI);
  document.getElementById("ra_impact")?.addEventListener("change", updateRiskScoreUI);

  document.getElementById("ra_save")?.addEventListener("click", async () => {
    const prob = Number(document.getElementById("ra_prob").value);
    const impact = Number(document.getElementById("ra_impact").value);
    const desc = document.getElementById("ra_desc").value.trim();
    if (!desc) { alert("Preenche pelo menos a descrição do risco."); return; }

    const risk = {
      id: document.getElementById("ra_id").value || `RK-${Date.now()}`,
      source: "acronis", sourceLabel: "Acronis",
      alertId: document.getElementById("ra_alertId").value || null,
      alertLabel: document.getElementById("ra_alert").value,
      asset: document.getElementById("ra_asset").value,
      description: desc,
      threat: document.getElementById("ra_threat").value.trim(),
      vulnerability: document.getElementById("ra_vuln").value.trim(),
      riskOwner: Number(document.getElementById("ra_owner").value),
      cia: {
        c: document.getElementById("ra_c").checked,
        i: document.getElementById("ra_i").checked,
        a: document.getElementById("ra_a").checked,
      },
      prob, impact, score: prob * impact,
      status: document.getElementById("ra_status").value,
      actions: document.getElementById("ra_actions").value.trim(),
      due: document.getElementById("ra_due").value.trim(),
      context: document.getElementById("ra_alertPreview")?.innerText || "",
      createdAt: new Date().toISOString(),
    };

    await saveRisk({
      id: risk.id,
      asset: risk.asset,
      title: risk.description,
      description: risk.description,
      threat: risk.threat,
      vulnerability: risk.vulnerability,
      risk_owner: risk.riskOwner,
      actions: risk.actions,
      due: risk.due,
      status: risk.status,
      probability: prob,
      impact: impact,
      origin: "acronis"
    });

    await renderRisks();
    closeModal("riskAlertModal");
  });

  // Treatment modal
  document.getElementById("treatmentAlertClose")?.addEventListener("click", () => closeModal("treatmentAlertModal"));
  document.getElementById("treatmentAlertClose2")?.addEventListener("click", () => closeModal("treatmentAlertModal"));
  document.getElementById("treatmentAlertModal")?.addEventListener("click", (e) => {
    if (e.target === document.getElementById("treatmentAlertModal")) closeModal("treatmentAlertModal");
  });

  document.getElementById("cncs24hModal")?.addEventListener("click", (e) => {
    if (e.target === document.getElementById("cncs24hModal")) closeModal("cncs24hModal");
  });

  document.getElementById("ta_save").addEventListener("click", async () => {

    const riskId = document.getElementById("ta_risk_id")?.value;

    if (!riskId) {
      alert("Nenhum risco associado ao plano.");
      return;
    }

    const plan = {
      risk_id: riskId,
      description: document.getElementById("ta_plan_desc").value.trim(),
      strategy: document.getElementById("ta_strategy").value,
      due: document.getElementById("ta_due").value,
      owner: document.getElementById("ta_owner").value,
      priority: document.getElementById("ta_priority").value
    };

    const res = await fetch("/api/treatment-plans", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(plan)
    });

    const data = await res.json();

    if (data.success) {

      closeModal("treatmentAlertModal");

      document.getElementById("btnGoTreatment").style.display = "inline-flex";

    } else {
      alert("Erro ao criar plano");
    }

  });

  // Risks filters
  document.getElementById("riskSearchInput")?.addEventListener("input", (e) => {
    riskSearch.q = e.target.value; renderRisks();
  });
  document.getElementById("riskLevelFilter")?.addEventListener("change", (e) => {
    riskSearch.level = e.target.value; renderRisks();
  });
  document.getElementById("riskStatusFilter")?.addEventListener("change", (e) => {
    riskSearch.status = e.target.value; renderRisks();
  });
  document.getElementById("btnClearRisks")?.addEventListener("click", async () => {
    await clearRisks(); selectedRiskIds.clear(); await renderRisks();
  });
  document.getElementById("btnRemoveRisks")?.addEventListener("click", async () => {
    const allRisks = await loadRisks();
    const toRemove = allRisks.filter(r => selectedRiskIds.has(r.id));
    for (const r of toRemove) {
      await deleteRisk(r.id);
    }
    selectedRiskIds.clear();
    document.getElementById("btnRemoveRisks").disabled = true;
    await renderRisks();
  });

  // ESC
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal("riskAlertModal");
      closeModal("treatmentAlertModal");
      closeModal("cncs24hModal");
    }
  });

  // Auto-select from dashboard link
  const url = new URL(window.location.href);
  const alertId = url.searchParams.get("alert_id");
  if (alertId) {
    document.getElementById("wazuh")?.scrollIntoView({ behavior: "smooth", block: "start" });
    selectAlert(alertId);
  }
}

document.addEventListener("DOMContentLoaded", async () => {
  await loadUsers();   // carregar users primeiro

  renderAlerts();
  renderRisks();

  wireUI();
});