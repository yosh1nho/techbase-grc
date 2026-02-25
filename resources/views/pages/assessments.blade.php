@extends('layouts.app')
@section('title', 'Avaliação • Techbase GRC')
@section('content')
    <section id="page-assessments" class="page hide">
        <div class="card">
            <h3>Avaliações de conformidade (RF5, RF6, RF9)</h3>
            <div class="panel" style="margin-top:10px">
                <h2>Criar avaliação</h2>
                <div class="two">
                    <div class="field">
                        <label>Escopo</label>
                        <select>
                            <option>Organização</option>
                            <option>Por ativo</option>
                        </select>
                    </div>
                    <div class="field">
                    <label>Ativo</label>

                    <div style="position:relative;">
                        <input id="assetSearch"
                            placeholder="Pesquisar ativo por nome, tipo, responsável..."
                            autocomplete="off" />

                        <div id="assetDropdown"
                            class="panel"
                            style="position:absolute; left:0; right:0; top:calc(100% + 6px);
                                    z-index:50; display:none; max-height:240px; overflow:auto; padding:6px;">
                        <!-- opções via JS -->
                        </div>
                    </div>

                    <input type="hidden" id="assetSelectedId" value="" />
                    </div>

                </div>
                <div class="two">
                    <div class="field"><label>Framework</label><select>
                            <option>QNRCS v2.1</option>
                        </select></div>
                    <div class="field">
                <label>Período</label>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <select id="periodYear" style="min-width:140px;">
                    <option value="2026" selected>2026</option>
                    <option value="2025">2025</option>
                    <option value="2024">2024</option>
                    </select>

                    <select id="periodQuarter" style="min-width:140px;">
                    <option value="Q1" selected>Q1</option>
                    <option value="Q2">Q2</option>
                    <option value="Q3">Q3</option>
                    <option value="Q4">Q4</option>
                    </select>
                </div>
                <div class="muted" style="margin-top:6px;">Ex.: Q1 2026</div>
                </div>

                </div>
                <button class="btn ok">Iniciar</button>
            </div>

            <div style="height:12px"></div>
            <div class="panel">
                <h2>Resultado por controlo</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Controlo</th>
                            <th>Status</th>
                            <th>Notas</th>
                            <th>Evidências</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><b>ID.GA-1</b>
                                <div class="muted">Inventário atualizado</div>
                            </td>
                            <td><span class="tag warn"><span class="s"></span> PARTIAL</span></td>
                            <td class="muted">Existe processo, falta prova periódica.</td>
                            <td class="muted">Procedimento v1.0</td>
                        </tr>
                        <tr>
                            <td><b>ID.AR-1</b>
                                <div class="muted">Análise de risco anual</div>
                            </td>
                            <td><span class="tag bad"><span class="s"></span> GAP</span></td>
                            <td class="muted">Sem registo formal.</td>
                            <td class="muted">—</td>
                        </tr>
                        <tr>
                            <td><b>PR.IP-4</b>
                                <div class="muted">Backups e testes</div>
                            </td>
                            <td><span class="tag ok"><span class="s"></span> COVERED</span></td>
                            <td class="muted">Relatórios mensais anexados.</td>
                            <td class="muted">Relatório Jan/2026</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @push('scripts')
        @vite(['resources/js/pages/assessments.js'])
    @endpush

@endsection