@extends('layouts.app')
@section('title', 'Questionário • Techbase GRC')

@section('content')
<div class="card">
  <h3>Questionário estruturado (RF13)</h3>

  <div class="panel" style="margin-top:10px">
    <div class="two" style="align-items:center">
      <div>
        <h2 style="margin:0">Plano de Segurança (NIS2 / CNCS / QNRCS)</h2>
        <p class="hint" style="margin-top:6px">
          Responde uma pergunta de cada vez. Se algo <b>não for aplicável</b>, clica em <b>“Não aplicável / Pular”</b>.
        </p>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end; align-items:center">
        <span class="chip">Progresso: <b id="qProgressText">0/0</b></span>
        <span class="chip" id="qSectionChip">Secção —</span>
      </div>
    </div>

    <div style="height:10px"></div>

    {{-- Barra de progresso --}}
    <div style="background: rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08); border-radius:14px; overflow:hidden;">
      <div id="qProgressBar" style="height:10px; width:0%; background: rgba(46,204,113,.75);"></div>
    </div>

    <div style="height:14px"></div>

    {{-- Card da pergunta (conteúdo troca via JS) --}}
    <div id="qCard" class="panel" style="transition:opacity .15s ease, transform .15s ease;">
      <div class="muted" id="qMetaTop" style="margin-bottom:6px">—</div>
      <div id="qText" style="font-size:18px; font-weight:800;">—</div>

      <div style="height:12px"></div>

      <div class="two">
        <div class="field">
          <label>Resposta</label>
          <select id="qAnswer">
            <option value="YES">Sim</option>
            <option value="PARTIAL">Parcial</option>
            <option value="NO">Não</option>
          </select>
          <p class="hint" id="qHint">—</p>
        </div>

        <div class="field">
          <label>Observações (vai para o relatório)</label>
          <textarea id="qNotes" rows="4" placeholder="Notas, contexto, responsáveis, links, etc."></textarea>
        </div>
      </div>

      <div style="height:10px"></div>

      <div style="display:flex; gap:10px; justify-content:space-between; flex-wrap:wrap;">
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <button id="btnPrevQ" class="btn" type="button">Anterior</button>
          <button id="btnSkipQ" class="btn warn" type="button">Não aplicável / Pular</button>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <button id="btnNextQ" class="btn ok" type="button">Guardar & Próximo</button>
          <button id="btnFinishQ" class="btn primary" type="button" style="display:none;">Finalizar & Gerar relatório</button>
        </div>
      </div>
    </div>

    <div style="height:12px"></div>

    {{-- Resumo rápido --}}
    <div class="kpirow">
      <span class="chip">Sim: <b id="kYes">0</b></span>
      <span class="chip">Parcial: <b id="kPartial">0</b></span>
      <span class="chip">Não: <b id="kNo">0</b></span>
      <span class="chip">N/A: <b id="kNA">0</b></span>
    </div>
  </div>
</div>

{{-- MODAL: Relatório (mock PDF/print) --}}
<div id="reportModal" class="modal-overlay is-hidden" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="reportTitle">
    <div class="modal-header">
      <div>
        <div class="muted" style="margin-bottom:4px">Relatório base (RF13)</div>
        <div id="reportTitle" style="font-size:18px;font-weight:800">Plano de Segurança — Draft</div>
      </div>
      <div style="display:flex; gap:10px; align-items:center">
        <button id="btnPrintReport" class="btn ok" type="button">Imprimir / Guardar PDF</button>
        <button id="btnCloseReport" class="btn" type="button">Fechar</button>
      </div>
    </div>

    <div style="height:10px"></div>

    <div id="reportBody" class="panel" style="max-height:65vh; overflow:auto;">
      {{-- JS injeta o HTML aqui --}}
    </div>
  </div>
</div>
@endsection

@push('scripts')
  @vite(['resources/js/pages/questionnaire.js'])
@endpush
