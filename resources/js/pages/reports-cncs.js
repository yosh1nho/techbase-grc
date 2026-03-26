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

// ─────────────────────────────────────────────────────────────
// Step accordion
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
// Tooltips urgente
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

    // ícone de info ao lado do label
    $('#urgentInfoIcon')?.addEventListener('mouseenter', show);
    $('#urgentInfoIcon')?.addEventListener('mouseleave', hide);
}

// ─────────────────────────────────────────────────────────────
// Carregar dados reais da API
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
// Render helpers
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

    const params = new URLSearchParams({
        framework,
        status,
        page,
        per_page: COMPLIANCE_PER_PAGE,
    });

    const tbody = $('#complianceTbody');
    const loading = $('#complianceLoading');
    const pager = $('#compliancePager');

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

        // Agrupar por framework para cabeçalhos visuais
        let lastFw = null;
        data.data.forEach(row => {
            // Cabeçalho de separação por framework
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
// Render principal
// ─────────────────────────────────────────────────────────────

async function renderPreview() {
    const year = $('#cncsYear')?.value ?? '—';
    const scope = $('#cncsIncidentScope')?.value ?? 'relevant';
    const entity = $('#cncsEntity')?.value?.trim() || '—';
    const period = $('#cncsPeriod')?.value?.trim() || '—';

    // Mostrar spinner enquanto carrega
    const spinner = $('#pvLoadingSpinner');
    if (spinner) spinner.style.display = 'flex';

    const data = await fetchReportData();

    if (spinner) spinner.style.display = 'none';

    if (!data) {
        // fallback se a API falhar
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

    // Secção 5 — análise agregada (mantém valores manuais se preenchidos)
    // Os utilizadores preenchem manualmente afectados/duração/geo no formulário
    // — se não estiver preenchido, mostra '—'
    const usersEl = $('#cncsUsersAffected');
    const durEl = $('#cncsDuration');
    setText('#pvUsersAffected', usersEl?.value || '—');
    setText('#pvUsersAffectedHint', '');
    setText('#pvDuration', durEl?.value ? `${durEl.value} h` : '—');
    setText('#pvDurationHint', '');

    renderGeo([]); // geo é preenchida manualmente via formulário
    renderCrossBorder(false);

    renderMeasures(data.measures);
    fillManualFromAutoIfEmpty(data);
    renderTextPreviews();
    renderSignature();

    if (window.lucide) window.lucide.createIcons();
}

// ─────────────────────────────────────────────────────────────
// Exportação PDF (mantida como estava — usa dados do DOM)
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

async function exportPdfCNCS() {
    if (!window.pdfMake?.createPdf) {
        alert('pdfmake ainda não carregou. Aguarda 2s e tenta novamente.');
        return;
    }

    const year = $('#cncsYear')?.value ?? '—';
    const scope = $('#cncsIncidentScope')?.value ?? 'relevant';
    const data = cachedReportData || await fetchReportData();

    const form = {
        year,
        scope,
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
    window.pdfMake.createPdf(def).download(filename);
}

// ─────────────────────────────────────────────────────────────
// Live sync
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

    // Secção 5 — campos manuais
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

// ─────────────────────────────────────────────────────────────
// Init
// ─────────────────────────────────────────────────────────────

function init() {
    initSteps();
    initUrgentTooltip();

    const dateInput = $('#cncsReportDate');
    if (dateInput && !dateInput.value) {
        dateInput.value = new Date().toISOString().slice(0, 10);
    }

    // Botões
    $('#btnPreviewCNCS')?.addEventListener('click', renderPreview);

    $('#btnExportCNCS')?.addEventListener('click', () => {
        const format = $('#cncsFormat')?.value ?? 'pdf';
        if (format !== 'pdf') {
            alert('Apenas PDF disponível de momento. ODT requer integração com backend.');
            return;
        }
        exportPdfCNCS();
    });

    // Re-fetch ao mudar ano ou escopo
    ['#cncsYear', '#cncsIncidentScope'].forEach(id => {
        $(id)?.addEventListener('change', renderPreview);
    });

    // Filtros da tabela de conformidade
    ['#complianceFrameworkFilter', '#complianceStatusFilter'].forEach(id => {
        $(id)?.addEventListener('change', () => loadComplianceTable(1));
    });

    wireLiveSync();

    // Primeiro render
    renderPreview();

    // Carregar tabela de conformidade (separado — pode ser lento)
    loadComplianceTable(1);
}

// Expor para os botões de paginação inline no HTML
window.loadComplianceTable = loadComplianceTable;

document.addEventListener('DOMContentLoaded', init);