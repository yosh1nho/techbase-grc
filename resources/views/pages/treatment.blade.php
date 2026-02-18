@extends('layouts.app')
@section('title', 'Tratamento • Techbase GRC')
@section('content')
            <section id="page-treatment" class="page hide">
                <div class="card">
                    <h3>Planos de tratamento (RF10, RF11)</h3>
                    <div class="panel" style="margin-top:10px">
                        <h2>Criar plano</h2>
                        <div class="two">
                            <div class="field">
                                <label>Risco</label>
                                <select>
                                    <option>R-001 — Inventário incompleto</option>
                                    <option>R-003 — Backups sem teste</option>
                                </select>
                            </div>
                            <div class="field">
                                <label>Estratégia</label>
                                <select>
                                    <option>Mitigar</option>
                                    <option>Evitar</option>
                                    <option>Transferir</option>
                                    <option>Aceitar</option>
                                </select>
                            </div>
                        </div>
                        <div class="two">
                            <div class="field"><label>Responsável</label><input placeholder="Ex.: João (TI)" /></div>
                            <div class="field"><label>Prazo</label><input placeholder="YYYY-MM-DD" /></div>
                        </div>
                        <div class="field"><label>Ações</label><textarea
                                placeholder="Passos, critérios de conclusão, evidências esperadas."></textarea></div>
                        <button class="btn ok">Guardar plano</button>
                    </div>

                    <div style="height:12px"></div>
                    <div class="panel">
                        <h2>Acompanhamento (mock Kanban)</h2>
                        <div class="two">
                            <div class="panel">
                                <b>To do</b>
                                <p class="muted">R-001: Criar template de inventário</p>
                                <span class="chip">Evidência: PDF/Export</span>
                            </div>
                            <div class="panel">
                                <b>Em curso</b>
                                <p class="muted">R-003: Agendar testes de restore</p>
                                <span class="chip warn">Prazo: 2026-02-28</span>
                            </div>
                        </div>
                        <div style="height:10px"></div>
                        <div class="two">
                            <div class="panel">
                                <b>Concluído</b>
                                <p class="muted">—</p>
                                <span class="chip ok">Anexar evidência (RF11)</span>
                            </div>
                            <div class="panel">
                                <b>Em atraso</b>
                                <p class="muted">—</p>
                                <span class="chip bad">Ações pendentes</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
@endsection
