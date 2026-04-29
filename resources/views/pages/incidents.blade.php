@extends('layouts.app')
@section('title', 'Incidentes • Techbase GRC')

@push('styles')
<style>
/* ── Topbar da página ─────────────────────────────────────────────────── */
.inc-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}
.inc-page-header h2 {
    margin: 0 0 3px;
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 9px;
}
.inc-page-header h2 svg { color: #f87171; }
.inc-page-header p { margin: 0; font-size: 13px; color: var(--muted); }

/* ── KPI grid ──────────────────────────────────────────────────────────── */
.inc-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}
@media (max-width: 1100px) { .inc-kpi-grid { grid-template-columns: repeat(3,1fr); } }
@media (max-width: 700px)  { .inc-kpi-grid { grid-template-columns: repeat(2,1fr); } }

.inc-kpi-card {
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--card-bg);
    padding: 14px 16px 12px;
}
.inc-kpi-card .kpi-label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--muted);
    margin-bottom: 6px;
}
.inc-kpi-card .kpi-value {
    font-size: 28px;
    font-weight: 700;
    font-family: var(--font-mono);
    letter-spacing: -.5px;
    line-height: 1.1;
}
.inc-kpi-card.urgent {
    border-color: rgba(239,68,68,.28);
    background: rgba(239,68,68,.05);
}
.inc-kpi-card.urgent .kpi-label { color: #f87171; }

/* ── Toolbar de filtros ────────────────────────────────────────────────── */
.inc-toolbar {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    padding: 12px 16px;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--card-bg);
    margin-bottom: 14px;
}
.inc-toolbar select {
    max-width: 170px;
    padding: 8px 36px 8px 10px;
    font-size: 12px;
    border-radius: 10px;
}
.inc-toolbar .toolbar-right {
    margin-left: auto;
    display: flex;
    gap: 8px;
    align-items: center;
}
.inc-search {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(0,0,0,.18);
    min-width: 200px;
}
.inc-search input {
    all: unset;
    flex: 1;
    font-size: 12px;
    color: var(--text);
    width: 100%;
}
:root[data-theme="light"] .inc-search {
    background: rgba(255,255,255,.8);
    border-color: rgba(15,23,42,.15);
}

/* ── Tabela ────────────────────────────────────────────────────────────── */
.inc-table-wrap {
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--card-bg);
    overflow: hidden;
}
.inc-table-wrap table { font-size: 13px; }
.inc-table-wrap th {
    background: rgba(255,255,255,.02);
    padding: 10px 14px;
    font-size: 10px;
    letter-spacing: .07em;
    white-space: nowrap;
}
.inc-table-wrap td { padding: 11px 14px; vertical-align: middle; }
.inc-title-cell { max-width: 260px; }
.inc-title-cell .main { font-weight: 500; }
.inc-title-cell .sub  { font-size: 11px; color: var(--muted); margin-top: 2px; }
.inc-actions { display: flex; gap: 5px; justify-content: flex-end; flex-wrap: wrap; }

/* ── Badge urgente ─────────────────────────────────────────────────────── */
.badge-nis2 {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 7px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    font-family: var(--font-mono);
    background: rgba(239,68,68,.12);
    border: 1px solid rgba(239,68,68,.3);
    color: #f87171;
    margin-left: 6px;
    vertical-align: middle;
}

/* ── Estado vazio ──────────────────────────────────────────────────────── */
.inc-empty {
    text-align: center;
    padding: 48px 24px;
    color: var(--muted);
}
.inc-empty svg {
    width: 36px; height: 36px;
    opacity: .3;
    display: block;
    margin: 0 auto 12px;
}
.inc-empty p { margin: 0; font-size: 13px; }

/* ── Spinner ───────────────────────────────────────────────────────────── */
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin .9s linear infinite; display: inline-block; }

/* ── Modal ─────────────────────────────────────────────────────────────── */
.inc-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 600px) { .inc-modal-grid { grid-template-columns: 1fr; } }
.inc-modal-grid .span2 { grid-column: 1 / -1; }
.inc-section-divider {
    grid-column: 1 / -1;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--muted);
    padding-top: 4px;
    border-top: 1px solid var(--line);
    margin-top: 4px;
}

/* ── Toast ─────────────────────────────────────────────────────────────── */
#incToast {
    position: fixed;
    bottom: 24px; right: 24px;
    z-index: 99999;
    padding: 12px 18px;
    border-radius: 12px;
    background: rgba(18,26,43,.97);
    border: 1px solid rgba(255,255,255,.12);
    color: var(--text);
    font-size: 13px;
    box-shadow: 0 10px 30px rgba(0,0,0,.4);
    transition: opacity .35s, transform .35s;
    max-width: 360px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
#incToast.hidden { opacity: 0; transform: translateY(8px); pointer-events: none; }
#incToast .toast-icon { flex-shrink: 0; margin-top: 1px; }
:root[data-theme="light"] #incToast {
    background: rgba(255,255,255,.98);
    border-color: rgba(15,23,42,.12);
    box-shadow: 0 10px 30px rgba(15,23,42,.12);
}
</style>
@endpush

@section('content')

{{-- ══════════════════════════════════════════════════════════════
     TOPBAR DA PÁGINA
══════════════════════════════════════════════════════════════ --}}
<div class="inc-page-header">
    <div>
        <h2>
            <i data-lucide="shield-alert" style="width:20px;height:20px;color:#f87171;flex-shrink:0;"></i>
            Gestão de Incidentes
        </h2>
        <p>Regista, acompanha e fecha incidentes de segurança. Gera notificações 24h para o CNCS a partir daqui.</p>
    </div>
    <button id="btnNewIncident" class="btn" style="background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.30);color:#f87171;display:flex;align-items:center;gap:7px;">
        <i data-lucide="plus" style="width:15px;height:15px;"></i>
        Registar Novo Incidente
    </button>
</div>

{{-- ══════════════════════════════════════════════════════════════
     KPI CARDS
══════════════════════════════════════════════════════════════ --}}
<div class="inc-kpi-grid">
    <div class="inc-kpi-card">
        <div class="kpi-label">Abertos</div>
        <div class="kpi-value" id="kpiOpen" style="color:#f87171;">—</div>
    </div>
    <div class="inc-kpi-card">
        <div class="kpi-label">Contidos</div>
        <div class="kpi-value" id="kpiContained" style="color:#fbbf24;">—</div>
    </div>
    <div class="inc-kpi-card">
        <div class="kpi-label">Resolvidos</div>
        <div class="kpi-value" id="kpiResolved" style="color:#60a5fa;">—</div>
    </div>
    <div class="inc-kpi-card">
        <div class="kpi-label">Fechados</div>
        <div class="kpi-value" id="kpiClosed" style="color:var(--muted);">—</div>
    </div>
    <div class="inc-kpi-card urgent">
        <div class="kpi-label">Urgentes Ativos</div>
        <div class="kpi-value" id="kpiUrgent" style="color:#f87171;">—</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     TOOLBAR DE FILTROS
══════════════════════════════════════════════════════════════ --}}
<div class="inc-toolbar">
    <select id="incFilterStatus">
        <option value="">Todos os estados</option>
        <option value="open" selected>Abertos</option>
        <option value="contained">Contidos</option>
        <option value="resolved">Resolvidos</option>
        <option value="closed">Fechados</option>
    </select>

    <select id="incFilterType">
        <option value="">Todos os tipos</option>
        <option value="ransomware">Ransomware</option>
        <option value="malware">Malware</option>
        <option value="phishing">Phishing</option>
        <option value="ddos">DDoS</option>
        <option value="unauthorized_access">Acesso indevido</option>
        <option value="data_breach">Fuga de dados</option>
        <option value="service_disruption">Indisponibilidade</option>
        <option value="backup_failure">Falha de backup</option>
        <option value="other">Outro</option>
    </select>

    <select id="incFilterSeverity">
        <option value="">Todas as severidades</option>
        <option value="critical">🔴 Crítica</option>
        <option value="high">🟠 Alta</option>
        <option value="medium">🟡 Média</option>
        <option value="low">🟢 Baixa</option>
    </select>

    <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;margin:0;color:var(--muted);">
        <input type="checkbox" id="incFilterUrgent" style="width:auto;margin:0;"> Só urgentes
    </label>

    <div class="toolbar-right">
        <div class="inc-search">
            <i data-lucide="search" style="width:13px;height:13px;flex-shrink:0;opacity:.6;"></i>
            <input id="incSearch" placeholder="Pesquisar…" />
        </div>
        <button id="btnIncRefresh" class="btn small" style="display:flex;align-items:center;gap:6px;">
            <i data-lucide="refresh-cw" style="width:13px;height:13px;"></i>
            Atualizar
        </button>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     TABELA DE INCIDENTES
══════════════════════════════════════════════════════════════ --}}
<div class="inc-table-wrap">
    <div style="overflow-x:auto;">
        <table id="incidentTable">
            <thead>
                <tr>
                    <th style="width:52px;">#</th>
                    <th>Incidente</th>
                    <th>Tipo</th>
                    <th>Severidade</th>
                    <th>Estado</th>
                    <th>Detetado em</th>
                    <th>Duração</th>
                    <th style="text-align:center;">Relatórios</th>
                    <th style="text-align:right;padding-right:18px;">Ações</th>
                </tr>
            </thead>
            <tbody id="incidentTableBody">
                <tr>
                    <td colspan="9">
                        <div class="inc-empty">
                            <i data-lucide="loader" class="spin" style="width:24px;height:24px;opacity:.5;display:block;margin:0 auto 10px;"></i>
                            <p>A carregar incidentes…</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════════
     MODAL — REGISTAR / EDITAR INCIDENTE
══════════════════════════════════════════════════════════════ --}}
<div id="modalIncident" class="modal-overlay is-hidden" role="dialog" aria-modal="true" aria-labelledby="modalIncTitle">
    <div class="modal-card" style="width:min(680px,96vw);">

        {{-- Header --}}
        <div class="modal-header" style="padding-bottom:14px;border-bottom:1px solid var(--line);margin-bottom:18px;">
            <div>
                <h3 id="modalIncTitle" style="margin:0 0 3px;font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;">
                    <i data-lucide="shield-alert" style="width:16px;height:16px;color:#f87171;flex-shrink:0;"></i>
                    <span id="modalIncTitleText">Registar Novo Incidente</span>
                </h3>
                <p style="margin:0;font-size:12px;color:var(--muted);">Preenche os campos conhecidos agora. Podes editar mais tarde.</p>
            </div>
            <button id="modalIncClose" class="btn" style="padding:5px;display:flex;align-items:center;">
                <i data-lucide="x" style="width:17px;height:17px;"></i>
            </button>
        </div>

        <input type="hidden" id="modalIncId">

        <div class="inc-modal-grid">

            {{-- Título --}}
            <div class="span2">
                <label>Título do incidente <span style="color:#f87171;">*</span></label>
                <input id="incTitle" placeholder="Ex.: Ransomware no servidor de ficheiros" />
            </div>

            {{-- Urgente --}}
            <div class="span2" style="margin:-4px 0 4px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0;font-size:13px;">
                    <input type="checkbox" id="incUrgent" style="width:auto;margin:0;">
                    <span style="color:#f87171;font-weight:600;">⚠ Incidente grave / relevante (Art. 23.º NIS2)</span>
                </label>
            </div>

            {{-- Tipo + Severidade --}}
            <div>
                <label>Tipo de incidente</label>
                <select id="incType">
                    <option value="">— Selecionar —</option>
                    <option value="ransomware">Ransomware</option>
                    <option value="malware">Malware</option>
                    <option value="phishing">Phishing</option>
                    <option value="ddos">DDoS</option>
                    <option value="unauthorized_access">Acesso indevido</option>
                    <option value="data_breach">Fuga de dados</option>
                    <option value="service_disruption">Indisponibilidade de serviço</option>
                    <option value="backup_failure">Falha de backup</option>
                    <option value="other">Outro</option>
                </select>
            </div>
            <div>
                <label>Severidade</label>
                <select id="incSeverity">
                    <option value="">— Selecionar —</option>
                    <option value="critical">🔴 Crítica</option>
                    <option value="high">🟠 Alta</option>
                    <option value="medium">🟡 Média</option>
                    <option value="low">🟢 Baixa</option>
                </select>
            </div>

            {{-- Datas --}}
            <div>
                <label>Data / hora de deteção</label>
                <input type="datetime-local" id="incDetectedAt" />
            </div>
            <div>
                <label>Início estimado do incidente</label>
                <input type="datetime-local" id="incStartedAt" />
            </div>

            {{-- Descrição --}}
            <div class="span2">
                <label>Descrição inicial</label>
                <textarea id="incDescription" rows="3" placeholder="O que aconteceu? Qual o impacto inicial observado?"></textarea>
            </div>

            <div class="inc-section-divider">Contexto técnico</div>

            {{-- Sistemas + Utilizadores --}}
            <div>
                <label>Sistemas / serviços afetados</label>
                <textarea id="incAffectedSystems" rows="2" placeholder="Ex.: servidor-01, Active Directory, backup offsite…"></textarea>
            </div>
            <div>
                <label>Utilizadores afetados (estimativa)</label>
                <input id="incAffectedUsers" placeholder="Ex.: ~200 utilizadores internos" />
                <p style="margin:5px 0 0;font-size:11px;color:var(--muted);">Número ou descrição.</p>
            </div>

            {{-- Vetor + Dados pessoais --}}
            <div>
                <label>Vetor de ataque</label>
                <select id="incAttackVector">
                    <option value="">— Desconhecido —</option>
                    <option value="email_phishing">Email / Phishing</option>
                    <option value="vulnerability_exploit">Exploração de vulnerabilidade</option>
                    <option value="credential_stuffing">Roubo / força bruta de credenciais</option>
                    <option value="supply_chain">Cadeia de fornecimento</option>
                    <option value="insider_threat">Ameaça interna</option>
                    <option value="physical">Acesso físico</option>
                    <option value="unknown">Desconhecido</option>
                    <option value="other">Outro</option>
                </select>
            </div>
            <div>
                <label>Dados pessoais envolvidos?</label>
                <select id="incPersonalData">
                    <option value="">— Desconhecido —</option>
                    <option value="yes_confirmed">Sim — confirmado</option>
                    <option value="yes_suspected">Sim — suspeito</option>
                    <option value="no">Não</option>
                    <option value="unknown">Desconhecido</option>
                </select>
            </div>

        </div>{{-- /inc-modal-grid --}}

        {{-- Footer do modal --}}
        <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:16px;margin-top:16px;border-top:1px solid var(--line);">
            <button id="modalIncCancel" class="btn">Cancelar</button>
            <button id="modalIncSave" class="btn" style="background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.30);color:#f87171;min-width:160px;display:flex;align-items:center;justify-content:center;gap:7px;">
                <i data-lucide="save" style="width:14px;height:14px;"></i>
                <span id="modalIncSaveLabel">Registar Incidente</span>
            </button>
        </div>
    </div>
</div>

{{-- ── Toast ─────────────────────────────────────────────────────────────── --}}
<div id="incToast" class="hidden" role="status" aria-live="polite">
    <span class="toast-icon" id="incToastIcon"></span>
    <span id="incToastMsg"></span>
</div>

@endsection


@push('scripts')
<script>
// =============================================================================
// incidents.js  (inline — mover para resources/js/pages/incidents.js quando
//                estiveres a usar Vite)
// =============================================================================
(function () {
    'use strict';

    // ── Estado ────────────────────────────────────────────────────────────────
    let _incidents = [];
    let _editingId = null;
    let _searchTimer = null;

    // ── DOM helpers ───────────────────────────────────────────────────────────
    const $  = (s) => document.querySelector(s);
    const el = (id) => document.getElementById(id);

    function fmtDateTime(val) {
        if (!val) return '—';
        const d = new Date(val);
        return isNaN(d.getTime())
            ? val
            : d.toLocaleString('pt-PT', { dateStyle: 'short', timeStyle: 'short' });
    }

    function severityChip(sev) {
        const map = {
            critical: ['bad',  '🔴 Crítica'],
            high:     ['warn', '🟠 Alta'],
            medium:   ['',     '🟡 Média'],
            low:      ['ok',   '🟢 Baixa'],
        };
        const [cls, label] = map[sev] ?? ['', '—'];
        return `<span class="chip ${cls}" style="font-size:11px;padding:3px 9px;">${label}</span>`;
    }

    function statusTag(status) {
        const map = {
            open:      ['bad',  'Aberto'],
            contained: ['warn', 'Contido'],
            resolved:  ['ok',   'Resolvido'],
            closed:    ['',     'Fechado'],
        };
        const [cls, label] = map[status] ?? ['', status];
        return `<span class="tag ${cls}"><span class="s"></span>${label}</span>`;
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    }

    // ── API helper ────────────────────────────────────────────────────────────
    async function api(method, path, body) {
        const opts = {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        };
        if (body) opts.body = JSON.stringify(body);
        const res = await fetch('/api/' + path, opts);
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message ?? `HTTP ${res.status}`);
        }
        return res.json();
    }

    // ── Carregar lista ────────────────────────────────────────────────────────
    async function loadIncidents() {
        const tbody = el('incidentTableBody');
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="9">
            <div class="inc-empty">
                <i data-lucide="loader" class="spin" style="width:22px;height:22px;opacity:.5;display:block;margin:0 auto 10px;"></i>
                <p>A carregar…</p>
            </div></td></tr>`;
        if (window.lucide) window.lucide.createIcons();

        const params = new URLSearchParams();
        const status   = el('incFilterStatus')?.value;
        const type     = el('incFilterType')?.value;
        const severity = el('incFilterSeverity')?.value;
        const urgent   = el('incFilterUrgent')?.checked;
        const search   = el('incSearch')?.value?.trim();

        if (status)   params.set('status',   status);
        if (type)     params.set('type',     type);
        if (severity) params.set('severity', severity);
        if (urgent)   params.set('urgent',   '1');
        if (search)   params.set('search',   search);

        try {
            const data = await api('GET', 'incidents?' + params.toString());
            _incidents = data.data ?? [];
            renderTable(_incidents);
            renderKpis(data.counts ?? {});
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="9">
                <div class="inc-empty">
                    <i data-lucide="alert-triangle" style="width:28px;height:28px;opacity:.5;display:block;margin:0 auto 10px;color:var(--bad);"></i>
                    <p style="color:var(--bad);">Erro ao carregar: ${e.message}</p>
                </div></td></tr>`;
            if (window.lucide) window.lucide.createIcons();
        }
    }

    // ── KPIs ──────────────────────────────────────────────────────────────────
    function renderKpis(counts) {
        const set = (id, val) => { const e = el(id); if (e) e.textContent = val ?? '—'; };
        set('kpiOpen',      counts.open      ?? '—');
        set('kpiContained', counts.contained ?? '—');
        set('kpiResolved',  counts.resolved  ?? '—');
        set('kpiClosed',    counts.closed    ?? '—');
        set('kpiUrgent',    counts.urgent_active ?? '—');
    }

    // ── Render tabela ─────────────────────────────────────────────────────────
    function renderTable(incidents) {
        const tbody = el('incidentTableBody');
        if (!tbody) return;

        if (!incidents.length) {
            tbody.innerHTML = `<tr><td colspan="9">
                <div class="inc-empty">
                    <i data-lucide="inbox" style="width:36px;height:36px;display:block;margin:0 auto 10px;opacity:.25;"></i>
                    <p>Nenhum incidente encontrado com estes filtros.</p>
                </div></td></tr>`;
            if (window.lucide) window.lucide.createIcons();
            return;
        }

        tbody.innerHTML = incidents.map(i => {
            const urgentBadge = i.is_urgent
                ? `<span class="badge-nis2">⚠ NIS2</span>`
                : '';
            const duration = i.duration_days !== null && i.duration_days !== undefined
                ? (i.duration_days === 0 ? '&lt;1d' : `${i.duration_days}d`)
                : '—';
            const reportBadge = i.report_count > 0
                ? `<a href="{{ route('relatorios-cncs') }}" class="chip" style="font-size:11px;cursor:pointer;">${i.report_count}</a>`
                : `<span style="color:var(--muted);font-size:11px;">0</span>`;

            // Acções dinâmicas consoante estado
            let actions = '';
            if (i.status === 'open') {
                actions += `<button class="btn small" onclick="incAction('contain',${i.id})" style="font-size:11px;padding:4px 9px;color:var(--warn);border-color:rgba(251,191,36,.3);">Conter</button>`;
            }
            if (i.status === 'contained') {
                actions += `<button class="btn small ok" onclick="incAction('resolve',${i.id})" style="font-size:11px;padding:4px 9px;">Resolver</button>`;
            }
            if (i.status === 'resolved') {
                actions += `<button class="btn small" onclick="incAction('close',${i.id})" style="font-size:11px;padding:4px 9px;">Fechar</button>`;
            }
            if (i.status === 'closed') {
                actions += `<button class="btn small" onclick="incAction('reopen',${i.id})" style="font-size:11px;padding:4px 9px;color:var(--muted);">Reabrir</button>`;
            }
            if (['open','contained'].includes(i.status)) {
                actions += `<a href="{{ route('relatorios-cncs') }}?from_incident=${i.id}" class="btn small" style="font-size:11px;padding:4px 9px;background:rgba(239,68,68,.10);border-color:rgba(239,68,68,.28);color:#f87171;" title="Gerar Notificação 24h">
                    <i data-lucide="alarm-clock" style="width:11px;height:11px;"></i> 24h
                </a>`;
            }
            if (i.status !== 'closed') {
                actions += `<button class="btn small" onclick="editInc(${i.id})" style="font-size:11px;padding:4px 8px;" title="Editar">
                    <i data-lucide="pencil" style="width:11px;height:11px;"></i>
                </button>`;
            }

            return `<tr style="transition:background .12s;">
                <td style="font-family:var(--font-mono);font-size:11px;color:var(--muted);">#${i.id}</td>
                <td class="inc-title-cell">
                    <div class="main">${i.title}${urgentBadge}</div>
                    ${i.reporter ? `<div class="sub">${i.reporter}</div>` : ''}
                </td>
                <td style="font-size:12px;">${i.type_label ?? '—'}</td>
                <td>${i.severity ? severityChip(i.severity) : '<span style="color:var(--muted);font-size:12px;">—</span>'}</td>
                <td>${statusTag(i.status)}</td>
                <td style="font-size:12px;white-space:nowrap;">${fmtDateTime(i.detected_at)}</td>
                <td style="font-family:var(--font-mono);font-size:12px;">${duration}</td>
                <td style="text-align:center;">${reportBadge}</td>
                <td><div class="inc-actions">${actions}</div></td>
            </tr>`;
        }).join('');

        if (window.lucide) window.lucide.createIcons();
    }

    // ── Transições de estado ──────────────────────────────────────────────────
    window.incAction = async function (action, id) {
        const labels = { contain: 'conter', resolve: 'resolver', close: 'fechar', reopen: 'reabrir' };
        if (!confirm(`Tens a certeza que queres ${labels[action] ?? action} este incidente?`)) return;
        try {
            await api('POST', `incidents/${id}/${action}`);
            toast(`✓ Incidente ${labels[action] ?? action}d com sucesso.`, 'ok');
            await loadIncidents();
        } catch (e) {
            toast(`Erro: ${e.message}`, 'bad');
        }
    };

    // ── Modal: abrir novo ─────────────────────────────────────────────────────
    function openNew() {
        _editingId = null;
        el('modalIncId').value = '';
        el('modalIncTitleText').textContent  = 'Registar Novo Incidente';
        el('modalIncSaveLabel').textContent  = 'Registar Incidente';
        clearForm();
        // Pré-preenche data de deteção com agora
        el('incDetectedAt').value = new Date().toISOString().slice(0, 16);
        openModal();
    }

    // ── Modal: abrir editar ───────────────────────────────────────────────────
    window.editInc = async function (id) {
        try {
            const i = await api('GET', `incidents/${id}`);
            _editingId = id;
            el('modalIncId').value = id;
            el('modalIncTitleText').textContent = 'Editar Incidente';
            el('modalIncSaveLabel').textContent = 'Guardar Alterações';

            const set = (eid, val) => { const e = el(eid); if (e) e.value = val ?? ''; };
            set('incTitle',           i.title);
            set('incType',            i.incident_type ?? '');
            set('incSeverity',        i.severity ?? '');
            set('incDescription',     i.description ?? '');
            set('incAffectedSystems', i.affected_systems ?? '');
            set('incAffectedUsers',   i.affected_users ?? '');
            set('incAttackVector',    i.attack_vector ?? '');
            set('incPersonalData',    i.personal_data ?? '');
            el('incUrgent').checked = !!i.is_urgent;

            if (i.detected_at) set('incDetectedAt', new Date(i.detected_at).toISOString().slice(0,16));
            if (i.started_at)  set('incStartedAt',  new Date(i.started_at).toISOString().slice(0,16));

            openModal();
        } catch (e) {
            toast('Erro ao carregar incidente: ' + e.message, 'bad');
        }
    };

    // ── Modal: guardar ────────────────────────────────────────────────────────
    async function saveInc() {
        const title = el('incTitle')?.value?.trim();
        if (!title) {
            el('incTitle').focus();
            el('incTitle').style.borderColor = 'var(--bad)';
            setTimeout(() => { el('incTitle').style.borderColor = ''; }, 2000);
            return;
        }

        const payload = {
            title,
            incident_type:    el('incType')?.value      || null,
            severity:         el('incSeverity')?.value  || null,
            is_urgent:        el('incUrgent')?.checked  ?? false,
            detected_at:      el('incDetectedAt')?.value || null,
            started_at:       el('incStartedAt')?.value  || null,
            description:      el('incDescription')?.value?.trim()     || null,
            affected_systems: el('incAffectedSystems')?.value?.trim() || null,
            affected_users:   el('incAffectedUsers')?.value?.trim()   || null,
            attack_vector:    el('incAttackVector')?.value            || null,
            personal_data:    el('incPersonalData')?.value            || null,
        };

        const saveBtn = el('modalIncSave');
        if (saveBtn) { saveBtn.disabled = true; el('modalIncSaveLabel').textContent = 'A guardar…'; }

try {
            if (_editingId) {
                await api('PUT',  `incidents/${_editingId}`, payload);
                toast('✓ Incidente actualizado.', 'ok');
                closeModal();
                await loadIncidents();
            } else {
                // 1. Criar o incidente e capturar a resposta
                const res = await api('POST', 'incidents', payload);
                toast('✓ Incidente registado. A preparar relatório...', 'ok');
                
                // 2. O SALTO: Se criou com sucesso, redireciona para o Relatório 24h
                if (res && res.id_incident) {
                    setTimeout(() => {
                        window.location.href = `{{ route('relatorios-cncs') }}?from_incident=${res.id_incident}`;
                    }, 1000);
                    return; // Para não fechar o modal nem recarregar a lista aqui
                }
                
                closeModal();
                await loadIncidents();
            }
        } catch (e) {
            toast('Erro ao guardar: ' + e.message, 'bad');
            if (saveBtn) { 
                saveBtn.disabled = false; 
                el('modalIncSaveLabel').textContent = 'Registar Incidente'; 
            }
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                el('modalIncSaveLabel').textContent = _editingId ? 'Guardar Alterações' : 'Registar Incidente';
            }
        }
    }

    // ── Modal helpers ─────────────────────────────────────────────────────────
    function openModal() {
        const m = el('modalIncident');
        if (m) {
            m.classList.remove('is-hidden');
            setTimeout(() => el('incTitle')?.focus(), 80);
            if (window.lucide) window.lucide.createIcons();
        }
    }

    function closeModal() {
        el('modalIncident')?.classList.add('is-hidden');
        _editingId = null;
    }

    function clearForm() {
        ['incTitle','incDescription','incAffectedSystems','incAffectedUsers','incDetectedAt','incStartedAt'].forEach(id => {
            const e = el(id); if (e) e.value = '';
        });
        ['incType','incSeverity','incAttackVector','incPersonalData'].forEach(id => {
            const e = el(id); if (e) e.value = '';
        });
        el('incUrgent').checked = false;
    }

    // ── Toast ─────────────────────────────────────────────────────────────────
    function toast(msg, type = 'ok') {
        const t    = el('incToast');
        const icon = el('incToastIcon');
        const txt  = el('incToastMsg');
        if (!t) return;

        const icons = { ok: '✓', bad: '✕', warn: '⚠' };
        const colors = { ok: 'var(--ok)', bad: 'var(--bad)', warn: 'var(--warn)' };
        if (icon) { icon.textContent = icons[type] ?? '·'; icon.style.color = colors[type] ?? 'var(--text)'; }
        if (txt) txt.textContent = msg;
        t.classList.remove('hidden');
        clearTimeout(t._timer);
        t._timer = setTimeout(() => t.classList.add('hidden'), 4200);
    }

    // ── Pesquisa com debounce ─────────────────────────────────────────────────
    function onSearchInput() {
        clearTimeout(_searchTimer);
        _searchTimer = setTimeout(loadIncidents, 350);
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    function init() {
        // Botão novo incidente
        el('btnNewIncident')?.addEventListener('click', openNew);
        el('btnIncRefresh')?.addEventListener('click',  loadIncidents);

        // Filtros
        ['incFilterStatus','incFilterType','incFilterSeverity','incFilterUrgent'].forEach(id => {
            el(id)?.addEventListener('change', loadIncidents);
        });
        el('incSearch')?.addEventListener('input', onSearchInput);

        // Modal — fechar
        el('modalIncClose')?.addEventListener('click',  closeModal);
        el('modalIncCancel')?.addEventListener('click', closeModal);
        el('modalIncident')?.addEventListener('click',  (e) => {
            if (e.target === el('modalIncident')) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // Modal — guardar
        el('modalIncSave')?.addEventListener('click', saveInc);

        // Primeiro load
        loadIncidents();
    }

    document.addEventListener('DOMContentLoaded', init);
})();
</script>
@endpush