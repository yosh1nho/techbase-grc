@extends('layouts.app')

@section('title', 'Ativos • Techbase GRC')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════
     PAGE HEADER
═══════════════════════════════════════════════════════════════ --}}
<div class="pg-header">
    <div class="pg-header-left">
        <div class="pg-eyebrow">Inventário</div>
        <h1 class="pg-title">Ativos</h1>
        <p class="pg-sub">Gerir, classificar e associar controlos de conformidade aos ativos da organização.</p>
    </div>
    <div class="pg-header-actions">
        <button class="btn-ghost" type="button" id="btnSyncAcronis">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            Sync Acronis
        </button>
        <button class="btn-primary" type="button" id="btnOpenCreateAsset">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Registar ativo
        </button>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     KPI STRIP
═══════════════════════════════════════════════════════════════ --}}
<div class="kpi-strip">
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon-blue">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
        </div>
        <div class="kpi-body">
            <div class="kpi-val" id="kpiAssets">0</div>
            <div class="kpi-lbl">Total de ativos</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon-green">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="kpi-body">
            <div class="kpi-val kpi-ok" id="kpiCovered">0</div>
            <div class="kpi-lbl">Covered</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon-warn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="kpi-body">
            <div class="kpi-val kpi-warn" id="kpiPartial">0</div>
            <div class="kpi-lbl">Partial</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon-red">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div class="kpi-body">
            <div class="kpi-val kpi-bad" id="kpiGap">0</div>
            <div class="kpi-lbl">Gap</div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     FILTERS + TABLE
═══════════════════════════════════════════════════════════════ --}}
<div class="table-card">
    <div class="table-toolbar">
        <div class="search-wrap">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input class="search-input" id="assetSearch" placeholder="Pesquisar por nome, tipo, responsável..." />
        </div>
        <div class="filter-group">
            <select class="filter-select" id="critFilter">
                <option value="all">Criticidade</option>
                <option value="Crítico">Crítico</option>
                <option value="Alto">Alto</option>
                <option value="Médio">Médio</option>
                <option value="Baixo">Baixo</option>
            </select>
            <select class="filter-select" id="typeFilter">
                <option value="all">Tipo</option>
                <option value="Servidor">Servidor</option>
                <option value="Workstation">Workstation</option>
                <option value="Aplicação">Aplicação</option>
                <option value="Rede">Rede</option>
                <option value="Cloud">Cloud</option>
            </select>
            <select class="filter-select" id="controlStatusFilter">
                <option value="all">Conformidade</option>
                <option value="GAP">GAP</option>
                <option value="PARTIAL">PARTIAL</option>
                <option value="COVERED">COVERED</option>
            </select>
            <select class="filter-select" id="sourceFilter">
                <option value="all">Origem</option>
                <option value="manual">Manual</option>
                <option value="acronis">Acronis</option>
            </select>
        </div>
    </div>

    <table class="assets-table">
        <thead>
            <tr>
                <th>Ativo</th>
                <th>Tipo</th>
                <th>Criticidade</th>
                <th>Tags</th>
                <th>Origem</th>
                <th>Responsável</th>
                <th>Risco</th>
                <th>Conformidade</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="assetsTbody">
            {{-- render via JS --}}
        </tbody>
    </table>
</div>


{{-- ═══════════════════════════════════════════════════════════════
     MODAL: DETALHES DO ATIVO  (tabbed)
═══════════════════════════════════════════════════════════════ --}}
<div id="assetModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card am-modal" role="dialog" aria-modal="true">

        {{-- HEADER --}}
        <div class="am-header">
            <div class="am-header-left">
                <div class="am-icon" id="mAssetIcon">⬡</div>
                <div class="am-header-info">
                    <div class="am-eyebrow">
                        <span id="mSourceBadge" class="src-badge src-manual">manual</span>
                        <span class="am-eyebrow-text">Detalhes do ativo</span>
                    </div>
                    <div class="am-name" id="assetModalTitle">—</div>
                    <div class="am-meta-row">
                        <span class="meta-chip meta-type"  id="mTypeChip">—</span>
                        <span class="meta-chip meta-crit"  id="mCritChip">—</span>
                        <span class="meta-chip meta-ip"    id="mIpChip">—</span>
                        <span class="meta-chip meta-agent" id="mAgentChip">—</span>
                    </div>
                </div>
            </div>
            <div class="am-header-actions">
                <button class="btn-sm" type="button" id="btnEditAsset">✎ Editar</button>
                <button class="btn-sm btn-close-modal" type="button" id="assetModalClose">✕</button>
            </div>
        </div>

        {{-- TABS --}}
        <div class="am-tabs">
            <button class="am-tab active" data-tab="overview">Visão Geral</button>
            <button class="am-tab" data-tab="controls">
                Controlos
                <span class="tab-badge" id="tabBadgeControls">0</span>
            </button>
            <button class="am-tab" data-tab="risk">Risco &amp; Tratamento</button>
            <button class="am-tab" data-tab="ai">
                Sugestões IA
                <span class="tab-badge tab-badge-ai">IA</span>
            </button>
        </div>

        {{-- TAB: OVERVIEW --}}
        <div class="am-tab-panel active" id="tab-overview">
            <div class="ov-grid">

                {{-- Coluna Esquerda --}}
                <div class="ov-col">

                    <div class="info-section">
                        <div class="info-section-title">Informação Geral</div>
                        <div class="info-grid">
                            <div class="info-row">
                                <span class="info-lbl">Responsável</span>
                                <span class="info-val" id="mOwner">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">Criado por</span>
                                <span class="info-val info-muted" id="mCreatedBy">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">Origem</span>
                                <span class="info-val" id="mSource">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">Último sync</span>
                                <span class="info-val info-muted" id="mSyncedAt">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <div class="info-section-title">Rede</div>
                        <div class="info-grid">
                            <div class="info-row">
                                <span class="info-lbl">IP</span>
                                <span class="info-val info-mono" id="mIpDetail">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">MAC</span>
                                <span class="info-val info-mono" id="mMac">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">Hostname</span>
                                <span class="info-val info-mono" id="mHostname">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">Domínio</span>
                                <span class="info-val info-mono" id="mDomain">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <div class="info-section-title">Sistema Operativo</div>
                        <div class="info-grid">
                            <div class="info-row">
                                <span class="info-lbl">Nome</span>
                                <span class="info-val" id="mOsName">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">Versão</span>
                                <span class="info-val info-mono" id="mOsVersion">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">Build</span>
                                <span class="info-val info-mono" id="mOsBuild">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">Arquitectura</span>
                                <span class="info-val info-mono" id="mOsArch">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">Patch level</span>
                                <span class="info-val info-mono" id="mOsPatch">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Coluna Direita --}}
                <div class="ov-col">

                    <div class="info-section">
                        <div class="info-section-title">Agente Acronis</div>
                        <div class="info-grid">
                            <div class="info-row">
                                <span class="info-lbl">Estado</span>
                                <span class="info-val" id="mAgentStatus">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">Versão</span>
                                <span class="info-val info-mono" id="mAgentVersion">—</span>
                            </div>
                            <div class="info-row">
                                <span class="info-lbl">Último contacto</span>
                                <span class="info-val info-muted" id="mAgentLastSeen">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <div class="info-section-title">Protecção</div>
                        <div class="protection-grid" id="mProtectionGrid">
                            {{-- rendered by JS --}}
                        </div>
                    </div>

                    <div class="info-section">
                        <div class="info-section-title">Risco Intrínseco</div>
                        <div class="risk-score-block">
                            <div class="risk-score-nums">
                                <div class="risk-num-item">
                                    <div class="risk-num" id="mProb">—</div>
                                    <div class="risk-num-lbl">Probabilidade</div>
                                </div>
                                <div class="risk-num-sep">×</div>
                                <div class="risk-num-item">
                                    <div class="risk-num" id="mImpact">—</div>
                                    <div class="risk-num-lbl">Impacto</div>
                                </div>
                                <div class="risk-num-sep">=</div>
                                <div class="risk-num-item">
                                    <div class="risk-num risk-num-score" id="mScoreVal">—</div>
                                    <div class="risk-num-lbl" id="mClassChip">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="matrix-wrap" style="margin-top:12px;">
                            <div class="matrix-ylabel">Impacto</div>
                            <div class="matrix">
                                <div class="matrix-grid">
                                    <div class="matrix-rowlabels" id="impactLabels"></div>
                                    <div class="matrix-cells"     id="riskMatrix"></div>
                                    <div class="matrix-collabels" id="probLabels"></div>
                                    <div class="matrix-bottomlabel">Probabilidade</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <div class="info-section-title">Descrição</div>
                        <div class="desc-text" id="mNotes">—</div>
                    </div>

                </div>
            </div>

            {{-- quick actions footer --}}
            <div class="ov-actions">
                <button class="btn-ghost btn-sm" type="button" id="btnGoDocs">↗ Evidências</button>
                <button class="btn-ghost btn-sm" type="button" id="btnGoAssessments">↗ Avaliações</button>
                <button class="btn-ghost btn-sm" type="button" id="btnGoRisks">↗ Ver riscos</button>
            </div>
        </div>

        {{-- TAB: CONTROLS --}}
        <div class="am-tab-panel" id="tab-controls">
            <div class="tab-panel-header">
                <div>
                    <div class="tab-panel-title">Controlos associados</div>
                    <div class="tab-panel-sub">Avaliação GAP · PARTIAL · COVERED com evidências documentais</div>
                </div>
                <button class="btn-primary btn-sm" type="button" id="btnAddControlToAsset">+ Associar controlo</button>
            </div>
            <div id="assetControlsList" class="controls-list"></div>
        </div>

{{-- TAB: RISK --}}
        <div class="am-tab-panel" id="tab-risk">
            <div class="tab-panel-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <div class="tab-panel-title">Riscos &amp; Planos de tratamento</div>
                    <div class="tab-panel-sub">Riscos identificados e respectivos planos em curso</div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn-ghost btn-sm" type="button" id="btnGoRisksForAsset">↗ Módulo de riscos</button>
                    
                    <button id="btnCreateRiskFromAssetTab" class="btn small" type="button" style="display:inline-flex; align-items:center; gap:6px; color: #fb923c; border-color: rgba(251,146,60,0.5); background: rgba(251,146,60,0.1);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
                        Criar Risco
                    </button>
                </div>
            </div>
            
            {{-- A lista onde o JS vai injetar os riscos --}}
            <div id="assetRiskTreatList" style="display: flex; flex-direction: column; gap: 8px;"></div>
        </div>

        {{-- TAB: AI --}}
        <div class="am-tab-panel" id="tab-ai">
            <div class="tab-panel-header">
                <div>
                    <div class="tab-panel-title">Sugestões IA <span class="ai-label">IA</span></div>
                    <div class="tab-panel-sub">RF16 — comparação descrição × controlos declarados</div>
                </div>
            </div>
            <div id="aiSuggestions" class="ai-box"></div>
            <div class="tab-hint">Gerado por embeddings + regras: "diz ter inventário, mas sem evidência formal".</div>
        </div>

    </div>{{-- /modal-card --}}
</div>


{{-- ═══════════════════════════════════════════════════════════════
     MODAL: CRIAR/EDITAR ATIVO
═══════════════════════════════════════════════════════════════ --}}
<div id="assetEditModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card modal-card-edit" role="dialog" aria-modal="true">
        <div class="modal-header">
            <div>
                <div class="muted" style="font-size:11px;margin-bottom:4px" id="assetEditSubtitle">—</div>
                <div id="assetEditTitle" style="font-size:18px;font-weight:800">—</div>
            </div>
            <button class="btn-sm btn-close-modal" type="button" id="assetEditClose">✕ Fechar</button>
        </div>

        <div class="edit-two" style="margin-top:16px">
            <div class="panel">
                <h2 style="font-size:13px;font-weight:700;margin-bottom:14px;">Dados do ativo</h2>

                <div class="field-grid-2">
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
                            <option>Endpoint</option>
                        </select>
                    </div>
                </div>

                <div class="field-grid-2">
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

                <div id="ipFieldWrap" class="field" style="display:none;">
                    <label>Endereço IP</label>
                    <input id="fIp" placeholder="ex.: 192.168.10.1" />
                </div>
                
                <div class="field">
                    <label>Tags (separadas por vírgula)</label>
                    <input id="fTags" class="search-input" placeholder="Ex.: Produção, PCI-DSS, Firewall" />
                </div>

                <div class="field-grid-2">
                    <div class="field">
                        <label>Probabilidade (1–5)</label>
                        <select id="fProb">
                            <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Impacto (1–5)</label>
                        <select id="fImpact">
                            <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label>Descrição</label>
                    <textarea id="fNotes" rows="3" placeholder="Descrição do ativo, contexto, onde está, como é gerido..."></textarea>
                </div>

                <div style="display:flex;gap:8px;margin-top:4px;">
                    <button class="btn-primary" type="button" id="btnSaveAsset">Guardar</button>
                    <button class="btn-danger" type="button" id="btnDeleteAsset" style="display:none;">Eliminar</button>
                </div>
            </div>

            <div class="panel">
                <h2 style="font-size:13px;font-weight:700;margin-bottom:14px;">Associar controlos</h2>

                <div class="field">
                    <label>Controlo</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <select id="fControlPick" style="flex:1">
                            <option value="ID.GA-1">ID.GA-1 — Inventário de ativos</option>
                            <option value="PR.IP-4">PR.IP-4 — Backups (testados)</option>
                            <option value="ID.AR-1">ID.AR-1 — Análise de risco</option>
                            <option value="PR.AC-1">PR.AC-1 — Controlo de acesso</option>
                        </select>
                        <span id="fControlInfo" class="ci" data-tip="">i</span>
                    </div>
                    <div class="muted" id="fControlInfoText" style="margin-top:6px;font-size:12px;"></div>
                </div>

                <div class="field-grid-2">
                    <div class="field">
                        <label>Status declarado</label>
                        <select id="fControlStatus">
                            <option value="GAP">GAP</option>
                            <option value="PARTIAL">PARTIAL</option>
                            <option value="COVERED">COVERED</option>
                        </select>
                        <div class="hint" id="fStatusHint" style="margin-top:4px"></div>
                    </div>
                    <div class="field">
                        <label>Sugestão IA</label>
                        <span class="chip" id="fAiSuggestChip">—</span>
                    </div>
                </div>

                <div class="field">
                    <label>Nota / Justificação</label>
                    <textarea id="fControlNote" rows="2" placeholder="Ex.: Inventário existe, mas sem periodicidade definida."></textarea>
                </div>

                <button class="btn-ghost" type="button" id="btnAddControlInline">+ Adicionar controlo</button>

                <div style="height:12px"></div>
                <div style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:8px;">Pré-visualização</div>
                <div id="createControlsPreview" class="controls-list"></div>
            </div>
        </div>
    </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════
     MODAL: ASSOCIAR CONTROLO (dentro do ativo)
═══════════════════════════════════════════════════════════════ --}}
<div id="addControlModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card modal-card-sm" role="dialog" aria-modal="true">
        <div class="modal-header">
            <div>
                <div class="muted" style="font-size:11px;margin-bottom:4px">Associar controlo ao ativo</div>
                <div id="addControlTitle" style="font-size:18px;font-weight:800">—</div>
            </div>
            <button class="btn-sm btn-close-modal" type="button" id="addControlClose">✕</button>
        </div>

        <div class="edit-two" style="margin-top:16px">
            <div class="panel">
                <div class="field">
                    <label>Controlo</label>
                    <select id="acControl">
                        <option value="ID.GA-1">ID.GA-1 — Inventário de ativos</option>
                        <option value="PR.IP-4">PR.IP-4 — Backups (testados)</option>
                        <option value="ID.AR-1">ID.AR-1 — Análise de risco</option>
                        <option value="PR.AC-1">PR.AC-1 — Controlo de acesso</option>
                    </select>
                    <div style="display:flex;gap:8px;align-items:center;margin-top:8px">
                        <span id="acControlInfo" class="ci" data-tip="">i</span>
                        <div class="muted" id="acControlInfoText" style="font-size:12px;">—</div>
                    </div>
                </div>

                <div class="field-grid-2">
                    <div class="field">
                        <label>Status declarado</label>
                        <select id="acStatus">
                            <option value="GAP">GAP</option>
                            <option value="PARTIAL">PARTIAL</option>
                            <option value="COVERED">COVERED</option>
                        </select>
                        <div class="hint" id="acStatusHint" style="margin-top:4px"></div>
                    </div>
                    <div class="field">
                        <label>Confiança IA</label>
                        <select id="acConfidence">
                            <option>0.30</option>
                            <option>0.55</option>
                            <option>0.72</option>
                            <option>0.82</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label>Sugestão IA</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        <span class="chip" id="acAiSuggestChip">—</span>
                        <span class="chip" id="acAiDiffChip" style="display:none">Divergência</span>
                    </div>
                </div>

                <div class="field">
                    <label>Justificação</label>
                    <textarea id="acNote" rows="2" placeholder="Ex.: Descrição menciona inventário, mas falta evidência formal."></textarea>
                </div>

                <div style="display:flex;gap:8px;">
                    <button class="btn-primary" type="button" id="btnAddControlConfirm">Adicionar</button>
                    <button class="btn-ghost" type="button" id="btnAddControlCancel">Cancelar</button>
                </div>
            </div>

            <div class="panel">
                <h2 style="font-size:13px;font-weight:700;margin-bottom:10px;">Evidências (mock)</h2>
                <div class="muted" style="font-size:12px;margin-bottom:12px;">Aqui será um seletor de documentos ligados (RF2/RF3).</div>
                <div class="evidence-box">
                    <div class="evi"><span class="chip">Procedimento Inventário v1.0</span><span class="muted" style="font-size:11px;">PDF</span></div>
                    <div class="evi"><span class="chip warn">Relatório Backups (Jan)</span><span class="muted" style="font-size:11px;">Pendente</span></div>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════
     CSS
═══════════════════════════════════════════════════════════════ --}}
<style>
/* ── Page layout ── */
.pg-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px; margin-bottom: 20px; flex-wrap: wrap;
}
.pg-header-left { display: flex; flex-direction: column; gap: 2px; }
.pg-eyebrow {
    font-size: 10px; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: var(--muted); margin-bottom: 2px;
}
.pg-title { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -.3px; }
.pg-sub   { margin: 4px 0 0; font-size: 13px; color: var(--muted); }
.pg-header-actions { display: flex; gap: 8px; align-items: center; padding-top: 4px; }

/* ── KPI strip ── */
.kpi-strip {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 10px; margin-bottom: 16px;
}
.kpi-card {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; border-radius: 12px;
    background: var(--modal-bg); border: 1px solid var(--modal-border);
}
.kpi-icon {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    display: grid; place-items: center;
}
.kpi-icon-blue  { background: rgba(79,156,249,.1);  border: 1px solid rgba(79,156,249,.2);  color: #7eb8fb; }
.kpi-icon-green { background: rgba(45,212,191,.09); border: 1px solid rgba(45,212,191,.2);  color: var(--ok); }
.kpi-icon-warn  { background: rgba(251,191,36,.1);  border: 1px solid rgba(251,191,36,.22); color: var(--warn); }
.kpi-icon-red   { background: rgba(251,113,133,.1); border: 1px solid rgba(251,113,133,.22); color: var(--bad); }
.kpi-val  { font-size: 22px; font-weight: 800; font-family: var(--font-mono,monospace); line-height: 1; }
.kpi-lbl  { font-size: 11px; color: var(--muted); margin-top: 3px; }
.kpi-ok   { color: var(--ok); }
.kpi-warn { color: var(--warn); }
.kpi-bad  { color: var(--bad); }

/* ── Table card ── */
.table-card {
    border-radius: 14px; border: 1px solid var(--modal-border);
    background: var(--modal-bg); overflow: hidden;
}
.table-toolbar {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px; border-bottom: 1px solid var(--modal-border);
    flex-wrap: wrap;
}
.search-wrap {
    position: relative; flex: 1; min-width: 200px;
}
.search-icon {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    color: var(--muted); pointer-events: none;
}
.search-input {
    width: 100%; padding: 7px 10px 7px 32px;
    border: 1px solid var(--modal-border); border-radius: 8px;
    background: rgba(255,255,255,.04); color: var(--text);
    font-size: 13px;
}
.filter-group  { display: flex; gap: 8px; flex-wrap: wrap; }
.filter-select {
    padding: 6px 10px; border: 1px solid var(--modal-border);
    border-radius: 8px; background: rgba(255,255,255,.04);
    color: var(--text); font-size: 12px; cursor: pointer;
}

.assets-table { width: 100%; border-collapse: collapse; }
.assets-table thead th {
    padding: 10px 14px; text-align: left;
    font-size: 10px; font-weight: 700; letter-spacing: .07em;
    text-transform: uppercase; color: var(--muted);
    border-bottom: 1px solid var(--modal-border);
    background: rgba(0,0,0,.04);
}
.assets-table tbody tr {
    border-bottom: 1px solid var(--modal-border);
    transition: background .12s;
}
.assets-table tbody tr:last-child { border-bottom: none; }
.assets-table tbody tr:hover { background: rgba(79,156,249,.04); }
.assets-table td {
    padding: 11px 14px; font-size: 13px; color: var(--text);
    vertical-align: middle;
}
.asset-name   { font-weight: 600; }
.asset-sub    { font-size: 11px; color: var(--muted); margin-top: 2px; font-family: var(--font-mono,monospace); }

/* source badge */
.src-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 999px;
    font-size: 10px; font-weight: 700; letter-spacing: .04em;
    text-transform: uppercase;
}
.src-manual  { background: rgba(79,156,249,.1);  border: 1px solid rgba(79,156,249,.22);  color: #7eb8fb; }
.src-acronis { background: rgba(251,191,36,.09); border: 1px solid rgba(251,191,36,.22); color: var(--warn); }

/* criticality tags */
.tag { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.tag .s { width: 6px; height: 6px; border-radius: 50%; }
.tag.bad  { background: rgba(251,113,133,.1); border: 1px solid rgba(251,113,133,.22); color: var(--bad); }
.tag.bad  .s { background: var(--bad); }
.tag.warn { background: rgba(251,191,36,.1);  border: 1px solid rgba(251,191,36,.22);  color: var(--warn); }
.tag.warn .s { background: var(--warn); }
.tag.ok   { background: rgba(45,212,191,.09); border: 1px solid rgba(45,212,191,.22);  color: var(--ok); }
.tag.ok   .s { background: var(--ok); }
.tag.def  { background: rgba(255,255,255,.04); border: 1px solid var(--modal-border); color: var(--muted); }
.tag.def  .s { background: var(--muted); }

/* compliance inline */
.compl-bar { display: flex; gap: 4px; align-items: center; }
.compl-seg { height: 5px; border-radius: 99px; flex: 1; min-width: 8px; }

/* btn variants */
.btn-primary {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 8px; border: none; cursor: pointer;
    background: #4f9cf9; color: #fff; font-size: 12px; font-weight: 600;
    transition: opacity .15s;
}
.btn-primary:hover { opacity: .85; }
.btn-ghost {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 12px; border-radius: 8px; cursor: pointer;
    background: rgba(255,255,255,.04); border: 1px solid var(--modal-border);
    color: var(--text); font-size: 12px; font-weight: 500;
    transition: background .12s;
}
.btn-ghost:hover { background: rgba(255,255,255,.08); }
.btn-danger {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 12px; border-radius: 8px; cursor: pointer;
    background: rgba(251,113,133,.1); border: 1px solid rgba(251,113,133,.25);
    color: var(--bad); font-size: 12px; font-weight: 600;
}
.btn-sm { font-size: 11px; padding: 5px 10px; border-radius: 7px; }
.btn-close-modal {
    background: rgba(255,255,255,.04); border: 1px solid var(--modal-border);
    color: var(--muted); cursor: pointer; border-radius: 7px;
    padding: 5px 9px; font-size: 11px; font-weight: 600;
}
.btn-close-modal:hover { background: rgba(255,255,255,.08); }

/* ── MODAL shared ── */
.modal-overlay {
    position: fixed; inset: 0;
    background: var(--modal-overlay);
    display: none; align-items: center; justify-content: center;
    padding: 18px; z-index: 99999;
}
.modal-overlay.open { display: flex; }
.modal-card {
    width: min(1080px, 96vw); max-height: 92vh;
    overflow: auto; border: 1px solid var(--modal-border);
    border-radius: 16px; background: var(--modal-bg);
    color: var(--text); box-shadow: 0 30px 80px rgba(0,0,0,.55);
}
.modal-card-edit { width: min(900px, 96vw); }
.modal-card-sm   { width: min(780px, 96vw); }
.modal-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 18px 20px 14px; border-bottom: 1px solid var(--modal-border);
}

/* ── ASSET MODAL ── */
.am-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 16px; padding: 20px 22px 16px;
    border-bottom: 1px solid var(--modal-border);
    flex-wrap: wrap;
}
.am-header-left   { display: flex; align-items: flex-start; gap: 14px; }
.am-icon {
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 22px;
    background: rgba(79,156,249,.1); border: 1px solid rgba(79,156,249,.2);
    margin-top: 2px;
}
.am-eyebrow {
    display: flex; align-items: center; gap: 7px; margin-bottom: 5px;
}
.am-eyebrow-text {
    font-size: 10px; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: var(--muted);
}
.am-name {
    font-size: 21px; font-weight: 800; letter-spacing: -.3px;
    color: var(--text); line-height: 1.2;
}
.am-meta-row { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
.meta-chip {
    display: inline-flex; align-items: center; padding: 2px 9px;
    border-radius: 999px; font-size: 11px; font-weight: 600;
    border: 1px solid var(--modal-border); color: var(--muted);
    background: rgba(255,255,255,.03);
}
.meta-type  { background: rgba(79,156,249,.09);  border-color: rgba(79,156,249,.22);  color: #7eb8fb; }
.meta-crit  { background: rgba(251,191,36,.09);  border-color: rgba(251,191,36,.22);  color: var(--warn); }
.meta-ip    { font-family: var(--font-mono,monospace); font-size: 10px; }
.meta-agent.online  { background: rgba(45,212,191,.09); border-color: rgba(45,212,191,.22); color: var(--ok); }
.meta-agent.offline { background: rgba(251,113,133,.1); border-color: rgba(251,113,133,.22); color: var(--bad); }
.am-header-actions { display: flex; gap: 8px; align-items: flex-start; padding-top: 2px; }

/* ── TABS ── */
.am-tabs {
    display: flex; gap: 0;
    border-bottom: 1px solid var(--modal-border);
    padding: 0 20px;
    overflow-x: auto;
}
.am-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 11px 16px; font-size: 12px; font-weight: 600;
    color: var(--muted); border: none; background: none;
    border-bottom: 2px solid transparent;
    cursor: pointer; white-space: nowrap;
    transition: color .15s, border-color .15s;
    margin-bottom: -1px;
}
.am-tab:hover  { color: var(--text); }
.am-tab.active { color: #4f9cf9; border-bottom-color: #4f9cf9; }
.tab-badge {
    padding: 1px 7px; border-radius: 999px; font-size: 10px; font-weight: 700;
    background: rgba(79,156,249,.1); color: #7eb8fb; border: 1px solid rgba(79,156,249,.22);
}
.tab-badge-ai {
    background: rgba(168,85,247,.1); color: #c084fc; border-color: rgba(168,85,247,.25);
}

/* ── TAB PANELS ── */
.am-tab-panel { display: none; padding: 20px 22px; }
.am-tab-panel.active { display: block; }
.tab-panel-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 16px; gap: 12px; flex-wrap: wrap;
}
.tab-panel-title { font-size: 14px; font-weight: 700; color: var(--text); }
.tab-panel-sub   { font-size: 12px; color: var(--muted); margin-top: 3px; }
.tab-hint { font-size: 11px; color: var(--muted); margin-top: 14px; padding: 8px 12px; border-radius: 8px; background: rgba(255,255,255,.02); border: 1px solid var(--modal-border); }

/* ── OVERVIEW GRID ── */
.ov-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.ov-col  { display: flex; flex-direction: column; gap: 16px; }

.info-section {
    border: 1px solid var(--modal-border); border-radius: 12px;
    overflow: hidden;
}
.info-section-title {
    padding: 9px 14px; font-size: 10px; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase; color: var(--muted);
    background: rgba(0,0,0,.04); border-bottom: 1px solid var(--modal-border);
}
.info-grid { display: flex; flex-direction: column; }
.info-row {
    display: flex; align-items: baseline; justify-content: space-between;
    padding: 8px 14px; gap: 12px;
    border-bottom: 1px solid var(--modal-border);
    font-size: 13px;
}
.info-row:last-child { border-bottom: none; }
.info-lbl  { color: var(--muted); font-size: 12px; flex-shrink: 0; min-width: 110px; }
.info-val  { font-weight: 500; text-align: right; word-break: break-all; }
.info-muted { color: var(--muted); font-weight: 400; }
.info-mono  { font-family: var(--font-mono,monospace); font-size: 12px; }

/* protection grid */
.protection-grid {
    display: flex; flex-direction: column;
}
.prot-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 14px; border-bottom: 1px solid var(--modal-border);
    font-size: 12px; gap: 8px;
}
.prot-row:last-child { border-bottom: none; }
.prot-lbl { color: var(--muted); }
.prot-on  { color: var(--ok); font-weight: 600; }
.prot-off { color: var(--bad); font-weight: 600; }

/* risk score block */
.risk-score-block { padding: 12px 14px; }
.risk-score-nums {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.risk-num-item { text-align: center; }
.risk-num {
    font-size: 28px; font-weight: 800;
    font-family: var(--font-mono,monospace); line-height: 1;
    color: var(--text);
}
.risk-num-score { color: var(--warn); }
.risk-num-lbl { font-size: 10px; color: var(--muted); margin-top: 3px; }
.risk-num-sep { font-size: 20px; color: var(--muted); font-weight: 300; }

.desc-text { padding: 12px 14px; font-size: 13px; color: var(--muted); line-height: 1.55; }

.ov-actions {
    display: flex; gap: 8px; margin-top: 16px; padding-top: 14px;
    border-top: 1px solid var(--modal-border);
    flex-wrap: wrap;
}

/* ── CONTROLS list ── */
.controls-list { display: flex; flex-direction: column; gap: 8px; }
.control-row {
    border: 1px solid var(--modal-border); border-radius: 12px;
    background: rgba(255,255,255,.02); padding: 12px 14px;
    display: flex; flex-direction: column; gap: 8px;
}
.control-top   { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.control-code  {
    padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
    background: rgba(79,156,249,.1); border: 1px solid rgba(79,156,249,.22);
    color: #7eb8fb; font-family: var(--font-mono,monospace);
}
.status-pill { padding: 3px 9px; border-radius: 999px; font-size: 10px; font-weight: 700; border: 1px solid transparent; }
.st-gap     { background: rgba(251,113,133,.1); border-color: rgba(251,113,133,.28); color: var(--bad); }
.st-partial { background: rgba(251,191,36,.1);  border-color: rgba(251,191,36,.26);  color: var(--warn); }
.st-covered { background: rgba(45,212,191,.09); border-color: rgba(45,212,191,.22);  color: var(--ok); }
.control-left    { display: flex; flex-direction: column; gap: 5px; }
.control-title   { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.control-actions { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
.mini { font-size: 11px !important; padding: 4px 9px !important; border-radius: 7px !important; }

/* ── AI box ── */
.ai-label {
    display: inline-block; padding: 1px 7px; border-radius: 999px; font-size: 10px; font-weight: 700;
    background: rgba(168,85,247,.1); color: #c084fc; border: 1px solid rgba(168,85,247,.25);
    vertical-align: middle; margin-left: 4px;
}
.ai-box { display: flex; flex-direction: column; gap: 8px; }
.ai-item {
    border: 1px solid var(--modal-border); border-radius: 12px;
    background: rgba(255,255,255,.02); padding: 12px 14px;
}
.ai-item .top    { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }
.ai-item .title  { font-weight: 700; font-size: 13px; }
.ai-item .desc   { color: var(--muted); font-size: 12px; margin-top: 4px; line-height: 1.4; }
.ai-item .badg   { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; border: 1px solid var(--modal-border); background: rgba(255,255,255,.04); color: var(--muted); white-space: nowrap; }

/* ── Risk rows ── */
.am-risk-row { border: 1px solid var(--modal-border); border-radius: 10px; background: rgba(255,255,255,.02); padding: 10px 12px; margin-bottom: 8px; }
.am-risk-row:last-child { margin-bottom: 0; }
.am-risk-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 6px; }
.am-risk-id  { font-family: var(--font-mono,monospace); font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: rgba(251,113,133,.1); border: 1px solid rgba(251,113,133,.22); color: var(--bad); }
.am-treat-row { margin-top: 6px; padding: 8px 10px; border-radius: 8px; background: rgba(45,212,191,.04); border: 1px solid rgba(45,212,191,.12); font-size: 12px; }
.am-treat-id  { font-family: var(--font-mono,monospace); font-size: 10px; color: var(--ok); font-weight: 600; margin-right: 6px; }

/* ── Matrix (preserved) ── */
.matrix-wrap { display: grid; grid-template-columns: 20px 1fr; gap: 8px; align-items: stretch; }
.matrix-ylabel { writing-mode: vertical-rl; transform: rotate(180deg); color: var(--muted); font-size: 11px; display: flex; justify-content: center; }
.matrix { border: 1px solid var(--modal-border); border-radius: 10px; background: rgba(0,0,0,.1); padding: 10px; }
.matrix-grid { display: grid; grid-template-columns: 80px 1fr; gap: 8px; }
.matrix-rowlabels { display: grid; grid-template-rows: repeat(5,44px); gap: 4px; }
.matrix-rowlabels .lbl { display: flex; align-items: center; color: var(--muted); font-size: 10px; padding-left: 2px; }
.matrix-collabels { grid-column: 2/3; display: grid; grid-template-columns: repeat(5,1fr); gap: 4px; margin-top: -2px; }
.matrix-collabels .lbl { text-align: center; color: var(--muted); font-size: 10px; }
.matrix-cells { position: relative; grid-column: 2/3; display: grid; grid-template-columns: repeat(5,1fr); grid-template-rows: repeat(5,44px); gap: 4px; }
.mcell { position: relative; border-radius: 6px; border: 1px solid rgba(0,0,0,.2); display: flex; align-items: center; justify-content: center; text-align: center; padding: 4px; font-weight: 700; color: rgba(255,255,255,.92); overflow: hidden; }
.mcell small { display: block; font-size: 9px; font-weight: 600; opacity:.85; margin-bottom:1px; }
.mcell .score { font-size: 13px; font-weight: 900; }
.mcell.vlow  { background: #2b3447; } .mcell.low { background: #2e7d32; }
.mcell.med   { background: #f2b233; color: #1c1406; } .mcell.high { background: #d9534f; }
.mcell.vhigh { background: #8b1d3a; }
.marker { position: absolute; left: 50%; top: 50%; transform: translate(-50%,-50%); width: 26px; height: 26px; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 9px; color: #0b1220; background: rgba(255,255,255,.92); border: 2px solid rgba(96,165,250,.95); box-shadow: 0 4px 12px rgba(0,0,0,.4); pointer-events: none; }
.matrix-bottomlabel { grid-column: 2/3; text-align: center; color: var(--muted); font-size: 10px; margin-top: -4px; }

/* ── Edit modal ── */
.edit-two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding: 0 18px 18px; }
.field-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 10px; }
.field label { font-size: 11px; font-weight: 600; color: var(--muted); }
.panel { background: rgba(255,255,255,.02); border: 1px solid var(--modal-border); border-radius: 12px; padding: 14px; }

/* evidence */
.evidence-box { display: flex; flex-direction: column; gap: 8px; }
.evi { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 12px; border-radius: 9px; border: 1px solid var(--modal-border); background: rgba(255,255,255,.02); }

/* light mode */
:root[data-theme="light"] .search-input,
:root[data-theme="light"] .filter-select { background: #fff; }
:root[data-theme="light"] .assets-table thead th { background: rgba(0,0,0,.02); }
:root[data-theme="light"] .kpi-card { background: #fff; }
:root[data-theme="light"] .table-card { background: #fff; }
:root[data-theme="light"] .info-section { background: #fff; }
:root[data-theme="light"] .meta-type { color: #1a5fc8; }
:root[data-theme="light"] .control-code { color: #1a5fc8; }

@media (max-width: 700px) {
    .kpi-strip { grid-template-columns: 1fr 1fr; }
    .ov-grid   { grid-template-columns: 1fr; }
    .edit-two  { grid-template-columns: 1fr; }
}
</style>

<script>
window.APP_USER_ROLE = localStorage.getItem('mock_role') || 'GRC Manager';
</script>

@vite(['resources/js/pages/assets.js'])
@endsection
