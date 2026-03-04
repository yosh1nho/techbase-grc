// Techbase GRC • NIS2 — Docs page (mock)
// Note: sem frameworks JS, tudo vanilla.

(() => {
  // ===== Mock chunks base (sistema) =====
  const SYSTEM_CHUNKS = [
    {
      id: 'C1',
      label: 'Chunk #01 — Inventário mensal…',
      full: 'Inventário deve ser atualizado mensalmente, contendo responsáveis, criticidade, localização e evidências do processo de revisão.',
      suggested: { control: 'ID.GA-1', coverage: 'Alta', confidence: '0.82' }
    },
    {
      id: 'C2',
      label: 'Chunk #02 — Backups sem testes…',
      full: 'Backups existem e são executados com frequência, porém não há evidência de testes periódicos de restauração nem relatórios de validação.',
      suggested: { control: 'PR.IP-4', coverage: 'Média', confidence: '0.61' }
    },
  ];

  // ===== Mock docs (documentos oficiais) =====
  let DOCS = [
    { id: "D1", name: "Procedimento Inventário v1.0", type: "procedure", version: "v1.0", updated: "2026-02-10", status: "Ativo" },
    { id: "D2", name: "Política de Backups v0.9", type: "policy", version: "v0.9", updated: "2026-02-12", status: "Draft" },
  ];


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
    document.getElementById("u_name").value = "";
    document.getElementById("u_type").value = "evidence";
    document.getElementById("u_version").value = "";
    document.getElementById("u_updated").value = "";
    document.getElementById("u_source").value = "CNCS";
    document.getElementById("u_fwNotes").value = "";
    syncUploadTypeUI();
  }

  function syncUploadTypeUI() {
    const type = document.getElementById("u_type").value;
    const block = document.getElementById("u_frameworkBlock");
    if (!block) return;
    block.style.display = (type === "framework") ? "block" : "none";
  }

  function saveUpload() {
    const name = (document.getElementById("u_name").value || "").trim();
    const type = document.getElementById("u_type").value;
    const version = (document.getElementById("u_version").value || "").trim();
    const updated = (document.getElementById("u_updated").value || "").trim();

    if (!name) return alert("Nome é obrigatório.");

    if (type === "framework") {
      const source = document.getElementById("u_source").value;
      const notes = (document.getElementById("u_fwNotes").value || "").trim();

      FRAMEWORKS.unshift({
        id: "F" + (Math.floor(Math.random() * 9000) + 1000),
        name,
        source,
        version: version || "—",
        updated: updated || "—",
        status: "Oficial",
        notes
      });

      renderFrameworks();
      closeUploadModal();
      return;
    }

    // docs normais
    DOCS.unshift({
      id: "D" + (Math.floor(Math.random() * 9000) + 1000),
      name,
      type,
      version: version || "—",
      updated: updated || "—",
      status: "Ativo"
    });

    // aqui tu chama teu render de “Documentos no sistema”
    if (typeof renderDocsTable === "function") renderDocsTable();

    closeUploadModal();
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

    // Aprovar (tabela) — delegation para funcionar mesmo se a tabela crescer
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-approve-doc]');
      if (!btn) return;
      const docId = btn.dataset.docId;
      if (!docId) return;
      approveDoc(docId);
    });

    // Aprovar (modal)
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

    // Render inicial do painel de frameworks (RF4)
    renderFrameworks();

    // Upload modal binds
    document.getElementById("uploadDocClose")?.addEventListener("click", closeUploadModal);

    document.getElementById("u_type")?.addEventListener("change", syncUploadTypeUI);

    document.getElementById("u_save")?.addEventListener("click", () => {
      saveUpload();
    });

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
