@extends('layouts.app')
@section('title', 'Relatório Anual CNCS • Techbase GRC')

@section('content')
    <div class="card">
        <h3>Relatório Anual CNCS (RF20)</h3>
        <p class="muted">
            Gerar automaticamente o relatório anual exigido pelo CNCS, agregando dados do sistema e exportando em PDF.
        </p>

        <div class="two" style="margin-top:10px">
            {{-- LEFT: parâmetros --}}
            <div class="panel">
                <h2>Parâmetros</h2>

                <div class="row">
                    <div class="field">
                        <label>Entidade</label>
                        <input id="cncsEntity" value="Clínica Exemplo" />
                    </div>

                    <div class="field">
                        <label>Ano civil</label>
                        <select id="cncsYear">
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="field">
                        <label>Período</label>
                        <input id="cncsPeriod" value="01/01 - 31/12" />
                        <div class="hint">Default: ano civil. Pode ajustar se necessário.</div>
                    </div>

                    <div class="field">
                        <label>Escopo de incidentes</label>
                        <select id="cncsIncidentScope">
                            <option value="relevant">Apenas relevante/substancial</option>
                            <option value="all">Todos (inclui alertas convertidos em incidente)</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="field">
                        <label>Formato de saída</label>
                        <select id="cncsFormat">
                            <option value="pdf">PDF</option>
                            <option value="odt">ODT (opcional)</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Guardar como Documento no sistema (RF2)</label>
                        <select id="cncsSaveAsDoc">
                            <option value="yes">Sim</option>
                            <option value="no">Não</option>
                        </select>
                    </div>
                </div>

                <div style="height:10px"></div>

                <h2>Secções com edição manual</h2>
                <p class="muted">
                    Estas secções são auto-preenchidas, mas podes editar antes de exportar.
                </p>

                <div class="field">
                    <label>3 — Atividades de segurança (texto final)</label>
                    <textarea id="cncsManualActivities" placeholder="Será preenchido no preview..."></textarea>
                </div>

                <div class="field">
                    <label>6 — Recomendações de melhoria (texto final)</label>
                    <textarea id="cncsManualRecs" placeholder="Será preenchido no preview..."></textarea>
                </div>

                <div class="field">
                    <label>8 — Outra informação relevante</label>
                    <textarea id="cncsExtra"
                        placeholder="Ex.: auditoria externa, reestruturação, mudanças em fornecedores..."></textarea>
                </div>

                <div class="row">
                    <button id="btnPreviewCNCS" class="btn">Pré-visualizar</button>
                    <button id="btnExportCNCS" class="btn primary">Exportar</button>
                </div>

                <div class="hint">
                    Ao exportar: (mock) cria documento “Relatório CNCS” e regista auditoria (RNF5).
                </div>
            </div>

            {{-- RIGHT: preview --}}
            <div class="panel">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap">
                    <div>
                        <h2 style="margin-bottom:4px">Pré-visualização</h2>
                        <div class="muted" id="pvSubtitle">—</div>
                    </div>
                    <div class="kpirow">
                        <span class="chip">Incidentes: <b id="pvIncTotal">—</b></span>
                        <span class="chip warn">Relev./Subst.: <b id="pvIncRelevant">—</b></span>
                        <span class="chip">Riscos altos: <b id="pvHighRisks">—</b></span>
                    </div>
                </div>

                <div style="height:12px"></div>

                <h2>4 — Estatística trimestral</h2>
                <p class="muted">Número de incidentes por trimestre e principais tipos (mock).</p>
                <table>
                    <thead>
                        <tr>
                            <th>Trimestre</th>
                            <th>Total</th>
                            <th>Tipos</th>
                        </tr>
                    </thead>
                    <tbody id="pvQuarterBody">
                        <tr>
                            <td class="muted">—</td>
                            <td class="muted">—</td>
                            <td class="muted">—</td>
                        </tr>
                    </tbody>
                </table>

                <div style="height:12px"></div>

                <h2>5 — Análise agregada (relevante/substancial)</h2>
                <p class="muted">
                    Campos essenciais para CNCS: utilizadores afetados, duração, distribuição geográfica e impacto
                    transfronteiriço.
                </p>

                <div class="two">
                    <div class="panel" style="background:rgba(0,0,0,.12)">
                        <div class="muted">Utilizadores afetados (agregado)</div>
                        <div style="font-size:26px; font-weight:900" id="pvUsersAffected">—</div>
                        <div class="hint" id="pvUsersAffectedHint">—</div>
                    </div>

                    <div class="panel" style="background:rgba(0,0,0,.12)">
                        <div class="muted">Duração (agregado)</div>
                        <div style="font-size:26px; font-weight:900" id="pvDuration">—</div>
                        <div class="hint" id="pvDurationHint">—</div>
                    </div>
                </div>

                <div style="height:10px"></div>

                <div class="two">
                    <div class="panel" style="background:rgba(0,0,0,.12)">
                        <div class="muted">Distribuição geográfica</div>
                        <div id="pvGeo" class="muted" style="margin-top:6px">—</div>
                    </div>

                    <div class="panel" style="background:rgba(0,0,0,.12)">
                        <div class="muted">Impacto transfronteiriço</div>
                        <div id="pvCrossBorder" style="margin-top:6px">—</div>
                    </div>
                </div>

                <div style="height:12px"></div>

                <h2>7 — Problemas identificados e medidas implementadas</h2>
                <p class="muted">Exemplos (mock) que depois virão de “Planos de tratamento” + “Evidências”.</p>

                <div id="pvMeasures" class="stack"></div>

                <div style="height:12px"></div>

                <h2>Texto final das secções (prévia)</h2>

                <div class="panel" style="background:rgba(0,0,0,.10)">
                    <div class="muted">3 — Atividades de segurança</div>
                    <div id="pvActivitiesText" class="muted" style="margin-top:6px">—</div>
                </div>

                <div style="height:10px"></div>

                <div class="panel" style="background:rgba(0,0,0,.10)">
                    <div class="muted">6 — Recomendações</div>
                    <div id="pvRecsText" class="muted" style="margin-top:6px">—</div>
                </div>

                <div style="height:10px"></div>

                <div class="panel" style="background:rgba(0,0,0,.10)">
                    <div class="muted">8 — Outra informação relevante</div>
                    <div id="pvExtraText" class="muted" style="margin-top:6px">—</div>
                </div>

                <div class="hint" style="margin-top:10px">
                    Nota: esta página é mock. Depois ligamos ao backend para buscar dados reais e gerar PDF.
                </div>
            </div>
        </div>
    </div>

    {{-- estilos locais só pra essa página (pode mover pro CSS global depois) --}}
    <style>
        .stack {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .measure-item {
            border: 1px solid rgba(255, 255, 255, .10);
            background: rgba(0, 0, 0, .14);
            border-radius: 14px;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .measure-left {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .measure-right {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(96, 165, 250, .12);
            border: 1px solid rgba(96, 165, 250, .22);
            font-weight: 800;
        }
    </style>

    {{-- pdfmake para exportar PDF --}}
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/pdfmake.min.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/vfs_fonts.min.js"></script>

    {{-- JS separado --}}
    @vite(['resources/js/pages/reports-cncs.js'])
@endsection