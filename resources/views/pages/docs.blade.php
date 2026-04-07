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
                            <th>Assinatura</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="docsTbody">
                        <tr><td colspan="7" class="muted">A carregar...</td></tr>
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
<div id="uploadDocModal" class="modal-overlay" aria-hidden="true" style="display:none">
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

      {{-- Opção alternativa: escrever com IA sem fazer upload de ficheiro --}}
      <div id="u_aiDivider" style="display:flex;align-items:center;gap:10px;margin:4px 0 8px">
        <div style="flex:1;height:1px;background:var(--border)"></div>
        <span class="muted" style="font-size:12px;white-space:nowrap">ou</span>
        <div style="flex:1;height:1px;background:var(--border)"></div>
      </div>
      <button type="button" class="btn" id="btnOpenAiInUpload" style="width:100%;justify-content:center;gap:8px">
        ✦ Escrever com Assistente IA
        <span class="muted" style="font-size:11px;font-weight:400">(gera texto, guarda como .txt pendente)</span>
      </button>

      <div id="u_globalVersionBlock" class="two">
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
        <div style="display:flex; gap:10px; flex-wrap:wrap">
            <input type="file" id="fwUpdateFileInput" accept=".pdf,.txt,.md,.docx" style="display:none">
            <button class="btn" type="button" id="fwUpdateVersBtn">Atualizar versão</button>
            <button class="btn" style="color:#f87171" id="fwObsoleteBtn" type="button">Marcar Obsoleto</button>
        </div>
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



{{-- MODAL: Visualizador de documento --}}
<div id="docViewerModal" class="modal-overlay" aria-hidden="true" style="display:none">
  <div class="modal-card" role="dialog" aria-modal="true" style="width:min(1200px,96vw);max-height:94vh;display:flex;flex-direction:column;padding:0;overflow:hidden">

    {{-- Header --}}
    <div class="modal-header" style="padding:16px 20px;flex-shrink:0">
      <div style="min-width:0;flex:1">
        <div class="muted" style="font-size:11px;margin-bottom:2px">Documento</div>
        <div id="vwTitle" style="font-size:17px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">—</div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:6px">
          <span id="vwStatus"></span>
          <span id="vwSig"></span>
          <span class="muted" style="font-size:12px" id="vwType">—</span>
          <span class="muted" style="font-size:12px">v<span id="vwVersion">—</span></span>
          <span class="muted" style="font-size:12px"><span id="vwDate">—</span></span>
          <span class="muted" style="font-size:12px">Por: <span id="vwUploader">—</span></span>
        </div>
        <div class="muted" style="font-size:11px;margin-top:4px">SHA256: <span id="vwSha" style="font-family:monospace">—</span></div>
        <div id="vwRejection" style="display:none;margin-top:6px;padding:6px 10px;border-radius:8px;background:rgba(248,113,113,.1);color:#f87171;font-size:12px"></div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;flex-wrap:wrap">
        <button id="vwReupload" class="btn" type="button" title="Carregar nova versão">Nova versão</button>
        <button id="vwAiAssist" class="btn" type="button" title="Assistente IA">✦ IA</button>
        <a id="vwDownload" class="btn" target="_blank" style="text-decoration:none">Download</a>
        <button id="vwApprove" class="btn ok" type="button" style="display:none">Aprovar</button>
        <button id="vwClose" class="btn" type="button">Fechar</button>
      </div>
    </div>

    {{-- Body: preview (esq) + sugestões RF16 (dir) --}}
    <div style="display:flex;flex:1;min-height:0;overflow:hidden">

      {{-- Coluna esquerda: preview do ficheiro --}}
      <div style="flex:1;min-width:0;display:flex;flex-direction:column;overflow:hidden;border-right:1px solid rgba(255,255,255,.07)">

        <div id="vwPreviewArea" style="display:none;flex:1;overflow:auto;min-height:0"></div>

        {{-- DOCX --}}
        <div id="vwDocxMsg" style="display:none;flex:1;padding:40px;text-align:center;color:var(--muted)">
          <div style="font-size:48px;margin-bottom:16px">📄</div>
          <div style="font-size:15px;font-weight:600;margin-bottom:8px">Ficheiro Word (.docx)</div>
          <p style="font-size:13px">Não é possível pré-visualizar ficheiros Word directamente no browser.</p>
          <a id="vwDocxDownload" class="btn ok" style="text-decoration:none;margin-top:12px;display:inline-block" target="_blank">Download para visualizar</a>
        </div>

        {{-- Sem ficheiro --}}
        <div id="vwNoFile" style="display:none;flex:1;padding:40px;text-align:center;color:var(--muted)">
          <div style="font-size:48px;margin-bottom:16px">📭</div>
          <div style="font-size:15px;font-weight:600">Sem ficheiro associado</div>
          <p style="font-size:13px">Este documento não tem ficheiro no servidor.</p>
        </div>

      </div>

      {{-- Coluna direita: Sugestões RF16 --}}
      <div id="vwSuggestionsPanel" style="display:none;width:340px;flex-shrink:0;overflow-y:auto;padding:16px;background:rgba(0,0,0,.06)">

        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:4px">Sugestões automáticas</div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:14px;line-height:1.5">
          Controlos potencialmente cobertos por este documento, detectados via análise semântica (Pinecone / RF16).
        </div>

        {{-- Estado: a analisar --}}
        <div id="vwSuggestionsLoading" style="display:none;text-align:center;padding:24px 0">
          <div style="font-size:22px;margin-bottom:8px">🔍</div>
          <div class="muted" style="font-size:12px">A analisar controlos cobertos...</div>
        </div>

        {{-- Corpo das sugestões (tabela injectada pelo JS) --}}
        <div id="vwSuggestionsBody"></div>

        {{-- Meta info --}}
        <div id="vwSuggestionsMeta" style="display:none;margin-top:12px;padding:8px 10px;border-radius:8px;background:rgba(0,0,0,.1);font-size:11px;color:var(--muted)"></div>

        {{-- Botão re-analisar --}}
        <div style="margin-top:12px">
          <button id="vwReanalyse" class="btn small" type="button" style="width:100%;justify-content:center">↺ Re-analisar</button>
        </div>

      </div>

    </div>
  </div>
</div>


{{-- MODAL: Assistente IA (Ponto 2 — Geração de políticas) --}}
<div id="aiAssistModal" class="modal-overlay" aria-hidden="true" style="display:none">
  <div class="modal-card" role="dialog" aria-modal="true" style="width:min(800px,96vw);max-height:90vh;overflow:auto">

    <div class="modal-header">
      <div>
        <div class="muted" style="font-size:11px;margin-bottom:2px">Assistente IA</div>
        <div style="font-size:17px;font-weight:700">Gerar / Rever documento</div>
        <div class="muted" style="font-size:12px;margin-top:3px">Documento: <b id="aiDocTitle">—</b></div>
      </div>
      <button id="aiClose" class="btn" type="button">Fechar</button>
    </div>

    <div style="padding:16px 0 0">
      <div style="padding:12px 16px;border-radius:10px;background:rgba(96,165,250,.08);border:1px solid rgba(96,165,250,.18);margin-bottom:16px;font-size:13px;line-height:1.6">
        A IA usa os teus frameworks indexados (NIS2, QNRCS) como contexto para gerar texto alinhado com os controlos.
        O resultado pode ser copiado para o campo de texto ou guardado directamente como documento pendente de aprovação.
      </div>

      {{-- Tipo de documento (select que envia para o generator) --}}
      <div class="field" style="margin-bottom:12px">
        <label>Tipo de documento</label>
        <select id="aiDocType" style="width:100%">
          <option value="password_policy">Política de Gestão de Passwords</option>
          <option value="backup_procedure">Procedimento de Backup e Recuperação</option>
          <option value="access_control_policy">Política de Controlo de Acessos</option>
          <option value="incident_response">Plano de Resposta a Incidentes</option>
          <option value="asset_inventory">Procedimento de Inventário de Ativos</option>
          <option value="vulnerability_management">Política de Gestão de Vulnerabilidades</option>
          <option value="custom">Documento personalizado (instrução livre)</option>
        </select>
      </div>

      <div style="margin-bottom:12px">
        <div class="muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Sugestões rápidas</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn small" type="button" onclick="setAiTemplate('password_policy','Gera uma Política de Gestão de Passwords. Inclui requisitos mínimos de complexidade, periodicidade de renovação, uso de gestor de passwords e processo de reset seguro.')">Política de Passwords</button>
          <button class="btn small" type="button" onclick="setAiTemplate('backup_procedure','Gera um Procedimento de Backup e Recuperação. Inclui frequência das cópias, local de armazenamento (local e offsite), testes periódicos de restauro, RTO e RPO definidos.')">Proc. de Backup</button>
          <button class="btn small" type="button" onclick="setAiTemplate('access_control_policy','Gera uma Política de Controlo de Acessos baseada no princípio do menor privilégio. Inclui gestão de contas, revisão periódica de acessos e processo de offboarding.')">Controlo de Acessos</button>
          <button class="btn small" type="button" onclick="setAiTemplate('incident_response','Gera um Plano de Resposta a Incidentes de Segurança. Inclui classificação de incidentes, papéis e responsabilidades, fluxo de resposta e requisitos de notificação NIS2.')">Resposta a Incidentes</button>
          <button class="btn small" type="button" onclick="setAiTemplate('custom','Revê o seguinte texto e identifica lacunas face aos controlos do QNRCS e NIS2. Sugere melhorias concretas.')">Rever / Melhorar</button>
        </div>
      </div>

      <div class="field" style="margin-bottom:12px">
        <label>Instrução adicional <span class="muted" style="font-weight:400">(opcional — personaliza o documento gerado)</span></label>
        <textarea id="aiPromptInput" rows="3"
          placeholder="Ex.: Inclui cláusula sobre autenticação biométrica. O público-alvo são utilizadores não técnicos."
          style="width:100%;resize:vertical"></textarea>
        <div class="hint">Ctrl+Enter para gerar.</div>
      </div>

      <div style="display:flex;gap:8px;margin-bottom:8px">
        <button id="aiRunBtn" class="btn ok" type="button">✦ Gerar documento</button>
        <div class="field">
        <label>Formato do ficheiro</label>
        <select id="aiFileType">
        <option value="txt">TXT</option>
        <option value="pdf">PDF</option>
        <option value="md">Markdown</option>
        <option value="docx">Word (.docx)</option>
        </select>
        </div>
        <button class="btn" type="button" id="aiClearBtn">Limpar</button>
      </div>

      {{-- Controlos usados na geração --}}
      <div id="aiControlsUsed" style="display:none;font-size:11px;color:var(--muted);margin-bottom:8px;padding:6px 10px;border-radius:8px;background:rgba(96,165,250,.08);border:1px solid rgba(96,165,250,.15)"></div>

      {{-- Resultado editável --}}
      <div class="muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">
        Resultado <span style="font-weight:400;text-transform:none;letter-spacing:0">(editável antes de guardar)</span>
      </div>
      <textarea id="aiOutput"
        rows="14"
        placeholder="O documento gerado aparecerá aqui. Podes editar antes de guardar."
        style="width:100%;resize:vertical;font-size:13px;line-height:1.6;font-family:monospace;border-radius:10px;border:1px solid var(--border);background:rgba(0,0,0,.06);padding:14px;color:inherit"></textarea>

      <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap">
        <button class="btn" type="button" id="aiCopyBtn">Copiar texto</button>
        <button class="btn" id="aiDownload">Download</button>
        <button class="btn ok" type="button" id="aiSaveAsDoc">Usar no upload</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    @push('scripts')
        @vite(['resources/js/pages/docs.js'])
    @endpush

@endsection