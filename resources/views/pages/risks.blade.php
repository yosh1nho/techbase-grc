@extends('layouts.app')
@section('title', 'Gestão de Risco • Techbase GRC')

@section('content')

  {{-- ══ KPI STRIP ══ --}}
  <div class="rk-kpi-strip">
    <div class="rk-kpi">
      <div class="rk-kpi-num" id="rkKpiTotal">0</div>
      <div class="rk-kpi-label">Riscos totais</div>
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

  {{-- ══ ALERTAS ══ --}}
  <div class="card" style="margin-top:16px" id="wazuh">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:16px">
      <div>
        <h3 style="margin:0">Alertas recentes — recomendações IA</h3>
        <p class="muted" style="font-size:12px;margin:3px 0 0">Seleciona um alerta para ver contexto e gerar ações. <span style="opacity:.6">(RF17 · mock)</span></p>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button id="btnCreateTreatmentFromAlert" class="btn" type="button" disabled>
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Criar plano
        </button>
        <button id="btnCreateRiskFromAlert" class="btn" type="button" disabled>
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Registar risco
        </button>
        <button id="btnCNCS24h" class="btn" type="button" disabled>
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          CNCS 24h
        </button>
        <a id="btnGoTreatment" class="btn" href="{{ route('treatment') }}" style="text-decoration:none;display:none">Ver em Tratamento →</a>
      </div>
    </div>

    {{-- Grid de cards de alertas + painel de detalhe --}}
    <div class="rk-alerts-layout">
      {{-- Lista de cards --}}
      <div id="alertCardsList" class="rk-alert-cards"></div>

      {{-- Painel de contexto (lado direito) --}}
      <div class="rk-context-panel" id="alertContextPanel">
        <div class="rk-context-empty" id="alertContextEmpty">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:.3;margin-bottom:8px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <span class="muted" style="font-size:13px">Seleciona um alerta<br>para ver o contexto</span>
        </div>
        <div id="alertContextContent" style="display:none">
          <div class="rk-ctx-header">
            <div>
              <div class="rk-ctx-eyebrow">Alerta seleccionado</div>
              <div class="rk-ctx-title" id="ctxTitle">—</div>
            </div>
            <span id="ctxSevBadge" class="treat-status-badge tsb-todo">—</span>
          </div>

          <div class="rk-ctx-body">
            <div class="rk-ctx-row">
              <div class="rk-ctx-item">
                <div class="rk-ctx-lbl">Ativo</div>
                <div class="rk-ctx-val" id="ctxAsset">—</div>
              </div>
              <div class="rk-ctx-item">
                <div class="rk-ctx-lbl">Criticidade</div>
                <div class="rk-ctx-val" id="ctxCriticality">—</div>
              </div>
            </div>
            <div class="rk-ctx-row">
              <div class="rk-ctx-item">
                <div class="rk-ctx-lbl">Owner</div>
                <div class="rk-ctx-val" id="ctxOwner">—</div>
              </div>
              <div class="rk-ctx-item">
                <div class="rk-ctx-lbl">Confiança IA</div>
                <div class="rk-ctx-val" id="ctxConfidence">—</div>
              </div>
            </div>

            <div class="rk-ctx-risk-box">
              <div class="rk-ctx-lbl" style="margin-bottom:4px">Risco sugerido</div>
              <div id="ctxRisk" style="font-size:13px;font-weight:600">—</div>
            </div>

            <div class="rk-ctx-lbl" style="margin-bottom:6px">Ações sugeridas (IA)</div>
            <div id="ctxActions" class="treat-ai-steps" style="font-size:12px"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ══ RISCOS REGISTADOS ══ --}}
  <div class="card" style="margin-top:16px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:16px">
      <div>
        <h3 style="margin:0">Riscos registados <span class="muted" style="font-weight:400;font-size:13px">(RF7 · RF8 · RF9 · RF12)</span></h3>
        <p class="muted" style="font-size:12px;margin:3px 0 0">Guardado em localStorage (mock). Score 5×5 — Probabilidade × Impacto.</p>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input id="riskSearchInput" placeholder="Pesquisar ID, ativo, descrição..."
          style="padding:7px 12px;border-radius:10px;font-size:13px;min-width:220px;
                 background:var(--input-bg);border:1px solid var(--border);color:inherit" />
        <select id="riskLevelFilter"
          style="padding:7px 12px;border-radius:10px;font-size:13px;background:var(--input-bg);border:1px solid var(--border);color:inherit">
          <option value="all">Todos os níveis</option>
          <option value="Muito Alta">Muito Alta</option>
          <option value="Alta">Alta</option>
          <option value="Média">Média</option>
          <option value="Baixa">Baixa</option>
        </select>
        <select id="riskStatusFilter"
          style="padding:7px 12px;border-radius:10px;font-size:13px;background:var(--input-bg);border:1px solid var(--border);color:inherit">
          <option value="all">Todos os estados</option>
          <option value="Aberto">Aberto</option>
          <option value="Em tratamento">Em tratamento</option>
          <option value="Mitigado">Mitigado</option>
          <option value="Aceito">Aceito</option>
        </select>
        <button id="btnRemoveRisks" class="btn" type="button" disabled style="font-size:12px">Remover sel.</button>
        <button id="btnClearRisks" class="btn" type="button" style="font-size:12px">Limpar (mock)</button>
      </div>
    </div>

    <div id="riskCardsContainer" class="rk-risk-cards"></div>
  </div>

  {{-- ══ MODAL: Registar / Editar Risco ══ --}}
  <div id="riskAlertModal" class="modal-overlay is-hidden" aria-hidden="true">
    <div class="modal-card rk-risk-modal" role="dialog" aria-modal="true">
    <input type="hidden" id="ra_alertId" value="">

      {{-- Header --}}
      <div class="treat-modal-header">
        <div>
          <div class="treat-modal-eyebrow">Registo de risco — Matriz 5×5</div>
          <div class="treat-modal-title" id="riskAlertTitle">—</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <span id="rk_score_badge" class="treat-status-badge tsb-todo">Score: —</span>
          <button id="riskAlertClose" class="btn" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>

      {{-- Context strip --}}
      <div class="treat-context-strip">
        <div class="treat-ctx-item">
          <div class="treat-ctx-label">ID</div>
          <div class="treat-ctx-val" id="rk_id_disp">—</div>
        </div>
        <div class="treat-ctx-item">
          <div class="treat-ctx-label">Ativo</div>
          <div class="treat-ctx-val" id="rk_asset_disp">—</div>
        </div>
        <div class="treat-ctx-item">
          <div class="treat-ctx-label">Fonte / Alerta</div>
          <div class="treat-ctx-val" id="rk_alert_disp">—</div>
        </div>
        <div class="treat-ctx-item">
          <div class="treat-ctx-label">Criado em</div>
          <div class="treat-ctx-val" id="rk_created_disp">—</div>
        </div>
      </div>

      {{-- Corpo 2 colunas --}}
      <div class="treat-modal-body">

        {{-- Col esquerda: Identificação --}}
        <div class="treat-modal-col">
          <div style="font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:12px">Identificação</div>

          <div class="field">
            <label>Descrição do risco</label>
            <input id="ra_desc" placeholder="ex.: Acesso indevido por força bruta (SSH)" />
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div class="field">
              <label>Ameaça</label>
              <input id="ra_threat" placeholder="ex.: Ataque de força bruta" />
            </div>
            <div class="field">
              <label>Vulnerabilidade</label>
              <input id="ra_vuln" placeholder="ex.: Sem MFA" />
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div class="field">
              <label>Responsável do risco</label>
              <input id="ra_owner" placeholder="ex.: João — IT Ops" />
            </div>
            <div class="field">
              <label>Data alvo</label>
              <input id="ra_due" type="date" />
            </div>
          </div>

          {{-- Impactos CIA --}}
          <div class="field">
            <label>Impactos (CIA)</label>
            <div style="display:flex;gap:16px;margin-top:6px">
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                <input id="ra_c" type="checkbox" checked style="width:15px;height:15px"> Confidencialidade
              </label>
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                <input id="ra_i" type="checkbox" checked style="width:15px;height:15px"> Integridade
              </label>
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                <input id="ra_a" type="checkbox" style="width:15px;height:15px"> Disponibilidade
              </label>
            </div>
          </div>

          {{-- Ações resumo --}}
          <div class="field">
            <label>Ações (resumo)</label>
            <textarea id="ra_actions" rows="3" placeholder="ex.: Bloquear IPs, ativar MFA, rate-limit..."></textarea>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div class="field">
              <label>Responsável pelo tratamento</label>
              <input id="ra_action_owner" placeholder="ex.: Inês — SI" />
            </div>
            <div class="field">
              <label>Estratégia</label>
              <select id="ra_strategy">
                <option>Mitigar/Tratar</option>
                <option>Aceitar</option>
                <option>Evitar</option>
                <option>Transferir</option>
              </select>
            </div>
          </div>
        </div>

        {{-- Col direita: Avaliação 5×5 + estado --}}
        <div class="treat-modal-col">
          <div style="font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:12px">Avaliação de risco</div>

          {{-- Score visual --}}
          <div class="rk-score-box" id="rk_score_box">
            <div class="rk-score-num" id="ra_score">9</div>
            <div class="rk-score-label" id="ra_level">Médio</div>
            <div class="rk-score-sub">Probabilidade × Impacto</div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px">
            <div class="field">
              <label>Probabilidade (1–5)</label>
              <select id="ra_prob">
                <option value="1">1 — Rara</option>
                <option value="2">2 — Improvável</option>
                <option value="3" selected>3 — Possível</option>
                <option value="4">4 — Provável</option>
                <option value="5">5 — Quase certa</option>
              </select>
            </div>
            <div class="field">
              <label>Impacto (1–5)</label>
              <select id="ra_impact">
                <option value="1">1 — Insignificante</option>
                <option value="2">2 — Baixo</option>
                <option value="3" selected>3 — Moderado</option>
                <option value="4">4 — Alto</option>
                <option value="5">5 — Catastrófico</option>
              </select>
            </div>
          </div>

          {{-- Mini matriz 5×5 visual --}}
          <div class="rk-matrix-wrap">
            <div class="rk-matrix-label-y">← Probabilidade</div>
            <div class="rk-matrix" id="rk_matrix"></div>
            <div class="rk-matrix-label-x">Impacto →</div>
          </div>

          <div class="field" style="margin-top:14px">
            <label>Estado</label>
            <select id="ra_status">
              <option selected>Aberto</option>
              <option>Em tratamento</option>
              <option>Mitigado</option>
              <option>Aceito</option>
              <option>Evitado</option>
              <option>Transferido</option>
            </select>
          </div>

          {{-- Contexto do alerta --}}
          <div style="margin-top:14px">
            <div style="font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:8px">Contexto do alerta</div>
            <div id="ra_alertPreview" class="rk-alert-preview">—</div>
          </div>
        </div>
      </div>

      {{-- Footer --}}
      <div class="treat-modal-footer">
        <span class="muted" style="font-size:12px" id="rk_id_ref">Novo risco</span>
        <div style="display:flex;gap:8px">
          <button id="riskAlertClose2" class="btn" type="button">Cancelar</button>
          <button id="ra_save" class="btn primary" type="button">Guardar risco</button>
        </div>
      </div>
    </div>
  </div>

  {{-- ══ MODAL: Criar Plano a partir do Alerta ══ --}}
  <div id="treatmentAlertModal" class="modal-overlay is-hidden" aria-hidden="true">
    <div class="modal-card treat-detail-modal" role="dialog" aria-modal="true">

      <div class="treat-modal-header">
        <div>
          <div class="treat-modal-eyebrow">Plano de tratamento — RF10 · RF11</div>
          <div class="treat-modal-title">Criar a partir de alerta</div>
        </div>
        <button id="treatmentAlertClose" class="btn" type="button">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="treat-context-strip">
        <div class="treat-ctx-item">
          <div class="treat-ctx-label">Alerta</div>
          <div class="treat-ctx-val" id="ta_alert_disp">—</div>
        </div>
        <div class="treat-ctx-item">
          <div class="treat-ctx-label">Ativo</div>
          <div class="treat-ctx-val" id="ta_asset_disp">—</div>
        </div>
        <div class="treat-ctx-item">
          <div class="treat-ctx-label">Risco sugerido</div>
          <div class="treat-ctx-val" id="ta_risk_disp">—</div>
        </div>
      </div>

      <div class="treat-modal-body">
        <div class="treat-modal-col">
          <div class="treat-ai-box">
            <div class="treat-ai-header">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
              Ações sugeridas pela IA
            </div>
            <div id="ta_ai_steps" class="treat-ai-steps"></div>
          </div>

          <div class="field" style="margin-top:14px">
            <label>Descrição do plano <span class="muted" style="font-weight:400">(o que será feito)</span></label>
            <textarea id="ta_plan_desc" rows="4" placeholder="Descreve as ações, passos e responsáveis..."></textarea>
          </div>
        </div>

        <div class="treat-modal-col">
          <div class="field">
            <label>Estratégia (RF10)</label>
            <select id="ta_strategy">
              <option>Mitigar</option><option>Evitar</option>
              <option>Transferir</option><option>Aceitar</option>
            </select>
          </div>
          <div class="field">
            <label>Prazo</label>
            <input id="ta_due" type="date" />
          </div>
          <div class="field">
            <label>Responsável</label>
            <input id="ta_owner" placeholder="ex.: SOC / IT Ops" />
          </div>
          <div class="field">
            <label>Prioridade</label>
            <select id="ta_priority">
              <option>Alta</option><option>Média</option><option>Baixa</option>
            </select>
          </div>
        </div>
      </div>

      <div class="treat-modal-footer">
        <span class="muted" style="font-size:12px" id="ta_ref">—</span>
        <div style="display:flex;gap:8px">
          <button id="treatmentAlertClose2" class="btn" type="button">Cancelar</button>
          <button id="ta_save" class="btn primary" type="button">Guardar plano</button>
        </div>
      </div>
    </div>
  </div>

  {{-- ══ MODAL: CNCS 24h ══ --}}
  <div id="cncs24hModal" class="modal-overlay is-hidden" aria-hidden="true">
    <div class="modal-card treat-detail-modal" role="dialog" aria-modal="true" style="max-width:700px">
      <div class="treat-modal-header">
        <div>
          <div class="treat-modal-eyebrow">NIS2 · Notificação inicial (24h)</div>
          <div class="treat-modal-title">Draft para CNCS</div>
        </div>
        <div style="display:flex;gap:8px">
          <button id="btnPrintCNCS" class="btn primary" type="button" style="font-size:12px">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Guardar PDF
          </button>
          <button id="btnCloseCNCS" class="btn" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>

      <div id="cncs24hBody" style="flex:1;overflow-y:auto;padding:20px 24px"></div>

      <div class="treat-modal-footer">
        <div class="muted" style="font-size:12px">
          <b>CNCS:</b> incidentes@cncs.gov.pt · Tel 24/7: +351 210 012 000
        </div>
        <button id="btnCloseCNCS2" class="btn" type="button">Fechar</button>
      </div>
    </div>
  </div>

  {{-- Campos ocultos para compatibilidade com risks.js existente --}}
  <input type="hidden" id="ra_id" />
  <input type="hidden" id="ra_alert" />
  <input type="hidden" id="ra_asset" />
  <input type="hidden" id="ta_alert" />
  <input type="hidden" id="ta_asset" />
  <input type="hidden" id="ta_risk" />
  <input type="hidden" id="ta_actions" />

  <style>
    /* ══ CSS Variables (fallback — definidas no app.blade mas garantimos aqui) ══ */
    :root {
      --rk-input-bg:     rgba(0,0,0,.22);
      --rk-input-border: rgba(255,255,255,.14);
      --rk-card-bg:      rgba(18,26,43,.70);
      --rk-panel-border: rgba(255,255,255,.09);
    }
    :root[data-theme="light"] {
      --rk-input-bg:     #ffffff;
      --rk-input-border: rgba(15,23,42,.20);
      --rk-card-bg:      #ffffff;
      --rk-panel-border: rgba(15,23,42,.11);
    }

    /* ══ Input override — visibilidade garantida em ambos os temas ══ */
    .rk-risk-modal input:not([type="checkbox"]),
    .rk-risk-modal select,
    .rk-risk-modal textarea,
    #treatmentAlertModal input:not([type="checkbox"]),
    #treatmentAlertModal select,
    #treatmentAlertModal textarea,
    #cncs24hModal input:not([type="checkbox"]),
    #cncs24hModal select,
    #cncs24hModal textarea {
      background: var(--rk-input-bg) !important;
      border: 1.5px solid var(--rk-input-border) !important;
      border-radius: 10px;
      padding: 9px 12px;
      color: var(--text);
      font-size: 13px;
      font-family: inherit;
      width: 100%;
      transition: border-color .15s, box-shadow .15s;
    }
    .rk-risk-modal input:not([type="checkbox"]):focus,
    .rk-risk-modal select:focus,
    .rk-risk-modal textarea:focus,
    #treatmentAlertModal input:not([type="checkbox"]):focus,
    #treatmentAlertModal select:focus,
    #treatmentAlertModal textarea:focus {
      outline: none;
      border-color: rgba(96,165,250,.55) !important;
      box-shadow: 0 0 0 3px rgba(96,165,250,.12);
    }
    :root[data-theme="light"] .rk-risk-modal input:not([type="checkbox"]):focus,
    :root[data-theme="light"] .rk-risk-modal select:focus,
    :root[data-theme="light"] .rk-risk-modal textarea:focus,
    :root[data-theme="light"] #treatmentAlertModal input:not([type="checkbox"]):focus,
    :root[data-theme="light"] #treatmentAlertModal select:focus,
    :root[data-theme="light"] #treatmentAlertModal textarea:focus {
      border-color: rgba(37,99,235,.50) !important;
      box-shadow: 0 0 0 3px rgba(37,99,235,.10);
    }

    /* select arrow no light */
    :root[data-theme="light"] .rk-risk-modal select,
    :root[data-theme="light"] #treatmentAlertModal select {
      background-image:
        linear-gradient(45deg, transparent 50%, rgba(15,23,42,.55) 50%),
        linear-gradient(135deg, rgba(15,23,42,.55) 50%, transparent 50%) !important;
      background-position: calc(100% - 18px) 55%, calc(100% - 12px) 55% !important;
      background-size: 6px 6px, 6px 6px !important;
      background-repeat: no-repeat !important;
    }

    /* ── KPI Strip ── */
    .rk-kpi-strip {
      display:flex; gap:0; align-items:stretch;
      background:var(--rk-card-bg); border:1px solid var(--rk-panel-border);
      border-radius:16px; overflow:hidden;
    }
    .rk-kpi {
      flex:1; padding:16px 12px; text-align:center;
      border-right:1px solid var(--rk-panel-border);
    }
    .rk-kpi:last-child { border-right:none; }
    .rk-kpi-num   { font-size:28px; font-weight:900; line-height:1; }
    .rk-kpi-label { font-size:10px; color:var(--muted); margin-top:4px; letter-spacing:.03em; }
    .rk-kpi-critical .rk-kpi-num { color:#f87171; }
    .rk-kpi-high     .rk-kpi-num { color:#fb923c; }
    .rk-kpi-med      .rk-kpi-num { color:#fbbf24; }
    .rk-kpi-low      .rk-kpi-num { color:#34d399; }
    .rk-kpi-open     .rk-kpi-num { color:#94a3b8; }
    .rk-kpi-treat    .rk-kpi-num { color:#60a5fa; }

    /* ── Alerts layout ── */
    .rk-alerts-layout {
      display:grid; grid-template-columns:1fr 340px; gap:16px; align-items:start;
    }
    .rk-alert-cards { display:flex; flex-direction:column; gap:8px; }

    /* Alert card */
    .rk-alert-card {
      display:flex; align-items:center; gap:12px;
      padding:12px 14px; border-radius:12px;
      border:1.5px solid var(--rk-panel-border); cursor:pointer;
      transition:border-color .15s, background .15s;
      position:relative; overflow:hidden;
      background: var(--rk-card-bg);
    }
    .rk-alert-card:hover { border-color:rgba(96,165,250,.35); background:rgba(96,165,250,.04); }
    .rk-alert-card.selected {
      border-color:rgba(96,165,250,.55);
      background:rgba(96,165,250,.07);
    }
    .rk-alert-card-bar { position:absolute; left:0; top:0; bottom:0; width:3px; }
    .rk-alert-icon {
      width:34px; height:34px; border-radius:10px; flex-shrink:0;
      display:flex; align-items:center; justify-content:center;
    }
    .rk-alert-icon svg { display:block; }
    .rk-alert-body { flex:1; min-width:0; }
    .rk-alert-top  { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:3px; }
    .rk-alert-sev  { font-size:10px; font-weight:700; padding:2px 8px; border-radius:99px; letter-spacing:.04em; }
    .rk-alert-asset { font-weight:700; font-size:13px; }
    .rk-alert-msg   { font-size:12px; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .rk-alert-ts    { font-size:11px; color:var(--muted); margin-left:auto; flex-shrink:0; }

    /* Context panel */
    .rk-context-panel {
      background:var(--rk-card-bg); border:1px solid var(--rk-panel-border);
      border-radius:14px; overflow:hidden; position:sticky; top:16px;
    }
    .rk-context-empty {
      display:flex; flex-direction:column; align-items:center; justify-content:center;
      padding:40px 20px; text-align:center;
    }
    .rk-ctx-header {
      display:flex; align-items:flex-start; justify-content:space-between;
      padding:14px 16px; border-bottom:1px solid var(--rk-panel-border);
    }
    .rk-ctx-eyebrow {
      font-size:9px; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
      color:var(--muted); margin-bottom:3px;
    }
    .rk-ctx-title { font-size:14px; font-weight:800; }
    .rk-ctx-body  { padding:14px 16px; display:flex; flex-direction:column; gap:10px; }
    .rk-ctx-row   { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
    .rk-ctx-lbl   { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); margin-bottom:2px; }
    .rk-ctx-val   { font-size:12px; font-weight:600; }
    .rk-ctx-risk-box {
      background:rgba(248,113,113,.06); border:1px solid rgba(248,113,113,.18);
      border-radius:10px; padding:10px 12px;
    }

    /* ── Risk cards ── */
    .rk-risk-cards { display:flex; flex-direction:column; gap:8px; }
    .rk-risk-card {
      display:grid; grid-template-columns:auto 1fr auto auto auto auto;
      align-items:center; gap:12px; padding:12px 16px;
      border:1px solid var(--rk-panel-border); border-radius:12px;
      background: var(--rk-card-bg);
      transition:border-color .15s;
      position:relative; overflow:hidden;
    }
    .rk-risk-card:hover { border-color:rgba(96,165,250,.3); }
    .rk-risk-bar  { position:absolute; left:0; top:0; bottom:0; width:3px; }
    .rk-risk-id   { font-size:11px; font-weight:700; color:var(--muted); min-width:70px; padding-left:6px; }
    .rk-risk-main { min-width:0; }
    .rk-risk-desc { font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .rk-risk-meta { font-size:11px; color:var(--muted); margin-top:2px; }
    .rk-risk-score-badge { font-size:18px; font-weight:900; min-width:36px; text-align:center; }
    .rk-risk-level { font-size:10px; font-weight:700; padding:3px 10px; border-radius:99px; }
    .rk-risk-status {
      font-size:10px; font-weight:700; padding:3px 10px; border-radius:99px;
      background:rgba(148,163,184,.12); color:#94a3b8;
    }
    .rk-risk-check { display:flex; align-items:center; }

    /* ══ Modal shared styles (duplicados de treatment_blade para independence) ══ */
    .treat-detail-modal,
    .rk-risk-modal {
      padding:0 !important; display:flex; flex-direction:column;
      max-width:880px; width:100%; max-height:90vh;
      overflow:hidden;
    }
    .treat-modal-header {
      display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
      padding:20px 24px 16px; border-bottom:1px solid var(--modal-border);
      flex-shrink:0;
    }
    .treat-modal-eyebrow {
      font-size:10px; font-weight:700; letter-spacing:.08em;
      text-transform:uppercase; color:var(--muted); margin-bottom:4px;
    }
    .treat-modal-title { font-size:20px; font-weight:800; }

    .treat-status-badge {
      font-size:11px; font-weight:700; padding:4px 12px;
      border-radius:99px; letter-spacing:.04em; white-space:nowrap;
    }
    .tsb-todo    { background:rgba(148,163,184,.12); color:#94a3b8; }
    .tsb-doing   { background:rgba(96,165,250,.12);  color:#60a5fa; }
    .tsb-done    { background:rgba(52,211,153,.1);   color:#34d399; }
    .tsb-overdue { background:rgba(248,113,113,.12); color:#f87171; }

    .treat-context-strip {
      display:flex; border-bottom:1px solid var(--modal-border); flex-shrink:0;
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

    .treat-modal-footer {
      display:flex; align-items:center; justify-content:space-between; gap:12px;
      padding:14px 24px; border-top:1px solid var(--modal-border); flex-shrink:0;
    }

    /* ── Modal Risk Score box ── */
    .rk-score-box {
      background:rgba(255,255,255,.04); border-radius:14px; padding:18px;
      text-align:center; border:2px solid rgba(255,255,255,.08);
      transition:background .3s, border-color .3s;
    }
    .rk-score-num   { font-size:48px; font-weight:900; line-height:1; }
    .rk-score-label { font-size:14px; font-weight:700; margin-top:4px; }
    .rk-score-sub   { font-size:11px; color:var(--muted); margin-top:4px; }

    .rk-score-critical { background:rgba(248,113,113,.1); border-color:rgba(248,113,113,.3); }
    .rk-score-critical .rk-score-num, .rk-score-critical .rk-score-label { color:#f87171; }
    .rk-score-high { background:rgba(251,146,60,.08); border-color:rgba(251,146,60,.25); }
    .rk-score-high .rk-score-num, .rk-score-high .rk-score-label { color:#fb923c; }
    .rk-score-med  { background:rgba(251,191,36,.08); border-color:rgba(251,191,36,.25); }
    .rk-score-med  .rk-score-num, .rk-score-med .rk-score-label { color:#fbbf24; }
    .rk-score-low  { background:rgba(52,211,153,.06); border-color:rgba(52,211,153,.2); }
    .rk-score-low  .rk-score-num, .rk-score-low .rk-score-label { color:#34d399; }

    /* ── Matrix 5×5 ── */
    .rk-matrix-wrap { margin-top:12px; position:relative; }
    .rk-matrix {
      display:grid; grid-template-columns:repeat(5,1fr); gap:3px;
      margin:0 20px 16px 20px;
    }
    .rk-matrix-cell {
      aspect-ratio:1; border-radius:5px; display:flex; align-items:center;
      justify-content:center; font-size:10px; font-weight:700;
      opacity:.4; transition:opacity .2s, transform .2s;
    }
    .rk-matrix-cell.active { opacity:1; transform:scale(1.15); box-shadow:0 0 0 2.5px rgba(255,255,255,.7); }
    :root[data-theme="light"] .rk-matrix-cell.active { box-shadow:0 0 0 2.5px rgba(15,23,42,.35); }
    .rk-matrix-label-y {
      position:absolute; left:-18px; top:50%; transform:translateY(-50%) rotate(-90deg);
      font-size:9px; color:var(--muted); white-space:nowrap; letter-spacing:.04em;
    }
    .rk-matrix-label-x {
      text-align:center; font-size:9px; color:var(--muted); letter-spacing:.04em; margin-top:2px;
    }

    /* ── Alert preview in modal ── */
    .rk-alert-preview {
      background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08);
      border-radius:10px; padding:10px 12px; font-size:12px; line-height:1.55;
      color:var(--muted);
    }
    :root[data-theme="light"] .rk-alert-preview {
      background:rgba(15,23,42,.04); border-color:rgba(15,23,42,.12);
    }

    /* ── light theme modal overrides ── */
    :root[data-theme="light"] .treat-ai-box {
      background:rgba(37,99,235,.04); border-color:rgba(37,99,235,.15);
    }
    :root[data-theme="light"] .treat-ai-step-num {
      background:rgba(37,99,235,.10); color:#2563eb;
    }
    :root[data-theme="light"] .treat-ai-header { color:#2563eb; }
    :root[data-theme="light"] .rk-score-box {
      background:rgba(15,23,42,.04); border-color:rgba(15,23,42,.14);
    }
    :root[data-theme="light"] .rk-ctx-risk-box {
      background:rgba(220,38,38,.04); border-color:rgba(220,38,38,.15);
    }
    :root[data-theme="light"] .tsb-todo    { background:rgba(15,23,42,.08);  color:#64748b; }
    :root[data-theme="light"] .tsb-doing   { background:rgba(37,99,235,.10); color:#2563eb; }
    :root[data-theme="light"] .tsb-done    { background:rgba(22,163,74,.10); color:#16a34a; }
    :root[data-theme="light"] .tsb-overdue { background:rgba(220,38,38,.10); color:#dc2626; }

    @media (max-width:700px) {
      .rk-alerts-layout { grid-template-columns:1fr; }
      .rk-context-panel { position:static; }
      .rk-risk-card { grid-template-columns:auto 1fr auto; }
      .treat-modal-body { grid-template-columns:1fr; }
      .treat-modal-col:first-child { border-right:none; border-bottom:1px solid var(--modal-border); }
    }
  </style>

@endsection
@push('scripts')
  @vite(['resources/js/pages/risks.js'])
@endpush
