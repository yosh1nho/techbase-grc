// resources/js/pages/reports-cncs.js

const $ = (s) => document.querySelector(s);

function setText(id, value) {
    const el = $(id);
    if (el) el.textContent = value ?? '—';
}

// MOCK data builder (depois substituímos por fetch ao backend)
function buildMockData({ year, scope }) {
    // Em “relevant”, mostramos menos incidentes e análise só do que é relevante/substancial
    const incidentsTotal = 18;
    const incidentsRelevant = 6;

    const quarters = [
        { q: 'Q1', total: 4, types: 'Phishing (2), Malware (1), Login suspeito (1)' },
        { q: 'Q2', total: 6, types: 'Indisponibilidade (3), Backup (2), Config (1)' },
        { q: 'Q3', total: 5, types: 'Acesso indevido (2), Malware (2), Exfiltração (1)' },
        { q: 'Q4', total: 3, types: 'Configuração (2), Rede (1)' },
    ];

    // Análise agregada (só relevante/substancial)
    const agg = {
        usersAffected: 2300,
        usersHint: 'Agregado de incidentes relevantes/substanciais.',
        durationHours: 14.5,
        durationHint: 'Soma de janelas de indisponibilidade / impacto.',
        geo: [
            { label: 'Portugal', value: 4 },
            { label: 'UE (fora PT)', value: 2 },
        ],
        crossBorder: true,
    };

    // atividades/recomendações auto geradas
    const activitiesAuto =
        `No ano ${year}, foram executadas avaliações de conformidade (org e por ativo), ` +
        `com registo de evidências e revisão de controlos. Foram atualizadas políticas internas, ` +
        `mantido histórico e rastreabilidade via auditoria. Integrações de monitorização (Wazuh/Acronis) ` +
        `alimentaram a triagem de eventos e incidentes.`;

    const recsAuto =
        `Priorizar mitigação de lacunas críticas (ex.: inventário de ativos e testes de backup), ` +
        `formalizar periodicidade e responsáveis nas evidências, reforçar monitorização e resposta a incidentes, ` +
        `e manter auditoria contínua sobre alterações em papéis/permissões (RBAC).`;

    const measures = [
        {
            title: 'Backups: faltavam evidências de testes',
            detail: 'Criado plano mensal de testes + relatório anexado como evidência.',
            tags: ['Plano RF10', 'Evidência RF3', 'Controlo PR.IP-4'],
            status: 'Concluído',
        },
        {
            title: 'Inventário: incompleto',
            detail: 'Procedimento formal + dashboard de ativos e responsável definido.',
            tags: ['Procedimento RF2', 'Ativos RF1', 'Controlo ID.GA-1'],
            status: 'Em progresso',
        }
    ];

    return {
        subtitle: `Entidade selecionada • Ano ${year} • Escopo: ${scope === 'relevant' ? 'relevante/substancial' : 'todos'}`,
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

function renderQuarterTable(rows) {
    const body = $('#pvQuarterBody');
    if (!body) return;
    body.innerHTML = '';
    rows.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td><b>${r.q}</b></td><td>${r.total}</td><td class="muted">${r.types}</td>`;
        body.appendChild(tr);
    });
}

function renderGeo(geo) {
    const el = $('#pvGeo');
    if (!el) return;
    if (!geo?.length) { el.textContent = '—'; return; }

    el.innerHTML = geo.map(x => `<div class="muted">• ${x.label}: <b>${x.value}</b></div>`).join('');
}

function renderMeasures(items) {
    const wrap = $('#pvMeasures');
    if (!wrap) return;
    wrap.innerHTML = '';

    items.forEach(m => {
        const div = document.createElement('div');
        div.className = 'measure-item';
        div.innerHTML = `
      <div class="measure-left">
        <div><b>${m.title}</b></div>
        <div class="muted">${m.detail}</div>
      </div>
      <div class="measure-right">
        ${m.tags.map(t => `<span class="pill">${t}</span>`).join('')}
        <span class="tag ${m.status === 'Concluído' ? 'ok' : 'warn'}"><span class="s"></span> ${m.status}</span>
      </div>
    `;
        wrap.appendChild(div);
    });
}

function fillManualFromAutoIfEmpty(data) {
    const a = $('#cncsManualActivities');
    const r = $('#cncsManualRecs');

    if (a && !a.value.trim()) a.value = data.activitiesAuto;
    if (r && !r.value.trim()) r.value = data.recsAuto;
}

function renderPreview() {
    const year = $('#cncsYear')?.value ?? '—';
    const scope = $('#cncsIncidentScope')?.value ?? 'relevant';

    const data = buildMockData({ year, scope });

    setText('#pvSubtitle', data.subtitle);
    setText('#pvIncTotal', data.kpis.incidentsTotal);
    setText('#pvIncRelevant', data.kpis.incidentsRelevant);
    setText('#pvHighRisks', data.kpis.highRisks);

    renderQuarterTable(data.quarters);

    setText('#pvUsersAffected', data.aggregate.usersAffected);
    setText('#pvUsersAffectedHint', data.aggregate.usersHint);

    setText('#pvDuration', `${data.aggregate.durationHours} h`);
    setText('#pvDurationHint', data.aggregate.durationHint);

    renderGeo(data.aggregate.geo);
    setText('#pvCrossBorder', data.aggregate.crossBorder ? 'Sim (potencial impacto transfronteiriço)' : 'Não');

    renderMeasures(data.measures);

    // preencher texto manual (só se estiver vazio)
    fillManualFromAutoIfEmpty(data);

    // texto final (mostra o que vai pro PDF)
    setText('#pvActivitiesText', $('#cncsManualActivities')?.value || '—');
    setText('#pvRecsText', $('#cncsManualRecs')?.value || '—');
    setText('#pvExtraText', $('#cncsExtra')?.value || '—');
}

// ===== PDF Export (CNCS Relatório Anual) =====
function riskLabelPT(x) {
    if (x == null) return "—";
    return String(x);
}

function safe(v, dash = "—") {
    const s = (v ?? "").toString().trim();
    return s ? s : dash;
}

function buildCncsPdfDefinition(form, data) {
    const now = new Date();
    const dateStr = now.toISOString().slice(0, 10);

    // 4 — tabela trimestral
    const quarterRows = (data.quarters || []).map(r => [r.q, String(r.total), r.types || "—"]);

    // 5.3 — distribuição geográfica
    const geoLines = (data.aggregate?.geo || []).map(g => `• ${g.label}: ${g.value}`);

    // 7 — medidas (problemas + medidas)
    const measures = (data.measures || []).map(m => ({
        title: m.title,
        detail: m.detail,
        status: m.status
    }));

    return {
        pageSize: "A4",
        pageMargins: [44, 54, 44, 54],
        footer: (currentPage, pageCount) => ({
            text: `Relatório anual CNCS (mock) • Página ${currentPage} de ${pageCount}`,
            alignment: "center",
            fontSize: 9,
            margin: [0, 12, 0, 0],
        }),
        styles: {
            h0: { fontSize: 18, bold: true, alignment: "center" },
            h1: { fontSize: 13, bold: true, margin: [0, 14, 0, 6] },
            p: { fontSize: 10, lineHeight: 1.25 },
            muted: { fontSize: 9, color: "#444" },
            label: { fontSize: 10, bold: true },
            small: { fontSize: 9 },
            tableHeader: { bold: true, fontSize: 10 },
            boxTitle: { fontSize: 10, bold: true },
        },
        defaultStyle: { font: "Roboto" },
        content: [
            { text: "Relatório anual", style: "h0" },
            { text: "Modelo CNCS (mock funcional)", style: "muted", alignment: "center", margin: [0, 6, 0, 0] },
            { text: `Gerado em: ${dateStr}`, style: "muted", alignment: "center", margin: [0, 2, 0, 0] },

            // 1
            { text: "1 — Designação da entidade", style: "h1" },
            { text: safe(form.entityName), style: "p" },

            // 2
            { text: "2 — Ano civil e período de tempo do relatório", style: "h1" },
            {
                table: {
                    widths: ["*", "*"],
                    body: [
                        [{ text: "Ano civil", style: "tableHeader" }, safe(form.year)],
                        [{ text: "Período", style: "tableHeader" }, safe(form.period, "01-01 a 31-12")],
                    ],
                },
                layout: "lightHorizontalLines",
                fontSize: 10,
            },

            // 3
            { text: "3 — Descrição sumária das principais atividades desenvolvidas em matéria de segurança das redes e dos serviços de informação", style: "h1" },
            { text: safe(form.activitiesText), style: "p" },

            // 4
            { text: "4 — Estatística trimestral de todos os incidentes, com indicação do número e do tipo dos incidentes", style: "h1" },
            {
                table: {
                    headerRows: 1,
                    widths: [50, 60, "*"],
                    body: [
                        [{ text: "Trimestre", style: "tableHeader" }, { text: "N.º", style: "tableHeader" }, { text: "Tipo(s)", style: "tableHeader" }],
                        ...quarterRows,
                    ],
                },
                layout: "lightHorizontalLines",
                fontSize: 10,
            },
            { text: `Nota (escopo): ${form.scope === "relevant" ? "relevante/substancial (com análise agregada na secção 5)" : "todos os incidentes"}`, style: "muted", margin: [0, 6, 0, 0] },

            // 5
            { text: "5 — Análise agregada dos incidentes de segurança com impacto relevante ou substancial", style: "h1" },
            { text: "5.1 — Número de utilizadores afetados pela perturbação do serviço", style: "label" },
            { text: `${safe(data.aggregate?.usersAffected)}\n${safe(data.aggregate?.usersHint, "")}`, style: "p", margin: [0, 4, 0, 8] },

            { text: "5.2 — Duração dos incidentes", style: "label" },
            { text: `${safe(data.aggregate?.durationHours)} h\n${safe(data.aggregate?.durationHint, "")}`, style: "p", margin: [0, 4, 0, 8] },

            { text: "5.3 — Distribuição geográfica e impacto transfronteiriço", style: "label" },
            {
                text: [
                    geoLines.length ? geoLines.join("\n") : "—",
                    `\nImpacto transfronteiriço: ${data.aggregate?.crossBorder ? "Sim (potencial)" : "Não"}`,
                ].join("\n"),
                style: "p",
                margin: [0, 4, 0, 0],
            },

            // 6
            { text: "6 — Recomendações de atividades, de medidas ou de práticas que promovam a melhoria da segurança das redes e dos sistemas de informação", style: "h1" },
            { text: safe(form.recsText), style: "p" },

            // 7
            { text: "7 — Problemas identificados e medidas implementadas na sequência dos incidentes", style: "h1" },
            measures.length
                ? {
                    ul: measures.map(m => `${m.title} — ${m.detail} (${m.status})`),
                    fontSize: 10,
                }
                : { text: "—", style: "p" },

            // 8
            { text: "8 — Qualquer outra informação relevante", style: "h1" },
            { text: safe(form.extraText), style: "p" },

            // Fecho / assinatura (do template)
            { text: " ", margin: [0, 10, 0, 0] },
            {
                table: {
                    widths: ["*", "*"],
                    body: [
                        [{ text: "Data:", style: "tableHeader" }, safe(form.reportDate, dateStr)],
                        [{ text: "Responsável de segurança:", style: "tableHeader" }, safe(form.securityOfficer)],
                        [{ text: "Assinatura do Responsável de segurança:", style: "tableHeader" }, safe(form.signature, "____________________________")],
                    ],
                },
                layout: "lightHorizontalLines",
                fontSize: 10,
            },
        ],
    };
}

function exportPdfCNCS() {
    const ready = window.pdfMake && window.pdfMake.createPdf;
    if (!ready) {
        alert("pdfmake ainda não carregou. Tenta novamente em 2s.");
        return;
    }

    const year = $('#cncsYear')?.value ?? '—';
    const scope = $('#cncsIncidentScope')?.value ?? 'relevant';

    // Reusa o mesmo builder do preview (mock)
    const data = buildMockData({ year, scope });

    // Campos do formulário (alguns podem não existir ainda no blade — nesse caso ficam "—")
    const form = {
        year,
        scope,
        entityName: $('#cncsEntityName')?.value || '—',
        period: $('#cncsPeriod')?.value || '',

        // textos manuais (já existem no teu JS) :contentReference[oaicite:5]{index=5}
        activitiesText: $('#cncsManualActivities')?.value || data.activitiesAuto,
        recsText: $('#cncsManualRecs')?.value || data.recsAuto,
        extraText: $('#cncsExtra')?.value || '—',

        reportDate: $('#cncsReportDate')?.value || '',
        securityOfficer: $('#cncsSecurityOfficer')?.value || '',
        signature: $('#cncsSignature')?.value || '',
    };

    const def = buildCncsPdfDefinition(form, data);
    const filename = `cncs_relatorio_anual_${year}_${new Date().toISOString().slice(0, 10)}.pdf`;
    window.pdfMake.createPdf(def).download(filename);
}

function wireLivePreviewTextSync() {
    // Sempre que editar os textos manuais, atualizar prévia final
    ['#cncsManualActivities', '#cncsManualRecs', '#cncsExtra'].forEach(id => {
        const el = $(id);
        if (!el) return;
        el.addEventListener('input', () => {
            setText('#pvActivitiesText', $('#cncsManualActivities')?.value || '—');
            setText('#pvRecsText', $('#cncsManualRecs')?.value || '—');
            setText('#pvExtraText', $('#cncsExtra')?.value || '—');
        });
    });
}

function init() {
    // botões
    $('#btnPreviewCNCS')?.addEventListener('click', renderPreview);
    $('#btnExportCNCS')?.addEventListener('click', () => {
        const format = $('#cncsFormat')?.value ?? 'pdf';
        if (format !== 'pdf') {
            alert('Mock: por agora só exporta PDF. ODT fica para o backend.');
            return;
        }
        exportPdfCNCS();
    });

    // auto-preview ao mudar ano/escopo (fica “inteligente”)
    $('#cncsYear')?.addEventListener('change', renderPreview);
    $('#cncsIncidentScope')?.addEventListener('change', renderPreview);

    wireLivePreviewTextSync();

    // primeira renderização
    renderPreview();
}

document.addEventListener('DOMContentLoaded', init);
