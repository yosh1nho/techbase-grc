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
            const stepId = head.dataset.toggle;
            const step = document.getElementById(stepId);
            if (!step) return;
            step.classList.toggle('open');
            if (window.lucide) window.lucide.createIcons();
        });
    });
}

// ─────────────────────────────────────────────────────────────
// Mock data builder
// Substitui por fetch ao backend quando estiver pronto.
// ─────────────────────────────────────────────────────────────

function buildMockData({ year, scope }) {
    const incidentsTotal = 18;
    const incidentsRelevant = 6;

    const quarters = [
        { q: 'Q1', total: 4, types: 'Phishing (2), Malware (1), Login suspeito (1)' },
        { q: 'Q2', total: 6, types: 'Indisponibilidade (3), Backup (2), Config (1)' },
        { q: 'Q3', total: 5, types: 'Acesso indevido (2), Malware (2), Exfiltração (1)' },
        { q: 'Q4', total: 3, types: 'Configuração (2), Rede (1)' },
    ];

    const agg = {
        usersAffected: 2300,
        usersHint: 'Agregado de incidentes relev./substanciais',
        durationHours: 14.5,
        durationHint: 'Soma de janelas de indisponibilidade',
        geo: [
            { label: 'Portugal', value: 4 },
            { label: 'UE (fora PT)', value: 2 },
        ],
        crossBorder: true,
    };

    const activitiesAuto =
        `No ano ${year}, foram executadas avaliações de conformidade (organizacional e por ativo), ` +
        `com registo de evidências e revisão de controlos. Foram atualizadas políticas internas, ` +
        `mantido histórico e rastreabilidade via auditoria. Integrações de monitorização (Wazuh/Acronis) ` +
        `alimentaram a triagem de eventos e incidentes.`;

    const recsAuto =
        `Priorizar mitigação de lacunas críticas (ex.: inventário de ativos e testes de backup), ` +
        `formalizar periodicidade e responsáveis nas evidências, reforçar monitorização e resposta a incidentes, ` +
        `e manter auditoria contínua sobre alterações em papéis e permissões (RBAC).`;

    const measures = [
        {
            title: 'Backups: faltavam evidências de testes',
            detail: 'Criado plano mensal de testes com relatório anexado como evidência.',
            tags: ['Plano RF10', 'Evidência RF3', 'PR.IP-4'],
            status: 'Concluído',
        },
        {
            title: 'Inventário de ativos: incompleto',
            detail: 'Procedimento formal implementado, dashboard de ativos e responsável definido.',
            tags: ['Procedimento RF2', 'Ativos RF1', 'ID.GA-1'],
            status: 'Em progresso',
        },
    ];

    return {
        subtitle: `${scope === 'relevant' ? 'Relevante/substancial' : 'Todos os incidentes'} · Ano ${year}`,
        kpis: {
            incidentsTotal: scope === 'all' ? incidentsTotal : incidentsRelevant,
            incidentsRelevant,
            highRisks: 4,
        },
        quarters,
        aggregate: agg,
        activitiesAuto,
        recsAuto,
        measures,
    };
}

// ─────────────────────────────────────────────────────────────
// Render helpers
// ─────────────────────────────────────────────────────────────

function renderQuarterTable(rows) {
    const body = $('#pvQuarterBody');
    if (!body) return;
    body.innerHTML = '';
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
    if (crossBorder) {
        el.innerHTML = '<span class="pv-badge yes">Sim — potencial impacto transfronteiriço</span>';
    } else {
        el.innerHTML = '<span class="pv-badge no">Não identificado</span>';
    }
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
                    ${m.tags.map(t => `<span class="pv-tag">${t}</span>`).join('')}
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
    const a = $('#cncsManualActivities');
    const r = $('#cncsManualRecs');
    if (a && !a.value.trim()) a.value = data.activitiesAuto;
    if (r && !r.value.trim()) r.value = data.recsAuto;
}

function renderTextPreviews() {
    const actText = $('#cncsManualActivities')?.value?.trim();
    const recsText = $('#cncsManualRecs')?.value?.trim();
    const extraText = $('#cncsExtra')?.value?.trim();

    const pvAct = $('#pvActivitiesText');
    const pvRecs = $('#pvRecsText');
    const pvExtra = $('#pvExtraText');

    if (pvAct) {
        pvAct.textContent = actText || '—';
        pvAct.className = 'tb-content' + (actText ? '' : ' empty');
    }
    if (pvRecs) {
        pvRecs.textContent = recsText || '—';
        pvRecs.className = 'tb-content' + (recsText ? '' : ' empty');
    }
    if (pvExtra) {
        pvExtra.textContent = extraText || '—';
        pvExtra.className = 'tb-content' + (extraText ? '' : ' empty');
    }
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
// Main preview render
// ─────────────────────────────────────────────────────────────

function renderPreview() {
    const year = $('#cncsYear')?.value ?? '—';
    const scope = $('#cncsIncidentScope')?.value ?? 'relevant';
    const entity = $('#cncsEntity')?.value?.trim() || '—';
    const period = $('#cncsPeriod')?.value?.trim() || '—';

    const data = buildMockData({ year, scope });

    // Topbar
    setText('#pvSubtitle', data.subtitle);
    setText('#pvIncTotal', data.kpis.incidentsTotal);
    setText('#pvIncRelevant', data.kpis.incidentsRelevant);
    setText('#pvHighRisks', data.kpis.highRisks);

    // Identificação
    setText('#pvEntity', entity);
    setText('#pvPeriod', `${year} · ${period}`);

    // Trimestral
    renderQuarterTable(data.quarters);

    // Aggregate
    setText('#pvUsersAffected', data.aggregate.usersAffected.toLocaleString('pt-PT'));
    setText('#pvUsersAffectedHint', data.aggregate.usersHint);
    setText('#pvDuration', `${data.aggregate.durationHours} h`);
    setText('#pvDurationHint', data.aggregate.durationHint);
    renderGeo(data.aggregate.geo);
    renderCrossBorder(data.aggregate.crossBorder);

    // Medidas
    renderMeasures(data.measures);

    // Auto-fill manual fields if empty
    fillManualFromAutoIfEmpty(data);

    // Textos finais
    renderTextPreviews();

    // Assinatura
    renderSignature();

    // Re-render lucide icons inside dynamic HTML
    if (window.lucide) window.lucide.createIcons();
}

// ─────────────────────────────────────────────────────────────
// PDF Export
// ─────────────────────────────────────────────────────────────

function buildCncsPdfDefinition(form, data) {
    const now = new Date();
    const dateStr = now.toISOString().slice(0, 10);

    const quarterRows = (data.quarters || []).map(r => [
        { text: r.q, bold: true },
        { text: String(r.total), alignment: 'center' },
        { text: r.types || '—', color: '#555' },
    ]);

    const geoLines = (data.aggregate?.geo || []).map(g => `• ${g.label}: ${g.value}`).join('\n') || '—';

    const measures = (data.measures || []).map(m =>
        `${m.title}\n   ${m.detail} (${m.status})`
    );

    return {
        pageSize: 'A4',
        pageMargins: [50, 60, 50, 60],
        footer: (currentPage, pageCount) => ({
            text: `Relatório Anual CNCS · ${safe(form.year)} · Página ${currentPage} de ${pageCount}`,
            alignment: 'center',
            fontSize: 8,
            color: '#888',
            margin: [0, 14, 0, 0],
        }),
        styles: {
            h0:          { fontSize: 20, bold: true, alignment: 'center', color: '#0b1220' },
            sub0:        { fontSize: 10, alignment: 'center', color: '#666', margin: [0, 4, 0, 0] },
            h1:          { fontSize: 12, bold: true, margin: [0, 18, 0, 6], color: '#0b1220' },
            p:           { fontSize: 10, lineHeight: 1.4, color: '#1a2535' },
            label:       { fontSize: 10, bold: true, color: '#0b1220' },
            muted:       { fontSize: 9, color: '#666' },
            tableHeader: { bold: true, fontSize: 9, color: '#444', fillColor: '#f5f7fc' },
        },
        defaultStyle: { font: 'Roboto', fontSize: 10 },
        content: [
            // Capa
            { text: 'Relatório Anual de Segurança', style: 'h0' },
            { text: `Modelo CNCS · ${safe(form.entityName)} · ${safe(form.year)}`, style: 'sub0' },
            { text: `Gerado em ${dateStr}`, style: 'muted', alignment: 'center', margin: [0, 2, 0, 20] },

            // 1 — Entidade
            { text: '1 — Designação da entidade', style: 'h1' },
            { text: safe(form.entityName), style: 'p' },

            // 2 — Ano / Período
            { text: '2 — Ano civil e período de tempo do relatório', style: 'h1' },
            {
                table: {
                    widths: [120, '*'],
                    body: [
                        [{ text: 'Ano civil', style: 'tableHeader' }, safe(form.year)],
                        [{ text: 'Período', style: 'tableHeader' }, safe(form.period, '01-01 a 31-12')],
                        [{ text: 'Escopo de incidentes', style: 'tableHeader' }, form.scope === 'relevant' ? 'Apenas relevante / substancial' : 'Todos'],
                    ],
                },
                layout: 'lightHorizontalLines',
                fontSize: 10,
            },

            // 3 — Atividades
            { text: '3 — Descrição sumária das principais atividades desenvolvidas em matéria de segurança das redes e dos serviços de informação', style: 'h1' },
            { text: safe(form.activitiesText), style: 'p' },

            // 4 — Estatística trimestral
            { text: '4 — Estatística trimestral de todos os incidentes, com indicação do número e do tipo dos incidentes', style: 'h1' },
            {
                table: {
                    headerRows: 1,
                    widths: [50, 50, '*'],
                    body: [
                        [
                            { text: 'Trimestre', style: 'tableHeader' },
                            { text: 'N.º', style: 'tableHeader', alignment: 'center' },
                            { text: 'Tipo(s)', style: 'tableHeader' },
                        ],
                        ...quarterRows,
                    ],
                },
                layout: 'lightHorizontalLines',
                fontSize: 10,
            },

            // 5 — Análise agregada
            { text: '5 — Análise agregada dos incidentes de segurança com impacto relevante ou substancial', style: 'h1' },

            { text: '5.1 — Número de utilizadores afetados pela perturbação do serviço', style: 'label', margin: [0, 0, 0, 4] },
            { text: `${safe(data.aggregate?.usersAffected?.toLocaleString('pt-PT'))}\n${safe(data.aggregate?.usersHint, '')}`, style: 'p', margin: [0, 0, 0, 10] },

            { text: '5.2 — Duração dos incidentes', style: 'label', margin: [0, 0, 0, 4] },
            { text: `${safe(data.aggregate?.durationHours)} h\n${safe(data.aggregate?.durationHint, '')}`, style: 'p', margin: [0, 0, 0, 10] },

            { text: '5.3 — Distribuição geográfica e impacto transfronteiriço', style: 'label', margin: [0, 0, 0, 4] },
            {
                text: `${geoLines}\nImpacto transfronteiriço: ${data.aggregate?.crossBorder ? 'Sim (potencial)' : 'Não'}`,
                style: 'p',
                margin: [0, 0, 0, 0],
            },

            // 6 — Recomendações
            { text: '6 — Recomendações de atividades, de medidas ou de práticas que promovam a melhoria da segurança das redes e dos sistemas de informação', style: 'h1' },
            { text: safe(form.recsText), style: 'p' },

            // 7 — Medidas
            { text: '7 — Problemas identificados e medidas implementadas na sequência dos incidentes', style: 'h1' },
            measures.length
                ? { ul: measures, fontSize: 10, lineHeight: 1.4 }
                : { text: '—', style: 'p' },

            // 8 — Outra informação
            { text: '8 — Qualquer outra informação relevante', style: 'h1' },
            { text: safe(form.extraText), style: 'p' },

            // Fecho
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
                layout: 'lightHorizontalLines',
                fontSize: 10,
            },
        ],
    };
}

function exportPdfCNCS() {
    if (!window.pdfMake?.createPdf) {
        alert('pdfmake ainda não carregou. Aguarda 2s e tenta novamente.');
        return;
    }

    const year = $('#cncsYear')?.value ?? '—';
    const scope = $('#cncsIncidentScope')?.value ?? 'relevant';
    const data = buildMockData({ year, scope });

    const form = {
        year,
        scope,
        entityName:      $('#cncsEntity')?.value?.trim() || '—',
        period:          $('#cncsPeriod')?.value?.trim() || '',
        activitiesText:  $('#cncsManualActivities')?.value?.trim() || data.activitiesAuto,
        recsText:        $('#cncsManualRecs')?.value?.trim() || data.recsAuto,
        extraText:       $('#cncsExtra')?.value?.trim() || '—',
        reportDate:      $('#cncsReportDate')?.value || '',
        securityOfficer: $('#cncsSecurityOfficer')?.value?.trim() || '',
        signature:       $('#cncsSignature')?.value?.trim() || '',
    };

    const def = buildCncsPdfDefinition(form, data);
    const filename = `cncs_relatorio_${year}_${new Date().toISOString().slice(0, 10)}.pdf`;
    window.pdfMake.createPdf(def).download(filename);
}

// ─────────────────────────────────────────────────────────────
// Live sync — textos manuais e assinatura
// ─────────────────────────────────────────────────────────────

function wireLiveSync() {
    // Textarea manual → preview de texto
    ['#cncsManualActivities', '#cncsManualRecs', '#cncsExtra'].forEach(id => {
        $(id)?.addEventListener('input', renderTextPreviews);
    });

    // Parâmetros de identificação → preview
    ['#cncsEntity', '#cncsPeriod'].forEach(id => {
        $(id)?.addEventListener('input', () => {
            setText('#pvEntity', $('#cncsEntity')?.value?.trim() || '—');
            const year = $('#cncsYear')?.value ?? '—';
            const period = $('#cncsPeriod')?.value?.trim() || '—';
            setText('#pvPeriod', `${year} · ${period}`);
        });
    });

    // Assinatura
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

    // Set today as default date
    const dateInput = $('#cncsReportDate');
    if (dateInput && !dateInput.value) {
        dateInput.value = new Date().toISOString().slice(0, 10);
    }

    // Buttons
    $('#btnPreviewCNCS')?.addEventListener('click', renderPreview);

    $('#btnExportCNCS')?.addEventListener('click', () => {
        const format = $('#cncsFormat')?.value ?? 'pdf';
        if (format !== 'pdf') {
            alert('Mock: apenas PDF disponível de momento. ODT requer integração com backend.');
            return;
        }
        exportPdfCNCS();
    });

    // Auto-preview on param change
    ['#cncsYear', '#cncsIncidentScope'].forEach(id => {
        $(id)?.addEventListener('change', renderPreview);
    });

    wireLiveSync();

    // First render
    renderPreview();
}

document.addEventListener('DOMContentLoaded', init);
