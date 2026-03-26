// Techbase GRC • Compliance page
// Vanilla JS — sem frameworks externos.

(() => {
  // ── Helpers ─────────────────────────────────────────────────────────────────
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
    if (!r.ok) throw new Error(data.message || `HTTP ${r.status}`);
    return data;
  }
  async function apiDelete(url) {
    const r = await fetch(url, {
      method: "DELETE",
      headers: { "X-CSRF-TOKEN": csrf(), Accept: "application/json" },
    });
    const data = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(data.message || `HTTP ${r.status}`);
    return data;
  }

  function showToast(level, msg, ms = 7000) {
    let t = document.getElementById("grc-toast");
    if (!t) {
      t = document.createElement("div");
      t.id = "grc-toast";
      t.style.cssText = "position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500;max-width:380px;box-shadow:0 4px 20px rgba(0,0,0,.3);transition:opacity .3s";
      document.body.appendChild(t);
    }
    const bg = { ok: "rgba(52,211,153,.15)", warn: "rgba(251,191,36,.15)", err: "rgba(248,113,113,.15)", info: "rgba(96,165,250,.12)" };
    t.style.cssText += `;background:${bg[level] || bg.info};border:1px solid rgba(255,255,255,.1);color:var(--color-text-primary);opacity:1`;
    const icon = {
      ok: "✓",
      warn: "⚠",
      err: "✕",
      info: "ℹ"
    };

    t.innerHTML = `<b style="margin-right:6px">${icon[level] || ""}</b>${msg}`;
    clearTimeout(t._t);
    t._t = setTimeout(() => { t.style.opacity = "0"; }, ms);
  }

  // ── Estado ───────────────────────────────────────────────────────────────────
  let DATA = [];   // array de frameworks completo
  let ALL_DOCS = [];   // documentos aprovados para ligar
  let CURRENT_CTRL = null; // controlo aberto no modal

  // ── Labels e cores ───────────────────────────────────────────────────────────
  const STATUS_LABEL = { compliant: "Conforme", partial: "Parcial", non_compliant: "Não conforme" };
  const STATUS_CLS = { compliant: "ok", partial: "warn", non_compliant: "bad" };
  const STATUS_DOT = { compliant: "dot-compliant", partial: "dot-partial", non_compliant: "dot-non_compliant" };

  function statusBadge(s) {
    const cls = STATUS_CLS[s] || "";
    const lbl = STATUS_LABEL[s] || "Não avaliado";
    return `<span class="tag ${cls}" style="font-size:11px"><span class="s"></span>${lbl}</span>`;
  }

  // ── Carregar dados ───────────────────────────────────────────────────────────
  async function loadCompliance() {
    const list = document.getElementById("complianceList");
    list.innerHTML = '<div style="padding:40px;text-align:center;color:var(--muted)">A carregar...</div>';

    const fw = document.getElementById("fwFilter")?.value || "";
    const status = document.getElementById("statusFilter")?.value || "";

    let url = "/api/compliance";
    const params = new URLSearchParams();
    if (fw) params.set("framework", fw);
    if (status) params.set("status", status);
    if ([...params].length) url += "?" + params.toString();

    try {
      DATA = await apiGet(url);
      populateFwFilter();
      renderKpiBar();
      renderList();
    } catch (e) {
      list.innerHTML = `<div style="padding:40px;text-align:center;color:var(--muted)">Erro ao carregar: ${e.message}</div>`;
    }
  }

  function populateFwFilter() {
    const sel = document.getElementById("fwFilter");
    if (!sel || sel.options.length > 1) return; // já populado
    DATA.forEach(fw => {
      const opt = document.createElement("option");
      opt.value = fw.name;
      opt.textContent = fw.name;
      sel.appendChild(opt);
    });
  }

  // ── KPI Bar ──────────────────────────────────────────────────────────────────
  function renderKpiBar() {
    const bar = document.getElementById("kpiBar");
    if (!bar) return;

    // Agregar todos os controlos de todos os frameworks
    let total = 0, compliant = 0, partial = 0, nonCompliant = 0;
    DATA.forEach(fw => {
      total += fw.summary.total;
      compliant += fw.summary.compliant;
      partial += fw.summary.partial;
      nonCompliant += fw.summary.non_compliant;
    });
    const notAssessed = total - compliant - partial - nonCompliant;
    const pct = total > 0 ? Math.round((compliant / total) * 100) : 0;
    const pctW = total > 0 ? Math.round(((compliant + partial * 0.5) / total) * 100) : 0;

    bar.innerHTML = [
      kpiCard("Total de controlos", total, "", ""),
      kpiCard("Conformes", compliant, "color:#34d399", "compliant"),
      kpiCard("Parciais", partial, "color:#fbbf24", "partial"),
      kpiCard("Não conformes", nonCompliant, "color:#f87171", "non_compliant"),
      kpiCard("Por avaliar", notAssessed, "color:var(--muted)", "not_assessed"),
      kpiCard("Conformidade %", pct + "%", "color:#34d399", ""),
      kpiCard("Ponderada %", pctW + "%", "color:#fbbf24"),
    ].join("");

    $$(".kpi-clickable").forEach(card => {
      card.addEventListener("click", () => {
        const status = card.dataset.status;

        const select = document.getElementById("statusFilter");

        if (select) {
          select.value = status === "not_assessed" ? "" : status;
        }

        loadCompliance();

        showToast("info", `Filtro aplicado: ${card.textContent.trim()}`);
      });
    });

    // Barra global
    const wrap = document.getElementById("globalProgressWrap");
    const barEl = document.getElementById("globalBar");
    const pctEl = document.getElementById("globalPct");
    if (wrap && total > 0) {
      wrap.style.display = "block";
      pctEl.textContent = pct + "%";
      setTimeout(() => { barEl.style.width = pct + "%"; }, 50);
    }
  }

  function kpiCard(label, value, style, status = null) {
    return `<div class="panel kpi-clickable" data-status="${status || ""}" 
      style="flex:1;min-width:100px;padding:12px 14px;text-align:center;cursor:pointer;transition:.15s">
      <div style="font-size:22px;font-weight:800;${style}">${value}</div>
      <div class="muted" style="font-size:11px;margin-top:2px">${label}</div>
    </div>`;
  }

  // ── Lista principal ──────────────────────────────────────────────────────────
  function renderList() {
    const statusFilter = document.getElementById("statusFilter")?.value;
    const list = document.getElementById("complianceList");
    if (!DATA.length) {
      list.innerHTML = '<div style="padding:40px;text-align:center;color:var(--muted)">Sem dados de conformidade. Verifica se os seeds foram executados.</div>';
      return;
    }

    list.innerHTML = "";
    let filteredData = DATA;

    if (statusFilter) {
      filteredData = DATA
        .map(fw => {
          const groups = fw.groups
            .map(g => {
              const controls = g.controls.filter(c => c.status === statusFilter);
              return { ...g, controls };
            })
            .filter(g => g.controls.length > 0);

          return { ...fw, groups };
        })
        .filter(fw => fw.groups.length > 0);
    }

    filteredData.forEach((fw, fwIdx) => {
      // Cabeçalho do framework
      const fwWrap = document.createElement("div");
      fwWrap.style.marginBottom = "24px";

      const pct = fw.summary.pct_weighted ?? fw.summary.pct ?? 0;
      const color = pct >= 70 ? "#34d399" : pct >= 40 ? "#fbbf24" : "#f87171";

      fwWrap.innerHTML = `
        <div class="cpl-fw-header">
          <div style="flex:1">
            <div style="font-size:16px;font-weight:800">${fw.name}</div>
            <div class="muted" style="font-size:12px">${fw.version || ""}</div>
          </div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <span class="chip">${fw.summary.total} controlos</span>
            <span class="chip" style="color:#34d399">${fw.summary.compliant} conformes</span>
            <span class="chip" style="color:#fbbf24">${fw.summary.partial} parciais</span>
            <span class="chip" style="color:#f87171">${fw.summary.non_compliant} não conformes</span>
            <span style="font-size:16px;font-weight:700;color:${color}">${pct}%</span>
          </div>
        </div>`;

      // Grupos
      fw.groups.forEach((group, gIdx) => {
        if (!group.controls.length) return;

        const grpPct = group.summary.pct_weighted ?? group.summary.pct ?? 0;
        const grpColor = grpPct >= 70 ? "#34d399" : grpPct >= 40 ? "#fbbf24" : "#f87171";

        const grpEl = document.createElement("div");
        grpEl.className = "cpl-group";
        grpEl.dataset.fwIdx = fwIdx;
        grpEl.dataset.grpIdx = gIdx;

        // Contar por status para mini barra
        const sc = group.summary.compliant;
        const sp = group.summary.partial;
        const tot = group.summary.total;

        grpEl.innerHTML = `
          <div class="cpl-group-header" data-toggle>
            <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0">
              <span style="font-weight:700;font-size:13px">${group.code}</span>
              <span class="muted" style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${group.name}</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
              <div class="cpl-mini-bar">
                <div class="cpl-mini-bar-fill" style="width:${grpPct}%;background:${grpColor}"></div>
              </div>
              <span style="font-size:12px;color:${grpColor};min-width:36px;text-align:right">${grpPct}%</span>
              <span class="muted" style="font-size:11px">${tot} ctrl</span>
              <span class="muted" style="font-size:16px" data-arrow>▶</span>
            </div>
          </div>
          <div class="cpl-group-body" data-body></div>`;

        // Controlos dentro do grupo
        const body = grpEl.querySelector("[data-body]");
        group.controls.forEach(ctrl => {
          const dotCls = STATUS_DOT[ctrl.status] || "dot-none";
          const hasNotes = ctrl.notes ? `<span class="muted" style="font-size:11px;margin-left:4px" title="${ctrl.notes}">📝</span>` : "";
          const hasEvid = ctrl.evidences?.length ? `<span class="chip" style="font-size:10px">${ctrl.evidences.length} evidência${ctrl.evidences.length > 1 ? "s" : ""}</span>` : "";
          const assessedBy = ctrl.assessed_at
            ? `<span class="muted" style="font-size:11px">Avaliado ${(ctrl.assessed_at || "").slice(0, 10)}${ctrl.assessed_by ? " por " + ctrl.assessed_by : ""}</span>`
            : `<span class="muted" style="font-size:11px">Não avaliado</span>`;

          const row = document.createElement("div");
          row.className = "cpl-control-row";
          row.dataset.controlId = ctrl.id;
          row.innerHTML = `
            <div class="cpl-status-dot ${dotCls}"></div>
            <div style="flex:1;min-width:0">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span style="font-weight:700;font-size:13px">${ctrl.code}</span>
                ${statusBadge(ctrl.status)}
                ${hasEvid}
                ${hasNotes}
              </div>
              <div class="muted" style="font-size:12px;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:600px">${ctrl.description || "—"}</div>
            </div>
            <div style="flex-shrink:0;display:flex;align-items:center;gap:6px">
              ${assessedBy}
              <button class="btn small" type="button">Avaliar</button>
            </div>`;

          // Clique na linha ou no botão → abrir modal
          const openAssess = () => openAssessModal(ctrl);
          row.querySelector(".btn").addEventListener("click", (e) => { e.stopPropagation(); openAssess(); });
          row.addEventListener("click", openAssess);

          body.appendChild(row);
        });

        // Toggle do grupo
        grpEl.querySelector("[data-toggle]").addEventListener("click", () => {
          const b = grpEl.querySelector("[data-body]");
          const a = grpEl.querySelector("[data-arrow]");
          const open = b.classList.toggle("open");
          a.textContent = open ? "▼" : "▶";
        });

        fwWrap.appendChild(grpEl);
      });

      list.appendChild(fwWrap);
    });
  }

  // ── Expandir / Colapsar tudo ──────────────────────────────────────────────
  function setAllGroups(open) {
    $$(".cpl-group-body").forEach(b => b.classList.toggle("open", open));
    $$("[data-arrow]").forEach(a => a.textContent = open ? "▼" : "▶");
  }

  // ── Modal de avaliação ────────────────────────────────────────────────────
  function openAssessModal(ctrl) {
    CURRENT_CTRL = ctrl;

    // Cabeçalho
    document.getElementById("amCode").textContent = ctrl.code || "—";
    document.getElementById("amDesc").textContent = ctrl.description || "—";

    // Guidance
    const gwrap = document.getElementById("amGuidanceWrap");
    const gtext = document.getElementById("amGuidance");
    if (ctrl.guidance) {
      gwrap.style.display = "block";
      gtext.textContent = ctrl.guidance;
    } else {
      gwrap.style.display = "none";
    }

    // Status actual
    const radios = $$('input[name="am_status"]');
    radios.forEach(r => r.checked = r.value === (ctrl.status || "non_compliant"));

    // Notas e link
    document.getElementById("amNotes").value = ctrl.notes || "";
    document.getElementById("amEvidenceLink").value = ctrl.evidence_link || "";

    // Feedback oculto
    const fb = document.getElementById("amFeedback");
    fb.style.display = "none";

    // Evidências
    renderEvidences(ctrl.evidences || []);

    // Histórico — oculto até clicar
    document.getElementById("amHistoryBody").style.display = "none";
    document.getElementById("amHistoryArrow").textContent = "▼ Ver";
    document.getElementById("amHistoryBody").innerHTML = "";

    // Abrir modal
    const m = document.getElementById("assessModal");
    m.style.display = "";
    m.classList.add("open");
    m.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeAssessModal() {
    const m = document.getElementById("assessModal");
    m.classList.remove("open");
    m.setAttribute("aria-hidden", "true");
    m.style.display = "none";
    document.body.style.overflow = "";
    CURRENT_CTRL = null;
  }

  // ── Guardar avaliação ────────────────────────────────────────────────────
  async function saveAssessment() {
    if (!CURRENT_CTRL) return;

    const status = $('input[name="am_status"]:checked')?.value;
    if (!status) {
      showModalFeedback("warn", "Selecciona um estado de conformidade.");
      return;
    }

    const notes = document.getElementById("amNotes").value.trim() || null;
    const evLink = document.getElementById("amEvidenceLink").value.trim() || null;
    const btn = document.getElementById("amSave");

    btn.disabled = true;
    btn.textContent = "A guardar...";

    try {
      await apiPost("/api/compliance/assess", {
        control_id: CURRENT_CTRL.id,
        status,
        notes,
        evidence_link: evLink,
      });

      // Actualizar estado local para reflectir imediatamente na lista
      CURRENT_CTRL.status = status;
      CURRENT_CTRL.notes = notes;
      CURRENT_CTRL.evidence_link = evLink;
      CURRENT_CTRL.assessed_at = new Date().toISOString();

      showToast("ok", "Avaliação guardada.");
      closeAssessModal();

      // Re-render da lista para actualizar badges e pontos
      renderList();
      renderKpiBar();

    } catch (e) {
      showModalFeedback("err", "Erro: " + e.message);
    } finally {
      btn.disabled = false;
      btn.textContent = "Guardar avaliação";
    }
  }

  function showModalFeedback(level, msg) {
    const fb = document.getElementById("amFeedback");
    const bg = { ok: "rgba(52,211,153,.1)", warn: "rgba(251,191,36,.1)", err: "rgba(248,113,113,.1)" };
    fb.style.cssText = `display:block;background:${bg[level] || bg.info};border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 14px;font-size:13px`;
    fb.textContent = msg;
  }

  // ── Evidências no modal ───────────────────────────────────────────────────
  function renderEvidences(evidences) {
    const el = document.getElementById("amEvidencesList");
    if (!evidences.length) {
      el.innerHTML = '<div class="muted" style="font-size:13px">Sem documentos ligados.</div>';
      return;
    }
    el.innerHTML = evidences.map(e => `
      <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.05)">
        <div>
          <b style="font-size:13px">${e.title || e.file_name || "—"}</b>
          <span class="muted" style="font-size:11px;margin-left:8px">${e.type || ""} · ${e.status || ""}</span>
        </div>
        <div style="display:flex;gap:6px">
          <a href="/api/documents/${e.id}/download" target="_blank" class="btn small">Download</a>
          <button class="btn small" style="color:#f87171" data-unlink="${e.id}">Remover</button>
        </div>
      </div>`).join("");

    // Botões de remover
    el.querySelectorAll("[data-unlink]").forEach(btn => {
      btn.addEventListener("click", async () => {
        const docId = Number(btn.dataset.unlink);
        if (!confirm("Remover esta evidência do controlo?")) return;
        try {
          await apiDelete(`/api/compliance/${CURRENT_CTRL.id}/link-doc/${docId}`);
          CURRENT_CTRL.evidences = (CURRENT_CTRL.evidences || []).filter(e => e.id !== docId);
          renderEvidences(CURRENT_CTRL.evidences);
          showToast("ok", "Evidência removida.");
        } catch (err) {
          showToast("err", "Erro: " + err.message);
        }
      });
    });
  }

  // ── Histórico ───────────────────────────────────────────────────────────
  async function loadHistory() {
    if (!CURRENT_CTRL) return;
    const body = document.getElementById("amHistoryBody");
    const arrow = document.getElementById("amHistoryArrow");

    const isOpen = body.style.display !== "none";
    if (isOpen) {
      body.style.display = "none";
      arrow.textContent = "▼ Ver";
      return;
    }

    body.innerHTML = '<div class="muted" style="font-size:12px">A carregar...</div>';
    body.style.display = "block";
    arrow.textContent = "▲ Ocultar";

    try {
      const data = await apiGet(`/api/compliance/${CURRENT_CTRL.id}/history`);
      const history = data.history || [];

      if (!history.length) {
        body.innerHTML = '<div class="muted" style="font-size:12px">Sem histórico registado.</div>';
        return;
      }

      body.innerHTML = history.map(h => `
        <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:12px">
          <div style="flex-shrink:0;color:var(--muted)">${(h.changed_at || "").slice(0, 10)}</div>
          <div style="flex:1">
            ${h.previous_status
          ? `<span class="tag ${STATUS_CLS[h.previous_status] || ""}" style="font-size:10px">${STATUS_LABEL[h.previous_status] || h.previous_status}</span>
                 <span class="muted">→</span>`
          : ""
        }
            <span class="tag ${STATUS_CLS[h.new_status] || ""}" style="font-size:10px">${STATUS_LABEL[h.new_status] || h.new_status}</span>
            ${h.notes ? `<div class="muted" style="margin-top:3px">${h.notes}</div>` : ""}
          </div>
          <div style="flex-shrink:0;color:var(--muted)">${h.changed_by || "—"}</div>
        </div>`).join("");
    } catch (e) {
      body.innerHTML = `<div class="muted" style="font-size:12px">Erro: ${e.message}</div>`;
    }
  }

  // ── Modal ligar documento ────────────────────────────────────────────────
  async function openLinkDocModal() {
    if (!CURRENT_CTRL) return;

    document.getElementById("ldControlCode").textContent = CURRENT_CTRL.code;
    document.getElementById("ldSearch").value = "";

    const listEl = document.getElementById("ldDocsList");
    listEl.innerHTML = '<div class="muted" style="font-size:13px;padding:8px">A carregar...</div>';

    const m = document.getElementById("linkDocModal");
    m.style.display = "";
    m.classList.add("open");
    m.setAttribute("aria-hidden", "false");

    // Carregar docs aprovados (se ainda não carregados)
    if (!ALL_DOCS.length) {
      try {
        ALL_DOCS = await apiGet("/api/documents?status=approved");
      } catch (e) {
        listEl.innerHTML = `<div class="muted" style="font-size:13px;padding:8px">Erro: ${e.message}</div>`;
        return;
      }
    }

    renderDocsList(ALL_DOCS);
  }

  function closeLinkDocModal() {
    const m = document.getElementById("linkDocModal");
    m.classList.remove("open");
    m.setAttribute("aria-hidden", "true");
    m.style.display = "none";
  }

  function renderDocsList(docs) {
    const listEl = document.getElementById("ldDocsList");
    const search = (document.getElementById("ldSearch")?.value || "").toLowerCase();
    const already = new Set((CURRENT_CTRL?.evidences || []).map(e => e.id));

    const filtered = docs.filter(d => {
      const nm = (d.title || d.file_name || "").toLowerCase();
      return nm.includes(search);
    });

    if (!filtered.length) {
      listEl.innerHTML = '<div class="muted" style="font-size:13px;padding:8px">Sem documentos disponíveis.</div>';
      return;
    }

    listEl.innerHTML = filtered.map(d => {
      const linked = already.has(d.id);
      return `<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.05)">
        <div>
          <b style="font-size:13px">${d.title || d.file_name || "—"}</b>
          <div class="muted" style="font-size:11px">${d.type || ""} · v${d.version || "—"}</div>
        </div>
        <button class="btn small ${linked ? "" : "ok"}" data-link-doc="${d.id}" ${linked ? "disabled" : ""}>
          ${linked ? "Já ligado" : "Ligar"}
        </button>
      </div>`;
    }).join("");

    listEl.querySelectorAll("[data-link-doc]").forEach(btn => {
      btn.addEventListener("click", async () => {
        const docId = Number(btn.dataset.linkDoc);
        btn.disabled = true;
        btn.textContent = "A ligar...";
        try {
          const res = await apiPost(`/api/compliance/${CURRENT_CTRL.id}/link-doc`, { doc_id: docId });
          // Adicionar ao estado local
          if (!CURRENT_CTRL.evidences) CURRENT_CTRL.evidences = [];
          CURRENT_CTRL.evidences.push(res.document);
          renderEvidences(CURRENT_CTRL.evidences);
          btn.textContent = "Já ligado";
          showToast("ok", "Documento ligado ao controlo.");
        } catch (e) {
          showToast("err", "Erro: " + e.message);
          btn.disabled = false;
          btn.textContent = "Ligar";
        }
      });
    });
  }

  // ── Init ─────────────────────────────────────────────────────────────────
  document.addEventListener("DOMContentLoaded", () => {

    // Filtros
    document.getElementById("fwFilter")?.addEventListener("change", loadCompliance);
    document.getElementById("statusFilter")?.addEventListener("change", loadCompliance);

    // Expandir / Colapsar
    document.getElementById("btnExpandAll")?.addEventListener("click", () => setAllGroups(true));
    document.getElementById("btnCollapseAll")?.addEventListener("click", () => setAllGroups(false));

    // Modal avaliação
    document.getElementById("amClose")?.addEventListener("click", closeAssessModal);
    document.getElementById("amCancel")?.addEventListener("click", closeAssessModal);
    document.getElementById("amSave")?.addEventListener("click", saveAssessment);
    document.getElementById("assessModal")?.addEventListener("click", e => {
      if (!e.target.closest('.modal-card')) closeAssessModal();
    });

    // Histórico toggle
    document.getElementById("amHistoryToggle")?.addEventListener("click", loadHistory);

    // Ligar documento
    document.getElementById("amLinkDoc")?.addEventListener("click", openLinkDocModal);
    document.getElementById("ldClose")?.addEventListener("click", closeLinkDocModal);
    document.getElementById("linkDocModal")?.addEventListener("click", e => {
      if (e.target.id === "linkDocModal") closeLinkDocModal();
    });

    // Pesquisa no modal de documentos
    document.getElementById("ldSearch")?.addEventListener("input", () => renderDocsList(ALL_DOCS));

    //upload de documentos de evidencias
    const tabSelect = document.getElementById("ldTabSelect");
    const tabUpload = document.getElementById("ldTabUpload");

    const sectionSelect = document.getElementById("ldSelectSection");
    const sectionUpload = document.getElementById("ldUploadSection");

    tabSelect?.addEventListener("click", () => {
      sectionSelect.style.display = "";
      sectionUpload.style.display = "none";
      tabSelect.classList.add("ok");
      tabUpload.classList.remove("ok");
    });

    tabUpload?.addEventListener("click", () => {
      sectionSelect.style.display = "none";
      sectionUpload.style.display = "";
      tabUpload.classList.add("ok");
      tabSelect.classList.remove("ok");
    });

    document.getElementById("ldUploadBtn")?.addEventListener("click", async () => {
      const fileInput = document.getElementById("ldFile");
      const file = fileInput.files[0];

      if (!file) {
        showToast("warn", "Seleciona um ficheiro.");
        return;
      }

      const formData = new FormData();
      formData.append("file", file);
      formData.append("title", file.name);
      formData.append("type", "evidence");
      formData.append("version", "1.0");
      formData.append("origin", "compliance");

      const btn = document.getElementById("ldUploadBtn");
      btn.disabled = true;
      btn.textContent = "A enviar...";
      try {
        // 1. Upload do documento
        const res = await fetch("/api/documents/upload", {
          method: "POST",
          headers: {
            "X-CSRF-TOKEN": csrf(),
            "Accept": "application/json"
          },
          body: formData
        });

        const data = await res.json();
        console.log("UPLOAD RESPONSE:", data);
        console.log("STATUS:", res.status);
        console.log("DOC ID:", data.id);

        btn.disabled = false;
        btn.textContent = "Upload & ligar";

        // limpa input
        fileInput.value = "";


        document.getElementById("ldDocsList").innerHTML = "";

        if (!res.ok) throw new Error(data.message || "Erro upload");

        // 2. Ligar ao controlo
        await apiPost(`/api/compliance/${CURRENT_CTRL.id}/link-doc`, {
          doc_id: data.doc_id
        });

        // 3. Atualizar UI
        CURRENT_CTRL.evidences.push({
          id: data.doc_id,
          title: file.name,
          type: "evidence",
          status: "pending"
        });
        renderEvidences(CURRENT_CTRL.evidences);

        showToast("ok", "Documento enviado e ligado!");

      } catch (e) {
        showToast("err", e.message);
      }
    });
    // ESC
    document.addEventListener("keydown", e => {
      if (e.key !== "Escape") return;
      if (document.getElementById("linkDocModal")?.classList.contains("open")) { closeLinkDocModal(); return; }
      if (document.getElementById("assessModal")?.classList.contains("open")) { closeAssessModal(); return; }
    });

    // Carregar dados
    loadCompliance();
  });

})();
