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
                    fileUrl: s.doc_url || pdfUrlForTitle(key),
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

    function highlightChunkInPdf(frame, text) {

        if (!frame || !text) return;

        const needle = text
            .replace(/\s+/g, " ")
            .trim()
            .slice(0, 120)
            .toLowerCase();

        const tryHighlight = () => {

            const doc = frame.contentDocument || frame.contentWindow?.document;
            if (!doc) return;

            const spans = doc.querySelectorAll(".textLayer span");
            if (!spans.length) return;

            spans.forEach(span => {

                const txt = span.textContent
                    ?.replace(/\s+/g, " ")
                    .toLowerCase();

                if (!txt) return;

                if (needle.includes(txt) || txt.includes(needle.slice(0, 40))) {

                    span.style.background = "rgba(255,230,0,0.6)";
                    span.style.borderRadius = "3px";
                    span.style.padding = "1px 2px";

                    span.scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });
                }

            });

        };

        // PDF.js precisa de tempo para renderizar textLayer
        setTimeout(tryHighlight, 900);
    }

    // ─── Source modal: PDF + real chunks ─────────────────────
    // doc: { title, fileUrl, chunks: [source objects from API] }
    function openSourceModal(doc) {
        // Title
        const titleEl = $("#sourceModalTitle");
        if (titleEl) titleEl.textContent = doc.title || "Documento";

        // PDF viewer
        const pdfFrame = $("#sourcePdf");
        const pdfPlaceholder = $("#pdfPlaceholder");

        if (pdfFrame && pdfPlaceholder) {
            if (doc.fileUrl) {
                // Abre já na página do primeiro chunk usando o helper com cache-bust
                const firstPage = doc.chunks?.[0]?.page_number ?? null;
                if (firstPage) {
                    navigatePdfToPage(doc.fileUrl, firstPage);
                } else {
                    pdfFrame.style.display = "block";
                    pdfPlaceholder.style.display = "none";
                    const bust = Date.now();
                    pdfFrame.src = `${doc.fileUrl}?t=${bust}#toolbar=1&navpanes=0&view=FitH`;
                }
            } else {
                pdfFrame.style.display = "none";
                pdfPlaceholder.style.display = "flex";
                pdfFrame.src = "";
            }
        }

        // Chunks list
        const chunksList = $("#sourceChunksList");
        const fullText = $("#chunkFullText");
        const countBadge = $("#chunkCountBadge");

        if (!chunksList) return;
        chunksList.innerHTML = "";
        if (countBadge) countBadge.textContent = String(doc.chunks.length);
        if (fullText) fullText.textContent = doc.chunks.length ? "Seleciona um trecho acima." : "—";

        if (!doc.chunks.length) {
            chunksList.innerHTML = `<div style="font-size:13px;color:var(--c-text-muted);padding:12px">Sem trechos disponíveis.</div>`;
        } else {
            doc.chunks.forEach((chunk, idx) => {
                const row = document.createElement("div");
                row.className = "chunk-row" + (idx === 0 ? " active" : "");

                // Build label: use ref_label, control_code, or chunk_index
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
                        ${page ? `<span class="chunk-page-hint"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Vai para pág. ${page}</span>` : ""}
                    </div>
                `;

                row.addEventListener("click", () => {
                    chunksList.querySelectorAll(".chunk-row").forEach(r => r.classList.remove("active"));
                    row.classList.add("active");

                    if (fullText) {
                        fullText.textContent = chunk.snippet || chunk.ref_label || label || "—";
                    }

                    // ── Navegar o PDF para a página do chunk ──
                    if (page && doc.fileUrl) {
                        navigatePdfToPage(doc.fileUrl, page, chunk.snippet);
                    }
                });

                chunksList.appendChild(row);

                // Auto-select first chunk
                if (idx === 0 && fullText) {
                    fullText.textContent = chunk.snippet || chunk.ref_label || label || "—";
                }
            });
        }

        openModal("sourceModal");
    }

    // ─── PDF navigation (reliable cross-page) ───────────────────
    // Os browsers ignoram mudanças só no fragment (#page=N) se o path base
    // for o mesmo. A solução é adicionar ?t=timestamp para forçar novo load.
    // O timestamp é removido pelo servidor (query string ignorada em ficheiros estáticos).
    function navigatePdfToPage(fileUrl, page, chunkText) {

        const pdfFrame = $("#sourcePdf");
        const pdfPlaceholder = $("#pdfPlaceholder");
        if (!pdfFrame) return;

        pdfFrame.style.display = "block";
        if (pdfPlaceholder) pdfPlaceholder.style.display = "none";

        const bust = Date.now();

        pdfFrame.onload = () => {
            highlightChunkInPdf(pdfFrame, chunkText);
        };

        pdfFrame.src = `${fileUrl}?t=${bust}#page=${page}&toolbar=1&navpanes=0&view=FitH`;
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
        $("#sourceModalClose")?.addEventListener("click", () => closeModal("sourceModal"));
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
