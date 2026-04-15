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

      {{-- Tab nav --}}
      <div class="treat-tab-nav">
        <button class="treat-tab-btn active" data-tab="details" type="button">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Detalhes
        </button>
        <button class="treat-tab-btn" data-tab="tasks" type="button">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          Tarefas
          <span class="treat-tab-count" id="td_task_count">0</span>
        </button>
      </div>

      {{-- ── TAB: DETALHES ── --}}
      <div class="treat-tab-panel" id="tabPanel_details">
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
      </div>

      {{-- ── TAB: TAREFAS ── --}}
      <div class="treat-tab-panel is-hidden" id="tabPanel_tasks">
        <div class="tasks-panel">

          {{-- Barra de progresso das tasks --}}
          <div class="tasks-progress-bar-wrap" id="td_tasks_progress_wrap">
            <div class="tasks-progress-label">
              <span class="muted" style="font-size:11px">Progresso das tarefas</span>
              <span style="font-size:11px;font-weight:700" id="td_tasks_pct">0%</span>
            </div>
            <div class="tasks-progress-track">
              <div class="tasks-progress-fill" id="td_tasks_progress_fill" style="width:0%"></div>
            </div>
          </div>

          {{-- Botão nova task --}}
          <div style="display:flex;justify-content:flex-end;padding:0 20px 12px">
            <button class="btn primary small" id="td_btn_new_task" type="button">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Nova tarefa
            </button>
          </div>

          {{-- Lista de tasks --}}
          <div id="td_tasks_list" class="tasks-list"></div>

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

  {{-- ══ MODAL: CRIAR TAREFA ══ --}}
  <div id="newTaskModal" class="modal-overlay is-hidden" aria-hidden="true">
    <div class="modal-card new-task-modal" role="dialog" aria-modal="true">
      <div class="ntm-header">
        <div>
          <div class="treat-modal-eyebrow">Nova tarefa</div>
          <div class="ntm-title" id="ntm_plan_label">—</div>
        </div>
        <button class="btn small" type="button" id="ntm_close">✕</button>
      </div>

      <div class="ntm-body">
        <div class="ntm-field">
          <label class="ntm-label">Título <span style="color:var(--bad)">*</span></label>
          <input id="tf_title" class="ntm-input" placeholder="Ex.: Verificar estado do backup e alertas..." />
        </div>

        <div class="ntm-field">
          <label class="ntm-label">Descrição <span class="ntm-optional">opcional</span></label>
          <textarea id="tf_desc" class="ntm-textarea" rows="3" placeholder="Detalha o que deve ser feito, como e onde..."></textarea>
        </div>

        <div class="ntm-row">
          <div class="ntm-field">
            <label class="ntm-label">Designado a</label>
            <select id="tf_assigned" class="ntm-input">
              <option value="">Sem designado</option>
            </select>
          </div>
          <div class="ntm-field">
            <label class="ntm-label">Prazo</label>
            <input id="tf_due" type="date" class="ntm-input" />
          </div>
          <div class="ntm-field">
            <label class="ntm-label">Status inicial</label>
            <select id="tf_status" class="ntm-input">
              <option value="To do">To do</option>
              <option value="Em curso">Em curso</option>
              <option value="Concluído">Concluído</option>
            </select>
          </div>
        </div>
      </div>

      <div class="ntm-footer">
        <button type="button" class="btn small" id="tf_cancel">Cancelar</button>
        <button type="button" class="btn primary small" id="tf_save">Criar tarefa</button>
      </div>
    </div>
  </div>

  {{-- ══ MODAL DE TAREFA (detalhe + comentários) ══ --}}
  <div id="taskDetailModal" class="modal-overlay is-hidden" aria-hidden="true">
    <div class="modal-card task-detail-modal" role="dialog" aria-modal="true">

      {{-- Header --}}
      <div class="task-modal-header">
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">
            <button id="tkm_back" class="btn small task-back-btn" type="button">
              <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
              Voltar ao plano
            </button>
            <span class="treat-modal-eyebrow" style="margin-bottom:0" id="tkm_plan_ref">—</span>
          </div>
          <div class="task-modal-title" id="tkm_title">—</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-shrink:0">
          <select id="tkm_status" class="task-status-select">
            <option value="To do">To do</option>
            <option value="Em curso">Em curso</option>
            <option value="Concluído">Concluído</option>
          </select>
          <button id="tkm_close" class="btn" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>

      {{-- Body 2 col --}}
      <div class="task-modal-body">

        {{-- Col esquerda: meta + comentários --}}
        <div class="task-modal-main">

          {{-- Meta strip --}}
          <div class="task-meta-strip">
            <div class="task-meta-item">
              <span class="task-meta-label">Designado</span>
              <span class="task-meta-val" id="tkm_assigned">—</span>
            </div>
            <div class="task-meta-item">
              <span class="task-meta-label">Prazo</span>
              <span class="task-meta-val" id="tkm_due">—</span>
            </div>
            <div class="task-meta-item">
              <span class="task-meta-label">Criada</span>
              <span class="task-meta-val" id="tkm_created">—</span>
            </div>
          </div>

          {{-- Descrição --}}
          <div id="tkm_desc_wrap" class="task-desc-wrap">
            <div class="task-section-label">Descrição</div>
            <div id="tkm_desc" class="task-desc-text">—</div>
          </div>

          {{-- Thread de comentários --}}
          <div class="task-section-label" style="padding:0 20px;margin-bottom:8px">
            Comentários
            <span class="treat-tab-count" id="tkm_comment_count">0</span>
          </div>

          <div id="tkm_comments_list" class="task-comments-list"></div>

          {{-- Composer --}}
          <div class="task-composer">
            <div class="task-composer-avatar" id="tkm_composer_avatar">U</div>
            <div class="task-composer-right">
              <textarea id="tkm_comment_input" rows="2" placeholder="Adicionar um comentário ou update..."></textarea>

              {{-- Anexo preview --}}
              <div id="tkm_attach_preview" class="task-attach-preview is-hidden"></div>

              <div class="task-composer-actions">
                <label class="btn small task-attach-btn" title="Anexar ficheiro">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                  Anexar
                  <input type="file" id="tkm_file_input" multiple style="display:none" />
                </label>
                <button type="button" class="btn primary small" id="tkm_send_comment">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                  Enviar
                </button>
              </div>
            </div>
          </div>
        </div>

        {{-- Col direita: editar meta --}}
        <div class="task-modal-sidebar">
          <div class="task-sidebar-label">Editar tarefa</div>

          <div class="field">
            <label>Título</label>
            <input id="tkm_edit_title" />
          </div>
          <div class="field">
            <label>Descrição</label>
            <textarea id="tkm_edit_desc" rows="3"></textarea>
          </div>
          <div class="field">
            <label>Designado</label>
            <select id="tkm_edit_assigned">
              <option value="">Sem designado</option>
            </select>
          </div>
          <div class="field">
            <label>Prazo</label>
            <input id="tkm_edit_due" type="date" />
          </div>

          <div style="display:flex;gap:8px;margin-top:12px">
            <button type="button" class="btn primary small" id="tkm_save_meta" style="flex:1">Guardar</button>
            <button type="button" class="btn small tkm-delete-btn" id="tkm_delete_task" title="Eliminar tarefa">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Toast de confirmação --}}
  <div id="treat_toast" class="treat-toast is-hidden"></div>
@vite(['resources/css/pages/treatment_tasks.css'])

@push('styles')
@vite(['resources/css/pages/treatment.css'])
@endpush
@endsection
@push('scripts')
  @vite(['resources/js/pages/treatment.js'])
@endpush
