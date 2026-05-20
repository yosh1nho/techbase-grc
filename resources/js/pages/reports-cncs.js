// resources/js/pages/reports-cncs.js

const $ = (s) => document.querySelector(s);
let _loadedIncidents = [];   // todos os incidentes carregados
let _manualIncidents = [];
// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────

function setText(id, value) {
    const el = $(id);
    if (el) el.textContent = value ?? '—';
}

function safe(v, dash = '—') {
    const s = (v ?? '').toString().trim();
    return s ? s : dash;
}

function fmtDateTime(val) {
    if (!val) return '—';
    const d = new Date(val);
    return isNaN(d.getTime()) ? val : d.toLocaleString('pt-PT', { dateStyle: 'short', timeStyle: 'short' });
}

function fmtDate(val) {
    if (!val) return '—';
    const d = new Date(val);
    return isNaN(d.getTime()) ? val : d.toLocaleDateString('pt-PT');
}

// ─────────────────────────────────────────────────────────────
// Tabs: alternância entre relatório anual e 24h
// ─────────────────────────────────────────────────────────────

function initTabs() {
    const tabAnnual = document.getElementById('tabBtnAnnual');
    const tab24h = document.getElementById('tabBtn24h');
    const formAnnual = document.getElementById('formAnnual');
    const form24h = document.getElementById('form24h');
    const prevAnnual = document.getElementById('previewAnnual');
    const prev24h = document.getElementById('preview24h');

    if (!tabAnnual || !tab24h) { console.warn('Tab buttons not found'); return; }

    function showAnnual() {
        tabAnnual.classList.add('active-annual');
        tabAnnual.classList.remove('active-24h');
        tab24h.classList.remove('active-annual', 'active-24h');

        if (formAnnual) formAnnual.style.display = 'flex';
        if (prevAnnual) prevAnnual.style.display = 'block';
        if (form24h) form24h.style.display = 'none';
        if (prev24h) prev24h.style.display = 'none';

        if (window.lucide) window.lucide.createIcons();
    }

    function show24h() {
        tab24h.classList.add('active-24h');
        tab24h.classList.remove('active-annual');
        tabAnnual.classList.remove('active-annual', 'active-24h');

        if (form24h) form24h.style.display = 'flex';
        if (prev24h) prev24h.style.display = 'block';
        if (formAnnual) formAnnual.style.display = 'none';
        if (prevAnnual) prevAnnual.style.display = 'none';

        updateNotifProgress();
        if (window.lucide) window.lucide.createIcons();
    }

    tabAnnual.addEventListener('click', showAnnual);
    tab24h.addEventListener('click', show24h);
}

// ─────────────────────────────────────────────────────────────
// Step accordion (shared)
// ─────────────────────────────────────────────────────────────

function initSteps() {
    document.querySelectorAll('.cncs-step-head').forEach(head => {
        head.addEventListener('click', () => {
            const step = document.getElementById(head.dataset.toggle);
            if (!step) return;
            step.classList.toggle('open');
            if (window.lucide) window.lucide.createIcons();
        });
    });
}

// ─────────────────────────────────────────────────────────────
// Tooltips urgente (relatório anual)
// ─────────────────────────────────────────────────────────────

function initUrgentTooltip() {
    const toggle = $('#cncsIsUrgent');
    const tooltip = $('#urgentTooltip');
    if (!toggle || !tooltip) return;

    const show = () => { tooltip.style.display = 'block'; };
    const hide = () => { tooltip.style.display = 'none'; };

    toggle.addEventListener('change', () => {
        if (toggle.checked) show(); else hide();
    });

    $('#urgentInfoIcon')?.addEventListener('mouseenter', show);
    $('#urgentInfoIcon')?.addEventListener('mouseleave', hide);
}

// ─────────────────────────────────────────────────────────────
// Progresso do relatório 24h
// ─────────────────────────────────────────────────────────────

// Quais campos são obrigatórios por secção
const SECTION_REQUIRED = [
    ['#n24Entity'],                           // Secção 1
    ['#n24DetectedAt'],                       // Secção 2
    ['#n24IncidentType', '#n24Description'],  // Secção 3
    ['#n24AffectedSystems'],                  // Secção 4
    ['#n24Severity', '#n24OperationalImpact'],// Secção 5
    ['#n24Containment'],                      // Secção 6
    ['#n24SignerName', '#n24SubmitDate'],      // Secção 7
];

function isSectionDone(selectors) {
    return selectors.every(sel => {
        const el = $(sel);
        if (!el) return false;
        return el.value.trim().length > 0;
    });
}

function updateNotifProgress() {
    let done = 0;
    SECTION_REQUIRED.forEach((sels, i) => {
        const isDone = isSectionDone(sels);
        const seg = document.getElementById(`npSeg${i + 1}`);
        if (seg) {
            seg.classList.toggle('done', isDone);
        }
        if (isDone) done++;
    });

    const label = document.getElementById('notifProgressLabel');
    if (label) label.textContent = `${done} / 7 secções`;
}

// ── Inicializar ───────────────────────────────────────────────
function initStep2b() {
    document.getElementById('btnLoadIncidents')
        ?.addEventListener('click', loadIncidentsForReport);

    document.getElementById('incSelectAll')
        ?.addEventListener('change', (e) => {
            document.querySelectorAll('.inc-row-check')
                .forEach(cb => { cb.checked = e.target.checked; });
            recalcIncidentTotals();
        });

    document.getElementById('btnApplyIncidentTotals')
        ?.addEventListener('click', applyIncidentTotalsToFields);

    document.getElementById('btnAddManualIncident')
        ?.addEventListener('click', addManualIncidentRow);
}

// ── Carregar incidentes da API ─────────────────────────────────
async function loadIncidentsForReport() {
    const year = document.querySelector('#cncsYear')?.value ?? new Date().getFullYear();
    const scope = document.querySelector('#cncsIncidentScope')?.value ?? 'relevant';

    const spinner = document.getElementById('incListSpinner');
    const wrap = document.getElementById('incListWrap');
    const btn = document.getElementById('btnLoadIncidents');

    if (spinner) spinner.style.display = 'block';
    if (wrap) wrap.style.display = 'none';
    if (btn) btn.disabled = true;

    try {
        const res = await fetch(`/api/cncs-reports/incidents-for-report?year=${year}&scope=${scope}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        _loadedIncidents = data.incidents ?? [];
        renderIncidentList(_loadedIncidents);

    } catch (e) {
        console.error('Erro ao carregar incidentes:', e);
        alert('Não foi possível carregar os incidentes: ' + e.message);
    } finally {
        if (spinner) spinner.style.display = 'none';
        if (wrap) wrap.style.display = 'block';
        if (btn) btn.disabled = false;
        if (window.lucide) window.lucide.createIcons();
    }
}

// ── Renderizar tabela de incidentes ───────────────────────────
function renderIncidentList(incidents) {
    const empty = document.getElementById('incListEmpty');
    const table = document.getElementById('incListTable');
    const tbody = document.getElementById('incListBody');
    const totalsBar = document.getElementById('incTotalsBar');

    if (!incidents.length) {
        if (empty) empty.style.display = 'block';
        if (table) table.style.display = 'none';
        if (totalsBar) totalsBar.style.display = 'none';
        return;
    }

    if (empty) empty.style.display = 'none';
    if (table) table.style.display = 'table';
    if (totalsBar) totalsBar.style.display = 'block';
    if (tbody) tbody.innerHTML = '';

    const typeLabels = {
        ransomware: 'Ransomware', malware: 'Malware', phishing: 'Phishing',
        ddos: 'DDoS', unauthorized_access: 'Acesso indevido',
        data_breach: 'Fuga de dados', service_disruption: 'Indisponibilidade',
        backup_failure: 'Backup falhou', other: 'Outro',
    };

    incidents.forEach((inc, idx) => {
        const tr = document.createElement('tr');
        tr.style.cssText = 'border-bottom:1px solid var(--line)';
        tr.innerHTML = `
            <td style="padding:7px 4px;vertical-align:top">
                <input type="checkbox" class="inc-row-check" data-idx="${idx}"
                    checked style="cursor:pointer;margin-top:1px"
                    onchange="recalcIncidentTotals()">
            </td>
            <td style="padding:7px 4px">
                <div style="font-weight:500;color:var(--text);line-height:1.3">${inc.title}</div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px;display:flex;gap:8px;flex-wrap:wrap">
                    ${inc.incident_type ? `<span>${typeLabels[inc.incident_type] ?? inc.incident_type}</span>` : ''}
                    ${inc.severity ? `<span style="color:${inc.severity === 'critical' || inc.severity === 'high' ? '#f87171' : 'var(--muted)'}">● ${inc.severity}</span>` : ''}
                    ${inc.detected_at ? `<span>${new Date(inc.detected_at).toLocaleDateString('pt-PT')}</span>` : ''}
                    ${inc.is_urgent ? `<span style="color:#f59e0b;font-weight:600">⚑ Urgente</span>` : ''}
                </div>
            </td>
            <td style="padding:7px 4px;text-align:center;vertical-align:top;font-family:var(--font-mono)">
                ${inc.affected_users ?? '—'}
            </td>
            <td style="padding:7px 4px;text-align:center;vertical-align:top;font-family:var(--font-mono);white-space:nowrap">
                ${inc.duration_hours !== null ? inc.duration_hours + ' h' : '—'}
            </td>
        `;
        tbody?.appendChild(tr);
    });

    recalcIncidentTotals();
}

// ── Recalcular totais a partir das checkboxes ─────────────────
function recalcIncidentTotals() {
    const checks = document.querySelectorAll('.inc-row-check');
    let count = 0, users = 0, hours = 0;

    checks.forEach(cb => {
        if (!cb.checked) return;
        const inc = _loadedIncidents[parseInt(cb.dataset.idx)];
        if (!inc) return;
        count++;
        if (inc.affected_users && !isNaN(parseInt(inc.affected_users))) {
            users += parseInt(inc.affected_users);
        }
        if (inc.duration_hours !== null) hours += inc.duration_hours;
    });

    // Somar também as linhas manuais
    _manualIncidents.forEach(m => {
        count++;
        users += m.affected_users || 0;
        hours += m.duration_hours || 0;
    });

    const el = (id) => document.getElementById(id);
    if (el('incTotalCount')) el('incTotalCount').textContent = count;
    if (el('incTotalUsers')) el('incTotalUsers').textContent = users;
    if (el('incTotalDuration')) el('incTotalDuration').textContent = Math.round(hours * 10) / 10;
}

// ── Lógica para extrair Trimestres e Datas (Tópicos 4 e 7) ─────────────
function getCalculatedQuarters() {
    const checks = document.querySelectorAll('.inc-row-check:checked');
    const selectedIncs = [];
    checks.forEach(cb => {
        const inc = _loadedIncidents[parseInt(cb.dataset.idx)];
        if (inc) selectedIncs.push(inc);
    });

    _manualIncidents.forEach(m => selectedIncs.push(m));

    const stats = {
        1: { total: 0, types: new Set(), users: 0 },
        2: { total: 0, types: new Set(), users: 0 },
        3: { total: 0, types: new Set(), users: 0 },
        4: { total: 0, types: new Set(), users: 0 }
    };
    const typeLabels = { ransomware: 'Ransomware', malware: 'Malware', phishing: 'Phishing', ddos: 'DDoS', unauthorized_access: 'Acesso indevido', data_breach: 'Fuga de dados', service_disruption: 'Indisponibilidade', backup_failure: 'Backup falhou', other: 'Outro' };

    selectedIncs.forEach(inc => {
        const d = inc.detected_at ? new Date(inc.detected_at) : new Date();
        const month = d.getMonth() + 1;
        let q = 1;
        if (month >= 4 && month <= 6) q = 2;
        else if (month >= 7 && month <= 9) q = 3;
        else if (month >= 10) q = 4;

        stats[q].total++;
        const t = inc.incident_type || 'other';
        stats[q].types.add(typeLabels[t] || t);
        stats[q].users += parseInt(inc.affected_users || 0);
    });

    return [1, 2, 3, 4].map(q => ({
        q: `Q${q}`,
        total: stats[q].total,
        types: Array.from(stats[q].types).join(', ') || '—',
        affected_users: stats[q].users
    }));
}

function getIncidentDescriptions() {
    const checks = document.querySelectorAll('.inc-row-check:checked');
    const texts = [];
    checks.forEach(cb => {
        const inc = _loadedIncidents[parseInt(cb.dataset.idx)];
        if (inc) {
            const dateStr = inc.detected_at ? new Date(inc.detected_at).toLocaleDateString('pt-PT') : 'S/ Data';
            texts.push(`• [${dateStr}] ${inc.title}`);
        }
    });
    return texts.join('\n');
}

// ── Aplicar totais aos campos manuais da secção 5 ─────────────
function applyIncidentTotalsToFields() {
    const totalUsers = parseInt(document.getElementById('incTotalUsers')?.textContent ?? '0');
    const totalHours = parseFloat(document.getElementById('incTotalDuration')?.textContent ?? '0');

    const usersEl = document.querySelector('#cncsUsersAffected');
    const durEl = document.querySelector('#cncsDuration');

    if (usersEl) {
        usersEl.value = totalUsers || '';
        usersEl.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (durEl) {
        durEl.value = totalHours || '';
        durEl.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // ✅ Atualiza os Trimestres visualmente na Preview!
    const calcQuarters = getCalculatedQuarters();
    renderQuarterTable(calcQuarters);

    // ❌ REMOVIDO: Já não injeta a lista no campo #cncsExtra (Tópico 8)

    // Feedback visual breve
    const btn = document.getElementById('btnApplyIncidentTotals');
    if (btn) {
        btn.textContent = '✓ Aplicado!';
        setTimeout(() => { btn.innerHTML = '<i data-lucide="check" style="width:12px;height:12px"></i> Aplicar totais aos campos abaixo'; if (window.lucide) window.lucide.createIcons(); }, 1800);
    }
}

// ── Adicionar linha manual ─────────────────────────────────────
function addManualIncidentRow() {
    const idx = _manualIncidents.length;
    _manualIncidents.push({ title: '', affected_users: 0, duration_hours: 0 });

    const empty = document.getElementById('manualIncidentsEmpty');
    if (empty) empty.style.display = 'none';

    const list = document.getElementById('manualIncidentsList');
    if (!list) return;

    const row = document.createElement('div');
    row.dataset.manualIdx = idx;
    row.style.cssText = 'display:grid;grid-template-columns:1fr 80px 80px 28px;gap:6px;align-items:center;padding:8px 10px;background:var(--panel);border:1px solid var(--line);border-radius:8px';
    row.innerHTML = `
        <input type="text" placeholder="Descrição do incidente"
            style="font-size:12px;padding:5px 8px;border-radius:6px;border:1px solid var(--line);background:var(--input-bg);color:var(--text)"
            oninput="updateManualIncident(${idx},'title',this.value)">
        <input type="number" placeholder="Afetados" min="0"
            style="font-size:12px;padding:5px 8px;border-radius:6px;border:1px solid var(--line);background:var(--input-bg);color:var(--text);text-align:center"
            oninput="updateManualIncident(${idx},'affected_users',parseInt(this.value)||0)">
        <input type="number" placeholder="Horas" min="0" step="0.5"
            style="font-size:12px;padding:5px 8px;border-radius:6px;border:1px solid var(--line);background:var(--input-bg);color:var(--text);text-align:center"
            oninput="updateManualIncident(${idx},'duration_hours',parseFloat(this.value)||0)">
        <button type="button" onclick="removeManualIncident(this,${idx})"
            style="background:none;border:none;cursor:pointer;color:var(--muted);padding:4px;border-radius:4px;line-height:1"
            title="Remover">
            <i data-lucide="x" style="width:14px;height:14px"></i>
        </button>
    `;
    list.appendChild(row);
    if (window.lucide) window.lucide.createIcons();
}

function updateManualIncident(idx, field, value) {
    if (_manualIncidents[idx] !== undefined) {
        _manualIncidents[idx][field] = value;
        recalcIncidentTotals();
    }
}

function removeManualIncident(btn, idx) {
    _manualIncidents.splice(idx, 1);
    btn.closest('[data-manual-idx]')?.remove();
    recalcIncidentTotals();

    // Re-indexar os atributos data-manual-idx restantes
    document.querySelectorAll('[data-manual-idx]').forEach((row, i) => {
        row.dataset.manualIdx = i;
        row.querySelectorAll('input[oninput]').forEach(inp => {
            inp.setAttribute('oninput', inp.getAttribute('oninput').replace(/\d+/, i));
        });
        row.querySelector('button[onclick]')?.setAttribute('onclick',
            row.querySelector('button[onclick]').getAttribute('onclick').replace(/\d+\)/, i + ')'));
    });

    if (_manualIncidents.length === 0) {
        const empty = document.getElementById('manualIncidentsEmpty');
        if (empty) empty.style.display = 'block';
    }
}

// ─────────────────────────────────────────────────────────────
// Live sync do formulário 24h → preview
// ─────────────────────────────────────────────────────────────

// Mapeamentos: { src, dest, transform? }
const SYNC_MAP_24H = [
    { src: '#n24Entity', dest: '#pv24Entity' },
    { src: '#n24Nif', dest: '#pv24Nif' },
    { src: '#n24Description', dest: '#pv24Description' },
    { src: '#n24AffectedSystems', dest: '#pv24Systems' },
    { src: '#n24OperationalImpact', dest: '#pv24OperationalImpact' },
    { src: '#n24Containment', dest: '#pv24Containment' },
    { src: '#n24Recovery', dest: '#pv24Recovery' },
    { src: '#n24SignerName', dest: '#pv24SignerName' },
    { src: '#n24SignerRole', dest: '#pv24SignerRole' },
    { src: '#n24Notes', dest: '#pv24Notes' },
    { src: '#n24DetectionMethod', dest: '#pv24DetectionMethod' },
    { src: '#n24ThirdPartyRisk', dest: '#pv24ThirdPartyRisk' },
    {
        src: '#n24AffectedUsers',
        dest: '#pv24AffectedUsers',
        transform: v => v || '—',
    },
    {
        src: '#n24AffectedSystems2',
        dest: '#pv24AffectedSystems',
        transform: v => v || '—',
    },
    {
        src: '#n24FinancialImpact',
        dest: '#pv24Financial',
        transform: v => v ? `€ ${parseInt(v).toLocaleString('pt-PT')}` : '—',
    },
];

// Select labels
const SELECT_LABELS_24H = {
    '#n24Sector': '#pv24Sector',
    '#n24EntityType': '#pv24EntityType',
    '#n24IncidentType': '#pv24IncidentType',
    '#n24Status': '#pv24Status',
    '#n24AttackVector': '#pv24AttackVector',
    '#n24Severity': '#pv24Severity',
    '#n24PersonalData': '#pv24PersonalData',
};

function syncContactInfo() {
    const officer = $('#n24SecurityOfficer')?.value?.trim() || '—';
    const email = $('#n24ContactEmail')?.value?.trim();
    const phone = $('#n24ContactPhone')?.value?.trim();

    let contact = officer;
    if (email) contact += ` · ${email}`;
    if (phone) contact += ` · ${phone}`;
    setText('#pv24Contact', contact);
}

function syncCrossBorder() {
    const val = $('#n24CrossBorder')?.value;
    const grp = document.getElementById('n24CrossBorderCountriesGrp');
    const countries = $('#n24CrossBorderCountries')?.value?.trim();

    if (grp) grp.style.display = val === 'yes' ? 'flex' : 'none';

    let label = '—';
    if (val === 'yes') {
        label = countries ? `Sim — Países: ${countries}` : 'Sim — Países: (a especificar)';
    } else if (val === 'no') {
        label = 'Não identificado';
    } else if (val === 'unknown') {
        label = 'A avaliar';
    }
    setText('#pv24CrossBorder', label);
}

function syncDateTime24h() {
    const detectedAt = $('#n24DetectedAt')?.value;
    const startedAt = $('#n24StartedAt')?.value;
    setText('#pv24DetectedAt', fmtDateTime(detectedAt));
    setText('#pv24StartedAt', fmtDateTime(startedAt));

    const submitDate = $('#n24SubmitDate')?.value;
    const submitTime = $('#n24SubmitTime')?.value;
    let submitStr = '—';
    if (submitDate && submitTime) {
        submitStr = `${fmtDate(submitDate)} ${submitTime}`;
    } else if (submitDate) {
        submitStr = fmtDate(submitDate);
    }
    setText('#pv24SubmitDateTime', submitStr);
}

function wireLiveSync24h() {
    // Text / textarea fields
    SYNC_MAP_24H.forEach(({ src, dest, transform }) => {
        const el = $(src);
        if (!el) return;
        const update = () => {
            const val = el.value.trim();
            setText(dest, transform ? transform(val) : (val || '—'));
        };
        el.addEventListener('input', update);
        el.addEventListener('change', update);
    });

    // Select elements: show selected option label text
    Object.entries(SELECT_LABELS_24H).forEach(([srcSel, destSel]) => {
        const el = $(srcSel);
        if (!el) return;
        const update = () => {
            const opt = el.options[el.selectedIndex];
            const label = opt && opt.value ? opt.text : '—';
            setText(destSel, label);
        };
        el.addEventListener('change', update);
    });

    // Contact info (composite)
    ['#n24SecurityOfficer', '#n24ContactEmail', '#n24ContactPhone'].forEach(id => {
        $(id)?.addEventListener('input', syncContactInfo);
    });

    // Cross-border
    $('#n24CrossBorder')?.addEventListener('change', syncCrossBorder);
    $('#n24CrossBorderCountries')?.addEventListener('input', syncCrossBorder);

    // Date/time fields
    ['#n24DetectedAt', '#n24StartedAt', '#n24SubmitDate', '#n24SubmitTime'].forEach(id => {
        $(id)?.addEventListener('change', syncDateTime24h);
        $(id)?.addEventListener('input', syncDateTime24h);
    });

    // Progress update
    const progressFields = [
        '#n24Entity', '#n24DetectedAt', '#n24IncidentType', '#n24Description',
        '#n24AffectedSystems', '#n24Severity', '#n24OperationalImpact',
        '#n24Containment', '#n24SignerName', '#n24SubmitDate',
    ];
    progressFields.forEach(id => {
        $(id)?.addEventListener('input', updateNotifProgress);
        $(id)?.addEventListener('change', updateNotifProgress);
    });
}

// ─────────────────────────────────────────────────────────────
// Carregar dados reais da API (relatório anual)
// ─────────────────────────────────────────────────────────────

let cachedReportData = null;

async function fetchReportData() {
    const year = $('#cncsYear')?.value ?? new Date().getFullYear();
    const scope = $('#cncsIncidentScope')?.value ?? 'relevant';

    try {
        const res = await fetch(`/api/cncs-reports/report-data?year=${year}&scope=${scope}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        cachedReportData = await res.json();
    } catch (e) {
        console.error('Erro ao carregar dados do relatório:', e);
        cachedReportData = null;
    }

    return cachedReportData;
}

// ─────────────────────────────────────────────────────────────
// Render helpers (relatório anual)
// ─────────────────────────────────────────────────────────────

function renderQuarterTable(quarters) {
    const tbody = $('#pvQuarterBody');
    if (!tbody) return;

    if (!quarters?.length) {
        tbody.innerHTML = `<tr><td colspan="4" class="muted" style="font-size:12px;padding:10px 0">Nenhum dado disponível.</td></tr>`;
        return;
    }

    tbody.innerHTML = quarters.map(r => {
        const hasData = r.total > 0;
        return `
            <tr style="${hasData ? '' : 'opacity:.45'}">
                <td><span class="q-label" style="color:${hasData ? 'var(--info)' : 'var(--muted)'}">${r.q}</span></td>
                <td style="text-align:center"><span class="q-count" style="font-weight:${hasData ? '700' : '400'}">${r.total}</span></td>
                <td><span class="q-types">${r.types ?? '—'}</span></td>
                <td style="text-align:center;font-family:var(--font-mono);font-size:12px;color:${(r.affected_users ?? 0) > 0 ? 'var(--text)' : 'var(--muted)'}">
                    ${(r.affected_users ?? 0) > 0 ? r.affected_users : '—'}
                </td>
            </tr>
        `;
    }).join('');

    // Linha de totais
    const totalInc = quarters.reduce((s, r) => s + r.total, 0);
    const totalUsers = quarters.reduce((s, r) => s + (r.affected_users ?? 0), 0);
    if (totalInc > 0) {
        tbody.innerHTML += `
            <tr style="border-top:2px solid var(--line);font-weight:600">
                <td style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em">Total</td>
                <td style="text-align:center;font-family:var(--font-mono)">${totalInc}</td>
                <td style="font-size:11px;color:var(--muted)">—</td>
                <td style="text-align:center;font-family:var(--font-mono)">${totalUsers > 0 ? totalUsers : '—'}</td>
            </tr>
        `;
        // Auto-preencher utilizadores afetados se o campo estiver vazio
        const usersEl = $('#cncsUsersAffected');
        if (usersEl && !usersEl.value && totalUsers > 0) {
            usersEl.value = totalUsers;
            usersEl.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
}

function renderGeo(geo) {
    const el = $('#pvGeo');
    if (!el) return;
    if (!geo?.length) {
        el.innerHTML = '<div class="pv-geo-item"><span class="muted">—</span></div>';
        return;
    }
    el.innerHTML = geo.map(x => `
        <div class="pv-geo-item">
            <span>${x.label}</span>
            <span class="geo-count">${x.value}</span>
        </div>
    `).join('');
}

function renderCrossBorder(crossBorder) {
    const el = $('#pvCrossBorder');
    if (!el) return;
    el.innerHTML = crossBorder
        ? '<span class="pv-badge yes">Sim — potencial impacto transfronteiriço</span>'
        : '<span class="pv-badge no">Não identificado</span>';
}

function renderMeasures(items) {
    const wrap = $('#pvMeasures');
    if (!wrap) return;
    if (!items?.length) {
        wrap.innerHTML = '<div class="muted" style="font-size:12px">Nenhuma medida registada.</div>';
        return;
    }
    wrap.innerHTML = items.map(m => `
        <div class="pv-measure">
            <div class="pv-measure-left">
                <div class="pv-measure-title">${m.title}</div>
                <div class="pv-measure-detail">${m.detail}</div>
                <div class="pv-measure-tags">
                    ${(m.tags || []).map(t => `<span class="pv-tag">${t}</span>`).join('')}
                </div>
            </div>
            <div class="pv-measure-right">
                <span class="tag ${m.status === 'Concluído' ? 'ok' : 'warn'}">
                    <span class="s"></span> ${m.status}
                </span>
            </div>
        </div>
    `).join('');
}

function fillManualFromAutoIfEmpty(data) {
    // Não sobrescrever se o utilizador já editou ou se a IA já gerou
    const actEl = document.querySelector('#cncsManualActivities');
    const recEl = document.querySelector('#cncsManualRecs');
    const year = document.querySelector('#cncsYear')?.value ?? '—';

    if (actEl && !actEl.value.trim()) {
        const incidents = data?.kpis?.incidents_total ?? 0;
        const risks = data?.kpis?.high_risks ?? 0;
        const assets = data?.assets_summary?.total ?? 'N/A';
        actEl.value =
            `Em ${year}, a entidade realizou atividades de gestão da segurança das redes e sistemas de informação, ` +
            `incluindo avaliações de conformidade, monitorização de ${assets} ativo(s) registados no sistema, ` +
            `acompanhamento de ${incidents} incidente(s) e revisão de ${risks} risco(s) com score elevado. ` +
            `Clica em "✨ Gerar com IA" para obter texto detalhado baseado nos dados reais do sistema.`;
    }

    if (recEl && !recEl.value.trim()) {
        const withoutBackup = data?.assets_summary?.without_backup ?? 0;
        const nonCompliant = data?.compliance?.data
            ? data.compliance.data.filter(r => r.status === 'non_compliant').length
            : 0;
        recEl.value =
            `Recomenda-se priorizar: ` +
            (withoutBackup > 0 ? `ativação de backup nos ${withoutBackup} ativo(s) sem cobertura; ` : '') +
            (nonCompliant > 0 ? `resolução dos controlos não conformes identificados; ` : '') +
            `reforço de planos de tratamento para riscos críticos sem resposta. ` +
            `Clica em "✨ Gerar com IA" para recomendações detalhadas.`;
    }
}
function renderTextPreviews() {
    const actText = $('#cncsManualActivities')?.value?.trim();
    const recsText = $('#cncsManualRecs')?.value?.trim();
    const extraText = $('#cncsExtra')?.value?.trim();

    [
        ['#pvActivitiesText', actText],
        ['#pvRecsText', recsText],
        ['#pvExtraText', extraText],
    ].forEach(([sel, text]) => {
        const el = $(sel);
        if (!el) return;
        el.textContent = text || '—';
        el.className = 'tb-content' + (text ? '' : ' empty');
    });
}

function renderSignature() {
    const date = $('#cncsReportDate')?.value;
    const officer = $('#cncsSecurityOfficer')?.value?.trim();
    const role = $('#cncsSignature')?.value?.trim();

    const pvDate = $('#pvSignDate');
    const pvOfficer = $('#pvSignOfficer');
    const pvRole = $('#pvSignRole');

    if (pvDate) {
        pvDate.textContent = date ? new Date(date).toLocaleDateString('pt-PT') : '—';
        pvDate.className = 'sb-value' + (date ? '' : ' empty');
    }
    if (pvOfficer) {
        pvOfficer.textContent = officer || '—';
        pvOfficer.className = 'sb-value' + (officer ? '' : ' empty');
    }
    if (pvRole) {
        pvRole.textContent = role || '—';
        pvRole.className = 'sb-value' + (role ? '' : ' empty');
    }
}

// ─────────────────────────────────────────────────────────────
// Tabela de conformidade (paginada)
// ─────────────────────────────────────────────────────────────

let compliancePage = 1;
let complianceTotal = 0;
const COMPLIANCE_PER_PAGE = 20;

async function loadComplianceTable(page = 1) {
    compliancePage = page;

    const framework = $('#complianceFrameworkFilter')?.value ?? 'all';
    const status = $('#complianceStatusFilter')?.value ?? 'compliant,partial';

    const params = new URLSearchParams({ framework, status, page, per_page: COMPLIANCE_PER_PAGE });

    const tbody = $('#complianceTbody');
    const loading = $('#complianceLoading');

    if (loading) loading.style.display = 'flex';
    if (tbody) tbody.innerHTML = '';

    try {
        const res = await fetch(`/api/cncs-reports/compliance-table?${params}`);
        const data = await res.json();

        complianceTotal = data.pagination.total;

        if (loading) loading.style.display = 'none';
        if (!tbody) return;

        if (!data.data.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="muted" style="text-align:center;padding:24px">
                Nenhum controlo encontrado com os filtros seleccionados.
            </td></tr>`;
            renderCompliancePager(data.pagination);
            return;
        }

        let lastFw = null;
        data.data.forEach(row => {
            if (row.framework !== lastFw) {
                lastFw = row.framework;
                const sep = document.createElement('tr');
                sep.className = 'compliance-fw-sep';
                sep.innerHTML = `<td colspan="6" style="
                    font-size:11px;font-weight:700;letter-spacing:.06em;
                    text-transform:uppercase;color:var(--info);
                    padding:14px 0 6px;border-bottom:1px solid var(--line)
                ">${row.framework}</td>`;
                tbody.appendChild(sep);
            }

            const statusConfig = {
                compliant: { cls: 'ok', label: 'Conforme' },
                partial: { cls: 'warn', label: 'Parcial' },
                non_compliant: { cls: 'bad', label: 'Não conf.' },
            };
            const s = statusConfig[row.status] || statusConfig.non_compliant;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="font-family:var(--font-mono);font-size:12px;color:var(--info);white-space:nowrap">
                    ${row.control_code}
                </td>
                <td style="font-size:12px;color:var(--muted)">${row.group_code}</td>
                <td style="font-size:13px;max-width:300px">${row.description}</td>
                <td>
                    <span class="tag ${s.cls}">
                        <span class="s"></span> ${s.label}
                    </span>
                </td>
                <td style="font-size:12px;color:var(--muted);max-width:200px">
                    ${row.notes
                    ? `<span title="${row.notes}">${row.notes.substring(0, 60)}${row.notes.length > 60 ? '…' : ''}</span>`
                    : '—'}
                </td>
                <td style="font-size:11px;color:var(--muted);white-space:nowrap">
                    ${row.assessed_by ?? '—'}<br>
                    ${row.assessed_at ? new Date(row.assessed_at).toLocaleDateString('pt-PT') : ''}
                </td>
            `;
            tbody.appendChild(tr);
        });

        renderCompliancePager(data.pagination);

    } catch (e) {
        console.error('Erro ao carregar tabela de conformidade:', e);
        if (loading) loading.style.display = 'none';
        if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="muted" style="text-align:center;padding:24px">
            Erro ao carregar dados. Tenta novamente.
        </td></tr>`;
    }
}

function renderCompliancePager(pagination) {
    const pager = $('#compliancePager');
    if (!pager) return;

    const { total, page, per_page, pages } = pagination;
    const from = (page - 1) * per_page + 1;
    const to = Math.min(page * per_page, total);

    pager.innerHTML = `
        <span class="muted" style="font-size:12px">${from}–${to} de ${total} controlos</span>
        <div style="display:flex;gap:6px">
            <button class="btn" ${page <= 1 ? 'disabled' : ''} onclick="loadComplianceTable(${page - 1})">‹ Anterior</button>
            <span style="font-size:12px;padding:6px 10px;color:var(--muted)">Pág. ${page} / ${pages}</span>
            <button class="btn" ${page >= pages ? 'disabled' : ''} onclick="loadComplianceTable(${page + 1})">Seguinte ›</button>
        </div>
    `;
}

// ─────────────────────────────────────────────────────────────
// Render principal (relatório anual)
// ─────────────────────────────────────────────────────────────

async function renderPreview() {
    const year = $('#cncsYear')?.value ?? '—';
    const scope = $('#cncsIncidentScope')?.value ?? 'relevant';
    const entity = $('#cncsEntity')?.value?.trim() || '—';
    const period = $('#cncsPeriod')?.value?.trim() || '—';

    const spinner = $('#pvLoadingSpinner');
    if (spinner) spinner.style.display = 'flex';

    const data = await fetchReportData();

    if (spinner) spinner.style.display = 'none';

    if (!data) {
        setText('#pvSubtitle', 'Erro ao carregar dados. Verifica a ligação.');
        return;
    }

    const scopeLabel = scope === 'relevant' ? 'Relevante/substancial' : 'Todos os incidentes';
    setText('#pvSubtitle', `${scopeLabel} · Ano ${year}`);
    setText('#pvIncTotal', data.kpis.incidents_total);
    setText('#pvIncRelevant', data.kpis.incidents_relevant);
    setText('#pvHighRisks', data.kpis.high_risks);
    setText('#pvEntity', entity);
    setText('#pvPeriod', `${year} · ${period}`);

    renderQuarterTable(data.quarters);

    const usersEl = $('#cncsUsersAffected');
    const durEl = $('#cncsDuration');
    setText('#pvUsersAffected', usersEl?.value || '—');
    setText('#pvUsersAffectedHint', '');
    setText('#pvDuration', durEl?.value ? `${durEl.value} h` : '—');
    setText('#pvDurationHint', '');

    renderGeo([]);
    renderCrossBorder(false);
    renderMeasures(data.measures);
    fillManualFromAutoIfEmpty(data);
    renderTextPreviews();
    renderSignature();

    if (window.lucide) window.lucide.createIcons();
}

// ─────────────────────────────────────────────────────────────
// Render preview do relatório 24h (ao clicar "Atualizar prévia")
// ─────────────────────────────────────────────────────────────

function renderPreview24h() {
    syncContactInfo();
    syncCrossBorder();
    syncDateTime24h();

    // Syncs automáticos de texto
    SYNC_MAP_24H.forEach(({ src, dest, transform }) => {
        const el = $(src);
        if (!el) return;
        const val = el.value.trim();
        setText(dest, transform ? transform(val) : (val || '—'));
    });

    // Selects
    Object.entries(SELECT_LABELS_24H).forEach(([srcSel, destSel]) => {
        const el = $(srcSel);
        if (!el) return;
        const opt = el.options[el.selectedIndex];
        setText(destSel, opt && opt.value ? opt.text : '—');
    });

    // Subtitle
    const entity = $('#n24Entity')?.value?.trim() || '—';
    const now = new Date().toLocaleDateString('pt-PT');
    setText('#pv24Subtitle', `${entity} · Gerado em ${now}`);

    updateNotifProgress();
    if (window.lucide) window.lucide.createIcons();
}


/**
 * Gera o bloco PDF da secção 5 enriquecida.
 * Chamar de dentro de buildCncsPdfDefinition(), substituindo o bloco antigo.
 */
function buildSection5PdfBlock(form, data) {
    const assets = data?.assets_summary;
    const risks = data?.risk_summary;
    const inc = data?.incidents_detail;
    const section5Ai = window._aiSection5 || null;

    const blocks = [
        { text: '5 — Análise agregada dos incidentes de segurança', style: 'h1' },
    ];

    // Texto narrativo IA (se disponível) ou campos manuais
    if (section5Ai) {
        blocks.push({ text: section5Ai, style: 'p', margin: [0, 0, 0, 10] });
    } else {
        blocks.push({
            columns: [
                { text: `Utilizadores afetados: ${safe(form.usersAffected)}`, style: 'p', width: '*' },
                { text: `Duração: ${safe(form.duration)} h`, style: 'p', width: '*' },
            ],
            margin: [0, 0, 0, 10],
        });
    }

    // Tabela de ativos (se disponível)
    if (assets?.total) {
        const critRows = Object.entries(assets.by_criticality || {}).map(([k, v]) => [
            { text: k, fontSize: 9 },
            { text: String(v), alignment: 'center', fontSize: 9 },
        ]);

        blocks.push(
            { text: '5.1 — Inventário de ativos', style: 'label', margin: [0, 6, 0, 4] },
            {
                table: {
                    widths: [120, 60, '*'],
                    body: [
                        [
                            { text: 'Métrica', bold: true, fontSize: 9, fillColor: '#f5f7fc' },
                            { text: 'N.º', bold: true, alignment: 'center', fontSize: 9, fillColor: '#f5f7fc' },
                            { text: 'Observação', bold: true, fontSize: 9, fillColor: '#f5f7fc' },
                        ],
                        [{ text: 'Total de ativos', fontSize: 9 }, { text: String(assets.total), alignment: 'center', fontSize: 9 }, { text: 'Ativos registados no sistema GRC', fontSize: 9, color: '#555' }],
                        [{ text: 'Agentes offline', fontSize: 9 }, { text: String(assets.offline_agents), alignment: 'center', fontSize: 9 }, { text: assets.offline_agents > 0 ? 'Monitorização comprometida' : '—', fontSize: 9, color: '#555' }],
                    ],
                },
                layout: 'lightHorizontalLines', margin: [0, 0, 0, 10],
            }
        );
    }

    // Tabela de riscos (se disponível)
    if (risks?.total) {
        const distRows = Object.entries(risks.score_distribution || {}).map(([k, v]) => [
            { text: k, fontSize: 9 },
            { text: String(v), alignment: 'center', fontSize: 9 },
        ]);

        blocks.push(
            { text: '5.2 — Perfil de risco', style: 'label', margin: [0, 6, 0, 4] },
            {
                table: {
                    widths: [180, 60],
                    headerRows: 1,
                    body: [
                        [
                            { text: 'Nível de risco', bold: true, fontSize: 9, fillColor: '#f5f7fc' },
                            { text: 'Riscos', bold: true, alignment: 'center', fontSize: 9, fillColor: '#f5f7fc' },
                        ],
                        ...distRows,
                        [
                            { text: 'Tratamentos concluídos no ano', bold: true, fontSize: 9 },
                            { text: String(risks.treated_this_year), alignment: 'center', bold: true, fontSize: 9 },
                        ],
                    ],
                },
                layout: 'lightHorizontalLines', margin: [0, 0, 0, 10],
            }
        );
    }

    return blocks;
}

// ─────────────────────────────────────────────────────────────
// Exportação PDF — Relatório Anual
// ─────────────────────────────────────────────────────────────

function buildCncsPdfDefinition(form, data) {
    const now = new Date();
    const dateStr = now.toISOString().slice(0, 10);

    const quarterRows = (data?.quarters || []).map(r => [
        { text: r.q, bold: true },
        { text: String(r.total), alignment: 'center' },
        { text: r.types || '—', color: '#555' },
    ]);

    const measures = (data?.measures || []).map(m =>
        `${m.title}\n   ${m.detail} (${m.status})`
    );

    const section5Blocks = buildSection5PdfBlock(form, data);

    const complianceData = data?.compliance?.data || [];
    let complianceBlock = [];

    if (complianceData.length > 0) {
        const complianceRows = complianceData.map(c => [
            { text: c.control_code, fontSize: 9, color: '#0b1220' },
            { text: c.description, fontSize: 9 },
            { text: c.status_label, fontSize: 9, color: c.status === 'compliant' ? '#16a34a' : (c.status === 'partial' ? '#ca8a04' : '#dc2626') },
            { text: c.notes || '—', fontSize: 9, color: '#666' }
        ]);

        complianceBlock = [
            // ✅ NOVIDADE: Agora é a Secção 10
            { text: '10 — Anexo: Estado de Conformidade', style: 'h1', pageBreak: 'before' },
            {
                table: {
                    headerRows: 1,
                    widths: [50, '*', 60, 100],
                    body: [
                        [
                            { text: 'Controlo', style: 'tableHeader' },
                            { text: 'Descrição', style: 'tableHeader' },
                            { text: 'Estado', style: 'tableHeader' },
                            { text: 'Notas', style: 'tableHeader' }
                        ],
                        ...complianceRows
                    ]
                },
                layout: 'lightHorizontalLines',
                margin: [0, 0, 0, 10]
            }
        ];
    }

    return {
        pageSize: 'A4',
        pageMargins: [50, 60, 50, 60],
        footer: (currentPage, pageCount) => ({
            text: `Relatório Anual CNCS · ${safe(form.year)} · Página ${currentPage} de ${pageCount}`,
            alignment: 'center', fontSize: 8, color: '#888', margin: [0, 14, 0, 0],
        }),
        styles: {
            h0: { fontSize: 20, bold: true, alignment: 'center', color: '#0b1220' },
            sub0: { fontSize: 10, alignment: 'center', color: '#666', margin: [0, 4, 0, 0] },
            h1: { fontSize: 12, bold: true, margin: [0, 18, 0, 6], color: '#0b1220' },
            p: { fontSize: 10, lineHeight: 1.4, color: '#1a2535' },
            label: { fontSize: 10, bold: true, color: '#0b1220' },
            muted: { fontSize: 9, color: '#666' },
            tableHeader: { bold: true, fontSize: 9, color: '#444', fillColor: '#f5f7fc' },
        },
        defaultStyle: { font: 'Roboto', fontSize: 10 },
        content: [
            { text: 'Relatório Anual de Segurança', style: 'h0' },
            { text: `Modelo CNCS · ${safe(form.entityName)} · ${safe(form.year)}`, style: 'sub0' },
            { text: `Gerado em ${dateStr}`, style: 'muted', alignment: 'center', margin: [0, 2, 0, 20] },

            { text: '1 — Designação da entidade', style: 'h1' },
            { text: safe(form.entityName), style: 'p' },

            { text: '2 — Ano civil e período de tempo do relatório', style: 'h1' },
            {
                table: {
                    widths: [140, '*'],
                    body: [
                        [{ text: 'Ano civil', style: 'tableHeader' }, safe(form.year)],
                        [{ text: 'Período', style: 'tableHeader' }, safe(form.period, '01-01 a 31-12')],
                        [{ text: 'Escopo de incidentes', style: 'tableHeader' }, form.scope === 'relevant' ? 'Apenas relevante / substancial' : 'Todos'],
                        [{ text: 'Incidente urgente', style: 'tableHeader' }, form.isUrgent ? 'Sim — incidente grave (Art. 23.º NIS2)' : 'Não'],
                    ],
                },
                layout: 'lightHorizontalLines', fontSize: 10,
            },

            { text: '3 — Descrição sumária das principais atividades', style: 'h1' },
            { text: safe(form.activitiesText), style: 'p' },

            { text: '4 — Estatística trimestral', style: 'h1' },
            {
                table: {
                    headerRows: 1, widths: [50, 50, '*'],
                    body: [
                        [
                            { text: 'Trimestre', style: 'tableHeader' },
                            { text: 'N.º', style: 'tableHeader', alignment: 'center' },
                            { text: 'Tipo(s)', style: 'tableHeader' },
                        ],
                        ...quarterRows,
                    ],
                },
                layout: 'lightHorizontalLines', fontSize: 10,
            },

            ...section5Blocks,

            { text: '6 — Recomendações de melhoria', style: 'h1' },
            { text: safe(form.recsText), style: 'p' },

            { text: '7 — Problemas identificados e medidas implementadas', style: 'h1' },
            measures.length
                ? { ul: measures, fontSize: 10, lineHeight: 1.4 }
                : { text: '—', style: 'p' },

            { text: '8 — Outra informação relevante', style: 'h1' },
            { text: safe(form.extraText), style: 'p' },

            // ✅ NOVIDADE: Bloco 9 inteiramente dedicado aos Incidentes!
            { text: '9 — Anexo: Resumo dos Incidentes', style: 'h1', pageBreak: 'before' },
            { text: form.incidentsList, style: 'p', margin: [0, 0, 0, 10] },

            // ✅ Bloco 10 (Compliance)
            ...complianceBlock,

            { text: ' ', margin: [0, 16, 0, 0] },
            {
                table: {
                    widths: ['*', '*', '*'],
                    body: [
                        [
                            { text: 'Data', style: 'tableHeader' },
                            { text: 'Responsável de segurança', style: 'tableHeader' },
                            { text: 'Cargo / Assinatura', style: 'tableHeader' },
                        ],
                        [
                            safe(form.reportDate, dateStr),
                            safe(form.securityOfficer),
                            safe(form.signature, '____________________________'),
                        ],
                    ],
                },
                layout: 'lightHorizontalLines', fontSize: 10,
            },
        ],
    };
}

async function exportPdfCNCS() {
    if (!window.pdfMake?.createPdf) {
        alert('pdfmake ainda não carregou. Aguarda 2s e tenta novamente.');
        return;
    }

    const year = $('#cncsYear')?.value ?? '—';
    const scope = $('#cncsIncidentScope')?.value ?? 'relevant';
    const data = cachedReportData || await fetchReportData();

    // Força o PDF a usar os trimestres baseados na tua seleção atual!
    data.quarters = getCalculatedQuarters();

    const form = {
        year, scope,
        isUrgent: $('#cncsIsUrgent')?.checked ?? false,
        entityName: $('#cncsEntity')?.value?.trim() || '—',
        period: $('#cncsPeriod')?.value?.trim() || '',
        usersAffected: $('#cncsUsersAffected')?.value?.trim() || '—',
        duration: $('#cncsDuration')?.value?.trim() || '—',
        activitiesText: $('#cncsManualActivities')?.value?.trim() || '—',
        recsText: $('#cncsManualRecs')?.value?.trim() || '—',
        extraText: $('#cncsExtra')?.value?.trim() || '—',
        // ✅ NOVIDADE: Apanha a lista de incidentes aqui diretamente!
        incidentsList: getIncidentDescriptions() || 'Nenhum incidente listado.',
        reportDate: $('#cncsReportDate')?.value || '',
        securityOfficer: $('#cncsSecurityOfficer')?.value?.trim() || '',
        signature: $('#cncsSignature')?.value?.trim() || '',
    };

    const def = buildCncsPdfDefinition(form, data);
    const filename = `cncs_relatorio_${year}_${new Date().toISOString().slice(0, 10)}.pdf`;
    window.pdfMake.createPdf(def).download(filename);
}

// ─────────────────────────────────────────────────────────────
// Exportação PDF — Notificação 24h
// ─────────────────────────────────────────────────────────────

function getSelectText(id) {
    const el = $(id);
    if (!el) return '—';
    const opt = el.options[el.selectedIndex];
    return (opt && opt.value) ? opt.text : '—';
}

function buildNotif24hPdfDefinition(f) {
    const now = new Date();
    const dateStr = now.toLocaleString('pt-PT');

    return {
        pageSize: 'A4',
        pageMargins: [50, 65, 50, 55],
        header: (currentPage) => currentPage === 1 ? {
            text: '⚠ NOTIFICAÇÃO INICIAL DE INCIDENTE — 24 HORAS — CONFIDENCIAL',
            alignment: 'center',
            fontSize: 8,
            bold: true,
            color: '#c62828',
            margin: [50, 20, 50, 0],
        } : null,
        footer: (currentPage, pageCount) => ({
            columns: [
                { text: `Entidade: ${safe(f.entity)}`, fontSize: 8, color: '#888' },
                { text: `Pág. ${currentPage} de ${pageCount}`, alignment: 'right', fontSize: 8, color: '#888' },
            ],
            margin: [50, 14, 50, 0],
        }),
        styles: {
            title: { fontSize: 18, bold: true, alignment: 'center', color: '#b71c1c' },
            sub: { fontSize: 10, alignment: 'center', color: '#666', margin: [0, 3, 0, 0] },
            h1: { fontSize: 11, bold: true, margin: [0, 16, 0, 5], color: '#b71c1c', decoration: 'underline' },
            p: { fontSize: 10, lineHeight: 1.45, color: '#1a1a2e' },
            label: { fontSize: 9, bold: true, color: '#333', fillColor: '#fef2f2' },
            value: { fontSize: 10, color: '#1a1a2e' },
            muted: { fontSize: 8, color: '#777' },
            hdr: { bold: true, fontSize: 9, color: '#555', fillColor: '#f5f5f5' },
            urgent: { fontSize: 10, bold: true, color: '#c62828' },
        },
        defaultStyle: { font: 'Roboto', fontSize: 10 },
        content: [
            // Cabeçalho
            { text: 'NOTIFICAÇÃO INICIAL DE INCIDENTE', style: 'title' },
            { text: 'Artigo 23.º — Diretiva (UE) 2022/2555 (NIS2) · Decreto-Lei n.º 125/2025', style: 'sub' },
            { text: `CNCS — Centro Nacional de Cibersegurança`, style: 'sub' },
            { text: `Gerada em: ${dateStr}`, style: 'muted', alignment: 'center', margin: [0, 2, 0, 4] },
            {
                canvas: [{ type: 'line', x1: 0, y1: 0, x2: 495, y2: 0, lineWidth: 2, lineColor: '#c62828' }],
                margin: [0, 6, 0, 14],
            },

            // Aviso de urgência
            {
                table: {
                    widths: ['*'],
                    body: [[{
                        text: '⚠ Esta notificação deve ser submetida ao CNCS dentro de 24 horas após deteção do incidente, conforme obrigação legal.\n' +
                            'Email: incidentes@cncs.gov.pt  |  Telefone: +351 210 012 000 (24/7)',
                        fontSize: 9, color: '#c62828', bold: true,
                        fillColor: '#fff8f8', margin: [8, 8, 8, 8],
                    }]],
                },
                layout: { hLineColor: '#ef9a9a', vLineColor: '#ef9a9a' },
                margin: [0, 0, 0, 10],
            },

            // SECÇÃO 1
            { text: 'SECÇÃO 1 — IDENTIFICAÇÃO DA ENTIDADE', style: 'h1' },
            {
                table: {
                    widths: [160, '*'],
                    body: [
                        [{ text: 'Nome da entidade', style: 'label' }, { text: safe(f.entity), style: 'value' }],
                        [{ text: 'NIF / NIPC', style: 'label' }, { text: safe(f.nif), style: 'value' }],
                        [{ text: 'Setor de atividade', style: 'label' }, { text: safe(f.sector), style: 'value' }],
                        [{ text: 'Tipo de entidade (NIS2)', style: 'label' }, { text: safe(f.entityType), style: 'value' }],
                        [{ text: 'Responsável de segurança', style: 'label' }, { text: safe(f.securityOfficer), style: 'value' }],
                        [{ text: 'Email de contacto', style: 'label' }, { text: safe(f.email), style: 'value' }],
                        [{ text: 'Telefone de contacto', style: 'label' }, { text: safe(f.phone), style: 'value' }],
                    ],
                },
                layout: 'lightHorizontalLines', fontSize: 10, margin: [0, 0, 0, 6],
            },

            // SECÇÃO 2
            { text: 'SECÇÃO 2 — DETEÇÃO DO INCIDENTE', style: 'h1' },
            {
                table: {
                    widths: [160, '*'],
                    body: [
                        [{ text: 'Data e hora de deteção', style: 'label' }, { text: fmtDateTime(f.detectedAt), style: 'urgent' }],
                        [{ text: 'Início estimado', style: 'label' }, { text: fmtDateTime(f.startedAt), style: 'value' }],
                        [{ text: 'Quem detetou', style: 'label' }, { text: safe(f.detectedBy), style: 'value' }],
                    ],
                },
                layout: 'lightHorizontalLines', fontSize: 10, margin: [0, 0, 0, 4],
            },
            { text: 'Método / forma de deteção:', style: 'p', bold: true, margin: [0, 6, 0, 2] },
            { text: safe(f.detectionMethod), style: 'p', margin: [0, 0, 0, 6] },

            // SECÇÃO 3
            { text: 'SECÇÃO 3 — NATUREZA DO INCIDENTE', style: 'h1' },
            {
                table: {
                    widths: [160, '*'],
                    body: [
                        [{ text: 'Tipo / categoria', style: 'label' }, { text: safe(f.incidentType), style: 'value' }],
                        [{ text: 'Estado atual', style: 'label' }, { text: safe(f.status), style: 'value' }],
                        [{ text: 'Vetor de ataque (suspeito)', style: 'label' }, { text: safe(f.attackVector), style: 'value' }],
                        [{ text: 'Dados pessoais envolvidos', style: 'label' }, { text: safe(f.personalData), style: 'value' }],
                    ],
                },
                layout: 'lightHorizontalLines', fontSize: 10, margin: [0, 0, 0, 4],
            },
            { text: 'Descrição inicial:', style: 'p', bold: true, margin: [0, 6, 0, 2] },
            { text: safe(f.description), style: 'p', margin: [0, 0, 0, 6] },

            // SECÇÃO 4
            { text: 'SECÇÃO 4 — SISTEMAS E SERVIÇOS AFETADOS', style: 'h1' },
            {
                table: {
                    widths: [160, '*'],
                    body: [
                        [{ text: 'N.º utilizadores afetados', style: 'label' }, { text: safe(f.affectedUsers), style: 'value' }],
                        [{ text: 'N.º sistemas comprometidos', style: 'label' }, { text: safe(f.affectedSystems2), style: 'value' }],
                        [{ text: 'Impacto transfronteiriço', style: 'label' }, { text: safe(f.crossBorder), style: 'value' }],
                    ],
                },
                layout: 'lightHorizontalLines', fontSize: 10, margin: [0, 0, 0, 4],
            },
            { text: 'Sistemas / serviços afetados:', style: 'p', bold: true, margin: [0, 6, 0, 2] },
            { text: safe(f.affectedSystemsList), style: 'p', margin: [0, 0, 0, 4] },
            { text: 'Serviços críticos interrompidos:', style: 'p', bold: true, margin: [0, 4, 0, 2] },
            { text: safe(f.criticalServices), style: 'p', margin: [0, 0, 0, 6] },

            // SECÇÃO 5
            { text: 'SECÇÃO 5 — AVALIAÇÃO DE IMPACTO', style: 'h1' },
            {
                table: {
                    widths: [160, '*'],
                    body: [
                        [{ text: 'Nível de severidade', style: 'label' }, { text: safe(f.severity), style: 'urgent' }],
                        [{ text: 'Critério (Art. 23.º NIS2)', style: 'label' }, { text: safe(f.criterion), style: 'value' }],
                        [{ text: 'Impacto financeiro estimado', style: 'label' }, { text: f.financialImpact ? `€ ${parseInt(f.financialImpact).toLocaleString('pt-PT')}` : '—', style: 'value' }],
                    ],
                },
                layout: 'lightHorizontalLines', fontSize: 10, margin: [0, 0, 0, 4],
            },
            { text: 'Impacto operacional:', style: 'p', bold: true, margin: [0, 6, 0, 2] },
            { text: safe(f.operationalImpact), style: 'p', margin: [0, 0, 0, 4] },
            { text: 'Risco para terceiros:', style: 'p', bold: true, margin: [0, 4, 0, 2] },
            { text: safe(f.thirdPartyRisk), style: 'p', margin: [0, 0, 0, 6] },

            // SECÇÃO 6
            { text: 'SECÇÃO 6 — MEDIDAS IMEDIATAS TOMADAS', style: 'h1' },
            {
                table: {
                    widths: [160, '*'],
                    body: [
                        [{ text: 'Suporte externo / CSIRT', style: 'label' }, { text: safe(f.externalSupport), style: 'value' }],
                        [{ text: 'Backups disponíveis', style: 'label' }, { text: safe(f.backupAvailable), style: 'value' }],
                    ],
                },
                layout: 'lightHorizontalLines', fontSize: 10, margin: [0, 0, 0, 4],
            },
            { text: 'Medidas de contenção:', style: 'p', bold: true, margin: [0, 6, 0, 2] },
            { text: safe(f.containment), style: 'p', margin: [0, 0, 0, 4] },
            { text: 'Medidas de recuperação planeadas:', style: 'p', bold: true, margin: [0, 4, 0, 2] },
            { text: safe(f.recovery), style: 'p', margin: [0, 0, 0, 4] },
            { text: 'Notificação a outras autoridades (ex.: CNPD, MP):', style: 'p', bold: true, margin: [0, 4, 0, 2] },
            { text: safe(f.otherAuthorities), style: 'p', margin: [0, 0, 0, 6] },

            // SECÇÃO 7
            { text: 'SECÇÃO 7 — DECLARAÇÃO E ASSINATURA', style: 'h1' },
            {
                text: 'O(a) abaixo identificado(a) declara que as informações prestadas nesta notificação são verdadeiras e completas ao melhor do seu conhecimento, e que esta notificação é submetida ao CNCS dentro do prazo legal de 24 horas após a deteção do incidente, conforme o Art. 23.º da Diretiva (UE) 2022/2555 e o Decreto-Lei n.º 125/2025.',
                style: 'p', italics: true, margin: [0, 0, 0, 10],
            },
            {
                table: {
                    widths: ['*', '*', '*'],
                    body: [
                        [
                            { text: 'Nome do declarante', style: 'hdr' },
                            { text: 'Cargo / Função', style: 'hdr' },
                            { text: 'Data e hora de submissão', style: 'hdr' },
                        ],
                        [
                            safe(f.signerName),
                            safe(f.signerRole),
                            (f.submitDate && f.submitTime) ? `${fmtDate(f.submitDate)} ${f.submitTime}` :
                                f.submitDate ? fmtDate(f.submitDate) : '—',
                        ],
                    ],
                },
                layout: 'lightHorizontalLines', fontSize: 10, margin: [0, 0, 0, 14],
            },
            f.notes ? [
                { text: 'Notas adicionais:', style: 'p', bold: true, margin: [0, 0, 0, 2] },
                { text: f.notes, style: 'p' },
            ] : null,

            // Assinatura física
            {
                columns: [
                    { text: `Assinatura: _________________________________\n\nData: _________________________________________`, style: 'p', margin: [0, 20, 0, 0] },
                    { text: `Carimbo da entidade:\n\n\n\n\n\n`, style: 'p', margin: [0, 20, 0, 0] },
                ],
            },
        ].filter(Boolean).flat(),
    };
}

async function exportPdf24h() {
    if (!window.pdfMake?.createPdf) {
        alert('pdfmake ainda não carregou. Aguarda 2s e tenta novamente.');
        return;
    }

    const f = {
        entity: $('#n24Entity')?.value?.trim() || '—',
        nif: $('#n24Nif')?.value?.trim() || '—',
        sector: getSelectText('#n24Sector'),
        entityType: getSelectText('#n24EntityType'),
        securityOfficer: $('#n24SecurityOfficer')?.value?.trim() || '—',
        email: $('#n24ContactEmail')?.value?.trim() || '—',
        phone: $('#n24ContactPhone')?.value?.trim() || '—',
        detectedAt: $('#n24DetectedAt')?.value || '',
        startedAt: $('#n24StartedAt')?.value || '',
        detectedBy: getSelectText('#n24DetectedBy'),
        detectionMethod: $('#n24DetectionMethod')?.value?.trim() || '—',
        incidentType: getSelectText('#n24IncidentType'),
        description: $('#n24Description')?.value?.trim() || '—',
        status: getSelectText('#n24Status'),
        attackVector: getSelectText('#n24AttackVector'),
        personalData: getSelectText('#n24PersonalData'),
        affectedSystemsList: $('#n24AffectedSystems')?.value?.trim() || '—',
        affectedUsers: $('#n24AffectedUsers')?.value?.trim() || '—',
        affectedSystems2: $('#n24AffectedSystems2')?.value?.trim() || '—',
        criticalServices: $('#n24CriticalServices')?.value?.trim() || '—',
        crossBorder: getSelectText('#n24CrossBorder'),
        severity: getSelectText('#n24Severity'),
        criterion: getSelectText('#n24Criterion'),
        operationalImpact: $('#n24OperationalImpact')?.value?.trim() || '—',
        financialImpact: $('#n24FinancialImpact')?.value?.trim() || '',
        thirdPartyRisk: $('#n24ThirdPartyRisk')?.value?.trim() || '—',
        containment: $('#n24Containment')?.value?.trim() || '—',
        recovery: $('#n24Recovery')?.value?.trim() || '—',
        externalSupport: getSelectText('#n24ExternalSupport'),
        backupAvailable: getSelectText('#n24BackupAvailable'),
        otherAuthorities: $('#n24OtherAuthorities')?.value?.trim() || '—',
        signerName: $('#n24SignerName')?.value?.trim() || '—',
        signerRole: $('#n24SignerRole')?.value?.trim() || '—',
        submitDate: $('#n24SubmitDate')?.value || '',
        submitTime: $('#n24SubmitTime')?.value || '',
        notes: $('#n24Notes')?.value?.trim() || '',
    };

    const def = buildNotif24hPdfDefinition(f);
    const entity = f.entity !== '—' ? f.entity.replace(/\s+/g, '_').toLowerCase() : 'entidade';
    const datePart = new Date().toISOString().slice(0, 10);
    const filename = `cncs_notificacao_24h_${entity}_${datePart}.pdf`;
    window.pdfMake.createPdf(def).download(filename);
}

// ─────────────────────────────────────────────────────────────
// Live sync relatório anual
// ─────────────────────────────────────────────────────────────

function wireLiveSync() {
    ['#cncsManualActivities', '#cncsManualRecs', '#cncsExtra']
        .forEach(id => $(id)?.addEventListener('input', renderTextPreviews));

    ['#cncsEntity', '#cncsPeriod'].forEach(id => {
        $(id)?.addEventListener('input', () => {
            setText('#pvEntity', $('#cncsEntity')?.value?.trim() || '—');
            const year = $('#cncsYear')?.value ?? '—';
            const period = $('#cncsPeriod')?.value?.trim() || '—';
            setText('#pvPeriod', `${year} · ${period}`);
        });
    });

    ['#cncsUsersAffected', '#cncsDuration'].forEach(id => {
        $(id)?.addEventListener('input', () => {
            setText('#pvUsersAffected', $('#cncsUsersAffected')?.value || '—');
            const dur = $('#cncsDuration')?.value;
            setText('#pvDuration', dur ? `${dur} h` : '—');
        });
    });

    ['#cncsReportDate', '#cncsSecurityOfficer', '#cncsSignature'].forEach(id => {
        $(id)?.addEventListener('input', renderSignature);
        $(id)?.addEventListener('change', renderSignature);
    });
}

function initAiGenerate() {
    const btn = document.getElementById('btnGenerateAI');
    if (btn) btn.addEventListener('click', generateAiNarrative);
}

async function generateAiNarrative() {
    const btn = document.getElementById('btnGenerateAI');
    const spinner = document.getElementById('aiGenerateSpinner');
    const status = document.getElementById('aiGenerateStatus');

    const year = document.querySelector('#cncsYear')?.value ?? new Date().getFullYear();
    const scope = document.querySelector('#cncsIncidentScope')?.value ?? 'relevant';
    const entityName = document.querySelector('#cncsEntity')?.value?.trim() || 'Entidade';

    // Estado: a gerar
    if (btn) { btn.disabled = true; btn.textContent = 'A gerar…'; }
    if (spinner) spinner.style.display = 'inline-block';
    if (status) { status.textContent = 'A consultar base de dados e a gerar narrativa…'; status.style.color = 'var(--muted)'; }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const res = await fetch('/api/cncs-reports/generate-narrative', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ year, scope, entity_name: entityName }),
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || `HTTP ${res.status}`);
        }

        const data = await res.json();

        if (!data.success) throw new Error(data.message || 'Erro desconhecido');

        const s = data.sections;

        // Preencher os textareas com o texto gerado pela IA
        if (s.section3) setTextareaValue('#cncsManualActivities', s.section3);
        if (s.section6) setTextareaValue('#cncsManualRecs', s.section6);
        if (s.section8) setTextareaValue('#cncsExtra', s.section8);

        // Guardar a secção 5 para uso no PDF (campo oculto ou variável)
        if (s.section5) {
            window._aiSection5 = s.section5;
            // Preencher campo de texto da secção 5 se existir
            setTextareaValue('#cncsManualSection5', s.section5);
        }

        // Re-renderizar o preview
        renderTextPreviews();
        if (typeof renderPreview === 'function') renderPreview();

        if (status) { status.textContent = '✓ Narrativa gerada com sucesso. Revê antes de exportar.'; status.style.color = 'var(--ok, #22c55e)'; }

    } catch (e) {
        console.error('Erro ao gerar narrativa IA:', e);
        if (status) { status.textContent = `Erro: ${e.message}`; status.style.color = 'var(--bad, #ef4444)'; }
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = '✨ Gerar com IA'; }
        if (spinner) spinner.style.display = 'none';
    }
}

/**
 * Define o valor de um textarea e dispara os eventos para sync do preview.
 */
function setTextareaValue(selector, value) {
    const el = document.querySelector(selector);
    if (!el) return;
    el.value = value;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
}

// ─────────────────────────────────────────────────────────────
// Init
// ─────────────────────────────────────────────────────────────

function init() {
    initTabs();
    initSteps();
    initUrgentTooltip();
    initStep2b();
    initAiGenerate();
    wireLiveSync();
    wireLiveSync24h();

    // Data padrão relatório anual
    const dateInput = $('#cncsReportDate');
    if (dateInput && !dateInput.value) {
        dateInput.value = new Date().toISOString().slice(0, 10);
    }

    // Data/hora padrão 24h (agora)
    const now = new Date();
    const submitDateEl = $('#n24SubmitDate');
    if (submitDateEl && !submitDateEl.value) {
        submitDateEl.value = now.toISOString().slice(0, 10);
    }
    const submitTimeEl = $('#n24SubmitTime');
    if (submitTimeEl && !submitTimeEl.value) {
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        submitTimeEl.value = `${hh}:${mm}`;
    }

    // Botões relatório anual
    $('#btnPreviewCNCS')?.addEventListener('click', renderPreview);

    $('#btnExportCNCS')?.addEventListener('click', () => {
        const format = $('#cncsFormat')?.value ?? 'pdf';
        if (format !== 'pdf') {
            alert('Apenas PDF disponível de momento. ODT requer integração com backend.');
            return;
        }
        exportPdfCNCS();
    });

    // Botões 24h
    $('#btnPreview24h')?.addEventListener('click', renderPreview24h);
    $('#btnExport24h')?.addEventListener('click', exportPdf24h);

    // Re-fetch ao mudar ano ou escopo
    ['#cncsYear', '#cncsIncidentScope'].forEach(id => {
        $(id)?.addEventListener('change', () => {
            renderPreview();
            if (document.getElementById('incListWrap')?.style.display !== 'none') {
                loadIncidentsForReport();
            }
        });
    });

    // Filtros da tabela de conformidade
    ['#complianceFrameworkFilter', '#complianceStatusFilter'].forEach(id => {
        $(id)?.addEventListener('change', () => loadComplianceTable(1));
    });

    // Primeiro render anual
    renderPreview();

    // Tabela de conformidade
    loadComplianceTable(1);
    setTimeout(handleIncidentAutoFill, 150);
}

// Expor para os botões de paginação inline no HTML
window.loadComplianceTable = loadComplianceTable;

// =============================================================
// AUTO-PREENCHIMENTO VIA INCIDENTE (NIS2 / 24H)
// =============================================================
async function handleIncidentAutoFill() {
    const params = new URLSearchParams(window.location.search);
    const incidentId = params.get('from_incident');

    if (!incidentId || incidentId === 'undefined') return;

    // Função inteligente: Só preenche e avisa o Preview se houver realmente um valor
    const fillField = (id, val) => {
        const el = document.getElementById(id);
        // O String(val) evita erros se o NIF for um número inteiro
        if (el && val !== null && val !== undefined && String(val).trim() !== '') {
            el.value = val;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
            el.dispatchEvent(new Event('keyup', { bubbles: true }));
        }
    };

    // 1. Força o clique na aba 24h
    const tab24 = document.getElementById('tabBtn24h');
    if (tab24) tab24.click();

    // 2. BLOCO ISOLADO: Tenta carregar a Empresa (Se falhar, não afeta o resto)
    try {
        const cRes = await fetch('/api/incidents/company-settings');
        if (cRes.ok) {
            const co = await cRes.json();
            fillField('n24Entity', co.entity_name);
            fillField('n24Nif', co.nif);
            fillField('n24Address', co.address);
            fillField('n24Name', co.ciso_name);
            fillField('n24Email', co.ciso_email);
            fillField('n24Phone', co.ciso_phone);
        }
    } catch (e) {
        console.warn("Aviso: Ignorado erro ao ler empresa.", e);
    }

    // 3. BLOCO ISOLADO: Carrega o Incidente e os seus detalhes
    try {
        const res = await fetch(`/api/incidents/${incidentId}`);
        if (res.ok) {
            const inc = await res.json();

            fillField('n24Title', inc.title);
            fillField('n24IncidentType', inc.incident_type || 'other');

            const dataIncidente = inc.detected_at || inc.created_at;
            const dataFormatada = dataIncidente ? new Date(dataIncidente).toLocaleString('pt-PT') : 'recentemente';
            const desc = inc.description || `Incidente detetado e registado ${dataFormatada}. A aguardar apuramento de impacto.`;

            fillField('n24Desc', desc);

            // 4. Força o PDF Preview a atualizar-se 
            if (typeof renderPreview24h === 'function') {
                setTimeout(() => {
                    renderPreview24h();
                    console.log("✨ Preview 24h renderizado com sucesso!");
                }, 300);
            }
        }
    } catch (e) {
        console.error("Erro ao puxar dados técnicos do incidente:", e);
    }
}
document.addEventListener('DOMContentLoaded', init);