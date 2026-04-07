// Techbase GRC • NIS2 — Docs page
// Note: sem frameworks JS, tudo vanilla.

(() => {
  // ── CSRF helper ─────────────────────────────────────────────────────────────
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";
  let LAST_AI_TEXT = "";
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

    const btnObsolete = document.getElementById("fwObsoleteBtn");
    const btnUpdate = document.getElementById("fwUpdateVersBtn");
    
    if (btnObsolete) {
        btnObsolete.dataset.fwId = fw.id;
        btnObsolete.style.display = "block";
    }
    if (btnUpdate) {
        btnUpdate.style.display = "block";
    }

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
    m.style.display = "";        // limpa o display:none inline do HTML
    m.classList.add("open");
    m.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeUploadModal() {
    const m = document.getElementById("uploadDocModal");
    if (!m) return;
    m.classList.remove("open");
    m.style.display = "";        // deixa o CSS controlar
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
    const aiBtn = document.getElementById("btnOpenAiInUpload");
    const aiDiv = document.getElementById("u_aiDivider");

    if (block) block.style.display = (type === "framework") ? "block" : "none";
    if (aiBtn) aiBtn.style.display = (type === "framework") ? "none" : "flex";
    if (aiDiv) aiDiv.style.display = (type === "framework") ? "none" : "flex";
  }

  //Adiciona badge de assinatura digital
  function signatureBadge(doc) {
    // Aprovado sem assinatura → não conforme (prioridade máxima)
    if (doc.non_compliant) {
      return '<span class="tag warn" title="Aprovado sem assinatura digital — não conforme">⚠ Não conforme</span>';
    }
    // Aprovado com assinatura → conforme
    if (doc.is_signed && doc.status === 'approved') {
      return '<span class="tag ok" title="Assinado digitalmente">✓ Assinado</span>';
    }
    // Pendente/rejeitado sem assinatura → aviso simples
    if (!doc.is_signed) {
      return '<span class="tag bad" title="Sem assinatura digital">⚠ Não assinado</span>';
    }
    // Pendente com assinatura → info
    return '<span class="tag" title="Assinatura detectada — aguarda aprovação">~ Assinado</span>';
  }

  //Re-upload de documento
  async function reUploadDoc(docId) {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = ".pdf,.docx,.txt,.md";
    input.onchange = async (e) => {
      const file = e.target.files[0];
      if (!file) return;
      const fd = new FormData();
      fd.append("file", file);
      showToast("info", "A fazer upload da nova versão...");
      try {
        const res = await fetch(`/api/documents/${docId}/re-upload`, {
          method: "POST",
          body: fd,
          headers: { "X-CSRF-TOKEN": csrf() },
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.is_signed ? "ok" : "warn",
            data.message || "Nova versão carregada.");
          DOCS = await apiGet("/api/documents");
          renderDocsTable();
        } else {
          showToast("err", data.message || "Erro no upload.");
        }
      } catch (err) {
        showToast("err", "Erro ao fazer upload: " + err.message);
      }
    };
    input.click();
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

      if (data.signature_warning) {
        showFeedback("warn", data.signature_warning);
      }
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

  // Feedback dentro do modal de upload (u_feedback)
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

  // Toast global — para feedback fora do modal de upload (aprovações, re-upload, etc.)
  function showToast(level, msg, durationMs = 4000) {
    let toast = document.getElementById("grc-toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "grc-toast";
      toast.style.cssText = [
        "position:fixed;bottom:24px;right:24px;z-index:99999",
        "padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500",
        "max-width:380px;box-shadow:0 4px 20px rgba(0,0,0,.3)",
        "transition:opacity .3s",
      ].join(";");
      document.body.appendChild(toast);
    }
    const colors = {
      ok: "rgba(52,211,153,.15)",
      warn: "rgba(251,191,36,.15)",
      err: "rgba(248,113,113,.15)",
      success: "rgba(52,211,153,.15)",
      info: "rgba(96,165,250,.12)",
    };
    toast.style.background = colors[level] || colors.info;
    toast.style.border = "1px solid rgba(255,255,255,.1)";
    toast.style.color = "var(--color-text-primary)";
    toast.style.opacity = "1";
    toast.textContent = msg;
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => { toast.style.opacity = "0"; }, durationMs);
  }

  // ── Carregar frameworks da API ────────────────────────────────────────────
  async function loadFrameworks() {
    try {
      const data = await apiGet("/api/documents?type=framework&status=approved");

      const fromApi = data.map(d => ({
        id: "api-" + d.id,
        name: d.title || d.file_name,
        source: d.type || "—",
        version: d.version || "—",
        updated: (d.created_at || "").slice(0, 10),
        status: d.status === "approved" ? "Oficial" : d.status,
        notes: d.origin || "",
      }));

      // CORREÇÃO: Filtramos os frameworks estáticos (ex: F1, F2).
      // Se da API já vier um framework com o MESMO nome (ex: update do utilizador), omitimos o estático!
      const staticFrameworks = FRAMEWORKS.filter(f => {
          if (String(f.id).startsWith("api-")) return false;
          // Verificar se foi "overridden" por um da API
          const hasOverride = fromApi.some(apiFw => apiFw.name.trim().toLowerCase() === f.name.trim().toLowerCase());
          return !hasOverride;
      });

      FRAMEWORKS = [...staticFrameworks, ...fromApi];

    } catch (e) {
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
    if (!DOCS.length) { tbody.innerHTML = '<tr><td colspan="7" class="muted">Sem documentos registados.</td></tr>'; return; }

    tbody.innerHTML = DOCS.map(d => {
      const nm = d.title || d.file_name || "--";
      const sub = (d.file_name ? d.file_name : "")
        + (d.file_size ? " · " + _fmtSize(d.file_size) : "")
        + (d.origin ? " · " + d.origin.slice(0, 50) : "");

      const dl = d.has_file
        ? '<a class="btn small" href="/api/documents/' + d.id + '/download" target="_blank">Download</a>'
        : '<button class="btn small" disabled>Sem ficheiro</button>';

      // Botão Ver sempre presente
      let actionBtns = '<button class="btn small" data-view-doc="' + d.id + '">Ver</button> ' + dl;

      if (d.status === "pending") {
        actionBtns += ' <button class="btn ok small" data-approve-doc="' + d.id + '">Aprovar</button>';
        actionBtns += ' <button class="btn small" style="color:#f87171" data-reject-doc="' + d.id + '">Rejeitar</button>';
      }
      if (d.status === "pending" || d.status === "rejected") {
        actionBtns += ' <button class="btn small" data-reupload-doc="' + d.id + '" title="Carregar nova versão">Nova versão</button>';
      }
      // Apagar — disponível para todos os estados
      actionBtns += ' <button class="btn small" style="color:#f87171;opacity:.8" data-delete-doc="' + d.id + '" title="Eliminar documento">Apagar</button>';

      // Linha clicável — excepto na coluna de acções
      return '<tr data-doc-id="' + d.id + '" data-clickrow="' + d.id + '" style="cursor:pointer">'
        + '<td><b>' + nm + '</b><div class="muted" style="font-size:11px">' + sub + '</div></td>'
        + '<td>' + (d.type || "--") + '</td>'
        + '<td>' + (d.version || "--") + '</td>'
        + '<td data-doc-status-badge>' + _statusBadge(d.status) + '</td>'
        + '<td>' + signatureBadge(d) + '</td>'
        + '<td class="muted">' + ((d.created_at || "--").toString().slice(0, 10)) + '</td>'
        + '<td><div class="actions" onclick="event.stopPropagation()">' + actionBtns + '</div></td></tr>';
    }).join("");

    // Clicar na linha abre o visualizador
    tbody.querySelectorAll("tr[data-clickrow]").forEach(tr => {
      tr.addEventListener("click", (e) => {
        if (e.target.closest(".actions")) return;
        const doc = DOCS.find(x => x.id === Number(tr.dataset.clickrow));
        if (doc) openViewerModal(doc);
      });
      tr.addEventListener("mouseenter", () => tr.style.background = "rgba(255,255,255,.03)");
      tr.addEventListener("mouseleave", () => tr.style.background = "");
    });

    // Botão Ver
    tbody.querySelectorAll("[data-view-doc]").forEach(btn => {
      btn.addEventListener("click", () => {
        const doc = DOCS.find(x => x.id === Number(btn.dataset.viewDoc));
        if (doc) openViewerModal(doc);
      });
    });

    // Aprovar
    tbody.querySelectorAll("[data-approve-doc]").forEach(btn => {
      btn.addEventListener("click", async () => {
        const id = Number(btn.dataset.approveDoc);
        const doc = DOCS.find(x => x.id === id);
        if (doc && !doc.is_signed) {
          const ok = confirm(
            "⚠ Este documento não tem assinatura digital.\n\n" +
            "Se aprovar, ficará marcado como «não conforme» (non_compliant).\n\nContinuar mesmo assim?"
          );
          if (!ok) return;
        }
        btn.disabled = true; btn.textContent = "A aprovar...";
        try {
          const res = await apiPost("/api/documents/" + id + "/approve", { force: true });
          if (doc) doc.status = "approved";
          if (doc && res.non_compliant) doc.non_compliant = true;
          renderDocsTable();
          if (res.non_compliant) showToast("warn", "Aprovado mas marcado como não conforme (sem assinatura digital).");
          else if (res.ingest === "queued") showToast("ok", "Aprovado. A indexar no Pinecone...");
          else showToast("ok", res.message || "Documento aprovado.");
        } catch (e) {
          showToast("err", "Erro ao aprovar: " + e.message);
          btn.disabled = false; btn.textContent = "Aprovar";
        }
      });
    });

    // Rejeitar
    tbody.querySelectorAll("[data-reject-doc]").forEach(btn => {
      btn.addEventListener("click", async () => {
        const id = Number(btn.dataset.rejectDoc);
        const reason = prompt("Motivo da rejeição (opcional):");
        if (reason === null) return;
        try {
          await apiPost("/api/documents/" + id + "/reject", { reason: reason || null });
          const d = DOCS.find(x => x.id === id);
          if (d) d.status = "rejected";
          renderDocsTable();
          showToast("ok", "Documento rejeitado.");
        } catch (e) { showToast("err", "Erro ao rejeitar: " + e.message); }
      });
    });

    // Nova versão
    tbody.querySelectorAll("[data-reupload-doc]").forEach(btn => {
      btn.addEventListener("click", () => reUploadDoc(Number(btn.dataset.reuploadDoc)));
    });

    // Apagar documento (soft delete)
    tbody.querySelectorAll("[data-delete-doc]").forEach(btn => {
      btn.addEventListener("click", async () => {
        const id = Number(btn.dataset.deleteDoc);
        const doc = DOCS.find(x => x.id === id);
        const nm = doc?.title || doc?.file_name || `Documento #${id}`;
        const isApproved = doc?.status === "approved";
        const msg = isApproved
          ? `⚠ "${nm}" está APROVADO.\n\nTem a certeza que quer eliminar um documento aprovado?\nEsta acção não pode ser desfeita.`
          : `Eliminar "${nm}"?\n\nEsta acção não pode ser desfeita.`;
        if (!confirm(msg)) return;
        try {
          await apiPost(`/api/documents/${id}/delete`);
          DOCS = DOCS.filter(x => x.id !== id);
          renderDocsTable();
          showToast("ok", "Documento eliminado.");
        } catch (e) {
          showToast("err", "Erro ao eliminar: " + e.message);
        }
      });
    });
  }

  // ── Visualizador de documento ────────────────────────────────────────────────
  function openViewerModal(doc) {
    const m = document.getElementById("docViewerModal");
    if (!m) return;

    // Cabeçalho
    document.getElementById("vwTitle").textContent = doc.title || doc.file_name || "—";
    document.getElementById("vwType").textContent = doc.type || "—";
    document.getElementById("vwVersion").textContent = doc.version || "—";
    document.getElementById("vwStatus").innerHTML = _statusBadge(doc.status);
    document.getElementById("vwSig").innerHTML = signatureBadge(doc);
    document.getElementById("vwDate").textContent = (doc.created_at || "—").toString().slice(0, 10);
    document.getElementById("vwUploader").textContent = doc.uploader || "—";

    // SHA256
    const sha = doc.sha256 || doc.attach_sha256 || null;
    const shaEl = document.getElementById("vwSha");
    if (shaEl) shaEl.textContent = sha ? sha.substring(0, 16) + "…" : "—";

    // Motivo de rejeição
    const rejEl = document.getElementById("vwRejection");
    if (rejEl) {
      rejEl.style.display = doc.rejection_reason ? "block" : "none";
      if (doc.rejection_reason) rejEl.textContent = "Motivo da rejeição: " + doc.rejection_reason;
    }

    // Link de download
    const dlBtn = document.getElementById("vwDownload");
    if (dlBtn) {
      if (doc.has_file) {
        dlBtn.href = "/api/documents/" + doc.id + "/download";
        dlBtn.style.display = "";
      } else {
        dlBtn.style.display = "none";
      }
    }

    // Botão de aprovação — só pendentes
    const apBtn = document.getElementById("vwApprove");
    if (apBtn) {
      apBtn.style.display = doc.status === "pending" ? "" : "none";
      apBtn.onclick = () => {
        closeViewerModal();
        // Dispara clique no botão da tabela para reutilizar a lógica com confirmação
        const tableBtn = document.querySelector('[data-approve-doc="' + doc.id + '"]');
        if (tableBtn) tableBtn.click();
      };
    }

    // Botão IA
    const aiBtn = document.getElementById("vwAiAssist");
    if (aiBtn) {
      aiBtn.onclick = () => openAiAssistModal(doc);
    }

    // Preview do ficheiro
    const area = document.getElementById("vwPreviewArea");
    const noFile = document.getElementById("vwNoFile");
    const docMsg = document.getElementById("vwDocxMsg");

    if (area) { area.style.display = "none"; area.innerHTML = ""; }
    if (noFile) noFile.style.display = "none";
    if (docMsg) docMsg.style.display = "none";

    // Botão de nova versão no viewer — sempre visível
    const reupBtn = document.getElementById("vwReupload");
    if (reupBtn) {
      reupBtn.style.display = "";
      reupBtn.onclick = () => { closeViewerModal(); reUploadDoc(doc.id); };
    }

    // ── Painel de sugestões RF16 ─────────────────────────────────────────────
    const sugPanel = document.getElementById("vwSuggestionsPanel");
    const sugBody = document.getElementById("vwSuggestionsBody");
    const sugLoading = document.getElementById("vwSuggestionsLoading");
    const sugMeta = document.getElementById("vwSuggestionsMeta");
    const reanalyse = document.getElementById("vwReanalyse");

    const analysableMimes = ["application/pdf", "text/plain",
      "application/vnd.openxmlformats-officedocument.wordprocessingml.document"];
    const canAnalyse = doc.has_file && (
      analysableMimes.includes((doc.mime_type || "").toLowerCase()) ||
      /\.(pdf|txt|md|docx)$/i.test(doc.file_name || "")
    );

    if (sugPanel) sugPanel.style.display = canAnalyse ? "block" : "none";
    if (sugBody) sugBody.innerHTML = "";
    if (sugMeta) sugMeta.style.display = "none";

    if (canAnalyse) {
      // Trigger análise automática
      triggerAnalysis(doc.id, sugBody, sugLoading, sugMeta);

      // Botão re-analisar
      if (reanalyse) {
        reanalyse.onclick = () => triggerAnalysis(doc.id, sugBody, sugLoading, sugMeta);
      }
    }

    if (doc.has_file) {
      const mime = (doc.mime_type || "").toLowerCase();
      const name = (doc.file_name || "").toLowerCase();
      const urlPrev = "/api/documents/" + doc.id + "/preview";   // inline — para o viewer
      const urlDl = "/api/documents/" + doc.id + "/download";  // attachment — para download

      if (mime === "application/pdf" || name.endsWith(".pdf")) {
        if (area) {
          area.style.display = "flex";
          area.style.flexDirection = "column";
          area.innerHTML =
            '<object data="' + urlPrev + '" type="application/pdf" style="flex:1;width:100%;min-height:500px;border:none">' +
            '<div style="padding:32px;text-align:center;color:var(--muted)">' +
            '<div style="font-size:32px;margin-bottom:12px">📄</div>' +
            '<p style="font-size:14px;margin-bottom:12px">O teu browser não suporta pré-visualização de PDF inline.</p>' +
            '<a href="' + urlDl + '" target="_blank" class="btn ok" style="text-decoration:none">Abrir PDF numa nova aba</a>' +
            '</div>' +
            '</object>';
        }
      } else if (mime.startsWith("image/") || name.match(/\.(png|jpg|jpeg|webp|gif)$/)) {
        if (area) {
          area.style.display = "flex";
          area.style.alignItems = "center";
          area.style.justifyContent = "center";
          area.style.padding = "20px";
          area.innerHTML = '<img src="' + urlPrev + '" style="max-width:100%;max-height:560px;border-radius:10px;object-fit:contain" />';
        }
      } else if (mime.includes("wordprocessingml") || name.endsWith(".docx")) {
        if (docMsg) docMsg.style.display = "block";
        const dlDocx = document.getElementById("vwDocxDownload");
        if (dlDocx) dlDocx.href = urlDl;
      } else if (mime === "text/plain" || name.endsWith(".txt") || name.endsWith(".md")) {
        if (area) {
          area.style.display = "block";
          area.style.overflow = "auto";
          area.innerHTML = '<div style="padding:16px;color:var(--muted);font-size:13px">A carregar...</div>';
          fetch(urlPrev)
            .then(r => r.text())
            .then(txt => {
              area.innerHTML = '<pre style="white-space:pre-wrap;font-size:13px;line-height:1.6;padding:20px;margin:0;font-family:inherit">'
                + txt.replace(/&/g, "&amp;").replace(/</g, "&lt;") + '</pre>';
            })
            .catch(() => { area.innerHTML = '<p style="padding:20px;color:var(--muted)">Erro ao carregar texto.</p>'; });
        }
      } else {
        if (noFile) noFile.style.display = "block";
      }
    } else {
      if (noFile) noFile.style.display = "block";
    }

    m.style.display = "";
    m.classList.add("open");
    m.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeViewerModal() {
    const m = document.getElementById("docViewerModal");
    if (!m) return;
    // Limpar preview (para parar PDFs a carregar em background)
    const area = document.getElementById("vwPreviewArea");
    if (area) area.innerHTML = "";
    m.classList.remove("open");
    m.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  }

  // ── Assistente IA (placeholder — ponto 2) ───────────────────────────────────
  function openAiAssistModal(doc) {
    const m = document.getElementById("aiAssistModal");
    if (!m) return;

    const docTitle = doc ? (doc.title || doc.file_name || "documento") : "novo documento";
    document.getElementById("aiDocTitle").textContent = docTitle;

    const out = document.getElementById("aiOutput");

    // se já existia texto da IA, restaurar
    if (LAST_AI_TEXT && out) {
      out.value = LAST_AI_TEXT;
    }

    m.style.display = "";
    m.classList.add("open");
    m.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closeAiAssistModal() {
    const m = document.getElementById("aiAssistModal");
    if (!m) return;
    m.classList.remove("open");
    m.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  }

  // Converte Markdown para texto limpo para textarea editável
  function mdToPlainText(text) {
    return text
      .replace(/^### (.+)$/gm, "\n$1\n")
      .replace(/^## (.+)$/gm, "\n$1\n")
      .replace(/^# (.+)$/gm, "\n$1\n")
      .replace(/\*\*\*(.+?)\*\*\*/g, "$1")
      .replace(/\*\*(.+?)\*\*/g, "$1")
      .replace(/\*(.+?)\*/g, "$1")
      .replace(/^[\*\-] (.+)$/gm, "• $1")
      .replace(/^(\d+)\. (.+)$/gm, "$1. $2")
      .replace(/`([^`]+)`/g, "$1")
      .replace(/^---$/gm, "")
      .replace(/\n{3,}/g, "\n\n")
      .trim();
  }

  async function runAiAssist() {
    const prompt = document.getElementById("aiPromptInput")?.value?.trim();
    const out = document.getElementById("aiOutput");
    if (!prompt || !out) return;

    out.value = "A gerar documento...";
    out.disabled = true;
    document.getElementById("aiRunBtn").disabled = true;

    // Detectar tipo de documento a partir das sugestões rápidas seleccionadas
    // (ou deixar 'custom' se foi instrução livre)
    const docType = document.getElementById("aiDocType")?.value || "custom";

    try {
      const res = await fetch("/api/document-generator/generate", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrf(),
          "Accept": "application/json",
        },
        body: JSON.stringify({
          instruction: prompt,
          doc_type: docType,
        }),
      });
      const data = await res.json();

      if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);

      const text = data.content || data.answer || "";
      const clean = mdToPlainText(text);
      out.value = clean;
      LAST_AI_TEXT = clean;
      out.disabled = false;
      out.focus();
      out.setSelectionRange(0, 0);
      out.scrollTop = 0;

      // Mostrar controlos usados como referência (se disponíveis)
      const ctrlEl = document.getElementById("aiControlsUsed");
      if (ctrlEl && data.controls?.length) {
        ctrlEl.textContent = "Controlos: " + data.controls.join(", ");
        ctrlEl.style.display = "block";
      } else if (ctrlEl) {
        ctrlEl.style.display = "none";
      }

    } catch (e) {
      out.value = "Erro: " + e.message;
      out.disabled = false;
    } finally {
      document.getElementById("aiRunBtn").disabled = false;
    }
  }

  // Chunks do sistema (vindos do Pinecone / RAG) — preenchidos por loadDocChunks()
  let SYSTEM_CHUNKS = []; // {id, label, full, suggested}

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

  async function approveDoc(docId) {
    const doc = DOCS.find(x => x.id === docId);

    if (doc && !doc.is_signed) {
      const ok = confirm(
        "⚠ Este documento não tem assinatura digital.\n\n" +
        "Se aprovar, ficará marcado como «não conforme».\n\nContinuar?"
      );
      if (!ok) return;
    }

    try {
      const res = await apiPost("/api/documents/" + docId + "/approve", { force: true });
      if (doc) {
        doc.status = "approved";
        if (res.non_compliant) doc.non_compliant = true;
      }
      setDocRowStatus(docId, "Aprovado");
      if (CURRENT_DOC_ID === docId) {
        const statusSel = document.getElementById("dStatus");
        if (statusSel) statusSel.value = "Ativo";
        const approveBtn = document.getElementById("docApproveBtn");
        if (approveBtn) approveBtn.style.display = "none";
      }
      showToast(res.non_compliant ? "warn" : "ok",
        res.message || "Documento aprovado.");
    } catch (e) {
      showToast("err", "Erro ao aprovar: " + e.message);
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

    // Ambos os botões de upload abrem o mesmo modal
    const openUploadHandler = () => { resetUploadForm(); openUploadModal(); };
    document.getElementById("btnOpenUploadDoc")?.addEventListener("click", openUploadHandler);
    document.getElementById("btnOpenUploadDoc2")?.addEventListener("click", openUploadHandler);

    // Botão "Escrever com IA" dentro do modal de upload
    document.getElementById("btnOpenAiInUpload")?.addEventListener("click", () => {
      const name = document.getElementById("u_name")?.value?.trim() || "novo documento";
      document.getElementById("aiDocTitle").textContent = name;
      openAiAssistModal(null);
    });

    // Limpar IA
    document.getElementById("aiClearBtn")?.addEventListener("click", () => {
      const out = document.getElementById("aiOutput");
      if (out) out.value = "";
      document.getElementById("aiPromptInput").value = "";
      const ctrlEl = document.getElementById("aiControlsUsed");
      if (ctrlEl) ctrlEl.style.display = "none";
    });

    // Copiar texto da textarea (já editado)
    document.getElementById("aiCopyBtn")?.addEventListener("click", () => {
      const out = document.getElementById("aiOutput");
      const txt = out?.value?.trim() || "";
      if (!txt) { alert("Gera primeiro um texto com a IA."); return; }
      navigator.clipboard.writeText(txt).then(() => showToast("ok", "Texto copiado."));
    });

    // "Usar no upload" — usa o valor actual da textarea (já pode estar editado)
    document.getElementById("aiSaveAsDoc")?.addEventListener("click", async () => {

      const out = document.getElementById("aiOutput");
      const text = out?.value?.trim() || "";

      if (!text) {
        alert("Gera primeiro um texto com a IA.");
        return;
      }

      const name = document.getElementById("u_name")?.value?.trim() || "documento-ia";
      const type = document.getElementById("aiFileType")?.value || "txt";

      const clean = name.replace(/[^a-z0-9]/gi, "_").toLowerCase();
      const fileName = clean + "." + type;

      let file;

      // TXT
      if (type === "txt") {
        file = new File([text], fileName, { type: "text/plain" });
      }

      // MARKDOWN
      if (type === "md") {
        file = new File([text], fileName, { type: "text/markdown" });
      }

      // DOCX (simples — Word abre como texto)
      if (type === "docx") {
        file = new File([text], fileName, {
          type: "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        });
      }

      // PDF
      if (type === "pdf") {

        const { jsPDF } = window.jspdf;

        const pdf = new jsPDF({
          unit: "pt",
          format: "a4"
        });

        const margin = 60;
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();

        const contentWidth = pageWidth - margin * 2;

        pdf.setFont("Times", "Normal");
        pdf.setFontSize(12);

        const lines = pdf.splitTextToSize(text, contentWidth);

        let y = 120;
        let page = 1;

        function drawHeader() {

          pdf.setFont("Times", "Bold");
          pdf.setFontSize(16);

          pdf.text("Política de Segurança da Informação", margin, 60);

          pdf.setFontSize(10);
          pdf.setFont("Times", "Normal");

          pdf.text("Sistema Techbase GRC", margin, 80);

          const today = new Date().toLocaleDateString();

          pdf.text("Gerado em: " + today, pageWidth - margin, 80, { align: "right" });

          pdf.setDrawColor(200);
          pdf.line(margin, 90, pageWidth - margin, 90);

        }

        function drawFooter() {

          pdf.setDrawColor(200);
          pdf.line(margin, pageHeight - 50, pageWidth - margin, pageHeight - 50);

          pdf.setFontSize(10);

          pdf.text(
            "Página " + page,
            pageWidth / 2,
            pageHeight - 30,
            { align: "center" }
          );

        }

        drawHeader();

        lines.forEach(line => {

          if (y > pageHeight - 80) {

            drawFooter();

            pdf.addPage();
            page++;

            drawHeader();
            y = 120;

          }

          pdf.text(line, margin, y);
          y += 16;

        });

        drawFooter();

        const blob = pdf.output("blob");

        file = new File([blob], fileName, { type: "application/pdf" });

      }

      const dt = new DataTransfer();
      dt.items.add(file);

      const fileInput = document.getElementById("u_file");
      if (fileInput) fileInput.files = dt.files;

      closeAiAssistModal();

      showFeedback("ok", "Documento gerado. Revê e clica em 'Fazer upload'.");
    });

    document.getElementById("aiDownload")?.addEventListener("click", () => {

      const text = document.getElementById("aiOutput").value;

      const blob = new Blob([text], { type: "text/plain" });
      const url = URL.createObjectURL(blob);

      const a = document.createElement("a");
      a.href = url;
      a.download = "documento.txt";
      a.click();

    });

    // Carregar documentos e frameworks da BD
    loadDocs();
    loadFrameworks();

    // Upload modal binds
    document.getElementById("uploadDocClose")?.addEventListener("click", closeUploadModal);
    
    document.getElementById("fwUpdateVersBtn")?.addEventListener("click", () => {
        document.getElementById("fwUpdateFileInput")?.click();
    });

    document.getElementById("fwUpdateFileInput")?.addEventListener("change", async (e) => {
        if (!e.target.files.length) return;
        const file = e.target.files[0];
        const fwIdStr = document.getElementById("fwObsoleteBtn").dataset.fwId;
        const isStatic = !fwIdStr.startsWith('api-');
        
        let versionTitle = document.getElementById("fwModalTitle").textContent;
        const version = prompt(`Qual a nova versão de ${versionTitle}? (deixa em branco para incrementar automaticamente)`, "");
        if (version === null) {
            e.target.value = "";
            return;
        }

        const fd = new FormData();
        fd.append("file", file);
        if (version.trim()) fd.append("version", version.trim());
        
        // Se for um framework estático, em vez de re-upload, criamos um novo a partir deste
        let url = `/api/documents/${fwIdStr.replace('api-', '')}/re-upload`;
        if (isStatic) {
            url = `/api/documents/upload`;
            fd.append("title", versionTitle);
            fd.append("type", "framework");
        }
        
        const btn = document.getElementById("fwUpdateVersBtn");
        btn.disabled = true;
        btn.textContent = "A carregar...";

        try {
            const res = await apiPost(url, fd);
            showToast("ok", res.message || "Nova versão carregada com sucesso.");
            closeFwModal();
            loadFrameworks();
        } catch (err) {
            showToast("err", "Erro: " + err.message);
        } finally {
            btn.disabled = false;
            btn.textContent = "Atualizar versão";
            e.target.value = "";
        }
    });

    document.getElementById("fwObsoleteBtn")?.addEventListener("click", async (e) => {
        const btn = e.currentTarget;
        const fwIdStr = btn.dataset.fwId;
        if (!fwIdStr || !fwIdStr.startsWith('api-')) return showToast("warn", "Apenas frameworks registados podem ser marcados como obsoletos.");
        
        const realId = fwIdStr.replace('api-', '');
        if (!confirm("Tem a certeza que quer marcar esta norma como OBSOLETA?\nEla desaparecerá da lista e deixará de ser usada pela IA (Pinecone).")) return;
        
        btn.disabled = true;
        btn.textContent = "A processar...";
        try {
            const res = await apiPost(`/api/documents/${realId}/obsolete`);
            showToast("ok", res.message || "Marcado como obsoleto.");
            closeFwModal();
            loadFrameworks();
        } catch (err) {
            showToast("err", "Erro: " + err.message);
        } finally {
            btn.disabled = false;
            btn.textContent = "Marcar Obsoleto";
        }
    });

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
      if (e.key === 'Escape') {
        // Fechar em cascata: AI → viewer → doc modal antigo
        const ai = document.getElementById("aiAssistModal");
        if (ai?.classList.contains("open")) { closeAiAssistModal(); return; }
        const vw = document.getElementById("docViewerModal");
        if (vw?.classList.contains("open")) { closeViewerModal(); return; }
        closeDocModal();
      }
    });

    // Visualizador de documento
    document.getElementById("vwClose")?.addEventListener("click", closeViewerModal);
    document.getElementById("docViewerModal")?.addEventListener("click", (e) => {
      if (e.target.id === "docViewerModal") closeViewerModal();
    });

    // Assistente IA
    document.getElementById("aiClose")?.addEventListener("click", closeAiAssistModal);
    document.getElementById("aiAssistModal")?.addEventListener("click", (e) => {
      if (e.target.id === "aiAssistModal") closeAiAssistModal();
    });
    document.getElementById("aiRunBtn")?.addEventListener("click", runAiAssist);
    // Ctrl+Enter no textarea também dispara
    document.getElementById("aiPromptInput")?.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) runAiAssist();
    });

    document.getElementById('addAssocBtn')?.addEventListener('click', addNewAssocInline);

    document.getElementById('saveDocBtn')?.addEventListener('click', () => {
      const statusSel = document.getElementById('dStatus');
      const newStatus = statusSel ? statusSel.value : null;
      if (CURRENT_DOC_ID && newStatus) setDocRowStatus(CURRENT_DOC_ID, newStatus);
      const approveBtn = document.getElementById('docApproveBtn');
      if (approveBtn && newStatus) {
        approveBtn.style.display = (newStatus === 'Pendente') ? '' : 'none';
      }
      alert('Mock: alterações guardadas (status + associações + aprovações).');
    });
  });

  // Exposta globalmente — usada pelos botões de sugestão rápida no blade
  window.setAiTemplate = function (docType, promptText) {
    const sel = document.getElementById("aiDocType");
    if (sel) sel.value = docType;
    const inp = document.getElementById("aiPromptInput");
    if (inp) inp.value = promptText;
    // Limpar output anterior
    const out = document.getElementById("aiOutput");
    if (out) out.value = "";
    const ctrlEl = document.getElementById("aiControlsUsed");
    if (ctrlEl) ctrlEl.style.display = "none";
  };

  // ── Sugestões automáticas de controlos (RF16) ──────────────────────────────

  function triggerAnalysis(docId, container, loadingEl, metaEl) {
    if (!container) return;
    if (loadingEl) loadingEl.style.display = "block";
    container.innerHTML = "";
    loadDocumentSuggestions(docId, container, loadingEl, metaEl);
  }

  async function loadDocumentSuggestions(docId, container, loadingEl, metaEl) {
    try {
      const res = await fetch(`/api/documents/${docId}/analyse`, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": csrf(), "Accept": "application/json" },
      });
      const data = await res.json();
      if (loadingEl) loadingEl.style.display = "none";
      if (!res.ok || !data.success) {
        container.innerHTML = `<div class="muted" style="font-size:12px;padding:8px 0">${data.message || "Sem sugestões disponíveis."}</div>`;
        return;
      }
      renderSuggestions(data.suggestions || [], container);
      if (metaEl && data.meta) {
        const mt = data.meta;
        metaEl.style.display = "block";
        metaEl.textContent = `${mt.chunks_sent || 0} chunks · ${mt.total_hits || 0} hits · ${(mt.text_length || 0).toLocaleString()} chars` + (mt.error ? ` · ⚠ ${mt.error}` : "");
      }
    } catch (e) {
      if (loadingEl) loadingEl.style.display = "none";
      container.innerHTML = `<div class="muted" style="font-size:12px;padding:8px 0">Erro ao analisar: ${e.message}</div>`;
    }
  }

  function renderSuggestions(suggestions, container) {
    if (!suggestions.length) {
      container.innerHTML = '<div class="muted" style="font-size:12px;padding:8px 0">Nenhum controlo identificado com confiança suficiente.</div>';
      return;
    }
    const cvg = { high: { cls: "ok", label: "Alta" }, medium: { cls: "warn", label: "Média" }, low: { cls: "", label: "Baixa" } };
    container.innerHTML = `<table style="width:100%;border-collapse:collapse;font-size:13px">
      <thead><tr>
        <th style="text-align:left;padding:0 8px 8px 0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);border-bottom:1px solid var(--border)">Controlo</th>
        <th style="text-align:left;padding:0 8px 8px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);border-bottom:1px solid var(--border)">Score</th>
        <th style="text-align:left;padding:0 0 8px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);border-bottom:1px solid var(--border)">Justificação</th>
      </tr></thead>
      <tbody>${suggestions.map(s => {
      const c = cvg[s.coverage] || cvg.low;
      const fw = s.framework ? `<span style="font-size:10px;color:var(--muted);margin-left:4px">${s.framework}</span>` : "";
      const just = s.justification || (s.top_snippet ? s.top_snippet.substring(0, 100) + "…" : "—");
      return `<tr>
          <td style="padding:10px 8px 10px 0;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:top"><b>${s.control_code}</b>${fw}<div style="font-size:11px;color:var(--muted)">${s.control_family || ""}</div></td>
          <td style="padding:10px 8px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:top;white-space:nowrap"><span class="tag ${c.cls}" style="font-size:11px"><span class="s"></span>${s.score.toFixed(2)}</span><div style="font-size:10px;color:var(--muted);margin-top:3px">${c.label}</div></td>
          <td style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:top;color:var(--muted);font-size:12px;line-height:1.4">${just}</td>
        </tr>`;
    }).join("")}</tbody></table>`;
  }
})();
