const $ = (s) => document.querySelector(s);
const $$ = (s) => Array.from(document.querySelectorAll(s));

const ROUTES = window.__TB_ROUTES__ || {};

// ================== MOCK DATA ==================
const MATURITY_ITEMS = [
    {
        id: 'A1', type: 'asset', name: 'SRV-DB-01', subtitle: 'PostgreSQL • Produção',
        status: 'PARTIAL', summary: 'Inventário ok, evidências incompletas.',
        controls: [
            { key: 'ID.GA-1', desc: 'Inventário de ativos', status: 'PARTIAL', next: 'Anexar evidência periódica' },
            { key: 'ID.AM-2', desc: 'Gestão de alterações', status: 'GAP', next: 'Definir processo + registos' },
            { key: 'PR.IP-4', desc: 'Backups e testes', status: 'COVERED', next: 'Manter relatórios mensais' },
        ]
    },
    {
        id: 'A2', type: 'asset', name: 'FW-EDGE', subtitle: 'Firewall • Perímetro',
        status: 'GAP', summary: 'Falta documentação e evidências.',
        controls: [
            { key: 'ID.GA-1', desc: 'Inventário de ativos', status: 'COVERED', next: 'Ok' },
            { key: 'PR.AC-1', desc: 'Controlo de acessos', status: 'GAP', next: 'Rever perfis e RBAC' },
            { key: 'DE.CM-1', desc: 'Monitorização', status: 'PARTIAL', next: 'Aumentar cobertura de logs' },
        ]
    },
    {
        id: 'P1', type: 'policy', name: 'Procedimento Inventário v1.0', subtitle: 'PDF • hash: 9f2a…e11',
        status: 'COVERED', summary: 'Bem documentado e aprovado.',
        controls: [
            { key: 'ID.GA-1', desc: 'Inventário de ativos', status: 'COVERED', next: 'Manter revisão anual' },
            { key: 'ID.GV-1', desc: 'Governança', status: 'COVERED', next: 'Ok' },
        ]
    },
    {
        id: 'P2', type: 'policy', name: 'Política de Backups v0.9', subtitle: 'Draft • pendente',
        status: 'PARTIAL', summary: 'Faltam testes e RPO/RTO formalizados.',
        controls: [
            { key: 'PR.IP-4', desc: 'Backups e testes', status: 'PARTIAL', next: 'Adicionar testes + evidência' },
            { key: 'PR.PT-1', desc: 'Continuidade', status: 'GAP', next: 'Definir RTO/RPO e aprovar' },
        ]
    },
];

// ================== STORE (ponto 6: source of truth central) ==================
// Em vez de cada página fazer localStorage.getItem("tb_mock_risks") diretamente,
// usamos funções centralizadas. Quando migrares para store.js como módulo ES,
// basta trocar estas funções pelo import { store } from './store.js'.
const Store = {
    getAlerts:     () => { try { return JSON.parse(localStorage.getItem('tb_alerts') || '[]'); } catch { return []; } },
    setAlerts:     (v) => localStorage.setItem('tb_alerts', JSON.stringify(v)),
    getRisks:      () => { try { return JSON.parse(localStorage.getItem('tb_mock_risks') || '[]'); } catch { return []; } },
    getTreatments: () => { try { return JSON.parse(localStorage.getItem('tb_mock_treatments') || '[]'); } catch { return []; } },
};

// ================== ALERTS (Ponto 2 + Ponto 6) ==================
let ALERTS = [];

// Labels legíveis para o utilizador
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
    const map = { critical: "critical", high: "high", warning: "medium", medium: "medium", low: "low", info: "low" };
    return map[sev] || "low";
}

// Card de alertas — barra visual + 3 contadores, sem chips de categoria
function renderAlertCardBreakdown() {
    const total    = ALERTS.length;
    const critical = ALERTS.filter(a => a.sev === 'critical').length;
    const medium   = ALERTS.filter(a => a.sev === 'medium').length;
    const low      = ALERTS.filter(a => a.sev === 'low' || a.sev === 'high').length;

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

    // número grande e sub-título
    set('alertCount', total || '—');
    const subEl = document.getElementById('alertSub');
    if (subEl) subEl.textContent = total > 0
        ? `${critical} crítico${critical !== 1 ? 's' : ''} • ${total - critical} outros`
        : 'Sem alertas activos';

    // contadores no card
    set('alertCritCount', critical);
    set('alertMedCount',  medium);
    set('alertLowCount',  low);

    // mini barra proporcional
    if (total > 0) {
        const pct = v => `${Math.round(v / total * 100)}%`;
        const bC = document.getElementById('alertBarCrit');
        const bO = document.getElementById('alertBarOther');
        if (bC) bC.style.width = pct(critical);
        if (bO) bO.style.width = pct(total - critical);
    }
}

async function loadAcronisAlerts() {
    try {
        const res = await fetch("http://127.0.0.1:9999/api/alert_manager/v1/alerts", {
            headers: { Authorization: "Bearer acronis_fake_jwt_token_998877" }
        });
        const data = await res.json();
        ALERTS = data.items
            .filter(a => a.severity !== "info")
            .map(a => ({
                id:    a.id,
                ts:    new Date(a.createdAt).toISOString().slice(0, 16).replace("T", " "),
                sev:   normalizeSeverity(a.severity),
                asset: a.details.resourceName,
                cat:   a.type,
                msg:   a.details.message
            }));
        Store.setAlerts(ALERTS); // ponto 6: grava no store central
    } catch (e) {
        console.error("Erro ao carregar alertas:", e);
        // fallback: tenta carregar do store se o servidor estiver down
        ALERTS = Store.getAlerts();
    }
}

//========= Carregar Riscos — barra visual + top risco ===========
function loadRiskStats() {
    const risks = Store.getRisks();
    const total = risks.length;
    let high = 0, medium = 0, low = 0;
    let topRisk = null;

    risks.forEach(r => {
        const score = r.score || (r.prob * r.impact);
        if (score >= 17) { high++; if (!topRisk || score > (topRisk.score || topRisk.prob * topRisk.impact)) topRisk = r; }
        else if (score >= 10) medium++;
        else low++;
    });

    // número total
    const countEl = document.getElementById('riskCount');
    if (countEl) countEl.textContent = total || '0';

    // contagens individuais
    ['High','Med','Low'].forEach((k,i) => {
        const el = document.getElementById(`risk${k}Count`);
        if (el) el.textContent = [high, medium, low][i];
    });

    // barra proporcional
    if (total > 0) {
        const pct = v => `${Math.round(v / total * 100)}%`;
        const bH = document.getElementById('riskBarHigh');
        const bM = document.getElementById('riskBarMed');
        const bL = document.getElementById('riskBarLow');
        if (bH) bH.style.width = pct(high);
        if (bM) bM.style.width = pct(medium);
        if (bL) bL.style.width = pct(low);
    }

    // top risco em destaque — usa id se não tiver título
    const topEl = document.getElementById('riskTopItem');
    if (topEl && topRisk) {
        const score = topRisk.score || (topRisk.prob * topRisk.impact);
        // prioridade: title → name → id (abreviado) → fallback
        const displayName = topRisk.title || topRisk.name
            || (topRisk.id ? `Risco #${String(topRisk.id).slice(0, 8)}` : 'Risco sem ID');
        document.getElementById('riskTopTitle').textContent = displayName;
        document.getElementById('riskTopMeta').textContent  = `Score ${score} • ${topRisk.asset || topRisk.owner || '—'}`;
        topEl.style.display = 'block';
    }
}


//=========Carregar Planos de Tratamento — barra de progresso ===========
function loadTreatmentStats() {
    const plans = Store.getTreatments();
    const total   = plans.length;
    const overdue = plans.filter(p => p.status === "Em atraso").length;
    const doing   = plans.filter(p => p.status === "Em curso").length;
    const todo    = plans.filter(p => p.status === "To do").length;
    const done    = plans.filter(p => p.status === "Concluído").length;

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set("treatmentOverdueCount", overdue);
    set("treatmentOverdueLabel", overdue > 0 ? "em atraso" : "tudo em dia");
    set("treatCountDone",    done);
    set("treatCountDoing",   doing);
    set("treatCountOverdue", overdue);

    if (total > 0) {
        const pct = v => `${Math.round(v / total * 100)}%`;
        const setW = (id, v) => { const el = document.getElementById(id); if (el) el.style.width = pct(v); };
        setW("treatBarDone",    done);
        setW("treatBarDoing",   doing);
        setW("treatBarTodo",    todo);
        setW("treatBarOverdue", overdue);
    }

    // cor do número: vermelho se há atraso, verde se tudo feito
    const big = document.getElementById("treatmentOverdueCount");
    if (big) big.style.color = overdue > 0 ? "#f87171" : "#34d399";
}

// ================== HELPERS ==================
function normalize(s) { return (s || '').toLowerCase().trim(); }

function parseAlertTs(ts) {
    // expected: 'YYYY-MM-DD HH:MM'
    if (!ts) return null;
    const iso = ts.replace(' ', 'T') + ':00';
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? null : d;
}

function statusChip(status) {
    if (status === 'COVERED') return `<span class="tag ok"><span class="s"></span> COVERED</span>`;
    if (status === 'PARTIAL') return `<span class="tag warn"><span class="s"></span> PARTIAL</span>`;
    return `<span class="tag bad"><span class="s"></span> GAP</span>`;
}

function sevChip(sev) {
    const map = {
        critical: `<span class="tag bad"><span class="s"></span> crítica</span>`,
        high: `<span class="tag warn"><span class="s"></span> alta</span>`,
        medium: `<span class="tag"><span class="s"></span> média</span>`,
        low: `<span class="tag ok"><span class="s"></span> baixa</span>`,
    };
    return map[sev] || `<span class="tag"><span class="s"></span> —</span>`;
}

// ================== MODAL BASE ==================
function openModal(id) {
    const m = $(id);
    if (!m) return;
    m.classList.remove('is-hidden');
    m.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    const m = $(id);
    if (!m) return;
    m.classList.add('is-hidden');
    m.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

// ================== MATURITY MODAL ==================
let selectedMaturityItem = null;

function renderMaturityTable() {
    const tbody = $('#matTbody');
    if (!tbody) return;

    const q = normalize($('#matSearch')?.value);
    const type = $('#matType')?.value || 'all';
    const st = $('#matStatus')?.value || 'all';

    const items = MATURITY_ITEMS.filter(x => {
        const matchesQ = !q || normalize(x.name).includes(q) || normalize(x.subtitle).includes(q);
        const matchesType = type === 'all' || x.type === type;
        const matchesSt = st === 'all' || x.status === st;
        return matchesQ && matchesType && matchesSt;
    });

    tbody.innerHTML = '';
    if (!items.length) {
        tbody.innerHTML = `<tr><td class="muted" colspan="5">Nenhum item encontrado.</td></tr>`;
        return;
    }

    items.forEach(item => {
        const tr = document.createElement('tr');
        tr.setAttribute('data-row-click', '1');
        tr.innerHTML = `
      <td>
        <b>${item.name}</b>
        <div class="muted">${item.subtitle}</div>
      </td>
      <td>${item.type === 'asset' ? 'Ativo' : 'Política'}</td>
      <td>${statusChip(item.status)}</td>
      <td class="muted">${item.summary}</td>
      <td style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
        <button class="btn small" type="button" data-open-controls="${item.id}">Ver controlos</button>
        <button class="btn small primary" type="button" data-go="${item.id}">Ir para página</button>
      </td>
    `;
        tbody.appendChild(tr);
    });

    $$('[data-open-controls]').forEach(b => b.addEventListener('click', (e) => {
        e.stopPropagation();
        openControlsModal(b.dataset.openControls);
    }));

    $$('[data-go]').forEach(b => b.addEventListener('click', (e) => {
        e.stopPropagation();
        goToItemPage(b.dataset.go);
    }));

    // clique na linha também abre controlos
    $$('tr[data-row-click]').forEach((row, idx) => {
        row.addEventListener('click', () => {
            const currentItems = MATURITY_ITEMS.filter(x => {
                const q2 = normalize($('#matSearch')?.value);
                const type2 = $('#matType')?.value || 'all';
                const st2 = $('#matStatus')?.value || 'all';
                const matchesQ = !q2 || normalize(x.name).includes(q2) || normalize(x.subtitle).includes(q2);
                const matchesType = type2 === 'all' || x.type === type2;
                const matchesSt = st2 === 'all' || x.status === st2;
                return matchesQ && matchesType && matchesSt;
            });
            const item = currentItems[idx];
            if (item) openControlsModal(item.id);
        });
    });
}

function openControlsModal(itemId) {
    const item = MATURITY_ITEMS.find(x => x.id === itemId);
    if (!item) return;
    selectedMaturityItem = item;

    $('#controlsModalTitle').textContent = item.name;
    $('#cmType').textContent = item.type === 'asset' ? 'Ativo' : 'Política';
    $('#cmStatus').innerHTML = item.status;

    const tbody = $('#cmTbody');
    tbody.innerHTML = '';

    item.controls.forEach(c => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
      <td><b>${c.key}</b></td>
      <td class="muted">${c.desc}</td>
      <td>${statusChip(c.status)}</td>
      <td class="muted">${c.next}</td>
    `;
        tr.style.cursor = 'pointer';
        tr.addEventListener('click', () => {
            // mock: futuramente abrir detalhe do controlo / evidências
            // por agora só damos um feedback
            alert(`Mock: abrir detalhe do controlo ${c.key} (${item.type === 'asset' ? 'Ativos' : 'Documentos'})`);
        });
        tbody.appendChild(tr);
    });

    openModal('#controlsModal');
}

function goToItemPage(itemId) {
    const item = MATURITY_ITEMS.find(x => x.id === itemId);
    if (!item) return;

    // fecha modais antes de navegar
    closeModal('#controlsModal');
    closeModal('#maturityModal');

    if (item.type === 'asset') window.location.href = ROUTES.assets || '/ativos';
    else window.location.href = ROUTES.docs || '/documentos';
}

// ================== Redirecionar para Riscos ==================
function goToRiskFromAlert(alert) {
    const params = new URLSearchParams({
        from: 'wazuh',
        alert_id: alert.id,
    });

    // usa rota se existir no window.__TB_ROUTES__ (padrão do teu código)
    const risksUrl = ROUTES.risks || '/riscos';

    window.location.href = `${risksUrl}?${params.toString()}#wazuh`;
}

// ================== ALERTS MODAL (redesenhado) ==================
const ALERT_ICONS = {
    BackupFailed: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><line x1="2" y1="2" x2="22" y2="22"/></svg>',
    BackupSuccessful: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
    UpdateApplied: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg>',
    QuotaWarning: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    AgentOffline: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.56 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>',
    MalwareDetected: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    RansomwareBehavior: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    UserLoggedIn: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
};

const ALERT_ICON_DEFAULT = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

function updateAlertModalKpis() {
    // Sempre calculado sobre TODOS os alertas — independente do filtro activo
    const total    = ALERTS.length;
    const critical = ALERTS.filter(a => a.sev === 'critical').length;
    const medium   = ALERTS.filter(a => a.sev === 'medium').length;
    const low      = ALERTS.filter(a => a.sev === 'low' || a.sev === 'high').length;

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('akpiTotal',    total);
    set('akpiCritical', critical);
    set('akpiMedium',   medium);
    set('akpiLow',      low);
}

function renderAlertsTable() {
    const container = document.getElementById('alertTbody');
    if (!container) return;

    const q      = normalize($('#alertSearch')?.value);
    const sev    = $('#alertSeverity')?.value || 'all';
    const fromRaw = $('#alertDateFrom')?.value;
    const toRaw   = $('#alertDateTo')?.value;
    const from = fromRaw ? new Date(`${fromRaw}T00:00:00`) : null;
    const to   = toRaw   ? new Date(`${toRaw}T23:59:59`)   : null;

    const rows = ALERTS.filter(a => {
        const aDate = parseAlertTs(a.ts);
        const matchesQ    = !q || normalize(a.msg).includes(q) || normalize(a.asset).includes(q) || normalize(a.cat).includes(q);
        const matchesSev  = sev === 'all' || a.sev === sev;
        const matchesFrom = !from || (aDate && aDate >= from);
        const matchesTo   = !to   || (aDate && aDate <= to);
        return matchesQ && matchesSev && matchesFrom && matchesTo;
    });

    updateAlertModalKpis();

    // atualiza contagem de resultados no separador
    const resultCountEl = document.getElementById('alertResultCount');
    if (resultCountEl) resultCountEl.textContent = `${rows.length} alerta${rows.length !== 1 ? 's' : ''}`;

    container.innerHTML = '';
    if (!rows.length) {
        container.innerHTML = `<div style="padding:32px 0;text-align:center;color:var(--muted);font-size:13px">Nenhum alerta com os filtros aplicados.</div>`;
        return;
    }

    // paleta por severidade
    const sevStyles = {
        critical: { iconBg: 'rgba(248,113,113,.12)', dot: '#f87171', label: 'Crítico' },
        high:     { iconBg: 'rgba(251,146,60,.12)',  dot: '#fb923c', label: 'Alto'    },
        medium:   { iconBg: 'rgba(251,191,36,.1)',   dot: '#fbbf24', label: 'Médio'   },
        low:      { iconBg: 'rgba(52,211,153,.1)',   dot: '#34d399', label: 'Baixo'   },
    };

    rows.forEach(a => {
        const s = sevStyles[a.sev] || sevStyles.low;
        const icon  = ALERT_ICONS[a.cat] || ALERT_ICON_DEFAULT;
        const label = ALERT_TYPE_LABELS[a.cat] || a.cat;

        const card = document.createElement('div');
        card.className = 'am-alert-card';

        card.innerHTML = `
            <div class="am-alert-icon" style="background:${s.iconBg};color:${s.dot}">${icon}</div>
            <div class="am-alert-body">
                <div class="am-alert-top">
                    <span class="am-alert-sev" style="background:${s.iconBg};color:${s.dot}">${s.label}</span>
                    <span class="am-alert-cat">${label}</span>
                    <span class="am-alert-ts">${a.ts}</span>
                </div>
                <div class="am-alert-asset">${a.asset}</div>
                <div class="am-alert-msg">${a.msg}</div>
            </div>
            <div class="am-alert-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg></div>
        `;

        card.addEventListener('click', () => goToRiskFromAlert(a));
        container.appendChild(card);
    });
}

// ================== PONTO 5: PRÓXIMAS AÇÕES DINÂMICAS ==================
// Gera as ações mais urgentes cruzando alertas + riscos + tratamentos + maturidade.
// Quando tiveres backend real, este cálculo pode ser feito no servidor.
function buildNextActions() {
    const actions = [];

    // 1. Alertas críticos → ação de investigação
    const criticalAlerts = ALERTS.filter(a => a.sev === 'critical');
    if (criticalAlerts.length > 0) {
        const byType = {};
        criticalAlerts.forEach(a => { byType[a.cat] = (byType[a.cat] || 0) + 1; });
        const worst = Object.entries(byType).sort((a,b) => b[1]-a[1])[0];
        const label = ALERT_TYPE_LABELS[worst[0]] || worst[0];
        actions.push({
            priority: 1,
            urgency: 'critical',
            title: `Investigar ${worst[1]}× "${label}"`,
            desc: `${criticalAlerts.length} alertas críticos ativos. Confirma se há incidente em curso e regista no módulo de Riscos.`,
            controls: [],
            link: ROUTES.risks || '/riscos',
            linkLabel: 'Ver Riscos',
        });
    }

    // 2. Tratamentos em atraso
    const overdue = Store.getTreatments().filter(p => p.status === 'Em atraso');
    if (overdue.length > 0) {
        actions.push({
            priority: 2,
            urgency: 'high',
            title: `${overdue.length} plano(s) de tratamento em atraso`,
            desc: `Prazos ultrapassados requerem atualização de estado ou escalamento para o responsável.`,
            controls: [],
            link: ROUTES.treatment || '/tratamento',
            linkLabel: 'Ver Planos',
        });
    }

    // 3. Controlos com GAP em ativos críticos (da maturidade)
    const gapItems = MATURITY_ITEMS.flatMap(item =>
        item.controls
            .filter(c => c.status === 'GAP')
            .map(c => ({ item: item.name, control: c.key, desc: c.desc, next: c.next }))
    );
    if (gapItems.length > 0) {
        const top = gapItems.slice(0, 2);
        actions.push({
            priority: 3,
            urgency: 'medium',
            title: `${gapItems.length} controlo(s) GAP por resolver`,
            desc: top.map(g => `${g.item} → ${g.control}: ${g.next}`).join(' • '),
            controls: top.map(g => g.control),
            link: ROUTES.assets || '/ativos',
            linkLabel: 'Ver Ativos',
        });
    }

    // 4. Agentes offline (do Acronis)
    const offline = ALERTS.filter(a => a.cat === 'AgentOffline');
    if (offline.length > 0) {
        const assets = [...new Set(offline.map(a => a.asset))].slice(0, 3).join(', ');
        actions.push({
            priority: 4,
            urgency: 'medium',
            title: `${offline.length} endpoint(s) sem heartbeat`,
            desc: `Agentes Acronis offline: ${assets}. Verifica conectividade e estado do serviço.`,
            controls: [],
            link: ROUTES.assets || '/ativos',
            linkLabel: 'Ver Ativos',
        });
    }

    // 5. Fallback se tudo estiver ok
    if (actions.length === 0) {
        actions.push({
            priority: 1,
            urgency: 'ok',
            title: 'Tudo em ordem',
            desc: 'Nenhuma ação urgente identificada. Mantém revisões periódicas de conformidade.',
            controls: [],
            link: null,
            linkLabel: null,
        });
    }

    return actions.slice(0, 4); // máximo 4 ações no dashboard
}

function renderNextActions() {
    const container = document.getElementById('nextActionsContainer');
    if (!container) return;

    const actions = buildNextActions();

    const urgencyConfig = {
        critical: { cls: 'bad',  label: 'Crítico',  icon: '🔴' },
        high:     { cls: 'warn', label: 'Alto',     icon: '🟠' },
        medium:   { cls: 'warn', label: 'Médio',    icon: '🟡' },
        ok:       { cls: 'ok',   label: 'OK',       icon: '🟢' },
    };

    container.innerHTML = actions.map((a, i) => {
        const u = urgencyConfig[a.urgency] || urgencyConfig.medium;
        const chips = a.controls.map(c => `<span class="chip">${c}</span>`).join('');
        const btn = a.link
            ? `<a href="${a.link}" class="btn primary" style="font-size:12px;padding:5px 12px;text-decoration:none">${a.linkLabel} →</a>`
            : '';
        return `
        <div class="panel next-action-item" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="chip ${u.cls}" style="font-size:11px">${u.icon} P${i+1} · ${u.label}</span>
                    ${chips}
                </div>
                <b style="font-size:14px">${a.title}</b>
                <p class="muted" style="margin:4px 0 0;font-size:12px;line-height:1.5">${a.desc}</p>
            </div>
            <div style="display:flex;align-items:center">${btn}</div>
        </div>`;
    }).join('');
}

// ================== INIT ==================
async function init() {
    await loadAcronisAlerts();

    // Ponto 2: breakdown por categoria no card
    renderAlertCardBreakdown();

    // Ponto 5: próximas ações dinâmicas
    renderNextActions();

    renderAlertsTable();
    loadRiskStats();
    loadTreatmentStats();
    // Open maturity
    const openMat = $('[data-open-maturity]');
    openMat?.addEventListener('click', () => {
        openModal('#maturityModal');
        renderMaturityTable();
    });
    openMat?.addEventListener('keydown', (e) => { if (e.key === 'Enter') openMat.click(); });

    // Open alerts
    const openAlerts = $('[data-open-alerts]');
    openAlerts?.addEventListener('click', () => {
        openModal('#alertsModal');
        renderAlertsTable();
    });
    openAlerts?.addEventListener('keydown', (e) => { if (e.key === 'Enter') openAlerts.click(); });

    // KPI strip — filtro rápido por severidade
    $$('[data-sev-filter]').forEach(btn => {
        btn.addEventListener('click', () => {
            const sev = btn.dataset.sevFilter;

            // actualiza o select de severidade
            const sevSelect = $('#alertSeverity');
            if (sevSelect) sevSelect.value = sev;

            // actualiza aria-pressed em todos os botões
            $$('[data-sev-filter]').forEach(b => b.setAttribute('aria-pressed', 'false'));
            btn.setAttribute('aria-pressed', 'true');

            renderAlertsTable();
        });
    });

    // quando o select muda manualmente, reflecte estado nos botões KPI e re-renderiza
    $('#alertSeverity')?.addEventListener('change', () => {
        const val = $('#alertSeverity').value;
        $$('[data-sev-filter]').forEach(b => {
            b.setAttribute('aria-pressed', b.dataset.sevFilter === val ? 'true' : 'false');
        });
        renderAlertsTable();
    });

    // Close buttons
    $('#maturityModalClose')?.addEventListener('click', () => closeModal('#maturityModal'));
    $('#controlsModalClose')?.addEventListener('click', () => closeModal('#controlsModal'));
    $('#alertsModalClose')?.addEventListener('click', () => closeModal('#alertsModal'));

    // Go button in controls modal
    $('#controlsModalGo')?.addEventListener('click', () => {
        if (!selectedMaturityItem) return;
        goToItemPage(selectedMaturityItem.id);
    });

    // Filters (maturity)
    $('#matSearch')?.addEventListener('input', renderMaturityTable);
    $('#matType')?.addEventListener('change', renderMaturityTable);
    $('#matStatus')?.addEventListener('change', renderMaturityTable);

    // Filters (alerts)
    $('#alertSearch')?.addEventListener('input', renderAlertsTable);
    $('#alertDateFrom')?.addEventListener('change', renderAlertsTable);
    $('#alertDateTo')?.addEventListener('change', renderAlertsTable);

    // Click outside closes
    $('#maturityModal')?.addEventListener('click', (e) => { if (e.target.id === 'maturityModal') closeModal('#maturityModal'); });
    $('#controlsModal')?.addEventListener('click', (e) => { if (e.target.id === 'controlsModal') closeModal('#controlsModal'); });
    $('#alertsModal')?.addEventListener('click', (e) => { if (e.target.id === 'alertsModal') closeModal('#alertsModal'); });

    // ESC closes top-most modal
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        // fecha em ordem: controls -> maturity -> alerts
        if (!$('#controlsModal')?.classList.contains('is-hidden')) return closeModal('#controlsModal');
        if (!$('#maturityModal')?.classList.contains('is-hidden')) return closeModal('#maturityModal');
        if (!$('#alertsModal')?.classList.contains('is-hidden')) return closeModal('#alertsModal');
    });
}

document.addEventListener('DOMContentLoaded', init);
