// Techbase GRC • Riscos
// Vanilla JS — sem localStorage, sem mock de alertas.

(() => {
  // ── Helpers ─────────────────────────────────────────────────────────────────
  // Função auxiliar para verificar permissões globais
  const hasPerm = (p) => window.TB_PERMISSIONS && window.TB_PERMISSIONS.includes(p);
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

  async function apiGet(url) {
    const r = await fetch(url, { headers: { Accept: "application/json" } });
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return r.json();
  }
  async function apiPost(url, body = {}) {
    const r = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
      body: JSON.stringify(body),
    });
    const data = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(data.message || data.error || `HTTP ${r.status}`);
    return data;
  }
  async function apiPut(url, body = {}) {
    const r = await fetch(url, {
      method: "PUT",
      headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
      body: JSON.stringify(body),
    });
    const data = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(data.error || `HTTP ${r.status}`);
    return data;
  }
  async function apiDelete(url) {
    const r = await fetch(url, {
      method: "DELETE",
      headers: { "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
    });
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return r.json().catch(() => ({}));
  }

  function showToast(level, msg, ms = 4000) {
    let t = document.getElementById("grc-toast");
    if (!t) {
      t = document.createElement("div");
      t.id = "grc-toast";
      t.style.cssText = "position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500;max-width:380px;box-shadow:0 4px 20px rgba(0,0,0,.3);transition:opacity .3s";
      document.body.appendChild(t);
    }
    const bg = { ok: "rgba(52,211,153,.15)", warn: "rgba(251,191,36,.15)", err: "rgba(248,113,113,.15)", info: "rgba(96,165,250,.12)" };
    t.style.cssText += `;background:${bg[level] || bg.info};border:1px solid rgba(255,255,255,.1);color:var(--color-text-primary);opacity:1`;
    t.textContent = msg;
    clearTimeout(t._t);
    t._t = setTimeout(() => { t.style.opacity = "0"; }, ms);
  }

  // ── Estado ──────────────────────────────────────────────────────────────────
  let RISKS = [];
  let ASSETS = [];
  let USERS = [];
  let CURRENT_RISK = null;  // risco aberto no modal (null = novo)
  let AI_SUGGESTION = null; // última sugestão da IA
  let TREAT_AI_SUGGESTION = null;

  let filters = { q: "", level: "all", status: "all" };

  // ── Score helpers ────────────────────────────────────────────────────────────
  function riskLevel(score) {
    if (score >= 17) return { label: "Muito Alta", color: "#f87171", cls: "rk-score-critical", bg: "rgba(248,113,113,.12)" };
    if (score >= 10) return { label: "Alta", color: "#fb923c", cls: "rk-score-high", bg: "rgba(251,146,60,.1)" };
    if (score >= 5) return { label: "Média", color: "#fbbf24", cls: "rk-score-med", bg: "rgba(251,191,36,.1)" };
    return { label: "Baixa", color: "#34d399", cls: "rk-score-low", bg: "rgba(52,211,153,.08)" };
  }

  function matrixColor(score) {
    if (score >= 17) return "#f87171";
    if (score >= 10) return "#fb923c";
    if (score >= 5) return "#fbbf24";
    return "#34d399";
  }

  // ── Carregar dados ───────────────────────────────────────────────────────────
  async function loadAll() {
    try {
      [RISKS, ASSETS, USERS] = await Promise.all([
        apiGet("/api/risks"),
        apiGet("/api/assets"),
        apiGet("/api/users"),
      ]);
      renderKpis();
      renderTable();
      populateSelects();
    } catch (e) {
      document.getElementById("riskTableWrap").innerHTML =
        `<div class="muted" style="padding:32px;text-align:center">Erro ao carregar: ${e.message}</div>`;
    }
  }

  function populateSelects() {
    // Select de ativos no modal de risco
    const asel = document.getElementById("rm_asset");
    if (asel) {
      asel.innerHTML = '<option value="">Selecionar ativo...</option>'
        + ASSETS.map(a => `<option value="${a.id_asset}" data-hostname="${a.hostname || ""}" data-criticality="${a.criticality || ""}" data-type="${a.asset_type || ""}" data-ip="${a.ip_address || ""}">${a.display_name || a.hostname || "Ativo #" + a.id_asset}</option>`).join("");
    }

    // Select de owner (risco)
    [document.getElementById("rm_owner"), document.getElementById("ta_owner"), document.getElementById("tm_owner")].forEach(sel => {
      if (!sel) return;
      sel.innerHTML = '<option value="">Selecionar responsável...</option>'
        + USERS.map(u => `<option value="${u.id_user}">${u.name || u.email}</option>`).join("");
    });
  }

  // ── KPIs ─────────────────────────────────────────────────────────────────────
  function renderKpis() {
    const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setText("rkKpiTotal", RISKS.length);
    setText("rkKpiCritical", RISKS.filter(r => riskLevel(r.score ?? 1).label === "Muito Alta").length);
    setText("rkKpiHigh", RISKS.filter(r => riskLevel(r.score ?? 1).label === "Alta").length);
    setText("rkKpiMed", RISKS.filter(r => riskLevel(r.score ?? 1).label === "Média").length);
    setText("rkKpiLow", RISKS.filter(r => riskLevel(r.score ?? 1).label === "Baixa").length);
    setText("rkKpiOpen", RISKS.filter(r => r.status === "Aberto").length);
    setText("rkKpiTreat", RISKS.filter(r => r.status === "Em tratamento").length);
  }

  // ── Tabela de riscos ─────────────────────────────────────────────────────────
  function filteredRisks() {
    const q = filters.q.toLowerCase();
    return RISKS.filter(r => {
      const matchQ = !q
        || (r.title || r.description || "").toLowerCase().includes(q)
        || (r.hostname || "").toLowerCase().includes(q)
        || (r.asset_name || "").toLowerCase().includes(q);
      const lvl = riskLevel(r.score ?? 1).label;
      const matchL = filters.level === "all" || lvl === filters.level;
      const matchS = filters.status === "all" || r.status === filters.status;
      return matchQ && matchL && matchS;
    });
  }

  function renderTable() {
    const wrap = document.getElementById("riskTableWrap");
    if (!wrap) return;

    const risks = filteredRisks();

    if (!risks.length) {
      wrap.innerHTML = `<div class="muted" style="padding:32px;text-align:center">${RISKS.length ? "Nenhum risco com os filtros aplicados." : "Sem riscos registados. Clica em '+ Novo risco' para começar."
        }</div>`;
      return;
    }

    wrap.innerHTML = `
      <table class="rk-risk-table">
        <thead>
          <tr>
            <th style="width:4px;padding:0"></th>
            <th>Ativo</th>
            <th>Risco</th>
            <th>Score</th>
            <th>Nível</th>
            <th>Estado</th>
            <th>Responsável</th>
            <th>Data alvo</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="riskTbody"></tbody>
      </table>`;

    const tbody = document.getElementById("riskTbody");

    risks.forEach(r => {
      const score = r.score ?? (r.probability * r.impact) ?? 1;
      const lvl = riskLevel(score);
      const asset = r.asset_name || r.hostname || "—";
      const owner = r.owner_name || r.risk_owner || "—";
      const due = r.due ? r.due.slice(0, 10) : "—";
      const btnText = hasPerm('risk.edit') ? 'Editar' : 'Ver';

      let actionBtns = `<button class="btn small" data-edit="${r.id_risk}">${btnText}</button>`;

      // Só mostra o botão de Criar Plano se tiver permissão (ajuste o nome da permissão se necessário)
      if (hasPerm('risk.plan.manage')) {
        actionBtns += ` <button class="btn small ok" data-treat="${r.id_risk}">Plano</button>`;
      }

      // Só mostra o botão de Apagar se tiver permissão
      if (hasPerm('risk.delete')) {
        actionBtns += ` <button class="btn small" style="color:#f87171;opacity:.8" data-del="${r.id_risk}">Apagar</button>`;
      }

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td class="rk-risk-bar-cell"><div class="rk-risk-bar-inner" style="background:${lvl.color}"></div></td>
        <td style="max-width:140px">
          <b style="font-size:13px">${asset}</b>
          ${r.asset_criticality ? `<div class="muted" style="font-size:10px">${r.asset_criticality}</div>` : ""}
        </td>
        <td style="max-width:300px">
          <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${r.title || r.description || "—"}</div>
          ${r.threat ? `<div class="muted" style="font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">⚡ ${r.threat}</div>` : ""}
        </td>
        <td>
          <span class="rk-score-pill" style="background:${lvl.bg};color:${lvl.color}">${score}</span>
        </td>
        <td>
          <span style="font-size:11px;font-weight:700;color:${lvl.color}">${lvl.label}</span>
        </td>
        <td><span class="rk-status-pill">${r.status || "—"}</span></td>
        <td class="muted" style="font-size:12px">${owner}</td>
        <td class="muted" style="font-size:12px">${due}</td>
        <td>
          <div style="display:flex;gap:6px">
             ${actionBtns}
          </div>
        </td>`;

      // Editar
      tr.querySelector("[data-edit]").addEventListener("click", () => openRiskModal(r));

      // Editar / Ver (este existe sempre porque criaste-o em actionBtns logo no início)
      tr.querySelector("[data-edit]")?.addEventListener("click", () => openRiskModal(r));

      // Criar plano de tratamento (Só adiciona o evento se o botão existir)
      tr.querySelector("[data-treat]")?.addEventListener("click", () => openTreatModal(r));

      // Apagar (Só adiciona o evento se o botão existir)
      tr.querySelector("[data-del]")?.addEventListener("click", async () => {
        const nm = r.title || r.description || `Risco #${r.id_risk}`;
        if (!confirm(`Apagar "${nm}"? Esta acção não pode ser desfeita.`)) return;
        try {
          await apiDelete(`/api/risks/${r.id_risk}`);
          RISKS = RISKS.filter(x => x.id_risk !== r.id_risk);
          renderKpis();
          renderTable();
          showToast("ok", "Risco eliminado.");
        } catch (e) {
          showToast("err", "Erro ao eliminar: " + e.message);
        }
      });

      tbody.appendChild(tr);
    });
  }

  // ── Matriz 5×5 ───────────────────────────────────────────────────────────────
  function renderMatrix(prob, impact, containerId = "rm_matrix") {
    const matrix = document.getElementById(containerId);
    if (!matrix) return;
    matrix.innerHTML = "";
    for (let p = 5; p >= 1; p--) {
      for (let i = 1; i <= 5; i++) {
        const score = p * i;
        const cell = document.createElement("div");
        cell.className = "rk-matrix-cell" + (p === prob && i === impact ? " active" : "");
        cell.style.background = matrixColor(score);
        cell.textContent = score;
        matrix.appendChild(cell);
      }
    }
  }

  function updateScoreUI() {
    const prob = Number(document.getElementById("rm_prob")?.value || 3);
    const impact = Number(document.getElementById("rm_impact")?.value || 3);
    const score = prob * impact;
    const lvl = riskLevel(score);

    const num = document.getElementById("rmScoreNum");
    const label = document.getElementById("rmScoreLabel");
    const box = document.getElementById("rmScoreBox");
    const badge = document.getElementById("rmScoreBadge");

    if (num) num.textContent = score;
    if (label) label.textContent = lvl.label;
    if (box) box.className = `rk-score-box ${lvl.cls}`;
    if (badge) {
      badge.textContent = `Score: ${score} — ${lvl.label}`;
      badge.style.cssText = `background:${lvl.bg};color:${lvl.color};border-radius:99px;padding:4px 12px;font-size:11px;font-weight:700`;
    }

    renderMatrix(prob, impact);
  }

  // ── Modal de risco ───────────────────────────────────────────────────────────
  function openRiskModal(risk = null) {
    CURRENT_RISK = risk;
    AI_SUGGESTION = null;
    const canEdit = hasPerm('risk.edit');
    const isNew = !risk;
    document.getElementById("rmTitle").textContent = isNew ? "Novo risco" : (risk.title || risk.description || "Editar risco");
    document.getElementById("rmRef").textContent = isNew ? "Novo risco" : `ID #${risk.id_risk}`;

    // Bloquear inputs, selects e textareas
    const modalInputs = document.querySelectorAll('#riskModal input, #riskModal select, #riskModal textarea');
    modalInputs.forEach(input => {
      input.disabled = !canEdit;
      input.style.opacity = canEdit ? '1' : '0.7';
      input.style.cursor = canEdit ? '' : 'not-allowed';
    });

    //Mostrar/Esconder o botão de Guardar
    const btnSave = document.getElementById("rmSaveBtn");
    if (btnSave) {
      btnSave.style.display = canEdit ? 'inline-flex' : 'none';
    }
    // Limpar / preencher campos
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v ?? ""; };

    set("rm_asset", risk?.id_asset || "");
    set("rm_title", risk?.title || "");
    set("rm_desc", risk?.description || "");
    set("rm_threat", risk?.threat || "");
    set("rm_vuln", risk?.vulnerability || "");
    set("rm_actions", risk?.actions || "");
    set("rm_due", risk?.due ? risk.due.slice(0, 10) : "");
    set("rm_status", risk?.status || "Aberto");
    set("rm_owner", risk?.risk_owner_id || "");
    set("rm_prob", risk?.probability || 3);
    set("rm_impact", risk?.impact || 3);

    // Esconder output IA
    const aiOut = document.getElementById("rmAiOutput");
    const aiAct = document.getElementById("rmAiActions");
    if (aiOut) aiOut.style.display = "none";
    if (aiAct) aiAct.style.display = "none";

    // Info do ativo se já selecionado
    updateAssetInfo(risk?.id_asset);

    updateScoreUI();

    const m = document.getElementById("riskModal");
    m.style.display = "";
    m.classList.add("open");
    m.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeRiskModal() {
    const m = document.getElementById("riskModal");
    m.style.display = "none";
    m.classList.remove("open");
    m.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    CURRENT_RISK = null;
    AI_SUGGESTION = null;
  }

  function updateAssetInfo(assetId) {
    const info = document.getElementById("rmAssetInfo");
    if (!info) return;
    if (!assetId) { info.style.display = "none"; return; }

    const a = ASSETS.find(x => x.id_asset == assetId);
    if (!a) { info.style.display = "none"; return; }

    info.style.display = "block";
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || "—"; };
    set("rmAssetHostname", a.hostname);
    set("rmAssetCriticality", a.criticality);
    set("rmAssetType", a.asset_type);
    set("rmAssetIp", a.ip_address);
  }

  // ── Guardar risco ────────────────────────────────────────────────────────────
  async function saveRisk() {
    const assetId = document.getElementById("rm_asset")?.value;
    const title = document.getElementById("rm_title")?.value?.trim();

    if (!assetId) { showToast("warn", "Selecciona um ativo."); return; }
    if (!title) { showToast("warn", "Preenche o título do risco."); return; }

    const btn = document.getElementById("rmSave");
    btn.disabled = true;
    btn.textContent = "A guardar...";

    const payload = {
      id_asset: Number(assetId),
      title,
      description: document.getElementById("rm_desc")?.value?.trim() || null,
      threat: document.getElementById("rm_threat")?.value?.trim() || null,
      vulnerability: document.getElementById("rm_vuln")?.value?.trim() || null,
      actions: document.getElementById("rm_actions")?.value?.trim() || null,
      due: document.getElementById("rm_due")?.value || null,
      status: document.getElementById("rm_status")?.value || "Aberto",
      risk_owner_id: Number(document.getElementById("rm_owner")?.value) || null,
      probability: Number(document.getElementById("rm_prob")?.value) || 3,
      impact: Number(document.getElementById("rm_impact")?.value) || 3,
      origin: "manual",
    };

    try {
      if (CURRENT_RISK) {
        // Update
        await apiPut(`/api/risks/${CURRENT_RISK.id_risk}`, payload);
        showToast("ok", "Risco actualizado.");
      } else {
        // Create
        await apiPost("/api/risks", payload);
        showToast("ok", "Risco criado.");
      }

      closeRiskModal();
      // Recarregar lista
      RISKS = await apiGet("/api/risks");
      renderKpis();
      renderTable();

    } catch (e) {
      showToast("err", "Erro ao guardar: " + e.message);
    } finally {
      btn.disabled = false;
      btn.textContent = "Guardar risco";
    }
  }

  // ── IA: analisar risco ───────────────────────────────────────────────────────
  async function runAiAnalysis() {
    const title = document.getElementById("rm_title")?.value?.trim();
    const assetId = document.getElementById("rm_asset")?.value;
    const asset = ASSETS.find(x => x.id_asset == assetId);

    if (!title && !asset) {
      showToast("warn", "Preenche pelo menos o título do risco ou selecciona um ativo.");
      return;
    }

    const aiOut = document.getElementById("rmAiOutput");
    const aiAct = document.getElementById("rmAiActions");
    const btn = document.getElementById("rmAiBtn");

    aiOut.style.display = "block";
    aiOut.innerHTML = '<span class="muted">A analisar...</span>';
    if (aiAct) aiAct.style.display = "none";
    btn.disabled = true;

    // Construir prompt contextual
    const assetCtx = asset
      ? `Ativo: ${asset.display_name || asset.hostname} (tipo: ${asset.asset_type || "—"}, criticidade: ${asset.criticality || "—"}).`
      : "";
    const riskCtx = title ? `Risco identificado: "${title}".` : "";
    const threatCtx = document.getElementById("rm_threat")?.value?.trim()
      ? `Ameaça actual: ${document.getElementById("rm_threat").value}.` : "";

    const prompt = `Sou um gestor de GRC. ${assetCtx} ${riskCtx} ${threatCtx}
Analisa este risco segundo o QNRCS e NIS2 e devolve:
1. Descrição do risco em 2 linhas
2. Ameaça principal (1 linha)
3. Vulnerabilidade mais provável (1 linha)
4. 3 a 5 acções de mitigação concretas (bullets)
5. Probabilidade sugerida (1-5) e Impacto sugerido (1-5) com justificação breve`;

    try {
      const res = await fetch("/chat/ask", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
        body: JSON.stringify({ question: prompt }),
      });
      const data = await res.json();
      const text = data.answer || data.content || data.message || "";

      // Guardar sugestão para "Aplicar"
      AI_SUGGESTION = parseSuggestion(text);

      // Renderizar como texto limpo
      aiOut.innerHTML = `<div style="white-space:pre-wrap;font-size:12px;line-height:1.7">${text
        .replace(/\*\*(.+?)\*\*/g, "<b>$1</b>")
        .replace(/\*(.+?)\*/g, "<em>$1</em>")
        .replace(/^[•\-\*] (.+)$/gm, "• $1")
        }</div>`;

      if (aiAct) aiAct.style.display = "flex";

    } catch (e) {
      aiOut.innerHTML = `<span style="color:#f87171">Erro: ${e.message}</span>`;
    } finally {
      btn.disabled = false;
    }
  }

  // Parse básico da resposta da IA para extrair campos
  function parseSuggestion(text) {
    const lines = text.split("\n").map(l => l.trim()).filter(Boolean);

    // Tentar extrair probabilidade e impacto
    let prob = null, impact = null;
    const probMatch = text.match(/probabilidade[:\s]+(\d)/i);
    const impactMatch = text.match(/impacto[:\s]+(\d)/i);
    if (probMatch) prob = Number(probMatch[1]);
    if (impactMatch) impact = Number(impactMatch[1]);

    // Extrair bullets como acções
    const bullets = lines
      .filter(l => l.startsWith("•") || l.startsWith("-") || l.match(/^\d+\./))
      .map(l => l.replace(/^[•\-\d\.]\s*/, "").trim())
      .filter(Boolean);

    return { prob, impact, actions: bullets.join("\n"), raw: text };
  }

  function applyAiSuggestion() {
    if (!AI_SUGGESTION) return;
    if (AI_SUGGESTION.prob && AI_SUGGESTION.prob >= 1 && AI_SUGGESTION.prob <= 5) {
      const el = document.getElementById("rm_prob");
      if (el) el.value = AI_SUGGESTION.prob;
    }
    if (AI_SUGGESTION.impact && AI_SUGGESTION.impact >= 1 && AI_SUGGESTION.impact <= 5) {
      const el = document.getElementById("rm_impact");
      if (el) el.value = AI_SUGGESTION.impact;
    }
    if (AI_SUGGESTION.actions) {
      const el = document.getElementById("rm_actions");
      if (el && !el.value.trim()) el.value = AI_SUGGESTION.actions;
    }
    updateScoreUI();
    showToast("ok", "Sugestões da IA aplicadas.");
  }

  // ── IA: sugerir plano de tratamento ──────────────────────────────────────────
  async function runTreatAiAnalysis() {
    const riskId = document.getElementById("treatModal").dataset.riskId;
    const risk = RISKS.find(r => r.id_risk == riskId);
    if (!risk) return;

    const asset = ASSETS.find(a => a.id_asset == risk.id_asset);

    // O teu toque de génio: Procurar outros riscos abertos para o mesmo ativo
    const otherRisks = RISKS.filter(r =>
      r.id_asset == risk.id_asset &&
      r.id_risk != risk.id_risk &&
      (r.status === "Aberto" || r.status === "Em tratamento")
    );

    const aiOut = document.getElementById("tmAiOutput");
    const aiAct = document.getElementById("tmAiActions");
    const btn = document.getElementById("tmAiBtn");

    aiOut.style.display = "block";
    aiOut.innerHTML = '<span class="muted">A desenhar plano com IA...</span>';
    aiAct.style.display = "none";
    btn.disabled = true;

    // Construção do Prompt
    let prompt = `Sou um especialista de cibersegurança e GRC. Ajuda-me a desenhar um plano de tratamento para este risco:\n`;
    prompt += `- Risco: ${risk.title || risk.description}\n`;
    prompt += `- Ameaça: ${risk.threat || "Não definida"}\n`;
    prompt += `- Vulnerabilidade: ${risk.vulnerability || "Não definida"}\n`;

    if (asset) {
      prompt += `- Ativo: ${asset.display_name || asset.hostname} (Criticidade: ${asset.criticality || "Média"})\n\n`;
    }

    if (otherRisks.length > 0) {
      prompt += `Contexto Crítico: Este ativo também possui os seguintes riscos abertos:\n`;
      otherRisks.forEach(r => {
        prompt += `  • ${r.title || r.description} (Score: ${r.score})\n`;
      });
      prompt += `\nLevando estes outros riscos em consideração para criar sinergias de mitigação, responde com:\n`;
    } else {
      prompt += `Responde com:\n`;
    }

    prompt += `1. Passos recomendados para o plano de tratamento (em bullets práticos).\n`;
    prompt += `2. A prioridade sugerida no exato formato "Prioridade: [Alta, Média ou Baixa]".`;

    try {
      const res = await fetch("/chat/ask", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
        body: JSON.stringify({ question: prompt }),
      });
      const data = await res.json();
      const text = data.answer || data.content || data.message || "";

      // Extrair a prioridade da resposta
      let priority = "Média";
      const priorityMatch = text.match(/Prioridade:\s*(Alta|Média|Media|Baixa)/i);
      if (priorityMatch) {
        priority = priorityMatch[1].replace("Media", "Média"); // Normaliza ausência de acento
      }

      // Remover a linha da prioridade do texto para não poluir a caixa de descrição
      const descText = text.replace(/Prioridade:\s*(Alta|Média|Media|Baixa).*/i, "").trim();

      TREAT_AI_SUGGESTION = { desc: descText, priority: priority };

      aiOut.innerHTML = `<div style="white-space:pre-wrap;font-size:12px;line-height:1.7">${text
        .replace(/\*\*(.+?)\*\*/g, "<b>$1</b>")
        .replace(/\*(.+?)\*/g, "<em>$1</em>")
        }</div>`;

      aiAct.style.display = "flex";

    } catch (e) {
      aiOut.innerHTML = `<span style="color:#f87171">Erro: ${e.message}</span>`;
    } finally {
      btn.disabled = false;
    }
  }

  function applyTreatAiSuggestion() {
    if (!TREAT_AI_SUGGESTION) return;

    const descEl = document.getElementById("tm_desc");
    if (descEl) descEl.value = TREAT_AI_SUGGESTION.desc;

    const prioEl = document.getElementById("tm_priority");
    if (prioEl && TREAT_AI_SUGGESTION.priority) {
      // Encontrar a opção correspondente e selecioná-la
      for (let i = 0; i < prioEl.options.length; i++) {
        if (prioEl.options[i].text.toLowerCase() === TREAT_AI_SUGGESTION.priority.toLowerCase()) {
          prioEl.selectedIndex = i;
          break;
        }
      }
    }

    showToast("ok", "Plano sugerido pela IA aplicado.");
  }
  // ── Modal de tratamento ───────────────────────────────────────────────────────
  function openTreatModal(risk) {
    // Esconder IA do modal de tratamento
    TREAT_AI_SUGGESTION = null;
    const aiOut = document.getElementById("tmAiOutput");
    const aiAct = document.getElementById("tmAiActions");
    if (aiOut) aiOut.style.display = "none";
    if (aiAct) aiAct.style.display = "none";
    const lvl = riskLevel(risk.score ?? 1);

    document.getElementById("tmTitle").textContent = risk.title || risk.description || "—";
    document.getElementById("tmRiskId").textContent = `#${risk.id_risk}`;
    document.getElementById("tmAsset").textContent = risk.asset_name || risk.hostname || "—";
    document.getElementById("tmScore").textContent = `${risk.score ?? "—"} — ${lvl.label}`;
    document.getElementById("tmRiskDesc").textContent = risk.title || risk.description || "—";
    document.getElementById("tmThreat").textContent = risk.threat || "—";
    document.getElementById("tmVuln").textContent = risk.vulnerability || "—";
    document.getElementById("tmRef").textContent = `Risco #${risk.id_risk}`;

    // Pré-preencher prazo e owner do risco
    const due = document.getElementById("tm_due");
    if (due && risk.due) due.value = risk.due.slice(0, 10);

    const owner = document.getElementById("tm_owner");
    if (owner && risk.risk_owner_id) owner.value = risk.risk_owner_id;

    // Guardar id do risco para o save
    const m = document.getElementById("treatModal");
    m.dataset.riskId = risk.id_risk;
    m.style.display = "";
    m.classList.add("open");
    m.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeTreatModal() {
    const m = document.getElementById("treatModal");
    m.style.display = "none";
    m.classList.remove("open");
    m.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  }

  async function saveTreatment() {
    const m = document.getElementById("treatModal");
    const riskId = m.dataset.riskId;
    const desc = document.getElementById("tm_desc")?.value?.trim();

    if (!riskId) { showToast("warn", "Risco não identificado."); return; }
    if (!desc) { showToast("warn", "Preenche a descrição do plano."); return; }

    const btn = document.getElementById("tmSave");
    btn.disabled = true;
    btn.textContent = "A criar...";

    try {
      await apiPost("/api/treatment-plans", {
        risk_id: Number(riskId),
        description: desc,
        strategy: document.getElementById("tm_strategy")?.value || "Mitigar",
        due: document.getElementById("tm_due")?.value || null,
        owner: Number(document.getElementById("tm_owner")?.value) || null,
        priority: document.getElementById("tm_priority")?.value || "Média",
        origin_type: "risk",
      });

      showToast("ok", "Plano de tratamento criado.");
      closeTreatModal();

    } catch (e) {
      showToast("err", "Erro ao criar plano: " + e.message);
    } finally {
      btn.disabled = false;
      btn.textContent = "Criar plano";
    }
  }

  // ── Init ─────────────────────────────────────────────────────────────────────
  document.addEventListener("DOMContentLoaded", () => {

    // Carregar dados
    loadAll();

    // Filtros
    document.getElementById("riskSearch")?.addEventListener("input", e => {
      filters.q = e.target.value; renderTable();
    });
    document.getElementById("riskLevelFilter")?.addEventListener("change", e => {
      filters.level = e.target.value; renderTable();
    });
    document.getElementById("riskStatusFilter")?.addEventListener("change", e => {
      filters.status = e.target.value; renderTable();
    });

    // Novo risco
    document.getElementById("btnNewRisk")?.addEventListener("click", () => openRiskModal());

    // Modal risco — score reactivo
    document.getElementById("rm_prob")?.addEventListener("change", updateScoreUI);
    document.getElementById("rm_impact")?.addEventListener("change", updateScoreUI);

    // Modal risco — info ativo ao seleccionar
    document.getElementById("rm_asset")?.addEventListener("change", e => {
      updateAssetInfo(e.target.value);
    });

    // Modal risco — fechar
    document.getElementById("rmClose")?.addEventListener("click", closeRiskModal);
    document.getElementById("rmCancel")?.addEventListener("click", closeRiskModal);
    document.getElementById("riskModal")?.addEventListener("click", e => {
      if (e.target.id === "riskModal") closeRiskModal();
    });

    // Modal risco — guardar
    document.getElementById("rmSave")?.addEventListener("click", saveRisk);

    // Modal risco — IA
    document.getElementById("rmAiBtn")?.addEventListener("click", runAiAnalysis);
    // Modal tratamento — IA
    document.getElementById("tmAiBtn")?.addEventListener("click", runTreatAiAnalysis);
    document.getElementById("tmAiApply")?.addEventListener("click", applyTreatAiSuggestion);
    document.getElementById("rmAiApply")?.addEventListener("click", applyAiSuggestion);

    // Modal tratamento — fechar
    document.getElementById("tmClose")?.addEventListener("click", closeTreatModal);
    document.getElementById("tmCancel")?.addEventListener("click", closeTreatModal);
    document.getElementById("treatModal")?.addEventListener("click", e => {
      if (e.target.id === "treatModal") closeTreatModal();
    });

    // Modal tratamento — guardar
    document.getElementById("tmSave")?.addEventListener("click", saveTreatment);

    // ESC fecha modais
    document.addEventListener("keydown", e => {
      if (e.key !== "Escape") return;
      if (document.getElementById("treatModal")?.classList.contains("open")) { closeTreatModal(); return; }
      if (document.getElementById("riskModal")?.classList.contains("open")) { closeRiskModal(); return; }
    });

    // Ir para tratamento se vem de URL ?from=risk
    const params = new URLSearchParams(window.location.search);
    if (params.get("from") === "alert") {
      // Redirigir para page de tratamento — a lógica de alertas foi removida
      window.history.replaceState({}, "", window.location.pathname);
    }
    const newRiskAsset = params.get("new_risk_for");
    if (newRiskAsset) {
      window.history.replaceState({}, "", window.location.pathname);
      // Espera garantir o carregamento do estado antes de abrir
      const checkAndOpen = setInterval(() => {
        if (ASSETS && ASSETS.length > 0) {
          clearInterval(checkAndOpen);
          openRiskModal();
          const sel = document.getElementById("rm_asset");
          if (sel) {
            sel.value = newRiskAsset;
            sel.dispatchEvent(new Event("change"));
          }
        }
      }, 100);
      setTimeout(() => clearInterval(checkAndOpen), 5000);
    }
  });

})();