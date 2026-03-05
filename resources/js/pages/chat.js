// Techbase GRC • NIS2 — Chat (mock RAG)
// Vanilla JS, sem frameworks.

(() => {
    // ====== Mock docs + chunks (ajusta fileUrl pro teu public/mock) ======
    const DOCS = [
        {
            id: "F1",
            title: "QNRCS 2019 — Quadro Nacional de Referência para a Cibersegurança",
            kind: "framework",
            fileUrl: "/mock/frameworks/cncs-qnrcs-2019.pdf",
            chunks: [
                { id: "F1-C1", label: "QNRCS • Gestão de Ativos", text: "A organização deve manter inventário atualizado de ativos, incluindo dono, criticidade, localização e evidências de revisão periódica." },
                { id: "F1-C2", label: "QNRCS • Análise de Risco", text: "A organização deve executar análise de risco formal com periodicidade definida, com aprovação e evidência documental." }
            ]
        },
        {
            id: "F2",
            title: "NIS2 — Diretiva (UE) 2022/2555",
            kind: "framework",
            fileUrl: "/mock/frameworks/NIS2.pdf",
            chunks: [
                { id: "F2-C1", label: "NIS2 • Gestão de incidentes", text: "Exige capacidades de deteção, resposta e reporte de incidentes relevantes às autoridades competentes, conforme prazos e conteúdo definido." },
                { id: "F2-C2", label: "NIS2 • Medidas de gestão de risco", text: "Medidas técnicas e organizacionais para gerir riscos: políticas, continuidade, backup, controlo de acesso, etc." }
            ]
        },
        {
            id: "D1",
            title: "Procedimento Inventário v1.0",
            kind: "procedure",
            fileUrl: "/mock/docs/procedimento-inventario-v1.pdf", // cria/ajusta conforme teus ficheiros
            chunks: [
                { id: "D1-C1", label: "Inventário mensal", text: "Inventário deve ser atualizado mensalmente, contendo responsáveis, criticidade e evidências do processo de revisão." },
                { id: "D1-C2", label: "Evidência e revisão", text: "Cada revisão do inventário deve gerar evidência (export/relatório) e registo do responsável." }
            ]
        },
        {
            id: "D2",
            title: "Política de Backups v0.9",
            kind: "policy",
            fileUrl: "/mock/docs/politica-backups-v0-9.pdf", // cria/ajusta conforme teus ficheiros
            chunks: [
                { id: "D2-C1", label: "Frequência e retenção", text: "Backups devem seguir frequência definida e retenção mínima, conforme criticidade do sistema." },
                { id: "D2-C2", label: "Testes de restore", text: "Devem ser realizados testes periódicos de restauração, com registo de evidências e resultados." }
            ]
        }
    ];

    // ====== Helpers DOM ======
    const $ = (sel) => document.querySelector(sel);

    function openModal(id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.add("open");
        m.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.remove("open");
        m.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
    }

    function nowLabel() {
        return "agora";
    }

    //===== Helpers RAG ======
    function escapeHtml(str) {
        return (str || "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    // Extrai tabelas de blocos ```md``` e converte para markdown puro
    function unwrapTableCodeFences(md) {
        return (md || "").replace(/```(?:md|markdown)\n([\s\S]*?)```/g, (match, inner) => {
            const trimmed = inner.trim();
            if (trimmed.startsWith("|")) {
                return "\n\n" + trimmed + "\n\n";
            }
            return match;
        });
    }

    function renderBotMarkdown(text) {
        if (!window.marked || typeof window.marked.parse !== "function") {
            return `<pre style="white-space:pre-wrap;margin:0;">${escapeHtml(text)}</pre>`;
        }

        try {
            // Extrai tabelas de blocos ```md```
            const processed = unwrapTableCodeFences(text || "");
            const html = window.marked.parse(processed);
            if (window.DOMPurify) return window.DOMPurify.sanitize(html);
            return html;
        } catch (err) {
            console.error("Markdown render error:", err);
            return `<pre style="white-space:pre-wrap;margin:0;">${escapeHtml(text || "")}</pre>`;
        }
    }

    // ====== RAG mock: escolhe fontes por keywords ======
    function pickSources(question) {
        const q = (question || "").toLowerCase();
        const sources = [];

        // heurística simples (mock)
        const wantsRisk = q.includes("risco") || q.includes("ar-") || q.includes("análise");
        const wantsInventory = q.includes("invent") || q.includes("ativo") || q.includes("ga-");
        const wantsBackup = q.includes("backup") || q.includes("restore");
        const wantsIncident = q.includes("incidente") || q.includes("notificar") || q.includes("cncs") || q.includes("nis2");

        if (wantsInventory) {
            sources.push({
                docId: "F1",
                chunkIds: ["F1-C1"]
            });
            sources.push({
                docId: "D1",
                chunkIds: ["D1-C1", "D1-C2"]
            });
        }

        if (wantsRisk) {
            sources.push({
                docId: "F1",
                chunkIds: ["F1-C2"]
            });
        }

        if (wantsBackup) {
            sources.push({
                docId: "D2",
                chunkIds: ["D2-C2"]
            });
            sources.push({
                docId: "F1",
                chunkIds: ["F1-C2"]
            });
        }

        if (wantsIncident) {
            sources.push({
                docId: "F2",
                chunkIds: ["F2-C1"]
            });
        }

        // fallback: se nada bater, usa QNRCS geral
        if (!sources.length) {
            sources.push({ docId: "F1", chunkIds: ["F1-C2"] });
        }

        // merge por docId
        const merged = new Map();
        for (const s of sources) {
            const prev = merged.get(s.docId) || new Set();
            s.chunkIds.forEach(id => prev.add(id));
            merged.set(s.docId, prev);
        }

        return Array.from(merged.entries()).map(([docId, chunkSet]) => ({
            docId,
            chunkIds: Array.from(chunkSet)
        }));
    }

    function buildAnswer(question, sources) {
        const q = (question || "").toLowerCase();

        // texto mock baseado no tema
        if (q.includes("id.ar-1") || q.includes("análise de risco")) {
            return `Para cumprir o controlo relacionado com análise de risco (ex.: ID.AR-1), precisas de:
• evidência de uma análise formal recente (relatório)
• critérios de probabilidade × impacto e classificação
• aprovação/validação (responsável/gestão)
• plano de tratamento para gaps identificados (quando aplicável)

Se quiseres, eu consigo gerar um “checklist” e ligar aos riscos/alertas atuais.`;
        }

        if (q.includes("invent") || q.includes("id.ga-1")) {
            return `Para ID.GA-1 (Inventário de ativos), os pontos mais comuns que faltam são:
• periodicidade definida (ex.: mensal)
• dono/responsável por cada ativo
• criticidade e localização
• evidência de revisão (export/relatório + registo)

Sugestão: cria o procedimento + evidência automática (export) e associa ao controlo.`;
        }

        if (q.includes("incidente") || q.includes("cncs") || q.includes("notificar")) {
            return `Para incidentes relevantes (NIS2), o fluxo típico é:
• identificar o incidente e impacto (serviço afetado, criticidade, evidências)
• conter/mitigar (ações iniciais)
• preparar o reporte com campos obrigatórios (timeline, indicadores, medidas adotadas)
• registar auditoria (quem decidiu e quando)

No mock, eu posso “pré-montar” um template de notificação a partir do alerta + ativo.`;
        }

        return `Com base nas evidências disponíveis, recomendo validar se existe documento formal e evidências recentes para suportar o requisito. Se não existir, cria uma nova versão do documento e associa aos controlos relevantes.`;
    }

    // ====== Render chat ======
    function appendMessage({ who, text }) {
        const thread = $("#chatThread");
        if (!thread) return;

        const wrap = document.createElement("div");
        wrap.className = `chat-msg ${who === "user" ? "user" : "bot"}`;

        const meta = document.createElement("div");
        meta.className = "chat-meta muted";
        meta.innerHTML = `<b>${who === "user" ? "Utilizador" : "Sistema"}</b> • ${nowLabel()}`;

        const bubble = document.createElement("div");
        bubble.className = "chat-bubble";

        if (who === "user") {
            // utilizador: texto puro
            bubble.textContent = text || "";
        } else {
            // bot/sistema: markdown bonito
            bubble.innerHTML = renderBotMarkdown(text || "");
        }

        wrap.appendChild(meta);
        wrap.appendChild(bubble);

        thread.appendChild(wrap);
        thread.scrollTop = thread.scrollHeight;
    }

    function escapeHtml(str) {
        return (str || "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    // ====== Render sources panel ======
    function renderSources(sources) {
        const list = $("#sourcesList");
        const empty = $("#sourcesEmpty");
        const chip = $("#chatSourcesChip");

        if (!list || !empty) return;

        if (!sources || !sources.length) {
            empty.style.display = "block";
            list.style.display = "none";
            chip && (chip.textContent = "0");
            return;
        }

        chip && (chip.textContent = String(sources.length));
        empty.style.display = "none";
        list.style.display = "flex";
        list.innerHTML = "";

        sources.forEach((s) => {
            const doc = DOCS.find(d => d.id === s.docId);
            if (!doc) return;

            const item = document.createElement("div");
            item.className = "source-item";
            item.dataset.docId = doc.id;
            item.dataset.chunkIds = JSON.stringify(s.chunkIds);

            const kindLabel =
                doc.kind === "framework" ? "Norma oficial" :
                    doc.kind === "policy" ? "Política" :
                        doc.kind === "procedure" ? "Procedimento" : "Documento";

            item.innerHTML = `
        <div class="source-left">
          <div class="source-title">${doc.title}</div>
          <div class="source-sub">${kindLabel} • chunks: ${s.chunkIds.length}</div>
        </div>
        <div class="source-chips">
          <span class="chip">${kindLabel}</span>
          <span class="chip">Abrir</span>
        </div>
      `;

            item.addEventListener("click", () => openSourceModal(doc.id, s.chunkIds));
            list.appendChild(item);
        });
    }

    function openSourceModalDynamic(doc) {
        const pdf = $("#sourcePdf");
        if (pdf) {
            // importante: força refresh mesmo com mesmo URL
            pdf.src = doc.fileUrl ? (doc.fileUrl + `#toolbar=1&navpanes=0`) : "";
        }

        const chunkList = $("#sourceChunksList");
        const full = $("#chunkFullText");
        if (chunkList) chunkList.innerHTML = "";

        const usedChunks = (doc.chunks || []).filter(c => chunkIds.includes(c.id));
        if (!usedChunks.length) {
            chunkList.innerHTML = `<div class="muted">Sem chunks ligados (mock).</div>`;
            full.textContent = "—";
        } else {
            usedChunks.forEach((c, idx) => {
                const row = document.createElement("div");
                row.className = "chunkrow" + (idx === 0 ? " active" : "");
                row.innerHTML = `
          <div style="font-weight:900">${c.label}</div>
          <div class="muted" style="margin-top:4px">${c.text.slice(0, 120)}${c.text.length > 120 ? "…" : ""}</div>
        `;
                row.addEventListener("click", () => {
                    chunkList.querySelectorAll(".chunkrow").forEach(x => x.classList.remove("active"));
                    row.classList.add("active");
                    full.textContent = c.text;
                });
                chunkList.appendChild(row);

                if (idx === 0 && full) full.textContent = c.text;
            });
        }

        openModal("sourceModal");
    }

    // ====== Modal: PDF + chunks ======
    function openSourceModal(docId, chunkIds) {
        const doc = DOCS.find(d => d.id === docId);
        if (!doc) return;

        $("#sourceModalTitle").textContent = doc.title;

        const pdf = $("#sourcePdf");
        if (pdf) {
            // importante: força refresh mesmo com mesmo URL
            pdf.src = doc.fileUrl ? (doc.fileUrl + `#toolbar=1&navpanes=0`) : "";
        }

        const chunkList = $("#sourceChunksList");
        const full = $("#chunkFullText");
        if (chunkList) chunkList.innerHTML = "";

        const usedChunks = (doc.chunks || []).filter(c => chunkIds.includes(c.id));
        if (!usedChunks.length) {
            chunkList.innerHTML = `<div class="muted">Sem chunks ligados (mock).</div>`;
            full.textContent = "—";
        } else {
            usedChunks.forEach((c, idx) => {
                const row = document.createElement("div");
                row.className = "chunkrow" + (idx === 0 ? " active" : "");
                row.innerHTML = `
          <div style="font-weight:900">${c.label}</div>
          <div class="muted" style="margin-top:4px">${c.text.slice(0, 120)}${c.text.length > 120 ? "…" : ""}</div>
        `;
                row.addEventListener("click", () => {
                    chunkList.querySelectorAll(".chunkrow").forEach(x => x.classList.remove("active"));
                    row.classList.add("active");
                    full.textContent = c.text;
                });
                chunkList.appendChild(row);

                if (idx === 0 && full) full.textContent = c.text;
            });
        }

        openModal("sourceModal");
    }

    function openPdfModal(title, url) {
        const modal = document.querySelector("#pdfModal");
        const frame = document.querySelector("#pdfFrame");
        const t = document.querySelector("#pdfTitle");
        if (!modal || !frame) return;

        if (t) t.textContent = title || "Documento";
        frame.src = url;
        modal.style.display = "block";
    }

    function closePdfModal() {
        const modal = document.querySelector("#pdfModal");
        const frame = document.querySelector("#pdfFrame");
        if (frame) frame.src = "";
        if (modal) modal.style.display = "none";
    }

    document.addEventListener("DOMContentLoaded", () => {
        document.querySelector("#pdfClose")?.addEventListener("click", closePdfModal);
        document.querySelector("#pdfModal")?.addEventListener("click", (e) => {
            if (e.target?.id === "pdfModal") closePdfModal();
        });
    });

    function pdfUrlForDocTitle(title) {
        const t = (title || "").toLowerCase();
        if (t.includes("nis2")) return "/mock/frameworks/NIS2.pdf";
        if (t.includes("qnrcs") || t.includes("cncs")) return "/mock/frameworks/cncs-qnrcs-2019.pdf";
        return null;
    }

    function renderDocsPanelFromSources(sources) {
        const list = $("#sourcesList");
        const empty = $("#sourcesEmpty");
        const chip = $("#chatSourcesChip");
        if (!list || !empty) return;

        // agrupa por doc_title (ou doc_id)
        const map = new Map();
        for (const s of (sources || [])) {
            const docKey = s.doc_title || s.doc_id;
            if (!docKey) continue;

            if (!map.has(docKey)) {
                map.set(docKey, {
                    title: s.doc_title || s.doc_id || "Documento",
                    fileUrl: s.doc_url || pdfUrlForDocTitle(s.doc_title),
                    refs: new Set(),
                });
            }
            // guardar refs bonitas
            if (s.ref_label) map.get(docKey).refs.add(s.ref_label);
            else if (s.chunk_index != null) map.get(docKey).refs.add(`chunk ${s.chunk_index}`);
            else if (s.chunk_id) map.get(docKey).refs.add(s.chunk_id);
        }

        const docs = Array.from(map.values());

        if (!docs.length) {
            empty.style.display = "block";
            list.style.display = "none";
            chip && (chip.textContent = "0");
            return;
        }

        chip && (chip.textContent = String(docs.length));
        empty.style.display = "none";
        list.style.display = "flex";
        list.innerHTML = "";

        docs.forEach((doc) => {
            const item = document.createElement("div");
            item.className = "source-item";

            item.innerHTML = `
            <div class="source-left">
                <div class="source-title">${escapeHtml(doc.title)}</div>
                <div class="source-sub">Norma oficial • chunks: ${doc.refs.size}</div>
            </div>
            <div class="source-chips">
                <span class="chip">Norma oficial</span>
                <span class="chip">Abrir</span>
            </div>
            `;

            item.addEventListener("click", () => openSourceModalDynamic(doc));
            list.appendChild(item);
        });
    }
    // ====== Main send ======
    async function handleSend() {
        const input = $("#chatInput");
        const q = (input?.value || "").trim();
        if (!q) return;

        appendMessage({ who: "user", text: q });
        input.value = "";

        // (opcional) loading message
        appendMessage({ who: "bot", text: "A analisar evidências..." });

        try {
            const res = await fetch("/chat/ask", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                },
                body: JSON.stringify({ question: q }),
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data?.message || "Erro no chat");
            window.__RAG_SOURCES__ = data.sources || [];
            // Pega as fontes e renderiza o PDF
            renderDocsPanelFromSources(data.sources || []);
            // remove a msg "A analisar..." (simples: re-render/ajusta como preferir)
            // MVP: só adiciona resposta final
            appendMessage({ who: "bot", text: data.answer || "Sem resposta." });


            $("#chatAuditChip") && ($("#chatAuditChip").textContent = "OK");
        } catch (e) {
            console.error("CHAT ERROR:", e);
            appendMessage({ who: "bot", text: "Erro ao obter resposta. Verifica configuração do Gemini/Pinecone." });
            $("#chatAuditChip") && ($("#chatAuditChip").textContent = "ERRO");
        }
    }

    // transforma sources (lista) em [{docId, chunkIds}] como teu renderSources espera
    function groupSourcesForPanel(sources) {
        const map = new Map();
        for (const s of sources) {
            if (!s.doc_id) continue;
            const chunkId = s.chunk_id;
            const prev = map.get(s.doc_id) || new Set();
            if (chunkId) prev.add(chunkId);
            map.set(s.doc_id, prev);
        }
        return Array.from(map.entries()).map(([docId, set]) => ({
            docId,
            chunkIds: Array.from(set),
        }));
    }

    document.addEventListener("DOMContentLoaded", () => {
        $("#chatSend")?.addEventListener("click", handleSend);
        $("#chatInput")?.addEventListener("keydown", (e) => {
            if (e.key === "Enter") handleSend();
        });

        $("#btnClearSources")?.addEventListener("click", () => renderSources([]));

        $("#sourceModalClose")?.addEventListener("click", () => closeModal("sourceModal"));
        $("#sourceModal")?.addEventListener("click", (e) => {
            if (e.target?.id === "sourceModal") closeModal("sourceModal");
        });
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") closeModal("sourceModal");
        });

        // seed: mostrar uma fonte de exemplo logo ao abrir a página
        renderSources([
            { docId: "F1", chunkIds: ["F1-C2"] } // QNRCS (cncs-qnrcs-2019.pdf)
        ]);

    });
})();

// Injected: marked table renderer setup (runs after marked CDN loads)
(function setupMarked() {
    function _setup() {
        if (!window.marked) return;

        const renderer = new window.marked.Renderer();

        renderer.table = function(tokenOrHeader, body) {
            let headerHtml, bodyHtml;

            if (tokenOrHeader && typeof tokenOrHeader === "object" && tokenOrHeader.header) {
                // marked v5+
                const token = tokenOrHeader;
                const headerRow = token.header.map(cell => {
                    const txt = (cell.tokens || []).map(t => t.raw || t.text || "").join("") || cell.text || "";
                    return `<th>${txt}</th>`;
                }).join("");
                headerHtml = `<tr>${headerRow}</tr>`;

                bodyHtml = (token.rows || []).map(row => {
                    const cells = row.map(cell => {
                        const txt = (cell.tokens || []).map(t => t.raw || t.text || "").join("") || cell.text || "";
                        return `<td>${txt}</td>`;
                    }).join("");
                    return `<tr>${cells}</tr>`;
                }).join("");
            } else {
                headerHtml = tokenOrHeader || "";
                bodyHtml = body || "";
            }

            return `<div class="md-table-wrap"><table class="md-table"><thead>${headerHtml}</thead><tbody>${bodyHtml}</tbody></table></div>`;
        };

        window.marked.use({ renderer });
        window.marked.setOptions({ gfm: true, breaks: true });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", _setup);
    } else {
        _setup();
    }
})();
