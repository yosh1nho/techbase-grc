@extends('layouts.app')
@section('title', 'Chat Governança • Techbase GRC')

@section('content')
<section id="page-chat" class="page">

  {{-- Header --}}
  <div class="chat-page-header">
    <div class="chat-page-header-left">
      <div class="chat-page-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </div>
      <div>
        <h2 class="chat-page-title">Chat de Governança</h2>
        <p class="chat-page-sub">Respostas baseadas em documentos oficiais e evidências internas via RAG semântico</p>
      </div>
    </div>
    <div class="chat-status-badges">
      <div class="status-badge" id="auditBadge">
        <span class="status-dot green"></span>
        <span>Auditoria</span>
        <strong id="chatAuditChip">OK</strong>
      </div>
      <div class="status-badge">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <span>Fontes</span>
        <strong id="chatSourcesChip">0</strong>
      </div>
    </div>
  </div>

  {{-- Main layout --}}
  <div class="chat-layout">

    {{-- LEFT: Conversation --}}
    <div class="chat-pane">
      <div class="pane-header">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span>Conversa</span>
      </div>

      <div id="chatThread" class="chat-thread">
        {{-- Seed message --}}
        <div class="chat-msg bot">
          <div class="chat-avatar bot-avatar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
          </div>
          <div class="chat-msg-content">
            <div class="chat-meta">Sistema <span class="chat-time">agora</span></div>
            <div class="chat-bubble">
              Bem-vindo ao Chat de Governança. Podes perguntar sobre controlos NIS2, QNRCS, políticas internas ou pedir recomendações de compliance.
            </div>
          </div>
        </div>
      </div>

      {{-- Typing indicator (hidden by default) --}}
      <div id="typingIndicator" class="typing-indicator" style="display:none">
        <div class="chat-avatar bot-avatar">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
        </div>
        <div class="typing-dots">
          <span></span><span></span><span></span>
        </div>
      </div>

      {{-- Input area --}}
      <div class="chat-input-area">
        <div class="chat-input-wrap">
          <textarea
            id="chatInput"
            class="chat-textarea"
            placeholder="Pergunta sobre controlos, políticas, NIS2, QNRCS..."
            rows="1"
          ></textarea>
          <button id="chatSend" class="chat-send-btn" type="button" title="Enviar (Enter)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
        </div>
        <p class="chat-hint">Enter para enviar · Shift+Enter para nova linha · Respostas auditadas (RNF5)</p>
      </div>
    </div>

    {{-- RIGHT: Sources --}}
    <div class="sources-pane">
      <div class="pane-header">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <span>Fontes usadas</span>
        <button id="btnClearSources" class="pane-header-btn" type="button">Limpar</button>
      </div>

      <p class="sources-desc">Documentos e trechos (chunks) que suportam a última resposta.</p>

      <div id="sourcesEmpty" class="sources-empty">
        <div class="sources-empty-icon">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <p>Faz uma pergunta para ver as fontes aqui.</p>
      </div>

      <div id="sourcesList" class="sources-list" style="display:none"></div>
    </div>

  </div>
</section>

{{-- MODAL: Document viewer (PDF + Chunks) --}}
<div id="sourceModal" class="modal-overlay" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true">

    <div class="modal-topbar">
      <div class="modal-doc-info">
        <div class="modal-doc-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div>
          <div class="modal-doc-label">Documento referenciado</div>
          <div id="sourceModalTitle" class="modal-doc-title">—</div>
        </div>
      </div>
      <button id="sourceModalClose" class="modal-close-btn" type="button">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <div class="modal-body">

      {{-- LEFT: Chunks panel --}}
      <div class="modal-chunks-col">
        <div class="modal-col-header">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          Trechos citados
          <span id="chunkCountBadge" class="chunk-count-badge">0</span>
        </div>
        <p class="modal-col-sub">Seleciona um trecho para ver o texto completo.</p>

        <div id="sourceChunksList" class="chunks-scroll"></div>

        <div class="chunk-preview-section">
          <div class="chunk-preview-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Trecho completo
          </div>
          <div id="chunkFullText" class="chunk-full-text">Seleciona um trecho acima.</div>
        </div>
      </div>

      {{-- RIGHT: PDF viewer (pdf.js com text layer + highlight) --}}
      <div class="modal-pdf-col">
        <div class="modal-col-header">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 3h15v13H1z"/><path d="M16 8l4 0"/><path d="M16 11l4 0"/><path d="M16 14l4 0"/><path d="M20 3l0 18"/></svg>
          Pré-visualização
          <span id="pdfPageInfo" class="pdf-page-info" style="display:none"></span>
        </div>

        {{-- Placeholder quando não há PDF --}}
        <div id="pdfPlaceholder" class="pdf-placeholder">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <p>Sem PDF disponível para este documento.</p>
        </div>

        {{-- Loading enquanto pdf.js carrega --}}
        <div id="pdfLoading" class="pdf-loading" style="display:none">
          <div class="pdf-spinner"></div>
          <p>A carregar documento…</p>
        </div>

        {{-- Container pdf.js: canvas + text layer sobrepostos --}}
        <div id="pdfViewerWrap" class="pdf-viewer-wrap" style="display:none">
          <div id="pdfContainer" class="pdf-container"></div>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.11/dist/purify.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<style>
/* ══════════════════════════════════════════════
   TOKENS — Dark (default) / Light
══════════════════════════════════════════════ */
:root {
  --c-bg:           #080e1a;
  --c-surface:      #0d1526;
  --c-surface2:     #111d30;
  --c-surface3:     #162036;
  --c-border:       rgba(255,255,255,.07);
  --c-border-hover: rgba(59,130,246,.35);
  --c-border-active:rgba(34,211,238,.4);
  --c-text:         #e2e8f0;
  --c-text-muted:   rgba(226,232,240,.45);
  --c-accent:       #22d3ee;
  --c-accent2:      #3b82f6;
  --c-accent-glow:  rgba(34,211,238,.12);
  --c-user-bg:      rgba(34,211,238,.08);
  --c-user-bd:      rgba(34,211,238,.18);
  --c-bot-bg:       rgba(255,255,255,.04);
  --c-bot-bd:       rgba(255,255,255,.08);
  --c-danger:       #f87171;
  --c-green:        #34d399;
  --c-chunk-active: rgba(34,211,238,.10);
  --c-chunk-active-bd: rgba(34,211,238,.35);
  --radius:         14px;
  --radius-sm:      10px;
  --shadow-modal:   0 40px 80px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.06);
  --font-mono:      'JetBrains Mono', 'Fira Code', monospace;
}

:root[data-theme="light"] {
  --c-bg:           #f0f4f9;
  --c-surface:      #ffffff;
  --c-surface2:     #f7f9fc;
  --c-surface3:     #eef2f8;
  --c-border:       rgba(15,23,42,.10);
  --c-border-hover: rgba(37,99,235,.28);
  --c-border-active:rgba(6,182,212,.4);
  --c-text:         #0f172a;
  --c-text-muted:   rgba(15,23,42,.45);
  --c-accent:       #0891b2;
  --c-accent2:      #2563eb;
  --c-accent-glow:  rgba(8,145,178,.08);
  --c-user-bg:      rgba(6,182,212,.07);
  --c-user-bd:      rgba(6,182,212,.22);
  --c-bot-bg:       rgba(15,23,42,.03);
  --c-bot-bd:       rgba(15,23,42,.09);
  --c-green:        #059669;
  --c-chunk-active: rgba(6,182,212,.08);
  --c-chunk-active-bd: rgba(6,182,212,.35);
  --shadow-modal:   0 20px 60px rgba(0,0,0,.15), 0 0 0 1px rgba(15,23,42,.08);
}

/* ══════════════════════════════════════════════
   PAGE HEADER
══════════════════════════════════════════════ */
.chat-page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.chat-page-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.chat-page-icon {
  width: 42px; height: 42px;
  border-radius: 12px;
  background: var(--c-accent-glow);
  border: 1px solid var(--c-border-active);
  display: flex; align-items: center; justify-content: center;
  color: var(--c-accent);
  flex-shrink: 0;
}

.chat-page-title {
  font-size: 18px;
  font-weight: 800;
  color: var(--c-text);
  margin: 0 0 2px;
  letter-spacing: -.02em;
}

.chat-page-sub {
  font-size: 13px;
  color: var(--c-text-muted);
  margin: 0;
}

.chat-status-badges {
  display: flex;
  gap: 8px;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: var(--c-surface2);
  border: 1px solid var(--c-border);
  border-radius: 20px;
  font-size: 12px;
  color: var(--c-text-muted);
}

.status-badge strong {
  color: var(--c-text);
  font-weight: 700;
}

.status-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}
.status-dot.green { background: var(--c-green); box-shadow: 0 0 6px var(--c-green); }
.status-dot.red   { background: var(--c-danger); }

/* ══════════════════════════════════════════════
   LAYOUT: two-col
══════════════════════════════════════════════ */
.chat-layout {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 16px;
  height: calc(100vh - 200px);
  min-height: 600px;
}

@media (max-width: 900px) {
  .chat-layout { grid-template-columns: 1fr; height: auto; }
}

/* ══════════════════════════════════════════════
   PANES (shared)
══════════════════════════════════════════════ */
.chat-pane, .sources-pane {
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: var(--radius);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.pane-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--c-border);
  font-size: 13px;
  font-weight: 700;
  color: var(--c-text);
  letter-spacing: .03em;
  text-transform: uppercase;
  flex-shrink: 0;
}

.pane-header svg { color: var(--c-accent); flex-shrink: 0; }
.pane-header span { flex: 1; }

.pane-header-btn {
  margin-left: auto;
  padding: 4px 10px;
  border-radius: 6px;
  background: transparent;
  border: 1px solid var(--c-border);
  color: var(--c-text-muted);
  font-size: 11px;
  cursor: pointer;
  transition: all .15s;
}
.pane-header-btn:hover {
  border-color: var(--c-border-hover);
  color: var(--c-text);
}

/* ══════════════════════════════════════════════
   CHAT THREAD
══════════════════════════════════════════════ */
.chat-thread {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  scroll-behavior: smooth;
}

.chat-thread::-webkit-scrollbar { width: 4px; }
.chat-thread::-webkit-scrollbar-track { background: transparent; }
.chat-thread::-webkit-scrollbar-thumb { background: var(--c-border); border-radius: 4px; }

.chat-msg {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  animation: msgIn .2s ease-out;
}

@keyframes msgIn {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}

.chat-msg.user { flex-direction: row-reverse; }

.chat-avatar {
  width: 30px; height: 30px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  margin-top: 2px;
}

.bot-avatar {
  background: var(--c-accent-glow);
  border: 1px solid var(--c-border-active);
  color: var(--c-accent);
}

.user-avatar {
  background: var(--c-user-bg);
  border: 1px solid var(--c-user-bd);
  color: var(--c-accent);
  font-size: 12px;
  font-weight: 700;
}

.chat-msg-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  max-width: 88%;
}

.chat-msg.user .chat-msg-content { align-items: flex-end; }

.chat-meta {
  font-size: 11px;
  font-weight: 600;
  color: var(--c-text-muted);
  letter-spacing: .04em;
  text-transform: uppercase;
}

.chat-time {
  font-weight: 400;
  margin-left: 4px;
}

.chat-bubble {
  padding: 12px 14px;
  border-radius: var(--radius);
  border: 1px solid var(--c-bot-bd);
  background: var(--c-bot-bg);
  font-size: 14px;
  line-height: 1.6;
  color: var(--c-text);
  overflow-wrap: anywhere;
  word-break: break-word;
}

.chat-msg.user .chat-bubble {
  background: var(--c-user-bg);
  border-color: var(--c-user-bd);
  border-bottom-right-radius: 4px;
}

.chat-msg.bot .chat-bubble {
  border-bottom-left-radius: 4px;
}

/* Markdown inside bubbles */
.chat-bubble p { margin: 0 0 8px; }
.chat-bubble p:last-child { margin-bottom: 0; }
.chat-bubble ul, .chat-bubble ol { margin: 6px 0 8px; padding-left: 20px; }
.chat-bubble li { margin-bottom: 4px; }
.chat-bubble code { font-family: var(--font-mono); font-size: .85em; background: rgba(255,255,255,.07); padding: 1px 5px; border-radius: 4px; }
.chat-bubble pre { background: rgba(0,0,0,.25); border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 12px; overflow-x: auto; }
.chat-bubble pre code { background: none; padding: 0; }
.chat-bubble strong { color: var(--c-text); font-weight: 700; }
.chat-bubble h1, .chat-bubble h2, .chat-bubble h3 { font-size: 14px; font-weight: 700; margin: 8px 0 4px; }
.chat-bubble table { display: block; max-width: 100%; overflow-x: auto; }

/* ══════════════════════════════════════════════
   TYPING INDICATOR
══════════════════════════════════════════════ */
.typing-indicator {
  display: flex;
  gap: 10px;
  align-items: center;
  padding: 0 16px 12px;
}

.typing-dots {
  display: flex; gap: 4px; align-items: center;
  padding: 10px 14px;
  background: var(--c-bot-bg);
  border: 1px solid var(--c-bot-bd);
  border-radius: var(--radius);
  border-bottom-left-radius: 4px;
}

.typing-dots span {
  width: 6px; height: 6px;
  background: var(--c-accent);
  border-radius: 50%;
  opacity: .4;
  animation: typingBounce 1.2s infinite;
}
.typing-dots span:nth-child(2) { animation-delay: .2s; }
.typing-dots span:nth-child(3) { animation-delay: .4s; }

@keyframes typingBounce {
  0%, 80%, 100% { opacity: .4; transform: translateY(0); }
  40%           { opacity: 1; transform: translateY(-4px); }
}

/* ══════════════════════════════════════════════
   CHAT INPUT
══════════════════════════════════════════════ */
.chat-input-area {
  padding: 12px 16px;
  border-top: 1px solid var(--c-border);
  background: var(--c-surface);
  flex-shrink: 0;
}

.chat-input-wrap {
  display: flex;
  gap: 8px;
  align-items: flex-end;
  background: var(--c-surface2);
  border: 1px solid var(--c-border);
  border-radius: var(--radius);
  padding: 10px 12px;
  transition: border-color .15s;
}

.chat-input-wrap:focus-within {
  border-color: var(--c-border-active);
  box-shadow: 0 0 0 3px var(--c-accent-glow);
}

.chat-textarea {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: var(--c-text);
  font-size: 14px;
  line-height: 1.5;
  resize: none;
  max-height: 120px;
  font-family: inherit;
}

.chat-textarea::placeholder { color: var(--c-text-muted); }

.chat-send-btn {
  width: 34px; height: 34px;
  border-radius: 8px;
  background: var(--c-accent);
  border: none;
  color: #080e1a;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: all .15s;
}

.chat-send-btn:hover { background: #67e8f9; transform: scale(1.05); }
.chat-send-btn:active { transform: scale(.97); }
.chat-send-btn:disabled { opacity: .4; cursor: not-allowed; transform: none; }

.chat-hint {
  font-size: 11px;
  color: var(--c-text-muted);
  margin: 6px 0 0;
  text-align: center;
}

/* ══════════════════════════════════════════════
   SOURCES PANE
══════════════════════════════════════════════ */
.sources-pane { overflow: hidden; }

.sources-desc {
  font-size: 12px;
  color: var(--c-text-muted);
  padding: 10px 16px 0;
  margin: 0;
}

.sources-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 32px 24px;
  text-align: center;
  color: var(--c-text-muted);
  font-size: 13px;
}

.sources-empty-icon {
  opacity: .3;
  color: var(--c-accent);
}

.sources-list {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.sources-list::-webkit-scrollbar { width: 4px; }
.sources-list::-webkit-scrollbar-thumb { background: var(--c-border); border-radius: 4px; }

.source-item {
  border: 1px solid var(--c-border);
  border-radius: var(--radius-sm);
  padding: 12px;
  cursor: pointer;
  transition: all .15s;
  background: var(--c-surface2);
  animation: msgIn .2s ease-out;
}

.source-item:hover {
  border-color: var(--c-border-hover);
  background: var(--c-surface3);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,.15);
}

.source-item-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 6px;
}

.source-item-icon {
  width: 28px; height: 28px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .02em;
}

.source-item-icon.framework { background: rgba(34,211,238,.12); color: var(--c-accent); border: 1px solid rgba(34,211,238,.2); }
.source-item-icon.policy    { background: rgba(59,130,246,.12); color: #60a5fa; border: 1px solid rgba(59,130,246,.2); }
.source-item-icon.procedure { background: rgba(167,139,250,.12); color: #a78bfa; border: 1px solid rgba(167,139,250,.2); }
.source-item-icon.internal  { background: rgba(52,211,153,.12); color: #34d399; border: 1px solid rgba(52,211,153,.2); }

.source-item-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--c-text);
  line-height: 1.35;
  flex: 1;
}

.source-item-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.source-kind-chip {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .05em;
  text-transform: uppercase;
  padding: 2px 7px;
  border-radius: 10px;
  color: var(--c-text-muted);
  background: var(--c-surface3);
  border: 1px solid var(--c-border);
}

.source-chunks-count {
  font-size: 11px;
  color: var(--c-accent);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 4px;
}

.source-open-hint {
  font-size: 11px;
  color: var(--c-text-muted);
  display: flex;
  align-items: center;
  gap: 4px;
}

/* ══════════════════════════════════════════════
   MODAL
══════════════════════════════════════════════ */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.65);
  backdrop-filter: blur(6px);
  display: none;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 99999;
}

.modal-overlay.open { display: flex; }

.modal-card {
  width: min(1200px, 96vw);
  max-height: 90vh;
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: 18px;
  box-shadow: var(--shadow-modal);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: modalIn .2s cubic-bezier(.34,1.56,.64,1);
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(.95) translateY(10px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--c-border);
  flex-shrink: 0;
}

.modal-doc-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-doc-icon {
  width: 36px; height: 36px;
  border-radius: 10px;
  background: var(--c-accent-glow);
  border: 1px solid var(--c-border-active);
  display: flex; align-items: center; justify-content: center;
  color: var(--c-accent);
}

.modal-doc-label {
  font-size: 11px;
  color: var(--c-text-muted);
  text-transform: uppercase;
  letter-spacing: .05em;
  margin-bottom: 2px;
}

.modal-doc-title {
  font-size: 16px;
  font-weight: 800;
  color: var(--c-text);
  letter-spacing: -.01em;
}

.modal-close-btn {
  width: 36px; height: 36px;
  border-radius: 10px;
  background: var(--c-surface2);
  border: 1px solid var(--c-border);
  color: var(--c-text-muted);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all .15s;
}

.modal-close-btn:hover { border-color: var(--c-danger); color: var(--c-danger); }

.modal-body {
  display: grid;
  grid-template-columns: 340px 1fr;
  flex: 1;
  overflow: hidden;
}

@media (max-width: 760px) {
  .modal-body { grid-template-columns: 1fr; }
  .modal-pdf-col { display: none; }
}

.modal-col-header {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .05em;
  text-transform: uppercase;
  color: var(--c-text-muted);
  margin-bottom: 6px;
  padding: 14px 16px 0;
}

.modal-col-header svg { color: var(--c-accent); }

.chunk-count-badge {
  margin-left: auto;
  background: var(--c-accent-glow);
  color: var(--c-accent);
  border: 1px solid var(--c-border-active);
  border-radius: 10px;
  padding: 1px 7px;
  font-size: 11px;
  font-weight: 700;
}

.modal-col-sub {
  font-size: 11px;
  color: var(--c-text-muted);
  padding: 0 16px 10px;
  margin: 0;
}

/* Chunks scroll area */
.modal-chunks-col {
  border-right: 1px solid var(--c-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chunks-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 0 12px 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.chunks-scroll::-webkit-scrollbar { width: 4px; }
.chunks-scroll::-webkit-scrollbar-thumb { background: var(--c-border); border-radius: 4px; }

.chunk-row {
  border: 1px solid var(--c-border);
  background: var(--c-surface2);
  border-radius: var(--radius-sm);
  padding: 10px 12px;
  cursor: pointer;
  transition: all .15s;
}

.chunk-row:hover {
  border-color: var(--c-border-hover);
  background: var(--c-surface3);
}

.chunk-row.active {
  border-color: var(--c-chunk-active-bd);
  background: var(--c-chunk-active);
}

.chunk-row-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 5px;
}

.chunk-number {
  width: 20px; height: 20px;
  border-radius: 6px;
  background: var(--c-surface3);
  border: 1px solid var(--c-border);
  font-size: 10px;
  font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  color: var(--c-text-muted);
  flex-shrink: 0;
}

.chunk-row.active .chunk-number {
  background: var(--c-accent-glow);
  border-color: var(--c-border-active);
  color: var(--c-accent);
}

.chunk-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--c-text);
  line-height: 1.3;
}

.chunk-snippet {
  font-size: 11px;
  color: var(--c-text-muted);
  line-height: 1.45;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.chunk-row-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: 6px;
}

.chunk-score {
  font-size: 10px;
  color: var(--c-accent);
  font-weight: 600;
  font-family: var(--font-mono);
}

.chunk-page-badge {
  margin-left: auto;
  font-size: 10px;
  font-weight: 700;
  font-family: var(--font-mono);
  color: var(--c-accent);
  background: var(--c-accent-glow);
  border: 1px solid var(--c-border-active);
  border-radius: 6px;
  padding: 1px 6px;
  letter-spacing: .03em;
}

.chunk-page-hint {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 10px;
  color: var(--c-text-muted);
  font-style: italic;
}

.chunk-row.active .chunk-page-hint {
  color: var(--c-accent);
}

/* Chunk preview section */
.chunk-preview-section {
  border-top: 1px solid var(--c-border);
  padding: 12px;
  flex-shrink: 0;
}

.chunk-preview-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  color: var(--c-text-muted);
  text-transform: uppercase;
  letter-spacing: .05em;
  margin-bottom: 8px;
}

.chunk-preview-label svg { color: var(--c-accent); }

.chunk-full-text {
  background: var(--c-surface2);
  border: 1px solid var(--c-border);
  border-radius: var(--radius-sm);
  padding: 10px 12px;
  font-size: 12px;
  line-height: 1.55;
  color: var(--c-text);
  white-space: pre-wrap;
  max-height: 120px;
  overflow-y: auto;
  font-family: var(--font-mono);
}

.chunk-full-text::-webkit-scrollbar { width: 3px; }
.chunk-full-text::-webkit-scrollbar-thumb { background: var(--c-border); border-radius: 3px; }

/* PDF col */
.modal-pdf-col {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.pdf-placeholder {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: var(--c-text-muted);
  font-size: 13px;
  opacity: .5;
}

.pdf-page-info {
  margin-left: auto;
  font-size: 11px;
  color: var(--c-text-muted);
  font-family: var(--font-mono);
  font-weight: 400;
}

/* Loading spinner */
.pdf-loading {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: var(--c-text-muted);
  font-size: 13px;
}

.pdf-spinner {
  width: 28px; height: 28px;
  border: 2px solid var(--c-border);
  border-top-color: var(--c-accent);
  border-radius: 50%;
  animation: pdfSpin .7s linear infinite;
}

@keyframes pdfSpin { to { transform: rotate(360deg); } }

/* Viewer wrap: scroll vertical */
.pdf-viewer-wrap {
  flex: 1;
  overflow-y: auto;
  overflow-x: auto;
  background: var(--c-surface2);
  padding: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.pdf-viewer-wrap::-webkit-scrollbar { width: 5px; }
.pdf-viewer-wrap::-webkit-scrollbar-thumb { background: var(--c-border); border-radius: 4px; }

/* Container das páginas renderizadas */
.pdf-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: center;
  width: 100%;
}

/* Cada página: canvas + textLayer sobrepostos */
.pdf-page-wrap {
  position: relative;
  box-shadow: 0 4px 16px rgba(0,0,0,.3);
  border-radius: 4px;
  overflow: hidden;
  background: #fff;
  flex-shrink: 0;
}

.pdf-page-wrap canvas {
  display: block;
}

/* Text layer — gerado pelo pdf.js */
.pdf-page-wrap .textLayer {
  position: absolute;
  inset: 0;
  overflow: hidden;
  line-height: 1;
  pointer-events: none; /* não bloqueia scroll */
}

.pdf-page-wrap .textLayer span {
  color: transparent;
  position: absolute;
  white-space: pre;
  cursor: text;
  transform-origin: 0% 0%;
}

/* Highlight dos spans que batem com o snippet */
.pdf-page-wrap .textLayer span.hl-chunk {
  background: rgba(34,211,238,.35);
  border-radius: 2px;
  pointer-events: none;
}

/* Highlight mais forte no span activo (primeiro match) */
.pdf-page-wrap .textLayer span.hl-chunk-primary {
  background: rgba(34,211,238,.55);
  box-shadow: 0 0 0 1px rgba(34,211,238,.5);
}

:root[data-theme="light"] .pdf-page-wrap .textLayer span.hl-chunk {
  background: rgba(8,145,178,.22);
}
:root[data-theme="light"] .pdf-page-wrap .textLayer span.hl-chunk-primary {
  background: rgba(8,145,178,.40);
  box-shadow: 0 0 0 1px rgba(8,145,178,.5);
}

/* ══════════════════════════════════════════════
   MARKDOWN TABLES
══════════════════════════════════════════════ */
.md-table-wrap {
  max-width: 100%; overflow-x: auto;
  border: 1px solid var(--c-border);
  border-radius: 10px;
  margin: 8px 0;
}

.md-table {
  width: 100%; border-collapse: collapse;
  font-size: .85rem; min-width: 480px;
}

.md-table th, .md-table td {
  padding: 8px 12px;
  border-bottom: 1px solid var(--c-border);
  vertical-align: top;
}

.md-table th {
  font-weight: 700;
  font-size: .75rem;
  letter-spacing: .05em;
  text-transform: uppercase;
  background: var(--c-surface2);
  color: var(--c-text-muted);
}

.md-table tr:last-child td { border-bottom: none; }
</style>

@push('scripts')
  @vite(['resources/js/pages/chat.js'])
@endpush

@endsection
