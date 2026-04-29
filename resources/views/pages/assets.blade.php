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
    @permission('assets.view')
        <button class="btn-ghost" type="button" id="btnSyncWazuh">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            Sync Wazuh
        </button>
        @endpermission
        @permission('assets.create')
        <button class="btn-primary" type="button" id="btnOpenCreateAsset">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Registar ativo
        </button>
        @endpermission
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     KPI STRIP
═══════════════════════════════════════════════════════════════ --}}
{{-- ── KPI strip ── --}}
    <div class="kpi-strip">
        <div class="kpi-card">
            <div class="kpi-label">Total de ativos</div>
            <div class="kpi-num" id="kpiTotal">0</div>
            <div class="kpi-sub">Inventário completo</div>
        </div>
        <div class="kpi-card kc-a">
            <div class="kpi-label">Monitorizados</div>
            <div class="kpi-num" id="kpiWazuh">0</div>
            <div class="kpi-sub">Sincronizados via Wazuh</div>
        </div>
        <div class="kpi-card kc-b">
            <div class="kpi-label">Risco Elevado</div>
            <div class="kpi-num" id="kpiHighRisk">0</div>
            <div class="kpi-sub">Ativos de criticidade alta/crítica</div>
        </div>
        <div class="kpi-card kc-w">
            <div class="kpi-label">Agentes Offline</div>
            <div class="kpi-num" id="kpiOffline">0</div>
            <div class="kpi-sub">Falta de comunicação com o SIEM</div>
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
<select class="filter-select" id="tagFilter">
                <option value="all">Tag (Todas)</option>
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
                        <th style="width: 28%;">Ativo</th>
                        <th>Tipo</th>
                        <th>Criticidade</th>
                        <th>Tags</th>
                        <th>Origem</th>
                        <th>Responsável</th>
                        <th>Risco</th>
                        <th style="width: 130px;">Estado (Wazuh)</th>
                        <th style="width: 100px; text-align:center;">Ações</th>
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
                @permission('assets.edit')
                <button class="btn-sm" type="button" id="btnEditAsset">✎ Editar</button>
                @endpermission
                <button class="btn-sm btn-close-modal" type="button" id="assetModalClose">✕</button>
            </div>
        </div>

        {{-- TABS --}}
        <div class="am-tabs">
            <button class="am-tab active" data-tab="overview">Visão Geral</button>
            @permission('risk.view')
            <button class="am-tab" data-tab="risk">Risco &amp; Tratamento</button>
            @endpermission
            <button class="am-tab" data-tab="ai">
                Análise de Postura
                <span class="tab-badge tab-badge-ai" style="display:inline-flex; align-items:center; gap:4px; margin-left:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path><path d="M5 3v4"></path><path d="M19 17v4"></path><path d="M3 5h4"></path><path d="M17 19h4"></path></svg>
                    IA
                </span>
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
                        <div class="info-section-title">Tags &amp; Classificação</div>
                        <div style="padding: 12px 14px; display:flex; flex-direction:column; gap:10px;">
                            {{-- Contentor onde as tags vão aparecer --}}
                            <div id="mTagsWrap" style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                                <div class="muted" style="font-size:12px;">A carregar tags...</div>
                            </div>
                            
{{-- Input para adicionar nova tag com Autocomplete --}}
                            @permission('assets.edit')
                            <div style="display:flex; gap:6px; margin-top:4px; overflow:visible !important;">
                                <div class="tag-autocomplete-wrapper">
                                    <input type="text" id="mNewTagInput" class="search-input" style="width:100%; padding:6px 10px; font-size:11px;" placeholder="Nova tag (ex: DMZ, PCI-DSS)..." autocomplete="off">
                                    <div id="mTagSuggestions" class="tag-suggestions-box"></div>
                                </div>
                                <button type="button" class="btn-ghost btn-sm" id="btnAddTagBtn">Adicionar</button>
                            </div>
                            @endpermission
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

                    <div class="info-section risk-intrinsic-card">

                        <div class="info-section-title">Risco Intrínseco</div>
                        {{-- Score de topo --}}
                        <div class="ri-score-hero">
                            <div class="ri-score-num" id="mScoreVal">—</div>
                            <div class="ri-score-label" id="mClassChip">—</div>
                            <div class="ri-score-sub">Probabilidade × Impacto</div>
                        </div>
                    
                        {{-- Selects editáveis --}}
                        @permission('assets.edit')
                        <div class="ri-selects">
                            <div class="ri-select-group">
                                <label class="ri-select-label">Probabilidade (1–5)</label>
                                <select class="ri-select" id="riskProbSelect">
                                    <option value="1">1 — Muito Baixo</option>
                                    <option value="2">2 — Baixo</option>
                                    <option value="3" selected>3 — Possível</option>
                                    <option value="4">4 — Provável</option>
                                    <option value="5">5 — Muito Provável</option>
                                </select>
                            </div>
                            <div class="ri-select-group">
                                <label class="ri-select-label">Impacto (1–5)</label>
                                <select class="ri-select" id="riskImpactSelect">
                                    <option value="1">1 — Insignificante</option>
                                    <option value="2">2 — Menor</option>
                                    <option value="3" selected>3 — Moderado</option>
                                    <option value="4">4 — Maior</option>
                                    <option value="5">5 — Catastrófico</option>
                                </select>
                            </div>
                        </div>
                    
                        {{-- Botão guardar (aparece só quando há alteração) --}}
                        <div id="riSaveWrap" style="display:none; padding: 0 16px 4px; text-align:right;">
                            <button type="button" class="btn-primary btn-sm" id="btnSaveRisk">Guardar risco</button>
                        </div>
                        @endpermission
                    
                        {{-- Matriz 5×5 --}}
                        <div class="ri-matrix-wrap">
                            <div class="ri-matrix-ylabel">← Probabilidade</div>
                            <div class="ri-matrix-inner">
                                <div class="ri-matrix-rowlabels" id="probLabels"></div>
                                <div class="ri-matrix-cells"     id="riskMatrix"></div>
                                <div class="ri-matrix-collabels" id="impactLabels"></div>
                                <div class="ri-matrix-xlabel">Impacto →</div>
                            </div>
                        </div>
                    
                    </div>

                </div>{{-- /ov-col right --}}
            </div>{{-- /ov-grid --}}

            {{-- quick actions footer --}}
            <div class="ov-actions">
                <button class="btn-ghost btn-sm" type="button" id="btnGoDocs">↗ Evidências</button>
                <button class="btn-ghost btn-sm" type="button" id="btnGoAssessments">↗ Avaliações</button>
                <button class="btn-ghost btn-sm" type="button" id="btnGoRisks">↗ Ver riscos</button>
            </div>
        </div>{{-- /tab-overview --}}

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
            <div id="assetRiskTreatList" style="display: flex; flex-direction: column; gap: 8px;"></div>
        </div>

        {{-- TAB: AI POSTURE ANALYSIS --}}
        <div class="am-tab-panel" id="tab-ai">
            <div class="tab-panel-header" style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <div class="tab-panel-title" style="display:flex; align-items:center; gap:8px;">
                        Análise de Postura de Segurança
                        <span class="tab-badge tab-badge-ai" style="display:inline-flex; align-items:center; gap:4px; font-size:10px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path><path d="M5 3v4"></path><path d="M19 17v4"></path><path d="M3 5h4"></path><path d="M17 19h4"></path></svg>
                            IA
                        </span>
                    </div>
                    <div class="tab-panel-sub">Avaliação de contexto gerada com base nos dados, configurações e riscos deste ativo.</div>
                </div>
                <button class="btn-primary btn-sm" type="button" id="btnGenerateAiAnalysis" style="display:inline-flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path><path d="M5 3v4"></path><path d="M19 17v4"></path><path d="M3 5h4"></path><path d="M17 19h4"></path></svg>
                    Gerar nova análise
                </button>
            </div>
            
            <div id="aiAnalysisHistory" style="display:flex; flex-direction:column; gap:16px; margin-top:20px;">
                <div class="muted" style="font-size:12px;text-align:center;padding:20px;">A carregar histórico de análises...</div>
            </div>
        </div>{{-- /tab-ai --}}

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
                            <option value="Servidor">Servidor</option>
                            <option value="Workstation">Workstation</option>
                            <option value="Aplicação">Aplicação</option>
                            <option value="Rede">Rede</option>
                            <option value="Cloud">Cloud</option>
                            <option value="Endpoint">Endpoint</option>
                        </select>
                    </div>
                </div>

                <div class="field-grid-2">
                    <div class="field">
                        <label>Criticidade</label>
                        <select id="fCrit">
                            <option value="critical">Crítico</option>
                            <option value="high">Alto</option>
                            <option value="medium">Médio</option>
                            <option value="low">Baixo</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Responsável</label>
                        <select id="fOwner">
                            <option value="">A carregar utilizadores...</option>
                        </select>
                    </div>
                </div>

                <div id="ipFieldWrap" class="field" style="display:none;">
                    <label>Endereço IP</label>
                    <input id="fIp" placeholder="ex.: 192.168.10.1" />
                </div>
                
                <div class="field">
                    <label>Tags (separadas por vírgula)</label>
                    <input id="fTags" class="search-input" placeholder="Ex.: Produção, PCI-DSS, Firewall" />
                    <div id="fTagsVisual" style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;"></div>
                </div>

                <div class="field-grid-2">
                    <div class="field">
                        <label>Probabilidade (1–5)</label>
                        <select id="fProb">
                            <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Impacto (1–5)</label>
                        <select id="fImpact">
                            <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option>
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
                {{-- 1. Escolha da Framework (Abas) --}}
                <div class="field" style="margin-bottom: 14px;">
                    <label>Framework</label>
                    <div class="fw-tabs" id="acFrameworkTabs">
                        <div class="muted" style="font-size:12px;">A carregar...</div>
                    </div>
                    <input type="hidden" id="acFramework" value="" />
                </div>

                {{-- 2. Escolha do Grupo --}}
                <div class="field" style="margin-bottom: 14px;">
                    <label>Grupo / Domínio</label>
                    <select id="acGroup">
                        <option>A carregar...</option>
                    </select>
                </div>

                {{-- 3. Escolha do Controlo --}}
                <div class="field" style="margin-bottom: 16px;">
                    <label>Controlo específico</label>
                    <select id="acControl">
                        <option>A carregar...</option>
                    </select>
                    <div style="display:flex;gap:8px;align-items:flex-start;margin-top:8px">
                        <span id="acControlInfo" class="ci" style="flex-shrink:0" data-tip="">i</span>
                        <div class="muted" id="acControlInfoText" style="font-size:12px;line-height:1.4;">—</div>
                    </div>
                </div>

                {{-- O resto mantém-se igual... --}}
                <div class="field">
                    <label>Status declarado</label>
                    <select id="acStatus">
                        <option value="GAP">GAP</option>
                        <option value="PARTIAL">PARTIAL</option>
                        <option value="COVERED">COVERED</option>
                    </select>
                </div>

                <div class="field">
                    <label>Justificação / Notas</label>
                    <textarea id="acNote" rows="3" placeholder="Ex.: Controlo coberto. Ver política anexada."></textarea>
                </div>

                <div style="display:flex;gap:8px;margin-top:16px;">
                    <button class="btn-primary" type="button" id="btnAddControlConfirm">Associar</button>
                    <button class="btn-ghost" type="button" id="btnAddControlCancel">Cancelar</button>
                </div>
            </div>

            <div class="panel">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h2 style="font-size:13px;font-weight:700;margin:0;">Evidências</h2>
                    <label class="btn-ghost btn-sm" style="cursor:pointer;">
                        + Upload
                        <input type="file" id="acUploadFile" style="display:none;" />
                    </label>
                </div>
                <div class="muted" style="font-size:12px;margin-bottom:12px;">Seleciona os documentos para ligar a este controlo. O Upload envia diretamente para o módulo de Documentos.</div>
                
                <div class="evidence-box" id="acEvidenceList" style="max-height: 250px; overflow-y: auto;">
                    <div class="muted" style="font-size:12px;">A carregar documentos...</div>
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

/* ─── KPI strip (Dashboard Moderno & Suave) ─── */
.kpi-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.kpi-card {
    background: rgba(255,255,255,.015); border: 1px solid var(--modal-border);
    border-radius: 10px; padding: 18px; position: relative; overflow: hidden;
    display: flex; flex-direction: column;
}
.kpi-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.kpi-label { font-size: 13px; font-weight: 600; color: var(--text); margin: 0; }
.kpi-icon {
    width: 32px; height: 32px; border-radius: 8px; display: grid; place-items: center; flex-shrink: 0;
}
.kpi-icon svg { width: 16px; height: 16px; stroke-width: 2px; }
.kpi-num { font-size: 26px; font-weight: 700; font-family: var(--font-mono, monospace); line-height: 1; margin-bottom: 6px; color: var(--text); }
.kpi-sub { font-size: 12px; color: var(--muted); }

/* Temas de Cor dos Ícones */
.kc-blue .kpi-icon { background: rgba(79,156,249,.1); color: #4f9cf9; border: 1px solid rgba(79,156,249,.2); }
.kc-green .kpi-icon { background: rgba(45,212,191,.1); color: var(--ok); border: 1px solid rgba(45,212,191,.2); }
.kc-red .kpi-icon { background: rgba(251,113,133,.1); color: var(--bad); border: 1px solid rgba(251,113,133,.2); }
.kc-orange .kpi-icon { background: rgba(251,146,60,.1); color: #fb923c; border: 1px solid rgba(251,146,60,.2); }

/* Ajuste no modo claro */
:root[data-theme="light"] .kpi-card { background: #ffffff; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
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
.table-toolbar .search-wrap {
    position: relative; flex: 1; min-width: 200px;
}
.table-toolbar .search-icon {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    color: var(--muted); pointer-events: none;
}
.table-toolbar .search-input {
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
.src-wazuh   { background: rgba(251,191,36,.09); border: 1px solid rgba(251,191,36,.22); color: var(--warn); }
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
    overflow: visible; /* não cortar o dropdown de sugestões de tags */
}
/* Repor overflow:hidden só nos que precisam (os que têm border-radius com conteúdo interno) */
.info-section > .info-grid,
.info-section > .protection-grid {
    border-radius: 0 0 12px 12px;
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

.modal-card {
    position: relative;
    z-index: 1;
}

.modal-overlay {
    z-index: 99999;
}

/* ── Tabs das Frameworks no Modal ── */
.fw-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
.fw-tab-btn {
    flex: 1; text-align: center; padding: 7px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
    border: 1px solid var(--modal-border); background: rgba(255,255,255,.02);
    color: var(--muted); cursor: pointer; transition: all .15s;
}
.fw-tab-btn[aria-pressed="true"] {
    background: rgba(79,156,249,.1); border-color: rgba(79,156,249,.3); color: #4f9cf9;
}

/* ── Histórico IA Collapsible ── */
details.ai-history-card > summary { list-style: none; }
details.ai-history-card > summary::-webkit-details-marker { display: none; }
details.ai-history-card[open] .ai-chevron { transform: rotate(180deg); }

.tag-autocomplete-wrapper { position: relative; flex: 1; }
                            .tag-suggestions-box {
                                display: none;
                                position: fixed; /* fixed escapa de qualquer overflow ancestral */
                                z-index: 999999;
                                max-height: 200px; overflow-y: auto;
                                background: #1e293b; border: 1px solid #334155; border-radius: 8px;
                                box-shadow: 0 10px 30px rgba(0,0,0,0.55);
                                min-width: 200px;
                            }
                            :root[data-theme="light"] .tag-suggestions-box {
                                background: #ffffff; border-color: #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.12);
                            }
                            .tag-sugg-item {
                                padding: 9px 14px; cursor: pointer; font-size: 12px;
                                border-bottom: 1px solid rgba(255,255,255,0.05);
                                display: flex; align-items: center; gap: 8px; transition: background 0.12s;
                            }
                            :root[data-theme="light"] .tag-sugg-item { border-bottom: 1px solid rgba(0,0,0,0.05); }
                            .tag-sugg-item:last-child { border-bottom: none; }
                            .tag-sugg-item:hover { background: rgba(79,156,249,0.15); }

                            /* Efeito de Vidro (Glassmorphism) no painel da IA */
tr[id^="ai-panel"] {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.btn-primary.btn-sm:hover {
    transform: translateY(-1px);
    filter: brightness(1.1);
}

/* Ajuste das cores dos KPIs no modal */
.am-akpi-btn:hover {
    background: rgba(255,255,255,0.02) !important;
}

.am-alerts-filters input, .am-alerts-filters select {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    padding: 8px 12px;
}


/* ── Card principal ── */
.risk-intrinsic-card { padding: 0 !important; overflow: hidden; }
 
/* ── Score hero ── */
.ri-score-hero {
    text-align: center;
    padding: 22px 20px 16px;
    background: var(--modal-bg, rgba(255,255,255,.02));
    border-bottom: 1px solid var(--modal-border);
}
.ri-score-num {
    font-size: 40px;
    font-weight: 800;
    line-height: 1;
    color: var(--warn);
}
.ri-score-label {
    font-size: 14px;
    font-weight: 700;
    color: var(--warn);
    margin-top: 2px;
    letter-spacing: .5px;
}
.ri-score-sub {
    font-size: 11px;
    color: var(--muted);
    margin-top: 4px;
}
 
/* Cores por nível */
.ri-score-hero[data-level="vlow"]  .ri-score-num,
.ri-score-hero[data-level="vlow"]  .ri-score-label { color: var(--muted); }
.ri-score-hero[data-level="low"]   .ri-score-num,
.ri-score-hero[data-level="low"]   .ri-score-label { color: var(--ok); }
.ri-score-hero[data-level="med"]   .ri-score-num,
.ri-score-hero[data-level="med"]   .ri-score-label { color: var(--warn); }
.ri-score-hero[data-level="high"]  .ri-score-num,
.ri-score-hero[data-level="high"]  .ri-score-label { color: var(--bad); }
.ri-score-hero[data-level="vhigh"] .ri-score-num,
.ri-score-hero[data-level="vhigh"] .ri-score-label { color: var(--bad); }
 
/* ── Selects ── */
.ri-selects {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding: 14px 16px 10px;
    border-bottom: 1px solid var(--modal-border);
}
.ri-select-group { display: flex; flex-direction: column; gap: 4px; }
.ri-select-label {
    font-size: 10px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .4px;
}
.ri-select {
    appearance: none;
    -webkit-appearance: none;
    background: rgba(255,255,255,.04) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%2394a3b8' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 10px center;
    border: 1px solid var(--modal-border);
    border-radius: 8px;
    padding: 8px 28px 8px 10px;
    font-size: 12px;
    color: var(--text);
    cursor: pointer;
    transition: border-color .15s;
}
.ri-select:hover { border-color: rgba(79,156,249,.4); }
.ri-select:focus { outline: none; border-color: #4f9cf9; }
 
:root[data-theme="light"] .ri-select {
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%2364748b' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
}
 
/* ── Matriz ── */
.ri-matrix-wrap {
    display: grid;
    grid-template-columns: 14px 1fr;
    gap: 6px;
    padding: 14px 12px 14px;
    align-items: center;
}
.ri-matrix-ylabel {
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    font-size: 10px;
    color: var(--muted);
    white-space: nowrap;
    text-align: center;
}
.ri-matrix-inner {
    display: grid;
    grid-template-columns: 72px 1fr;
    grid-template-rows: auto auto auto;
    gap: 4px;
}
.ri-matrix-rowlabels {
    grid-column: 1;
    grid-row: 1;
    display: grid;
    grid-template-rows: repeat(5, 36px);
    gap: 3px;
}
.ri-matrix-rowlabels .lbl {
    display: flex;
    align-items: center;
    font-size: 9px;
    color: var(--muted);
    padding-left: 2px;
    line-height: 1.2;
}
.ri-matrix-cells {
    grid-column: 2;
    grid-row: 1;
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    grid-template-rows:    repeat(5, 36px);
    gap: 3px;
}
.ri-matrix-collabels {
    grid-column: 2;
    grid-row: 2;
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 3px;
}
.ri-matrix-collabels .lbl {
    text-align: center;
    font-size: 9px;
    color: var(--muted);
    line-height: 1.2;
    padding-top: 2px;
}
.ri-matrix-xlabel {
    grid-column: 2;
    grid-row: 3;
    text-align: center;
    font-size: 10px;
    color: var(--muted);
    margin-top: 2px;
}
 
/* Células da matriz — design limpo igual ao mockup */
.mcell {
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    cursor: default;
    transition: filter .12s, transform .12s;
    position: relative;
}
.mcell:hover { filter: brightness(1.12); transform: scale(1.05); z-index: 1; }
 
/* Paleta suave (igual às imagens enviadas) */
.mcell.vlow  { background: #d1fae5; color: #065f46; }  /* verde muito claro */
.mcell.low   { background: #a7f3d0; color: #065f46; }  /* verde claro */
.mcell.med   { background: #fde68a; color: #78350f; }  /* amarelo */
.mcell.high  { background: #fed7aa; color: #7c2d12; }  /* laranja claro */
.mcell.vhigh { background: #fca5a5; color: #7f1d1d; }  /* vermelho claro */
 
:root[data-theme="dark"] .mcell.vlow  { background: #064e3b; color: #6ee7b7; }
:root[data-theme="dark"] .mcell.low   { background: #065f46; color: #6ee7b7; }
:root[data-theme="dark"] .mcell.med   { background: #78350f; color: #fde68a; }
:root[data-theme="dark"] .mcell.high  { background: #7c2d12; color: #fed7aa; }
:root[data-theme="dark"] .mcell.vhigh { background: #7f1d1d; color: #fca5a5; }
 
/* Marcador da célula activa */
.mcell.ri-active {
    outline: 2.5px solid var(--text);
    outline-offset: -2px;
    transform: scale(1.08);
    z-index: 2;
    box-shadow: 0 4px 14px rgba(0,0,0,.25);
}

</style>

<script>
window.APP_USER_ROLE = localStorage.getItem('mock_role') || 'GRC Manager';
</script>

@vite(['resources/js/pages/assets.js'])
@endsection