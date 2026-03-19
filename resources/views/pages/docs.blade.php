@extends('layouts.app')
@section('title', 'Documentos & Evidências • Techbase GRC')

@section('content')
    <section id="page-docs" class="page">
        <div class="card">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap">
            <div>
                <h3>Documentos & Evidências (RF2, RF3, RF16) + Frameworks (RF4)</h3>
                <div class="muted">Upload, versionamento, associações com controlos e normas oficiais.</div>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
                <button id="btnOpenUploadDoc" class="btn ok" type="button">+ Upload documento</button>
            </div>
            </div>


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
                                <option value="framework">Framework / Norma oficial</option>
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

        <div class="panel" id="frameworkPanel">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap">
            <div>
            <h2 style="margin-bottom:6px">Frameworks & Normas oficiais (RF4)</h2>
            <p class="hint">Fontes normativas (CNCS/QNRCS/NIS2). Não são “julgadas” pela IA — guiam as associações e relatórios.</p>
            </div>
            <div class="kpirow">
            <span class="chip">Total: <b id="fwCount">0</b></span>
            </div>
        </div>

        <table style="margin-top:10px">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Origem</th>
                <th>Versão</th>
                <th>Atualizado</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody id="fwTbody">
            <tr><td class="muted" colspan="6">—</td></tr>
            </tbody>
        </table>
        </div>
        </div>

            <div class="panel">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
                    <div>
                        <h2 style="margin:0">Documentos no sistema</h2>
                        <p class="muted" style="margin:3px 0 0;font-size:12px">Evidências e políticas. Documenta promovidos de comentários ficam aqui para aprovação.</p>
                    </div>
                    <span class="chip">Total: <b id="docsCount">0</b></span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Tipo</th>
                            <th>Versão</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="docsTbody">
                        <tr><td colspan="6" class="muted">A carregar...</td></tr>
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

                            <div style="display:flex; gap:10px; margin-top:10px; flex-wrap:wrap">
                                <button id="addAssocBtn" class="btn" type="button">+ Nova associação</button>
                                <button id="docApproveBtn" class="btn ok" type="button" style="display:none">Aprovar documento</button>
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
                            <div class="panel">
                            <h2>Pré-visualização</h2>
                            <p class="muted">Preview do ficheiro (PDF/Imagem). Mock: ficheiros em /public/mock/docs/</p>

                            <div id="docPreviewEmpty" class="chunk-preview">Sem ficheiro associado.</div>

                            <iframe
                                id="docPreviewPdf"
                                src=""
                                style="width:100%; height:340px; border:1px solid rgba(255,255,255,.10); border-radius:12px; background:rgba(0,0,0,.12); display:none">
                            </iframe>

                            <img
                                id="docPreviewImg"
                                src=""
                                alt="Pré-visualização"
                                style="width:100%; max-height:340px; object-fit:contain; border:1px solid rgba(255,255,255,.10); border-radius:12px; background:rgba(0,0,0,.12); display:none" />

                            <div style="height:12px"></div>

                            <h2>Chunks / Trechos analisados</h2>
                            ...
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
                    background: var(--modal-overlay);
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
                    border: 1px solid var(--modal-border);
                    border-radius: 16px;
                    background: var(--modal-bg); color: var(--text);
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
                    border: 1px solid var(--modal-border);
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
                    background: var(--modal-bg); color: var(--text);
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
                    background: var(--modal-bg); color: var(--text);
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
                    border: 1px solid var(--modal-border);
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
                    border: 1px solid var(--modal-border);
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
                    border: 1px solid var(--modal-border);
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
            
                /* ACTIONS (table buttons alignment) */
                .actions{
                    display:flex;
                    gap:10px;
                    justify-content:flex-end;
                    align-items:center;
                    flex-wrap:wrap;
                }
                .actions .btn{
                    min-width: 110px;
                    text-align:center;
                }
                .btn-ghost{
                    opacity:0;
                    pointer-events:none;
                }

</style>
        </div>
    </section>

    {{-- MODAL: Upload documento --}}
<div id="uploadDocModal" class="modal-overlay is-hidden" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="uploadDocTitle">
    <div class="modal-header">
      <div>
        <div class="muted" style="margin-bottom:4px">Novo documento</div>
        <div id="uploadDocTitle" style="font-size:18px;font-weight:800">Upload documento</div>
      </div>
      <div style="display:flex; gap:10px; align-items:center">
        <button id="uploadDocClose" class="btn" type="button">Fechar</button>
      </div>
    </div>

    <div style="height:10px"></div>

    <div class="panel">
      <div class="two">
        <div class="field">
          <label>Nome / Título</label>
          <input id="u_name" placeholder="ex.: QNRCS 2019 / Política de Backups v1.0" />
        </div>
        <div class="field">
          <label>Tipo</label>
          <select id="u_type">
            <option value="evidence">Evidência</option>
            <option value="policy">Política</option>
            <option value="procedure">Procedimento</option>
            <option value="report">Relatório</option>
            <option value="framework">Framework / Norma oficial</option>
          </select>
        </div>
      </div>

      <div class="field">
        <label>Ficheiro <span style="color:var(--bad)">*</span></label>
        <input type="file" id="u_file"
          accept=".pdf,.txt,.md,.docx"
          style="padding:8px;border-radius:10px;border:1px solid var(--border);background:var(--input-bg);color:inherit;width:100%;cursor:pointer" />
        <div class="hint" style="margin-top:4px">Formatos: PDF, TXT, DOCX. São indexados no Pinecone após aprovação (ou imediatamente se for Framework).</div>
      </div>

      <div class="two">
        <div class="field">
          <label>Versão <span class="muted" style="font-weight:400">(opcional)</span></label>
          <input id="u_version" placeholder="ex.: v1.0" />
        </div>
        <div class="field">
          <label>Data <span class="muted" style="font-weight:400">(opcional)</span></label>
          <input id="u_updated" type="date" />
        </div>
      </div>

      <div id="u_frameworkBlock" class="panel" style="margin-top:10px; display:none">
        <h2 style="margin-bottom:6px">Dados normativos (RF4)</h2>
        <div class="hint" style="margin-bottom:10px">Frameworks são aprovados e indexados automaticamente no Pinecone ao fazer upload.</div>
        <div class="two">
          <div class="field">
            <label>Origem</label>
            <select id="u_source">
              <option>CNCS</option><option>QNRCS</option>
              <option>NIS2</option><option>ISO 27001</option><option>Outro</option>
            </select>
          </div>
          <div class="field">
            <label>Notas</label>
            <input id="u_fwNotes" placeholder="ex.: Base normativa para avaliações CNCS" />
          </div>
        </div>
      </div>

      <div id="u_feedback" style="display:none;margin-top:12px;padding:10px 14px;border-radius:10px;font-size:13px;border:1px solid var(--border)"></div>

      <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:14px">
        <button id="uploadDocClose2" class="btn" type="button">Cancelar</button>
        <button id="u_save" class="btn ok" type="button">Fazer upload</button>
      </div>
    </div>
  </div>
</div>


{{-- MODAL: Detalhes do Framework --}}
<div id="fwModal" class="modal-overlay" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="fwModalTitle">
    <div class="modal-header">
      <div>
        <div class="muted" style="margin-bottom:4px">Framework / Norma oficial</div>
        <div id="fwModalTitle" style="font-size:18px;font-weight:800">—</div>
      </div>
      <button id="fwModalClose" class="btn" type="button">Fechar</button>
    </div>

    <div class="two" style="margin-top:12px">
      <div class="panel">
        <h2>Informações</h2>
        <div class="two">
          <div><div class="muted">Origem</div><div id="fwM_source">—</div></div>
          <div><div class="muted">Versão</div><div id="fwM_version">—</div></div>
        </div>
        <div style="height:10px"></div>
        <div class="two">
          <div><div class="muted">Atualizado</div><div id="fwM_updated">—</div></div>
          <div><div class="muted">Status</div><div id="fwM_status">—</div></div>
        </div>
        <div style="height:10px"></div>
        <div class="muted" style="margin-bottom:6px">Notas</div>
        <div id="fwM_notes" class="chunk-preview">—</div>

        <div style="height:12px"></div>
        <button class="btn" type="button">Atualizar versão (mock)</button>
      </div>

      <div class="panel">
        <h2>Pré-visualização (PDF)</h2>
        <iframe id="fwM_pdf" src=""
          style="width:100%; height:520px; border:1px solid rgba(255,255,255,.10); border-radius:12px; background:rgba(0,0,0,.12)">
        </iframe>
        <div class="hint" style="margin-top:8px">
          Se não abrir, confirma que o PDF está em <b>public/mock/frameworks/</b>.
        </div>
      </div>
    </div>
  </div>
</div>



    @push('scripts')

        @vite(['resources/js/pages/docs.js'])

    @endpush

@endsection