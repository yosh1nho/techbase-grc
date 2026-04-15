@extends('layouts.app')
@section('title', 'Avaliações • Techbase GRC')
@section('content')



<section id="page-assessments" class="page">

  {{-- ── Header ── --}}
  <div class="a-header">
    <div class="a-header-left">
      <div class="a-icon">⚖</div>
      <div>
        <h1 class="a-title">Avaliações de conformidade</h1>
        <div class="a-rf-row">
          <span class="rf-badge">RF5</span>
          <span class="rf-badge">RF6</span>
          <span class="rf-badge">RF9</span>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button id="btnCompare" type="button" class="btn">Comparar</button>
      <button id="btnCloseAssessment" type="button" class="btn">Fechar avaliação</button>
    </div>
  </div>

  {{-- ── KPI strip ── --}}
  <div class="kpi-strip">
    <div class="kpi-card kc-a">
      <div class="kpi-label">Maturidade atual</div>
      <div class="kpi-num" id="kpiMaturity">—</div>
      <div class="kpi-bar"><div class="kpi-fill" id="kpiMatFill" style="width:0%;background:var(--a-accent);transition:width .6s ease;"></div></div>
      <div class="kpi-sub" id="kpiMatSub">A carregar...</div>
    </div>
    <div class="kpi-card kc-ok">
      <div class="kpi-label">Covered</div>
      <div class="kpi-num" id="kpiCovered">—</div>
      <div class="kpi-bar"><div class="kpi-fill" id="kpiCovFill" style="width:0%;background:var(--ok);transition:width .6s ease;"></div></div>
      <div class="kpi-sub">Controlos cobertos</div>
    </div>
    <div class="kpi-card kc-w">
      <div class="kpi-label">Partial</div>
      <div class="kpi-num" id="kpiPartial">—</div>
      <div class="kpi-bar"><div class="kpi-fill" id="kpiParFill" style="width:0%;background:var(--warn);transition:width .6s ease;"></div></div>
      <div class="kpi-sub">Evidência incompleta</div>
    </div>
    <div class="kpi-card kc-b">
      <div class="kpi-label">GAP</div>
      <div class="kpi-num" id="kpiGap">—</div>
      <div class="kpi-bar"><div class="kpi-fill" id="kpiGapFill" style="width:0%;background:var(--bad);transition:width .6s ease;"></div></div>
      <div class="kpi-sub">Sem cobertura</div>
    </div>
  </div>

{{-- ── Criar avaliação ── --}}
  <div class="panel" style="margin-bottom:14px;">
    <div class="ph">
      <div>
        <h2>Criar avaliação</h2>
        <div class="ph-sub">Seleciona framework(s), escopo e período. A IA cruza riscos, evidências e controlos do ativo.</div>
      </div>
      <button id="btnStartAssessment" type="button" class="btn ok">▶ Iniciar</button>
    </div>

    {{-- LINHA 1: Escopo e Ativo --}}
    <div class="two" style="margin-bottom:12px;">
      <div class="field">
        <label>Escopo</label>
        <div class="scope-row" id="scopeRow">
          <button type="button" class="scope-btn" data-scope="asset" aria-pressed="true" style="cursor: default;">Por ativo</button>
        </div>
        <input type="hidden" id="scopeSelect" value="asset" />
      </div>

      <div class="field" id="assetField">
        <label>Ativo alvo <span style="color:#f87171">*</span></label>
        <div class="dd-wrap">
          <input id="assetSearch" placeholder="Pesquisar ativo por nome, tipo, responsável…" autocomplete="off" />
          <div id="assetDropdown" class="asset-dd"></div>
        </div>
        <input type="hidden" id="assetSelectedId" value="" />
        <div class="muted" style="margin-top:5px;font-size:11px;">A avaliação será cruzada com riscos deste ativo.</div>
      </div>
    </div>

    {{-- LINHA 2: Frameworks e Período --}}
    <div class="two">
      <div class="field">
        <label>Framework(s) <span style="color:#f87171">*</span></label>
        <div class="muted" style="font-size:11px;margin-bottom:8px;">Clica para adicionar/remover</div>
        
        <div id="fwChips" class="fw-chips">
          <div style="font-size: 12px; color: var(--muted);">A carregar frameworks...</div>
        </div>
        
        <input type="hidden" id="frameworksSelected" value="" />
        <div id="fwSummary" class="fw-summary">Selecionados: <b>—</b></div>
      </div>

      <div class="field">
        <label>Período</label>
        <div style="display:flex;gap:8px;">
          <select id="periodYear"    style="flex:1;"><option value="2026" selected>2026</option><option value="2025">2025</option><option value="2024">2024</option></select>
          <select id="periodQuarter" style="flex:1;"><option value="Q1" selected>Q1</option><option value="Q2">Q2</option><option value="Q3">Q3</option><option value="Q4">Q4</option></select>
        </div>
        <div class="muted" style="margin-top:5px;font-size:11px;">Ex.: Q1 2026</div>
      </div>
    </div>
  </div>

  {{-- ── Histórico (RF9) ── --}}
  <div class="panel">
    <div class="ph">
      <div>
        <h2>Histórico de avaliações <span style="font-family:var(--font-mono,monospace);font-size:10px;color:var(--muted);font-weight:400;vertical-align:middle;margin-left:4px;">RF9</span></h2>
        <div class="ph-sub">Comparação de maturidade entre períodos. Clica em "Detalhes" para ver domínios por área.</div>
      </div>
    </div>
    <table>
      <thead>
        <tr>
          <th>Período</th>
          <th>Data</th>
          <th>Escopo</th>
          <th>Framework(s)</th>
          <th>Maturidade</th>
          <th>Tendência</th>
          <th>Fecho</th>
          <th style="text-align:right;"></th>
        </tr>
      </thead>
      <tbody id="historyRows">{{-- via JS --}}</tbody>
    </table>
  </div>

</section>

{{-- ══ Modal detalhes ══ --}}
<div id="assessmentDetailsModal" class="modal-overlay" aria-hidden="true" style="display:none;">
  <div class="modal-card a-modal" role="dialog" aria-modal="true" aria-labelledby="assessmentDetailsTitle" style="max-width:660px;width:96vw;">

    <div class="m-header">
      <div>
        <h2 id="assessmentDetailsTitle" style="margin:0;font-size:15px;">Detalhes</h2>
        <div id="assessmentDetailsSubtitle" class="muted" style="margin-top:5px;font-size:12px;">—</div>
      </div>
      <button id="assessmentDetailsClose" type="button" class="btn" style="padding:5px 12px;font-size:12px;flex-shrink:0;">✕ Fechar</button>
    </div>

    {{-- Status + Confiança IA --}}
    <div class="m-section">
      <div class="m-grid">
        <div class="m-block">
          <div class="m-label">Status</div>
          <div class="m-val" id="detStatus" style="margin-top:5px;">—</div>
        </div>
        <div class="m-block">
          <div class="m-label">Confiança IA</div>
          <div class="m-val" id="detAiConfidence" style="margin-top:5px;">—</div>
        </div>
      </div>
    </div>

    <div class="m-divider"></div>

    {{-- Análise IA --}}
    <div class="m-section">
      <div class="m-section-title">Análise IA</div>
      <div class="m-grid" style="margin-bottom:12px;">
        <div class="m-block">
          <div class="m-label">Requisitos cruzados</div>
          <div class="m-val" id="detFrameworkCross" style="margin-top:5px;">—</div>
        </div>
        <div class="m-block">
          <div class="m-label">Evidências usadas</div>
          <div class="m-val" id="detEvidenceUsed" style="margin-top:5px;">—</div>
        </div>
      </div>
      <div class="m-block">
        <div class="m-label">Justificação do resultado</div>
        <div class="m-val" id="detRationale" style="margin-top:5px;">—</div>
      </div>
    </div>

    {{-- Domínios (injetado pelo JS quando disponível) --}}
    <div id="domainSection" style="display:none;">
      <div class="m-divider"></div>
      <div class="m-section">
        <div class="m-section-title">Maturidade por domínio</div>
        <div class="dom-list" id="domainList"></div>
      </div>
    </div>

    <div class="m-divider"></div>

    {{-- Contexto --}}
    <div class="m-section">
      <div class="m-section-title">Contexto considerado</div>
      <div class="m-grid" style="margin-bottom:12px;">
        <div class="m-block">
          <div class="m-label">Riscos associados</div>
          <div class="m-val" id="detRisks" style="margin-top:5px;">—</div>
        </div>
        <div class="m-block">
          <div class="m-label">Alertas associados</div>
          <div class="m-val" id="detAlerts" style="margin-top:5px;">—</div>
        </div>
      </div>
      <div class="m-block">
        <div class="m-label">Planos de tratamento ligados</div>
        <div class="m-val" id="detTreatments" style="margin-top:5px;">—</div>
      </div>
    </div>

    <div class="m-footer">
      <button type="button" class="btn" style="font-size:12px;padding:6px 12px;">↓ Exportar PDF</button>
      <button type="button" class="btn ok" style="font-size:12px;padding:6px 12px;">Fechar avaliação</button>
    </div>

  </div>
</div>

@push('scripts')
  @vite(['resources/js/pages/assessments.js'])
@endpush

@push('styles')
@vite(['resources/css/pages/assessments.css'])
@endpush

@endsection