@extends('layouts.app')
@section('title', 'Cyberplanner • Techbase GRC')

@section('content')
<div class="card">
  <h3>Cyberplanner (mock funcional)</h3>

  <div class="panel" style="margin-top:10px">
    <div class="two" style="align-items:center">
      <div>
        <h2 style="margin:0">Plano de Segurança (estilo FCC + mapeamento QNRCS/NIS2)</h2>
        <p class="hint" style="margin-top:6px">
          1) Escolhe as áreas aplicáveis • 2) Responde as perguntas • 3) Gera o plano em PDF.
        </p>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end; align-items:center; flex-wrap:wrap;">
        <span class="chip">Progresso: <b id="qProgressText">0/0</b></span>
        <span class="chip" id="qAreaChip">Área —</span>
      </div>
    </div>

    <div style="height:10px"></div>

    {{-- Barra de progresso --}}
    <div style="background: rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08); border-radius:14px; overflow:hidden;">
      <div id="qProgressBar" style="height:10px; width:0%; background: rgba(46,204,113,.75);"></div>
    </div>

    <div style="height:14px"></div>

    {{-- Etapa 1: Picker de Áreas --}}
    <div id="areaPicker" class="panel">
      <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:flex-start;">
        <div>
          <div style="font-size:16px; font-weight:900;">1) Seleciona as áreas aplicáveis</div>
          <div class="hint" style="margin-top:6px;">
            Isto define o escopo do plano (estilo Cyberplanner). Podes ajustar depois.
          </div>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
          <button id="btnSelectAllAreas" class="btn" type="button">Selecionar tudo</button>
          <button id="btnClearAreas" class="btn warn" type="button">Limpar</button>
          <button id="btnStartWizard" class="btn primary" type="button">Começar</button>
        </div>
      </div>

      <div style="height:12px"></div>

      <div id="areaGrid" style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:10px;"></div>

      <div style="height:10px"></div>
      <div class="muted">
        Dica: marca só o que existe/é relevante. O relatório e PDF ficam mais úteis.
      </div>
    </div>

    {{-- Card da pergunta (Etapa 2) --}}
    <div id="wizardWrap" style="display:none;">
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
            <label>Observações (vai para o plano)</label>
            <textarea id="qNotes" rows="4" placeholder="Notas, contexto, responsáveis, links, evidências, etc."></textarea>
          </div>
        </div>

        <div style="height:10px"></div>

        <div style="display:flex; gap:10px; justify-content:space-between; flex-wrap:wrap;">
          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button id="btnBackToAreas" class="btn" type="button">Voltar às áreas</button>
            <button id="btnPrevQ" class="btn" type="button">Anterior</button>
            <button id="btnSkipQ" class="btn warn" type="button">Não aplicável / Pular</button>
          </div>

          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button id="btnNextQ" class="btn ok" type="button">Guardar & Próximo</button>
            <button id="btnFinishQ" class="btn primary" type="button" style="display:none;">Finalizar & Ver plano</button>
          </div>
        </div>
      </div>

      <div style="height:12px"></div>

      {{-- Resumo rápido --}}
      <div class="kpirow" style="display:flex; gap:10px; flex-wrap:wrap;">
        <span class="chip">Sim: <b id="kYes">0</b></span>
        <span class="chip">Parcial: <b id="kPartial">0</b></span>
        <span class="chip">Não: <b id="kNo">0</b></span>
        <span class="chip">N/A: <b id="kNA">0</b></span>
      </div>
    </div>
  </div>
</div>

{{-- Toast (mock UX) --}}
<div id="toast" style="position:fixed; right:16px; bottom:16px; z-index:9999; display:none;">
  <div class="panel" style="min-width:260px; border:1px solid rgba(255,255,255,.12);">
    <div style="font-weight:900;" id="toastTitle">—</div>
    <div class="muted" style="margin-top:6px;" id="toastMsg">—</div>
  </div>
</div>

{{-- MODAL: Plano / Preview --}}
<div id="reportModal" class="modal-overlay is-hidden" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="reportTitle">
    <div class="modal-header">
      <div>
        <div class="muted" style="margin-bottom:4px">Plano de Segurança (preview)</div>
        <div id="reportTitle" style="font-size:18px;font-weight:800">Plano — Draft</div>
      </div>

      <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <button id="btnGenPdf" class="btn ok" type="button">Gerar PDF</button>
        <button id="btnSendToDocs" class="btn primary" type="button">Enviar para Documentos/Evidências</button>
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
  {{-- pdfmake (PDF bonito client-side) --}}
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/pdfmake.min.js"></script>
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/vfs_fonts.min.js"></script>

  @vite(['resources/js/pages/questionnaire.js'])
@endpush