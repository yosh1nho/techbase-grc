const $ = (s) => document.querySelector(s);
const $$ = (s) => Array.from(document.querySelectorAll(s));

const ROUTES = window.__TB_ROUTES__ || {};

// ================== ESTADO GLOBAL (vem da API) ==================
// Preenchido por loadDashboardData() — lido pelas funções de render e nextActions
let API = {
    risks: null,   // { total, high, medium, low, top_risk }
    treatments: null,   // { total, done, doing, todo, overdue, overdue_list }
    compliance: null,   // { frameworks, nis2, qnrcs }
};


// ================== ALERTS (WAZUH SIEM) ==================
let WAZUH_ALERTS = [];
let ALERTS = [];   // formato normalizado usado por renderAlertCardBreakdown, buildNextActions, etc.

// Carrega os alertas da API
async function loadWazuhAlerts() {
    try {
        const res = await fetch('/api/dashboard/wazuh-alerts');
        if (res.ok) {
            WAZUH_ALERTS = await res.json();

            // Mapear para o formato usado pelo dashboard (Crítico >= 8, Médio 4-7, Baixo < 4)
            ALERTS = WAZUH_ALERTS.map(a => ({
                id: a.id,
                ts: a.timestamp ? new Date(a.timestamp).toISOString().slice(0, 16).replace('T', ' ') : '—',
                sev: a.level >= 8 ? 'critical' : a.level >= 4 ? 'medium' : 'low',
                asset: a.agent || '—',
                cat: a.rule_id ? 'Wazuh-' + a.rule_id : 'WazuhAlert',
                msg: a.description || '—',
            }));

            renderAlertCardBreakdown();
            updateAlertModalKpis();
            renderAlertsTable();
        }
    } catch (e) {
        console.error('Erro a carregar Wazuh:', e);
    }
}

// Mapa: safeId → id real do alerta (necessário para a chamada à API)
const _alertIdMap = {};

// Renderizar a tabela no Modal
function renderAlertsTable() {
    const tbody = $('#alertsTableBody');
    if (!tbody) return;

    if (WAZUH_ALERTS.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center muted" style="padding: 30px;">Nenhum alerta recente encontrado.</td></tr>';
        return;
    }

    const sevFilter = $('#alertSeverity')?.value || 'all';
    const q = ($('#alertSearch')?.value || '').toLowerCase().trim();

    const filtered = WAZUH_ALERTS.filter(a => {
        let matchSev = true;
        // 👈 MAPEAMENTO CORRIGIDO PARA BATER CERTO COM OS BOTÕES
        const normalizedSev = a.level >= 8 ? 'critical' : a.level >= 4 ? 'medium' : 'low';
        if (sevFilter !== 'all') matchSev = normalizedSev === sevFilter;
        const matchQ = !q || a.description.toLowerCase().includes(q) || a.agent.toLowerCase().includes(q);
        return matchSev && matchQ;
    });

    tbody.innerHTML = filtered.map((a, idx) => {
        const safeId = `alert_${idx}`;
        _alertIdMap[safeId] = a.id;
        const dateStr = new Date(a.timestamp).toLocaleString('pt-PT');
        let badgeBg = a.level >= 8 ? '#f87171' : a.level >= 4 ? '#fbbf24' : '#34d399';

        const mitreTac = Array.isArray(a.mitre_tactics) ? a.mitre_tactics : [];
        const mitreTech = Array.isArray(a.mitre_techniques) ? a.mitre_techniques : [];
        const comp = Array.isArray(a.compliance) ? a.compliance : [];

        const tableTags = [...comp, ...mitreTac.map(t => 'MITRE: ' + t)].slice(0, 3);
        const tableTagsHtml = tableTags.length
            ? tableTags.map(t => `<span class="soc-chip">${t}</span>`).join('')
            : '<span class="muted">—</span>';

        const compChips = comp.length
            ? comp.map(c => `<span class="soc-chip">${c}</span>`).join('')
            : '<span class="muted">Nenhuma</span>';

        return `
        <tr data-row-click="true" onclick="toggleAlertAiPanel('${safeId}')">
            <td class="muted" style="font-size:11px;">${dateStr}</td>
            <td style="font-weight:600; color:var(--text);">${a.agent}</td>
            <td style="font-size:12px; color:var(--text);">${a.description}</td>
            <td style="text-align:center;">
                <span style="padding:3px 10px; border-radius:6px; font-size:11px; font-weight:800;
                             background:${badgeBg}18; color:${badgeBg}; border:1px solid ${badgeBg}35;">
                    ${a.level}
                </span>
            </td>
            <td><div style="display:flex; gap:4px; flex-wrap:wrap;">${tableTagsHtml}</div></td>
        </tr>
        <tr id="ai-panel-${safeId}" class="detail-row" style="display:none;">
            <td colspan="5">
                <div class="alert-details-container">

                    <div class="details-grid">

                        <div class="detail-card">
                            <h4><i data-lucide="terminal" style="width:13px;height:13px;"></i> Contexto Técnico</h4>
                            <div class="info-pair"><span class="label">ID da Regra</span><span class="value">${a.rule_id || 'N/A'}</span></div>
                            <div class="info-pair"><span class="label">Agente</span><span class="value">${a.agent}</span></div>
                            <div class="info-pair"><span class="label">Severidade Original</span><span class="value" style="color:${badgeBg};">${a.level}</span></div>
                        </div>

                        <div class="detail-card">
                            <h4><i data-lucide="shield-alert" style="width:13px;height:13px;"></i> MITRE ATT&CK</h4>
                            <div class="info-pair"><span class="label">Táticas</span><span class="value">${mitreTac.length ? mitreTac.join(', ') : '<span class="muted">Sem mapeamento</span>'}</span></div>
                            <div class="info-pair"><span class="label">Técnicas</span><span class="value">${mitreTech.length ? mitreTech.join(', ') : '<span class="muted">Sem mapeamento</span>'}</span></div>
                        </div>

                        <div class="detail-card">
                            <h4><i data-lucide="clipboard-check" style="width:13px;height:13px;"></i> Conformidade</h4>
                            <div style="display:flex; flex-wrap:wrap; gap:5px; margin-top:4px;">
                                ${compChips}
                            </div>
                        </div>

                    </div>

                    <div class="remedy-banner">
                        <div class="detail-card">
                            <h4><i data-lucide="list-checks" style="width:13px;height:13px;"></i> Remediação Sugerida (Wazuh)</h4>
                            <p style="font-size:13px; color:var(--text); line-height:1.65; margin:0;">
                                ${a.remediation || 'O Wazuh não forneceu passos automáticos de remediação para esta regra.'}
                            </p>
                        </div>
                    </div>

                    <div class="ai-soc-banner">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:10px; color:var(--info);">
                                <i data-lucide="bot" style="width:16px;height:16px;"></i>
                                <span style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.07em;">
                                    Plano de Mitigação Estratégico (IA)
                                </span>
                            </div>
                            <button class="btn-ai-action" onclick="event.stopPropagation(); analyzeAlert('${a.id}')" id="btn-ai-${safeId}">
                                <i data-lucide="sparkles" style="width:13px;height:13px;"></i>
                                Gerar Análise Profunda
                            </button>
                        </div>
                        <div id="ai-content-${safeId}" style="font-size:12px; color:var(--muted); margin-top:10px; line-height:1.6;">
                            Cruze estes dados com a nossa base de conhecimento GRC para um plano de ação completo.
                        </div>
                    </div>

                </div>
            </td>
        </tr>
        `;
    }).join('');

    if (window.lucide) lucide.createIcons();
}

// Expandir/Colapsar o painel da IA por baixo da linha
window.toggleAlertAiPanel = function (safeId) {
    const panel = document.getElementById('ai-panel-' + safeId);
    if (panel) {
        const isHidden = panel.style.display === 'none' || panel.style.display === '';
        panel.style.display = isHidden ? 'table-row' : 'none';
        if (isHidden && window.lucide) lucide.createIcons();
    }
};

// Chamar a API do Gemini para o Alerta
window.analyzeAlert = async function (safeId) {
    const realId = _alertIdMap[safeId] || safeId;
    const btn = document.getElementById('btn-ai-' + safeId);
    const content = document.getElementById('ai-content-' + safeId);
    if (!btn || !content) return;

    btn.disabled = true;
    const ogHtml = btn.innerHTML;
    btn.innerHTML = `<span class="spinner" style="width:12px;height:12px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:spin 1s linear infinite;display:inline-block;"></span> A analisar...`;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
        const res = await fetch(`/api/dashboard/wazuh-alerts/${realId}/analyze`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken || '', 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (res.ok) {
            btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M20 6 9 17l-5-5"/></svg> Analisado (${data.date})`;
            btn.className = "btn-ghost btn-sm"; // Fica discreto após gerar

            // Injetar o HTML gerado pela IA
            content.innerHTML = `
                <div style="background: rgba(79,156,249,0.05); border-left: 3px solid #4f9cf9; padding: 12px 16px; border-radius: 4px;">
                    ${data.text}
                </div>
            `;
        } else {
            throw new Error(data.message || 'Erro ao analisar');
        }
    } catch (err) {
        alert("Erro na IA: " + err.message);
        btn.innerHTML = ogHtml;
        btn.disabled = false;
    }
};

// ================== API DASHBOARD ==================
async function loadDashboardData() {
    try {
        const res = await fetch('/api/dashboard');
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        API.risks = data.risks;
        API.treatments = data.treatments;
        API.compliance = data.compliance;

        // Render de todos os cards (usando as funções originais completas)
        renderRiskStats();
        renderTreatmentStats();
        renderComplianceCards();
        renderGapGroups();
        renderTopGapControls();
        renderNextActions();

    } catch (e) {
        console.error('Erro ao carregar dados do dashboard:', e);
    }
}

// ================== RENDER: ALERTAS ==================
function renderAlertCardBreakdown() {
    const total = ALERTS.length;
    const critical = ALERTS.filter(a => a.sev === 'critical').length;
    const medium = ALERTS.filter(a => a.sev === 'medium').length;
    const low = ALERTS.filter(a => a.sev === 'low' || a.sev === 'high').length;

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('alertCount', total || '—');

    const subEl = document.getElementById('alertSub');
    if (subEl) subEl.textContent = total > 0
        ? `${critical} crítico${critical !== 1 ? 's' : ''} • ${total - critical} outros`
        : 'Sem alertas activos';

    set('alertCritCount', critical);
    set('alertMedCount', medium);
    set('alertLowCount', low);

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

    const total = d.total;
    const high = d.high;
    const medium = d.medium;
    const low = d.low;

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('riskCount', total || '0');
    set('riskHighCount', high);
    set('riskMedCount', medium);
    set('riskLowCount', low);

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
    const topEl = document.getElementById('riskTopItem');
    if (topEl && topRisk) {
        document.getElementById('riskTopTitle').textContent = topRisk.title || `Risco #${topRisk.id}`;
        document.getElementById('riskTopMeta').textContent = `Score ${topRisk.score} • ${topRisk.asset || topRisk.owner || '—'}`;
        topEl.style.display = 'block';
    }
}

// ================== RENDER: PLANOS DE TRATAMENTO ==================
function renderTreatmentStats() {
    const d = API.treatments;
    if (!d) return;

    const total = d.total;
    const done = d.done;
    const doing = d.doing;
    const todo = d.todo;
    const overdue = d.overdue;

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('treatmentOverdueCount', overdue);
    set('treatmentOverdueLabel', overdue > 0 ? 'em atraso' : 'tudo em dia');
    set('treatCountDone', done);
    set('treatCountDoing', doing);
    set('treatCountOverdue', overdue);

    if (total > 0) {
        const pct = v => `${Math.round(v / total * 100)}%`;
        const setW = (id, v) => { const el = document.getElementById(id); if (el) el.style.width = pct(v); };
        setW('treatBarDone', done);
        setW('treatBarDoing', doing);
        setW('treatBarTodo', todo);
        setW('treatBarOverdue', overdue);
    }

    const big = document.getElementById('treatmentOverdueCount');
    if (big) big.style.color = overdue > 0 ? '#f87171' : '#34d399';
}

// ================== RENDER: TOP LACUNAS POR GRUPO ==================
function renderGapGroups() {
    const el = document.getElementById('gapGroupsBody');
    if (!el) return;

    // Construir dados a partir do compliance já carregado em API
    const compliance = API.compliance;
    if (!compliance) {
        el.innerHTML = '<p class="muted" style="font-size:13px">Dados de compliance não disponíveis.</p>';
        return;
    }

    // Agregar gaps + partials por framework
    const groups = [];
    ['nis2', 'qnrcs'].forEach(key => {
        const fw = compliance[key];
        if (!fw) return;
        const gap = fw.non_compliant ?? 0;
        const partial = fw.partial ?? 0;
        const total = fw.total_controls ?? 0;
        if (total === 0) return;
        groups.push({
            name: fw.framework_name || key.toUpperCase(),
            gap,
            partial,
            total,
            pct: fw.compliance_pct_weighted ?? 0,
        });
    });

    if (!groups.length) {
        el.innerHTML = '<p class="muted" style="font-size:13px">Sem dados de conformidade registados ainda.</p>';
        return;
    }

    // Ordenar por mais lacunas
    groups.sort((a, b) => (b.gap + b.partial * 0.5) - (a.gap + a.partial * 0.5));

    el.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>Framework</th>
                    <th>GAP</th>
                    <th>Parcial</th>
                    <th>Progresso</th>
                </tr>
            </thead>
            <tbody>
                ${groups.map(g => {
        const barColor = g.pct >= 70 ? '#34d399' : g.pct >= 40 ? '#fbbf24' : '#f87171';
        return `<tr>
                        <td><b>${g.name}</b><div class="muted" style="font-size:11px">${g.total} controlos</div></td>
                        <td>${g.gap > 0 ? `<span class="tag bad"><span class="s"></span>${g.gap} GAP</span>` : '<span class="muted">—</span>'}</td>
                        <td>${g.partial > 0 ? `<span class="tag warn"><span class="s"></span>${g.partial} Parcial</span>` : '<span class="muted">—</span>'}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div style="flex:1;height:5px;border-radius:99px;background:rgba(255,255,255,.08);overflow:hidden">
                                    <div style="height:100%;border-radius:99px;background:${barColor};width:${g.pct}%;transition:width .5s"></div>
                                </div>
                                <span style="font-size:11px;font-weight:700;color:${barColor};min-width:34px">${g.pct}%</span>
                            </div>
                        </td>
                    </tr>`;
    }).join('')}
            </tbody>
        </table>
        <div class="hint" style="margin-top:8px">
            <a href="${ROUTES.compliance || '/compliance'}" style="color:var(--a-accent,#60a5fa);text-decoration:none;font-size:12px">Ver detalhe completo em Compliance →</a>
        </div>`;
}

// ================== RENDER: TOP CONTROLOS GAP ==================
function renderTopGapControls() {
    const el = document.getElementById('topGapControlsBody');
    if (!el) return;

    const compliance = API.compliance;
    if (!compliance) {
        el.innerHTML = '<p class="muted" style="font-size:13px">Dados não disponíveis.</p>';
        return;
    }

    // Construir lista de controlos GAP a partir dos dados da API
    // A API /api/dashboard/compliance devolve summary — para os controlos individuais
    // usamos os dados que já temos: total GAP por framework
    const items = [];
    ['nis2', 'qnrcs'].forEach(key => {
        const fw = compliance[key];
        if (!fw || !fw.non_compliant) return;
        items.push({
            framework: fw.framework_name || key.toUpperCase(),
            gap: fw.non_compliant,
            partial: fw.partial ?? 0,
            pct: fw.compliance_pct_weighted ?? 0,
            link: `${ROUTES.compliance || '/compliance'}?framework=${fw.framework_name || key.toUpperCase()}`,
        });
    });

    if (!items.length) {
        el.innerHTML = `
            <div style="text-align:center;padding:16px">
                <div style="font-size:24px;margin-bottom:8px">✓</div>
                <div style="font-size:13px;font-weight:600;margin-bottom:4px">Sem lacunas críticas</div>
                <p class="muted" style="font-size:12px">Todos os controlos avaliados estão conformes ou parciais.</p>
            </div>`;
        return;
    }

    // Calcular prioridade baseado em riscos abertos
    const highRisks = API.risks?.high ?? 0;
    const overdueTP = API.treatments?.overdue ?? 0;

    el.innerHTML = items.map((item, i) => {
        const urgency = item.pct < 30 ? { cls: 'bad', label: 'Crítico' }
            : item.pct < 60 ? { cls: 'warn', label: 'Médio' }
                : { cls: 'ok', label: 'Baixo' };
        const context = i === 0 && highRisks > 0
            ? `${highRisks} risco(s) de score alto associados — avaliar e criar planos.`
            : i === 0 && overdueTP > 0
                ? `${overdueTP} plano(s) em atraso — priorizar tratamento.`
                : `${item.gap} controlo(s) sem cobertura. Avaliar e documentar evidências.`;

        return `
        <div class="panel" style="margin-bottom:8px">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px">
                <div style="display:flex;align-items:center;gap:8px">
                    <span class="chip ${urgency.cls}" style="font-size:11px">P${i + 1} · ${urgency.label}</span>
                    <b style="font-size:13px">${item.framework}</b>
                </div>
                <span style="font-size:11px;font-weight:700;color:${item.pct < 40 ? '#f87171' : '#fbbf24'}">${item.pct}% conforme</span>
            </div>
            <p class="muted" style="margin:0 0 8px;font-size:12px;line-height:1.5">${context}</p>
            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                ${item.gap > 0 ? `<span class="chip bad"  style="font-size:10px">${item.gap} GAP</span>` : ''}
                ${item.partial > 0 ? `<span class="chip warn" style="font-size:10px">${item.partial} Parcial</span>` : ''}
                <a href="${item.link}" style="font-size:11px;color:var(--a-accent,#60a5fa);text-decoration:none;margin-left:auto">
                    Avaliar controlos →
                </a>
            </div>
        </div>`;
    }).join('');
}

// ================== RENDER: CARTÕES DE COMPLIANCE (NIS2/QNRCS) ==================
function renderComplianceCards() {
    const data = API.compliance;
    if (!data) return;

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

    // --- NIS2 ---
    const n = data.nis2;
    if (n) {
        const pct = Math.round(n.compliance_pct_weighted || 0);
        set('nis2Pct', pct + '%');
        set('nis2Compliant', n.compliant);
        set('nis2Partial', n.partial);
        set('nis2NonCompliant', n.non_compliant);
        set('nis2Total', n.total_controls);

        const bar = document.getElementById('nis2Bar');
        if (bar) {
            bar.style.width = pct + '%';
            // Cor dinâmica baseada no progresso
            bar.style.background = pct >= 70 ? '#34d399' : pct >= 40 ? '#fbbf24' : '#f87171';
        }
    }

    // --- QNRCS ---
    const q = data.qnrcs;
    if (q) {
        const pct = Math.round(q.compliance_pct_weighted || 0);
        set('qnrcsPct', pct + '%');
        set('qnrcsCompliant', q.compliant);
        set('qnrcsPartial', q.partial);
        set('qnrcsNonCompliant', q.non_compliant);
        set('qnrcsTotal', q.total_controls);

        const bar = document.getElementById('qnrcsBar');
        if (bar) {
            bar.style.width = pct + '%';
            bar.style.background = pct >= 70 ? '#34d399' : pct >= 40 ? '#fbbf24' : '#f87171';
        }
    }
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
    const nis2Pct = API.compliance?.nis2?.compliance_pct_weighted ?? null;
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

    // Configuração com cores em vez de ícones
    const urgencyConfig = {
        critical: { cls: 'bad', label: 'Crítico', color: '#f87171' }, // Vermelho
        high: { cls: 'warn', label: 'Alto', color: '#fb923c' },     // Laranja
        medium: { cls: 'warn', label: 'Médio', color: '#fbbf24' },  // Amarelo
        ok: { cls: 'ok', label: 'OK', color: '#34d399' },           // Verde
    };

    container.innerHTML = actions.map((a, i) => {
        const u = urgencyConfig[a.urgency] || urgencyConfig.medium;
        const btn = a.link
            ? `<a href="${a.link}" class="btn primary" style="font-size:12px;padding:5px 12px;text-decoration:none">${a.linkLabel} →</a>`
            : '';

        return `
        <div class="panel next-action-item" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap; margin-bottom: 8px;">
            <div style="flex:1;min-width:200px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="chip ${u.cls}" style="font-size:11px; display:inline-flex; align-items:center; gap:6px; font-weight:600;">
                        <span style="width:7px; height:7px; border-radius:50%; background:${u.color}; display:inline-block; box-shadow: 0 0 6px ${u.color}80;"></span>
                        P${i + 1} · ${u.label}
                    </span>
                </div>
                <b style="font-size:14px; color:var(--text);">${a.title}</b>
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
        high: `<span class="tag warn"><span class="s"></span> alta</span>`,
        medium: `<span class="tag"><span class="s"></span> média</span>`,
        low: `<span class="tag ok"><span class="s"></span> baixa</span>`,
    };
    return map[sev] || `<span class="tag"><span class="s"></span> —</span>`;
}

// ================== MODAL BASE ==================
function openModal(id) {
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
    const total = ALERTS.length;
    const critical = ALERTS.filter(a => a.sev === 'critical').length;
    const medium = ALERTS.filter(a => a.sev === 'medium').length;
    const low = ALERTS.filter(a => a.sev === 'low').length; // 👈 ERRO CORRIGIDO AQUI

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('akpiTotal', total);
    set('akpiCritical', critical);
    set('akpiMedium', medium);
    set('akpiLow', low);
}


// ================== INIT ==================
function init() {
    // 1. Carregar dados básicos do Dashboard (KPIs de risco, compliance, etc)
    loadDashboardData();

    // 2. Carregar os alertas reais do Wazuh
    loadWazuhAlerts();

    // 3. Configurar abertura do Modal
    const openAlerts = $('[data-open-alerts]');
    if (openAlerts) {
        openAlerts.addEventListener('click', () => {
            openModal('#alertsModal');
            renderAlertsTable(); // Garante que a tabela renderiza ao abrir
        });
        openAlerts.addEventListener('keydown', e => { if (e.key === 'Enter') openAlerts.click(); });
    }

    // 4. Fechar Modais
    $('#alertsModalClose')?.addEventListener('click', () => closeModal('#alertsModal'));
    $('#alertsModal')?.addEventListener('click', e => { if (e.target.id === 'alertsModal') closeModal('#alertsModal'); });

    // 5. Filtros de Severidade (KPIs clicáveis dentro do modal)
    $$('[data-sev-filter]').forEach(btn => {
        btn.addEventListener('click', () => {
            const sev = btn.dataset.sevFilter;
            const sel = $('#alertSeverity');
            if (sel) sel.value = sev;

            // UI update
            $$('[data-sev-filter]').forEach(b => b.setAttribute('aria-pressed', 'false'));
            btn.setAttribute('aria-pressed', 'true');

            renderAlertsTable();
        });
    });

    // 6. Outros filtros (Busca e Select)
    $('#alertSeverity')?.addEventListener('change', () => {
        const val = $('#alertSeverity').value;
        $$('[data-sev-filter]').forEach(b => {
            b.setAttribute('aria-pressed', b.dataset.sevFilter === val ? 'true' : 'false');
        });
        renderAlertsTable();
    });

    $('#alertSearch')?.addEventListener('input', renderAlertsTable);

    // 7. Tecla ESC para fechar
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            if (!$('#alertsModal')?.classList.contains('is-hidden')) {
                closeModal('#alertsModal');
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', init);