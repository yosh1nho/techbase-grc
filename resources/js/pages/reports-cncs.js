// resources/js/pages/reports-cncs.js

const $ = (s) => document.querySelector(s);

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

function renderQuarterTable(rows) {
    const body = $('#pvQuarterBody');
    if (!body) return;
    body.innerHTML = '';
    if (!rows?.length) {
        body.innerHTML = '<tr><td colspan="3" class="muted" style="font-size:12px;padding:10px 0">Sem incidentes registados.</td></tr>';
        return;
    }
    rows.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="q-label">${r.q}</span></td>
            <td><span class="q-count">${r.total}</span></td>
            <td><span class="q-types">${r.types}</span></td>
        `;
        body.appendChild(tr);
    });
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
    const year = $('#cncsYear')?.value ?? '—';
    const actEl = $('#cncsManualActivities');
    const recEl = $('#cncsManualRecs');

    if (actEl && !actEl.value.trim()) {
        actEl.value =
            `No ano ${year}, foram executadas avaliações de conformidade (organizacional e por ativo), ` +
            `com registo de evidências e revisão de controlos. Foram atualizadas políticas internas, ` +
            `mantido histórico e rastreabilidade via auditoria. ` +
            `Integrações de monitorização alimentaram a triagem de eventos e incidentes.`;
    }

    if (recEl && !recEl.value.trim()) {
        recEl.value =
            `Priorizar mitigação de lacunas críticas (inventário de ativos, testes de backup), ` +
            `formalizar periodicidade e responsáveis nas evidências, reforçar monitorização e ` +
            `resposta a incidentes, e manter auditoria contínua sobre alterações em papéis e permissões.`;
    }
}


// ─────────────────────────────────────────────────────────────
// Preencher com IA — chama Gemini via CncsReportController
// ─────────────────────────────────────────────────────────────

async function aiFillReport() {
    const btn = $('#btnAiFill');
    const label = $('#btnAiFillLabel');
    const spinner = $('#btnAiFillSpinner');
    if (!btn) return;

    // Garante dados carregados — reutiliza o cachedReportData existente
    const data = cachedReportData || await fetchReportData();
    if (!data) {
        alert('Sem dados carregados. Clica em "Atualizar prévia" primeiro.');
        return;
    }

    const year = $('#cncsYear')?.value ?? new Date().getFullYear();
    const scope = $('#cncsIncidentScope')?.value ?? 'relevant';

    // — UI: estado loading —
    btn.disabled = true;
    label.textContent = 'A gerar com IA…';
    if (spinner) spinner.style.display = 'inline-block';

    try {
        const res = await fetch('/api/cncs-reports/ai-summary', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ year, scope, report_data: data }),
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.error || `HTTP ${res.status}`);
        }

        const result = await res.json();

        // Preenche as 3 textareas
        const map = [
            ['#cncsManualActivities', result.activities],
            ['#cncsManualRecs', result.recommendations],
            ['#cncsExtra', result.extra_info],
        ];
        map.forEach(([sel, val]) => {
            const el = $(sel);
            if (el && val) el.value = val;
        });

        // Atualiza a preview imediatamente
        renderTextPreviews();

        // — UI: sucesso —
        btn.style.borderColor = 'rgba(52,211,153,.5)';
        btn.style.color = '#34d399';
        label.textContent = '✓ Preenchido com IA';
        if (window.lucide) window.lucide.createIcons();

        setTimeout(() => {
            btn.style.borderColor = 'rgba(99,102,241,.35)';
            btn.style.color = '#a78bfa';
            label.textContent = 'Preencher com IA';
            btn.disabled = false;
        }, 3500);

    } catch (e) {
        console.error('[aiFillReport]', e);

        // — UI: erro —
        btn.style.borderColor = 'rgba(239,68,68,.5)';
        btn.style.color = '#f87171';
        label.textContent = '✗ Erro — tenta novamente';

        setTimeout(() => {
            btn.style.borderColor = 'rgba(99,102,241,.35)';
            btn.style.color = '#a78bfa';
            label.textContent = 'Preencher com IA';
            btn.disabled = false;
        }, 3500);
    } finally {
        if (spinner) spinner.style.display = 'none';
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

            { text: '5 — Análise agregada dos incidentes', style: 'h1' },
            { text: `Utilizadores afetados: ${safe(form.usersAffected)}`, style: 'p' },
            { text: `Duração: ${safe(form.duration)} h`, style: 'p', margin: [0, 0, 0, 10] },

            { text: '6 — Recomendações de melhoria', style: 'h1' },
            { text: safe(form.recsText), style: 'p' },

            { text: '7 — Problemas identificados e medidas implementadas', style: 'h1' },
            measures.length
                ? { ul: measures, fontSize: 10, lineHeight: 1.4 }
                : { text: '—', style: 'p' },

            // ── Conformidade NIS2 / QNRCS ──────────────────────────────
            { text: '7b — Conformidade NIS2 & QNRCS', style: 'h1' },
            ...(buildComplianceRows(data)),

            { text: '8 — Outra informação relevante', style: 'h1' },
            { text: safe(form.extraText), style: 'p' },

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


function buildComplianceRows(data) {
    const compliance = data?.compliance?.data ?? [];

    if (!compliance.length) {
        return [{ text: 'Sem controlos avaliados.', style: 'p' }];
    }

    const statusConfig = {
        compliant: { label: 'Conforme', color: '#16a34a' },
        partial: { label: 'Parcial', color: '#d97706' },
        non_compliant: { label: 'Não conforme', color: '#dc2626' },
    };

    // Agrupa por framework
    const byFramework = {};
    compliance.forEach(row => {
        const fw = row.framework || 'Outro';
        if (!byFramework[fw]) byFramework[fw] = [];
        byFramework[fw].push(row);
    });

    const blocks = [];

    Object.entries(byFramework).forEach(([fw, rows]) => {
        // Cabeçalho do framework
        blocks.push({
            text: fw,
            fontSize: 9,
            bold: true,
            color: '#0369a1',
            margin: [0, 8, 0, 4],
        });

        // Tabela de controlos
        const tableBody = [
            [
                { text: 'Controlo', style: 'tableHeader' },
                { text: 'Grupo', style: 'tableHeader' },
                { text: 'Descrição', style: 'tableHeader' },
                { text: 'Estado', style: 'tableHeader' },
                { text: 'Avaliado em', style: 'tableHeader' },
            ],
        ];

        rows.forEach(row => {
            const s = statusConfig[row.status] || statusConfig.non_compliant;
            tableBody.push([
                { text: row.control_code ?? '—', fontSize: 9, color: '#0369a1', bold: true },
                { text: row.group_code ?? '—', fontSize: 9, color: '#555' },
                { text: row.description ?? '—', fontSize: 9 },
                { text: s.label, fontSize: 9, bold: true, color: s.color },
                {
                    text: row.assessed_at
                        ? new Date(row.assessed_at).toLocaleDateString('pt-PT')
                        : '—',
                    fontSize: 9, color: '#555',
                },
            ]);
        });

        blocks.push({
            table: {
                headerRows: 1,
                widths: [55, 45, '*', 60, 55],
                body: tableBody,
            },
            layout: 'lightHorizontalLines',
            fontSize: 9,
            margin: [0, 0, 0, 6],
        });
    });

    // Sumário de conformidade
    const total = compliance.length;
    const conforme = compliance.filter(r => r.status === 'compliant').length;
    const parcial = compliance.filter(r => r.status === 'partial').length;
    const naoConf = compliance.filter(r => r.status === 'non_compliant').length;

    blocks.push({
        text: `Total avaliado: ${total}  |  Conformes: ${conforme}  |  Parciais: ${parcial}  |  Não conformes: ${naoConf}`,
        fontSize: 9,
        color: '#555',
        italics: true,
        margin: [0, 2, 0, 8],
    });

    return blocks;
}

async function exportPdfCNCS() {
    if (!window.pdfMake?.createPdf) {
        alert('pdfmake ainda não carregou. Aguarda 2s e tenta novamente.');
        return;
    }

    const year = $('#cncsYear')?.value ?? '—';
    const scope = $('#cncsIncidentScope')?.value ?? 'relevant';
    const data = cachedReportData || await fetchReportData();

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
        reportDate: $('#cncsReportDate')?.value || '',
        securityOfficer: $('#cncsSecurityOfficer')?.value?.trim() || '',
        signature: $('#cncsSignature')?.value?.trim() || '',
    };

    const def = buildCncsPdfDefinition(form, data);
    const filename = `cncs_relatorio_${year}_${new Date().toISOString().slice(0, 10)}.pdf`;

    // AQUI: Usar a variável 'def' em vez de 'docDefinition'
    const pdfDoc = pdfMake.createPdf(def);

    pdfDoc.download(filename);

    const saveToDocs = $('#cncsSaveAsDoc')?.value === 'yes';
    if (saveToDocs) {
        pdfDoc.getBlob((blob) => {
            saveReportToServer(blob, filename);
        });
    }
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


// Função para enviar o PDF gerado para o módulo de Documentos & Evidências
async function saveReportToServer(blob, filename) {
    const formData = new FormData();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    formData.append('file', blob, filename);
    formData.append('title', filename.replace('.pdf', ''));
    formData.append('type', 'report'); // Categoriza como relatório
    formData.append('version', '1.0');

    try {
        const response = await fetch("/api/documents/upload", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json"
            },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert("Sucesso: Relatório guardado em Documentos & Evidências.");
        } else {
            throw new Error(result.message || "Erro desconhecido");
        }
    } catch (error) {
        console.error("Erro ao guardar relatório:", error);
        alert("Não foi possível guardar o relatório no sistema: " + error.message);
    }
}
// ─────────────────────────────────────────────────────────────
// Init
// ─────────────────────────────────────────────────────────────

function init() {
    initTabs();
    initSteps();
    initUrgentTooltip();
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
    $('#btnAiFill')?.addEventListener('click', aiFillReport);

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
        $(id)?.addEventListener('change', renderPreview);
    });

    // Filtros da tabela de conformidade
    ['#complianceFrameworkFilter', '#complianceStatusFilter'].forEach(id => {
        $(id)?.addEventListener('change', () => loadComplianceTable(1));
    });

    // Primeiro render anual
    renderPreview();

    // Tabela de conformidade
    loadComplianceTable(1);
}

// Expor para os botões de paginação inline no HTML
window.loadComplianceTable = loadComplianceTable;

document.addEventListener('DOMContentLoaded', init);