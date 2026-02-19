@extends('layouts.app')

@section('title', 'Gestão de Risco • Techbase GRC')

@section('content')
    <div class="card">
        <h3>Riscos (RF7, RF8, RF9, RF12)</h3>
        <div style="height:12px"></div>

<div class="panel" id="wazuh">
  <h2>Registo de riscos + recomendações (RF8)</h2>

  <div style="height:10px"></div>

  <div class="split">
    <div class="panel">
      <h2 style="margin-bottom:6px">Alertas (Wazuh) → Recomendações IA</h2>
      <p class="hint">
        Mock (RF17): clique num alerta para ver contexto e sugerir ações. Pode gerar um plano (RF10/RF11).
      </p>

      <table>
        <thead>
          <tr>
            <th>Data</th>
            <th>Sever.</th>
            <th>Ativo</th>
            <th>Risco sugerido</th>
            <th>Recomendação IA</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="wazuhRiskTbody">
          <tr>
            <td class="muted">—</td><td class="muted">—</td><td class="muted">—</td>
            <td class="muted">—</td><td class="muted">—</td><td class="muted">—</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="panel">
      <h2 style="margin-bottom:6px">Detalhe do alerta</h2>
      <div id="alertContextBox" class="muted">Seleciona um alerta para ver o contexto.</div>

      <div style="height:10px"></div>

      <div class="kpirow">
        <span class="chip" id="aiConfidenceChip">Confiança IA: —</span>
        <span class="chip" id="suggestedStatusChip">Classe risco: —</span>
      </div>

      <div style="height:10px"></div>

      <button id="btnCreateTreatmentFromAlert" class="btn warn" type="button" disabled>
        Criar plano de tratamento
      </button>

      <button id="btnCNCS24h" class="btn primary" type="button" disabled>
        Gerar notificação CNCS (24h)
      </button>

      <button id="btnCreateRiskFromAlert" class="btn primary" type="button" disabled>Criar risco</button>
      <a id="btnGoTreatment" class="btn" href="{{ route('treatment') }}" style="text-decoration:none; display:none;">
        Ver em Tratamento
      </a>
    </div>
  </div>
</div>

<div style="height:12px"></div>

{{-- Tabela: Riscos --}}
<div class="panel" id="risksListPanel">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap">
    <div>
      <h2 style="margin-bottom:6px">Riscos registados no sistema</h2>
      <p class="muted">Criados a partir de alertas (mock). Guardado em localStorage.</p>
    </div>

    <div style="display:flex; gap:10px; align-items:center">
      <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
  <input id="riskSearchInput" class="input" style="min-width:260px"
         placeholder="Pesquisar por ID, Ativo, Descrição..." />

    <select id="riskSearchField" class="select">
      <option value="all">Tudo</option>
      <option value="id">ID</option>
      <option value="asset">Ativo</option>
      <option value="description">Descrição</option>
      <option value="status">Estado</option>
      <option value="strategy">Estratégia</option>
      <option value="sourceLabel">Fonte</option>
    </select>

    <button id="btnClearRiskSearch" class="btn" type="button">Limpar pesquisa</button>
  </div>

      <span class="chip">Total: <b id="risksCount">0</b></span>
      <button id="btnClearRisks" class="btn" type="button">Limpar (mock)</button>
      <button id="btnRemoveRisks" class="btn" type="button" disabled>Remover selecionados</button>
    </div>
  </div>

  <table style="margin-top:10px">
    <thead>
      <tr>
        <th>Sel.</th>
        <th>ID</th>
        <th>Ativo</th>
        <th>Descrição</th>
        <th>Prob.</th>
        <th>Impacto</th>
        <th>Nível</th>
        <th>Estratégia</th>
        <th>Estado</th>
        <th>Fonte</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="risksTbody">
      <tr><td class="muted" colspan="101">Sem riscos registados ainda.</td></tr>
    </tbody>
  </table>

  <p class="hint" style="margin-top:8px">
    Depois ligamos isto ao backend e ao algoritmo de análise preditiva (score sugerido).
  </p>
</div>


{{-- MODAL: Criar Plano a partir do Alerta --}}
<div id="treatmentAlertModal" class="modal-overlay is-hidden" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="treatmentAlertTitle">
    <div class="modal-header">
      <div>
        <div class="muted" style="margin-bottom:4px">Plano de tratamento</div>
        <div id="treatmentAlertTitle" style="font-size:18px;font-weight:800">Criar a partir de alerta</div>
      </div>
      <div style="display:flex; gap:10px; align-items:center">
        <button id="treatmentAlertClose" class="btn" type="button">Fechar</button>
      </div>
    </div>

    <div style="height:10px"></div>

    <div class="panel">
      <div class="two">
        <div class="field">
          <label>Alerta</label>
          <input id="ta_alert" disabled />
        </div>
        <div class="field">
          <label>Ativo</label>
          <input id="ta_asset" disabled />
        </div>
      </div>

      <div class="field">
        <label>Risco</label>
        <input id="ta_risk" placeholder="ex.: Exposição a malware / credenciais comprometidas" readonly/>
      </div>

      <div class="two">
        <div class="field">
          <label>Estratégia (RF10)</label>
          <select id="ta_strategy">
            <option>Mitigar</option>
            <option>Evitar</option>
            <option>Transferir</option>
            <option>Aceitar</option>
          </select>
        </div>
        <div class="field">
          <label>Prazo</label>
          <input id="ta_due" placeholder="YYYY-MM-DD" />
        </div>
      </div>

      <div class="two">
        <div class="field">
          <label>Responsável</label>
          <input id="ta_owner" placeholder="ex.: SOC / IT Ops" />
        </div>
        <div class="field">
          <label>Prioridade</label>
          <select id="ta_priority">
            <option>Alta</option>
            <option>Média</option>
            <option>Baixa</option>
          </select>
        </div>
      </div>

      <div class="field">
        <label>Ações sugeridas (IA)</label>
        <textarea id="ta_actions" rows="4" readonly></textarea>
      </div>

      <div class="field">
        <label>Descrição do plano (o que será feito)</label>
        <textarea id="ta_plan_desc" rows="4" placeholder="Descreve as ações que serão executadas, passos, responsáveis, etc."></textarea>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end">
        <button id="ta_save" class="btn warn" type="button">Guardar plano</button>
      </div>
    </div>
  </div>
</div>

{{-- MODAL: Notificação CNCS 24h --}}
<div id="cncs24hModal" class="modal-overlay is-hidden" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true">
    <div class="modal-header">
      <div>
        <div class="muted" style="margin-bottom:4px">NIS2 • Notificação inicial (24h)</div>
        <div style="font-size:18px;font-weight:800">Draft para CNCS</div>
      </div>
      <div style="display:flex; gap:10px; align-items:center">
        <button id="btnPrintCNCS" class="btn ok" type="button">Imprimir / Guardar PDF</button>
        <button id="btnCloseCNCS" class="btn" type="button">Fechar</button>
      </div>
    </div>

    <div style="height:10px"></div>

    <div id="cncs24hBody" class="panel" style="max-height:70vh; overflow:auto;">
      {{-- JS injeta o conteúdo aqui --}}
    </div>

    <div style="height:10px"></div>

    <div class="panel">
      <b>Contacto CNCS (referência do template)</b>
      <div class="muted" style="margin-top:6px">
        Email: incidentes@cncs.gov.pt • Tel 24/7: +351 210 012 000
      </div>
    </div>
  </div>
</div>

{{-- MODAL: Criar/Ver Risco (a partir de alerta) --}}
<div id="riskAlertModal" class="modal-overlay is-hidden" aria-hidden="true">
  <input type="hidden" id="ra_alertId" value="">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="riskAlertTitle">
    <div class="modal-header">
      <div>
        <div class="muted" style="margin-bottom:4px">Registo de risco (5×5)</div>
        <div id="riskAlertTitle" style="font-size:18px;font-weight:800">—</div>
      </div>
      <div style="display:flex; gap:10px; align-items:center">
        <button id="riskAlertClose" class="btn" type="button">Fechar</button>
      </div>
    </div>

    <div style="height:10px"></div>

    <div class="two">
      {{-- ESQ: formulário estilo CNCS --}}
      <div class="panel">
        <h2>Resumo do Risco</h2>

        <div class="two">
          <div class="field">
            <label>ID</label>
            <input id="ra_id" disabled />
          </div>
          <div class="field">
            <label>Fonte (alerta)</label>
            <input id="ra_alert" disabled />
          </div>
        </div>

        <div class="field">
          <label>Descrição do risco</label>
          <input id="ra_desc" placeholder="ex.: Possibilidade de acesso indevido por brute force (SSH)" />
        </div>

        <div class="two">
          <div class="field">
            <label>Ativo</label>
            <input id="ra_asset" disabled />
          </div>
          <div class="field">
            <label>Responsável do risco</label>
            <input id="ra_owner" placeholder="ex.: João — IT Ops" />
          </div>
        </div>

        <div class="two">
          <div class="field">
            <label>Ameaça</label>
            <input id="ra_threat" placeholder="ex.: Ataque de força bruta" />
          </div>
          <div class="field">
            <label>Vulnerabilidade</label>
            <input id="ra_vuln" placeholder="ex.: Política de passwords deficiente / sem MFA" />
          </div>
        </div>

        <div class="row" style="gap:14px; margin-top:6px">
          <label style="margin:0">Impactos (CIA)</label>
          <label class="toggle"><input id="ra_c" type="checkbox" checked> <span>Confidencialidade</span></label>
          <label class="toggle"><input id="ra_i" type="checkbox" checked> <span>Integridade</span></label>
          <label class="toggle"><input id="ra_a" type="checkbox"> <span>Disponibilidade</span></label>
        </div>

        <div style="height:10px"></div>

        <div class="two">
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

        <div class="kpirow" style="margin-top:10px">
          <span class="chip">Score: <b id="ra_score">9</b></span>
          <span class="chip" id="ra_level_chip">Nível: <b id="ra_level">Médio</b></span>
        </div>

        <div style="height:10px"></div>

        <div class="two">
          <div class="field">
            <label>Estratégia</label>
            <select id="ra_strategy">
              <option>Mitigar/Tratar</option>
              <option>Aceitar</option>
              <option>Evitar</option>
              <option>Transferir</option>
            </select>
          </div>
          <div class="field">
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
        </div>

        <div class="field">
          <label>Ações (resumo)</label>
          <textarea id="ra_actions" placeholder="ex.: Bloquear IPs, ativar MFA, rate-limit, auditar contas..."></textarea>
        </div>

        <div class="two">
          <div class="field">
            <label>Responsável pelo tratamento</label>
            <input id="ra_action_owner" placeholder="ex.: Inês — Sistemas de Informação" />
          </div>
          <div class="field">
            <label>Data alvo</label>
            <input id="ra_due" placeholder="YYYY-MM-DD" />
          </div>
        </div>

        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:10px">
          <button id="ra_save" class="btn ok" type="button">Guardar risco (mock)</button>
        </div>

        <p class="hint">Este registo fica na tabela “Riscos registados no sistema”.</p>
      </div>

      {{-- DIR: contexto do alerta --}}
      <div class="panel">
        <h2>Contexto do alerta</h2>
        <div id="ra_alertPreview" class="chunk-preview">—</div>
        <div class="hint" style="margin-top:8px">
          Depois podemos anexar evidências/documentos e ligar ao Tratamento.
        </div>
      </div>
    </div>
  </div>
</div>
</div>
@endsection

@push('scripts')
  @vite(['resources/js/pages/risks.js'])
@endpush
