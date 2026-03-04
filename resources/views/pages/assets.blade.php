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
        <div class="modal-card am-modal" role="dialog" aria-modal="true" aria-labelledby="assetModalTitle">

            {{-- ══ HERO HEADER ══ --}}
            <div class="am-hero">
                <div class="am-hero-left">
                    <div class="am-asset-icon" id="mAssetIcon">⬡</div>
                    <div class="am-hero-info">
                        <div class="am-eyebrow">Detalhes do ativo</div>
                        <div class="am-title" id="assetModalTitle">—</div>
                        <div class="am-chips-row">
                            <span class="am-chip am-chip-type"  id="mTypeChip">—</span>
                            <span class="am-chip am-chip-crit"  id="mCritChip">—</span>
                            <span class="am-chip am-chip-mono"  id="mIpChip">—</span>
                        </div>
                    </div>
                </div>
                <div class="am-hero-actions">
                    <button class="btn" type="button" id="btnGoDocs"        style="font-size:12px;padding:6px 12px;">↗ Evidências</button>
                    <button class="btn" type="button" id="btnGoAssessments" style="font-size:12px;padding:6px 12px;">↗ Avaliações</button>
                    <button class="btn" type="button" id="btnEditAsset"     style="font-size:12px;padding:6px 12px;">✎ Editar</button>
                    <button class="btn" type="button" id="assetModalClose"  style="font-size:12px;padding:6px 12px;">✕</button>
                </div>
            </div>

            {{-- ══ KPI STRIP ══ --}}
            <div class="am-kpi-strip">
                <div class="am-kpi">
                    <div class="am-kpi-label">Responsável</div>
                    <div class="am-kpi-val" id="mOwner">—</div>
                </div>
                <div class="am-kpi">
                    <div class="am-kpi-label">Criado por</div>
                    <div class="am-kpi-val am-kpi-muted" id="mCreatedBy">—</div>
                </div>
                <div class="am-kpi am-kpi-sep">
                    <div class="am-kpi-label">Probabilidade</div>
                    <div class="am-kpi-val am-kpi-num" id="mProb">—</div>
                </div>
                <div class="am-kpi">
                    <div class="am-kpi-label">Impacto</div>
                    <div class="am-kpi-val am-kpi-num" id="mImpact">—</div>
                </div>
                <div class="am-kpi">
                    <div class="am-kpi-label">Score P×I</div>
                    <div class="am-kpi-val am-kpi-num am-kpi-score" id="mScoreVal">—</div>
                </div>
                <div class="am-kpi">
                    <div class="am-kpi-label">Classe de risco</div>
                    <div class="am-kpi-val" id="mClassChip">—</div>
                </div>
                <div class="am-kpi am-kpi-action">
                    <button class="btn" type="button" id="btnGoRisks" style="font-size:12px;padding:5px 12px;width:100%;">↗ Ver riscos</button>
                </div>
            </div>

            {{-- ══ DESCRIÇÃO ══ --}}
            <div class="am-desc-bar">
                <span class="am-desc-label">Descrição</span>
                <span id="mNotes" class="am-desc-text muted">—</span>
            </div>

            {{-- ══ BODY 3-col ══ --}}
            <div class="am-body">

                {{-- Col 1: Controlos --}}
                <div class="am-col">
                    <div class="am-section-header">
                        <div>
                            <div class="am-section-title">Controlos associados</div>
                            <div class="am-section-sub">GAP · PARTIAL · COVERED + evidências</div>
                        </div>
                        <button class="btn ok" type="button" id="btnAddControlToAsset" style="font-size:12px;padding:5px 12px;flex-shrink:0;">+ Associar</button>
                    </div>
                    <div id="assetControlsList" class="controls-list"></div>
                    <div class="am-hint">Mock: ao "Guardar", cria auditoria com diffs (RNF5).</div>
                </div>

                {{-- Col 2: Matriz de risco --}}
                <div class="am-col">
                    <div class="am-section-header">
                        <div>
                            <div class="am-section-title">Matriz de risco</div>
                            <div class="am-section-sub">Impacto × Probabilidade · ● posição atual</div>
                        </div>
                    </div>
                    <div class="matrix-wrap">
                        <div class="matrix-ylabel">Impacto</div>
                        <div class="matrix">
                            <div class="matrix-grid">
                                <div class="matrix-rowlabels" id="impactLabels"></div>
                                <div class="matrix-cells"     id="riskMatrix"></div>
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

                    {{-- Riscos & Planos --}}
                    <div class="am-section-header" style="margin-top:18px;">
                        <div>
                            <div class="am-section-title">Riscos &amp; Planos de tratamento</div>
                            <div class="am-section-sub">Riscos associados e planos em curso</div>
                        </div>
                    </div>
                    <div id="assetRiskTreatList"></div>
                </div>

                {{-- Col 3: Sugestões IA --}}
                <div class="am-col">
                    <div class="am-section-header">
                        <div>
                            <div class="am-section-title">
                                Sugestões IA
                                <span class="am-ai-badge">IA</span>
                            </div>
                            <div class="am-section-sub">RF16 — comparação descrição × controlos alegados</div>
                        </div>
                    </div>
                    <div id="aiSuggestions" class="ai-box"></div>
                    <div class="am-hint">Seria gerado por embeddings + regras (ex.: "diz ter inventário, mas sem evidência").</div>
                </div>

            </div>{{-- /am-body --}}

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
                        <div id="ipFieldWrap" class="field" style="display:none;">
                            <label>Endereço IP (opcional)</label>
                            <input id="fIp" placeholder="ex.: 10.0.0.12" />
                            <p class="hint">Para ativos de rede/infra (FW, Router, Switch, Servidor). Ajuda a correlacionar alertas (RF17).</p>
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
                        <div class="hint" style="margin-top:6px">Mock: sugestão gerada pela IA ao validar descrição +
                            evidências.</div>
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
                        <div class="hint" style="margin-top:6px">Se a sugestão divergir do status declarado, fica marcado
                            para revisão (RF16/RNF5).</div>
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
        /* ══ Modal overlay & card (shared) ══ */
        .modal-overlay {
            position: fixed; inset: 0;
            background: var(--modal-overlay);
            display: none; align-items: center; justify-content: center;
            padding: 18px; z-index: 99999;
        }
        .modal-overlay.open { display: flex; }

        .modal-card {
            width: min(1200px, 96vw);
            max-height: 92vh; overflow: auto;
            border: 1px solid var(--modal-border);
            border-radius: 16px;
            background: var(--modal-bg); color: var(--text);
            box-shadow: 0 30px 80px rgba(0,0,0,.6);
            padding: 0;
        }

        /* ══ ASSET MODAL specific ══ */

        /* Hero header */
        .am-hero {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 16px; padding: 20px 22px 18px;
            border-bottom: 1px solid var(--modal-border);
            flex-wrap: wrap;
        }
        .am-hero-left  { display: flex; align-items: center; gap: 16px; }
        .am-asset-icon {
            width: 50px; height: 50px; border-radius: 14px; flex-shrink: 0;
            display: grid; place-items: center; font-size: 22px;
            background: rgba(79,156,249,.1); border: 1px solid rgba(79,156,249,.25);
            box-shadow: 0 0 20px rgba(79,156,249,.1);
        }
        .am-eyebrow {
            font-size: 10px; font-weight: 600; color: var(--muted);
            text-transform: uppercase; letter-spacing: .08em; margin-bottom: 4px;
        }
        .am-title {
            font-size: 20px; font-weight: 800; color: var(--text);
            letter-spacing: -.2px; line-height: 1.2;
        }
        .am-chips-row { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
        .am-chip {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600;
            border: 1px solid var(--modal-border);
            background: rgba(255,255,255,.04); color: var(--muted);
        }
        .am-chip-type { background: rgba(79,156,249,.09); border-color: rgba(79,156,249,.22); color: #7eb8fb; }
        .am-chip-crit { background: rgba(251,191,36,.09); border-color: rgba(251,191,36,.22); color: var(--warn); }
        .am-chip-mono { font-family: var(--font-mono,monospace); font-size: 11px; }
        .am-hero-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-start; padding-top: 2px; }

        /* KPI strip */
        .am-kpi-strip {
            display: flex; gap: 0;
            border-bottom: 1px solid var(--modal-border);
            overflow-x: auto;
        }
        .am-kpi {
            flex: 1; min-width: 90px; padding: 13px 18px;
            border-right: 1px solid var(--modal-border);
        }
        .am-kpi:last-child { border-right: none; }
        .am-kpi-sep { border-left: 2px solid rgba(79,156,249,.2); }
        .am-kpi-action { display: flex; align-items: center; padding: 10px 14px; }
        .am-kpi-label {
            font-size: 10px; font-weight: 600; color: var(--muted);
            text-transform: uppercase; letter-spacing: .07em; margin-bottom: 5px;
            white-space: nowrap;
        }
        .am-kpi-val    { font-size: 13px; font-weight: 500; color: var(--text); }
        .am-kpi-muted  { color: var(--muted); }
        .am-kpi-num    { font-family: var(--font-mono,monospace); font-size: 18px; font-weight: 700; }
        .am-kpi-score  { color: var(--warn); }

        /* Descrição bar */
        .am-desc-bar {
            display: flex; align-items: baseline; gap: 14px; flex-wrap: wrap;
            padding: 12px 22px; border-bottom: 1px solid var(--modal-border);
            background: rgba(255,255,255,.015);
        }
        .am-desc-label {
            font-size: 10px; font-weight: 600; color: var(--muted);
            text-transform: uppercase; letter-spacing: .07em; white-space: nowrap; flex-shrink: 0;
        }
        .am-desc-text { font-size: 13px; line-height: 1.5; }

        /* Body 3-col */
        .am-body {
            display: grid; grid-template-columns: 1fr 1fr 1fr;
            gap: 0;
        }
        @media (max-width: 900px) { .am-body { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 600px) { .am-body { grid-template-columns: 1fr; } }

        .am-col {
            padding: 18px 20px;
            border-right: 1px solid var(--modal-border);
        }
        .am-col:last-child { border-right: none; }

        .am-section-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 10px; margin-bottom: 14px;
        }
        .am-section-title {
            font-size: 12px; font-weight: 700; color: var(--text);
            text-transform: uppercase; letter-spacing: .06em;
            display: flex; align-items: center; gap: 7px;
        }
        .am-section-sub { font-size: 11px; color: var(--muted); margin-top: 3px; }
        .am-ai-badge {
            display: inline-flex; align-items: center; padding: 2px 7px;
            border-radius: 999px; font-size: 9px; font-weight: 700; letter-spacing: .05em;
            background: rgba(167,139,250,.15); border: 1px solid rgba(167,139,250,.3);
            color: #c4b5fd;
        }
        .am-hint {
            margin-top: 12px; font-size: 11px; color: var(--muted);
            border-top: 1px solid var(--modal-border); padding-top: 10px;
            line-height: 1.45;
        }

        /* Controls list */
        .controls-list { display: flex; flex-direction: column; gap: 8px; }
        .control-row {
            border: 1px solid var(--modal-border); border-radius: 12px;
            background: rgba(255,255,255,.02); padding: 10px 12px;
            display: flex; flex-direction: column; gap: 8px;
        }
        .control-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .control-code {
            display: inline-flex; align-items: center; padding: 3px 10px;
            border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: .03em;
            background: rgba(79,156,249,.1); border: 1px solid rgba(79,156,249,.22);
            color: #7eb8fb; font-family: var(--font-mono,monospace);
        }
        .status-pill {
            padding: 3px 9px; border-radius: 999px;
            font-size: 10px; font-weight: 700; letter-spacing: .05em;
            border: 1px solid transparent;
        }
        .st-gap     { background: rgba(251,113,133,.1); border-color: rgba(251,113,133,.28); color: var(--bad); }
        .st-partial { background: rgba(251,191,36,.1);  border-color: rgba(251,191,36,.26);  color: var(--warn); }
        .st-covered { background: rgba(45,212,191,.09); border-color: rgba(45,212,191,.22);  color: var(--ok); }

        .control-left { display: flex; flex-direction: column; gap: 5px; }
        .control-title { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .control-actions { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
        .mini { font-size: 12px; padding: 5px 10px; border-radius: 10px; }

        /* AI box */
        .ai-box { display: flex; flex-direction: column; gap: 8px; }
        .ai-item {
            border: 1px solid var(--modal-border); border-radius: 12px;
            background: rgba(255,255,255,.02); padding: 10px 12px;
        }
        .ai-item .top  { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
        .ai-item .title { font-weight: 700; font-size: 13px; color: var(--text); }
        .ai-item .desc  { color: var(--muted); font-size: 12px; margin-top: 4px; line-height: 1.4; }
        .ai-item .badg  {
            font-size: 11px; font-weight: 700; padding: 3px 9px;
            border-radius: 999px; border: 1px solid var(--modal-border);
            background: rgba(255,255,255,.04); color: var(--muted); white-space: nowrap;
        }

        /* Risk matrix (preserved exactly) */
        .matrix-wrap { display: grid; grid-template-columns: 24px 1fr; gap: 10px; align-items: stretch; }
        .matrix-ylabel {
            writing-mode: vertical-rl; transform: rotate(180deg);
            color: var(--muted); font-size: 12px; letter-spacing: .6px;
            display: flex; justify-content: center;
        }
        .matrix {
            border: 1px solid var(--modal-border); border-radius: 12px;
            background: rgba(0,0,0,.12); padding: 12px;
        }
        .matrix-grid { display: grid; grid-template-columns: 90px 1fr; gap: 10px; }
        .matrix-rowlabels { display: grid; grid-template-rows: repeat(5, 52px); gap: 6px; }
        .matrix-rowlabels .lbl {
            display: flex; align-items: center; color: var(--muted);
            font-size: 11px; padding-left: 4px;
        }
        .matrix-collabels {
            grid-column: 2/3; display: grid; grid-template-columns: repeat(5,1fr);
            gap: 6px; margin-top: -2px;
        }
        .matrix-collabels .lbl { text-align: center; color: var(--muted); font-size: 11px; }
        .matrix-cells {
            position: relative; grid-column: 2/3;
            display: grid; grid-template-columns: repeat(5,1fr);
            grid-template-rows: repeat(5, 52px); gap: 6px;
        }
        .mcell {
            position: relative; border-radius: 8px; border: 1px solid rgba(0,0,0,.2);
            display: flex; align-items: center; justify-content: center;
            text-align: center; padding: 6px; font-weight: 700;
            color: rgba(255,255,255,.92); overflow: hidden;
        }
        .mcell small  { display: block; font-size: 10px; font-weight: 600; opacity:.85; margin-bottom:2px; }
        .mcell .score { font-size: 15px; font-weight: 900; }
        .mcell.vlow  { background: #2b3447; }
        .mcell.low   { background: #2e7d32; }
        .mcell.med   { background: #f2b233; color: #1c1406; }
        .mcell.high  { background: #d9534f; }
        .mcell.vhigh { background: #8b1d3a; }
        .marker {
            position: absolute; left: 6px; top: 50%; transform: translateY(-50%);
            width: 28px; height: 28px; border-radius: 999px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: 10px; color: #0b1220;
            background: rgba(255,255,255,.92); border: 2px solid rgba(96,165,250,.95);
            box-shadow: 0 6px 16px rgba(0,0,0,.35); pointer-events: none;
        }
        .matrix-legend {
            display: flex; gap: 10px; flex-wrap: wrap;
            margin-top: 10px; color: var(--muted); font-size: 11px;
        }
        .legend-item { display: flex; align-items: center; gap: 6px; }
        .sw { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
        .sw.vlow { background: #2b3447; } .sw.low { background: #2e7d32; }
        .sw.med  { background: #f2b233; } .sw.high { background: #d9534f; }
        .sw.vhigh { background: #8b1d3a; }
        .matrix-bottomlabel {
            grid-column: 2/3; text-align: center; color: var(--muted);
            font-size: 11px; margin-top: -6px;
        }

        /* Evidence box */
        .evidence-box { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
        .evi {
            display: flex; align-items: center; justify-content: space-between;
            gap: 10px; padding: 9px 12px; border-radius: 10px;
            border: 1px solid var(--modal-border); background: rgba(255,255,255,.02);
        }

        /* Risk treat rows */
        .am-risk-row {
            border: 1px solid var(--modal-border); border-radius: 10px;
            background: rgba(255,255,255,.02); padding: 10px 12px;
            margin-bottom: 8px;
        }
        .am-risk-row:last-child { margin-bottom: 0; }
        .am-risk-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 6px; }
        .am-risk-id {
            font-family: var(--font-mono,monospace); font-size: 10px; font-weight: 600;
            padding: 2px 8px; border-radius: 999px;
            background: rgba(251,113,133,.1); border: 1px solid rgba(251,113,133,.22); color: var(--bad);
        }
        .am-treat-row {
            margin-top: 6px; padding: 8px 10px; border-radius: 8px;
            background: rgba(45,212,191,.04); border: 1px solid rgba(45,212,191,.12);
            font-size: 12px;
        }
        .am-treat-id {
            font-family: var(--font-mono,monospace); font-size: 10px;
            color: var(--ok); font-weight: 600; margin-right: 6px;
        }

        /* Light mode overrides */
        :root[data-theme="light"] .am-chip-type { color: #1a5fc8; }
        :root[data-theme="light"] .am-chip-crit { color: #9a5c04; }
        :root[data-theme="light"] .am-asset-icon { background: rgba(79,156,249,.08); }
        :root[data-theme="light"] .am-desc-bar { background: rgba(15,23,42,.02); }
        :root[data-theme="light"] .control-code { color: #1a5fc8; }
        :root[data-theme="light"] .am-body .am-col { background: transparent; }

        /* grid-2 (used in edit modal) */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    </style>

    <script>
        // Mock RBAC (RF19): define a role do utilizador autenticado.
        // Para testar rapidamente:
        // localStorage.setItem('mock_role','Admin') | 'GRC Manager' | 'Auditor' | 'Viewer'
        window.APP_USER_ROLE = localStorage.getItem('Admin') || 'GRC Manager';
    </script>

    @vite(['resources/js/pages/assets.js'])
@endsection