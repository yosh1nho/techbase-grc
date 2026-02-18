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
                        <label>Ativo (se aplicável)</label>
                        <select>
                            <option>—</option>
                            <option>SRV-DB-01</option>
                            <option>NAS-BKP</option>
                        </select>
                    </div>
                </div>
                <div class="two">
                    <div class="field"><label>Framework</label><select>
                            <option>QNRCS v2.1</option>
                        </select></div>
                    <div class="field"><label>Período</label><input placeholder="Ex.: Q1 2026" /></div>
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
@endsection