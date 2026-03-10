@extends('layouts.app')
@section('title', 'Tratamento • Techbase GRC')

@section('content')

  {{-- ══ KPI STRIP ══ --}}
  <div class="treat-kpi-strip">
    <div class="treat-kpi">
      <div class="treat-kpi-num" id="kpiTotal">0</div>
      <div class="treat-kpi-label">Total</div>
    </div>
    <div class="treat-kpi treat-kpi-overdue">
      <div class="treat-kpi-num" id="kpiOverdue">0</div>
      <div class="treat-kpi-label">Em atraso</div>
    </div>
    <div class="treat-kpi treat-kpi-doing">
      <div class="treat-kpi-num" id="kpiDoing">0</div>
      <div class="treat-kpi-label">Em curso</div>
    </div>
    <div class="treat-kpi treat-kpi-todo">
      <div class="treat-kpi-num" id="kpiTodo">0</div>
      <div class="treat-kpi-label">To do</div>
    </div>
    <div class="treat-kpi treat-kpi-done">
      <div class="treat-kpi-num" id="kpiDone">0</div>
      <div class="treat-kpi-label">Concluído</div>
    </div>

    {{-- barra de progresso geral --}}
    <div class="treat-kpi-progress">
      <div class="treat-kpi-progress-label">
        <span class="muted" style="font-size:12px">Progresso geral</span>
        <span style="font-size:12px;font-weight:700" id="kpiProgressPct">0%</span>
      </div>
      <div class="treat-progress-bar">
        <div id="treatProgDone"    class="treat-prog-seg" style="background:#34d399;width:0%"></div>
        <div id="treatProgDoing"   class="treat-prog-seg" style="background:#60a5fa;width:0%"></div>
        <div id="treatProgTodo"    class="treat-prog-seg" style="background:#94a3b8;width:0%"></div>
        <div id="treatProgOverdue" class="treat-prog-seg" style="background:#f87171;width:0%"></div>
      </div>
      <div style="display:flex;gap:14px;font-size:11px;margin-top:6px">
        <span style="display:flex;align-items:center;gap:4px">
          <span style="width:7px;height:7px;border-radius:50%;background:#34d399;display:inline-block"></span>
          <span class="muted">Feito</span>
        </span>
        <span style="display:flex;align-items:center;gap:4px">
          <span style="width:7px;height:7px;border-radius:50%;background:#60a5fa;display:inline-block"></span>
          <span class="muted">Em curso</span>
        </span>
        <span style="display:flex;align-items:center;gap:4px">
          <span style="width:7px;height:7px;border-radius:50%;background:#94a3b8;display:inline-block"></span>
          <span class="muted">To do</span>
        </span>
        <span style="display:flex;align-items:center;gap:4px">
          <span style="width:7px;height:7px;border-radius:50%;background:#f87171;display:inline-block"></span>
          <span class="muted">Atraso</span>
        </span>
      </div>
    </div>
  </div>

  {{-- ══ KANBAN ══ --}}
  <div class="card" style="margin-top:16px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:16px">
      <div>
        <h3 style="margin:0">Planos de tratamento <span class="muted" style="font-weight:400;font-size:13px">(RF10 · RF11)</span></h3>
        <p class="muted" style="font-size:12px;margin:3px 0 0">Arrasta os cartões entre colunas para actualizar o estado.</p>
      </div>

      {{-- Filtros --}}
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input id="treatSearch" placeholder="Pesquisar plano, ativo, owner..."
          style="padding:7px 12px;border-radius:10px;font-size:13px;min-width:220px;
                 background:var(--input-bg);border:1px solid var(--border);color:inherit" />
        <select id="treatPriorityFilter"
          style="padding:7px 12px;border-radius:10px;font-size:13px;background:var(--input-bg);border:1px solid var(--border);color:inherit">
          <option value="all">Todas as prioridades</option>
          <option value="Alta">Alta</option>
          <option value="Média">Média</option>
          <option value="Baixa">Baixa</option>
        </select>
        <select id="treatOwnerFilter"
          style="padding:7px 12px;border-radius:10px;font-size:13px;background:var(--input-bg);border:1px solid var(--border);color:inherit">
          <option value="all">Todos os owners</option>
        </select>
      </div>
    </div>

    <div class="kanban">
      <div class="kanban-col" data-status="To do">
        <div class="kanban-col-head">
          <div style="display:flex;align-items:center;gap:7px">
            <span class="treat-col-dot" style="background:#94a3b8"></span>
            <b>To do</b>
          </div>
          <span class="chip" id="countTodo">0</span>
        </div>
        <div class="kanban-drop" id="colTodo"></div>
      </div>

      <div class="kanban-col" data-status="Em curso">
        <div class="kanban-col-head">
          <div style="display:flex;align-items:center;gap:7px">
            <span class="treat-col-dot" style="background:#60a5fa"></span>
            <b>Em curso</b>
          </div>
          <span class="chip" id="countDoing">0</span>
        </div>
        <div class="kanban-drop" id="colDoing"></div>
      </div>

      <div class="kanban-col" data-status="Concluído">
        <div class="kanban-col-head">
          <div style="display:flex;align-items:center;gap:7px">
            <span class="treat-col-dot" style="background:#34d399"></span>
            <b>Concluído</b>
          </div>
          <span class="chip ok" id="countDone">0</span>
        </div>
        <div class="kanban-drop" id="colDone"></div>
      </div>

      <div class="kanban-col" data-status="Em atraso">
        <div class="kanban-col-head">
          <div style="display:flex;align-items:center;gap:7px">
            <span class="treat-col-dot" style="background:#f87171"></span>
            <b>Em atraso</b>
          </div>
          <span class="chip bad" id="countOverdue">0</span>
        </div>
        <div class="kanban-drop" id="colOverdue"></div>
      </div>
    </div>
  </div>

  {{-- ══ MODAL DE DETALHES ══ --}}
  <div id="treatDetailModal" class="modal-overlay is-hidden" aria-hidden="true">
    <div class="modal-card treat-detail-modal" role="dialog" aria-modal="true">

      {{-- Header --}}
      <div class="treat-modal-header">
        <div>
          <div class="treat-modal-eyebrow">Plano de tratamento</div>
          <div class="treat-modal-title" id="td_title">—</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <span id="td_status_badge" class="treat-status-badge tsb-todo">—</span>
          <button id="td_close" class="btn" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>

      {{-- Context strip --}}
      <div class="treat-context-strip">
        <div class="treat-ctx-item">
          <div class="treat-ctx-label">Ativo</div>
          <div class="treat-ctx-val" id="td_asset_disp">—</div>
        </div>
        <div class="treat-ctx-item">
          <div class="treat-ctx-label">Risco identificado</div>
          <div class="treat-ctx-val" id="td_risk_disp">—</div>
        </div>
        <div class="treat-ctx-item">
          <div class="treat-ctx-label">Fonte / Alerta</div>
          <div class="treat-ctx-val" id="td_source_disp">—</div>
        </div>
        <div class="treat-ctx-item">
          <div class="treat-ctx-label">Criado em</div>
          <div class="treat-ctx-val" id="td_created_disp">—</div>
        </div>
      </div>

      {{-- Corpo 2 colunas --}}
      <div class="treat-modal-body">

        {{-- Col esquerda: IA + descrição + evidência --}}
        <div class="treat-modal-col">

          <div class="treat-ai-box">
            <div class="treat-ai-header">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
              Ações sugeridas pela IA
            </div>
            <div id="td_ai_actions" class="treat-ai-steps"></div>
          </div>

          <div class="field" style="margin-top:14px">
            <label>Descrição do plano <span class="muted" style="font-weight:400">(o que será feito)</span></label>
            <textarea id="td_desc" rows="4"></textarea>
          </div>

          <div class="field" style="margin-top:10px">
            <label>
              Evidência <span style="font-size:10px;font-weight:700;color:#60a5fa;letter-spacing:.05em">RF11</span>
              <span class="muted" style="font-weight:400;font-size:11px"> — link, nota ou ref. a documento</span>
            </label>
            <input id="td_evidence" placeholder="ex.: Relatório EDR v2, link SharePoint..." />
          </div>
        </div>

        {{-- Col direita: campos editáveis --}}
        <div class="treat-modal-col">

          <div class="field">
            <label>Responsável (owner)</label>
            <input id="td_owner" placeholder="ex.: IT Ops, SecOps, Network..." />
          </div>

          <div class="field">
            <label>Prazo</label>
            <input id="td_due" type="date" />
          </div>

          {{-- Indicador de deadline --}}
          <div id="td_deadline_box" class="treat-deadline-box" style="display:none">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span id="td_deadline_msg"></span>
          </div>

          <div class="field">
            <label>Prioridade</label>
            <select id="td_priority">
              <option>Alta</option>
              <option>Média</option>
              <option>Baixa</option>
            </select>
          </div>

          <div class="field">
            <label>Estado</label>
            <select id="td_status">
              <option>To do</option>
              <option>Em curso</option>
              <option>Concluído</option>
              <option>Em atraso</option>
            </select>
          </div>

          <div class="field">
            <label>Estratégia de tratamento</label>
            <select id="td_strategy">
              <option>Mitigar</option>
              <option>Aceitar</option>
              <option>Transferir</option>
              <option>Evitar</option>
            </select>
          </div>
        </div>
      </div>

      {{-- Footer --}}
      <div class="treat-modal-footer">
        <span class="muted" style="font-size:12px" id="td_alert_ref">—</span>
        <div style="display:flex;gap:8px">
          <button id="td_close2" class="btn" type="button">Cancelar</button>
          <button id="td_save" class="btn primary" type="button">Guardar alterações</button>
        </div>
      </div>
    </div>
  </div>

  <style>
    /* ── KPI Strip ── */
    .treat-kpi-strip {
      display:flex; gap:0; align-items:stretch;
      background:var(--card-bg); border:1px solid var(--border);
      border-radius:16px; overflow:hidden;
    }
    .treat-kpi {
      flex:1; padding:18px 16px; text-align:center;
      border-right:1px solid var(--border);
    }
    .treat-kpi:last-child { border-right:none; }
    .treat-kpi-num   { font-size:30px; font-weight:900; line-height:1; }
    .treat-kpi-label { font-size:11px; color:var(--muted); margin-top:4px; }
    .treat-kpi-overdue .treat-kpi-num { color:#f87171; }
    .treat-kpi-doing   .treat-kpi-num { color:#60a5fa; }
    .treat-kpi-done    .treat-kpi-num { color:#34d399; }
    .treat-kpi-progress {
      flex:2.5; padding:16px 20px;
      display:flex; flex-direction:column; justify-content:center; gap:7px;
      border-left:1px solid var(--border);
    }
    .treat-kpi-progress-label { display:flex; justify-content:space-between; }
    .treat-progress-bar {
      display:flex; height:8px; border-radius:99px; overflow:hidden; gap:2px;
    }
    .treat-prog-seg { border-radius:99px; transition:width .5s; min-width:0; }

    /* ── Coluna dot ── */
    .treat-col-dot {
      width:9px; height:9px; border-radius:50%;
      display:inline-block; flex-shrink:0;
    }

    /* ── Kanban card melhorado ── */
    .kcard { position:relative; overflow:hidden; }
    .kcard-urgency-bar {
      position:absolute; left:0; top:0; bottom:0;
      width:3px;
    }
    .kcard-due-badge {
      font-size:10px; font-weight:700; padding:2px 8px;
      border-radius:99px; display:inline-flex; align-items:center; gap:4px;
    }
    .kcard-due-overdue { background:rgba(248,113,113,.12); color:#f87171; }
    .kcard-due-soon    { background:rgba(251,191,36,.1);   color:#fbbf24; }
    .kcard-due-ok      { background:rgba(52,211,153,.08);  color:#34d399; }
    .kcard-owner {
      display:inline-flex; align-items:center; gap:5px;
      font-size:11px; color:var(--muted);
    }
    .kcard-owner-avatar {
      width:18px; height:18px; border-radius:50%;
      background:var(--border); display:flex; align-items:center;
      justify-content:center; font-size:9px; font-weight:800; flex-shrink:0;
    }

    /* ── Modal ── */
    .treat-detail-modal {
      padding:0; display:flex; flex-direction:column;
      max-width:880px; width:100%; max-height:90vh;
    }
    .treat-modal-header {
      display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
      padding:20px 24px 16px; border-bottom:1px solid var(--modal-border);
    }
    .treat-modal-eyebrow {
      font-size:10px; font-weight:700; letter-spacing:.08em;
      text-transform:uppercase; color:var(--muted); margin-bottom:4px;
    }
    .treat-modal-title { font-size:20px; font-weight:800; }

    .treat-status-badge {
      font-size:11px; font-weight:700; padding:4px 12px;
      border-radius:99px; letter-spacing:.04em;
    }
    .tsb-todo    { background:rgba(148,163,184,.12); color:#94a3b8; }
    .tsb-doing   { background:rgba(96,165,250,.12);  color:#60a5fa; }
    .tsb-done    { background:rgba(52,211,153,.1);   color:#34d399; }
    .tsb-overdue { background:rgba(248,113,113,.12); color:#f87171; }

    .treat-context-strip {
      display:flex; border-bottom:1px solid var(--modal-border);
    }
    .treat-ctx-item {
      flex:1; padding:11px 20px;
      border-right:1px solid var(--modal-border);
    }
    .treat-ctx-item:last-child { border-right:none; }
    .treat-ctx-label {
      font-size:9px; font-weight:700; letter-spacing:.07em;
      text-transform:uppercase; color:var(--muted); margin-bottom:3px;
    }
    .treat-ctx-val { font-size:12px; font-weight:600; }

    .treat-modal-body {
      display:grid; grid-template-columns:1fr 1fr;
      overflow-y:auto; flex:1;
    }
    .treat-modal-col { padding:20px 24px; }
    .treat-modal-col:first-child { border-right:1px solid var(--modal-border); }

    .treat-ai-box {
      background:rgba(96,165,250,.05);
      border:1px solid rgba(96,165,250,.18);
      border-radius:12px; padding:12px 14px;
    }
    .treat-ai-header {
      display:flex; align-items:center; gap:6px;
      font-size:10px; font-weight:700; letter-spacing:.07em;
      text-transform:uppercase; color:#60a5fa; margin-bottom:10px;
    }
    .treat-ai-steps { display:flex; flex-direction:column; gap:8px; }
    .treat-ai-step  { display:flex; align-items:flex-start; gap:9px; font-size:13px; line-height:1.45; }
    .treat-ai-step-num {
      flex-shrink:0; width:19px; height:19px; border-radius:50%;
      background:rgba(96,165,250,.15); color:#60a5fa;
      font-size:10px; font-weight:800;
      display:flex; align-items:center; justify-content:center; margin-top:1px;
    }

    .treat-deadline-box {
      display:flex; align-items:center; gap:7px;
      padding:8px 12px; border-radius:10px;
      font-size:12px; font-weight:600; margin-bottom:10px;
    }
    .treat-deadline-box.overdue { background:rgba(248,113,113,.09); color:#f87171; border:1px solid rgba(248,113,113,.2); }
    .treat-deadline-box.soon    { background:rgba(251,191,36,.07);  color:#fbbf24; border:1px solid rgba(251,191,36,.2); }
    .treat-deadline-box.ok      { background:rgba(52,211,153,.06);  color:#34d399; border:1px solid rgba(52,211,153,.18); }

    .treat-modal-footer {
      display:flex; align-items:center; justify-content:space-between; gap:12px;
      padding:14px 24px; border-top:1px solid var(--modal-border);
    }

    @media (max-width:680px) {
      .treat-modal-body { grid-template-columns:1fr; }
      .treat-modal-col:first-child { border-right:none; border-bottom:1px solid var(--modal-border); }
      .treat-kpi-strip { flex-wrap:wrap; }
      .treat-context-strip { flex-wrap:wrap; }
    }
  </style>

@endsection
@push('scripts')
  @vite(['resources/js/pages/treatment.js'])
@endpush
