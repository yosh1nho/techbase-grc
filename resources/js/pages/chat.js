// Techbase GRC • Chat RAG — chat.js
// Reescrito: chunks reais da API no modal, UX melhorada.

(() => {
    // ─── Helpers DOM ─────────────────────────────────────────
    const $ = (sel) => document.querySelector(sel);

    function escapeHtml(str) {
        return (str || "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    // ─── Modal helpers ────────────────────────────────────────
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

    // ─── Timestamp ───────────────────────────────────────────
    function nowLabel() {
        return new Date().toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit" });
    }

    // ─── Markdown rendering ───────────────────────────────────
    function unwrapTableCodeFences(md) {
        return (md || "").replace(/```(?:md|markdown)\n([\s\S]*?)```/g, (_, inner) => {
            const t = inner.trim();
            return t.startsWith("|") ? "\n\n" + t + "\n\n" : _;
        });
    }

    function renderBotMarkdown(text) {
        if (!window.marked || typeof window.marked.parse !== "function") {
            return `<pre style="white-space:pre-wrap;margin:0">${escapeHtml(text)}</pre>`;
        }
        try {
            const html = window.marked.parse(unwrapTableCodeFences(text || ""));
            return window.DOMPurify ? window.DOMPurify.sanitize(html) : html;
        } catch (e) {
            console.error("Markdown render error:", e);
            return `<pre style="white-space:pre-wrap;margin:0">${escapeHtml(text || "")}</pre>`;
        }
    }

    // ─── Chat thread rendering ────────────────────────────────
    function appendMessage({ who, text, isLoading = false }) {
        const thread = $("#chatThread");
        if (!thread) return;

        // Remove loading message if it exists and we're adding real content
        if (!isLoading) {
            const loading = thread.querySelector("[data-loading]");
            if (loading) loading.remove();
        }

        const wrap = document.createElement("div");
        wrap.className = `chat-msg ${who === "user" ? "user" : "bot"}`;
        if (isLoading) wrap.dataset.loading = "1";

        const avatar = document.createElement("div");
        avatar.className = who === "user" ? "chat-avatar user-avatar" : "chat-avatar bot-avatar";

        if (who === "user") {
            avatar.textContent = "U";
        } else {
            avatar.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>`;
        }

        const msgContent = document.createElement("div");
        msgContent.className = "chat-msg-content";

        const meta = document.createElement("div");
        meta.className = "chat-meta";
        meta.innerHTML = `${who === "user" ? "Utilizador" : "Sistema"} <span class="chat-time">${nowLabel()}</span>`;

        const bubble = document.createElement("div");
        bubble.className = "chat-bubble";

        if (isLoading) {
            bubble.innerHTML = `<span style="color:var(--c-text-muted);font-size:13px">A analisar evidências…</span>`;
        } else if (who === "user") {
            bubble.textContent = text || "";
        } else {
            bubble.innerHTML = renderBotMarkdown(text || "");
        }

        msgContent.appendChild(meta);
        msgContent.appendChild(bubble);
        wrap.appendChild(avatar);
        wrap.appendChild(msgContent);

        thread.appendChild(wrap);
        thread.scrollTop = thread.scrollHeight;
        return wrap;
    }

    // ─── Typing indicator ─────────────────────────────────────
    function showTyping() {
        const el = $("#typingIndicator");
        if (el) el.style.display = "flex";
        const thread = $("#chatThread");
        if (thread) thread.scrollTop = thread.scrollHeight;
    }

    function hideTyping() {
        const el = $("#typingIndicator");
        if (el) el.style.display = "none";
    }

    // ─── Source kind helpers ──────────────────────────────────
    function kindFromTitle(title) {
        const t = (title || "").toLowerCase();
        if (t.includes("nis2") || t.includes("qnrcs") || t.includes("cncs") || t.includes("diretiva")) return "framework";
        if (t.includes("política") || t.includes("politica") || t.includes("policy")) return "policy";
        if (t.includes("procedimento") || t.includes("procedure")) return "procedure";
        return "internal";
    }

    function kindLabel(kind) {
        return { framework: "Norma oficial", policy: "Política", procedure: "Procedimento", internal: "Documento interno" }[kind] || "Documento";
    }

    function kindInitials(kind) {
        return { framework: "NRM", policy: "POL", procedure: "PROC", internal: "INT" }[kind] || "DOC";
    }

    function pdfUrlForTitle(title) {
        const t = (title || "").toLowerCase();
        if (t.includes("nis2")) return "/mock/frameworks/NIS2.pdf";
        if (t.includes("qnrcs") || t.includes("cncs")) return "/mock/frameworks/cncs-qnrcs-2019.pdf";
        return null;
    }

    // ─── Sources panel rendering ──────────────────────────────
    // sources: array from RagChatService (doc_title, doc_url, snippet, ref_label, score, etc.)
    function renderSourcesPanel(sources) {
        const list = $("#sourcesList");
        const empty = $("#sourcesEmpty");
        const chip = $("#chatSourcesChip");
        if (!list || !empty) return;

        if (!sources || !sources.length) {
            empty.style.display = "flex";
            list.style.display = "none";
            chip && (chip.textContent = "0");
            return;
        }

        // Group by doc_title so each document appears once
        const docMap = new Map();
        for (const s of sources) {
            const key = s.doc_title || s.doc_id || "Documento";
            if (!docMap.has(key)) {
                docMap.set(key, {
                    title: key,
                    fileUrl: s.doc_url || pdfUrlForTitle(key) || null,
                    doc_id: s.doc_id ?? null,   // ← preserva para fallback no modal
                    kind: kindFromTitle(key),
                    chunks: [],
                });
            }
            docMap.get(key).chunks.push(s);
        }

        const docs = Array.from(docMap.values());

        chip && (chip.textContent = String(docs.length));
        empty.style.display = "none";
        list.style.display = "flex";
        list.innerHTML = "";

        docs.forEach((doc, i) => {
            const kind = doc.kind;
            const item = document.createElement("div");
            item.className = "source-item";
            item.style.animationDelay = `${i * 50}ms`;

            item.innerHTML = `
                <div class="source-item-header">
                    <div class="source-item-icon ${kind}">${kindInitials(kind)}</div>
                    <div class="source-item-title">${escapeHtml(doc.title)}</div>
                </div>
                <div class="source-item-meta">
                    <span class="source-kind-chip">${escapeHtml(kindLabel(kind))}</span>
                    <span class="source-chunks-count">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        ${doc.chunks.length} trecho${doc.chunks.length !== 1 ? "s" : ""}
                    </span>
                    <span class="source-open-hint">
                        Ver documento
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </span>
                </div>
            `;

            item.addEventListener("click", () => openSourceModal(doc));
            list.appendChild(item);
        });
    }

    // ═══════════════════════════════════════════════════════════
    //  PDF.JS RENDERER — canvas + text layer + highlight
    // ═══════════════════════════════════════════════════════════

    // Estado do viewer actual
    const pdfState = {
        fileUrl: null,
        pdfDoc: null,
        rendering: false,
        snippet: null,
        currentPage: 1,
        snippetTokens: [],
    };

    // Elementos do viewer
    function pdfEls() {
        return {
            placeholder: $("#pdfPlaceholder"),
            loading: $("#pdfLoading"),
            viewerWrap: $("#pdfViewerWrap"),
            container: $("#pdfContainer"),
            pageInfo: $("#pdfPageInfo"),
        };
    }

    function showPdfState(state) {
        const e = pdfEls();
        e.placeholder && (e.placeholder.style.display = state === "empty" ? "flex" : "none");
        e.loading && (e.loading.style.display = state === "loading" ? "flex" : "none");
        e.viewerWrap && (e.viewerWrap.style.display = state === "ready" ? "flex" : "none");
    }

    // ─── Tokeniza snippet para matching fuzzy ─────────────────
    // Remove pontuação, normaliza espaços, retorna array de palavras >= 3 chars
    function tokenize(text) {
        return (text || "")
            .toLowerCase()
            .replace(/[.,;:!?()\[\]{}"'«»\-–—\/]/g, " ")
            .split(/\s+/)
            .filter(w => w.length >= 3);
    }

    // ─── Carrega PDF via pdf.js ───────────────────────────────
    async function loadPdfDoc(fileUrl) {
        // Aguarda pdf.js estar pronto (carregado pelo app.blade)
        const lib = await window.__pdfjs_ready;
        const loadingTask = lib.getDocument(fileUrl);
        return await loadingTask.promise;
    }

    // ─── Renderiza uma página e aplica text layer + highlight ─
    async function renderPage(pdfDoc, pageNum, container, snippetTokens) {
        const page = await pdfDoc.getPage(pageNum);

        // Scale para caber na largura do container (max 900px)
        const containerWidth = Math.min(container.clientWidth || 700, 900);
        const viewport = page.getViewport({ scale: 1 });
        const scale = Math.max((containerWidth - 24) / viewport.width, 0.8);
        const scaledViewport = page.getViewport({ scale });

        // Wrap para posicionamento relativo canvas + textLayer
        const wrap = document.createElement("div");
        wrap.className = "pdf-page-wrap";
        wrap.dataset.page = pageNum;
        wrap.style.width = scaledViewport.width + "px";
        wrap.style.height = scaledViewport.height + "px";

        // Canvas
        const canvas = document.createElement("canvas");
        canvas.width = scaledViewport.width;
        canvas.height = scaledViewport.height;
        const ctx = canvas.getContext("2d");

        wrap.appendChild(canvas);
        container.appendChild(wrap);

        // Renderiza página no canvas
        await page.render({ canvasContext: ctx, viewport: scaledViewport }).promise;

        // Text layer
        const textContent = await page.getTextContent();
        const textLayerDiv = document.createElement("div");
        textLayerDiv.className = "textLayer";
        textLayerDiv.style.width = scaledViewport.width + "px";
        textLayerDiv.style.height = scaledViewport.height + "px";
        wrap.appendChild(textLayerDiv);

        // Renderiza text layer via pdf.js
        const lib = await window.__pdfjs_ready;
        const renderTask = lib.renderTextLayer({
            textContentSource: textContent,
            container: textLayerDiv,
            viewport: scaledViewport,
            textDivs: [],
        });
        await renderTask.promise;

        // Highlight: itera spans e marca os que contêm tokens do snippet
        if (snippetTokens && snippetTokens.length > 0) {
            highlightSpans(textLayerDiv, snippetTokens);
        }
    }

    // ─── Highlight fuzzy nos spans do text layer ──────────────
    function highlightSpans(textLayerDiv, snippetTokens) {
        const spans = Array.from(textLayerDiv.querySelectorAll("span"));
        let firstMatch = true;

        spans.forEach(span => {
            const text = (span.textContent || "").toLowerCase();
            if (!text.trim()) return;

            // Conta quantos tokens do snippet existem neste span
            const matches = snippetTokens.filter(tok => text.includes(tok));
            const ratio = matches.length / snippetTokens.length;

            // Threshold: pelo menos 30% dos tokens ou 2+ tokens em comum
            if (ratio >= 0.30 || matches.length >= 2) {
                span.classList.add("hl-chunk");
                if (firstMatch) {
                    span.classList.add("hl-chunk-primary");
                    firstMatch = false;
                }
            }
        });
    }

    // ─── Limpa highlights anteriores ─────────────────────────
    function clearHighlights() {
        document.querySelectorAll(".hl-chunk, .hl-chunk-primary").forEach(el => {
            el.classList.remove("hl-chunk", "hl-chunk-primary");
        });
    }

    // ─── Renderiza UMA página (a pedida) — sem scroll, sem delay ─
    // Estratégia: só renderiza a página exacta do chunk.
    // ─── Renderiza uma página com navegação completa ──────────
    async function renderPdfViewer(fileUrl, targetPage, snippetText) {
        if (!fileUrl) { showPdfState("empty"); return; }

        pdfState.rendering = true;
        pdfState.snippetTokens = tokenize(snippetText);
        showPdfState("loading");

        const e = pdfEls();
        const page = Math.max(1, targetPage || 1);

        try {
            // Carrega o documento apenas uma vez por URL
            if (pdfState.fileUrl !== fileUrl) {
                pdfState.pdfDoc = await loadPdfDoc(fileUrl);
                pdfState.fileUrl = fileUrl;
            }

            if (!pdfState.rendering) return;

            const pdfDoc = pdfState.pdfDoc;
            pdfState.currentPage = Math.min(page, pdfDoc.numPages);

            // Injeta barra de navegação se ainda não existir
            ensurePdfNavBar(pdfDoc.numPages);

            // Renderiza a página pedida
            await goToPage(pdfState.currentPage);

            showPdfState("ready");

        } catch (err) {
            console.error("PDF render error:", err);
            showPdfState("empty");
            const p = e.placeholder?.querySelector("p");
            if (p) p.textContent = "Erro ao carregar PDF.";
        } finally {
            pdfState.rendering = false;
        }
    }

    // ─── Vai para uma página específica ──────────────────────
    async function goToPage(page) {
        const e = pdfEls();
        if (!pdfState.pdfDoc) return;

        const totalPages = pdfState.pdfDoc.numPages;
        pdfState.currentPage = Math.max(1, Math.min(page, totalPages));

        if (e.container) e.container.innerHTML = "";

        // Actualiza contador
        const pageInfo = $("#pdfPageInfo");
        if (pageInfo) {
            pageInfo.textContent = `${pdfState.currentPage} / ${totalPages}`;
            pageInfo.style.display = "inline";
        }

        // Actualiza estado dos botões
        const btnPrev = $("#pdfNavPrev");
        const btnNext = $("#pdfNavNext");
        if (btnPrev) btnPrev.disabled = pdfState.currentPage <= 1;
        if (btnNext) btnNext.disabled = pdfState.currentPage >= totalPages;

        await renderPage(
            pdfState.pdfDoc,
            pdfState.currentPage,
            e.container,
            pdfState.snippetTokens
        );
    }

    // ─── Cria a barra de navegação (só uma vez por modal) ─────
    function ensurePdfNavBar(totalPages) {
        // Se já existe, só actualiza o total
        if ($("#pdfNavBar")) {
            const pageInfo = $("#pdfPageInfo");
            if (pageInfo) pageInfo.textContent = `${pdfState.currentPage} / ${totalPages}`;
            return;
        }

        const viewerWrap = $("#pdfViewerWrap");
        if (!viewerWrap) return;

        // Cria barra fixa no topo do viewer
        const bar = document.createElement("div");
        bar.id = "pdfNavBar";
        bar.style.cssText = [
            "display:flex",
            "align-items:center",
            "justify-content:center",
            "gap:10px",
            "padding:6px 12px",
            "background:var(--c-card, #1e2433)",
            "border-bottom:1px solid var(--c-border, rgba(255,255,255,.1))",
            "flex-shrink:0",
        ].join(";");

        const btnStyle = [
            "background:var(--c-surface, rgba(255,255,255,.06))",
            "border:1px solid var(--c-border, rgba(255,255,255,.12))",
            "color:var(--c-text, #e2e8f0)",
            "border-radius:6px",
            "padding:3px 10px",
            "font-size:13px",
            "cursor:pointer",
            "line-height:1.6",
            "transition:opacity .15s",
        ].join(";");

        bar.innerHTML = `
        <button id="pdfNavPrev" style="${btnStyle}" title="Página anterior">‹</button>
        <span id="pdfPageInfo" style="font-size:12px;color:var(--c-text-muted,#94a3b8);min-width:60px;text-align:center">
            ${pdfState.currentPage} / ${totalPages}
        </span>
        <button id="pdfNavNext" style="${btnStyle}" title="Página seguinte">›</button>
    `;

        // Insere a barra ANTES do container de páginas
        viewerWrap.prepend(bar);

        // Eventos dos botões
        $("#pdfNavPrev").addEventListener("click", () => {
            if (pdfState.currentPage > 1) goToPage(pdfState.currentPage - 1);
        });
        $("#pdfNavNext").addEventListener("click", () => {
            if (pdfState.pdfDoc && pdfState.currentPage < pdfState.pdfDoc.numPages) {
                goToPage(pdfState.currentPage + 1);
            }
        });
    }

    // ─── Source modal: chunks + pdf.js viewer ────────────────
    // doc: { title, fileUrl, chunks: [source objects from API] }
    function openSourceModal(doc) {
        // Se o doc_url for nulo, mas tivermos um doc_id, criamos o link para a nossa rota segura
        if (!doc.fileUrl && (doc.doc_id || doc.id)) {
            const id = doc.doc_id || doc.id;
            doc.fileUrl = `/documents/view/${id}`;
            console.log("Forçando URL seguro para doc interno:", doc.fileUrl);
        }

        // Title
        const titleEl = $("#sourceModalTitle");
        if (titleEl) titleEl.textContent = doc.title || "Documento";
        // Chunks list
        const chunksList = $("#sourceChunksList");
        const fullText = $("#chunkFullText");
        const countBadge = $("#chunkCountBadge");

        if (!chunksList) return;
        chunksList.innerHTML = "";
        if (countBadge) countBadge.textContent = String(doc.chunks.length);
        if (fullText) fullText.textContent = doc.chunks.length ? "Seleciona um trecho acima." : "—";

        // Primeiro chunk é activo por defeito
        const firstChunk = doc.chunks[0] ?? null;

        if (!doc.chunks.length) {
            chunksList.innerHTML = `<div style="font-size:13px;color:var(--c-text-muted);padding:12px">Sem trechos disponíveis.</div>`;
        } else {
            doc.chunks.forEach((chunk, idx) => {
                const row = document.createElement("div");
                row.className = "chunk-row" + (idx === 0 ? " active" : "");

                const label = chunk.ref_label || chunk.ref
                    || (chunk.control_code ? `${chunk.control_family || ""} ${chunk.control_code}`.trim() : null)
                    || (chunk.article_code ? chunk.article_code : null)
                    || `Trecho ${idx + 1}`;

                const snippet = chunk.snippet || "";
                const score = chunk.score != null ? (parseFloat(chunk.score) * 100).toFixed(1) : null;
                const page = chunk.page_number ?? null;

                row.innerHTML = `
                    <div class="chunk-row-header">
                        <div class="chunk-number">${idx + 1}</div>
                        <div class="chunk-label">${escapeHtml(label)}</div>
                        ${page ? `<div class="chunk-page-badge">p. ${page}</div>` : ""}
                    </div>
                    <div class="chunk-snippet">${escapeHtml(snippet)}</div>
                    <div class="chunk-row-footer">
                        ${score ? `<span class="chunk-score">relevância ${score}%</span>` : ""}
                        ${page ? `<span class="chunk-page-hint">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            Vai para pág. ${page}
                        </span>` : ""}
                    </div>
                `;

                row.addEventListener("click", () => {
                    chunksList.querySelectorAll(".chunk-row").forEach(r => r.classList.remove("active"));
                    row.classList.add("active");

                    const snippetText = chunk.snippet || chunk.ref_label || label || "";
                    if (fullText) fullText.textContent = snippetText;

                    // Re-renderiza directamente a página do chunk — sem scroll animado
                    if (doc.fileUrl) {
                        renderPdfViewer(doc.fileUrl, page ?? 1, snippetText);
                    }
                });

                chunksList.appendChild(row);

                // Texto completo do primeiro chunk
                if (idx === 0 && fullText) {
                    fullText.textContent = chunk.snippet || chunk.ref_label || label || "—";
                }
            });
        }

        openModal("sourceModal");

        // Carrega PDF com o primeiro chunk activo
        if (doc.fileUrl && firstChunk) {
            renderPdfViewer(doc.fileUrl, firstChunk.page_number ?? 1, firstChunk.snippet || "");
        } else if (!doc.fileUrl) {
            showPdfState("empty");
        }
    }

    // ─── Status badge ─────────────────────────────────────────
    function setAuditStatus(ok) {
        const chip = $("#chatAuditChip");
        const badge = $("#auditBadge");
        if (chip) chip.textContent = ok ? "OK" : "ERRO";
        if (badge) {
            const dot = badge.querySelector(".status-dot");
            if (dot) {
                dot.classList.toggle("green", ok);
                dot.classList.toggle("red", !ok);
            }
        }
    }

    // ─── Textarea auto-resize ─────────────────────────────────
    function autoResize(textarea) {
        textarea.style.height = "auto";
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + "px";
    }

    // ─── Send handler ─────────────────────────────────────────
    async function handleSend() {
        const input = $("#chatInput");
        const sendBtn = $("#chatSend");
        const q = (input?.value || "").trim();
        if (!q) return;

        // Disable input while loading
        if (input) input.value = "";
        if (input) { input.style.height = "auto"; }
        if (sendBtn) sendBtn.disabled = true;

        appendMessage({ who: "user", text: q });
        showTyping();

        try {
            const res = await fetch("/chat/ask", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                },
                body: JSON.stringify({ question: q }),
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data?.message || "Erro no servidor");

            hideTyping();

            // Store globally for debugging
            window.__RAG_SOURCES__ = data.sources || [];

            // Render sources panel with real API data
            renderSourcesPanel(data.sources || []);

            // Append bot answer
            appendMessage({ who: "bot", text: data.answer || "Sem resposta." });

            setAuditStatus(true);

        } catch (e) {
            hideTyping();
            console.error("CHAT ERROR:", e);
            appendMessage({ who: "bot", text: `Erro ao obter resposta: ${e.message || "Verifica configuração do Gemini/Pinecone."}` });
            setAuditStatus(false);
        } finally {
            if (sendBtn) sendBtn.disabled = false;
            input?.focus();
        }
    }

    // ─── DOMContentLoaded ─────────────────────────────────────
    document.addEventListener("DOMContentLoaded", () => {
        const input = $("#chatInput");
        const sendBtn = $("#chatSend");

        sendBtn?.addEventListener("click", handleSend);

        input?.addEventListener("keydown", (e) => {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                handleSend();
            }
        });

        input?.addEventListener("input", () => autoResize(input));

        $("#btnClearSources")?.addEventListener("click", () => renderSourcesPanel([]));

        // Modal close
        $("#sourceModalClose")?.addEventListener("click", () => {
            closeModal("sourceModal");
            pdfState.pdfDoc = null;
            pdfState.fileUrl = null;
            pdfState.currentPage = 1;
            pdfState.snippetTokens = [];
            const c = $("#pdfContainer");
            if (c) c.innerHTML = "";
            // Remove a navbar para recriar limpa na próxima abertura
            const nav = $("#pdfNavBar");
            if (nav) nav.remove();
        });
        $("#sourceModal")?.addEventListener("click", (e) => {
            if (e.target?.id === "sourceModal") closeModal("sourceModal");
        });
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") closeModal("sourceModal");
        });
    });

})();

// ─── Marked: custom table renderer ───────────────────────────
(function setupMarked() {
    function _setup() {
        if (!window.marked) return;

        const renderer = new window.marked.Renderer();

        renderer.table = function (tokenOrHeader, body) {
            let headerHtml, bodyHtml;

            if (tokenOrHeader && typeof tokenOrHeader === "object" && tokenOrHeader.header) {
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
