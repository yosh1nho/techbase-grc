@extends('layouts.app')
@section('title', 'Chat Governança • Techbase GRC')
@section('content')
    <section id="page-chat" class="page hide">
        <div class="card">
            <h3>Chat de governação (RF14, RF15)</h3>
            <div class="two" style="margin-top:10px">
                <div class="panel">
                    <h2>Conversa</h2>
                    <div class="panel" style="background:rgba(0,0,0,.18)">
                        <p class="muted"><b>Utilizador:</b> O que falta para cumprir o ID.AR-1?</p>
                        <p class="muted"><b>Sistema:</b> Não encontrei evidência de análise formal no último
                            ano. Recomendo iniciar o módulo “Calculadora de Risco”, anexar relatório e aprovar.
                        </p>
                        <div class="kpirow">
                            <span class="chip">Fontes: QNRCS v2.1</span>
                            <span class="chip">Docs: 0</span>
                            <span class="chip">Logs: OK</span>
                        </div>
                    </div>
                    <div style="height:10px"></div>
                    <div class="row">
                        <input style="flex:1" placeholder="Pergunta ao chat (RAG)..." />
                        <button class="btn primary">Enviar</button>
                    </div>
                    <p class="hint">Guardar logs de auditoria: pergunta, resposta, user, timestamp, fontes.</p>
                </div>

                <div class="panel">
                    <h2>Pesquisa semântica (RF14)</h2>
                    <input placeholder="Ex.: 'procedimento inventário', 'teste restore', 'plano incidentes'..." />
                    <div style="height:10px"></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Resultado</th>
                                <th>Tipo</th>
                                <th>Relevância</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><b>Procedimento Inventário v1.0</b>
                                    <div class="muted">Trecho: periodicidade mensal...</div>
                                </td>
                                <td>PDF</td>
                                <td><span class="tag ok"><span class="s"></span> 0.79</span></td>
                            </tr>
                            <tr>
                                <td><b>Relatório Backup Jan/2026</b>
                                    <div class="muted">Trecho: teste restore realizado...</div>
                                </td>
                                <td>PDF</td>
                                <td><span class="tag warn"><span class="s"></span> 0.63</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection