@extends('layouts.app')

@section('title', 'Dashboard • Techbase GRC')

@section('content')
    <div class="grid cards">

        {{-- ALERTAS RECENTES --}}
        <div class="card dash-card" role="button" tabindex="0" data-open-alerts>
            <h3>Alertas recentes (Wazuh SIEM)</h3>
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
                <p class="big" id="friskCount" style="margin:0">0</p>
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

        {{-- PLANOS DE TRATAMENTO --}}
        <a class="card dash-card link-card" href="{{ route('treatment') }}" style="text-decoration:none">
            <h3>Planos de tratamento</h3>
            <div style="display:flex;align-items:baseline;gap:8px;margin:6px 0 2px">
                <p class="big" id="treatmentOverdueCount" style="margin:0;color:#f87171">0</p>
                <span class="muted" style="font-size:13px" id="treatmentOverdueLabel">em atraso</span>
            </div>

            {{-- barra de progresso --}}
            <div style="display:flex;height:6px;border-radius:99px;overflow:hidden;gap:2px;margin:10px 0 8px">
                <div id="treatBarDone"    style="background:#34d399;border-radius:99px;transition:width .4s;width:0%"></div>
                <div id="treatBarDoing"   style="background:#60a5fa;border-radius:99px;transition:width .4s;width:0%"></div>
                <div id="treatBarTodo"    style="background:#94a3b8;border-radius:99px;transition:width .4s;width:0%"></div>
                <div id="treatBarOverdue" style="background:#f87171;border-radius:99px;transition:width .4s;width:0%"></div>
            </div>

            <div style="display:flex;gap:12px;font-size:12px">
                <span style="display:flex;align-items:center;gap:4px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#34d399;display:inline-block"></span>
                    <span class="muted">Feito</span> <b id="treatCountDone">0</b>
                </span>
                <span style="display:flex;align-items:center;gap:4px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#60a5fa;display:inline-block"></span>
                    <span class="muted">Curso</span> <b id="treatCountDoing">0</b>
                </span>
                <span style="display:flex;align-items:center;gap:4px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#f87171;display:inline-block"></span>
                    <span class="muted">Atraso</span> <b id="treatCountOverdue">0</b>
                </span>
            </div>

            <div class="hint" style="margin-top:10px">Clique para gerir planos de tratamento.</div>
        </a>

        {{-- COMPLIANCE NIS2 --}}
        <a class="card dash-card link-card" href="{{ route('compliance') }}?framework=NIS2" style="text-decoration:none">
            <h3>Conformidade NIS2</h3>
            <div style="display:flex;align-items:baseline;gap:8px;margin:6px 0 2px">
                <p class="big" id="nis2Pct" style="margin:0">—</p>
                <span class="muted" style="font-size:13px">ponderado</span>
            </div>
        
            {{-- barra de progresso --}}
            <div style="height:6px;border-radius:99px;overflow:hidden;background:rgba(255,255,255,.08);margin:10px 0 8px">
                <div id="nis2Bar" style="height:100%;border-radius:99px;transition:width .6s ease,background .4s;width:0%"></div>
            </div>
        
            <div style="display:flex;gap:14px;font-size:12px">
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#34d399;display:inline-block"></span>
                    <span class="muted">Conforme</span> <b id="nis2Compliant">—</b>
                </span>
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#fbbf24;display:inline-block"></span>
                    <span class="muted">Parcial</span> <b id="nis2Partial">—</b>
                </span>
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#f87171;display:inline-block"></span>
                    <span class="muted">Não conf.</span> <b id="nis2NonCompliant">—</b>
                </span>
            </div>
        
            <div class="hint" style="margin-top:10px">
                <span id="nis2Total">—</span> controlos · Clique para detalhar por artigo.
            </div>
        </a>
        
        {{-- COMPLIANCE QNRCS --}}
        <a class="card dash-card link-card" href="{{ route('compliance') }}?framework=QNRCS" style="text-decoration:none">
            <h3>Conformidade QNRCS</h3>
            <div style="display:flex;align-items:baseline;gap:8px;margin:6px 0 2px">
                <p class="big" id="qnrcsPct" style="margin:0">—</p>
                <span class="muted" style="font-size:13px">ponderado</span>
            </div>
        
            {{-- barra de progresso --}}
            <div style="height:6px;border-radius:99px;overflow:hidden;background:rgba(255,255,255,.08);margin:10px 0 8px">
                <div id="qnrcsBar" style="height:100%;border-radius:99px;transition:width .6s ease,background .4s;width:0%"></div>
            </div>
        
            <div style="display:flex;gap:14px;font-size:12px">
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#34d399;display:inline-block"></span>
                    <span class="muted">Conforme</span> <b id="qnrcsCompliant">—</b>
                </span>
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#fbbf24;display:inline-block"></span>
                    <span class="muted">Parcial</span> <b id="qnrcsPartial">—</b>
                </span>
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:7px;height:7px;border-radius:50%;background:#f87171;display:inline-block"></span>
                    <span class="muted">Não conf.</span> <b id="qnrcsNonCompliant">—</b>
                </span>
            </div>
        
            <div class="hint" style="margin-top:10px">
                <span id="qnrcsTotal">—</span> controlos · Clique para detalhar por grupo.
            </div>
        </a>

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

        {{-- Top lacunas por domínio — preenchido pelo JS --}}
        <div class="card">
            <h3>Top lacunas por grupo/domínio</h3>
            <p class="muted" style="font-size:12px;margin:2px 0 12px">Grupos com mais controlos não conformes ou parciais.</p>
            <div id="gapGroupsBody">
                <div class="muted" style="font-size:13px;padding:8px 0">A carregar...</div>
            </div>
        </div>

        {{-- Recomendações dinâmicas — preenchido pelo JS --}}
        <div class="card">
            <h3>Prioridades de acção</h3>
            <p class="muted" style="font-size:12px;margin:2px 0 12px">Controlos GAP com maior impacto para resolver primeiro.</p>
            <div id="topGapControlsBody">
                <div class="muted" style="font-size:13px;padding:8px 0">A carregar...</div>
            </div>
        </div>

    </div>



    

{{-- MODAL: ALERTAS RECENTES --}}
<div id="alertsModal" class="modal-overlay is-hidden">
    <div class="modal-card am-alerts-modal">

        {{-- ── TOPO: Logo + Título + Fechar ── --}}
        <div class="am-modal-topbar">
            <div class="am-modal-brand">
                <div class="am-brand-dot"></div>
                <span class="am-brand-label">SIEM WAZUH · AO VIVO</span>
            </div>
            <button id="alertsModalClose" class="am-close-btn" aria-label="Fechar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        {{-- ── HEADER COM TÍTULO ── --}}
        <div class="am-modal-header">
            <div>
                <h2 class="am-modal-title">Alertas de Cibersegurança</h2>
                <p class="am-modal-subtitle">Eventos em tempo real detetados pelo SIEM Wazuh</p>
            </div>
        </div>

        {{-- ── KPI STRIP ── --}}
        <div class="am-kpi-strip">
            <button class="am-kpi-card" data-sev-filter="all" aria-pressed="true">
                <span class="am-kpi-num" id="akpiTotal">—</span>
                <span class="am-kpi-lbl">Total</span>
            </button>
            <button class="am-kpi-card am-kpi--critical" data-sev-filter="critical" aria-pressed="false">
                <span class="am-kpi-num" id="akpiCritical">—</span>
                <span class="am-kpi-lbl">Críticos</span>
            </button>
            <button class="am-kpi-card am-kpi--medium" data-sev-filter="medium" aria-pressed="false">
                <span class="am-kpi-num" id="akpiMedium">—</span>
                <span class="am-kpi-lbl">Médios</span>
            </button>
            <button class="am-kpi-card am-kpi--low" data-sev-filter="low" aria-pressed="false">
                <span class="am-kpi-num" id="akpiLow">—</span>
                <span class="am-kpi-lbl">Baixos</span>
            </button>
        </div>

        {{-- ── FILTROS ── --}}
        <div class="am-filters-bar">
            <div class="am-filter-field am-filter-flex">
                <label class="am-filter-label">Pesquisar</label>
                <input id="alertSearch" class="am-filter-input" placeholder="ativo, tipo, mensagem…" />
            </div>
            <div class="am-filter-field">
                <label class="am-filter-label">Severidade</label>
                <select id="alertSeverity" class="am-filter-input">
                    <option value="all">Todas</option>
                    <option value="critical">Crítica</option>
                    <option value="medium">Média</option>
                    <option value="low">Baixa</option>
                </select>
            </div>
            <div class="am-filter-field">
                <label class="am-filter-label">De</label>
                <input id="alertDateFrom" type="date" class="am-filter-input" />
            </div>
            <div class="am-filter-field">
                <label class="am-filter-label">Até</label>
                <input id="alertDateTo" type="date" class="am-filter-input" />
            </div>
        </div>

        {{-- ── SEPARADOR COM CONTAGEM ── --}}
        <div class="am-results-bar">
            <span id="alertResultCount" class="am-results-count">— alertas</span>
            <div class="am-results-line"></div>
        </div>

        {{-- ── TABELA ── --}}
        <div class="am-table-wrap">
            <table class="am-table">
                <thead>
                    <tr>
                        <th style="width:140px">Data/Hora</th>
                        <th style="width:120px">Agente</th>
                        <th>Regra Disparada</th>
                        <th style="width:90px;text-align:center">Nível</th>
                        <th style="width:160px">Contexto</th>
                    </tr>
                </thead>
                <tbody id="alertsTableBody">
                    <tr>
                        <td colspan="5" class="am-loading-cell">
                            <span class="am-spinner"></span>
                            A carregar alertas do SIEM…
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ── RODAPÉ ── --}}
        <div class="am-modal-footer">
            Clique num alerta para expandir os detalhes e gerar um <strong>Plano de Ação com Inteligência Artificial</strong>.
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





    {{-- Rotas para o JS (sem hardcode) --}}
    <script>
        window.__TB_ROUTES__ = {
            assets: "{{ route('assets') }}",
            docs: "{{ route('docs') }}",
            risks: "{{ route('risks') }}",
            treatment: "{{ route('treatment') }}",
            compliance: "{{ route('compliance') }}",
        };
    </script>

    @push('styles')
    @vite(['resources/css/pages/dashboard.css'])
@endpush

    @vite(['resources/js/pages/dashboard.js'])
@endsection