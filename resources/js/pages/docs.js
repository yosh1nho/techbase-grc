// Techbase GRC • NIS2 — Docs page
// Note: sem frameworks JS, tudo vanilla.

(() => {
  // ── CSRF helper ─────────────────────────────────────────────────────────────
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";

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
    if (!r.ok) { const e = await r.json().catch(() => ({})); throw new Error(e.message || `HTTP ${r.status}`); }
    return r.json();
  }

  // ── Estado ───────────────────────────────────────────────────────────────────
  // DOCS agora vem da API — array mantido em memória para UI reactiva
  let DOCS = [];


  let FRAMEWORKS = [
    {
      id: "F1",
      name: "QNRCS",
      source: "CNCS",
      version: "2019",
      updated: "2019-01-01",
      status: "Oficial",
      notes: "Base normativa para avaliações.",
      fileUrl: "/mock/frameworks/cncs-qnrcs-2019.pdf"
    },
    {
      id: "F2",
      name: "NIS2 (Diretiva)",
      source: "UE",
      version: "2022/2555",
      updated: "2022-12-14",
      status: "Oficial",
      notes: "Requisitos legais + reporting.",
      fileUrl: "/mock/frameworks/NIS2.pdf"
    },
  ];


  function fwStatusChip(s) {
    if (s === "Oficial") return `<span class="tag ok"><span class="s"></span> Oficial</span>`;
    return `<span class="tag"><span class="s"></span> ${s || "—"}</span>`;
  }

  function renderFrameworks() {
    const tbody = document.getElementById("fwTbody");
    const count = document.getElementById("fwCount");
    if (!tbody) return;

    count && (count.textContent = String(FRAMEWORKS.length));

    if (!FRAMEWORKS.length) {
      tbody.innerHTML = `<tr><td class="muted" colspan="6">Sem frameworks registados.</td></tr>`;
      return;
    }

    tbody.innerHTML = FRAMEWORKS.map(f => `
    <tr>
      <td><b>${f.name}</b><div class="muted">${f.notes || ""}</div></td>
      <td>${f.source || "—"}</td>
      <td>${f.version || "—"}</td>
      <td class="muted">${f.updated || "—"}</td>
      <td>${fwStatusChip(f.status)}</td>
      <td><button class="btn small" type="button" data-open-fw="${f.id}">Detalhes</button></td>
    </tr>
  `).join("");

    tbody.querySelectorAll("[data-open-fw]").forEach(btn => {
      btn.addEventListener("click", () => {
        const fw = FRAMEWORKS.find(x => x.id === btn.dataset.openFw);
        if (!fw) return;
        openFwModal(fw);
      });
    });
  }

  function openFrameworkDetails(fw) {
    document.getElementById("fwD_name").textContent = fw.name || "—";
    document.getElementById("fwD_source").textContent = fw.source || "—";
    document.getElementById("fwD_version").textContent = fw.version || "—";
    document.getElementById("fwD_updated").textContent = fw.updated || "—";
    document.getElementById("fwD_status").textContent = fw.status || "—";
    document.getElementById("fwD_notes").textContent = fw.notes || "—";

    const iframe = document.getElementById("fwPdf");
    iframe.src = fw.fileUrl || "";
  }

  function clearFrameworkDetails() {
    document.getElementById("fwD_name").textContent = "Selecione um item…";
    document.getElementById("fwD_source").textContent = "—";
    document.getElementById("fwD_version").textContent = "—";
    document.getElementById("fwD_updated").textContent = "—";
    document.getElementById("fwD_status").textContent = "—";
    document.getElementById("fwD_notes").textContent = "—";
    document.getElementById("fwPdf").src = "";
  }

  function openFwModal(fw) {
    const m = document.getElementById("fwModal");
    if (!m) return;

    document.getElementById("fwModalTitle").textContent = fw.name || "—";
    document.getElementById("fwM_source").textContent = fw.source || "—";
    document.getElementById("fwM_version").textContent = fw.version || "—";
    document.getElementById("fwM_updated").textContent = fw.updated || "—";
    document.getElementById("fwM_status").textContent = fw.status || "—";
    document.getElementById("fwM_notes").textContent = fw.notes || "—";
    document.getElementById("fwM_pdf").src = fw.fileUrl || "";

    m.classList.add("open");
    m.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeFwModal() {
    const m = document.getElementById("fwModal");
    if (!m) return;
    m.classList.remove("open");
    m.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    const iframe = document.getElementById("fwM_pdf");
    if (iframe) iframe.src = ""; // limpa pra parar o pdf
  }



  function openUploadModal() {
    const m = document.getElementById("uploadDocModal");
    if (!m) return;
    m.classList.add("open");
    m.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeUploadModal() {
    const m = document.getElementById("uploadDocModal");
    if (!m) return;
    m.classList.remove("open");
    m.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  }


  function resetUploadForm() {
    ["u_name", "u_version", "u_fwNotes"].forEach(id => {
      const el = document.getElementById(id); if (el) el.value = "";
    });
    const type = document.getElementById("u_type"); if (type) type.value = "evidence";
    const src = document.getElementById("u_source"); if (src) src.value = "CNCS";
    const upd = document.getElementById("u_updated"); if (upd) upd.value = "";
    const file = document.getElementById("u_file"); if (file) file.value = "";
    const fb = document.getElementById("u_feedback"); if (fb) { fb.style.display = "none"; fb.textContent = ""; }
    const btn = document.getElementById("u_save"); if (btn) btn.disabled = false;
    syncUploadTypeUI();
  }

  function syncUploadTypeUI() {
    const type = document.getElementById("u_type").value;
    const block = document.getElementById("u_frameworkBlock");
    if (!block) return;
    block.style.display = (type === "framework") ? "block" : "none";
  }

  // ── Upload de documento (real) ──────────────────────────────────────────────
  async function saveUpload() {
    const name = (document.getElementById("u_name")?.value || "").trim();
    const type = document.getElementById("u_type")?.value || "evidence";
    const version = (document.getElementById("u_version")?.value || "").trim();
    const updated = document.getElementById("u_updated")?.value || "";
    const fileEl = document.getElementById("u_file");
    const file = fileEl?.files?.[0] ?? null;

    if (!name) return showFeedback("warn", "Preenche o nome do documento.");
    if (!file) return showFeedback("warn", "Selecciona um ficheiro.");

    const btn = document.getElementById("u_save");
    if (btn) { btn.disabled = true; }
    showFeedback("info", "A fazer upload...");

    const fd = new FormData();
    fd.append("title", name);
    fd.append("type", type);
    fd.append("version", version || "1.0");
    if (updated) fd.append("date", updated);
    fd.append("file", file);

    // Dados extra para framework
    if (type === "framework") {
      fd.append("source", document.getElementById("u_source")?.value || "Outro");
      fd.append("notes", document.getElementById("u_fwNotes")?.value || "");
    }

    try {
      const res = await fetch("/api/documents/upload", {
        method: "POST",
        headers: { "X-CSRF-TOKEN": csrf(), "Accept": "application/json" },
        body: fd,
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);

      if (type === "framework") {
        // Framework: aprovado imediatamente, recarrega lista de frameworks
        showFeedback("ok", data.ingest === "queued"
          ? "Framework guardado e a indexar no Pinecone..."
          : "Framework guardado com sucesso.");
        await loadFrameworks();
        setTimeout(closeUploadModal, 1800);
      } else {
        // Documento normal: fica pendente para aprovação
        showFeedback("ok", "Ficheiro enviado. Aguarda aprovação de um administrador.");
        await loadDocs();
        setTimeout(closeUploadModal, 1800);
      }
    } catch (e) {
      showFeedback("err", "Erro: " + e.message);
      if (btn) btn.disabled = false;
    }
  }

  function showFeedback(level, msg) {
    const fb = document.getElementById("u_feedback");
    if (!fb) return;
    const colors = {
      ok: "var(--color-background-success, rgba(52,211,153,.12))",
      warn: "var(--color-background-warning, rgba(251,191,36,.12))",
      err: "var(--color-background-danger,  rgba(248,113,113,.12))",
      info: "var(--color-background-secondary)",
    };
    fb.style.display = "block";
    fb.style.background = colors[level] || colors.info;
    fb.textContent = msg;
  }

  // ── Carregar frameworks da API ────────────────────────────────────────────
  async function loadFrameworks() {
    try {
      const data = await apiGet("/api/documents?type=framework&status=approved");
      // Combinar com frameworks estáticos (QNRCS, NIS2 pré-carregados) se não vierem da BD
      const fromApi = data.map(d => ({
        id: "api-" + d.id,
        name: d.title || d.file_name,
        source: d.type || "—",
        version: d.version || "—",
        updated: (d.created_at || "").slice(0, 10),
        status: d.status === "approved" ? "Oficial" : d.status,
        notes: d.origin || "",
      }));
      // Só substitui se vier algo da API, senão mantém os estáticos do seed
      if (fromApi.length > 0) FRAMEWORKS = fromApi;
    } catch (e) {
      // Silencioso — frameworks estáticos continuam a funcionar
      console.warn("loadFrameworks:", e.message);
    }
    renderFrameworks();
  }

  // Carregar documentos da API
  async function loadDocs() {
    const tbody = document.getElementById("docsTbody");
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="muted">A carregar...</td></tr>';
    try {
      DOCS = await apiGet("/api/documents");
      renderDocsTable();
    } catch (e) {
      if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="muted">Erro ao carregar documentos.</td></tr>';
      console.error("loadDocs:", e);
    }
  }

  function _fmtSize(b) {
    if (!b) return ""; if (b < 1024) return b + " B"; if (b < 1048576) return (b / 1024).toFixed(1) + " KB"; return (b / 1048576).toFixed(1) + " MB";
  }
  function _statusBadge(s) {
    if (s === "approved") return '<span class="tag ok"><span class="s"></span> Aprovado</span>';
    if (s === "pending") return '<span class="tag warn"><span class="s"></span> Pendente</span>';
    if (s === "rejected") return '<span class="tag"><span class="s"></span> Rejeitado</span>';
    return '<span class="tag"><span class="s"></span> ' + (s || "--") + '</span>';
  }

  function renderDocsTable() {
    const tbody = document.getElementById("docsTbody"), countEl = document.getElementById("docsCount");
    if (!tbody) return;
    if (countEl) countEl.textContent = DOCS.length;
    if (!DOCS.length) { tbody.innerHTML = '<tr><td colspan="6" class="muted">Sem documentos registados.</td></tr>'; return; }
    tbody.innerHTML = DOCS.map(d => {
      const nm = d.title || d.file_name || "--";
      const sub = (d.file_name ? d.file_name : "") + (d.file_size ? " · " + _fmtSize(d.file_size) : "") + (d.origin ? " · " + d.origin.slice(0, 50) : "");
      const dl = d.has_file ? '<a class="btn small" href="/api/documents/' + d.id + '/download" target="_blank">Download</a>'
        : '<button class="btn small" disabled>Sem ficheiro</button>';
      const ap = d.status === "pending"
        ? '<button class="btn ok small" data-approve-doc="' + d.id + '">Aprovar</button><button class="btn small" style="color:#f87171" data-reject-doc="' + d.id + '">Rejeitar</button>'
        : "";
      return '<tr data-doc-id="' + d.id + '">'
        + '<td><b>' + nm + '</b><div class="muted" style="font-size:11px">' + sub + '</div></td>'
        + '<td>' + (d.type || "--") + '</td><td>' + (d.version || "--") + '</td>'
        + '<td data-doc-status-badge>' + _statusBadge(d.status) + '</td>'
        + '<td class="muted">' + ((d.created_at || "--").toString().slice(0, 10)) + '</td>'
        + '<td><div class="actions">' + dl + ap + '</div></td></tr>';
    }).join("");
    tbody.querySelectorAll("[data-approve-doc]").forEach(btn => {
      btn.addEventListener("click", async () => {
        const id = Number(btn.dataset.approveDoc);
        btn.disabled = true;
        btn.textContent = "A aprovar...";
        try {
          const res = await apiPost("/api/documents/" + id + "/approve");
          const d = DOCS.find(x => x.id === id);
          if (d) d.status = "approved";
          renderDocsTable();
          // Feedback de indexação
          if (res.ingest === "queued") {
            const row = document.querySelector('[data-doc-id="' + id + '"]');
            if (row) {
              const cell = row.querySelector("td:first-child");
              if (cell) {
                const badge = document.createElement("div");
                badge.style.cssText = "font-size:11px;color:var(--color-text-warning,#fbbf24);margin-top:3px";
                badge.textContent = "A indexar no Pinecone...";
                cell.appendChild(badge);
                setTimeout(() => badge.remove(), 6000);
              }
            }
          }
        } catch (e) { alert("Erro ao aprovar: " + e.message); btn.disabled = false; btn.textContent = "Aprovar"; }
      });
    });
    tbody.querySelectorAll("[data-reject-doc]").forEach(btn => {
      btn.addEventListener("click", async () => {
        const id = Number(btn.dataset.rejectDoc);
        if (!confirm("Rejeitar?")) return;
        try { await apiPost("/api/documents/" + id + "/reject"); const d = DOCS.find(x => x.id === id); if (d) d.status = "rejected"; renderDocsTable(); }
        catch (e) { alert("Erro ao rejeitar: " + e.message); }
      });
    });
  }

  // manual chunks criados (aparecem depois no histórico)
  let MANUAL_CHUNKS = []; // {id,label,full,suggested}

  // Doc atualmente aberto no modal (mock)
  let CURRENT_DOC_ID = null;

  function statusTagClass(status) {
    if (status === 'Ativo') return 'ok';
    if (status === 'Pendente') return 'warn';
    return '';
  }

  function setDocRowStatus(docId, newStatus) {
    const row = document.querySelector(`tr[data-doc-id="${docId}"]`);
    if (!row) return;

    const badge = row.querySelector('[data-doc-status-badge]');
    if (badge) {
      badge.className = `tag ${statusTagClass(newStatus)}`.trim();
      badge.innerHTML = `<span class="s"></span> ${newStatus}`;
    }

    // mantém o dataset do botão Detalhes em sync
    const detailsBtn = row.querySelector('[data-open-doc-modal]');
    if (detailsBtn) detailsBtn.dataset.docStatus = newStatus;

    // botão Aprovar some quando não é Pendente
    const approveBtn = row.querySelector('[data-approve-doc]');
    if (approveBtn) {
      approveBtn.style.display = (newStatus === 'Pendente') ? '' : 'none';
    }
  }

  function approveDoc(docId) {
    // MOCK: ao aprovar, muda para Ativo.
    setDocRowStatus(docId, 'Ativo');

    // se o modal estiver aberto nesse doc, reflete lá também
    if (CURRENT_DOC_ID === docId) {
      const statusSel = document.getElementById('dStatus');
      if (statusSel) statusSel.value = 'Ativo';

      const approveBtn = document.getElementById('docApproveBtn');
      if (approveBtn) approveBtn.style.display = 'none';
    }
  }

  function classifyStateBadge(state) {
    if (state === 'Aprovado') return 'ok';
    if (state === 'Pendente') return 'warn';
    return '';
  }

  function openDocModal(btn) {
    const modal = document.getElementById('docModal');
    const title = document.getElementById('docModalTitle');

    CURRENT_DOC_ID = btn.dataset.docId || null;

    title.textContent = btn.dataset.docName;
    document.getElementById('dType').textContent = btn.dataset.docType;
    document.getElementById('dVersion').textContent = btn.dataset.docVersion;
    document.getElementById('dUpdated').textContent = btn.dataset.docUpdated;

    const url = btn.dataset.docUrl || "";

    const empty = document.getElementById("docPreviewEmpty");
    const pdf = document.getElementById("docPreviewPdf");
    const img = document.getElementById("docPreviewImg");

    function hideAll() {
      if (empty) empty.style.display = "block";
      if (pdf) { pdf.style.display = "none"; pdf.src = ""; }
      if (img) { img.style.display = "none"; img.src = ""; }
    }

    hideAll();

    if (url) {
      const lower = url.toLowerCase();
      if (lower.endsWith(".pdf")) {
        if (empty) empty.style.display = "none";
        if (pdf) { pdf.src = url; pdf.style.display = "block"; }
      } else if (lower.match(/\.(png|jpg|jpeg|webp)$/)) {
        if (empty) empty.style.display = "none";
        if (img) { img.src = url; img.style.display = "block"; }
      } else {
        // outros tipos (docx etc) -> fica no placeholder
      }
    }

    const docType = btn.dataset.docType;
    const isFramework = docType.toLowerCase().includes("framework") || docType.toLowerCase().includes("norma");

    document.getElementById("frameworkPanel")?.classList.toggle("hide", !isFramework);
    document.getElementById("evidencePanel")?.classList.toggle("hide", isFramework);

    const statusSel = document.getElementById('dStatus');
    statusSel.value = btn.dataset.docStatus;

    // botão de aprovação (mock): só aparece se estiver Pendente
    const approveBtn = document.getElementById('docApproveBtn');
    if (approveBtn) {
      approveBtn.style.display = (btn.dataset.docStatus === 'Pendente') ? '' : 'none';
    }
    if (isFramework) {
      // Zera KPIs de evidência
      document.getElementById('dAssocCount').textContent = '—';
      document.getElementById('dPendingCount').textContent = '—';

      // (Opcional) você pode limpar a lista pra não ficar lixo ao alternar
      const assocList = document.getElementById('assocList');
      if (assocList) assocList.innerHTML = '';

      // aqui você pode preencher um painel específico do framework (se existir)
      // ex.: document.getElementById('fwMetaBox').innerHTML = ...

      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      return;
    }
    const assocList = document.getElementById('assocList');
    assocList.innerHTML = '';

    // Mock inicial
    const rows = [
      {
        id: 'A1',
        control: 'ID.GA-1',
        coverage: 'Alta',
        confidence: '0.82',
        state: 'Aprovado',
        justification: 'Evidência cobre parcialmente; falta periodicidade.'
      },
      {
        id: 'A2',
        control: 'PR.IP-4',
        coverage: 'Média',
        confidence: '0.61',
        state: 'Pendente',
        justification: 'Descreve backup, mas não há evidência de testes.'
      },
    ];

    rows.forEach(r => assocList.appendChild(buildAssocRow(r)));

    document.getElementById('dAssocCount').textContent = rows.length;
    document.getElementById('dPendingCount').textContent = rows.filter(x => x.state === 'Pendente').length;

    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeDocModal() {
    const modal = document.getElementById('docModal');
    const pdf = document.getElementById("docPreviewPdf");
    const img = document.getElementById("docPreviewImg");
    if (pdf) pdf.src = "";
    if (img) img.src = "";
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    CURRENT_DOC_ID = null;
  }

  function buildAssocRow({ id, control, coverage, confidence, state, justification, sourceChunkId, sourceType }) {
    const row = document.createElement('div');
    row.className = 'assoc-row';
    row.dataset.assocId = id || '';

    const badgeCls = classifyStateBadge(state);

    row.innerHTML = `
                      <div class="assoc-left">
                        <div class="assoc-meta">
                          <span class="control-pill">
                            ${control}
                            <span class="ci" data-tip="${control} — descrição curta do controlo (mock).">i</span>
                          </span>
                          <span class="chip">Cobertura: <b>${coverage}</b></span>
                          <span class="chip">Confiança: <b>${confidence}</b></span>
                          <span class="chip ${badgeCls}">Estado: <b>${state}</b></span>
                          ${sourceType ? `<span class="chip">Fonte: <b>${sourceType}</b></span>` : ''}
                        </div>
                        <div class="muted">Justificação: ${justification || '—'}</div>
                      </div>

                      <div class="assoc-actions">
                        <button class="btn" type="button" data-edit>Editar</button>
                        <button class="btn warn" type="button" data-remove>Remover</button>
                      </div>
                    `;

    row.querySelector('[data-remove]').addEventListener('click', () => row.remove());
    row.querySelector('[data-edit]').addEventListener('click', () => openInlineEdit(row, { control, coverage, confidence, state, justification }));

    return row;
  }

  // ===== Edit inline (associação) =====
  function openInlineEdit(row, data) {
    // evita duplicar
    if (row.querySelector('[data-editor]')) return;

    const editor = document.createElement('div');
    editor.dataset.editor = '1';
    editor.style.marginTop = '10px';
    editor.innerHTML = `
                      <div class="assoc-row" style="background: rgba(0,0,0,.10)">
                        <div class="assoc-left">
                          <div class="assoc-meta">
                            <span class="chip">Editar</span>

                            <div style="min-width:220px">
                              <label class="muted">Cobertura</label>
                              <select data-e-coverage>
                                <option ${data.coverage === 'Baixa' ? 'selected' : ''}>Baixa</option>
                                <option ${data.coverage === 'Média' ? 'selected' : ''}>Média</option>
                                <option ${data.coverage === 'Alta' ? 'selected' : ''}>Alta</option>
                              </select>
                            </div>

                            <div style="min-width:220px">
                              <label class="muted">Confiança</label>
                              <select data-e-confidence>
                                <option ${data.confidence === '0.30' ? 'selected' : ''}>0.30</option>
                                <option ${data.confidence === '0.61' ? 'selected' : ''}>0.61</option>
                                <option ${data.confidence === '0.82' ? 'selected' : ''}>0.82</option>
                              </select>
                            </div>

                            <div style="min-width:220px">
                              <label class="muted">Estado</label>
                              <select data-e-state>
                                <option ${data.state === 'Aprovado' ? 'selected' : ''}>Aprovado</option>
                                <option ${data.state === 'Pendente' ? 'selected' : ''}>Pendente</option>
                                <option ${data.state === 'Rejeitado' ? 'selected' : ''}>Rejeitado</option>
                              </select>
                            </div>
                          </div>

                          <div style="margin-top:8px">
                            <label class="muted">Justificação</label>
                            <textarea data-e-just style="min-height:70px">${data.justification || ''}</textarea>
                            <div class="mini-note" style="margin-top:6px">
                              Precisas alterar o documento? Em vez de “editar evidência”, o ideal é criar <b>nova versão</b>.
                            </div>
                          </div>
                        </div>

                        <div class="assoc-actions" style="min-width:260px">
                          <button class="btn" type="button" data-propose>Propor alteração no documento</button>
                          <button class="btn ok" type="button" data-apply>Aplicar</button>
                          <button class="btn" type="button" data-cancel>Cancelar</button>
                        </div>
                      </div>
                    `;

    editor.querySelector('[data-propose]').addEventListener('click', () => {
      alert('Mock: abrir fluxo "Upload nova versão" / editor de política (se for documento texto).');
    });

    editor.querySelector('[data-cancel]').addEventListener('click', () => editor.remove());

    editor.querySelector('[data-apply]').addEventListener('click', () => {
      const cov = editor.querySelector('[data-e-coverage]').value;
      const conf = editor.querySelector('[data-e-confidence]').value;
      const st = editor.querySelector('[data-e-state]').value;
      const just = editor.querySelector('[data-e-just]').value;

      // atualiza a UI da linha principal (chips + texto)
      const chips = row.querySelectorAll('.assoc-meta .chip');
      // chips[0] = Cobertura, chips[1] = Confiança, chips[2] = Estado (pela ordem que criamos)
      chips[0].innerHTML = `Cobertura: <b>${cov}</b>`;
      chips[1].innerHTML = `Confiança: <b>${conf}</b>`;

      const stateChip = chips[2];
      stateChip.className = `chip ${classifyStateBadge(st)}`;
      stateChip.innerHTML = `Estado: <b>${st}</b>`;

      row.querySelector('.muted').innerHTML = `Justificação: ${just || '—'}`;

      editor.remove();
    });

    row.appendChild(editor);
  }

  // ===== Nova associação (Auto/Manual) =====
  function addNewAssocInline() {
    const assocList = document.getElementById('assocList');

    const row = document.createElement('div');
    row.className = 'assoc-row';

    row.innerHTML = `
                      <div class="assoc-left">
                        <div class="assoc-meta">
                          <span class="chip">Novo</span>

                          <div class="seg" role="tablist" aria-label="Modo de trecho">
                            <button type="button" class="seg-btn active" data-mode="auto">Trecho do sistema</button>
                            <button type="button" class="seg-btn" data-mode="manual">Trecho manual</button>
                          </div>

                          <div style="min-width:240px">
                            <label class="muted">Controlo</label>
                            <select data-control>
                              <option>ID.GA-1</option>
                              <option>PR.IP-4</option>
                              <option>ID.AR-1</option>
                            </select>
                          </div>

                          <div style="min-width:160px">
                            <label class="muted">Cobertura</label>
                            <select data-coverage>
                              <option>Baixa</option><option>Média</option><option>Alta</option>
                            </select>
                          </div>

                          <div style="min-width:160px">
                            <label class="muted">Confiança</label>
                            <select data-confidence>
                              <option>0.30</option><option>0.61</option><option>0.82</option>
                            </select>
                          </div>
                        </div>

                        <div style="height:10px"></div>

                        <div data-mode-panel="auto">
                          <div class="two">
                            <div class="field">
                              <label>Trecho/Chunk</label>
                              <select data-chunk></select>
                            </div>
                          </div>

                          <div class="field" style="margin-top:8px">
                            <label class="muted">Pré-visualização do trecho</label>
                            <div class="chunk-preview" data-preview>—</div>
                          </div>

                          <div class="field" style="margin-top:8px">
                            <label class="muted">Justificação / racional</label>
                            <textarea data-just placeholder="Por que esta evidência cobre o controlo? O que falta?" style="min-height:70px"></textarea>
                          </div>
                        </div>

                        <div data-mode-panel="manual" class="hide">
                          <div class="field">
                            <label>Trecho manual</label>
                            <textarea data-manual placeholder="Cole aqui o trecho do documento (ou escreva)..." style="min-height:90px"></textarea>
                          </div>

                          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
                            <button class="btn" type="button" data-analyze>Analisar trecho</button>
                            <span class="mini-note">Mock: o sistema sugere controlo, cobertura e confiança.</span>
                          </div>

                          <div class="field" style="margin-top:8px">
                            <label class="muted">Pré-visualização (manual)</label>
                            <div class="chunk-preview" data-manual-preview>—</div>
                          </div>

                          <div class="field" style="margin-top:8px">
                            <label class="muted">Justificação / racional</label>
                            <textarea data-just-manual placeholder="Justificação da associação (podes ajustar)" style="min-height:70px"></textarea>
                          </div>
                        </div>
                      </div>

                      <div class="assoc-actions" style="min-width:260px">
                        <button class="btn ok" type="button" data-add>Adicionar</button>
                        <button class="btn" type="button" data-cancel-inline>Cancelar</button>
                      </div>
                    `;

    // Populate chunks select
    const chunkSelect = row.querySelector('[data-chunk]');
    const preview = row.querySelector('[data-preview]');

    function allChunks() {
      return [...SYSTEM_CHUNKS, ...MANUAL_CHUNKS];
    }

    function fillChunkSelect() {
      chunkSelect.innerHTML = '';
      allChunks().forEach(ch => {
        const opt = document.createElement('option');
        opt.value = ch.id;
        opt.textContent = ch.label;
        chunkSelect.appendChild(opt);
      });
    }

    fillChunkSelect();

    // Default preview + suggested data
    function applyChunkToForm(chunkId) {
      const ch = allChunks().find(x => x.id === chunkId);
      if (!ch) return;
      preview.textContent = `“${ch.full}”`;

      // aplica sugestão (mock)
      row.querySelector('[data-control]').value = ch.suggested.control;
      row.querySelector('[data-coverage]').value = ch.suggested.coverage;
      row.querySelector('[data-confidence]').value = ch.suggested.confidence;
    }

    chunkSelect.addEventListener('change', () => applyChunkToForm(chunkSelect.value));
    applyChunkToForm(chunkSelect.value);

    // Mode switch
    const segBtns = row.querySelectorAll('.seg-btn');
    const panelAuto = row.querySelector('[data-mode-panel="auto"]');
    const panelManual = row.querySelector('[data-mode-panel="manual"]');

    function setMode(mode) {
      segBtns.forEach(b => b.classList.toggle('active', b.dataset.mode === mode));
      panelAuto.classList.toggle('hide', mode !== 'auto');
      panelManual.classList.toggle('hide', mode !== 'manual');
      row.dataset.mode = mode;
    }

    segBtns.forEach(b => b.addEventListener('click', () => setMode(b.dataset.mode)));
    setMode('auto');

    // Manual analyze (mock)
    const manualTa = row.querySelector('[data-manual]');
    const manualPrev = row.querySelector('[data-manual-preview]');
    const analyzeBtn = row.querySelector('[data-analyze]');

    analyzeBtn.addEventListener('click', () => {
      const text = (manualTa.value || '').trim();
      if (!text) {
        alert('Cole um trecho para analisar.');
        return;
      }

      manualPrev.textContent = `“${text}”`;

      // Mock heuristic: se contém "backup" => PR.IP-4; se contém "inventário" => ID.GA-1
      let control = 'ID.AR-1';
      let coverage = 'Baixa';
      let confidence = '0.30';

      const t = text.toLowerCase();
      if (t.includes('backup')) {
        control = 'PR.IP-4';
        coverage = 'Média';
        confidence = '0.61';
      }
      if (t.includes('invent') || t.includes('ativo')) {
        control = 'ID.GA-1';
        coverage = 'Alta';
        confidence = '0.82';
      }

      row.querySelector('[data-control]').value = control;
      row.querySelector('[data-coverage]').value = coverage;
      row.querySelector('[data-confidence]').value = confidence;

      // também preenche justificativa manual (mock)
      row.querySelector('[data-just-manual]').value = 'Sugestão automática baseada em semelhança semântica; rever e ajustar.';
    });

    // Add association
    row.querySelector('[data-add]').addEventListener('click', () => {
      const mode = row.dataset.mode || 'auto';

      const control = row.querySelector('[data-control]').value;
      const coverage = row.querySelector('[data-coverage]').value;
      const confidence = row.querySelector('[data-confidence]').value;

      let justification = '';
      let sourceType = '';
      let sourceChunkId = '';

      if (mode === 'auto') {
        sourceType = 'chunk_sistema';
        sourceChunkId = chunkSelect.value;
        justification = row.querySelector('[data-just]').value || '—';
      } else {
        sourceType = 'chunk_manual';
        const text = (manualTa.value || '').trim();
        if (!text) {
          alert('Trecho manual vazio.');
          return;
        }

        // cria chunk manual e guarda (para aparecer depois)
        const id = 'M' + (MANUAL_CHUNKS.length + 1);
        const newChunk = {
          id,
          label: `Chunk manual #${MANUAL_CHUNKS.length + 1} — ${text.slice(0, 28)}${text.length > 28 ? '…' : ''}`,
          full: text,
          suggested: { control, coverage, confidence }
        };
        MANUAL_CHUNKS.unshift(newChunk);

        sourceChunkId = id;
        justification = row.querySelector('[data-just-manual]').value || '—';
      }

      const assoc = {
        id: 'AX' + Math.floor(Math.random() * 9999),
        control,
        coverage,
        confidence,
        state: 'Pendente',
        justification,
        sourceChunkId,
        sourceType
      };

      assocList.prepend(buildAssocRow(assoc));
      row.remove();
    });

    row.querySelector('[data-cancel-inline]').addEventListener('click', () => row.remove());
    assocList.prepend(row);
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-open-doc-modal]').forEach(btn => {
      btn.addEventListener('click', () => openDocModal(btn));
    });

    // Aprovar (modal) — mantido para docs com modal aberto
    document.getElementById('docApproveBtn')?.addEventListener('click', () => {
      if (!CURRENT_DOC_ID) return;
      approveDoc(CURRENT_DOC_ID);
    });
    document.getElementById("fwD_clear")?.addEventListener("click", clearFrameworkDetails);
    document.getElementById("fwModalClose")?.addEventListener("click", closeFwModal);
    document.getElementById("fwModal")?.addEventListener("click", (e) => {
      if (e.target.id === "fwModal") closeFwModal();
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeFwModal();
    });

    document.getElementById("btnOpenUploadDoc")?.addEventListener("click", () => {
      resetUploadForm();
      openUploadModal();
    });

    // Carregar documentos e frameworks da BD
    loadDocs();
    loadFrameworks();

    // Upload modal binds
    document.getElementById("uploadDocClose")?.addEventListener("click", closeUploadModal);

    document.getElementById("u_type")?.addEventListener("change", syncUploadTypeUI);

    document.getElementById("u_save")?.addEventListener("click", () => saveUpload());
    document.getElementById("uploadDocClose2")?.addEventListener("click", closeUploadModal);

    // clicar fora para fechar
    document.getElementById("uploadDocModal")?.addEventListener("click", (e) => {
      if (e.target?.id === "uploadDocModal") closeUploadModal();
    });

    // ESC fecha upload também (junto do doc modal)
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeUploadModal();
    });


    document.getElementById('docModalClose')?.addEventListener('click', closeDocModal);
    document.getElementById('docModal')?.addEventListener('click', (e) => {
      if (e.target.id === 'docModal') closeDocModal();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeDocModal();
    });

    document.getElementById('addAssocBtn')?.addEventListener('click', addNewAssocInline);

    document.getElementById('saveDocBtn')?.addEventListener('click', () => {
      // Mock: sincroniza status selecionado no modal com a linha da tabela
      const statusSel = document.getElementById('dStatus');
      const newStatus = statusSel ? statusSel.value : null;
      if (CURRENT_DOC_ID && newStatus) setDocRowStatus(CURRENT_DOC_ID, newStatus);

      // se o user mudou para Ativo no select, também esconde o botão aprovar
      const approveBtn = document.getElementById('docApproveBtn');
      if (approveBtn && newStatus) {
        approveBtn.style.display = (newStatus === 'Pendente') ? '' : 'none';
      }

      alert('Mock: alterações guardadas (status + associações + aprovações).');
    });
  });
})();