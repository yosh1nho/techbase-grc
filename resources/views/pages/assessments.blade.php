@extends('layouts.app')
@section('title', 'Avaliações • Techbase GRC')
@section('content')

<style>
/* ══════════════════════════════════════════════════════
   Assessments — estilos locais (usa vars do app.blade)
   ══════════════════════════════════════════════════════ */

#page-assessments {
    --a-accent:   #4f9cf9;
    --a-glow:     rgba(79,156,249,.12);
    --a-glow2:    rgba(79,156,249,.06);
}

/* ─── Header ─── */
#page-assessments .a-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
}
#page-assessments .a-header-left { display: flex; align-items: center; gap: 14px; }
#page-assessments .a-icon {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 20px;
    background: var(--a-glow); border: 1px solid rgba(79,156,249,.28);
    box-shadow: 0 0 20px rgba(79,156,249,.1);
}
#page-assessments .a-title { font-size: 17px; font-weight: 600; margin: 0; color: var(--text); }
#page-assessments .a-rf-row { display: flex; gap: 6px; margin-top: 5px; }
#page-assessments .rf-badge {
    font-family: var(--font-mono, monospace); font-size: 10px; font-weight: 500;
    padding: 2px 8px; border-radius: 999px; letter-spacing: .04em;
    background: var(--a-glow); border: 1px solid rgba(79,156,249,.22);
    color: var(--a-accent);
}

/* ─── KPI strip ─── */
#page-assessments .kpi-strip {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 10px; margin-bottom: 16px;
}
@media (max-width: 860px) { #page-assessments .kpi-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px)  { #page-assessments .kpi-strip { grid-template-columns: 1fr; } }

#page-assessments .kpi-card {
    padding: 15px 16px; border-radius: 12px;
    border: 1px solid var(--line); background: rgba(255,255,255,.015);
    position: relative; overflow: hidden;
    transition: border-color .15s;
}
#page-assessments .kpi-card:hover { border-color: rgba(255,255,255,.12); }
#page-assessments .kpi-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 2px; border-radius: 2px 2px 0 0;
}
#page-assessments .kpi-card.kc-a::before  { background: var(--a-accent); }
#page-assessments .kpi-card.kc-ok::before  { background: var(--ok); }
#page-assessments .kpi-card.kc-w::before   { background: var(--warn); }
#page-assessments .kpi-card.kc-b::before   { background: var(--bad); }

#page-assessments .kpi-label {
    font-size: 10px; font-weight: 600; color: var(--muted);
    text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px;
}
#page-assessments .kpi-num {
    font-family: var(--font-mono, monospace); font-size: 28px; font-weight: 700;
    letter-spacing: -.5px; line-height: 1; margin-bottom: 8px;
}
#page-assessments .kpi-card.kc-a  .kpi-num { color: var(--a-accent); }
#page-assessments .kpi-card.kc-ok .kpi-num { color: var(--ok); }
#page-assessments .kpi-card.kc-w  .kpi-num { color: var(--warn); }
#page-assessments .kpi-card.kc-b  .kpi-num { color: var(--bad); }
#page-assessments .kpi-sub { font-size: 11px; color: var(--muted); line-height: 1.4; }

#page-assessments .kpi-bar { height: 3px; background: var(--line); border-radius: 3px; overflow: hidden; margin-bottom: 8px; }
#page-assessments .kpi-fill { height: 100%; border-radius: 3px; transition: width .6s ease; }

/* ─── Panel header ─── */
#page-assessments .ph {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 12px; flex-wrap: wrap; margin-bottom: 16px;
}
#page-assessments .ph h2 { margin: 0; }
#page-assessments .ph-sub { font-size: 12px; color: var(--muted); margin-top: 4px; }

/* ─── Scope buttons ─── */
#page-assessments .scope-row { display: flex; gap: 8px; }
#page-assessments .scope-btn {
    flex: 1; padding: 8px 12px; border-radius: 10px; cursor: pointer;
    border: 1px solid var(--line); background: transparent;
    color: var(--muted); font-family: var(--font, inherit);
    font-size: 13px; font-weight: 500; transition: all .13s;
}
#page-assessments .scope-btn[aria-pressed="true"] {
    border-color: var(--a-accent); color: var(--a-accent); background: var(--a-glow);
}

/* ─── Framework chips ─── */
#page-assessments .fw-chips { display: flex; gap: 8px; flex-wrap: wrap; }
#page-assessments .fw-chip {
    padding: 5px 13px; border-radius: 999px; font-size: 12px; font-weight: 500;
    cursor: pointer; border: 1px solid var(--line); background: transparent;
    color: var(--muted); font-family: var(--font, inherit);
    transition: all .13s; user-select: none;
}
#page-assessments .fw-chip[aria-pressed="true"] {
    border-color: var(--a-accent); color: var(--a-accent); background: var(--a-glow);
}
#page-assessments .fw-summary { font-size: 12px; color: var(--muted); margin-top: 8px; }
#page-assessments .fw-summary b { color: var(--a-accent); }

/* ─── Asset dropdown ─── */
#page-assessments .dd-wrap { position: relative; z-index: 100; }
#page-assessments .asset-dd {
    position: absolute; left: 0; right: 0; top: calc(100% + 5px); z-index: 100;
    display: none; max-height: 240px; overflow: auto; padding: 5px;
    border-radius: 10px; background: var(--panel); border: 1px solid var(--line);
    box-shadow: 0 14px 40px rgba(0,0,0,.45);
}
#page-assessments .asset-opt {
    display: flex; align-items: center; gap: 10px; padding: 9px 10px;
    border-radius: 8px; border: 1px solid transparent; background: transparent;
    cursor: pointer; width: 100%; text-align: left; transition: background .1s;
}
#page-assessments .asset-opt:hover { background: var(--a-glow); border-color: rgba(79,156,249,.2); }
#page-assessments .aopt-icon {
    width: 30px; height: 30px; border-radius: 7px; flex-shrink: 0;
    display: grid; place-items: center;
    background: var(--a-glow); border: 1px solid rgba(79,156,249,.2);
    color: var(--a-accent);
}
#page-assessments .aopt-icon svg { width: 16px; height: 16px; stroke-width: 2.2px; }
#page-assessments .aopt-name { font-size: 13px; font-weight: 500; color: var(--text); }
#page-assessments .aopt-sub  { font-size: 11px; color: var(--muted); }

/* ─── Status tags ─── */
#page-assessments .st {
    display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px;
    border-radius: 999px; font-family: var(--font-mono, monospace);
    font-size: 11px; font-weight: 600; letter-spacing: .04em; white-space: nowrap;
}
#page-assessments .st .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
#page-assessments .st.ok   { color: var(--ok);   background: rgba(45,212,191,.09);  border: 1px solid rgba(45,212,191,.22); }
#page-assessments .st.warn { color: var(--warn); background: rgba(251,191,36,.09);  border: 1px solid rgba(251,191,36,.22); }
#page-assessments .st.bad  { color: var(--bad);  background: rgba(251,113,133,.09); border: 1px solid rgba(251,113,133,.22); }
#page-assessments .st.neu  { color: var(--muted); background: rgba(255,255,255,.03); border: 1px solid var(--line); }
#page-assessments .st.ok   .dot { background: var(--ok); }
#page-assessments .st.warn .dot { background: var(--warn); }
#page-assessments .st.bad  .dot { background: var(--bad); }
#page-assessments .st.neu  .dot { background: var(--muted); }

/* ─── Maturity bar (histórico) ─── */
#page-assessments .mat-cell { display: flex; align-items: center; gap: 10px; min-width: 130px; }
#page-assessments .mat-bar  { flex: 1; height: 4px; background: var(--line); border-radius: 4px; overflow: hidden; }
#page-assessments .mat-fill { height: 100%; border-radius: 4px; }
#page-assessments .mat-pct  { font-family: var(--font-mono, monospace); font-size: 12px; color: var(--text); min-width: 34px; }

/* ─── Trend ─── */
#page-assessments .trend { font-family: var(--font-mono, monospace); font-size: 11px; font-weight: 600; white-space: nowrap; }
#page-assessments .trend.up   { color: var(--ok); }
#page-assessments .trend.down { color: var(--bad); }

/* ─── Modal internals ─── */
.a-modal .m-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 12px; padding-bottom: 14px; border-bottom: 1px solid var(--line); margin-bottom: 18px;
}
.a-modal .m-footer {
    padding-top: 14px; border-top: 1px solid var(--line);
    display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px;
}
.a-modal .m-section { margin-bottom: 20px; }
.a-modal .m-section:last-child { margin-bottom: 0; }
.a-modal .m-section-title {
    font-size: 10px; font-weight: 600; color: var(--muted);
    text-transform: uppercase; letter-spacing: .08em; margin: 0 0 12px;
}
.a-modal .m-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 540px) { .a-modal .m-grid { grid-template-columns: 1fr; } }
.a-modal .m-block .m-label {
    font-size: 10px; font-weight: 600; color: var(--muted);
    text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px;
}
.a-modal .m-block .m-val { font-size: 13px; color: var(--text); line-height: 1.55; }
.a-modal .m-divider { height: 1px; background: var(--line); margin: 16px 0; }

/* ─── Domain bars (modal) ─── */
.a-modal .dom-list  { display: flex; flex-direction: column; gap: 10px; }
.a-modal .dom-row   { display: flex; align-items: center; gap: 10px; }
.a-modal .dom-name  { font-size: 12px; color: var(--muted); min-width: 105px; }
.a-modal .dom-bar   { flex: 1; height: 5px; background: var(--line); border-radius: 4px; overflow: hidden; }
.a-modal .dom-fill  { height: 100%; border-radius: 4px; transition: width .55s ease; }
.a-modal .dom-pct   { font-family: var(--font-mono, monospace); font-size: 11px; color: var(--muted); min-width: 34px; text-align: right; }

/* ─── AI confidence pill ─── */
.a-modal .conf-pill, #page-assessments .conf-pill {
    display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px;
    border-radius: 999px; font-size: 11px; font-weight: 600;
    font-family: var(--font-mono, monospace);
    background: var(--a-glow); color: var(--a-accent); border: 1px solid rgba(79,156,249,.25);
}

/* ─── Light mode ─── */
:root[data-theme="light"] #page-assessments .kpi-card { background: rgba(255,255,255,.85); }
:root[data-theme="light"] #page-assessments .scope-btn { background: rgba(255,255,255,.8); border-color: rgba(15,23,42,.12); color: var(--muted); }
:root[data-theme="light"] #page-assessments .scope-btn[aria-pressed="true"] { background: rgba(79,156,249,.08); border-color: rgba(79,156,249,.35); color: #1a5fc8; }
:root[data-theme="light"] #page-assessments .fw-chip  { background: rgba(255,255,255,.8); border-color: rgba(15,23,42,.12); color: var(--muted); }
:root[data-theme="light"] #page-assessments .fw-chip[aria-pressed="true"]  { background: rgba(79,156,249,.08); border-color: rgba(79,156,249,.35); color: #1a5fc8; }
:root[data-theme="light"] #page-assessments .rf-badge  { background: rgba(79,156,249,.07); color: #1a5fc8; border-color: rgba(79,156,249,.2); }
:root[data-theme="light"] #page-assessments .asset-dd  { background: #fff; box-shadow: 0 8px 24px rgba(15,23,42,.12); }
:root[data-theme="light"] #page-assessments .st.ok   { color: #0a7a6e; }
:root[data-theme="light"] #page-assessments .st.warn { color: #9a5c04; }
:root[data-theme="light"] #page-assessments .st.bad  { color: #a01535; }
:root[data-theme="light"] .a-modal .conf-pill, #page-assessments .conf-pill { color: #1a5fc8; }
:root[data-theme="light"] #page-assessments .kpi-card.kc-ok .kpi-num { color: #0a7a6e; }
:root[data-theme="light"] #page-assessments .kpi-card.kc-w  .kpi-num { color: #9a5c04; }
:root[data-theme="light"] #page-assessments .kpi-card.kc-b  .kpi-num { color: #a01535; }

    /* st tags inside modal */
    .a-modal .st {
        display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px;
        border-radius: 999px; font-family: var(--font-mono, monospace);
        font-size: 11px; font-weight: 600; letter-spacing: .04em; white-space: nowrap;
    }
    .a-modal .st .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .a-modal .st.ok   { color: var(--ok);   background: rgba(45,212,191,.09);  border: 1px solid rgba(45,212,191,.22); }
    .a-modal .st.warn { color: var(--warn); background: rgba(251,191,36,.09);  border: 1px solid rgba(251,191,36,.22); }
    .a-modal .st.bad  { color: var(--bad);  background: rgba(251,113,133,.09); border: 1px solid rgba(251,113,133,.22); }
    .a-modal .st.neu  { color: var(--muted); background: rgba(255,255,255,.03); border: 1px solid var(--line); }
    .a-modal .st.ok   .dot { background: var(--ok); }
    .a-modal .st.warn .dot { background: var(--warn); }
    .a-modal .st.bad  .dot { background: var(--bad); }
    .a-modal .st.neu  .dot { background: var(--muted); }
    :root[data-theme="light"] .a-modal .st.ok   { color: #0a7a6e; }
    :root[data-theme="light"] .a-modal .st.warn { color: #9a5c04; }
    :root[data-theme="light"] .a-modal .st.bad  { color: #a01535; }
    .a-modal .conf-pill {
        display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px;
        border-radius: 999px; font-size: 11px; font-weight: 600;
        font-family: var(--font-mono, monospace);
        background: var(--a-glow, rgba(79,156,249,.12)); color: #4f9cf9;
        border: 1px solid rgba(79,156,249,.25);
    }
</style>

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
      <div class="kpi-num">62%</div>
      <div class="kpi-bar"><div class="kpi-fill" style="width:62%;background:var(--a-accent);"></div></div>
      <div class="kpi-sub">Q1 2026 · QNRCS v2.1 + NIS2</div>
    </div>
    <div class="kpi-card kc-ok">
      <div class="kpi-label">Covered</div>
      <div class="kpi-num">41</div>
      <div class="kpi-bar"><div class="kpi-fill" style="width:58%;background:var(--ok);"></div></div>
      <div class="kpi-sub">Controlos cobertos</div>
    </div>
    <div class="kpi-card kc-w">
      <div class="kpi-label">Partial</div>
      <div class="kpi-num">18</div>
      <div class="kpi-bar"><div class="kpi-fill" style="width:25%;background:var(--warn);"></div></div>
      <div class="kpi-sub">Evidência incompleta</div>
    </div>
    <div class="kpi-card kc-b">
      <div class="kpi-label">GAP</div>
      <div class="kpi-num">12</div>
      <div class="kpi-bar"><div class="kpi-fill" style="width:17%;background:var(--bad);"></div></div>
      <div class="kpi-sub">Sem cobertura</div>
    </div>
  </div>

  {{-- ── Criar avaliação ── --}}
  <div class="panel" style="margin-bottom:14px;">
    <div class="ph">
      <div>
        <h2>Criar avaliação</h2>
        <div class="ph-sub">Seleciona framework(s), escopo e período. O mock pré-preenche e cruza evidências/IA.</div>
      </div>
      <button id="btnStartAssessment" type="button" class="btn ok">▶ Iniciar</button>
    </div>

    <div class="two" style="margin-bottom:12px;">
      <div class="field">
        <label>Escopo</label>
        <div class="scope-row" id="scopeRow">
          <button type="button" class="scope-btn" data-scope="org"   aria-pressed="true">Organização</button>
          <button type="button" class="scope-btn" data-scope="asset" aria-pressed="false">Por ativo</button>
        </div>
        <input type="hidden" id="scopeSelect" value="org" />
      </div>

      <div class="field" id="assetField">
        <label>Ativo</label>
        <div class="dd-wrap">
          <input id="assetSearch" placeholder="Pesquisar ativo por nome, tipo, responsável…" autocomplete="off" />
          <div id="assetDropdown" class="asset-dd"></div>
        </div>
        <input type="hidden" id="assetSelectedId" value="" />
        <div class="muted" style="margin-top:5px;font-size:11px;">Recomendado quando escopo = "Por ativo".</div>
      </div>
    </div>

    <div class="two">
      <div class="field">
        <label>Framework(s)</label>
        <div class="muted" style="font-size:11px;margin-bottom:8px;">Clica para adicionar/remover</div>
        <div id="fwChips" class="fw-chips">
          <button type="button" class="fw-chip" data-fw="QNRCS v2.1"  aria-pressed="true">QNRCS v2.1</button>
          <button type="button" class="fw-chip" data-fw="NIS2"        aria-pressed="false">NIS2</button>
          <button type="button" class="fw-chip" data-fw="ISO 27001"   aria-pressed="false">ISO 27001</button>
          <button type="button" class="fw-chip" data-fw="CIS Controls" aria-pressed="false">CIS Controls</button>
        </div>
        <input type="hidden" id="frameworksSelected" value="QNRCS v2.1" />
        <div id="fwSummary" class="fw-summary">Selecionados: <b>QNRCS v2.1</b></div>
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

  {{-- ── Resultado por controlo ── --}}
  <div class="panel" style="margin-bottom:14px;">
    <div class="ph">
      <div>
        <h2>Resultado por controlo</h2>
        <div class="ph-sub">Cada controlo recebe status + notas + evidências. "Detalhes" mostra o cruzamento IA/framework.</div>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;flex-shrink:0;">
        <span class="st ok"><span class="dot"></span>1 COVERED</span>
        <span class="st warn"><span class="dot"></span>1 PARTIAL</span>
        <span class="st bad"><span class="dot"></span>1 GAP</span>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Controlo</th>
          <th>Status</th>
          <th>Notas</th>
          <th>Evidências</th>
          <th style="width:100px;text-align:right;"></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><b>ID.GA-1</b><div class="muted">Inventário atualizado</div></td>
          <td><span class="st warn"><span class="dot"></span>PARTIAL</span></td>
          <td class="muted">Existe processo, falta prova periódica.</td>
          <td class="muted">Procedimento v1.0</td>
          <td style="text-align:right;"><button type="button" class="btn" style="padding:5px 11px;font-size:12px;" data-open-details="ID.GA-1">Detalhes</button></td>
        </tr>
        <tr>
          <td><b>ID.AR-1</b><div class="muted">Análise de risco anual</div></td>
          <td><span class="st bad"><span class="dot"></span>GAP</span></td>
          <td class="muted">Sem registo formal.</td>
          <td class="muted">—</td>
          <td style="text-align:right;"><button type="button" class="btn" style="padding:5px 11px;font-size:12px;" data-open-details="ID.AR-1">Detalhes</button></td>
        </tr>
        <tr>
          <td><b>PR.IP-4</b><div class="muted">Backups e testes</div></td>
          <td><span class="st ok"><span class="dot"></span>COVERED</span></td>
          <td class="muted">Relatórios mensais anexados.</td>
          <td class="muted">Relatório Jan/2026</td>
          <td style="text-align:right;"><button type="button" class="btn" style="padding:5px 11px;font-size:12px;" data-open-details="PR.IP-4">Detalhes</button></td>
        </tr>
      </tbody>
    </table>
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

@endsection
