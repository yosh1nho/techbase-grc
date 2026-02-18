@extends('layouts.app')
@section('title', 'Auditoria • Techbase GRC')
@section('content')
    <section id="page-audit" class="page hide">
        <div class="card">
            <h3>Auditoria / Logs (RNF5)</h3>
            <div class="panel" style="margin-top:10px">
                <h2>Registos recentes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Utilizador</th>
                            <th>Ação</th>
                            <th>Entidade</th>
                            <th>Detalhe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="muted">2026-02-16 09:22</td>
                            <td>ana</td>
                            <td>Chat</td>
                            <td>RF15</td>
                            <td class="muted">Pergunta sobre ID.AR-1, fontes: QNRCS</td>
                        </tr>
                        <tr>
                            <td class="muted">2026-02-16 09:10</td>
                            <td>joao</td>
                            <td>Upload</td>
                            <td>Documento</td>
                            <td class="muted">Procedimento Inventário v1.0</td>
                        </tr>
                        <tr>
                            <td class="muted">2026-02-15 18:05</td>
                            <td>pedro</td>
                            <td>Avaliação</td>
                            <td>QNRCS</td>
                            <td class="muted">Status PR.IP-4 = COVERED</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection