@extends('layouts.app')

@section('title', 'Gestão de Risco • Techbase GRC')

@section('content')
    <div class="card">
        <h3>Riscos (RF7, RF8, RF9, RF12)</h3>

        <div class="two" style="margin-top:10px">
            <div class="panel">
                <h2>Calculadora (Prob × Impacto)</h2>

                <div class="two">
                    <div class="field">
                        <label>Probabilidade</label>
                        <select id="prob">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="field">
                        <label>Impacto</label>
                        <select id="impact">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="kpirow">
                    <span class="chip">Score: <b id="scoreValue">1</b></span>
                    <span class="chip" id="classChip">Classe: Baixo</span>
                </div>

                <p class="hint">Regra: score = prob × impacto. Classificação conforme escala definida.</p>
            </div>

            <div class="panel">
                <h2>Aceitação formal de risco (RF12)</h2>
                <div class="field">
                    <label>Risco</label>
                    <select>
                        <option>R-003 — Falha de backup testado</option>
                    </select>
                </div>
                <div class="two">
                    <div class="field"><label>Aprovador</label><input placeholder="Nome / role" /></div>
                    <div class="field"><label>Data</label><input placeholder="YYYY-MM-DD" /></div>
                </div>
                <div class="field">
                    <label>Justificativa</label>
                    <textarea placeholder="Motivo para aceitar o risco e condições."></textarea>
                </div>
                <button class="btn warn" type="button">Registar aceitação</button>
            </div>
        </div>

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

      <a id="btnGoTreatment" class="btn" href="{{ route('treatment') }}" style="text-decoration:none; display:none;">
        Ver em Tratamento
      </a>
    </div>
  </div>
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

    </div>
@endsection

@push('scripts')
  @vite(['resources/js/pages/risks.js'])
@endpush
