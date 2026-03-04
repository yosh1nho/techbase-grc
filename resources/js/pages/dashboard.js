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

const ALERTS = [
    { id: 'WZ-1001', ts: '2026-02-18 08:22', sev: 'critical', asset: 'SRV-DB-01', cat: 'EDR', msg: 'Possible credential dumping detected.' },
    { id: 'WZ-1002', ts: '2026-02-18 07:58', sev: 'high', asset: 'APP-GRC', cat: 'Web', msg: 'Multiple failed logins from same IP.' },
    { id: 'WZ-1003', ts: '2026-02-18 06:40', sev: 'medium', asset: 'FW-EDGE', cat: 'Network', msg: 'Port scan pattern detected.' },
    { id: 'WZ-1004', ts: '2026-02-17 23:10', sev: 'low', asset: 'NAS-BKP', cat: 'Backup', msg: 'Backup finished with warnings.' },
];


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

// ================== ALERTS MODAL ==================
function renderAlertsTable() {
    const tbody = $('#alertTbody');
    if (!tbody) return;

    const q = normalize($('#alertSearch')?.value);
const sev = $('#alertSeverity')?.value || 'all';

const fromRaw = $('#alertDateFrom')?.value; // YYYY-MM-DD
const toRaw = $('#alertDateTo')?.value;     // YYYY-MM-DD

const from = fromRaw ? new Date(`${fromRaw}T00:00:00`) : null;
const to = toRaw ? new Date(`${toRaw}T23:59:59`) : null;

const rows = ALERTS.filter(a => {
    const aDate = parseAlertTs(a.ts);
            const matchesQ = !q || normalize(a.msg).includes(q) || normalize(a.asset).includes(q) || normalize(a.cat).includes(q);
    const matchesSev = sev === 'all' || a.sev === sev;

    const matchesFrom = !from || (aDate && aDate >= from);
    const matchesTo = !to || (aDate && aDate <= to);
    const matchesDate = matchesFrom && matchesTo;

    return matchesQ && matchesSev && matchesDate;
});


    tbody.innerHTML = '';
    if (!rows.length) {
        tbody.innerHTML = `<tr><td class="muted" colspan="5">Nenhum alerta encontrado.</td></tr>`;
        return;
    }

    rows.forEach(a => {
        const tr = document.createElement('tr');
        tr.setAttribute('data-alert-id', a.id);
        tr.style.cursor = 'pointer';

        tr.innerHTML = `
        <td class="muted">${a.ts}</td>
        <td>${sevChip(a.sev)}</td>
        <td><b>${a.asset}</b></td>
        <td class="muted">${a.cat}</td>
        <td class="muted">${a.msg}</td>
    `;

        tr.addEventListener('click', () => {
            goToRiskFromAlert(a);
        });

        tbody.appendChild(tr);
    });
}

// ================== INIT ==================
function init() {
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
$('#alertSeverity')?.addEventListener('change', renderAlertsTable);
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
