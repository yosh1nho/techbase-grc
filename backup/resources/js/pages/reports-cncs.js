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

function exportMock() {
    const format = $('#cncsFormat')?.value ?? 'pdf';
    const saveAsDoc = $('#cncsSaveAsDoc')?.value ?? 'yes';

    // Aqui depois vira: POST /reports/cncs/export (e backend gera PDF/ODT)
    // Por enquanto, mock:
    alert(`Mock: exportado em ${format.toUpperCase()}${saveAsDoc === 'yes' ? ' e guardado como Documento' : ''}.`);
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
    $('#btnExportCNCS')?.addEventListener('click', exportMock);

    // auto-preview ao mudar ano/escopo (fica “inteligente”)
    $('#cncsYear')?.addEventListener('change', renderPreview);
    $('#cncsIncidentScope')?.addEventListener('change', renderPreview);

    wireLivePreviewTextSync();

    // primeira renderização
    renderPreview();
}

document.addEventListener('DOMContentLoaded', init);
