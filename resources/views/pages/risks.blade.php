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
      @permission('risk.create')
      <button id="btnNewRisk" class="btn ok" type="button">+ Novo risco</button>
      @endpermission
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
            @permission('risk.edit')
            <div class="rk-section-label" style="margin:0">Assistente IA</div>
            <button id="rmAiBtn" class="btn small" type="button">✦ Analisar com IA</button>
            @endpermission
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


@push('styles')
@vite(['resources/css/pages/risks.css'])
@endpush

@endsection
@push('scripts')
  @vite(['resources/js/pages/risks.js'])
@endpush