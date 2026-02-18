@extends('layouts.app')

@section('title', 'Ativos • Techbase GRC')

@section('content')
    <div class="card">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px">
            <div>
                <h3 style="margin:0">Ativos (RF1)</h3>
                <div class="muted" style="margin-top:4px">Registar, gerir e associar controlos (GAP/PARTIAL/COVERED) com
                    evidências.</div>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
                <button class="btn primary" type="button" id="btnOpenCreateAsset">+ Registar ativo</button>
            </div>
        </div>

        <div class="row" style="margin-top:12px">
            <div class="field">
                <label>Pesquisar</label>
                <input id="assetSearch" placeholder="Nome, tipo, responsável..." />
            </div>

            <div class="field">
                <label>Filtrar por criticidade</label>
                <select id="critFilter">
                    <option value="all">Todos</option>
                    <option value="Crítico">Crítico</option>
                    <option value="Alto">Alto</option>
                    <option value="Médio">Médio</option>
                    <option value="Baixo">Baixo</option>
                </select>
            </div>

            <div class="field">
                <label>Tipo</label>
                <select id="typeFilter">
                    <option value="all">Todos</option>
                    <option value="Servidor">Servidor</option>
                    <option value="Workstation">Workstation</option>
                    <option value="Aplicação">Aplicação</option>
                    <option value="Rede">Rede</option>
                    <option value="Cloud">Cloud</option>
                </select>
            </div>

            <div class="field">
                <label>Status (controlos)</label>
                <select id="controlStatusFilter">
                    <option value="all">Todos</option>
                    <option value="GAP">GAP</option>
                    <option value="PARTIAL">PARTIAL</option>
                    <option value="COVERED">COVERED</option>
                </select>
            </div>
        </div>

        <div style="height:12px"></div>

        <div class="panel">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap">
                <h2 style="margin:0">Inventário de ativos</h2>
                <div class="kpirow" style="margin:0">
                    <span class="chip">Total: <b id="kpiAssets">0</b></span>
                    <span class="chip warn">GAP: <b id="kpiGap">0</b></span>
                    <span class="chip">PARTIAL: <b id="kpiPartial">0</b></span>
                    <span class="chip ok">COVERED: <b id="kpiCovered">0</b></span>
                </div>
            </div>

            <div style="height:10px"></div>

            <table>
                <thead>
                    <tr>
                        <th>Ativo</th>
                        <th>Tipo</th>
                        <th>Criticidade</th>
                        <th>Responsável</th>
                        <th>Risco (P×I)</th>
                        <th>Conformidade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="assetsTbody">
                    {{-- render via JS --}}
                </tbody>
            </table>

            <div class="hint">Dica: “Conformidade” aqui é um resumo do status dos controlos associados (mock).</div>
        </div>
    </div>

    {{-- ================= MODAL: DETALHES DO ATIVO ================= --}}
    <div id="assetModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="assetModalTitle">
            <div class="modal-header">
                <div>
                    <div class="muted" style="margin-bottom:4px">Detalhes do ativo</div>
                    <div id="assetModalTitle" style="font-size:18px;font-weight:800">—</div>
                </div>
                <div style="display:flex; gap:10px; align-items:center">
                    <button class="btn" type="button" id="btnEditAsset">Editar</button>
                    <button id="assetModalClose" class="btn" type="button">Fechar</button>
                </div>
            </div>

            <div class="two" style="margin-top:12px">
                {{-- COLUNA ESQ --}}
                <div class="panel">
                    <h2>Informações</h2>

                    <div class="grid-2">
                        <div>
                            <div class="muted">Tipo</div>
                            <div id="mType">—</div>
                        </div>
                        <div>
                            <div class="muted">Criticidade</div>
                            <div id="mCrit">—</div>
                        </div>
                    </div>

                    <div style="height:10px"></div>

                    <div class="grid-2">
                        <div>
                            <div class="muted">Responsável</div>
                            <div id="mOwner">—</div>
                        </div>
                        <div>
                            <div class="muted">Criado por</div>
                            <div id="mCreatedBy" class="muted">—</div>
                        </div>
                    </div>

                    <div style="height:10px"></div>

                    <div class="muted">Descrição</div>
                    <div id="mNotes" class="muted">—</div>

                    <div style="height:12px"></div>

                    <h2>Risco</h2>
                    <div class="kpirow">
                        <span class="chip">Prob: <b id="mProb">-</b></span>
                        <span class="chip">Impacto: <b id="mImpact">-</b></span>
                        <span class="chip" id="mClassChip">Classe: -</span>
                        <button class="btn" type="button" id="btnGoRisks">Abrir Riscos</button>
                    </div>

                    <div style="height:12px"></div>

                    <h2>Atalhos</h2>
                    <div style="display:flex; gap:10px; flex-wrap:wrap">
                        <button class="btn" type="button" id="btnGoDocs">Ver evidências (Documentos)</button>
                        <button class="btn" type="button" id="btnGoAssessments">Abrir Avaliações</button>
                    </div>
                </div>

                {{-- COLUNA DIR --}}
                <div class="panel">
                    <h2>Matriz de risco</h2>
                    <div class="muted" style="margin-bottom:10px">Impacto (vertical) × Probabilidade (horizontal)</div>

                    <div class="matrix-wrap">
                        <div class="matrix-ylabel">Impacto</div>

                        <div class="matrix">
                            <div class="matrix-grid">
                                <div class="matrix-rowlabels" id="impactLabels"></div>
                                <div class="matrix-cells" id="riskMatrix"></div>
                                <div class="matrix-collabels" id="probLabels"></div>
                                <div class="matrix-bottomlabel">Probabilidade</div>
                            </div>

                            <div class="matrix-legend">
                                <span class="legend-item"><span class="sw vlow"></span> Muito Baixo</span>
                                <span class="legend-item"><span class="sw low"></span> Baixo</span>
                                <span class="legend-item"><span class="sw med"></span> Médio</span>
                                <span class="legend-item"><span class="sw high"></span> Alto</span>
                                <span class="legend-item"><span class="sw vhigh"></span> Muito Alto</span>
                            </div>
                        </div>
                    </div>

                    <div class="hint">A bolinha marca a célula atual do risco do ativo (P×I).</div>
                </div>
            </div>

            <div style="height:12px"></div>

            <div class="two">
                {{-- CONTROLOS --}}
                <div class="panel">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap">
                        <div>
                            <h2 style="margin:0">Controlos associados</h2>
                            <div class="muted">Define status (GAP/PARTIAL/COVERED), evidências e notas.</div>
                        </div>
                        <button class="btn primary" type="button" id="btnAddControlToAsset">+ Associar controlo</button>
                    </div>

                    <div style="height:10px"></div>

                    <div id="assetControlsList" class="controls-list">
                        {{-- render via JS --}}
                    </div>

                    <div class="hint">Mock: ao “Guardar”, o sistema criaria auditoria (RNF5) com diffs.</div>
                </div>

                {{-- SUGESTÕES IA --}}
                <div class="panel">
                    <h2>Sugestões automáticas (IA)</h2>
                    <div class="muted">Comparação entre descrição do ativo e controlos alegados (RF16 como apoio ao RF1).
                    </div>

                    <div style="height:10px"></div>

                    <div id="aiSuggestions" class="ai-box">
                        {{-- render via JS --}}
                    </div>

                    <div class="hint">Ideia: isto seria gerado por embeddings + regras (ex.: “diz ter inventário, mas sem
                        evidência”).</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= MODAL: CRIAR/EDITAR ATIVO ================= --}}
    <div id="assetEditModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="assetEditTitle">
            <div class="modal-header">
                <div>
                    <div class="muted" style="margin-bottom:4px" id="assetEditSubtitle">—</div>
                    <div id="assetEditTitle" style="font-size:18px;font-weight:800">—</div>
                </div>
                <div style="display:flex; gap:10px">
                    <button class="btn" type="button" id="assetEditClose">Fechar</button>
                </div>
            </div>

            <div class="two" style="margin-top:12px">
                <div class="panel">
                    <h2>Dados do ativo</h2>

                    <div class="two">
                        <div class="field">
                            <label>Nome</label>
                            <input id="fName" placeholder="Ex.: SRV-DB-01" />
                        </div>
                        <div class="field">
                            <label>Tipo</label>
                            <select id="fType">
                                <option>Servidor</option>
                                <option>Workstation</option>
                                <option>Aplicação</option>
                                <option>Rede</option>
                                <option>Cloud</option>
                            </select>
                        </div>
                    </div>

                    <div class="two">
                        <div class="field">
                            <label>Criticidade</label>
                            <select id="fCrit">
                                <option>Crítico</option>
                                <option>Alto</option>
                                <option>Médio</option>
                                <option>Baixo</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Responsável</label>
                            <input id="fOwner" placeholder="Ex.: TI • João" />
                        </div>
                    </div>

                    <div class="two">
                        <div class="field">
                            <label>Probabilidade (1–5)</label>
                            <select id="fProb">
                                <option>1</option>
                                <option>2</option>
                                <option>3</option>
                                <option>4</option>
                                <option>5</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Impacto (1–5)</label>
                            <select id="fImpact">
                                <option>1</option>
                                <option>2</option>
                                <option>3</option>
                                <option>4</option>
                                <option>5</option>
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label>Descrição</label>
                        <textarea id="fNotes"
                            placeholder="Descrição do ativo, contexto, onde está, como é gerido..."></textarea>
                    </div>

                    <div class="row">
                        <button class="btn primary" type="button" id="btnSaveAsset">Guardar (mock)</button>
                        <button class="btn warn" type="button" id="btnDeleteAsset">Eliminar (mock)</button>
                    </div>

                    <div class="hint">Mock: “Criado por” vem do utilizador autenticado (RNF2). Aqui vamos simular.</div>
                </div>

                <div class="panel">
                    <h2>Associar controlos no registo</h2>
                    <div class="muted">Podes já associar 1+ controlos (vai aparecer na lista do ativo).</div>

                    <div style="height:10px"></div>

                    <div class="row">
                        <div class="field" style="flex:1">
                            <label>Controlo</label>
                            <div style="display:flex; gap:10px; align-items:center">
                                <select id="fControlPick" style="flex:1">
                                    <option value="ID.GA-1">ID.GA-1 — Inventário de ativos</option>
                                    <option value="PR.IP-4">PR.IP-4 — Backups (testados)</option>
                                    <option value="ID.AR-1">ID.AR-1 — Análise de risco</option>
                                    <option value="PR.AC-1">PR.AC-1 — Controlo de acesso</option>
                                </select>

                                <span id="fControlInfo" class="ci" data-tip="">i</span>
                            </div>
                            <div class="muted" id="fControlInfoText" style="margin-top:6px"></div>
                        </div>
                        
<div class="field" style="min-width:210px">
                            <label>Nível de confiança (IA)</label>
                            <select id="fControlConfidence">
                                <option value="0.30">0.30</option>
                                <option value="0.55" selected>0.55</option>
                                <option value="0.75">0.75</option>
                                <option value="0.90">0.90</option>
                            </select>
                            <div class="hint" style="margin-top:6px">Mock: confiança gerada pelo RAG/IA (RF16).</div>
                        </div>

                        <div class="field" style="min-width:220px">
                            <label>Status declarado <span class="muted">(utilizador)</span></label>
                            <select id="fControlStatus">
                                <option value="GAP">GAP</option>
                                <option value="PARTIAL">PARTIAL</option>
                                <option value="COVERED">COVERED</option>
                            </select>
                            <div class="hint" id="fStatusHint" style="margin-top:6px"></div>
                        </div>

                        <div class="field" style="min-width:220px">
                            <label>Sugestão <span class="muted">(IA)</span></label>
                            <div class="chip" id="fAiSuggestChip">—</div>
                            <div class="hint" style="margin-top:6px">Mock: sugestão gerada pela IA ao validar descrição + evidências.</div>
                        </div>

                        <div class="field" style="min-width:160px">
                            <label>&nbsp;</label>
                            <button class="btn" type="button" id="btnAddControlInline">Adicionar</button>
                        </div>
                    </div>

                    <div class="field">
                        <label>Nota/Justificação (opcional)</label>
                        <textarea id="fControlNote"
                            placeholder="Ex.: Inventário existe, mas sem periodicidade definida."></textarea>
                    </div>

                    <div class="hint">Isto deixa o mockup “mais real”: o cliente consegue mexer e ver efeito imediato.</div>

                    <div style="height:12px"></div>
                    <h2>Pré-visualização</h2>
                    <div id="createControlsPreview" class="controls-list"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= MODAL: ASSOCIAR CONTROLO (DENTRO DO ATIVO) ================= --}}
    <div id="addControlModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="addControlTitle">
            <div class="modal-header">
                <div>
                    <div class="muted" style="margin-bottom:4px">Associar controlo ao ativo</div>
                    <div id="addControlTitle" style="font-size:18px;font-weight:800">—</div>
                </div>
                <button class="btn" type="button" id="addControlClose">Fechar</button>
            </div>

            <div class="two" style="margin-top:12px">
                <div class="panel">
                    <h2>Controlo</h2>
                    <div class="field">
                        <label>Selecionar</label>
                        <select id="acControl">
                            <option value="ID.GA-1">ID.GA-1 — Inventário de ativos</option>
                            <option value="PR.IP-4">PR.IP-4 — Backups (testados)</option>
                            <option value="ID.AR-1">ID.AR-1 — Análise de risco</option>
                            <option value="PR.AC-1">PR.AC-1 — Controlo de acesso</option>
                        </select>
                        <div style="display:flex; gap:10px; align-items:center; margin-top:8px">
                            <span id="acControlInfo" class="ci" data-tip="">i</span>
                            <div class="muted" id="acControlInfoText">—</div>
                        </div>
                    </div>

                    <div class="two">
                        <div class="field">
                            <label>Status declarado <span class="muted">(utilizador)</span></label>
                            <select id="acStatus">
                                <option value="GAP">GAP</option>
                                <option value="PARTIAL">PARTIAL</option>
                                <option value="COVERED">COVERED</option>
                            </select>
                            <div class="hint" id="acStatusHint" style="margin-top:6px"></div>
                        </div>

                        <div class="field">
                            <label>Nível de confiança <span class="muted">(IA)</span></label>
                            <select id="acConfidence">
                                <option>0.30</option>
                                <option>0.55</option>
                                <option>0.72</option>
                                <option>0.82</option>
                            </select>
                            <div class="hint" style="margin-top:6px">Mock: confiança gerada pelo RAG/IA (RF16).</div>
                        </div>
                    </div>

                    <div class="field">
                        <label>Sugestão <span class="muted">(IA)</span></label>
                        <div class="kpirow" style="gap:10px; flex-wrap:wrap">
                            <span class="chip" id="acAiSuggestChip">—</span>
                            <span class="chip" id="acAiDiffChip" style="display:none">Divergência</span>
                        </div>
                        <div class="hint" style="margin-top:6px">Se a sugestão divergir do status declarado, fica marcado para revisão (RF16/RNF5).</div>
                    </div>

                    <div class="field">
                        <label>Justificação</label>
                        <textarea id="acNote"
                            placeholder="Ex.: Descrição menciona inventário, mas falta evidência formal."></textarea>
                    </div>

                    <div class="row">
                        <button class="btn primary" type="button" id="btnAddControlConfirm">Adicionar</button>
                        <button class="btn" type="button" id="btnAddControlCancel">Cancelar</button>
                    </div>
                </div>

                <div class="panel">
                    <h2>Evidências (mock)</h2>
                    <div class="muted">No real, aqui abriria um seletor de documentos (RF2/RF3) para ligar evidências.</div>

                    <div class="evidence-box">
                        <div class="evi">
                            <span class="chip">Procedimento Inventário v1.0</span>
                            <span class="muted">PDF</span>
                        </div>
                        <div class="evi">
                            <span class="chip warn">Relatório Backups (Jan)</span>
                            <span class="muted">Pendente</span>
                        </div>
                    </div>

                    <div class="hint">Depois a gente integra com a aba Documentos (link por ID do documento).</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= CSS local (pode ir pro global depois) ================= --}}
    <style>
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

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .controls-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .control-row {
            border: 1px solid rgba(255, 255, 255, .10);
            border-radius: 14px;
            background: rgba(0, 0, 0, .14);
            padding: 10px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .control-left {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .control-title {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .control-code {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(96, 165, 250, .12);
            border: 1px solid rgba(96, 165, 250, .22);
            font-weight: 900;
        }

        .status-pill {
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, .14);
            font-weight: 900;
            font-size: 12px;
        }

        .st-gap {
            background: rgba(251, 113, 133, .12);
            border-color: rgba(251, 113, 133, .28);
        }

        .st-partial {
            background: rgba(251, 191, 36, .12);
            border-color: rgba(251, 191, 36, .26);
        }

        .st-covered {
            background: rgba(45, 212, 191, .10);
            border-color: rgba(45, 212, 191, .22);
        }

        .control-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .mini {
            font-size: 12px;
            padding: 8px 10px;
            border-radius: 12px;
        }

        .ai-box {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .ai-item {
            border: 1px solid rgba(255, 255, 255, .10);
            border-radius: 14px;
            background: rgba(0, 0, 0, .14);
            padding: 10px;
        }

        .ai-item .top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .ai-item .title {
            font-weight: 900;
        }

        .ai-item .desc {
            color: var(--muted);
            font-size: 12px;
            margin-top: 4px;
            line-height: 1.35;
        }

        .ai-item .badg {
            font-weight: 900;
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, .14);
            background: rgba(255, 255, 255, .06);
        }

        /* risk matrix (igual ao que já tinhas, com marker à esquerda e centrado verticalmente) */
        .matrix-wrap {
            display: grid;
            grid-template-columns: 24px 1fr;
            gap: 10px;
            align-items: stretch;
        }

        .matrix-ylabel {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            color: var(--muted);
            font-size: 12px;
            letter-spacing: .6px;
            display: flex;
            justify-content: center;
        }

        .matrix {
            border: 1px solid rgba(255, 255, 255, .10);
            border-radius: 14px;
            background: rgba(0, 0, 0, .14);
            padding: 12px;
        }

        .matrix-grid {
            display: grid;
            grid-template-columns: 90px 1fr;
            gap: 10px;
        }

        .matrix-rowlabels {
            display: grid;
            grid-template-rows: repeat(5, 64px);
            gap: 6px;
        }

        .matrix-rowlabels .lbl {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            color: var(--muted);
            font-size: 12px;
            padding-left: 6px;
        }

        .matrix-collabels {
            grid-column: 2/3;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            margin-top: -2px;
        }

        .matrix-collabels .lbl {
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }

        .matrix-cells {
            position: relative;
            grid-column: 2/3;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-template-rows: repeat(5, 64px);
            gap: 6px;
        }

        .mcell {
            position: relative;
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, .25);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 8px;
            font-weight: 700;
            color: rgba(255, 255, 255, .92);
            overflow: hidden;
        }

        .mcell small {
            display: block;
            font-weight: 600;
            opacity: .9;
            margin-bottom: 2px;
        }

        .mcell .score {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: .2px;
        }

        .mcell.vlow {
            background: #2b3447;
        }

        .mcell.low {
            background: #2e7d32;
        }

        .mcell.med {
            background: #f2b233;
            color: #1c1406;
        }

        .mcell.high {
            background: #d9534f;
        }

        .mcell.vhigh {
            background: #8b1d3a;
        }

        /* marker: lado esquerdo e centralizado verticalmente dentro da célula */
        .marker {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 11px;
            color: #0b1220;
            background: rgba(255, 255, 255, .92);
            border: 3px solid rgba(96, 165, 250, .95);
            box-shadow: 0 10px 20px rgba(0, 0, 0, .35);
            pointer-events: none;
        }

        .matrix-legend {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
            color: var(--muted);
            font-size: 12px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sw {
            width: 12px;
            height: 12px;
            border-radius: 4px;
            display: inline-block;
        }

        .sw.vlow {
            background: #2b3447;
        }

        .sw.low {
            background: #2e7d32;
        }

        .sw.med {
            background: #f2b233;
        }

        .sw.high {
            background: #d9534f;
        }

        .sw.vhigh {
            background: #8b1d3a;
        }

        .matrix-bottomlabel {
            grid-column: 2/3;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            margin-top: -6px;
        }

        .evidence-box {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .evi {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, .10);
            background: rgba(0, 0, 0, .14);
        }
    </style>

    <script>
        // Mock RBAC (RF19): define a role do utilizador autenticado.
        // Para testar rapidamente:
        // localStorage.setItem('mock_role','Admin') | 'GRC Manager' | 'Auditor' | 'Viewer'
        window.APP_USER_ROLE = localStorage.getItem('mock_role') || 'Viewer';
    </script>

    @vite(['resources/js/pages/assets.js'])
@endsection