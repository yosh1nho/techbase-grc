@extends('layouts.app')
@section('title', 'Questionário • Techbase GRC')
@section('content')
            <section id="page-questionnaire" class="page hide">
                <div class="card">
                    <h3>Questionário estruturado (RF13)</h3>
                    <div class="panel" style="margin-top:10px">
                        <h2>Secção 1 — Gestão de Ativos</h2>
                        <div class="field">
                            <label>Existe inventário atualizado de todos os dispositivos?</label>
                            <select>
                                <option>Sim</option>
                                <option>Não</option>
                            </select>
                            <p class="hint">Se “Não”, gerar template “Procedimento de Inventário”.</p>
                        </div>
                        <div class="field">
                            <label>Observações</label>
                            <textarea placeholder="Notas que irão para o relatório base."></textarea>
                        </div>
                        <div class="row">
                            <button class="btn">Anterior</button>
                            <button class="btn ok">Próximo</button>
                            <button class="btn primary">Gerar relatório (PDF)</button>
                        </div>
                    </div>
                </div>
            </section>
@endsection
