@extends('layouts.app')
@section('title', 'Gestão de Risco • Techbase GRC')

@section('content')

{{-- ══ KPI STRIP ══ --}}
<div class="rk-kpi-strip">
  <div class="rk-kpi">
    <div class="rk-kpi-num" id="rkKpiTotal">0</div>
    <div class="rk-kpi-label">Total</div>
  </div>
  <div class="rk-kpi rk-kpi-critical">
    <div class="rk-kpi-num" id="rkKpiCritical">0</div>
    <div class="rk-kpi-label">Muito alta</div>
  </div>
  <div class="rk-kpi rk-kpi-high">
    <div class="rk-kpi-num" id="rkKpiHigh">0</div>
    <div class="rk-kpi-label">Alta</div>
  </div>
  <div class="rk-kpi rk-kpi-med">
    <div class="rk-kpi-num" id="rkKpiMed">0</div>
    <div class="rk-kpi-label">Média</div>
  </div>
  <div class="rk-kpi rk-kpi-low">
    <div class="rk-kpi-num" id="rkKpiLow">0</div>
    <div class="rk-kpi-label">Baixa</div>
  </div>
  <div class="rk-kpi rk-kpi-open">
    <div class="rk-kpi-num" id="rkKpiOpen">0</div>
    <div class="rk-kpi-label">Abertos</div>
  </div>
  <div class="rk-kpi rk-kpi-treat">
    <div class="rk-kpi-num" id="rkKpiTreat">0</div>
    <div class="rk-kpi-label">Em tratamento</div>
  </div>
</div>


{{-- ══ LISTA DE RISCOS ══ --}}
<div class="card" style="margin-top:16px">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:16px">
    <div>
      <h3 style="margin:0">Riscos registados</h3>
      <p class="muted" style="font-size:12px;margin:3px 0 0">Associados a ativos. Score Probabilidade × Impacto (1–25).</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <input id="riskSearch" placeholder="Pesquisar..." style="padding:7px 12px;border-radius:10px;font-size:13px;min-width:180px;background:var(--input-bg);border:1px solid var(--border);color:inherit" />
      <select id="riskLevelFilter" style="padding:7px 12px;border-radius:10px;font-size:13px;background:var(--input-bg);border:1px solid var(--border);color:inherit">
        <option value="all">Todos os níveis</option>
        <option value="Muito Alta">Muito Alta</option>
        <option value="Alta">Alta</option>
        <option value="Média">Média</option>
        <option value="Baixa">Baixa</option>
      </select>
      <select id="riskStatusFilter" style="padding:7px 12px;border-radius:10px;font-size:13px;background:var(--input-bg);border:1px solid var(--border);color:inherit">
        <option value="all">Todos os estados</option>
        <option value="Aberto">Aberto</option>
        <option value="Em tratamento">Em tratamento</option>
        <option value="Mitigado">Mitigado</option>
        <option value="Aceito">Aceito</option>
      </select>
      <button id="btnNewRisk" class="btn ok" type="button">+ Novo risco</button>
    </div>
  </div>

  <div id="riskTableWrap">
    <div class="muted" style="padding:32px;text-align:center">A carregar riscos...</div>
  </div>
</div>


{{-- ══ MODAL: Criar / Editar Risco ══ --}}
<div id="riskModal" class="modal-overlay" aria-hidden="true" style="display:none">
  <div class="modal-card rk-risk-modal" role="dialog" aria-modal="true">

    {{-- Header --}}
    <div class="treat-modal-header">
      <div>
        <div class="treat-modal-eyebrow">Risco — Matriz 5×5</div>
        <div class="treat-modal-title" id="rmTitle">Novo risco</div>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <span id="rmScoreBadge" class="treat-status-badge tsb-todo">Score: —</span>
        <button id="rmClose" class="btn" type="button">Fechar</button>
      </div>
    </div>

    {{-- Corpo 2 colunas --}}
    <div class="treat-modal-body" style="overflow-y:auto;flex:1">

      {{-- Col esquerda: identificação --}}
      <div class="treat-modal-col">
        <div class="rk-section-label">Identificação</div>

        <div class="field">
          <label>Ativo associado <span style="color:var(--bad)">*</span></label>
          <select id="rm_asset">
            <option value="">A carregar ativos...</option>
          </select>
        </div>

        <div class="field">
          <label>Título / Descrição do risco <span style="color:var(--bad)">*</span></label>
          <input id="rm_title" placeholder="ex.: Acesso não autorizado por força bruta (SSH)" />
        </div>

        <div class="field">
          <label>Descrição detalhada</label>
          <textarea id="rm_desc" rows="2" placeholder="Contexto adicional (opcional)"></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div class="field">
            <label>Ameaça</label>
            <input id="rm_threat" placeholder="ex.: Ataque de força bruta" />
          </div>
          <div class="field">
            <label>Vulnerabilidade</label>
            <input id="rm_vuln" placeholder="ex.: Sem MFA" />
          </div>
        </div>

        <div class="field">
          <label>Acções de mitigação</label>
          <textarea id="rm_actions" rows="3" placeholder="ex.: Activar MFA, bloquear IPs, rate-limit..."></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div class="field">
            <label>Responsável do risco</label>
            <select id="rm_owner">
              <option value="">Selecionar responsável...</option>
            </select>
          </div>
          <div class="field">
            <label>Data alvo</label>
            <input id="rm_due" type="date" />
          </div>
        </div>

        <div class="field">
          <label>Estado</label>
          <select id="rm_status">
            <option>Aberto</option>
            <option>Em tratamento</option>
            <option>Mitigado</option>
            <option>Aceito</option>
            <option>Evitado</option>
            <option>Transferido</option>
          </select>
        </div>

        {{-- Painel IA --}}
        <div style="margin-top:16px;border-top:1px solid var(--border);padding-top:14px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
            <div class="rk-section-label" style="margin:0">Assistente IA</div>
            <button id="rmAiBtn" class="btn small" type="button">✦ Analisar com IA</button>
          </div>
          <div id="rmAiOutput" style="display:none;border-radius:10px;border:1px solid rgba(96,165,250,.2);background:rgba(96,165,250,.05);padding:12px;font-size:12px;line-height:1.6;max-height:200px;overflow-y:auto"></div>
          <div id="rmAiActions" style="display:none;margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
            <button id="rmAiApply" class="btn small ok" type="button">↓ Aplicar sugestões</button>
          </div>
        </div>

      </div>

      {{-- Col direita: avaliação 5×5 --}}
      <div class="treat-modal-col">
        <div class="rk-section-label">Avaliação de risco</div>

        {{-- Score visual --}}
        <div class="rk-score-box" id="rmScoreBox">
          <div class="rk-score-num" id="rmScoreNum">9</div>
          <div class="rk-score-label" id="rmScoreLabel">Média</div>
          <div class="rk-score-sub">Probabilidade × Impacto</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px">
          <div class="field">
            <label>Probabilidade (1–5)</label>
            <select id="rm_prob">
              <option value="1">1 — Rara</option>
              <option value="2">2 — Improvável</option>
              <option value="3" selected>3 — Possível</option>
              <option value="4">4 — Provável</option>
              <option value="5">5 — Quase certa</option>
            </select>
          </div>
          <div class="field">
            <label>Impacto (1–5)</label>
            <select id="rm_impact">
              <option value="1">1 — Insignificante</option>
              <option value="2">2 — Baixo</option>
              <option value="3" selected>3 — Moderado</option>
              <option value="4">4 — Alto</option>
              <option value="5">5 — Catastrófico</option>
            </select>
          </div>
        </div>

        {{-- Matriz 5×5 --}}
        <div class="rk-matrix-wrap">
          <div class="rk-matrix-label-y">← Probabilidade</div>
          <div class="rk-matrix" id="rm_matrix"></div>
          <div class="rk-matrix-label-x">Impacto →</div>
        </div>

        {{-- Info ativo seleccionado --}}
        <div id="rmAssetInfo" style="display:none;margin-top:16px;padding:10px 14px;border-radius:10px;background:rgba(0,0,0,.08);border:1px solid var(--border)">
          <div class="rk-section-label" style="margin-bottom:6px">Informação do ativo</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:12px">
            <div><span class="muted">Hostname:</span> <b id="rmAssetHostname">—</b></div>
            <div><span class="muted">Criticidade:</span> <b id="rmAssetCriticality">—</b></div>
            <div><span class="muted">Tipo:</span> <b id="rmAssetType">—</b></div>
            <div><span class="muted">IP:</span> <b id="rmAssetIp">—</b></div>
          </div>
        </div>

      </div>
    </div>

    {{-- Footer --}}
    <div class="treat-modal-footer">
      <span class="muted" style="font-size:12px" id="rmRef">Novo risco</span>
      <div style="display:flex;gap:8px">
        <button id="rmCancel" class="btn" type="button">Cancelar</button>
        <button id="rmSave" class="btn ok" type="button">Guardar risco</button>
      </div>
    </div>
  </div>
</div>


{{-- ══ MODAL: Criar Plano de Tratamento ══ --}}
<div id="treatModal" class="modal-overlay" aria-hidden="true" style="display:none">
  <div class="modal-card treat-detail-modal" role="dialog" aria-modal="true">

    <div class="treat-modal-header">
      <div>
        <div class="treat-modal-eyebrow">Plano de tratamento</div>
        <div class="treat-modal-title" id="tmTitle">—</div>
      </div>
      <button id="tmClose" class="btn" type="button">Fechar</button>
    </div>

    <div class="treat-context-strip">
      <div class="treat-ctx-item">
        <div class="treat-ctx-label">Risco</div>
        <div class="treat-ctx-val" id="tmRiskId">—</div>
      </div>
      <div class="treat-ctx-item">
        <div class="treat-ctx-label">Ativo</div>
        <div class="treat-ctx-val" id="tmAsset">—</div>
      </div>
      <div class="treat-ctx-item">
        <div class="treat-ctx-label">Score</div>
        <div class="treat-ctx-val" id="tmScore">—</div>
      </div>
    </div>

    <div class="treat-modal-body" style="overflow-y:auto;flex:1">
      <div class="treat-modal-col">
<div class="treat-ai-box" style="margin-bottom:14px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
            <div class="treat-ai-header" style="margin:0">Risco a tratar</div>
            <button id="tmAiBtn" class="btn small" type="button">✦ Sugerir Plano (IA)</button>
          </div>
          
          <div id="tmRiskDesc" style="font-size:13px;font-weight:600;margin-bottom:6px">—</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12px;margin-top:6px">
            <div><div class="muted" style="font-size:10px;margin-bottom:2px">Ameaça</div><div id="tmThreat" style="font-weight:600">—</div></div>
            <div><div class="muted" style="font-size:10px;margin-bottom:2px">Vulnerabilidade</div><div id="tmVuln" style="font-weight:600">—</div></div>
          </div>

          {{-- Output da IA para o Plano --}}
          <div id="tmAiOutput" style="display:none;margin-top:12px;border-radius:8px;background:rgba(255,255,255,.05);padding:10px;font-size:12px;line-height:1.6;max-height:180px;overflow-y:auto;border:1px solid rgba(96,165,250,.3)"></div>
          <div id="tmAiActions" style="display:none;margin-top:8px;gap:8px;flex-wrap:wrap">
            <button id="tmAiApply" class="btn small ok" type="button">↓ Aplicar sugestão</button>
          </div>
        </div>

        <div class="field">
          <label>Descrição do plano</label>
          <textarea id="tm_desc" rows="4" placeholder="Descreve as acções, passos e responsáveis..."></textarea>
        </div>
      </div>

      <div class="treat-modal-col">
        <div class="field">
          <label>Estratégia</label>
          <select id="tm_strategy">
            <option>Mitigar</option><option>Evitar</option>
            <option>Transferir</option><option>Aceitar</option>
          </select>
        </div>
        <div class="field">
          <label>Prazo</label>
          <input id="tm_due" type="date" />
        </div>
        <div class="field">
          <label>Responsável</label>
          <select id="tm_owner">
            <option value="">Selecionar responsável...</option>
          </select>
        </div>
        <div class="field">
          <label>Prioridade</label>
          <select id="tm_priority">
            <option>Alta</option><option selected>Média</option><option>Baixa</option>
          </select>
        </div>
      </div>
    </div>

    <div class="treat-modal-footer">
      <span class="muted" style="font-size:12px" id="tmRef">—</span>
      <div style="display:flex;gap:8px">
        <button id="tmCancel" class="btn" type="button">Cancelar</button>
        <button id="tmSave" class="btn ok" type="button">Criar plano</button>
      </div>
    </div>
  </div>
</div>


{{-- CSS ──────────────────────────────────────────────────── --}}
<style>
  .rk-section-label {
    font-size:10px;font-weight:700;letter-spacing:.08em;
    text-transform:uppercase;color:var(--muted);margin-bottom:12px;
  }

  /* KPI strip */
  .rk-kpi-strip {
    display:flex;gap:0;align-items:stretch;
    background:var(--card-bg,rgba(18,26,43,.7));
    border:1px solid var(--border);border-radius:16px;overflow:hidden;
  }
  .rk-kpi {flex:1;padding:16px 12px;text-align:center;border-right:1px solid var(--border)}
  .rk-kpi:last-child{border-right:none}
  .rk-kpi-num{font-size:28px;font-weight:900;line-height:1}
  .rk-kpi-label{font-size:10px;color:var(--muted);margin-top:4px;letter-spacing:.03em}
  .rk-kpi-critical .rk-kpi-num{color:#f87171}
  .rk-kpi-high     .rk-kpi-num{color:#fb923c}
  .rk-kpi-med      .rk-kpi-num{color:#fbbf24}
  .rk-kpi-low      .rk-kpi-num{color:#34d399}
  .rk-kpi-open     .rk-kpi-num{color:#94a3b8}
  .rk-kpi-treat    .rk-kpi-num{color:#60a5fa}

  /* Risk table */
  .rk-risk-table { width:100%; border-collapse:collapse; font-size:13px }
  .rk-risk-table th {
    text-align:left;padding:0 10px 10px;
    font-size:10px;font-weight:700;letter-spacing:.06em;
    text-transform:uppercase;color:var(--muted);
    border-bottom:1px solid var(--border);
  }
  .rk-risk-table td {
    padding:12px 10px;border-bottom:1px solid rgba(255,255,255,.04);
    vertical-align:middle;
  }
  .rk-risk-table tr:last-child td{border-bottom:none}
  .rk-risk-table tr:hover td{background:rgba(255,255,255,.02)}
  .rk-risk-bar-cell{width:4px;padding:0!important}
  .rk-risk-bar-inner{width:4px;height:40px;border-radius:999px}

  /* Score badge */
  .rk-score-pill {
    display:inline-flex;align-items:center;gap:5px;
    padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;
  }

  /* Status badge */
  .rk-status-pill {
    display:inline-block;padding:2px 10px;border-radius:99px;
    font-size:10px;font-weight:700;letter-spacing:.03em;
    background:rgba(148,163,184,.12);color:#94a3b8;
  }

  /* Modal shared */
  .rk-risk-modal, .treat-detail-modal {
    padding:0!important;display:flex;flex-direction:column;
    width:min(900px,96vw);max-height:92vh;overflow:hidden;
  }
  .treat-modal-header {
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
    padding:20px 24px 16px;border-bottom:1px solid var(--border);flex-shrink:0;
  }
  .treat-modal-eyebrow {
    font-size:10px;font-weight:700;letter-spacing:.08em;
    text-transform:uppercase;color:var(--muted);margin-bottom:4px;
  }
  .treat-modal-title{font-size:20px;font-weight:800}
  .treat-status-badge {
    font-size:11px;font-weight:700;padding:4px 12px;
    border-radius:99px;letter-spacing:.04em;white-space:nowrap;
  }
  .tsb-todo{background:rgba(148,163,184,.12);color:#94a3b8}

  .treat-context-strip{display:flex;border-bottom:1px solid var(--border);flex-shrink:0}
  .treat-ctx-item{flex:1;padding:11px 20px;border-right:1px solid var(--border)}
  .treat-ctx-item:last-child{border-right:none}
  .treat-ctx-label{font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);margin-bottom:3px}
  .treat-ctx-val{font-size:12px;font-weight:600}

  .treat-modal-body{display:grid;grid-template-columns:1fr 1fr}
  .treat-modal-col{padding:20px 24px}
  .treat-modal-col:first-child{border-right:1px solid var(--border)}
  .treat-modal-footer {
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    padding:14px 24px;border-top:1px solid var(--border);flex-shrink:0;
  }

  .treat-ai-box {
    background:rgba(96,165,250,.05);border:1px solid rgba(96,165,250,.18);
    border-radius:12px;padding:12px 14px;
  }
  .treat-ai-header {
    display:flex;align-items:center;gap:6px;
    font-size:10px;font-weight:700;letter-spacing:.07em;
    text-transform:uppercase;color:#60a5fa;margin-bottom:10px;
  }

  /* Score box */
  .rk-score-box {
    background:rgba(255,255,255,.04);border-radius:14px;padding:18px;
    text-align:center;border:2px solid rgba(255,255,255,.08);
    transition:background .3s,border-color .3s;
  }
  .rk-score-num{font-size:48px;font-weight:900;line-height:1}
  .rk-score-label{font-size:14px;font-weight:700;margin-top:4px}
  .rk-score-sub{font-size:11px;color:var(--muted);margin-top:4px}
  .rk-score-critical{background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.3)}
  .rk-score-critical .rk-score-num,.rk-score-critical .rk-score-label{color:#f87171}
  .rk-score-high{background:rgba(251,146,60,.08);border-color:rgba(251,146,60,.25)}
  .rk-score-high .rk-score-num,.rk-score-high .rk-score-label{color:#fb923c}
  .rk-score-med{background:rgba(251,191,36,.08);border-color:rgba(251,191,36,.25)}
  .rk-score-med .rk-score-num,.rk-score-med .rk-score-label{color:#fbbf24}
  .rk-score-low{background:rgba(52,211,153,.06);border-color:rgba(52,211,153,.2)}
  .rk-score-low .rk-score-num,.rk-score-low .rk-score-label{color:#34d399}

  /* Matriz 5×5 */
  .rk-matrix-wrap{margin-top:12px;position:relative}
  .rk-matrix{display:grid;grid-template-columns:repeat(5,1fr);gap:3px;margin:0 20px 16px}
  .rk-matrix-cell{
    aspect-ratio:1;border-radius:5px;display:flex;align-items:center;
    justify-content:center;font-size:10px;font-weight:700;
    opacity:.4;transition:opacity .2s,transform .2s;
  }
  .rk-matrix-cell.active{opacity:1;transform:scale(1.15);box-shadow:0 0 0 2.5px rgba(255,255,255,.7)}
  .rk-matrix-label-y{position:absolute;left:-18px;top:50%;transform:translateY(-50%) rotate(-90deg);font-size:9px;color:var(--muted);white-space:nowrap}
  .rk-matrix-label-x{text-align:center;font-size:9px;color:var(--muted)}

  @media(max-width:700px){
    .treat-modal-body{grid-template-columns:1fr}
    .treat-modal-col:first-child{border-right:none;border-bottom:1px solid var(--border)}
    .rk-kpi-strip{flex-wrap:wrap}
    .rk-kpi{flex-basis:33%}
  }
</style>

@endsection
@push('scripts')
  @vite(['resources/js/pages/risks.js'])
@endpush