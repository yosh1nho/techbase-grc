@extends('layouts.app')
@section('title', 'Tratamento • Techbase GRC')

@section('content')
  <div class="card">
    <h3>Planos de tratamento (RF10, RF11)</h3>

    <div class="panel" style="margin-top:10px">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
        <div>
          <h2 style="margin-bottom:6px">Kanban de acompanhamento</h2>
          <p class="hint">Arrasta cartões entre colunas para atualizar estado. (mock)</p>
        </div>
        <div class="kpirow" style="flex-wrap:wrap; justify-content:flex-end;">
          <span class="chip">Total: <b id="kpiTotal">0</b></span>
          <span class="chip">To do: <b id="kpiTodo">0</b></span>
          <span class="chip">Em curso: <b id="kpiDoing">0</b></span>
          <span class="chip ok">Concluído: <b id="kpiDone">0</b></span>
          <span class="chip bad">Em atraso: <b id="kpiOverdue">0</b></span>
        </div>
      </div>
    </div>

    <div style="height:12px"></div>

    <div class="kanban">
      <div class="kanban-col" data-status="To do">
        <div class="kanban-col-head">
          <b>To do</b>
          <span class="chip" id="countTodo">0</span>
        </div>
        <div class="kanban-drop" id="colTodo"></div>
      </div>

      <div class="kanban-col" data-status="Em curso">
        <div class="kanban-col-head">
          <b>Em curso</b>
          <span class="chip" id="countDoing">0</span>
        </div>
        <div class="kanban-drop" id="colDoing"></div>
      </div>

      <div class="kanban-col" data-status="Concluído">
        <div class="kanban-col-head">
          <b>Concluído</b>
          <span class="chip ok" id="countDone">0</span>
        </div>
        <div class="kanban-drop" id="colDone"></div>
      </div>

      <div class="kanban-col" data-status="Em atraso">
        <div class="kanban-col-head">
          <b>Em atraso</b>
          <span class="chip bad" id="countOverdue">0</span>
        </div>
        <div class="kanban-drop" id="colOverdue"></div>
      </div>
    </div>

    {{-- Modal de detalhes (opcional, mas ajuda RF11) --}}
    <div id="treatDetailModal" class="modal-overlay is-hidden" aria-hidden="true">
      <div class="modal-card" role="dialog" aria-modal="true">
        <div class="modal-header">
          <div>
            <div class="muted" style="margin-bottom:4px">Plano</div>
            <div style="font-size:18px;font-weight:800" id="td_title">Detalhes</div>
          </div>
          <button id="td_close" class="btn" type="button">Fechar</button>
        </div>

        <div style="height:10px"></div>

        <div class="panel">
          <div class="two">
            <div class="field"><label>Alerta</label><input id="td_alert" disabled></div>
            <div class="field"><label>Ativo</label><input id="td_asset" disabled></div>
          </div>

          <div class="two">
            <div class="field"><label>Responsável</label><input id="td_owner"></div>
            <div class="field"><label>Prazo</label><input id="td_due" placeholder="YYYY-MM-DD"></div>
          </div>

          <div class="two">
            <div class="field"><label>Prioridade</label>
              <select id="td_priority">
                <option>Alta</option><option>Média</option><option>Baixa</option>
              </select>
            </div>
            <div class="field"><label>Estado</label>
              <select id="td_status">
                <option>To do</option><option>Em curso</option><option>Concluído</option><option>Em atraso</option>
              </select>
            </div>
          </div>

          <div class="field">
            <label>Descrição do plano (o que será feito)</label>
            <textarea id="td_desc" rows="4"></textarea>
          </div>

          <div class="field">
            <label>Evidência (RF11) — mock</label>
            <input id="td_evidence" placeholder="ex.: link/arquivo/nota">
            <p class="hint">No futuro: upload + vínculo com documentos/evidências.</p>
          </div>

          <div style="display:flex; gap:10px; justify-content:flex-end">
            <button id="td_save" class="btn warn" type="button">Guardar</button>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection

@push('scripts')
  @vite(['resources/js/pages/treatment.js'])
@endpush
