@extends('layouts.app')

@section('title', 'Dashboard • Techbase GRC')

@section('content')
    <div class="grid cards">

        {{-- ALERTAS RECENTES --}}
        <div class="card dash-card" role="button" tabindex="0" data-open-alerts>
            <h3>Alertas recentes (Acronis)</h3>
            <p class="big" id="alertCount">—</p>
            <p class="sub" id="alertSub">A carregar...</p>

            {{-- mini barra críticos/outros --}}
            <div id="alertMiniBar" style="display:flex;height:4px;border-radius:99px;overflow:hidden;gap:2px;margin:10px 0 8px;opacity:.8">
                <div id="alertBarCrit" style="background:#f87171;border-radius:99px;transition:width .4s;width:0%"></div>
                <div id="alertBarOther" style="background:var(--muted,#94a3b8);border-radius:99px;transition:width .4s;width:100%"></div>
            </div>

            <div style="display:flex;gap:14px;font-size:12px;margin-bottom:10px">
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#f87171;display:inline-block"></span>
                    <span class="muted">Críticos</span> <b id="alertCritCount">—</b>
                </span>
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#fbbf24;display:inline-block"></span>
                    <span class="muted">Médios</span> <b id="alertMedCount">—</b>
                </span>
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#34d399;display:inline-block"></span>
                    <span class="muted">Baixos</span> <b id="alertLowCount">—</b>
                </span>
            </div>

            <div class="hint">Clique para ver detalhes e filtrar alertas.</div>
        </div>

        {{-- RISCOS --}}
        <a class="card dash-card link-card" href="{{ route('risks') }}" style="text-decoration:none">
            <h3>Riscos identificados</h3>
            <div style="display:flex;align-items:baseline;gap:10px;margin:6px 0 2px">
                <p class="big" id="riskCount" style="margin:0">0</p>
                <span class="muted" style="font-size:13px">total</span>
            </div>

            {{-- barra de severidade proporcional --}}
            <div id="riskBar" style="display:flex;height:6px;border-radius:99px;overflow:hidden;gap:2px;margin:10px 0 8px">
                <div id="riskBarHigh" style="background:#f87171;border-radius:99px;transition:width .4s"></div>
                <div id="riskBarMed"  style="background:#fbbf24;border-radius:99px;transition:width .4s"></div>
                <div id="riskBarLow"  style="background:#34d399;border-radius:99px;transition:width .4s"></div>
            </div>

            {{-- legenda da barra --}}
            <div style="display:flex;gap:14px;font-size:12px">
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:8px;height:8px;border-radius:50%;background:#f87171;display:inline-block"></span>
                    <span class="muted">Alto</span> <b id="riskHighCount">0</b>
                </span>
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:8px;height:8px;border-radius:50%;background:#fbbf24;display:inline-block"></span>
                    <span class="muted">Médio</span> <b id="riskMedCount">0</b>
                </span>
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:8px;height:8px;border-radius:50%;background:#34d399;display:inline-block"></span>
                    <span class="muted">Baixo</span> <b id="riskLowCount">0</b>
                </span>
            </div>

            {{-- top risco em destaque --}}
            <div id="riskTopItem" style="display:none;margin-top:10px;padding:8px 10px;border-radius:10px;background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.2)">
                <div style="font-size:10px;font-weight:700;letter-spacing:.06em;color:#f87171;margin-bottom:3px">RISCO MAIS CRÍTICO</div>
                <div id="riskTopTitle" style="font-size:13px;font-weight:600"></div>
                <div id="riskTopMeta"  style="font-size:11px;color:var(--muted);margin-top:2px"></div>
            </div>

            <div class="hint" style="margin-top:10px">Clique para ver todos os riscos.</div>
        </a>

        {{-- PLANOS EM ATRASO --}}
        <a class="card dash-card link-card" href="{{ route('treatment') }}" style="text-decoration:none">
            <h3>Planos em atraso</h3>
            <p class="big" id="treatmentOverdueCount">0</p>
            <p class="sub" id="treatmentOverdueLabel">—</p>
            <div class="kpirow">
                <span class="chip warn">Ações pendentes</span>
            </div>
            <div class="hint">Clique para abrir Tratamento de Risco.</div>
        </a>

        {{-- MATURIDADE --}}
        <div class="card dash-card" role="button" tabindex="0" data-open-maturity>
            <h3>Maturidade (QNRCS)</h3>
            <p class="big">62%</p>
            <p class="sub">Evolução: +6% vs. última avaliação</p>
            <div class="kpirow">
                <span class="chip ok">Covered: 41</span>
                <span class="chip warn">Partial: 18</span>
                <span class="chip bad">GAP: 12</span>
            </div>
            <div class="hint">Clique para detalhar por Ativo / Política.</div>
        </div>

    </div>

    {{-- PRÓXIMAS AÇÕES (ponto 5: dinâmico via JS) --}}
    <div class="section-title" style="margin-top:24px">Próximas ações</div>
    <div class="card" style="padding:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;flex-wrap:wrap">
            <div>
                <h3 style="margin:0">O que fazer agora</h3>
                <p class="muted" style="margin:2px 0 0;font-size:12px">Gerado automaticamente cruzando alertas, riscos e estado dos controlos.</p>
            </div>
        </div>
        <div id="nextActionsContainer" style="display:flex;flex-direction:column;gap:8px">
            <div class="muted" style="font-size:13px">A calcular ações...</div>
        </div>
    </div>

    <div class="section-title">Visão rápida</div>
    <div class="split">
        <div class="card">
            <h3>Top lacunas por domínio</h3>
            <table>
                <thead>
                    <tr>
                        <th>Domínio</th>
                        <th>Lacunas</th>
                        <th>Impacto</th>
                        <th>Ação sugerida</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gestão de Ativos</td>
                        <td><span class="tag bad"><span class="s"></span> 4 GAP</span></td>
                        <td class="muted">Inventário incompleto</td>
                        <td class="muted">Criar procedimento + evidência</td>
                    </tr>
                    <tr>
                        <td>Backups & Continuidade</td>
                        <td><span class="tag warn"><span class="s"></span> 3 PARTIAL</span></td>
                        <td class="muted">RPO/RTO não formalizados</td>
                        <td class="muted">Atualizar política + testes</td>
                    </tr>
                    <tr>
                        <td>Resposta a Incidentes</td>
                        <td><span class="tag warn"><span class="s"></span> 2 PARTIAL</span></td>
                        <td class="muted">Playbooks sem treino</td>
                        <td class="muted">Registar simulação</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Recomendações automáticas (RF8)</h3>

            <div class="panel">
                <b>Prioridade 1</b>
                <p class="muted">Completar inventário e associar responsáveis por ativo crítico.</p>
                <div class="kpirow">
                    <span class="chip">Controlos: ID.GA-1</span>
                    <span class="chip warn">Evidência: Procedimento + Export</span>
                </div>
            </div>

            <div style="height:10px"></div>

            <div class="panel">
                <b>Prioridade 2</b>
                <p class="muted">Formalizar testes de backup e anexar relatórios por período.</p>
                <div class="kpirow">
                    <span class="chip">Controlos: PR.IP-4</span>
                    <span class="chip ok">Acronis: logs disponíveis</span>
                </div>
            </div>
        </div>
    </div>



    

    {{-- MODAL: ALERTAS RECENTES --}}
    <div id="alertsModal" class="modal-overlay is-hidden" aria-hidden="true">
        <div class="modal-card am-alerts-modal" role="dialog" aria-modal="true" aria-labelledby="alertsModalTitle">

            {{-- Header --}}
            <div class="am-alerts-header">
                <div>
                    <div class="am-alerts-eyebrow">Integração Acronis · Sincronizado agora</div>
                    <div id="alertsModalTitle" class="am-alerts-title">Alertas recentes</div>
                </div>
                <button id="alertsModalClose" class="btn" type="button">✕</button>
            </div>

            {{-- KPI strip — clicável para filtrar --}}
            <div class="am-alerts-kpis">
                <button class="am-akpi am-akpi-btn" data-sev-filter="all" aria-pressed="true">
                    <div class="am-akpi-num" id="akpiTotal">—</div>
                    <div class="am-akpi-label">Total</div>
                </button>
                <button class="am-akpi am-akpi-btn am-akpi-critical" data-sev-filter="critical" aria-pressed="false">
                    <div class="am-akpi-num" id="akpiCritical">—</div>
                    <div class="am-akpi-label">Críticos</div>
                </button>
                <button class="am-akpi am-akpi-btn am-akpi-medium" data-sev-filter="medium" aria-pressed="false">
                    <div class="am-akpi-num" id="akpiMedium">—</div>
                    <div class="am-akpi-label">Médios</div>
                </button>
                <button class="am-akpi am-akpi-btn am-akpi-low" data-sev-filter="low" aria-pressed="false">
                    <div class="am-akpi-num" id="akpiLow">—</div>
                    <div class="am-akpi-label">Baixos</div>
                </button>
            </div>

            {{-- Filtros --}}
            <div class="am-alerts-filters">
                <div class="field" style="flex:1;min-width:200px;margin:0">
                    <label>Pesquisar</label>
                    <input id="alertSearch" placeholder="ativo, tipo, mensagem..." />
                </div>
                <div class="field" style="min-width:150px;margin:0">
                    <label>Severidade</label>
                    <select id="alertSeverity">
                        <option value="all">Todas</option>
                        <option value="critical">Crítica</option>
                        <option value="high">Alta</option>
                        <option value="medium">Média</option>
                        <option value="low">Baixa</option>
                    </select>
                </div>
                <div class="field" style="min-width:140px;margin:0">
                    <label>De</label>
                    <input id="alertDateFrom" type="date" />
                </div>
                <div class="field" style="min-width:140px;margin:0">
                    <label>Até</label>
                    <input id="alertDateTo" type="date" />
                </div>
            </div>

            {{-- Separador com contagem --}}
            <div class="am-alerts-sep">
                <span id="alertResultCount" class="muted" style="font-size:12px">— alertas</span>
            </div>

            {{-- Lista --}}
            <div id="alertTbody" class="am-alerts-list"></div>

            <div class="hint" style="margin-top:12px;padding-top:10px;border-top:1px solid var(--modal-border)">
                Clique num alerta para ir direto ao módulo de Riscos e registar ocorrência (RF17).
            </div>
        </div>
    </div>

    <style>
        /* ── Alerts Modal ── */
        .am-alerts-modal { padding: 0; display: flex; flex-direction: column; }
        .am-alerts-header {
            display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
            padding: 20px 20px 16px; border-bottom: 1px solid var(--modal-border);
        }
        .am-alerts-eyebrow {
            font-size: 11px; font-weight: 600; letter-spacing: .07em;
            text-transform: uppercase; color: var(--muted); margin-bottom: 4px;
        }
        .am-alerts-title { font-size: 20px; font-weight: 800; }

        .am-alerts-kpis {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 0; border-bottom: 1px solid var(--modal-border);
        }
        .am-akpi {
            padding: 14px 16px; text-align: center;
            border-right: 1px solid var(--modal-border);
        }
        .am-akpi:last-child { border-right: none; }
        .am-akpi-num { font-size: 26px; font-weight: 900; line-height: 1; }
        .am-akpi-label { font-size: 11px; color: var(--muted); margin-top: 3px; }
        .am-akpi-critical { background: rgba(248,113,113,.05); }
        .am-akpi-critical .am-akpi-num { color: #f87171; }
        .am-akpi-medium   { background: rgba(251,191,36,.04); }
        .am-akpi-medium   .am-akpi-num { color: #fbbf24; }
        .am-akpi-low      { background: rgba(52,211,153,.04); }
        .am-akpi-low      .am-akpi-num { color: #34d399; }

        /* KPI como filtro clicável */
        .am-akpi-btn {
            all: unset; cursor: pointer;
            transition: background .15s, opacity .15s, box-shadow .15s;
            position: relative;
        }
        .am-akpi-btn[aria-pressed="false"] { opacity: .45; }
        .am-akpi-btn[aria-pressed="false"]:hover { opacity: .7; }
        .am-akpi-btn[aria-pressed="true"] { opacity: 1; }

        /* Total activo */
        .am-akpi-btn:not(.am-akpi-critical):not(.am-akpi-medium):not(.am-akpi-low)[aria-pressed="true"] {
            background: rgba(100,116,139,.1);
            box-shadow: inset 0 0 0 1.5px rgba(100,116,139,.35);
        }
        /* Críticos activo */
        .am-akpi-btn.am-akpi-critical[aria-pressed="true"] {
            background: rgba(248,113,113,.14) !important;
            box-shadow: inset 0 0 0 1.5px rgba(248,113,113,.45);
        }
        /* Médios activo */
        .am-akpi-btn.am-akpi-medium[aria-pressed="true"] {
            background: rgba(251,191,36,.12) !important;
            box-shadow: inset 0 0 0 1.5px rgba(251,191,36,.4);
        }
        /* Baixos activo */
        .am-akpi-btn.am-akpi-low[aria-pressed="true"] {
            background: rgba(52,211,153,.1) !important;
            box-shadow: inset 0 0 0 1.5px rgba(52,211,153,.35);
        }

        .am-alerts-filters {
            display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;
            padding: 14px 20px; border-bottom: 1px solid var(--modal-border);
        }
        .am-alerts-sep {
            padding: 8px 20px 4px;
            display: flex; align-items: center; gap: 10px;
        }
        .am-alerts-sep::after {
            content: ''; flex: 1; height: 1px; background: var(--modal-border);
        }

        .am-alerts-list {
            display: flex; flex-direction: column; gap: 0;
            max-height: 400px; overflow-y: auto;
            padding: 0 20px 4px;
        }
        .am-alert-card {
            display: flex; align-items: flex-start; gap: 14px;
            padding: 13px 0; border-bottom: 1px solid var(--modal-border);
            cursor: pointer; transition: background .12s;
            border-radius: 0;
        }
        .am-alert-card:last-child { border-bottom: none; }
        .am-alert-card:hover { background: rgba(255,255,255,.03); margin: 0 -20px; padding-left: 20px; padding-right: 20px; }
        .am-alert-icon {
            width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .am-alert-icon svg { display: block; }
        .am-alert-body { flex: 1; min-width: 0; }
        .am-alert-top { display: flex; align-items: center; gap: 8px; margin-bottom: 3px; flex-wrap: wrap; }
        .am-alert-sev {
            font-size: 10px; font-weight: 800; letter-spacing: .07em;
            padding: 2px 7px; border-radius: 999px; text-transform: uppercase;
        }
        .am-alert-cat  { font-size: 12px; font-weight: 600; }
        .am-alert-ts   { font-size: 11px; color: var(--muted); margin-left: auto; }
        .am-alert-asset { font-size: 13px; font-weight: 700; margin-bottom: 2px; }
        .am-alert-msg   { font-size: 12px; color: var(--muted); line-height: 1.45; }
        .am-alert-arrow { display:flex; align-items:center; color: var(--muted); flex-shrink: 0; align-self: center; opacity: .6; }
    </style>


{{-- MODAL 1: MATURIDADE (lista de Ativos/Políticas) --}}
    <div id="maturityModal" class="modal-overlay is-hidden" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="maturityModalTitle">
            <div class="modal-header">
                <div>
                    <div class="muted" style="margin-bottom:4px">Detalhe de maturidade</div>
                    <div id="maturityModalTitle" style="font-size:18px;font-weight:800">Maturidade por Ativo / Política
                    </div>
                </div>
                <div style="display:flex; gap:10px; align-items:center">
                    <button id="maturityModalClose" class="btn" type="button">Fechar</button>
                </div>
            </div>

            <div style="height:10px"></div>

            <div class="row">
                <div class="field" style="flex:1">
                    <label>Filtro</label>
                    <input id="matSearch" placeholder="Procurar por nome..." />
                </div>
                <div class="field" style="min-width:220px">
                    <label>Tipo</label>
                    <select id="matType">
                        <option value="all">Todos</option>
                        <option value="asset">Ativos</option>
                        <option value="policy">Políticas</option>
                    </select>
                </div>
                <div class="field" style="min-width:220px">
                    <label>Status</label>
                    <select id="matStatus">
                        <option value="all">Todos</option>
                        <option value="GAP">GAP</option>
                        <option value="PARTIAL">PARTIAL</option>
                        <option value="COVERED">COVERED</option>
                    </select>
                </div>
            </div>

            <div style="height:10px"></div>

            <div class="panel">
                <h2 style="margin-bottom:6px">Itens (mock)</h2>
                <p class="muted" style="margin-top:0">
                    Clique num item para ver os controlos e navegar para a página correta.
                </p>

                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Resumo</th>
                            <th style="width:220px">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="matTbody">
                        <tr>
                            <td class="muted">—</td>
                            <td class="muted">—</td>
                            <td class="muted">—</td>
                            <td class="muted">—</td>
                            <td class="muted">—</td>
                        </tr>
                    </tbody>
                </table>

                <div class="hint" style="margin-top:10px">
                    Mock: “Ativo” → vai para {{ route('assets') }} | “Política” → vai para {{ route('docs') }}
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 2: CONTROL LIST (por Ativo ou Política) --}}
    <div id="controlsModal" class="modal-overlay is-hidden" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="controlsModalTitle">
            <div class="modal-header">
                <div>
                    <div class="muted" style="margin-bottom:4px">Controlos do item</div>
                    <div id="controlsModalTitle" style="font-size:18px;font-weight:800">—</div>
                </div>
                <div style="display:flex; gap:10px; align-items:center">
                    <button id="controlsModalGo" class="btn primary" type="button">Ir para página</button>
                    <button id="controlsModalClose" class="btn" type="button">Fechar</button>
                </div>
            </div>

            <div style="height:10px"></div>

            <div class="panel">
                <div class="kpirow">
                    <span class="chip">Tipo: <b id="cmType">—</b></span>
                    <span class="chip">Status geral: <b id="cmStatus">—</b></span>
                    <span class="chip warn">Clique num controlo para “ver detalhe” (mock)</span>
                </div>

                <div style="height:10px"></div>

                <table>
                    <thead>
                        <tr>
                            <th>Controlo</th>
                            <th>Descrição (curta)</th>
                            <th>Status</th>
                            <th>Próxima ação</th>
                        </tr>
                    </thead>
                    <tbody id="cmTbody">
                        <tr>
                            <td class="muted">—</td>
                            <td class="muted">—</td>
                            <td class="muted">—</td>
                            <td class="muted">—</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Estilos locais mínimos (podes mover pro CSS global depois) --}}
    <style>
        .dash-card {
            cursor: pointer;
        }

        .link-card {
            cursor: pointer;
            display: block;
            color: inherit;
        }

        .modal-overlay.is-hidden {
            display: none;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: var(--modal-overlay);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 99999;
        }

        .modal-card {
            width: min(1200px, 96vw);
            max-height: 90vh;
            overflow: auto;
            border: 1px solid var(--modal-border);
            border-radius: 16px;
            background: var(--modal-bg);
            color: var(--text);
            box-shadow: 0 30px 60px rgba(0, 0, 0, .55);
            padding: 14px;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
        }

        .btn.small {
            padding: 8px 10px;
            font-size: 12px;
            border-radius: 10px;
        }

        tr[data-row-click] {
            cursor: pointer;
        }

        tr[data-row-click]:hover {
            background: rgba(255, 255, 255, .03);
        }
    </style>

    {{-- Rotas para o JS (sem hardcode) --}}
    <script>
        window.__TB_ROUTES__ = {
            assets: "{{ route('assets') }}",
            docs: "{{ route('docs') }}",
            risks: "{{ route('risks') }}",
            treatment: "{{ route('treatment') }}",
        };
    </script>

    @vite(['resources/js/pages/dashboard.js'])
@endsection