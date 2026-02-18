@extends('layouts.app')
@section('title', 'Documentos & Evidências • Techbase GRC')

@section('content')
    <section id="page-docs" class="page">
        <div class="card">
            <h3>Documentos & Evidências (RF2, RF3, RF16, RF14)</h3>

            <div class="two" style="margin-top:10px">
                <div class="panel">
                    <h2>Upload + Versionamento</h2>
                    <div class="row">
                        <div class="field">
                            <label>Tipo de documento</label>
                            <select>
                                <option>Política</option>
                                <option>Procedimento</option>
                                <option>Relatório</option>
                                <option>Imagem</option>
                                <option>PDF</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Ficheiro</label>
                            <input placeholder="(mock) selecionar ficheiro..." />
                        </div>
                    </div>
                    <div class="field">
                        <label>Descrição</label>
                        <textarea placeholder="Ex.: Procedimento de inventário — versão 1.0"></textarea>
                    </div>
                    <div class="row">
                        <button class="btn primary" type="button">Carregar</button>
                        <button class="btn" type="button">Criar nova versão</button>
                    </div>
                    <p class="hint">Guardar: autor, data, versão, hash, tags, sistema origem.</p>
                </div>

                <div class="panel">
                    <h2>Sugestões automáticas (RF16)</h2>
                    <p class="muted">Após upload, o sistema sugere controlos potencialmente cobertos.</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Controlo</th>
                                <th>Alinhamento</th>
                                <th>Justificação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><b>ID.GA-1</b>
                                    <div class="muted">Inventário</div>
                                </td>
                                <td><span class="tag ok"><span class="s"></span> 0.82</span></td>
                                <td class="muted">Menciona inventário, periodicidade e responsável.</td>
                            </tr>
                            <tr>
                                <td><b>PR.IP-4</b>
                                    <div class="muted">Backups</div>
                                </td>
                                <td><span class="tag warn"><span class="s"></span> 0.61</span></td>
                                <td class="muted">Descreve backup, mas sem evidência de testes.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="height:12px"></div>

            <div class="panel">
                <h2>Documentos no sistema</h2>
                <p class="muted">Lista de evidências e políticas carregadas. Gerir status e associações a controlos.</p>

                <table>
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Tipo</th>
                            <th>Versão</th>
                            <th>Status</th>
                            <th>Última atualização</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <b>Procedimento Inventário v1.0</b>
                                <div class="muted">PDF • hash: 9f2a…e11</div>
                            </td>
                            <td>Política</td>
                            <td>v1.0</td>
                            <td><span class="tag ok"><span class="s"></span> Ativo</span></td>
                            <td class="muted">2026-02-16</td>
                            <td>
                                <button class="btn" type="button" data-open-doc-modal
                                    data-doc-name="Procedimento Inventário v1.0" data-doc-type="Política"
                                    data-doc-version="v1.0" data-doc-status="Ativo"
                                    data-doc-updated="2026-02-16">Detalhes</button>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <b>Relatório Backups (Jan)</b>
                                <div class="muted">Imagem/Relatório • hash: 1ac0…b9d</div>
                            </td>
                            <td>Relatório</td>
                            <td>v1.2</td>
                            <td><span class="tag warn"><span class="s"></span> Pendente</span></td>
                            <td class="muted">2026-02-15</td>
                            <td>
                                <button class="btn" type="button" data-open-doc-modal
                                    data-doc-name="Relatório Backups (Jan)" data-doc-type="Relatório"
                                    data-doc-version="v1.2" data-doc-status="Pendente"
                                    data-doc-updated="2026-02-15">Detalhes</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- MODAL --}}
            <div id="docModal" class="modal-overlay" aria-hidden="true">
                <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="docModalTitle">
                    <div class="modal-header">
                        <div>
                            <div class="muted" style="margin-bottom:4px">Detalhes do documento</div>
                            <div id="docModalTitle" style="font-size:18px;font-weight:800">—</div>
                        </div>
                        <div style="display:flex; gap:10px; align-items:center">
                            <button id="docModalClose" class="btn" type="button">Fechar</button>
                        </div>
                    </div>

                    <div class="two" style="margin-top:12px">
                        {{-- ESQ --}}
                        <div class="panel">
                            <h2>Meta + Status</h2>

                            <div class="two">
                                <div>
                                    <div class="muted">Tipo</div>
                                    <div id="dType">—</div>
                                </div>
                                <div>
                                    <div class="muted">Versão</div>
                                    <div id="dVersion">—</div>
                                </div>
                            </div>

                            <div style="height:10px"></div>

                            <div class="two">
                                <div>
                                    <div class="muted">Última atualização</div>
                                    <div id="dUpdated" class="muted">—</div>
                                </div>
                                <div>
                                    <label class="muted">Status</label>
                                    <select id="dStatus">
                                        <option>Ativo</option>
                                        <option>Pendente</option>
                                        <option>Suspenso</option>
                                        <option>Inativo</option>
                                    </select>
                                </div>
                            </div>

                            <div style="height:12px"></div>

                            <div class="kpirow">
                                <span class="chip">Associações: <b id="dAssocCount">0</b></span>
                                <span class="chip warn">Revisões pendentes: <b id="dPendingCount">0</b></span>
                            </div>

                            <div style="height:12px"></div>

                            <h2>Associações</h2>
                            <p class="muted">Controlos ligados ao documento (RF3). Podes adicionar via chunk do sistema ou
                                trecho manual.</p>

                            <div id="assocList" class="assoc-list"></div>

                            <div style="display:flex; gap:10px; margin-top:10px">
                                <button id="addAssocBtn" class="btn" type="button">+ Nova associação</button>
                                <button id="saveDocBtn" class="btn primary" type="button">Guardar alterações</button>
                            </div>
                        </div>

                        {{-- DIR --}}
                        <div class="panel">
                            <h2>Chunks / Trechos analisados</h2>
                            <p class="muted">Sugestões de controlos por trecho (RF16). Aprova ou rejeita por checkbox.</p>

                            <div class="chunk">
                                <div class="chunk-head">
                                    <div>
                                        <b>Chunk #01</b>
                                        <div class="muted">“Inventário deve ser atualizado mensalmente…”</div>
                                    </div>
                                    <div class="chunk-actions">
                                        <span class="control-pill">
                                            ID.GA-1
                                            <span class="ci"
                                                data-tip="ID.GA-1 — Inventário de ativos: manter inventário atualizado, dono, periodicidade e evidências.">i</span>
                                        </span>
                                        <span class="chip">Cobertura: <b>Alta</b></span>
                                        <span class="chip">Confiança: <b>0.82</b></span>
                                        <label class="toggle">
                                            <input type="checkbox" checked>
                                            <span>Aprovar</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="chunk">
                                <div class="chunk-head">
                                    <div>
                                        <b>Chunk #02</b>
                                        <div class="muted">“Backups existem, mas não há evidência de testes…”</div>
                                    </div>
                                    <div class="chunk-actions">
                                        <span class="control-pill">
                                            PR.IP-4
                                            <span class="ci"
                                                data-tip="PR.IP-4 — Backups: definir, executar, testar e manter relatórios periódicos.">i</span>
                                        </span>
                                        <span class="chip">Cobertura: <b>Média</b></span>
                                        <span class="chip">Confiança: <b>0.61</b></span>
                                        <label class="toggle">
                                            <input type="checkbox">
                                            <span>Aprovar</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="hint">
                                Dica: ao “Guardar alterações”, o sistema registaria auditoria (RNF5) e atualizaria status.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CSS local (podes mover pro global depois) --}}
            <style>
                /* MODAL */
                .modal-overlay {
                    position: fixed;
                    inset: 0;
                    background: rgba(0, 0, 0, .62);
                    display: none;
                    align-items: center;
                    justify-content: center;
                    padding: 18px;
                    z-index: 99999;
                }

                .modal-overlay.open {
                    display: flex;
                }

                .modal-card {
                    width: min(1200px, 96vw);
                    max-height: 90vh;
                    overflow: auto;
                    border: 1px solid rgba(255, 255, 255, .10);
                    border-radius: 16px;
                    background: rgba(18, 26, 43, .96);
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

                .assoc-list {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }

                .assoc-row {
                    display: flex;
                    gap: 12px;
                    align-items: flex-start;
                    justify-content: space-between;
                    padding: 12px;
                    border-radius: 14px;
                    border: 1px solid rgba(255, 255, 255, .10);
                    background: rgba(0, 0, 0, .16);
                }

                .assoc-left {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                    width: 100%;
                }

                .assoc-meta {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                    flex-wrap: wrap;
                }

                .control-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 6px 10px;
                    border-radius: 999px;
                    background: rgba(96, 165, 250, .12);
                    border: 1px solid rgba(96, 165, 250, .22);
                    font-weight: 900;
                }

                .ci {
                    width: 18px;
                    height: 18px;
                    border-radius: 999px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: 900;
                    background: rgba(255, 255, 255, .14);
                    border: 1px solid rgba(255, 255, 255, .20);
                    cursor: default;
                    position: relative;
                }

                .ci:hover::after {
                    content: attr(data-tip);
                    position: absolute;
                    left: 50%;
                    top: -10px;
                    transform: translate(-50%, -100%);
                    width: 340px;
                    padding: 10px;
                    border-radius: 12px;
                    background: rgba(18, 26, 43, .98);
                    border: 1px solid rgba(255, 255, 255, .14);
                    box-shadow: 0 18px 30px rgba(0, 0, 0, .45);
                    color: var(--text);
                    font-weight: 600;
                    font-size: 12px;
                    line-height: 1.3;
                    z-index: 10001;
                }

                .ci:hover::before {
                    content: "";
                    position: absolute;
                    left: 50%;
                    top: -10px;
                    transform: translate(-50%, -2px);
                    width: 10px;
                    height: 10px;
                    background: rgba(18, 26, 43, .98);
                    border-left: 1px solid rgba(255, 255, 255, .14);
                    border-bottom: 1px solid rgba(255, 255, 255, .14);
                    rotate: 45deg;
                    z-index: 10001;
                }

                .assoc-actions {
                    display: flex;
                    gap: 8px;
                    align-items: center;
                    justify-content: flex-end;
                    min-width: 220px;
                }

                .chunk {
                    border: 1px solid rgba(255, 255, 255, .10);
                    border-radius: 14px;
                    background: rgba(0, 0, 0, .14);
                    padding: 10px;
                    margin-top: 10px;
                }

                .chunk-head {
                    display: flex;
                    justify-content: space-between;
                    gap: 12px;
                    align-items: flex-start;
                }

                .chunk-actions {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                    flex-wrap: wrap;
                    justify-content: flex-end;
                }

                .toggle {
                    display: flex;
                    gap: 8px;
                    align-items: center;
                    color: var(--muted);
                    font-size: 12px;
                }

                /* New assoc UI */
                .seg {
                    display: flex;
                    gap: 8px;
                    padding: 6px;
                    border-radius: 999px;
                    border: 1px solid rgba(255, 255, 255, .10);
                    background: rgba(0, 0, 0, .12);
                    width: fit-content;
                }

                .seg-btn {
                    padding: 8px 10px;
                    border-radius: 999px;
                    border: 1px solid transparent;
                    background: transparent;
                    color: var(--muted);
                    cursor: pointer;
                    font-weight: 800;
                    font-size: 12px;
                }

                .seg-btn.active {
                    background: rgba(96, 165, 250, .14);
                    border-color: rgba(96, 165, 250, .24);
                    color: rgba(255, 255, 255, .92);
                }

                .hide {
                    display: none !important;
                }

                .chunk-preview {
                    border: 1px solid rgba(255, 255, 255, .10);
                    background: rgba(0, 0, 0, .14);
                    border-radius: 12px;
                    padding: 10px;
                    color: rgba(255, 255, 255, .88);
                    font-size: 13px;
                    line-height: 1.35;
                    white-space: pre-wrap;
                }

                .mini-note {
                    color: var(--muted);
                    font-size: 12px;
                    line-height: 1.35;
                }
            </style>
        </div>
    </section>

    @push('scripts')

        @vite(['resources/js/pages/docs.js'])

    @endpush

@endsection