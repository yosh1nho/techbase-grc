@extends('layouts.app')
@section('title', 'Frameworks • Techbase GRC')
@section('content')
    <section id="page-frameworks" class="page hide">
        <div class="card">
            <h3>Frameworks e versões (RF4)</h3>
            <div class="row" style="margin-top:10px">
                <button class="btn primary">Importar framework</button>
                <button class="btn">Atualizar versão</button>
                <button class="btn">Ver estrutura (Domínios → Controlos)</button>
            </div>
            <div style="height:12px"></div>
            <table>
                <thead>
                    <tr>
                        <th>Framework</th>
                        <th>Versão</th>
                        <th>Estado</th>
                        <th>Última importação</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><b>QNRCS</b></td>
                        <td>2.1</td>
                        <td><span class="tag ok"><span class="s"></span> Ativo</span></td>
                        <td class="muted">2026-02-10</td>
                    </tr>
                    <tr>
                        <td><b>NIS2 (mapeado)</b></td>
                        <td>2024</td>
                        <td><span class="tag"><span class="s"></span> Referência</span></td>
                        <td class="muted">2026-02-02</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection