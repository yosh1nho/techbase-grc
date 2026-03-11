// resources/js/pages/questionnaire.js — Techbase GRC Cyberplanner v4
// Tabs por área · Lucide icons · PDF texto selecionável (pdfmake) · Light/Dark safe
// ─────────────────────────────────────────────────────────────────────────────

const $ = (s) => document.querySelector(s);
const $$ = (s) => [...document.querySelectorAll(s)];

/* ─── UI HELPERS ──────────────────────────────────────────────────────────── */
function openModal(id) {
    const el = document.getElementById(id);
    el?.classList.remove("is-hidden");
    el?.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}
function closeModal(id) {
    const el = document.getElementById(id);
    el?.classList.add("is-hidden");
    el?.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
}
function toast(title, msg, type = "ok") {
    const t = $("#toast");
    if (!t) return;
    $("#toastTitle").textContent = title;
    $("#toastMsg").textContent = msg;
    t.style.display = "";
    t.className = type === "err" ? "toast-box toast-err" : "toast-box toast-ok";
    clearTimeout(toast._t);
    toast._t = setTimeout(() => (t.style.display = "none"), 3000);
}
function esc(s) {
    return String(s ?? "")
        .replace(/&/g, "&amp;").replace(/</g, "&lt;")
        .replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

/* ─── CATÁLOGO ────────────────────────────────────────────────────────────── */
// icon = nome do ícone Lucide
const AREAS = [
    {
        id: "PDS", icon: "lock-keyhole", title: "Dados & Privacidade",
        desc: "Inventário, acessos, cifragem e backups.",
        items: [
            { id: "PDS_01", risk: "Crítico", q: "Existe inventário dos dados tratados (PII, saúde, financeiro) com localização e responsável?", action: "Criar registo de dados com tipo, localização, owner e classificação de criticidade.", qnrcs: "ID.GA-1", nis2: "Art. 21" },
            { id: "PDS_02", risk: "Crítico", q: "Os dados sensíveis têm RBAC e MFA obrigatório nos sistemas críticos?", action: "Aplicar least-privilege + MFA + revisão trimestral de contas privilegiadas.", qnrcs: "PR.AC-1", nis2: "Art. 21" },
            { id: "PDS_03", risk: "Alto", q: "Dados sensíveis são cifrados em repouso e em trânsito (TLS 1.2+)?", action: "Rever cifragem AES/TLS, rotação de chaves e certificados HTTPS.", qnrcs: "PR.DS-1", nis2: "Art. 21" },
            { id: "PDS_04", risk: "Crítico", q: "Existe backup regular (3-2-1) com testes de restauração periódicos?", action: "Backups diários off-site + testes mensais + evidências documentadas.", qnrcs: "PR.IP-4", nis2: "Art. 21" },
        ],
    },
    {
        id: "NS", icon: "network", title: "Segurança de Rede",
        desc: "Firewall, Wi-Fi, VPN e patches.",
        items: [
            { id: "NS_01", risk: "Alto", q: "Firewall com regras documentadas e revisão periódica de portas expostas?", action: "Auditar regras, fechar portas desnecessárias, manter inventário de serviços.", qnrcs: "PR.AC-4", nis2: "Art. 21" },
            { id: "NS_02", risk: "Alto", q: "Wi-Fi corporativo usa WPA2/WPA3 com rede de convidados separada?", action: "VLAN guest isolada, senha robusta, desativar WPS.", qnrcs: "PR.AC-1", nis2: "Art. 21" },
            { id: "NS_03", risk: "Crítico", q: "Acessos remotos obrigam VPN segura com MFA?", action: "VPN + MFA + políticas por função + logging de acessos.", qnrcs: "PR.AC-1", nis2: "Art. 21" },
            { id: "NS_04", risk: "Alto", q: "Processo de patching mensal com priorização CVSS e evidências?", action: "Janela de patching definida, criticidade CVSS, registo de evidências.", qnrcs: "PR.IP-12", nis2: "Art. 21" },
        ],
    },
    {
        id: "EM", icon: "mail-check", title: "Email & Comunicações",
        desc: "Phishing, SPF/DKIM/DMARC e formação.",
        items: [
            { id: "EM_01", risk: "Alto", q: "Filtros anti-spam/phishing ativos e domínio com SPF, DKIM e DMARC?", action: "Filtros avançados + SPF + DKIM + DMARC (evoluir para p=reject).", qnrcs: "PR.DS-2", nis2: "Art. 21" },
            { id: "EM_02", risk: "Alto", q: "Equipa recebe formação anti-phishing com simulações periódicas?", action: "Programa de awareness semestral + simulações + registo de participação.", qnrcs: "PR.AT-1", nis2: "Art. 21" },
            { id: "EM_03", risk: "Médio", q: "Bloqueio de anexos perigosos (exe, macro) e política de retenção?", action: "Bloquear extensões perigosas, sandbox para anexos, retenção 90 dias.", qnrcs: "PR.IP-1", nis2: "Art. 21" },
        ],
    },
    {
        id: "EMP", icon: "users-round", title: "Pessoas & Acessos",
        desc: "Onboarding, offboarding e privilégios.",
        items: [
            { id: "EMP_01", risk: "Crítico", q: "Onboarding/offboarding formal com remoção de acessos em < 24h na saída?", action: "Checklist formal + revogação automática de acessos ao fim de 24h.", qnrcs: "PR.AC-4", nis2: "Art. 21" },
            { id: "EMP_02", risk: "Crítico", q: "Contas admin separadas do dia-a-dia, com MFA e auditadas trimestralmente?", action: "Contas admin dedicadas + MFA + logs + revisão trimestral.", qnrcs: "PR.AC-1", nis2: "Art. 21" },
            { id: "EMP_03", risk: "Alto", q: "Política de passwords com requisitos mínimos e gestor corporativo?", action: "Política 12+ caracteres + MFA + gestor de passwords corporativo.", qnrcs: "PR.AC-1", nis2: "Art. 21" },
            { id: "EMP_04", risk: "Médio", q: "Colaboradores assinaram AUP e recebem formação de segurança anual?", action: "AUP atualizada + assinaturas + formação anual com registo.", qnrcs: "GV.PO-1", nis2: "Art. 21" },
        ],
    },
    {
        id: "IR", icon: "siren", title: "Resposta a Incidentes",
        desc: "Plano, deteção, comunicação e recuperação.",
        items: [
            { id: "IR_01", risk: "Crítico", q: "Plano de resposta a incidentes documentado com papéis e playbooks?", action: "Criar IRP com papéis, contactos de emergência e playbooks por tipo.", qnrcs: "RS.RP-1", nis2: "Art. 21" },
            { id: "IR_02", risk: "Alto", q: "Logging centralizado com retenção ≥ 90 dias e alertas de anomalias?", action: "SIEM ou equivalente, retenção 90+ dias, alertas automáticos.", qnrcs: "DE.CM-1", nis2: "Art. 21" },
            { id: "IR_03", risk: "Médio", q: "RTO/RPO definidos para serviços críticos e testados anualmente?", action: "Definir RTO/RPO + teste anual de recuperação + evidências.", qnrcs: "PR.PT-4", nis2: "Art. 21" },
            { id: "IR_04", risk: "Médio", q: "Plano de comunicação de incidentes ao CNCS/autoridades (NIS2)?", action: "Template de comunicação + contactos CNCS + notificação em 24h.", qnrcs: "RS.CO-2", nis2: "Art. 23" },
        ],
    },
    {
        id: "SUP", icon: "handshake", title: "Fornecedores & Terceiros",
        desc: "Cadeia de fornecimento e contratos.",
        items: [
            { id: "SUP_01", risk: "Alto", q: "Inventário de fornecedores críticos com avaliação de risco?", action: "Registo de fornecedores com criticidade, tipo de acesso e owner.", qnrcs: "ID.SC-1", nis2: "Art. 21" },
            { id: "SUP_02", risk: "Médio", q: "Contratos incluem cláusulas de segurança, SLA e notificação de incidentes?", action: "Cláusulas de segurança + SLA + auditoria em todos os contratos.", qnrcs: "ID.SC-2", nis2: "Art. 21" },
            { id: "SUP_03", risk: "Alto", q: "Acessos de terceiros são limitados, temporários e revogados ao fim?", action: "Least-privilege + MFA + expiração automática + logs por fornecedor.", qnrcs: "PR.AC-1", nis2: "Art. 21" },
        ],
    },
    {
        id: "POL", icon: "clipboard-list", title: "Políticas & Governança",
        desc: "Políticas versionadas, ativos e registo de riscos.",
        items: [
            { id: "POL_01", risk: "Alto", q: "Políticas formais (passwords, acessos, backups, IR) versionadas e aprovadas?", action: "Criar e versionar políticas base, com aprovação da gestão e revisão anual.", qnrcs: "GV.PO-1", nis2: "Art. 21" },
            { id: "POL_02", risk: "Alto", q: "Inventário de ativos (hardware, software, dados) com owners e criticidade?", action: "Manter inventário atualizado com tipo, owner, criticidade e localização.", qnrcs: "ID.GA-1", nis2: "Art. 21" },
            { id: "POL_03", risk: "Médio", q: "Registo de riscos com P×I, plano de tratamento e responsável?", action: "Criar registo de riscos com matriz P×I, ações, prazos e evidências.", qnrcs: "ID.RA-1", nis2: "Art. 21" },
        ],
    },
    {
        id: "WS", icon: "globe-lock", title: "Web & Serviços Públicos",
        desc: "HTTPS, WAF, updates e vulnerabilidades.",
        items: [
            { id: "WS_01", risk: "Alto", q: "Serviços públicos com TLS 1.2+, HSTS e security headers configurados?", action: "Verificar TLS, HSTS, CSP, X-Frame-Options e Referrer-Policy.", qnrcs: "PR.DS-2", nis2: "Art. 21" },
            { id: "WS_02", risk: "Alto", q: "CMS, plugins e dependências atualizados com monitorização CVE?", action: "Updates semanais + inventário de dependências + alertas CVE.", qnrcs: "PR.IP-12", nis2: "Art. 21" },
            { id: "WS_03", risk: "Médio", q: "WAF ativo e pentest anual documentado?", action: "WAF com regras OWASP + proteção DDoS + pentest anual.", qnrcs: "ID.RA-1", nis2: "Art. 21" },
        ],
    },
    {
        id: "MD", icon: "smartphone", title: "Dispositivos Móveis",
        desc: "MDM, BYOD e cifragem.",
        items: [
            { id: "MD_01", risk: "Médio", q: "Dispositivos corporativos com bloqueio automático, PIN e cifragem ativa?", action: "Política de bloqueio 30s + PIN forte + cifragem obrigatória.", qnrcs: "PR.AC-1", nis2: "Art. 21" },
            { id: "MD_02", risk: "Alto", q: "MDM implementado com inventário, baseline e wipe remoto?", action: "MDM (Intune/Jamf) + baseline de segurança + wipe remoto.", qnrcs: "PR.IP-1", nis2: "Art. 21" },
            { id: "MD_03", risk: "Médio", q: "Política BYOD formal com separação de dados corporativos?", action: "Política BYOD com containerização, requisitos mínimos e aceitação.", qnrcs: "PR.AC-1", nis2: "Art. 21" },
        ],
    },
];

const RISK_ORDER = { Crítico: 0, Alto: 1, Médio: 2, Baixo: 3 };
const RISK_COLOR_HEX = { Crítico: "#ef4444", Alto: "#f97316", Médio: "#eab308", Baixo: "#22c55e" };

/* ─── STORAGE ─────────────────────────────────────────────────────────────── */
const SK_ANS = "tb_cp_answers_v4";
const SK_META = "tb_cp_meta_v4";

function load(k, fb) { try { const r = localStorage.getItem(k); return r ? JSON.parse(r) : fb; } catch { return fb; } }
function save(k, v) { localStorage.setItem(k, JSON.stringify(v)); }

let answers = load(SK_ANS, {});
let meta = load(SK_META, { company: "", sector: "", date: "" });
let activeTab = AREAS[0].id;

/* ─── TABS ────────────────────────────────────────────────────────────────── */
function buildTabs() {
    const wrap = $("#areaTabs");
    if (!wrap) return;
    wrap.innerHTML = "";

    AREAS.forEach(area => {
        const answered = area.items.filter(q => answers[q.id]?.answer).length;
        const total = area.items.length;
        const allDone = answered === total;
        const isActive = area.id === activeTab;

        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "area-tab" + (isActive ? " active" : "");
        btn.dataset.area = area.id;
        btn.setAttribute("aria-selected", isActive ? "true" : "false");

        btn.innerHTML = `
            <span class="area-tab-icon"><i data-lucide="${esc(area.icon)}"></i></span>
            <span class="area-tab-label">${esc(area.title)}</span>
            <span class="area-tab-badge ${allDone ? "done" : answered > 0 ? "partial" : ""}">${answered}/${total}</span>
        `;

        btn.addEventListener("click", () => {
            activeTab = area.id;
            buildTabs();
            buildForm();
            if (window.lucide) window.lucide.createIcons();
        });

        wrap.appendChild(btn);
    });
}

/* ─── FORM ────────────────────────────────────────────────────────────────── */
function buildForm() {
    const wrap = $("#formSections");
    if (!wrap) return;
    wrap.innerHTML = "";

    const area = AREAS.find(a => a.id === activeTab);
    if (!area) return;

    // Cabeçalho da área
    const header = document.createElement("div");
    header.className = "area-header";
    header.innerHTML = `
        <div class="area-header-icon"><i data-lucide="${esc(area.icon)}"></i></div>
        <div>
            <div class="area-header-title">${esc(area.title)}</div>
            <div class="area-header-desc">${esc(area.desc)}</div>
        </div>
    `;
    wrap.appendChild(header);

    // Perguntas
    area.items.forEach(q => {
        const prev = answers[q.id] || { answer: "", notes: "" };
        const rHex = RISK_COLOR_HEX[q.risk] || "#6b7280";
        const row = document.createElement("div");
        row.className = "cp-row";
        row.id = `row-${q.id}`;

        row.innerHTML = `
            <div class="cp-row-top">
                <div class="cp-row-question">${esc(q.q)}</div>
                <span class="risk-badge" style="--rc:${rHex};">${esc(q.risk)}</span>
            </div>

            <div class="cp-row-answers">
                ${[
                { v: "YES", label: "Sim", icon: "circle-check" },
                { v: "PARTIAL", label: "Parcial", icon: "circle-half" },
                { v: "NO", label: "Não", icon: "circle-x" },
                { v: "NA", label: "N/A", icon: "minus-circle" },
            ].map(opt => `
                    <label class="ans-opt ${prev.answer === opt.v ? "selected ans-" + opt.v : ""}" data-val="${opt.v}">
                        <input type="radio" name="ans_${q.id}" value="${opt.v}"
                            ${prev.answer === opt.v ? "checked" : ""} style="display:none;">
                        <span class="ans-icon"><i data-lucide="${opt.icon}"></i></span>
                        <span>${opt.label}</span>
                    </label>
                `).join("")}
            </div>

            <div class="cp-row-notes">
                <textarea name="notes_${q.id}"
                    placeholder="Observações (opcional) — contexto, responsáveis, evidências…"
                >${esc(prev.notes)}</textarea>
            </div>

            <div class="cp-row-meta">
                <span class="meta-id">${esc(q.id)}</span>
                <span class="meta-ref">QNRCS: <b>${esc(q.qnrcs)}</b></span>
                <span class="meta-ref">NIS2: <b>${esc(q.nis2)}</b></span>
            </div>
        `;

        // Interação radio
        row.querySelectorAll(`input[name="ans_${q.id}"]`).forEach(radio => {
            radio.addEventListener("change", () => {
                saveAnswers();
                refreshRow(row, q.id);
                buildTabs();
                updateKPIs();
                if (window.lucide) window.lucide.createIcons();
            });
        });

        row.querySelector(`textarea[name="notes_${q.id}"]`)
            .addEventListener("input", () => saveAnswers());

        wrap.appendChild(row);
    });

    // Navegação entre tabs
    const nav = document.createElement("div");
    nav.className = "tab-nav";
    const aIdx = AREAS.findIndex(a => a.id === activeTab);
    const hasPrev = aIdx > 0;
    const hasNext = aIdx < AREAS.length - 1;

    nav.innerHTML = `
        <button type="button" class="btn ${hasPrev ? "" : "btn-disabled"}" id="btnTabPrev"
            ${hasPrev ? "" : "disabled"}>
            <i data-lucide="arrow-left"></i> Anterior
        </button>
        <span class="tab-nav-info">${aIdx + 1} / ${AREAS.length}</span>
        ${hasNext
            ? `<button type="button" class="btn ok" id="btnTabNext">
                Próximo <i data-lucide="arrow-right"></i>
               </button>`
            : `<button type="button" class="btn primary" id="btnTabFinish">
                <i data-lucide="file-text"></i> Ver Plano &amp; PDF
               </button>`
        }
    `;
    wrap.appendChild(nav);

    nav.querySelector("#btnTabPrev")?.addEventListener("click", () => {
        if (aIdx > 0) { activeTab = AREAS[aIdx - 1].id; buildTabs(); buildForm(); if (window.lucide) window.lucide.createIcons(); }
    });
    nav.querySelector("#btnTabNext")?.addEventListener("click", () => {
        saveAnswers();
        if (aIdx < AREAS.length - 1) { activeTab = AREAS[aIdx + 1].id; buildTabs(); buildForm(); if (window.lucide) window.lucide.createIcons(); }
    });
    nav.querySelector("#btnTabFinish")?.addEventListener("click", () => {
        saveAnswers(); showReport();
    });
}

function refreshRow(row, qid) {
    const val = row.querySelector(`input[name="ans_${qid}"]:checked`)?.value;
    row.querySelectorAll(".ans-opt").forEach(lbl => {
        const v = lbl.dataset.val;
        lbl.className = "ans-opt" + (v === val ? ` selected ans-${v}` : "");
    });
}

/* ─── SAVE ────────────────────────────────────────────────────────────────── */
function saveAnswers() {
    AREAS.forEach(area => area.items.forEach(q => {
        const radio = document.querySelector(`input[name="ans_${q.id}"]:checked`);
        const notes = document.querySelector(`textarea[name="notes_${q.id}"]`);
        if (radio || notes?.value) {
            answers[q.id] = {
                answer: radio?.value || answers[q.id]?.answer || "",
                notes: (notes?.value || "").trim(),
            };
        }
    }));
    save(SK_ANS, answers);
}

/* ─── KPIs ────────────────────────────────────────────────────────────────── */
function calcScore() {
    const all = AREAS.flatMap(a => a.items);
    const sc = all.map(q => answers[q.id]).filter(a => a?.answer);
    const yes = sc.filter(a => a.answer === "YES").length;
    const noNA = sc.filter(a => a.answer !== "NA").length;
    const pct = noNA ? Math.round((yes / noNA) * 100) : 0;
    return {
        pct,
        label: pct >= 80 ? "Bom" : pct >= 60 ? "Atenção" : pct >= 40 ? "Fraco" : "Crítico",
        color: pct >= 80 ? "#22c55e" : pct >= 60 ? "#f59e0b" : pct >= 40 ? "#f97316" : "#ef4444",
        yes: sc.filter(a => a.answer === "YES").length,
        partial: sc.filter(a => a.answer === "PARTIAL").length,
        no: sc.filter(a => a.answer === "NO").length,
        na: sc.filter(a => a.answer === "NA").length,
        total: all.length,
        answered: sc.length,
    };
}

function updateKPIs() {
    const s = calcScore();
    const pEl = $("#scorePercent"); if (pEl) { pEl.textContent = `${s.pct}%`; pEl.style.color = s.color; }
    const lEl = $("#scoreLabel"); if (lEl) { lEl.textContent = s.label; lEl.style.color = s.color; }
    if ($("#kYes")) $("#kYes").textContent = s.yes;
    if ($("#kPartial")) $("#kPartial").textContent = s.partial;
    if ($("#kNo")) $("#kNo").textContent = s.no;
    if ($("#kNA")) $("#kNA").textContent = s.na;
    const prog = $("#progressBar"); if (prog) prog.style.width = `${s.answered / Math.max(1, s.total) * 100}%`;
    const pTxt = $("#progressText"); if (pTxt) pTxt.textContent = `${s.answered}/${s.total} respondidas`;
}

/* ─── REPORT HTML (modal preview) ────────────────────────────────────────── */
function buildReportHtml() {
    const sc = calcScore();
    const allItems = AREAS.flatMap(a => a.items.map(q => ({
        ...q, areaTitle: a.title, areaIcon: a.icon,
        ...(answers[q.id] || { answer: null, notes: "" }),
    })));
    const gaps = allItems.filter(a => a.answer === "NO" || a.answer === "PARTIAL");
    const nas = allItems.filter(a => a.answer === "NA");
    const now = new Date().toLocaleDateString("pt-PT");

    const byArea = {};
    gaps.forEach(a => {
        if (!byArea[a.areaTitle]) byArea[a.areaTitle] = { icon: a.areaIcon, items: [] };
        byArea[a.areaTitle].items.push(a);
    });
    const sorted = Object.keys(byArea).sort((a, b) => {
        const w = k => Math.min(...byArea[k].items.map(i => RISK_ORDER[i.risk] ?? 9));
        return w(a) - w(b);
    });

    let html = `
    <div class="report-kpis">
        <div class="report-kpi" style="--kc:${sc.color}">
            <div class="rkpi-val">${sc.pct}%</div>
            <div class="rkpi-lbl">Score · ${sc.label}</div>
        </div>
        <div class="report-kpi" style="--kc:#22c55e">
            <div class="rkpi-val">${sc.yes}</div>
            <div class="rkpi-lbl">Implementado</div>
        </div>
        <div class="report-kpi" style="--kc:#f59e0b">
            <div class="rkpi-val">${sc.partial}</div>
            <div class="rkpi-lbl">Parcial</div>
        </div>
        <div class="report-kpi" style="--kc:#ef4444">
            <div class="rkpi-val">${sc.no}</div>
            <div class="rkpi-lbl">Gap</div>
        </div>
        <div class="report-kpi" style="--kc:#6b7280">
            <div class="rkpi-val">${sc.na}</div>
            <div class="rkpi-lbl">N/A</div>
        </div>
    </div>
    <div class="report-hint">
        <i data-lucide="pencil-line" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i>
        <b>Clica em qualquer texto sublinhado</b> para editar antes de exportar o PDF.
        <span style="margin-left:auto; opacity:.6;">${now} · ${meta.company || "—"}</span>
    </div>
    `;

    const riskCounts = { "Crítico": 0, "Alto": 0, "Médio": 0, "Baixo": 0 };

    // contar riscos primeiro
    allItems.forEach(a => {
        if (a.answer === "NO" || a.answer === "PARTIAL") {
            riskCounts[a.risk] = (riskCounts[a.risk] || 0) + 1;
        }
    });

    // descobrir o maior valor
    const maxRisk = Math.max(
        riskCounts["Crítico"],
        riskCounts["Alto"],
        riskCounts["Médio"],
        riskCounts["Baixo"],
        1
    );

    // calcular percentagens
    const critPct = (riskCounts["Crítico"] / maxRisk) * 100;
    const altoPct = (riskCounts["Alto"] / maxRisk) * 100;
    const medioPct = (riskCounts["Médio"] / maxRisk) * 100;
    const baixoPct = (riskCounts["Baixo"] / maxRisk) * 100;

    allItems.forEach(a => {
        if (a.answer === "NO" || a.answer === "PARTIAL") {
            riskCounts[a.risk] = (riskCounts[a.risk] || 0) + 1;
        }
    });

    html += `
        <div class="report-risk">
        <div class="risk-title">Distribuição de Riscos</div>

        <div class="risk-row crit">
        <span>Crítico</span>
        <div class="risk-bar"><span style="width:${critPct}%"></span></div>
        <b>${riskCounts["Crítico"]}</b>
        </div>

        <div class="risk-row alto">
        <span>Alto</span>
        <div class="risk-bar"><span style="width:${altoPct}%"></span></div>
        <b>${riskCounts["Alto"]}</b>
        </div>

        <div class="risk-row medio">
        <span>Médio</span>
        <div class="risk-bar"><span style="width:${medioPct}%"></span></div>
        <b>${riskCounts["Médio"]}</b>
        </div>

        <div class="risk-row baixo">
        <span>Baixo</span>
        <div class="risk-bar"><span style="width:${baixoPct}%"></span></div>
        <b>${riskCounts["Baixo"]}</b>
        </div>

        </div>
        `;

    if (!gaps.length) {
        html += `<div class="report-empty">
            <i data-lucide="party-popper" style="width:32px;height:32px;"></i>
            <div>Sem gaps identificados</div>
            <div class="report-empty-sub">Todos os controlos estão implementados ou N/A.</div>
        </div>`;
    } else {
        sorted.forEach(aTitle => {
            const { icon, items } = byArea[aTitle];
            const sortedItems = [...items].sort((a, b) => (RISK_ORDER[a.risk] ?? 9) - (RISK_ORDER[b.risk] ?? 9));

            html += `<div class="report-area">
                <div class="report-area-head">
                    <span class="report-area-icon"><i data-lucide="${esc(icon)}"></i></span>
                    <span class="report-area-title">${esc(aTitle)}</span>
                    <span class="report-area-count">${sortedItems.length} item${sortedItems.length > 1 ? "s" : ""}</span>
                </div>`;

            sortedItems.forEach((a, i) => {
                const isGap = a.answer === "NO";
                const rHex = RISK_COLOR_HEX[a.risk] || "#6b7280";
                html += `
                <div class="report-item ${i < sortedItems.length - 1 ? "has-border" : ""}">
                    <div class="report-item-top">
                        <div class="report-item-q">${esc(a.q)}</div>
                        <div class="report-item-badges">
                            <span class="state-badge ${isGap ? "gap" : "partial"}">${isGap ? "GAP" : "PARCIAL"}</span>
                            <span class="risk-badge" style="--rc:${rHex};">${esc(a.risk)}</span>
                        </div>
                    </div>
                    <div class="report-item-action">
                        <b>Ação:</b>
                        <span contenteditable="true" data-action="${esc(a.id)}" class="editable">${esc(a.action)}</span>
                    </div>
                    ${a.notes ? `<div class="report-item-notes">
                        <b>Obs.:</b>
                        <span contenteditable="true" data-notes="${esc(a.id)}" class="editable">${esc(a.notes)}</span>
                    </div>` : ""}
                    <div class="report-item-refs">
                        <span class="meta-ref">QNRCS: <b>${esc(a.qnrcs)}</b></span>
                        <span class="meta-ref">NIS2: <b>${esc(a.nis2)}</b></span>
                    </div>
                </div>`;
            });
            html += `</div>`;
        });
    }

    if (nas.length) {
        html += `<div class="report-na">
            <div class="report-na-title">Itens não aplicáveis</div>
            ${nas.map(a => `<div class="report-na-item">
                <span class="meta-id">${esc(a.id)}</span> — ${esc(a.q)}
            </div>`).join("")}
        </div>`;
    }

    return html;
}

/* ─── READ EDITS ──────────────────────────────────────────────────────────── */
function readEdits() {
    const actions = {}, notes = {};
    $$("[data-action]").forEach(el => { actions[el.dataset.action] = el.textContent.trim(); });
    $$("[data-notes]").forEach(el => { notes[el.dataset.notes] = el.textContent.trim(); });
    return { actions, notes };
}

/* ─── PDF via pdfmake (texto selecionável para Pinecone) ─────────────────── */
function buildPdfDefinition() {
    const sc = calcScore();
    const { actions, notes } = readEdits();
    const now = new Date().toLocaleDateString("pt-PT");
    const company = meta.company || "Techbase GRC";
    const sector = meta.sector || "—";

    const allItems = AREAS.flatMap(a => a.items.map(q => ({
        ...q, areaTitle: a.title,
        ...(answers[q.id] || { answer: null, notes: "" }),
        actionText: actions[q.id] || q.action,
        notesText: notes[q.id] || answers[q.id]?.notes || "",
    })));

    const gaps = allItems.filter(a => a.answer === "NO" || a.answer === "PARTIAL");
    const nas = allItems.filter(a => a.answer === "NA");

    const byArea = {};
    gaps.forEach(a => {
        if (!byArea[a.areaTitle]) byArea[a.areaTitle] = [];
        byArea[a.areaTitle].push(a);
    });
    const sortedAreaTitles = Object.keys(byArea).sort((a, b) => {
        const w = k => Math.min(...byArea[k].map(i => RISK_ORDER[i.risk] ?? 9));
        return w(a) - w(b);
    });
    Object.keys(byArea).forEach(k => byArea[k].sort((a, b) => (RISK_ORDER[a.risk] ?? 9) - (RISK_ORDER[b.risk] ?? 9)));

    const DARK = "#0e1625";
    const ACCENT = "#3b82f6";
    const RED = "#ef4444";
    const ORG = "#f97316";
    const GRN = "#22c55e";
    const YLW = "#eab308";
    const GRAY = "#6b7280";
    const RCOLOR = { Crítico: RED, Alto: ORG, Médio: YLW, Baixo: GRN };

    const stateLabel = { YES: "SIM", NO: "NÃO", PARTIAL: "PARCIAL", NA: "N/A" };
    const stateColor = { YES: GRN, NO: RED, PARTIAL: YLW, NA: GRAY };

    const content = [];

    /* ── CAPA ── */
    content.push(
        { canvas: [{ type: "rect", x: 0, y: 0, w: 515, h: 780, color: DARK }], absolutePosition: { x: 40, y: 40 } },
        { text: " ", margin: [0, 50] },
        { text: "TECHBASE GRC", color: ACCENT, bold: true, fontSize: 10, alignment: "center", characterSpacing: 3, margin: [0, 60, 0, 6] },
        { text: "Plano de Segurança", color: "#ffffff", bold: true, fontSize: 34, alignment: "center", margin: [0, 0, 0, 6] },
        { text: "Cyberplanner · QNRCS / NIS2", color: "#6b7280", fontSize: 12, alignment: "center", margin: [0, 0, 0, 56] },
        {
            table: {
                widths: ["*", "*", "*"],
                body: [[
                    { stack: [{ text: "EMPRESA", color: "#4b5563", fontSize: 8, characterSpacing: 1 }, { text: company, color: "#f9fafb", bold: true, fontSize: 13, margin: [0, 3, 0, 0] }], border: [false, false, true, false], borderColor: ["", "", "#1f2937", ""], margin: [20, 10, 20, 10] },
                    { stack: [{ text: "SETOR", color: "#4b5563", fontSize: 8, characterSpacing: 1 }, { text: sector, color: "#f9fafb", bold: true, fontSize: 13, margin: [0, 3, 0, 0] }], border: [false, false, true, false], borderColor: ["", "", "#1f2937", ""], margin: [20, 10, 20, 10], alignment: "center" },
                    { stack: [{ text: "DATA", color: "#4b5563", fontSize: 8, characterSpacing: 1 }, { text: now, color: "#f9fafb", bold: true, fontSize: 13, margin: [0, 3, 0, 0] }], border: [false, false, false, false], margin: [20, 10, 20, 10], alignment: "right" },
                ]],
            },
            layout: { hLineColor: () => "#1f2937", vLineColor: () => "#1f2937", hLineWidth: () => 1, vLineWidth: () => 1, paddingLeft: () => 0, paddingRight: () => 0, paddingTop: () => 0, paddingBottom: () => 0 },
            margin: [0, 0, 0, 36],
        },
        {
            columns: [
                { stack: [{ text: `${sc.pct}%`, color: sc.color, bold: true, fontSize: 30 }, { text: sc.label, color: "#9ca3af", fontSize: 9, margin: [0, 2] }], alignment: "center" },
                { stack: [{ text: `${sc.yes}`, color: GRN, bold: true, fontSize: 30 }, { text: "Implementado", color: "#9ca3af", fontSize: 9, margin: [0, 2] }], alignment: "center" },
                { stack: [{ text: `${sc.partial}`, color: YLW, bold: true, fontSize: 30 }, { text: "Parcial", color: "#9ca3af", fontSize: 9, margin: [0, 2] }], alignment: "center" },
                { stack: [{ text: `${sc.no}`, color: RED, bold: true, fontSize: 30 }, { text: "Gap", color: "#9ca3af", fontSize: 9, margin: [0, 2] }], alignment: "center" },
                { stack: [{ text: `${sc.na}`, color: GRAY, bold: true, fontSize: 30 }, { text: "N/A", color: "#9ca3af", fontSize: 9, margin: [0, 2] }], alignment: "center" },
            ],
            margin: [20, 0, 20, 0],
        },
        { text: " ", margin: [0, 50] },
        { text: "Gerado pelo Techbase GRC Cyberplanner · Documento confidencial · Uso interno.\nAs ações devem ser implementadas como tarefas, evidências e políticas no módulo GRC.", color: "#374151", fontSize: 9, alignment: "center", margin: [40, 0] },
        { text: " ", pageBreak: "after" }
    );

    /* ── PLANO DE AÇÃO ── */
    content.push(
        { text: "Plano de Ação", style: "h1" },
        { text: `${company} · ${now} · Score: ${sc.pct}% (${sc.label})`, style: "sub", margin: [0, 0, 0, 20] }
    );

    if (!gaps.length) {
        content.push({ text: "Sem gaps ou itens parciais identificados.", style: "p", color: GRAY });
    } else {
        sortedAreaTitles.forEach(aTitle => {
            content.push({ text: aTitle, style: "h2", margin: [0, 14, 0, 8] });
            byArea[aTitle].forEach(a => {
                const isGap = a.answer === "NO";
                const rColor = RCOLOR[a.risk] || GRAY;
                content.push({
                    table: {
                        widths: ["*", 110],
                        body: [
                            [
                                { text: a.q, bold: true, fontSize: 10, color: "#111827" },
                                {
                                    stack: [
                                        {
                                            text: isGap ? "GAP" : "PARCIAL", color: "#fff", bold: true, fontSize: 9, alignment: "center",
                                            background: isGap ? RED : ORG, margin: [0, 0, 0, 4]
                                        },
                                        { text: `Risco: ${a.risk}`, color: rColor, fontSize: 9, bold: true, alignment: "center" },
                                    ],
                                },
                            ],
                            [
                                {
                                    stack: [
                                        { text: [{ text: "Ação: ", bold: true, fontSize: 9, color: "#374151" }, { text: a.actionText || a.action, fontSize: 9, color: "#374151" }] },
                                        ...(a.notesText ? [{ text: [{ text: "Obs.: ", bold: true, fontSize: 9, color: "#6b7280" }, { text: a.notesText, fontSize: 9, color: "#6b7280" }], margin: [0, 4, 0, 0] }] : []),
                                    ],
                                },
                                {
                                    stack: [
                                        { text: `ID: ${a.id}`, fontSize: 8, color: "#9ca3af" },
                                        { text: `QNRCS: ${a.qnrcs}`, fontSize: 8, color: "#9ca3af", margin: [0, 2] },
                                        { text: `NIS2: ${a.nis2}`, fontSize: 8, color: "#9ca3af" },
                                    ],
                                    margin: [4, 0],
                                },
                            ],
                        ],
                    },
                    layout: {
                        hLineColor: () => "#e5e7eb", vLineColor: () => "#e5e7eb",
                        hLineWidth: () => 1, vLineWidth: () => 1,
                        paddingLeft: () => 10, paddingRight: () => 10,
                        paddingTop: () => 8, paddingBottom: () => 8,
                    },
                    margin: [0, 0, 0, 8],
                });
            });
        });
    }

    if (nas.length) {
        content.push({ text: "Itens não aplicáveis", style: "h2", margin: [0, 16, 0, 6] });
        nas.forEach(a => content.push({
            text: [{ text: `${a.id}`, color: GRAY, fontSize: 9, bold: true }, { text: ` — ${a.q}`, fontSize: 9, color: "#374151" }],
            margin: [0, 2],
        }));
    }

    /* ── ANEXO: mapeamento completo (essencial para Pinecone) ── */
    content.push(
        { text: "Anexo — Mapeamento Completo (QNRCS / NIS2)", style: "h1", pageBreak: "before" },
        { text: `${company} · ${now} · Todos os ${allItems.length} controlos avaliados com estado, risco e referências normativas.`, style: "sub", margin: [0, 0, 0, 16] }
    );

    const mapRows = allItems.map(a => [
        { text: a.id, fontSize: 8, color: GRAY, font: "Roboto" },
        { text: a.areaTitle, fontSize: 8, color: "#374151" },
        { text: a.q, fontSize: 8, color: "#111827" },
        { text: stateLabel[a.answer] || "—", fontSize: 8, bold: true, color: stateColor[a.answer] || GRAY },
        { text: a.risk || "—", fontSize: 8, bold: true, color: RCOLOR[a.risk] || GRAY },
        { text: a.qnrcs, fontSize: 8, color: "#6b7280" },
        { text: a.nis2, fontSize: 8, color: "#6b7280" },
        { text: (actions[a.id] || a.action), fontSize: 8, color: "#374151" },
    ]);

    content.push({
        table: {
            headerRows: 1,
            widths: [42, 60, "*", 42, 40, 52, 36, "*"],
            body: [
                ["ID", "Área", "Controlo", "Estado", "Risco", "QNRCS", "NIS2", "Ação recomendada"].map(h => ({
                    text: h, fontSize: 8, bold: true, color: "#6b7280", fillColor: "#f3f4f6",
                })),
                ...mapRows,
            ],
        },
        layout: "lightHorizontalLines",
    });

    return {
        pageSize: "A4",
        pageMargins: [40, 50, 40, 50],
        footer(currentPage, pageCount) {
            return {
                columns: [
                    { text: company, fontSize: 8, color: "#9ca3af", margin: [40, 0, 0, 0] },
                    { text: `Plano de Segurança · ${now} · ${currentPage} / ${pageCount}`, fontSize: 8, color: "#9ca3af", alignment: "right", margin: [0, 0, 40, 0] },
                ],
                margin: [0, 10],
            };
        },
        styles: {
            h1: { fontSize: 20, bold: true, color: "#111827", margin: [0, 0, 0, 4] },
            h2: { fontSize: 13, bold: true, color: "#1f2937" },
            sub: { fontSize: 10, color: "#6b7280" },
            p: { fontSize: 10, lineHeight: 1.4, color: "#374151" },
        },
        defaultStyle: { font: "Roboto", fontSize: 10, lineHeight: 1.3 },
        content,
    };
}

function downloadPdf() {
    if (!window.pdfMake?.createPdf) {
        toast("PDF", "pdfmake ainda não carregou. Aguarda 2s.", "err"); return;
    }
    const def = buildPdfDefinition();
    const slug = (meta.company || "empresa").replace(/\s+/g, "_").toLowerCase().replace(/[^a-z0-9_]/g, "");
    const dt = new Date().toISOString().slice(0, 10);
    window.pdfMake.createPdf(def).download(`techbase_plano_${slug}_${dt}.pdf`);
    toast("PDF gerado", "Download iniciado — texto selecionável ✓");
}

/* ─── SEND TO DOCS ────────────────────────────────────────────────────────── */
function sendToDocs() {
    const { actions, notes } = readEdits();
    const sc = calcScore();
    const payload = {
        type: "security_plan", created_at: new Date().toISOString(),
        meta, score: sc, answers, actions, notes,
        stats: { total: sc.total, yes: sc.yes, partial: sc.partial, no: sc.no, na: sc.na },
    };
    // TODO: POST /api/documents/cyberplan
    console.log("[Techbase] sendToDocs:", payload);
    toast("Enviado para Evidências", "Plano guardado como evidência (mock) ✓");
}

/* ─── META MODAL ──────────────────────────────────────────────────────────── */
function ensureMetaModal() {
    if (document.getElementById("metaModal")) return;
    const wrap = document.createElement("div");
    wrap.id = "metaModal";
    wrap.className = "modal-overlay is-hidden";
    wrap.setAttribute("aria-hidden", "true");
    wrap.innerHTML = `
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header" style="margin-bottom:16px;">
                <div>
                    <div class="muted" style="font-size:12px; margin-bottom:4px;">Antes de gerar o plano</div>
                    <div style="font-size:16px; font-weight:800;">Identificação</div>
                </div>
            </div>
            <div style="margin-bottom:12px;">
                <label>Empresa / Organização</label>
                <input type="text" id="metaCompany" value="${esc(meta.company)}" placeholder="Ex.: Techbase Lda.">
            </div>
            <div style="margin-bottom:20px;">
                <label>Setor de atividade</label>
                <select id="metaSector">
                    <option value="">Selecionar…</option>
                    ${["Tecnologia & Software", "Serviços Financeiros", "Saúde & Farmacêutica", "Indústria & Manufatura", "Comércio & Retalho", "Educação", "Administração Pública", "Energia & Utilities", "Transportes & Logística", "Outro"]
            .map(s => `<option ${meta.sector === s ? "selected" : ""}>${s}</option>`).join("")}
                </select>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button id="btnMetaSkip" class="btn" type="button">Pular</button>
                <button id="btnMetaSave" class="btn primary" type="button">Continuar</button>
            </div>
        </div>
    `;
    document.body.appendChild(wrap);
    document.getElementById("btnMetaSave").addEventListener("click", () => {
        meta.company = document.getElementById("metaCompany").value.trim() || "—";
        meta.sector = document.getElementById("metaSector").value || "—";
        meta.date = new Date().toLocaleDateString("pt-PT");
        save(SK_META, meta);
        closeModal("metaModal");
        openReport();
    });
    document.getElementById("btnMetaSkip").addEventListener("click", () => {
        closeModal("metaModal");
        openReport();
    });
}

function openReport() {
    saveAnswers();
    const body = $("#reportBody");
    if (body) body.innerHTML = buildReportHtml();
    const title = $("#reportTitle");
    if (title) title.textContent = meta.company ? `Plano de Segurança — ${meta.company}` : "Plano de Segurança";
    openModal("reportModal");
    if (window.lucide) window.lucide.createIcons();
}

function showReport() {
    ensureMetaModal();
    if (!meta.company) openModal("metaModal");
    else openReport();
}

/* ─── WIRE ────────────────────────────────────────────────────────────────── */
function wire() {
    // botão "Ver Plano" no header
    $("#btnFinishQ")?.addEventListener("click", () => { saveAnswers(); showReport(); });

    // botão "Exportar PDF" fora do modal (bottom bar)
    $("#btnExportPdf")?.addEventListener("click", () => { saveAnswers(); showReport(); });

    // modal
    $("#btnCloseReport")?.addEventListener("click", () => closeModal("reportModal"));
    $("#btnGenPdf")?.addEventListener("click", () => downloadPdf());
    $("#btnSendToDocs")?.addEventListener("click", () => sendToDocs());

    document.addEventListener("keydown", e => {
        if (e.key !== "Escape") return;
        closeModal("reportModal");
        document.getElementById("metaModal") && closeModal("metaModal");
    });

    document.addEventListener("visibilitychange", () => { if (document.hidden) saveAnswers(); });
    window.addEventListener("beforeunload", saveAnswers);

    // fechar modal ao clicar fora (overlay)
    document.querySelectorAll(".modal-overlay").forEach(modal => {
        modal.addEventListener("click", (e) => {

            const card = modal.querySelector(".modal-card");

            if (!card.contains(e.target)) {
                modal.classList.add("is-hidden");
                modal.setAttribute("aria-hidden", "true");
                document.body.style.overflow = "";
            }

        });
    });
}

/* ─── INIT ────────────────────────────────────────────────────────────────── */
(function init() {
    buildTabs();
    buildForm();
    updateKPIs();
    wire();
    if (window.lucide) window.lucide.createIcons();
    // re-render icons after dynamic content
    setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 100);
})();