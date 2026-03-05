@extends('layouts.app')
@section('title', 'Chat Governança • Techbase GRC')

@section('content')
<section id="page-chat" class="page">
  <div class="card">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap">
      <div>
        <h3>Chat de governação (RF14, RF15)</h3>
        <div class="muted">Respostas baseadas em documentos oficiais + evidências internas (mock RAG).</div>
      </div>
      <div class="kpirow">
        <span class="chip">Logs: <b id="chatAuditChip">OK</b></span>
        <span class="chip">Fontes: <b id="chatSourcesChip">0</b></span>
      </div>
    </div>

    <div class="two" style="margin-top:10px">
      {{-- ESQ: Chat --}}
      <div class="panel">
        <h2>Conversa</h2>

        <div id="chatThread" class="chat-thread panel" style="background:rgba(0,0,0,.18)">
          {{-- seed mock --}}
          <div class="chat-msg user">
            <div class="chat-meta muted"><b>Utilizador</b> • agora</div>
            <div class="chat-bubble">
              O que falta para cumprir o ID.AR-1?
            </div>
          </div>

          <div class="chat-msg bot">
            <div class="chat-meta muted"><b>Sistema</b> • agora</div>
            <div class="chat-bubble">
              Não encontrei evidência de análise formal no último ano. Recomendo iniciar a “Calculadora de Risco”, anexar relatório e aprovar.
              <div style="height:10px"></div>
              <div class="kpirow">
                <span class="chip">Docs usados: <b>0</b></span>
                <span class="chip">Normas: <b>QNRCS</b></span>
              </div>
            </div>
          </div>
        </div>

        <div style="height:10px"></div>

        <div class="row">
          <input id="chatInput" style="flex:1" placeholder="Pergunta ao chat (RAG)..." />
          <button id="chatSend" class="btn primary" type="button">Enviar</button>
        </div>

        <p class="hint">
          Mock RNF5: ao enviar, registaria auditoria (pergunta, resposta, user, timestamp, documentos usados).
        </p>
      </div>

      {{-- DIR: Fontes usadas --}}
      <div class="panel">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px">
          <div>
            <h2 style="margin-bottom:6px">Fontes usadas na resposta</h2>
            <p class="muted">Documentos + trechos (chunks) que suportam a resposta do chat.</p>
          </div>
          <button id="btnClearSources" class="btn" type="button">Limpar</button>
        </div>

        <div style="height:10px"></div>

        <div id="sourcesEmpty" class="muted" style="padding:12px; border:1px solid rgba(255,255,255,.08); border-radius:12px; background:rgba(0,0,0,.12)">
          Faz uma pergunta para ver as fontes usadas aqui.
        </div>

        <div id="sourcesList" class="sources-list" style="display:none"></div>
        <div id="sourceInline" style="display:none; margin-top:12px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
          <div>
            <div class="muted" style="margin-bottom:4px;">Documento usado na resposta</div>
            <div id="sourceInlineTitle" style="font-size:16px; font-weight:900;">—</div>
          </div>
          <button id="sourceInlineClose" class="btn" type="button">Fechar</button>
        </div>

        <div class="two" style="margin-top:12px;">
          <div class="panel">
            <h2>Chunks citados</h2>
            <p class="muted">Lista de referências/chunks usados.</p>
            <div id="sourceInlineChunks" class="chunklist"></div>
          </div>

          <div class="panel">
            <h2>Pré-visualização (PDF)</h2>
            <iframe id="sourceInlinePdf" src=""
              style="width:100%; height:520px; border:1px solid rgba(255,255,255,.10); border-radius:12px; background:rgba(0,0,0,.12)">
            </iframe>
          </div>
        </div>
      </div>
        <div class="hint" style="margin-top:10px">
          Ao clicar numa fonte, abre o modal com PDF + lista de chunks usados.
        </div>
      </div>
    </div>
  </div>
</section>

{{-- MODAL: Fonte usada (PDF + chunks) --}}
<div id="sourceModal" class="modal-overlay" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="sourceModalTitle">
    <div class="modal-header">
      <div>
        <div class="muted" style="margin-bottom:4px">Documento usado na resposta</div>
        <div id="sourceModalTitle" style="font-size:18px;font-weight:800">—</div>
      </div>
      <button id="sourceModalClose" class="btn" type="button">Fechar</button>
    </div>

    <div class="two" style="margin-top:12px">
      <div class="panel">
        <h2>Chunks citados</h2>
        <p class="muted">Clica num chunk para destacar e ver o trecho completo.</p>

        <div id="sourceChunksList" class="chunklist"></div>

        <div style="height:10px"></div>
        <div class="muted" style="margin-bottom:6px">Trecho selecionado</div>
        <div id="chunkFullText" class="chunk-preview">—</div>
      </div>

      <div class="panel">
        <h2>Pré-visualização (PDF)</h2>
        <iframe id="sourcePdf" src=""
          style="width:100%; height:520px; border:1px solid rgba(255,255,255,.10); border-radius:12px; background:rgba(0,0,0,.12)">
        </iframe>
        <div class="hint" style="margin-top:8px">
          Dica: garante que o PDF está em <b>public/mock/...</b> e acessível via URL.
        </div>
      </div>
    </div>

    <div id="pdfModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:9999;">
      <div style="background:#0b1220; width:min(1100px,95vw); height:min(85vh,95vh); margin:5vh auto; border-radius:16px; overflow:hidden; border:1px solid rgba(255,255,255,.12);">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; border-bottom:1px solid rgba(255,255,255,.12);">
          <div id="pdfTitle" style="color:#e5e7eb; font-weight:600;">Documento</div>
          <button id="pdfClose" style="padding:8px 10px; border-radius:10px; background:rgba(255,255,255,.08); color:#e5e7eb; border:0; cursor:pointer;">Fechar</button>
        </div>
        <iframe id="pdfFrame" style="width:100%; height:calc(100% - 48px); border:0;"></iframe>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.11/dist/purify.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>


<style>
  /* ── Tokens adaptativos claro/escuro ──
     O app.blade.php usa :root sem data-theme = DARK (default)
     e :root[data-theme="light"] = LIGHT
     Seguimos a mesma convenção aqui.
  ── */

  /* DEFAULT = dark (igual ao app) */
  :root {
    --chat-border:       rgba(255,255,255,.10);
    --chat-bubble-bot:   rgba(255,255,255,.06);
    --chat-bubble-user-bg:  rgba(34,211,238,.10);
    --chat-bubble-user-bd:  rgba(34,211,238,.18);
    --chat-thread-bg:    rgba(0,0,0,.18);
    --chat-source-bg:    rgba(255,255,255,.04);
    --chat-source-hover: rgba(96,165,250,.06);
    --chat-source-bd:    rgba(255,255,255,.10);
    --chat-chunk-bg:     rgba(255,255,255,.04);
    --chat-chunk-preview-bg:   rgba(0,0,0,.18);
    --chat-chunk-preview-color: rgba(255,255,255,.85);
    --chat-table-border: rgba(255,255,255,.10);
    --chat-table-bg:     rgba(0,0,0,.10);
    --chat-table-head-bg:rgba(255,255,255,.05);
    --chat-table-row-bd: rgba(255,255,255,.07);
    --modal-header-bd:   rgba(255,255,255,.06);
  }

  /* LIGHT — ativado pelo app quando data-theme="light" está no :root */
  :root[data-theme="light"] {
    --chat-border:       rgba(15,23,42,.12);
    --chat-bubble-bot:   rgba(15,23,42,.04);
    --chat-bubble-user-bg:  rgba(6,182,212,.09);
    --chat-bubble-user-bd:  rgba(6,182,212,.28);
    --chat-thread-bg:    rgba(15,23,42,.03);
    --chat-source-bg:    rgba(15,23,42,.03);
    --chat-source-hover: rgba(37,99,235,.06);
    --chat-source-bd:    rgba(15,23,42,.10);
    --chat-chunk-bg:     rgba(15,23,42,.03);
    --chat-chunk-preview-bg:   rgba(15,23,42,.04);
    --chat-chunk-preview-color: var(--text);
    --chat-table-border: rgba(15,23,42,.10);
    --chat-table-bg:     rgba(15,23,42,.02);
    --chat-table-head-bg:rgba(15,23,42,.05);
    --chat-table-row-bd: rgba(15,23,42,.07);
    --modal-header-bd:   rgba(15,23,42,.08);
  }

  /* ── Modal ── */
  .modal-overlay{
    position:fixed; inset:0; background:var(--modal-overlay, rgba(0,0,0,.55));
    display:none; align-items:center; justify-content:center; padding:18px; z-index:99999;
  }
  .modal-overlay.open{ display:flex; }
  .modal-card{
    width:min(1200px,96vw); max-height:90vh; overflow:auto;
    border:1px solid var(--modal-border, var(--chat-border)); border-radius:16px;
    background:var(--modal-bg, var(--surface, #fff)); color:var(--text);
    box-shadow:0 30px 60px rgba(0,0,0,.35); padding:14px;
  }
  .modal-header{
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding-bottom:12px; border-bottom:1px solid var(--modal-header-bd);
  }

  /* ── Chat thread ── */
  .chat-thread{
    min-height:420px; max-height:60vh; overflow:auto; border-radius:14px;
    background: var(--chat-thread-bg) !important;
  }
  .chat-msg{ display:flex; flex-direction:column; gap:6px; margin:10px 0; }
  .chat-msg.user{ align-items:flex-end; }
  .chat-msg.bot { align-items:flex-start; }

  .chat-bubble{
    max-width:92%; padding:12px;
    border-radius:14px;
    border:1px solid var(--chat-border);
    background: var(--chat-bubble-bot);
    line-height:1.5;
    overflow-wrap:anywhere; word-break:break-word;
    overflow-x:auto;
  }
  .chat-msg.user .chat-bubble{
    background: var(--chat-bubble-user-bg);
    border-color: var(--chat-bubble-user-bd);
  }
  .chat-bubble pre, .chat-bubble code{ white-space:pre-wrap; }
  .chat-bubble table{ display:block; max-width:100%; overflow-x:auto; }

  /* ── Sources panel ── */
  .sources-list{ display:flex; flex-direction:column; gap:10px; }
  .source-item{
    border:1px solid var(--chat-source-bd);
    background: var(--chat-source-bg);
    border-radius:14px; padding:12px;
    display:flex; justify-content:space-between; gap:12px; align-items:flex-start;
    cursor:pointer; transition: background .15s, border-color .15s;
  }
  .source-item:hover{
    border-color:rgba(59,130,246,.35);
    background: var(--chat-source-hover);
  }
  .source-left{ display:flex; flex-direction:column; gap:6px; }
  .source-title{ font-weight:900; }
  .source-sub{ color:var(--muted); font-size:12px; }
  .source-chips{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }

  /* ── Chunks ── */
  .chunklist{ display:flex; flex-direction:column; gap:10px; }
  .chunkrow{
    border:1px solid var(--chat-source-bd);
    background: var(--chat-chunk-bg);
    border-radius:12px; padding:10px; cursor:pointer;
    transition: background .15s, border-color .15s;
  }
  .chunkrow.active{
    border-color:rgba(34,211,238,.35);
    background:rgba(34,211,238,.08);
  }
  .chunk-preview{
    border:1px solid var(--chat-source-bd);
    background: var(--chat-chunk-preview-bg);
    border-radius:12px; padding:10px;
    color: var(--chat-chunk-preview-color);
    font-size:13px; line-height:1.45; white-space:pre-wrap;
  }

  /* ── Markdown tables ── */
  .md-table-wrap{
    max-width:100%; overflow-x:auto;
    border:1px solid var(--chat-table-border);
    border-radius:12px;
    background: var(--chat-table-bg);
    margin: 8px 0;
  }
  .md-table{
    width:100%; border-collapse:collapse;
    font-size:0.9rem; min-width:560px;
  }
  .md-table th, .md-table td{
    padding:9px 12px;
    border-bottom:1px solid var(--chat-table-row-bd);
    vertical-align:top; white-space:normal; word-break:break-word;
  }
  .md-table th{
    font-weight:700; font-size:.8rem; letter-spacing:.04em; text-transform:uppercase;
    background: var(--chat-table-head-bg);
  }
  .md-table tr:last-child td{ border-bottom:none; }
</style>

@push('scripts')
  @vite(['resources/js/pages/chat.js'])
@endpush

@endsection

{{-- tema gerido por CSS :root[data-theme] — sem JS necessário --}}
