@extends('layouts.app')
@section('title', 'Compliance • Techbase GRC')

@section('content')
<section id="page-compliance" class="page">
<div class="card">

  {{-- ── Cabeçalho ── --}}
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px">
    <div>
      <h3>Compliance</h3>
      <div class="muted">Avaliação de conformidade por framework normativo (QNRCS / NIS2).</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      {{-- Filtro de framework --}}
      <select id="fwFilter" style="min-width:140px">
        <option value="">Todos os frameworks</option>
      </select>
      {{-- Filtro de status --}}
      <select id="statusFilter">
        <option value="">Todos os estados</option>
        <option value="compliant">Conforme</option>
        <option value="partial">Parcial</option>
        <option value="non_compliant">Não conforme</option>
      </select>
      <button id="btnExpandAll" class="btn small" type="button">Expandir tudo</button>
      <button id="btnCollapseAll" class="btn small" type="button">Colapsar tudo</button>
    </div>
  </div>

  {{-- ── KPI Bar ── --}}
  <div id="kpiBar" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
    {{-- injectado pelo JS --}}
  </div>

  {{-- ── Progress global ── --}}
  <div id="globalProgressWrap" style="display:none;margin-bottom:20px">
    <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px">
      <span class="muted">Conformidade global</span>
      <b id="globalPct">0%</b>
    </div>
    <div style="height:8px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden">
      <div id="globalBar" style="height:100%;border-radius:999px;background:linear-gradient(90deg,#34d399,#10b981);transition:width .6s ease;width:0%"></div>
    </div>
  </div>

  {{-- ── Lista de frameworks/grupos/controlos ── --}}
  <div id="complianceList">
    <div style="padding:40px;text-align:center;color:var(--muted)">A carregar...</div>
  </div>

</div>
</section>


{{-- ══════════════════════════════════════════════════════
     MODAL: Avaliação de controlo
══════════════════════════════════════════════════════ --}}
<div id="assessModal" class="modal-overlay" aria-hidden="true" style="display:none">
  <div class="modal-card" role="dialog" aria-modal="true" style="width:min(720px,96vw);max-height:90vh;overflow:auto">

    <div class="modal-header">
      <div>
        <div class="muted" style="font-size:11px;margin-bottom:2px">Avaliação de controlo</div>
        <div id="amCode" style="font-size:18px;font-weight:800">—</div>
        <div id="amDesc" class="muted" style="font-size:13px;margin-top:3px;max-width:500px;line-height:1.4">—</div>
      </div>
      <button id="amClose" class="btn" type="button">Fechar</button>
    </div>

    {{-- Guidance --}}
    <div id="amGuidanceWrap" style="display:none;margin:14px 0;padding:12px 14px;border-radius:10px;background:rgba(96,165,250,.07);border:1px solid rgba(96,165,250,.16);font-size:13px;line-height:1.6;color:var(--muted)">
      <b style="color:var(--text);display:block;margin-bottom:4px">Orientação</b>
      <div id="amGuidance"></div>
    </div>

    {{-- Formulário de avaliação --}}
    <div class="panel" style="margin-top:14px">
      <h2>Classificação</h2>

      <div class="field">
        <label>Estado de conformidade <span style="color:var(--bad)">*</span></label>
        <div id="amStatusPicker" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px">
          <label class="status-radio" data-val="compliant">
            <input type="radio" name="am_status" value="compliant">
            <span class="tag ok" style="cursor:pointer;padding:8px 16px;font-size:13px">✓ Conforme</span>
          </label>
          <label class="status-radio" data-val="partial">
            <input type="radio" name="am_status" value="partial">
            <span class="tag warn" style="cursor:pointer;padding:8px 16px;font-size:13px">~ Parcial</span>
          </label>
          <label class="status-radio" data-val="non_compliant">
            <input type="radio" name="am_status" value="non_compliant">
            <span class="tag bad" style="cursor:pointer;padding:8px 16px;font-size:13px">✗ Não conforme</span>
          </label>
        </div>
      </div>

      <div class="field" style="margin-top:14px">
        <label>Notas / Justificação</label>
        <textarea id="amNotes" rows="3" placeholder="Descreve a evidência, lacunas ou acções correctivas previstas..." style="width:100%;resize:vertical"></textarea>
      </div>

      <div class="field" style="margin-top:10px">
        <label>Link de evidência <span class="muted" style="font-weight:400">(URL opcional)</span></label>
        <input id="amEvidenceLink" type="url" placeholder="https://..." style="width:100%" />
      </div>

      <div id="amFeedback" style="display:none;margin-top:10px;padding:10px 14px;border-radius:10px;font-size:13px"></div>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
        <button id="amCancel" class="btn" type="button">Cancelar</button>
        <button id="amSave" class="btn ok" type="button">Guardar avaliação</button>
      </div>
    </div>

    {{-- Evidências ligadas --}}
    <div class="panel" style="margin-top:12px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <h2 style="margin:0">Documentos de evidência</h2>
        <button id="amLinkDoc" class="btn small ok" type="button">+ Ligar documento</button>
      </div>
      <div id="amEvidencesList">
        <div class="muted" style="font-size:13px">Sem documentos ligados.</div>
      </div>
    </div>

    {{-- Histórico --}}
    <div class="panel" style="margin-top:12px">
      <div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer" id="amHistoryToggle">
        <h2 style="margin:0">Histórico de avaliações</h2>
        <span class="muted" style="font-size:12px" id="amHistoryArrow">▼ Ver</span>
      </div>
      <div id="amHistoryBody" style="display:none;margin-top:10px"></div>
    </div>

  </div>
</div>


{{-- ══════════════════════════════════════════════════════
     MODAL: Ligar documento a controlo
══════════════════════════════════════════════════════ --}}
<div id="linkDocModal" class="modal-overlay" aria-hidden="true" style="display:none">
  <div class="modal-card" role="dialog" aria-modal="true" style="width:min(600px,96vw);max-height:80vh;overflow:auto">

    <div class="modal-header">
      <div>
        <div class="muted" style="font-size:11px;margin-bottom:2px">Ligar evidência</div>
        <div style="font-size:17px;font-weight:700">Seleccionar documento</div>
        <div class="muted" style="font-size:12px;margin-top:2px">Controlo: <b id="ldControlCode">—</b></div>
      </div>
      <button id="ldClose" class="btn" type="button">Fechar</button>
    </div>

<div style="display:flex;gap:8px;margin:12px 0">
  <button id="ldTabSelect" class="btn small ok" type="button">Selecionar existente</button>
  <button id="ldTabUpload" class="btn small" type="button">Upload novo</button>
</div>

<!-- LISTA -->
<div id="ldSelectSection">
  <div style="margin-bottom:10px">
    <input id="ldSearch" type="search" placeholder="Filtrar por nome..." style="width:100%" />
  </div>
  <div id="ldDocsList"></div>
</div>

<!-- UPLOAD -->
<div id="ldUploadSection" style="display:none">
  <input id="ldFile" type="file" style="margin-bottom:10px" />
  <button id="ldUploadBtn" class="btn ok small">Upload & ligar</button>
</div>

  </div>
</div>


@push('styles')
@vite(['resources/css/pages/compliance.css'])
@endpush


@push('scripts')
@vite(['resources/js/pages/compliance.js'])
@endpush

@endsection
