@extends('layouts.app')
@section('title', 'Relatório Anual CNCS • Techbase GRC')

@push('styles')
<style>
/* ── Page layout ─────────────────────────────────────────────────────────── */
.cncs-root {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 16px;
    align-items: start;
}

/* ── Left panel: steps ───────────────────────────────────────────────────── */
.cncs-sidebar {
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: sticky;
    top: 16px;
}

.cncs-header {
    padding: 20px 22px 18px;
    border-radius: var(--radius);
    border: 1px solid var(--line);
    background: var(--panel);
}

.cncs-header h2 {
    margin: 0 0 4px;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: -.01em;
}

.cncs-header p {
    margin: 0;
    color: var(--muted);
    font-size: 12.5px;
    line-height: 1.5;
}

/* Step blocks */
.cncs-step {
    border-radius: var(--radius);
    border: 1px solid var(--line);
    background: var(--panel);
    overflow: hidden;
}

.cncs-step-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 16px;
    cursor: pointer;
    user-select: none;
    transition: background .12s;
}

.cncs-step-head:hover {
    background: rgba(255,255,255,.04);
}

.cncs-step-num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(96,165,250,.12);
    border: 1px solid rgba(96,165,250,.22);
    color: var(--info);
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-family: var(--font-mono);
}

.cncs-step-num.done {
    background: rgba(45,212,191,.12);
    border-color: rgba(45,212,191,.22);
    color: var(--ok);
}

.cncs-step-title {
    font-size: 13px;
    font-weight: 600;
    flex: 1;
}

.cncs-step-caret {
    color: var(--muted);
    transition: transform .18s;
    display: flex;
    align-items: center;
}

.cncs-step.open .cncs-step-caret {
    transform: rotate(180deg);
}

.cncs-step-body {
    display: none;
    padding: 0 16px 16px;
    flex-direction: column;
    gap: 12px;
}

.cncs-step.open .cncs-step-body {
    display: flex;
}

/* ── Field components ────────────────────────────────────────────────────── */
.field-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.field-group label {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--muted);
    letter-spacing: .03em;
    text-transform: uppercase;
}

.field-group input,
.field-group select,
.field-group textarea {
    background: var(--input-bg);
    border: 1px solid var(--input-border);
    color: var(--text);
    border-radius: 8px;
    padding: 8px 11px;
    font-size: 13px;
    font-family: var(--font);
    width: 100%;
    transition: border-color .15s, box-shadow .15s;
}

.field-group input:focus,
.field-group select:focus,
.field-group textarea:focus {
    outline: none;
    border-color: rgba(96,165,250,.4);
    box-shadow: 0 0 0 3px rgba(96,165,250,.08);
}

.field-group textarea {
    resize: vertical;
    min-height: 80px;
    line-height: 1.55;
}

.field-hint {
    font-size: 11px;
    color: var(--muted);
    line-height: 1.4;
}

.field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

/* ── Action bar ──────────────────────────────────────────────────────────── */
.cncs-actions {
    display: flex;
    gap: 8px;
}

.cncs-actions .btn {
    flex: 1;
    justify-content: center;
    gap: 7px;
}

/* ── Right panel: preview ────────────────────────────────────────────────── */
.cncs-preview {
    border-radius: var(--radius);
    border: 1px solid var(--line);
    background: var(--panel);
    overflow: hidden;
}

.cncs-preview-topbar {
    padding: 16px 22px;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.cncs-preview-title {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.cncs-preview-title h2 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
}

.cncs-preview-subtitle {
    font-size: 12px;
    color: var(--muted);
}

.kpi-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.kpi-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 8px;
    background: var(--chip);
    border: 1px solid var(--line);
    font-size: 12px;
    font-weight: 500;
}

.kpi-chip b {
    font-family: var(--font-mono);
    font-weight: 700;
}

.kpi-chip.warn { border-color: rgba(251,191,36,.22); background: rgba(251,191,36,.07); }
.kpi-chip.ok   { border-color: rgba(45,212,191,.22); background: rgba(45,212,191,.07); }
.kpi-chip.bad  { border-color: rgba(251,113,133,.22); background: rgba(251,113,133,.07); }

/* ── Preview body ────────────────────────────────────────────────────────── */
.cncs-preview-body {
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.pv-section {}

.pv-section-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 10px;
}

.pv-section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--line);
}

/* Inline stat boxes */
.pv-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 10px;
}

.pv-stat-box {
    border-radius: 10px;
    border: 1px solid var(--line);
    background: rgba(0,0,0,.12);
    padding: 12px 14px;
}

.pv-stat-box .stat-label {
    font-size: 10.5px;
    color: var(--muted);
    font-weight: 600;
    letter-spacing: .03em;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.pv-stat-box .stat-value {
    font-size: 22px;
    font-weight: 800;
    font-family: var(--font-mono);
    letter-spacing: -.02em;
    line-height: 1;
}

.pv-stat-box .stat-hint {
    font-size: 11px;
    color: var(--muted);
    margin-top: 4px;
    line-height: 1.4;
}

/* Quarter table */
.pv-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.pv-table th {
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--muted);
    padding: 0 10px 8px;
    border-bottom: 1px solid var(--line);
}

.pv-table th:first-child { padding-left: 0; }

.pv-table td {
    padding: 8px 10px;
    border-bottom: 1px solid rgba(255,255,255,.04);
    vertical-align: top;
}

.pv-table td:first-child { padding-left: 0; }

.pv-table tr:last-child td { border-bottom: none; }

.pv-table .q-label {
    font-family: var(--font-mono);
    font-weight: 700;
    color: var(--info);
    font-size: 12px;
}

.pv-table .q-count {
    font-family: var(--font-mono);
    font-weight: 700;
}

.pv-table .q-types {
    color: var(--muted);
    font-size: 12px;
}

/* Geo list */
.pv-geo-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.pv-geo-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 10px;
    border-radius: 8px;
    background: rgba(0,0,0,.12);
    border: 1px solid var(--line);
    font-size: 13px;
}

.pv-geo-item .geo-count {
    font-family: var(--font-mono);
    font-weight: 700;
    color: var(--info);
}

/* Cross border badge */
.pv-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}

.pv-badge.yes { background: rgba(251,191,36,.08); border: 1px solid rgba(251,191,36,.2); color: var(--warn); }
.pv-badge.no  { background: rgba(45,212,191,.08); border: 1px solid rgba(45,212,191,.2); color: var(--ok); }

/* Measures list */
.pv-measures {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.pv-measure {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    padding: 11px 13px;
    border-radius: 10px;
    border: 1px solid var(--line);
    background: rgba(0,0,0,.12);
}

.pv-measure-left {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}

.pv-measure-title {
    font-size: 13px;
    font-weight: 600;
}

.pv-measure-detail {
    font-size: 12px;
    color: var(--muted);
    line-height: 1.4;
}

.pv-measure-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 5px;
}

.pv-tag {
    padding: 2px 8px;
    border-radius: 5px;
    font-size: 11px;
    font-family: var(--font-mono);
    background: rgba(96,165,250,.10);
    border: 1px solid rgba(96,165,250,.18);
    color: var(--info);
}

.pv-measure-right {
    flex-shrink: 0;
}

/* Text preview blocks */
.pv-text-block {
    border-radius: 10px;
    border: 1px solid var(--line);
    background: rgba(0,0,0,.10);
    padding: 12px 14px;
}

.pv-text-block .tb-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--muted);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.pv-text-block .tb-content {
    font-size: 13px;
    line-height: 1.6;
    color: var(--text);
}

.pv-text-block .tb-content.empty {
    color: var(--muted);
    font-style: italic;
}

/* Section dividers within preview text */
.pv-two {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

/* Signature table preview */
.pv-sign-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
}

.pv-sign-box {
    border-radius: 8px;
    border: 1px solid var(--line);
    background: rgba(0,0,0,.10);
    padding: 10px 12px;
}

.pv-sign-box .sb-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--muted);
    margin-bottom: 4px;
}

.pv-sign-box .sb-value {
    font-size: 13px;
    font-weight: 500;
}

.pv-sign-box .sb-value.empty {
    color: var(--muted);
    font-style: italic;
}

/* Status tag (reutiliza o global mas garante aqui) */
.tag { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 7px; font-size: 11.5px; font-weight: 600; }
.tag .s { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.tag.ok   { background: rgba(45,212,191,.10); color: var(--ok); }
.tag.warn { background: rgba(251,191,36,.10);  color: var(--warn); }
.tag.bad  { background: rgba(251,113,133,.10); color: var(--bad); }

/* Loading state */
.pv-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 48px 22px;
    color: var(--muted);
    font-size: 13px;
}

.pv-spinner {
    width: 28px;
    height: 28px;
    border: 2px solid var(--line);
    border-top-color: var(--info);
    border-radius: 50%;
    animation: spin .7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 1100px) {
    .cncs-root { grid-template-columns: 1fr; }
    .cncs-sidebar { position: static; }
    .pv-sign-grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 640px) {
    .pv-two { grid-template-columns: 1fr; }
    .pv-sign-grid { grid-template-columns: 1fr; }
    .pv-stats-grid { grid-template-columns: 1fr 1fr; }
}
</style>
@endpush

@section('content')

<div class="cncs-root">

    {{-- ════════════════════════════════════════════
         COLUNA ESQUERDA — Parâmetros + secções manuais
    ══════════════════════════════════════════════ --}}
    <div class="cncs-sidebar">

        {{-- Cabeçalho --}}
        <div class="cncs-header">
            <h2>Relatório Anual CNCS</h2>
            <p>Modelo RF20 — gerado automaticamente a partir dos dados do sistema. Revisa e exporta em PDF.</p>
        </div>

        {{-- Step 1: Parâmetros base --}}
        <div class="cncs-step open" id="step1">
            <div class="cncs-step-head" data-toggle="step1">
                <span class="cncs-step-num">1</span>
                <span class="cncs-step-title">Parâmetros do relatório</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-group">
                    <label>Entidade</label>
                    <input id="cncsEntity" value="Clínica Exemplo" placeholder="Nome da entidade" />
                </div>

                <div class="field-row">
                    <div class="field-group">
                        <label>Ano civil</label>
                        <select id="cncsYear">
                            <option value="2026">2026</option>
                            <option value="2025" selected>2025</option>
                            <option value="2024">2024</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Período</label>
                        <input id="cncsPeriod" value="01/01 – 31/12" />
                    </div>
                </div>

                <div class="field-group">
                    <label>Escopo de incidentes</label>
                    <select id="cncsIncidentScope">
                        <option value="relevant">Apenas relevante / substancial</option>
                        <option value="all">Todos (inclui alertas convertidos)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Step 2: Secções editáveis --}}
        <div class="cncs-step open" id="step2">
            <div class="cncs-step-head" data-toggle="step2">
                <span class="cncs-step-num">2</span>
                <span class="cncs-step-title">Secções com edição manual</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <p class="field-hint">Auto-preenchidas com base nos dados do sistema. Edita antes de exportar.</p>

                <div class="field-group">
                    <label>3 — Atividades de segurança</label>
                    <textarea id="cncsManualActivities" rows="4" placeholder="Carregando dados..."></textarea>
                </div>

                <div class="field-group">
                    <label>6 — Recomendações de melhoria</label>
                    <textarea id="cncsManualRecs" rows="4" placeholder="Carregando dados..."></textarea>
                </div>

                <div class="field-group">
                    <label>8 — Outra informação relevante</label>
                    <textarea id="cncsExtra" rows="3" placeholder="Ex.: auditorias externas, mudanças de fornecedores…"></textarea>
                </div>
            </div>
        </div>

        {{-- Step 3: Dados de fecho --}}
        <div class="cncs-step" id="step3">
            <div class="cncs-step-head" data-toggle="step3">
                <span class="cncs-step-num">3</span>
                <span class="cncs-step-title">Assinatura e fecho</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-group">
                    <label>Data do relatório</label>
                    <input type="date" id="cncsReportDate" />
                </div>
                <div class="field-group">
                    <label>Responsável de segurança</label>
                    <input id="cncsSecurityOfficer" placeholder="Nome completo" />
                </div>
                <div class="field-group">
                    <label>Cargo / Função</label>
                    <input id="cncsSignature" placeholder="CISO, DPO, Responsável de segurança…" />
                </div>
            </div>
        </div>

        {{-- Step 4: Exportar --}}
        <div class="cncs-step" id="step4">
            <div class="cncs-step-head" data-toggle="step4">
                <span class="cncs-step-num">4</span>
                <span class="cncs-step-title">Exportar</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-row">
                    <div class="field-group">
                        <label>Formato</label>
                        <select id="cncsFormat">
                            <option value="pdf">PDF</option>
                            <option value="odt">ODT (backend)</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Guardar no sistema</label>
                        <select id="cncsSaveAsDoc">
                            <option value="yes">Sim (RF2)</option>
                            <option value="no">Não</option>
                        </select>
                    </div>
                </div>

                <div class="cncs-actions">
                    <button id="btnPreviewCNCS" class="btn">
                        <i data-lucide="refresh-cw" style="width:14px;height:14px"></i>
                        Atualizar prévia
                    </button>
                    <button id="btnExportCNCS" class="btn primary">
                        <i data-lucide="download" style="width:14px;height:14px"></i>
                        Exportar
                    </button>
                </div>

                <p class="field-hint">Ao exportar: cria documento "Relatório CNCS" e regista entrada de auditoria (RNF5).</p>
            </div>
        </div>

    </div>{{-- /cncs-sidebar --}}

    {{-- ════════════════════════════════════════════
         COLUNA DIREITA — Pré-visualização estruturada
    ══════════════════════════════════════════════ --}}
    <div class="cncs-preview">

        <div class="cncs-preview-topbar">
            <div class="cncs-preview-title">
                <h2>Pré-visualização</h2>
                <div class="cncs-preview-subtitle" id="pvSubtitle">Seleciona os parâmetros e clica em Atualizar prévia</div>
            </div>
            <div class="kpi-row">
                <span class="kpi-chip">
                    <i data-lucide="alert-triangle" style="width:13px;height:13px;color:var(--muted)"></i>
                    Incidentes: <b id="pvIncTotal">—</b>
                </span>
                <span class="kpi-chip warn">
                    <i data-lucide="shield-alert" style="width:13px;height:13px"></i>
                    Relev./Subst.: <b id="pvIncRelevant">—</b>
                </span>
                <span class="kpi-chip bad">
                    <i data-lucide="trending-up" style="width:13px;height:13px"></i>
                    Riscos altos: <b id="pvHighRisks">—</b>
                </span>
            </div>
        </div>

        <div class="cncs-preview-body" id="pvBody">

            {{-- Secção 1 + 2 --}}
            <div class="pv-section">
                <div class="pv-section-label">1 + 2 — Identificação</div>
                <div class="pv-two">
                    <div class="pv-text-block">
                        <div class="tb-label"><i data-lucide="building-2" style="width:11px;height:11px"></i> Entidade</div>
                        <div class="tb-content" id="pvEntity">—</div>
                    </div>
                    <div class="pv-text-block">
                        <div class="tb-label"><i data-lucide="calendar" style="width:11px;height:11px"></i> Período</div>
                        <div class="tb-content" id="pvPeriod">—</div>
                    </div>
                </div>
            </div>

            {{-- Secção 3 —  Atividades --}}
            <div class="pv-section">
                <div class="pv-section-label">3 — Atividades de segurança</div>
                <div class="pv-text-block">
                    <div class="tb-label"><i data-lucide="file-text" style="width:11px;height:11px"></i> Texto final</div>
                    <div class="tb-content" id="pvActivitiesText">—</div>
                </div>
            </div>

            {{-- Secção 4 — Estatística trimestral --}}
            <div class="pv-section">
                <div class="pv-section-label">4 — Estatística trimestral</div>
                <table class="pv-table">
                    <thead>
                        <tr>
                            <th>Trim.</th>
                            <th>Total</th>
                            <th>Tipos</th>
                        </tr>
                    </thead>
                    <tbody id="pvQuarterBody">
                        <tr>
                            <td colspan="3" class="muted" style="font-size:12px; padding: 10px 0">Nenhum dado. Clique em "Atualizar prévia".</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Secção 5 — Análise agregada --}}
            <div class="pv-section">
                <div class="pv-section-label">5 — Análise agregada (relevante / substancial)</div>

                <div class="pv-stats-grid" style="margin-bottom:10px">
                    <div class="pv-stat-box">
                        <div class="stat-label">Utilizadores afetados</div>
                        <div class="stat-value" id="pvUsersAffected">—</div>
                        <div class="stat-hint" id="pvUsersAffectedHint">—</div>
                    </div>
                    <div class="pv-stat-box">
                        <div class="stat-label">Duração agregada</div>
                        <div class="stat-value" id="pvDuration">—</div>
                        <div class="stat-hint" id="pvDurationHint">—</div>
                    </div>
                </div>

                <div class="pv-two">
                    <div>
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); margin-bottom:7px">Distribuição geográfica</div>
                        <div id="pvGeo" class="pv-geo-list"><div class="pv-geo-item"><span class="muted">—</span></div></div>
                    </div>
                    <div>
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); margin-bottom:7px">Impacto transfronteiriço</div>
                        <div id="pvCrossBorder">—</div>
                    </div>
                </div>
            </div>

            {{-- Secção 6 — Recomendações --}}
            <div class="pv-section">
                <div class="pv-section-label">6 — Recomendações de melhoria</div>
                <div class="pv-text-block">
                    <div class="tb-label"><i data-lucide="lightbulb" style="width:11px;height:11px"></i> Texto final</div>
                    <div class="tb-content" id="pvRecsText">—</div>
                </div>
            </div>

            {{-- Secção 7 — Medidas implementadas --}}
            <div class="pv-section">
                <div class="pv-section-label">7 — Problemas identificados e medidas implementadas</div>
                <div class="pv-measures" id="pvMeasures">
                    <div class="muted" style="font-size:12px">—</div>
                </div>
            </div>

            {{-- Secção 8 — Outra informação --}}
            <div class="pv-section">
                <div class="pv-section-label">8 — Outra informação relevante</div>
                <div class="pv-text-block">
                    <div class="tb-label"><i data-lucide="info" style="width:11px;height:11px"></i> Informação adicional</div>
                    <div class="tb-content" id="pvExtraText">—</div>
                </div>
            </div>

            {{-- Fecho / Assinatura --}}
            <div class="pv-section">
                <div class="pv-section-label">Fecho e assinatura</div>
                <div class="pv-sign-grid">
                    <div class="pv-sign-box">
                        <div class="sb-label">Data</div>
                        <div class="sb-value" id="pvSignDate">—</div>
                    </div>
                    <div class="pv-sign-box">
                        <div class="sb-label">Responsável</div>
                        <div class="sb-value" id="pvSignOfficer">—</div>
                    </div>
                    <div class="pv-sign-box">
                        <div class="sb-label">Cargo / Assinatura</div>
                        <div class="sb-value" id="pvSignRole">—</div>
                    </div>
                </div>
            </div>

            <p class="field-hint" style="text-align:center">
                Dados de demonstração (mock). Ao integrar com o backend, os valores serão preenchidos automaticamente.
            </p>

        </div>{{-- /cncs-preview-body --}}
    </div>{{-- /cncs-preview --}}

</div>{{-- /cncs-root --}}

{{-- pdfmake --}}
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/pdfmake.min.js"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/vfs_fonts.min.js"></script>

@vite(['resources/js/pages/reports-cncs.js'])
@endsection
