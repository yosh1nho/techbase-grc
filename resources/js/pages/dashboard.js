const $ = (s) => document.querySelector(s);
const $$ = (s) => Array.from(document.querySelectorAll(s));

const ROUTES = window.__TB_ROUTES__ || {};

// ================== STORE (alertas continuam em localStorage) ==================
const Store = {
    getAlerts: () => { try { return JSON.parse(localStorage.getItem('tb_alerts') || '[]'); } catch { return []; } },
    setAlerts: (v) => localStorage.setItem('tb_alerts', JSON.stringify(v)),
};

// ================== ESTADO GLOBAL (vem da API) ==================
// Preenchido por loadDashboardData() — lido pelas funções de render e nextActions
let API = {
    risks:      null,   // { total, high, medium, low, top_risk }
    treatments: null,   // { total, done, doing, todo, overdue, overdue_list }
    compliance: null,   // { frameworks, nis2, qnrcs }
};

// ================== ALERTS (mantêm-se directo do Acronis) ==================
let ALERTS = [];

const ALERT_TYPE_LABELS = {
    BackupFailed:       'Backup falhou',
    BackupSuccessful:   'Backup OK',
    UpdateApplied:      'Update aplicado',
    QuotaWarning:       'Quota a esgotar',
    AgentOffline:       'Agente offline',
    MalwareDetected:    'Malware detetado',
    RansomwareBehavior: 'Ransomware bloqueado',
    UserLoggedIn:       'Login admin',
};

function normalizeSeverity(sev) {
    const map = { critical: 'critical', high: 'high', warning: 'medium', medium: 'medium', low: 'low', info: 'low' };
    return map[sev] || 'low';
}

async function loadAcronisAlerts() {
    try {
        const res = await fetch('http://127.0.0.1:9999/api/alert_manager/v1/alerts', {
            headers: { Authorization: 'Bearer acronis_fake_jwt_token_998877' }
        });
        const data = await res.json();
        ALERTS = data.items
            .filter(a => a.severity !== 'info')
            .map(a => ({
                id:    a.id,
                ts:    new Date(a.createdAt).toISOString().slice(0, 16).replace('T', ' '),
                sev:   normalizeSeverity(a.severity),
                asset: a.details.resourceName,
                cat:   a.type,
                msg:   a.details.message,
            }));
        Store.setAlerts(ALERTS);
    } catch (e) {
        console.warn('Acronis offline — a usar alertas em cache:', e.message);
        ALERTS = Store.getAlerts();
    }
}

// ================== API DASHBOARD ==================
async function loadDashboardData() {
    try {
        const res = await fetch('/api/dashboard');
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        API.risks      = data.risks;
        API.treatments = data.treatments;
        API.compliance = data.compliance;
    } catch (e) {
        console.error('Erro ao carregar dados do dashboard:', e);
    }
}

// ================== RENDER: ALERTAS ==================
function renderAlertCardBreakdown() {
    const total    = ALERTS.length;
    const critical = ALERTS.filter(a => a.sev === 'critical').length;
    const medium   = ALERTS.filter(a => a.sev === 'medium').length;
    const low      = ALERTS.filter(a => a.sev === 'low' || a.sev === 'high').length;

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('alertCount', total || '—');

    const subEl = document.getElementById('alertSub');
    if (subEl) subEl.textContent = total > 0
        ? `${critical} crítico${critical !== 1 ? 's' : ''} • ${total - critical} outros`
        : 'Sem alertas activos';

    set('alertCritCount', critical);
    set('alertMedCount',  medium);
    set('alertLowCount',  low);

    if (total > 0) {
        const pct = v => `${Math.round(v / total * 100)}%`;
        const bC = document.getElementById('alertBarCrit');
        const bO = document.getElementById('alertBarOther');
        if (bC) bC.style.width = pct(critical);
        if (bO) bO.style.width = pct(total - critical);
    }
}

// ================== RENDER: RISCOS ==================
function renderRiskStats() {
    const d = API.risks;
    if (!d) return;

    const total  = d.total;
    const high   = d.high;
    const medium = d.medium;
    const low    = d.low;

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('riskCount',    total || '0');
    set('riskHighCount', high);
    set('riskMedCount',  medium);
    set('riskLowCount',  low);

    if (total > 0) {
        const pct = v => `${Math.round(v / total * 100)}%`;
        const bH = document.getElementById('riskBarHigh');
        const bM = document.getElementById('riskBarMed');
        const bL = document.getElementById('riskBarLow');
        if (bH) bH.style.width = pct(high);
        if (bM) bM.style.width = pct(medium);
        if (bL) bL.style.width = pct(low);
    }

    // Top risco em destaque
    const topRisk = d.top_risk;
    const topEl   = document.getElementById('riskTopItem');
    if (topEl && topRisk) {
        document.getElementById('riskTopTitle').textContent = topRisk.title || `Risco #${topRisk.id}`;
        document.getElementById('riskTopMeta').textContent  = `Score ${topRisk.score} • ${topRisk.asset || topRisk.owner || '—'}`;
        topEl.style.display = 'block';
    }
}

// ================== RENDER: PLANOS DE TRATAMENTO ==================
function renderTreatmentStats() {
    const d = API.treatments;
    if (!d) return;

    const total   = d.total;
    const done    = d.done;
    const doing   = d.doing;
    const todo    = d.todo;
    const overdue = d.overdue;

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('treatmentOverdueCount', overdue);
    set('treatmentOverdueLabel', overdue > 0 ? 'em atraso' : 'tudo em dia');
    set('treatCountDone',    done);
    set('treatCountDoing',   doing);
    set('treatCountOverdue', overdue);

    if (total > 0) {
        const pct  = v => `${Math.round(v / total * 100)}%`;
        const setW = (id, v) => { const el = document.getElementById(id); if (el) el.style.width = pct(v); };
        setW('treatBarDone',    done);
        setW('treatBarDoing',   doing);
        setW('treatBarTodo',    todo);
        setW('treatBarOverdue', overdue);
    }

    const big = document.getElementById('treatmentOverdueCount');
    if (big) big.style.color = overdue > 0 ? '#f87171' : '#34d399';
}

// ================== RENDER: COMPLIANCE (dois mini-cards) ==================
function renderComplianceCards() {
    const d = API.compliance;
    if (!d) return;

    ['nis2', 'qnrcs'].forEach(key => {
        const fw = d[key];
        if (!fw) return;

        const prefix = key; // 'nis2' ou 'qnrcs'

        const pctEl  = document.getElementById(`${prefix}Pct`);
        const barEl  = document.getElementById(`${prefix}Bar`);
        const cEl    = document.getElementById(`${prefix}Compliant`);
        const pEl    = document.getElementById(`${prefix}Partial`);
        const nEl    = document.getElementById(`${prefix}NonCompliant`);
        const totEl  = document.getElementById(`${prefix}Total`);

        const pct = fw.compliance_pct_weighted;

        if (pctEl)  pctEl.textContent  = `${pct}%`;
        if (totEl)  totEl.textContent  = fw.total_controls;
        if (cEl)    cEl.textContent    = fw.compliant;
        if (pEl)    pEl.textContent    = fw.partial;
        if (nEl)    nEl.textContent    = fw.non_compliant;

        // Barra de progresso circular ou linear — usando a barra linear existente
        if (barEl) {
            barEl.style.width = `${pct}%`;
            // Cor: verde se ≥70%, amarelo se ≥40%, vermelho se <40%
            barEl.style.background = pct >= 70 ? '#34d399' : pct >= 40 ? '#fbbf24' : '#f87171';
        }
    });
}

// ================== PRÓXIMAS ACÇÕES (cruza API + alertas) ==================
function buildNextActions() {
    const actions = [];

    // 1. Alertas críticos
    const criticalAlerts = ALERTS.filter(a => a.sev === 'critical');
    if (criticalAlerts.length > 0) {
        const byType = {};
        criticalAlerts.forEach(a => { byType[a.cat] = (byType[a.cat] || 0) + 1; });
        const [worstCat, worstCount] = Object.entries(byType).sort((a, b) => b[1] - a[1])[0];
        const label = ALERT_TYPE_LABELS[worstCat] || worstCat;
        actions.push({
            priority: 1, urgency: 'critical',
            title: `Investigar ${worstCount}× "${label}"`,
            desc: `${criticalAlerts.length} alertas críticos activos. Confirma se há incidente em curso e regista no módulo de Riscos.`,
            controls: [],
            link: ROUTES.risks || '/riscos', linkLabel: 'Ver Riscos',
        });
    }

    // 2. Planos em atraso (dados reais da API)
    const overdue = API.treatments?.overdue ?? 0;
    if (overdue > 0) {
        const overdueList = API.treatments?.overdue_list ?? [];
        const sample = overdueList.slice(0, 2).map(p => p.risk_title).filter(Boolean).join(' • ');
        actions.push({
            priority: 2, urgency: 'high',
            title: `${overdue} plano(s) de tratamento em atraso`,
            desc: sample ? `Prazos ultrapassados: ${sample}.` : 'Prazos ultrapassados — requerem actualização ou escalamento.',
            controls: [],
            link: ROUTES.treatment || '/tratamento', linkLabel: 'Ver Planos',
        });
    }

    // 3. Riscos de score alto (dados reais da API)
    const highRisks = API.risks?.high ?? 0;
    if (highRisks > 0 && !actions.find(a => a.urgency === 'critical')) {
        const topRisk = API.risks?.top_risk;
        actions.push({
            priority: 3, urgency: 'high',
            title: `${highRisks} risco(s) de score alto sem plano activo`,
            desc: topRisk
                ? `Risco mais crítico: "${topRisk.title}" (score ${topRisk.score}) em ${topRisk.asset || '—'}.`
                : `${highRisks} riscos com score ≥17 identificados.`,
            controls: [],
            link: ROUTES.risks || '/riscos', linkLabel: 'Ver Riscos',
        });
    }

    // 4. Compliance baixa (dados reais da API)
    const qnrcsPct = API.compliance?.qnrcs?.compliance_pct_weighted ?? null;
    const nis2Pct  = API.compliance?.nis2?.compliance_pct_weighted  ?? null;
    if (qnrcsPct !== null && qnrcsPct < 50) {
        actions.push({
            priority: 4, urgency: 'medium',
            title: `Conformidade QNRCS abaixo de 50% (${qnrcsPct}%)`,
            desc: 'Existem controlos por avaliar ou não conformes. Revê os grupos com mais lacunas.',
            controls: [],
            link: ROUTES.compliance || '/compliance', linkLabel: 'Ver Compliance',
        });
    } else if (nis2Pct !== null && nis2Pct < 50) {
        actions.push({
            priority: 4, urgency: 'medium',
            title: `Conformidade NIS2 abaixo de 50% (${nis2Pct}%)`,
            desc: 'Existem artigos NIS2 por avaliar ou não conformes. Revê os capítulos com mais lacunas.',
            controls: [],
            link: ROUTES.compliance || '/compliance', linkLabel: 'Ver Compliance',
        });
    }

    // 5. Agentes offline (Acronis)
    const offline = ALERTS.filter(a => a.cat === 'AgentOffline');
    if (offline.length > 0) {
        const assets = [...new Set(offline.map(a => a.asset))].slice(0, 3).join(', ');
        actions.push({
            priority: 5, urgency: 'medium',
            title: `${offline.length} endpoint(s) sem heartbeat`,
            desc: `Agentes Acronis offline: ${assets}.`,
            controls: [],
            link: ROUTES.assets || '/ativos', linkLabel: 'Ver Ativos',
        });
    }

    // Fallback
    if (actions.length === 0) {
        actions.push({
            priority: 1, urgency: 'ok',
            title: 'Tudo em ordem',
            desc: 'Nenhuma acção urgente identificada. Mantém revisões periódicas de conformidade.',
            controls: [], link: null, linkLabel: null,
        });
    }

    return actions.slice(0, 4);
}

function renderNextActions() {
    const container = document.getElementById('nextActionsContainer');
    if (!container) return;

    const actions = buildNextActions();
    const urgencyConfig = {
        critical: { cls: 'bad',  label: 'Crítico',  icon: '🔴' },
        high:     { cls: 'warn', label: 'Alto',      icon: '🟠' },
        medium:   { cls: 'warn', label: 'Médio',     icon: '🟡' },
        ok:       { cls: 'ok',   label: 'OK',        icon: '🟢' },
    };

    container.innerHTML = actions.map((a, i) => {
        const u   = urgencyConfig[a.urgency] || urgencyConfig.medium;
        const btn = a.link
            ? `<a href="${a.link}" class="btn primary" style="font-size:12px;padding:5px 12px;text-decoration:none">${a.linkLabel} →</a>`
            : '';
        return `
        <div class="panel next-action-item" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="chip ${u.cls}" style="font-size:11px">${u.icon} P${i + 1} · ${u.label}</span>
                </div>
                <b style="font-size:14px">${a.title}</b>
                <p class="muted" style="margin:4px 0 0;font-size:12px;line-height:1.5">${a.desc}</p>
            </div>
            <div style="display:flex;align-items:center">${btn}</div>
        </div>`;
    }).join('');
}

// ================== HELPERS ==================
function normalize(s) { return (s || '').toLowerCase().trim(); }

function parseAlertTs(ts) {
    if (!ts) return null;
    const d = new Date(ts.replace(' ', 'T') + ':00');
    return Number.isNaN(d.getTime()) ? null : d;
}

function sevChip(sev) {
    const map = {
        critical: `<span class="tag bad"><span class="s"></span> crítica</span>`,
        high:     `<span class="tag warn"><span class="s"></span> alta</span>`,
        medium:   `<span class="tag"><span class="s"></span> média</span>`,
        low:      `<span class="tag ok"><span class="s"></span> baixa</span>`,
    };
    return map[sev] || `<span class="tag"><span class="s"></span> —</span>`;
}

// ================== MODAL BASE ==================
function openModal(id)  {
    const m = $(id); if (!m) return;
    m.classList.remove('is-hidden');
    m.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    const m = $(id); if (!m) return;
    m.classList.add('is-hidden');
    m.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

// ================== ALERTS MODAL ==================
const ALERT_ICONS = {
    BackupFailed:       '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><line x1="2" y1="2" x2="22" y2="22"/></svg>',
    BackupSuccessful:   '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
    UpdateApplied:      '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg>',
    QuotaWarning:       '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    AgentOffline:       '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.56 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>',
    MalwareDetected:    '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    RansomwareBehavior: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    UserLoggedIn:       '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
};
const ALERT_ICON_DEFAULT = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

function updateAlertModalKpis() {
    const total    = ALERTS.length;
    const critical = ALERTS.filter(a => a.sev === 'critical').length;
    const medium   = ALERTS.filter(a => a.sev === 'medium').length;
    const low      = ALERTS.filter(a => a.sev === 'low' || a.sev === 'high').length;
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('akpiTotal', total); set('akpiCritical', critical);
    set('akpiMedium', medium); set('akpiLow', low);
}

function renderAlertsTable() {
    const container = document.getElementById('alertTbody');
    if (!container) return;

    const q       = normalize($('#alertSearch')?.value);
    const sev     = $('#alertSeverity')?.value || 'all';
    const fromRaw = $('#alertDateFrom')?.value;
    const toRaw   = $('#alertDateTo')?.value;
    const from    = fromRaw ? new Date(`${fromRaw}T00:00:00`) : null;
    const to      = toRaw   ? new Date(`${toRaw}T23:59:59`)   : null;

    const rows = ALERTS.filter(a => {
        const aDate = parseAlertTs(a.ts);
        return (!q    || normalize(a.msg).includes(q) || normalize(a.asset).includes(q) || normalize(a.cat).includes(q))
            && (sev === 'all' || a.sev === sev)
            && (!from || (aDate && aDate >= from))
            && (!to   || (aDate && aDate <= to));
    });

    updateAlertModalKpis();

    const rcEl = document.getElementById('alertResultCount');
    if (rcEl) rcEl.textContent = `${rows.length} alerta${rows.length !== 1 ? 's' : ''}`;

    container.innerHTML = '';
    if (!rows.length) {
        container.innerHTML = `<div style="padding:32px 0;text-align:center;color:var(--muted);font-size:13px">Nenhum alerta com os filtros aplicados.</div>`;
        return;
    }

    const sevStyles = {
        critical: { iconBg: 'rgba(248,113,113,.12)', dot: '#f87171', label: 'Crítico' },
        high:     { iconBg: 'rgba(251,146,60,.12)',  dot: '#fb923c', label: 'Alto'    },
        medium:   { iconBg: 'rgba(251,191,36,.1)',   dot: '#fbbf24', label: 'Médio'   },
        low:      { iconBg: 'rgba(52,211,153,.1)',   dot: '#34d399', label: 'Baixo'   },
    };

    rows.forEach(a => {
        const s    = sevStyles[a.sev] || sevStyles.low;
        const icon = ALERT_ICONS[a.cat] || ALERT_ICON_DEFAULT;
        const lbl  = ALERT_TYPE_LABELS[a.cat] || a.cat;
        const card = document.createElement('div');
        card.className = 'am-alert-card';
        card.innerHTML = `
            <div class="am-alert-icon" style="background:${s.iconBg};color:${s.dot}">${icon}</div>
            <div class="am-alert-body">
                <div class="am-alert-top">
                    <span class="am-alert-sev" style="background:${s.iconBg};color:${s.dot}">${s.label}</span>
                    <span class="am-alert-cat">${lbl}</span>
                    <span class="am-alert-ts">${a.ts}</span>
                </div>
                <div class="am-alert-asset">${a.asset}</div>
                <div class="am-alert-msg">${a.msg}</div>
            </div>
            <div class="am-alert-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg></div>`;
        card.addEventListener('click', () => {
            window.location.href = `${ROUTES.risks || '/riscos'}?from=acronis&alert_id=${a.id}#acronis`;
        });
        container.appendChild(card);
    });
}

// ================== INIT ==================
async function init() {
    // Fetch paralelo — não bloqueia um ao outro
    await Promise.all([
        loadAcronisAlerts(),
        loadDashboardData(),
    ]);

    // Render todos os cards
    renderAlertCardBreakdown();
    renderRiskStats();
    renderTreatmentStats();
    renderComplianceCards();
    renderNextActions();

    // Alerts modal
    renderAlertsTable();

    // Event listeners — alertas modal
    const openAlerts = $('[data-open-alerts]');
    openAlerts?.addEventListener('click', () => { openModal('#alertsModal'); renderAlertsTable(); });
    openAlerts?.addEventListener('keydown', e => { if (e.key === 'Enter') openAlerts.click(); });

    $('#alertsModalClose')?.addEventListener('click', () => closeModal('#alertsModal'));
    $('#alertsModal')?.addEventListener('click', e => { if (e.target.id === 'alertsModal') closeModal('#alertsModal'); });

    // Filtros de severidade nos KPI chips
    $$('[data-sev-filter]').forEach(btn => {
        btn.addEventListener('click', () => {
            const sev = btn.dataset.sevFilter;
            const sel = $('#alertSeverity');
            if (sel) sel.value = sev;
            $$('[data-sev-filter]').forEach(b => b.setAttribute('aria-pressed', 'false'));
            btn.setAttribute('aria-pressed', 'true');
            renderAlertsTable();
        });
    });

    $('#alertSeverity')?.addEventListener('change', () => {
        const val = $('#alertSeverity').value;
        $$('[data-sev-filter]').forEach(b => b.setAttribute('aria-pressed', b.dataset.sevFilter === val ? 'true' : 'false'));
        renderAlertsTable();
    });

    $('#alertSearch')?.addEventListener('input',  renderAlertsTable);
    $('#alertDateFrom')?.addEventListener('change', renderAlertsTable);
    $('#alertDateTo')?.addEventListener('change',   renderAlertsTable);

    // ESC fecha modal
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (!$('#alertsModal')?.classList.contains('is-hidden')) closeModal('#alertsModal');
    });
}

document.addEventListener('DOMContentLoaded', init);
