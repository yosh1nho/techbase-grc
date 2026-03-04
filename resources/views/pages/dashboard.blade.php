@extends('layouts.app')

@section('title', 'Dashboard • Techbase GRC')

@section('content')
    <div class="grid cards">

        {{-- ALERTAS RECENTES --}}
        <div class="card dash-card" role="button" tabindex="0" data-open-alerts>
            <h3>Alertas recentes (Wazuh)</h3>
            <p class="big">9</p>
            <p class="sub">Últimas 24h (correlacionados)</p>
            <div class="kpirow">
                <span class="chip">Sync: OK</span>
                <span class="chip warn">2 críticos</span>
            </div>
            <div class="hint">Clique para ver detalhes dos alertas.</div>
        </div>

        {{-- RISCOS --}}
        <a class="card dash-card link-card" href="{{ route('risks') }}" style="text-decoration:none">
            <h3>Riscos (ativos críticos)</h3>
            <p class="big">14</p>
            <p class="sub">3 altos • 8 médios • 3 baixos</p>
            <div class="kpirow">
                <span class="chip bad">Alto</span>
                <span class="chip warn">Médio</span>
                <span class="chip ok">Baixo</span>
            </div>
            <div class="hint">Clique para abrir a aba de Riscos.</div>
        </a>

        {{-- PLANOS EM ATRASO --}}
        <a class="card dash-card link-card" href="{{ route('treatment') }}" style="text-decoration:none">
            <h3>Planos em atraso</h3>
            <p class="big">2</p>
            <p class="sub">Prazos ultrapassados</p>
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
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="alertsModalTitle">
            <div class="modal-header">
                <div>
                    <div class="muted" style="margin-bottom:4px">Alertas recentes</div>
                    <div id="alertsModalTitle" style="font-size:18px;font-weight:800">Wazuh (mock)</div>
                </div>
                <div style="display:flex; gap:10px; align-items:center">
                    <button id="alertsModalClose" class="btn" type="button">Fechar</button>
                </div>
            </div>

            <div style="height:10px"></div>

            <div class="row">
                <div class="field" style="flex:1; min-width:280px">
                    <label>Pesquisar</label>
                    <input id="alertSearch" placeholder="ex.: malware, SRV-DB-01, critical..." />
                </div>
                <div class="field" style="min-width:220px">
                    <label>Severidade</label>
                    <select id="alertSeverity">
                        <option value="all">Todas</option>
                        <option value="critical">Crítica</option>
                        <option value="high">Alta</option>
                        <option value="medium">Média</option>
                        <option value="low">Baixa</option>
                    </select>
                </div>
                <div class="field" style="min-width:180px">
                    <label>De</label>
                    <input id="alertDateFrom" type="date" />
                </div>
                <div class="field" style="min-width:180px">
                    <label>Até</label>
                    <input id="alertDateTo" type="date" />
                </div>
            </div>

            <div style="height:10px"></div>

            <div class="panel">
                <h2 style="margin-bottom:6px">Lista</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Severidade</th>
                            <th>Ativo</th>
                            <th>Categoria</th>
                            <th>Resumo</th>
                        </tr>
                    </thead>
                    <tbody id="alertTbody">
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
                    Mock: depois ligamos isto à integração (RF17) e abrimos “detalhe do alerta/incidente”.
                </div>
            </div>
        </div>
    </div>

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