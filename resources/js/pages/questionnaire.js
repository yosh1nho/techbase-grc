// resources/js/pages/questionnaire.js
const $ = (s) => document.querySelector(s);

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

function toast(title, msg) {
    const t = $("#toast");
    $("#toastTitle").textContent = title;
    $("#toastMsg").textContent = msg;
    t.style.display = "";
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => (t.style.display = "none"), 2600);
}

function escapeHtml(str) {
    return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

/**
 * FCC-like Areas
 * - Corpo do plano é por "áreas"
 * - Cada item pode ter metadata (controlos / NIS2) só para anexo/mapeamento
 *
 * Nota: isto é mock. Dá para expandir o catálogo à vontade sem mexer no motor.
 */
const AREAS = [
    {
        id: "PDS",
        title: "Privacy & Data Security",
        description: "Inventário de dados, acessos, retenção, cifragem e backups.",
        items: [
            { id: "PDS_01", question: "Existe inventário dos tipos de dados (PII/saúde/financeiro) e onde são armazenados?", risk_if_no: "Alto", controls: "ID.GA-1, ID.BE-3", nis2: "Art. 21", action: "Criar registo de dados + owners + classificação de criticidade." },
            { id: "PDS_02", question: "Os dados sensíveis têm controlos de acesso por função (RBAC) e MFA em sistemas críticos?", risk_if_no: "Crítico", controls: "PR.AC-1", nis2: "Art. 21", action: "Aplicar least privilege + MFA + revisão de contas privilegiadas." },
            { id: "PDS_03", question: "Existe política de retenção e eliminação segura (ex.: dados antigos e backups)?", risk_if_no: "Médio", controls: "PR.IP-6", nis2: "Art. 21", action: "Definir prazos, owners, e método de eliminação segura." },
            { id: "PDS_04", question: "Dados sensíveis são cifrados em repouso e em trânsito (TLS/HTTPS)?", risk_if_no: "Alto", controls: "PR.DS-1", nis2: "Art. 21", action: "Rever cifragem, TLS, chaves e rotação." },
            { id: "PDS_05", question: "Existe backup regular e testes de restauração para dados críticos?", risk_if_no: "Crítico", controls: "PR.IP-4, PR.PT-4", nis2: "Art. 21", action: "Implementar 3-2-1 + testes periódicos + evidências." },
        ],
    },

    {
        id: "NS",
        title: "Network Security",
        description: "Firewall, Wi-Fi, VPN, segmentação, hardening e patches.",
        items: [
            { id: "NS_01", question: "Há firewall/regras documentadas e revisão periódica de portas expostas?", risk_if_no: "Alto", controls: "PR.AC-4", nis2: "Art. 21", action: "Rever regras, fechar portas, e manter inventário de serviços expostos." },
            { id: "NS_02", question: "Wi-Fi interno usa WPA2/WPA3 e rede de convidados é separada?", risk_if_no: "Alto", controls: "PR.AC-1", nis2: "Art. 21", action: "Separar VLAN guest, senha forte, e desativar WPS." },
            { id: "NS_03", question: "Existe segmentação de rede (ex.: separar servidores, usuários, IoT)?", risk_if_no: "Médio", controls: "PR.AC-5", nis2: "Art. 21", action: "Criar segmentação por criticidade e controlar tráfego entre segmentos." },
            { id: "NS_04", question: "Existe VPN segura para acessos remotos e MFA no acesso remoto?", risk_if_no: "Crítico", controls: "PR.AC-1", nis2: "Art. 21", action: "VPN com MFA, políticas de acesso e logging." },
            { id: "NS_05", question: "Há processo de patching para SO/apps/equipamentos de rede com evidências?", risk_if_no: "Alto", controls: "PR.IP-12", nis2: "Art. 21", action: "Definir janela de patching, prioridades e registo de evidências." },
        ],
    },

    {
        id: "EM",
        title: "Email & Messaging",
        description: "Phishing, SPF/DKIM/DMARC, treino, e proteções.",
        items: [
            { id: "EM_01", question: "Existem proteções anti-phishing e filtros de spam configurados?", risk_if_no: "Alto", controls: "DE.CM-1", nis2: "Art. 21", action: "Ativar filtros avançados + quarentena + alertas." },
            { id: "EM_02", question: "Domínio tem SPF e DKIM configurados?", risk_if_no: "Médio", controls: "PR.DS-2", nis2: "Art. 21", action: "Publicar SPF e DKIM, validar alinhamento." },
            { id: "EM_03", question: "Existe DMARC (pelo menos em modo monitor) e reporting?", risk_if_no: "Médio", controls: "PR.DS-2", nis2: "Art. 21", action: "Ativar DMARC (p=none → quarantine → reject)." },
            { id: "EM_04", question: "Há política para anexos/macro e bloqueios de tipos perigosos?", risk_if_no: "Alto", controls: "PR.IP-1", nis2: "Art. 21", action: "Bloquear executáveis/macro, reforçar sandbox." },
            { id: "EM_05", question: "Equipa recebe treino anti-phishing e testes periódicos?", risk_if_no: "Alto", controls: "PR.AT-1", nis2: "Art. 21", action: "Programa de awareness + simulações + registo." },
        ],
    },

    {
        id: "MD",
        title: "Mobile Devices",
        description: "MDM, BYOD, cifragem, bloqueio remoto e updates.",
        items: [
            { id: "MD_01", question: "Dispositivos móveis corporativos têm bloqueio, PIN forte e biometria quando aplicável?", risk_if_no: "Médio", controls: "PR.AC-1", nis2: "Art. 21", action: "Política de bloqueio + tempos de inatividade + MFA." },
            { id: "MD_02", question: "Existe MDM (ou equivalente) para gestão e compliance dos dispositivos?", risk_if_no: "Alto", controls: "PR.IP-1", nis2: "Art. 21", action: "Definir baseline, inventário, e políticas no MDM." },
            { id: "MD_03", question: "É possível limpar remotamente dispositivos perdidos/roubados?", risk_if_no: "Alto", controls: "RS.MI-1", nis2: "Art. 21", action: "Ativar wipe remoto + processo de reporte." },
            { id: "MD_04", question: "Apps são controladas (store gerida) e fontes desconhecidas bloqueadas?", risk_if_no: "Médio", controls: "PR.IP-1", nis2: "Art. 21", action: "Permitir apenas apps aprovadas + bloquear sideloading." },
            { id: "MD_05", question: "Existe política BYOD (se aplicável) e separação de dados corporativos?", risk_if_no: "Médio", controls: "PR.AC-1", nis2: "Art. 21", action: "Definir BYOD, containerização e requisitos mínimos." },
        ],
    },

    {
        id: "EMP",
        title: "Employees & Access",
        description: "Onboarding/offboarding, privilégios, formação e disciplina.",
        items: [
            { id: "EMP_01", question: "Existe processo de onboarding/offboarding com remoção imediata de acessos?", risk_if_no: "Crítico", controls: "PR.AC-4", nis2: "Art. 21", action: "Checklist de entradas/saídas + revisão de acessos." },
            { id: "EMP_02", question: "Contas privilegiadas são controladas (separadas) e auditadas?", risk_if_no: "Crítico", controls: "PR.AC-1", nis2: "Art. 21", action: "Separar contas admin, MFA, logs e revisões periódicas." },
            { id: "EMP_03", question: "Existe política de passwords e/ou passkeys + MFA?", risk_if_no: "Alto", controls: "PR.AC-1", nis2: "Art. 21", action: "Definir requisitos, e reduzir risco de credenciais fracas." },
            { id: "EMP_04", question: "Há formação anual obrigatória e registo de participação?", risk_if_no: "Médio", controls: "PR.AT-1", nis2: "Art. 21", action: "Criar plano de formação e evidências." },
            { id: "EMP_05", question: "Existe política disciplinar/aceitação de uso (AUP) assinada?", risk_if_no: "Médio", controls: "GV.PO-1", nis2: "Art. 21", action: "Criar AUP + recolher assinaturas e versionar." },
        ],
    },

    {
        id: "WS",
        title: "Website & Public Services",
        description: "HTTPS, WAF, updates, backups e vulnerabilidades.",
        items: [
            { id: "WS_01", question: "Website e serviços públicos usam HTTPS/TLS atualizado e HSTS?", risk_if_no: "Alto", controls: "PR.DS-2", nis2: "Art. 21", action: "Rever TLS, certificados, HSTS e headers." },
            { id: "WS_02", question: "CMS/plugins/frameworks são atualizados regularmente e com evidências?", risk_if_no: "Alto", controls: "PR.IP-12", nis2: "Art. 21", action: "Ciclo de updates + inventário de dependências." },
            { id: "WS_03", question: "Existe WAF/Rate limiting para mitigar ataques comuns?", risk_if_no: "Médio", controls: "DE.CM-1", nis2: "Art. 21", action: "Ativar WAF + regras OWASP + rate limit." },
            { id: "WS_04", question: "Existem backups e plano de rollback de deployments?", risk_if_no: "Alto", controls: "PR.IP-4", nis2: "Art. 21", action: "Backups + rollback + testes de recuperação." },
            { id: "WS_05", question: "Existe rotina de scanning (SAST/DAST) ou pentest periódico?", risk_if_no: "Médio", controls: "ID.RA-1", nis2: "Art. 21", action: "Definir scanning e correções priorizadas." },
        ],
    },

    {
        id: "IR",
        title: "Incident Response & Reporting",
        description: "Deteção, playbooks, comunicação e recuperação.",
        items: [
            { id: "IR_01", question: "Existe processo de resposta a incidentes (papéis, contactos, playbooks)?", risk_if_no: "Crítico", controls: "RS.RP-1", nis2: "Art. 21", action: "Criar IRP + papéis + fluxos + contactos." },
            { id: "IR_02", question: "Existe logging centralizado e retenção suficiente para investigação?", risk_if_no: "Alto", controls: "DE.CM-1", nis2: "Art. 21", action: "Centralizar logs + retenção + correlação." },
            { id: "IR_03", question: "Há testes (tabletop) do plano de incidentes pelo menos 1x/ano?", risk_if_no: "Médio", controls: "RS.IM-1", nis2: "Art. 21", action: "Executar tabletop e guardar evidências." },
            { id: "IR_04", question: "Existe plano de comunicação (interno/externo) em incidentes?", risk_if_no: "Médio", controls: "RS.CO-2", nis2: "Art. 23/Art. 24 (depende)", action: "Definir comunicação, templates e aprovações." },
            { id: "IR_05", question: "Existe plano de recuperação (RTO/RPO) para serviços críticos?", risk_if_no: "Alto", controls: "PR.PT-4", nis2: "Art. 21", action: "Definir RTO/RPO + dependências + priorização." },
        ],
    },

    {
        id: "SUP",
        title: "Suppliers & Third Parties",
        description: "Risco de fornecedores, acessos, contratos e revisões.",
        items: [
            { id: "SUP_01", question: "Existe inventário de fornecedores e serviços críticos (SaaS/outsourcing)?", risk_if_no: "Alto", controls: "ID.SC-1", nis2: "Art. 21", action: "Criar lista + criticidade + owners." },
            { id: "SUP_02", question: "Contratos incluem requisitos de segurança (SLA, incidentes, backups, etc.)?", risk_if_no: "Médio", controls: "ID.SC-2", nis2: "Art. 21", action: "Adicionar cláusulas de segurança e auditoria." },
            { id: "SUP_03", question: "Acessos de terceiros são limitados, temporários e auditados?", risk_if_no: "Alto", controls: "PR.AC-1", nis2: "Art. 21", action: "Least privilege + MFA + expiração + logs." },
            { id: "SUP_04", question: "Existe avaliação periódica (questionário/score) dos fornecedores críticos?", risk_if_no: "Médio", controls: "ID.SC-3", nis2: "Art. 21", action: "Criar processo de avaliação e evidências." },
            { id: "SUP_05", question: "Há plano de contingência caso fornecedor falhe (lock-in / continuidade)?", risk_if_no: "Médio", controls: "PR.PT-4", nis2: "Art. 21", action: "Plano alternativo + exportações + backups." },
        ],
    },

    {
        id: "POL",
        title: "Policies & Governance",
        description: "Políticas versionadas, aprovações e compliance interno.",
        items: [
            { id: "POL_01", question: "As principais políticas (passwords, acessos, backups, IR, AUP) existem e estão versionadas?", risk_if_no: "Alto", controls: "GV.PO-1", nis2: "Art. 21", action: "Criar pacotes de políticas + versionamento + aprovação." },
            { id: "POL_02", question: "Existe processo de revisão periódica das políticas (ex.: anual) com evidências?", risk_if_no: "Médio", controls: "GV.PO-2", nis2: "Art. 21", action: "Definir ciclo de revisão + responsáveis + histórico." },
            { id: "POL_03", question: "Existe registo de riscos e plano de tratamento priorizado?", risk_if_no: "Alto", controls: "ID.RA-1", nis2: "Art. 21", action: "Registar riscos + matriz P×I + ações e prazos." },
            { id: "POL_04", question: "Existe inventário de ativos com owners e criticidade?", risk_if_no: "Crítico", controls: "ID.GA-1", nis2: "Art. 21", action: "Manter inventário + owners + criticidade." },
            { id: "POL_05", question: "Existem evidências anexadas (docs/prints/logs) para suportar conformidade?", risk_if_no: "Médio", controls: "GV.OV-1", nis2: "Art. 21", action: "Criar repositório de evidências e trilha de auditoria." },
        ],
    },
];

// storage
const STORAGE_ANSWERS = "tb_cyberplanner_answers_v1";
const STORAGE_AREAS = "tb_cyberplanner_areas_v1";

function loadJson(key, fallback) {
    try {
        const raw = localStorage.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
    } catch {
        return fallback;
    }
}
function saveJson(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}

// state
let selectedAreas = loadJson(STORAGE_AREAS, []);
let state = loadJson(STORAGE_ANSWERS, {}); // { [qid]: { answer, notes, meta } }

let FLAT = []; // perguntas escolhidas
let idx = 0;

// helpers
function buildFlatFromSelectedAreas() {
    const chosen = selectedAreas.length ? new Set(selectedAreas) : new Set(AREAS.map((a) => a.id));
    FLAT = AREAS
        .filter((a) => chosen.has(a.id))
        .flatMap((a) =>
            a.items.map((q) => ({
                ...q,
                areaId: a.id,
                areaTitle: a.title,
            }))
        );
}

function counts() {
    const vals = Object.values(state);
    const by = (v) => vals.filter((x) => x?.answer === v).length;
    $("#kYes").textContent = by("YES");
    $("#kPartial").textContent = by("PARTIAL");
    $("#kNo").textContent = by("NO");
    $("#kNA").textContent = by("NA");
}

function setProgress() {
    const total = FLAT.length;
    $("#qProgressText").textContent = `${Math.min(idx + 1, total)}/${total}`;
    const p = total ? (idx / Math.max(1, total - 1)) * 100 : 0;
    $("#qProgressBar").style.width = `${Math.max(0, Math.min(100, p))}%`;
}

function fadeSwap(fn) {
    const card = $("#qCard");
    card.style.opacity = "0";
    card.style.transform = "translateY(6px)";
    setTimeout(() => {
        fn();
        card.style.opacity = "1";
        card.style.transform = "translateY(0)";
    }, 140);
}

function showAreaPicker() {
    $("#areaPicker").style.display = "";
    $("#wizardWrap").style.display = "none";
    $("#qAreaChip").textContent = "Área —";
    $("#qProgressText").textContent = "0/0";
    $("#qProgressBar").style.width = "0%";
}

function showWizard() {
    $("#areaPicker").style.display = "none";
    $("#wizardWrap").style.display = "";
}

function renderAreaPicker() {
    const grid = $("#areaGrid");
    grid.innerHTML = "";

    AREAS.forEach((a) => {
        const checked = selectedAreas.includes(a.id);

        const card = document.createElement("label");
        card.className = "panel";
        card.style.cursor = "pointer";
        card.style.userSelect = "none";
        card.innerHTML = `
      <div style="display:flex; gap:10px; align-items:flex-start;">
        <input type="checkbox" data-area="${a.id}" ${checked ? "checked" : ""} style="margin-top:3px;">
        <div>
          <div style="font-weight:900;">${escapeHtml(a.title)}</div>
          <div class="hint" style="margin-top:6px;">${escapeHtml(a.description)}</div>
          <div class="muted" style="margin-top:8px; font-size:12px;">
            Perguntas: <b>${a.items.length}</b>
          </div>
        </div>
      </div>
    `;
        grid.appendChild(card);
    });

    grid.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
        cb.addEventListener("change", () => {
            selectedAreas = Array.from(grid.querySelectorAll('input[type="checkbox"]:checked')).map((x) => x.dataset.area);
            saveJson(STORAGE_AREAS, selectedAreas);
        });
    });
}

function render() {
    const q = FLAT[idx];
    if (!q) return;

    fadeSwap(() => {
        $("#qAreaChip").textContent = q.areaTitle;

        $("#qMetaTop").innerHTML = `
      <span class="chip">ID: <b>${escapeHtml(q.id)}</b></span>
      <span class="chip">Área: <b>${escapeHtml(q.areaTitle)}</b></span>
      <span class="chip">QNRCS: <b>${escapeHtml(q.controls || "—")}</b></span>
      <span class="chip">NIS2: <b>${escapeHtml(q.nis2 || "—")}</b></span>
      <span class="chip">Risco se “Não”: <b>${escapeHtml(q.risk_if_no || "—")}</b></span>
    `;

        $("#qText").textContent = q.question;

        const prev = state[q.id] || {};
        $("#qAnswer").value = prev.answer && prev.answer !== "NA" ? prev.answer : "YES";
        $("#qNotes").value = prev.notes || "";
        $("#qHint").textContent = q.action || "—";

        $("#btnPrevQ").disabled = idx === 0;

        const isLast = idx === FLAT.length - 1;
        $("#btnNextQ").style.display = isLast ? "none" : "";
        $("#btnFinishQ").style.display = isLast ? "" : "none";
    });

    setProgress();
    counts();
}

function saveCurrent(answerOverride = null) {
    const q = FLAT[idx];
    if (!q) return;

    const answer = answerOverride || $("#qAnswer").value;
    const notes = ($("#qNotes").value || "").trim();

    state[q.id] = {
        answer,
        notes,
        meta: {
            id: q.id,
            question: q.question,
            area: q.areaTitle,
            controls: q.controls,
            nis2: q.nis2,
            risk_if_no: q.risk_if_no,
            action: q.action,
        },
    };

    saveJson(STORAGE_ANSWERS, state);
}

function next() {
    if (idx < FLAT.length - 1) idx += 1;
    render();
}

function prev() {
    if (idx > 0) idx -= 1;
    render();
}

function buildReportHtml() {
    const answers = Object.values(state);
    const relevantIds = new Set(FLAT.map((q) => q.id)); // só o escopo atual

    const scoped = answers.filter((a) => relevantIds.has(a.meta?.id));
    const gaps = scoped.filter((a) => a.answer === "NO" || a.answer === "PARTIAL");
    const nas = scoped.filter((a) => a.answer === "NA");

    const byArea = {};
    gaps.forEach((a) => {
        const k = a.meta.area || "Outros";
        byArea[k] = byArea[k] || [];
        byArea[k].push(a);
    });

    const now = new Date().toISOString().slice(0, 10);

    let html = `
    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
      <div>
        <div style="font-size:18px; font-weight:900;">Plano de Segurança — Draft</div>
        <div class="muted">Gerado em ${now} (mock) • Escopo: <b>${selectedAreas.length || AREAS.length}</b> áreas</div>
      </div>
      <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <span class="chip">Não/Parcial: <b>${gaps.length}</b></span>
        <span class="chip">N/A: <b>${nas.length}</b></span>
      </div>
    </div>

    <div style="height:12px"></div>

    <div class="panel">
      <b>Nota</b>
      <p class="muted" style="margin-top:6px">
        Este plano é um <b>mock</b>. O botão “Enviar para Documentos/Evidências” ainda não integra com backend.
      </p>
    </div>

    <div style="height:12px"></div>
  `;

    const areaNames = Object.keys(byArea);
    if (!areaNames.length) {
        html += `<div class="panel">
      <div style="font-weight:900;">Sem gaps</div>
      <div class="muted" style="margin-top:6px">
        Não há itens “Não” ou “Parcial” no escopo atual.
      </div>
    </div>`;
    } else {
        areaNames.forEach((area) => {
            html += `<div class="panel">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap;">
          <div style="font-size:16px; font-weight:900;">${escapeHtml(area)}</div>
          <span class="chip">Itens: <b>${byArea[area].length}</b></span>
        </div>
        <div style="height:10px"></div>
      `;

            byArea[area].forEach((a) => {
                const tag =
                    a.answer === "NO"
                        ? `<span class="tag bad"><span class="s"></span> GAP</span>`
                        : `<span class="tag warn"><span class="s"></span> PARTIAL</span>`;

                html += `
          <div class="panel" style="margin-bottom:10px">
            <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
              <div style="font-weight:800;">${escapeHtml(a.meta.question)}</div>
              <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                ${tag}
                <span class="chip">QNRCS: <b>${escapeHtml(a.meta.controls || "—")}</b></span>
                <span class="chip">NIS2: <b>${escapeHtml(a.meta.nis2 || "—")}</b></span>
                <span class="chip">Risco: <b>${escapeHtml(a.meta.risk_if_no || "—")}</b></span>
              </div>
            </div>

            <div style="height:8px"></div>
            <div><b>Ação sugerida</b></div>
            <div class="muted" style="margin-top:6px">${escapeHtml(a.meta.action || "—")}</div>

            ${a.notes
                        ? `
              <div style="height:10px"></div>
              <div><b>Observações</b></div>
              <div class="muted" style="margin-top:6px; white-space:pre-wrap;">${escapeHtml(a.notes)}</div>
            `
                        : ""
                    }
          </div>
        `;
            });

            html += `</div><div style="height:10px"></div>`;
        });
    }

    if (nas.length) {
        html += `<div class="panel">
      <div style="font-weight:900;">Itens marcados como “Não aplicável”</div>
      <div class="muted" style="margin-top:6px">${nas
                .map((a) => `• ${escapeHtml(a.meta.question)}`)
                .join("<br>")}</div>
    </div>`;
    }

    return html;
}

function showReport() {
    $("#reportBody").innerHTML = buildReportHtml();
    openModal("reportModal");
}

/** ===== PDF bonito (pdfmake) ===== */
function buildPdfDefinition() {
    const answers = Object.values(state);
    const relevantIds = new Set(FLAT.map((q) => q.id));
    const scoped = answers.filter((a) => relevantIds.has(a.meta?.id));

    const gaps = scoped.filter((a) => a.answer === "NO" || a.answer === "PARTIAL");
    const nas = scoped.filter((a) => a.answer === "NA");
    const yes = scoped.filter((a) => a.answer === "YES");

    const now = new Date().toISOString().slice(0, 10);
    const scopeCount = selectedAreas.length || AREAS.length;

    const byArea = {};
    gaps.forEach((a) => {
        const k = a.meta.area || "Outros";
        byArea[k] = byArea[k] || [];
        byArea[k].push(a);
    });

    // anexo mapeamento
    const mappingRows = scoped.map((a) => [
        a.meta.id,
        a.meta.area,
        a.answer === "YES" ? "SIM" : a.answer === "NO" ? "NÃO" : a.answer === "PARTIAL" ? "PARCIAL" : "N/A",
        a.meta.controls || "—",
        a.meta.nis2 || "—",
    ]);

    const content = [];

    // Capa
    content.push(
        { text: "PLANO DE SEGURANÇA", style: "coverTitle" },
        { text: "Techbase GRC — Cyberplanner (mock)", style: "coverSub" },
        { text: `Data: ${now}`, margin: [0, 18, 0, 0] },
        { text: `Escopo: ${scopeCount} áreas`, margin: [0, 4, 0, 0] },
        { text: " ", pageBreak: "after" }
    );

    // Sumário executivo
    content.push(
        { text: "Sumário Executivo", style: "h1" },
        {
            columns: [
                { text: `Itens avaliados: ${scoped.length}`, style: "kpi" },
                { text: `Gaps/Parciais: ${gaps.length}`, style: "kpi" },
                { text: `N/A: ${nas.length}`, style: "kpi" },
                { text: `Sim: ${yes.length}`, style: "kpi" },
            ],
            columnGap: 10,
            margin: [0, 10, 0, 8],
        },
        {
            text:
                "Este plano foi gerado a partir de um questionário por áreas (estilo FCC). As ações listadas devem ser transformadas em tarefas, evidências e/ou políticas no Techbase.",
            style: "p",
        },
        { text: " ", margin: [0, 6, 0, 0] }
    );

    const areaNames = Object.keys(byArea);
    if (!areaNames.length) {
        content.push({
            text: "Não há gaps/parciais no escopo atual.",
            style: "p",
            margin: [0, 8, 0, 0],
        });
    } else {
        areaNames.forEach((area) => {
            content.push({ text: area, style: "h2", margin: [0, 14, 0, 6] });

            byArea[area].forEach((a) => {
                const statusLabel = a.answer === "NO" ? "GAP" : "PARTIAL";

                content.push({
                    table: {
                        widths: ["*", "auto"],
                        body: [
                            [
                                { text: a.meta.question, style: "qTitle" },
                                { text: statusLabel, style: statusLabel === "GAP" ? "badgeBad" : "badgeWarn" },
                            ],
                            [
                                {
                                    text: [
                                        { text: "Ação sugerida: ", bold: true },
                                        a.meta.action || "—",
                                        a.notes ? "\n\n" : "",
                                        a.notes ? { text: "Observações: ", bold: true } : "",
                                        a.notes ? a.notes : "",
                                    ],
                                    style: "p",
                                },
                                {
                                    text: [
                                        { text: "QNRCS: ", bold: true },
                                        a.meta.controls || "—",
                                        "\n",
                                        { text: "NIS2: ", bold: true },
                                        a.meta.nis2 || "—",
                                        "\n",
                                        { text: "Risco: ", bold: true },
                                        a.meta.risk_if_no || "—",
                                    ],
                                    style: "metaBox",
                                },
                            ],
                        ],
                    },
                    layout: {
                        hLineColor: () => "#dddddd",
                        vLineColor: () => "#dddddd",
                        paddingLeft: () => 10,
                        paddingRight: () => 10,
                        paddingTop: () => 8,
                        paddingBottom: () => 8,
                    },
                    margin: [0, 0, 0, 10],
                });
            });
        });
    }

    if (nas.length) {
        content.push({ text: "Itens N/A", style: "h2", margin: [0, 10, 0, 6] });
        content.push({
            ul: nas.map((a) => `${a.meta.id} — ${a.meta.question}`),
            margin: [0, 0, 0, 10],
        });
    }

    // Anexo
    content.push({ text: "Anexo — Mapeamento (QNRCS / NIS2)", style: "h1", pageBreak: "before" });
    content.push({
        table: {
            headerRows: 1,
            widths: [70, "*", 60, 110, 80],
            body: [
                ["ID", "Área", "Estado", "QNRCS", "NIS2"],
                ...mappingRows,
            ],
        },
        layout: "lightHorizontalLines",
        fontSize: 9,
    });

    return {
        pageSize: "A4",
        pageMargins: [40, 50, 40, 50],
        footer: function (currentPage, pageCount) {
            return {
                text: `Techbase GRC • Plano de Segurança (mock) • Página ${currentPage} de ${pageCount}`,
                alignment: "center",
                fontSize: 9,
                margin: [0, 12, 0, 0],
            };
        },
        styles: {
            coverTitle: { fontSize: 28, bold: true, alignment: "center", margin: [0, 120, 0, 0] },
            coverSub: { fontSize: 12, alignment: "center", margin: [0, 12, 0, 0] },
            h1: { fontSize: 18, bold: true, margin: [0, 0, 0, 6] },
            h2: { fontSize: 14, bold: true },
            p: { fontSize: 10, lineHeight: 1.25 },
            kpi: { fontSize: 10, bold: true, margin: [0, 2, 0, 2] },
            qTitle: { fontSize: 11, bold: true },
            metaBox: { fontSize: 9, lineHeight: 1.2 },
            badgeBad: { color: "white", fillColor: "#d9534f", bold: true, margin: [6, 4, 6, 4] },
            badgeWarn: { color: "white", fillColor: "#f0ad4e", bold: true, margin: [6, 4, 6, 4] },
        },
        defaultStyle: { font: "Roboto" },
        content,
    };
}

function downloadPdf() {
    const ready = window.pdfMake && window.pdfMake.createPdf;
    if (!ready) {
        toast("PDF", "pdfmake ainda não carregou. Tenta de novo em 2s.");
        return;
    }

    const def = buildPdfDefinition();
    const now = new Date().toISOString().slice(0, 10);
    window.pdfMake.createPdf(def).download(`techbase_plano_seguranca_${now}.pdf`);
    toast("PDF gerado", "Download iniciado.");
}

/** ===== Mock: enviar para Documentos/Evidências ===== */
function sendToDocumentsMock() {
    const now = new Date().toISOString();

    const payload = {
        type: "security_plan_draft",
        created_at: now,
        selected_areas: selectedAreas.length ? selectedAreas : AREAS.map((a) => a.id),
        answers: state,
        stats: {
            total: FLAT.length,
            yes: Number($("#kYes").textContent || 0),
            partial: Number($("#kPartial").textContent || 0),
            no: Number($("#kNo").textContent || 0),
            na: Number($("#kNA").textContent || 0),
        },
    };

    // mock: só loga
    console.log("[MOCK] send to Documents/Evidences payload:", payload);
    toast("Enviado (mock)", "Payload preparado (ver console).");
}

function wire() {
    // area picker
    $("#btnSelectAllAreas").addEventListener("click", () => {
        selectedAreas = AREAS.map((a) => a.id);
        saveJson(STORAGE_AREAS, selectedAreas);
        renderAreaPicker();
        toast("Áreas", "Selecionaste todas.");
    });

    $("#btnClearAreas").addEventListener("click", () => {
        selectedAreas = [];
        saveJson(STORAGE_AREAS, selectedAreas);
        renderAreaPicker();
        toast("Áreas", "Seleção limpa.");
    });

    $("#btnStartWizard").addEventListener("click", () => {
        // valida mínimo
        if (!selectedAreas.length) {
            toast("Áreas", "Seleciona pelo menos 1 área (ou clica em “Selecionar tudo”).");
            return;
        }

        buildFlatFromSelectedAreas();
        idx = 0;

        // se o escopo mudou, não apaga respostas antigas; só filtra no relatório/PDF
        showWizard();
        render();
        toast("Wizard", "Questionário iniciado.");
    });

    // wizard controls
    $("#btnBackToAreas").addEventListener("click", () => {
        saveCurrent();
        showAreaPicker();
    });

    $("#btnPrevQ").addEventListener("click", () => {
        saveCurrent();
        prev();
    });

    $("#btnNextQ").addEventListener("click", () => {
        saveCurrent();
        next();
    });

    $("#btnFinishQ").addEventListener("click", () => {
        saveCurrent();
        showReport();
    });

    $("#btnSkipQ").addEventListener("click", () => {
        saveCurrent("NA");
        next();
    });

    // report modal
    $("#btnCloseReport").addEventListener("click", () => closeModal("reportModal"));
    $("#btnGenPdf").addEventListener("click", () => downloadPdf());
    $("#btnSendToDocs").addEventListener("click", () => sendToDocumentsMock());
}

(function init() {
    // init UI
    renderAreaPicker();
    showAreaPicker();

    wire();
})();