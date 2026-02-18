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

// ============================
// PERGUNTAS (GERADAS DO TEU XLSX: sheet "Questionário")
// ============================
const QUESTIONNAIRE = [
    {
        section: 1,
        title: "Secção 1 — Gestão de Ativos",
        items: [
            { id: "Q01", question: "1.1. Existe um inventário atualizado de todos os dispositivos físicos e virtuais?", risk_if_no: "Crítico", controls: "ID.GA-1", nis2: "Art. 21 (Gestão de Ativos)", action: "Gera template \"Procedimento de Inventário\" + Cria dashboard vazio de ativos." },
            { id: "Q02", question: "1.2. Estão identificados os softwares e plataformas utilizados nos processos críticos?", risk_if_no: "Alto", controls: "ID.GA-2", nis2: "Art. 21", action: "Solicita lista de software para análise de vulnerabilidades (CVEs)." },
            { id: "Q03", question: "1.3. A organização realizou uma análise de riscos formal no último ano?", risk_if_no: "Crítico", controls: "ID.AR-1 a ID.AR-5", nis2: "Art. 21 (Análise de Risco)", action: "Inicia o módulo \"Calculadora de Risco\" (Matriz Probabilidade × Impacto)." },
            { id: "Q04", question: "1.4. Existe uma lista de todos os fornecedores e parceiros críticos?", risk_if_no: "Alto", controls: "ID.SC-1", nis2: "Art. 21 (Gestão de risco de fornecedores)", action: "Solicita lista de fornecedores e criticidade + inicia avaliação." },
            { id: "Q05", question: "1.5. Existe classificação de dados e ativos por criticidade/impacto?", risk_if_no: "Médio", controls: "ID.BE-3", nis2: "Art. 21", action: "Gera template de classificação + recomenda etiquetas e owners." },
        ],
    },
    {
        section: 2,
        title: "Secção 2 — Proteção",
        items: [
            { id: "Q06", question: "2.1. Existem políticas de controlo de acessos (RBAC/MFA) para sistemas críticos?", risk_if_no: "Crítico", controls: "PR.AC-1", nis2: "Art. 21 (Controlo de acessos)", action: "Gera checklist RBAC/MFA + recomenda revisão de privilégios." },
            { id: "Q07", question: "2.2. Existe política de passwords e rotação para contas privilegiadas?", risk_if_no: "Alto", controls: "PR.AC-6", nis2: "Art. 21", action: "Gera template de política de passwords + recomenda MFA/SSO." },
            { id: "Q08", question: "2.3. Backups são realizados e testados regularmente (restore)?", risk_if_no: "Crítico", controls: "PR.IP-4", nis2: "Art. 21 (Continuidade / backups)", action: "Gera plano de backup + agenda testes de restore com evidência." },
            { id: "Q09", question: "2.4. Existe gestão de vulnerabilidades (inventário + patching)?", risk_if_no: "Alto", controls: "PR.IP-12", nis2: "Art. 21", action: "Inicia módulo CVEs + recomenda ciclo de patching." },
            { id: "Q10", question: "2.5. Existem medidas de hardening para servidores e endpoints?", risk_if_no: "Médio", controls: "PR.IP-1", nis2: "Art. 21", action: "Gera baseline hardening + checklist CIS/boas práticas." },
            { id: "Q11", question: "2.6. Existe formação e sensibilização periódica (phishing / boas práticas)?", risk_if_no: "Médio", controls: "PR.AT-1", nis2: "Art. 21", action: "Gera plano anual de awareness + registos de participação." },
        ],
    },
    {
        section: 3,
        title: "Secção 3 — Deteção",
        items: [
            { id: "Q12", question: "3.1. Existem logs centralizados e retenção definida para sistemas críticos?", risk_if_no: "Alto", controls: "DE.AE-3", nis2: "Art. 21 (Monitorização)", action: "Define retenção e fontes + recomenda SIEM/centralização." },
            { id: "Q13", question: "3.2. Existe monitorização e alertas (EDR/Wazuh) para incidentes relevantes?", risk_if_no: "Alto", controls: "DE.CM-1", nis2: "Art. 21", action: "Configura regras Wazuh + integrações e owners." },
            { id: "Q14", question: "3.3. Existe processo de triagem e classificação de alertas (severidade/impacto)?", risk_if_no: "Médio", controls: "DE.DP-1", nis2: "Art. 21", action: "Gera workflow SOC + SLAs e critérios de severidade." },
            { id: "Q15", question: "3.4. Existe baseline de comportamento e deteção de anomalias?", risk_if_no: "Baixo", controls: "DE.CM-7", nis2: "Art. 21", action: "Recomenda métricas + alertas por desvios." },
        ],
    },
    {
        section: 4,
        title: "Secção 4 — Resposta",
        items: [
            { id: "Q16", question: "4.1. Existe plano de resposta a incidentes (papéis, contactos, passos)?", risk_if_no: "Crítico", controls: "RS.RP-1", nis2: "Art. 21 (Resposta a incidentes)", action: "Gera template IRP + lista de contactos e runbooks." },
            { id: "Q17", question: "4.2. Existe procedimento de reporte interno e externo (ex.: autoridades)?", risk_if_no: "Alto", controls: "RS.CO-2", nis2: "NIS2 (reporting)", action: "Gera fluxo de reporte + prazos e responsáveis." },
            { id: "Q18", question: "4.3. Incidentes geram ações e evidências (lições aprendidas)?", risk_if_no: "Médio", controls: "RS.IM-1", nis2: "Art. 21", action: "Cria processo pós-incidente + registo de evidências." },
            { id: "Q19", question: "4.4. Existe testes/exercícios periódicos do plano de resposta?", risk_if_no: "Médio", controls: "RS.IM-2", nis2: "Art. 21", action: "Agenda tabletop/exercícios + relatórios." },
        ],
    },
    {
        section: 5,
        title: "Secção 5 — Continuidade",
        items: [
            { id: "Q20", question: "5.1. Existe BCP/DRP (continuidade e recuperação) formalizado?", risk_if_no: "Crítico", controls: "PR.PT-1", nis2: "Art. 21 (Continuidade)", action: "Gera templates BCP/DRP + owners + revisão anual." },
            { id: "Q21", question: "5.2. Existem RTO/RPO definidos para serviços críticos?", risk_if_no: "Alto", controls: "PR.PT-4", nis2: "Art. 21", action: "Define RTO/RPO + mapa de dependências." },
            { id: "Q22", question: "5.3. Existe testes periódicos de recuperação e evidências?", risk_if_no: "Alto", controls: "PR.IP-4", nis2: "Art. 21", action: "Agenda testes de recovery + anexos e evidências." },
        ],
    },
];

// flatten
const FLAT = QUESTIONNAIRE.flatMap(sec => sec.items.map(q => ({
    ...q,
    section: sec.section,
    sectionTitle: sec.title
})));

const STORAGE_KEY = "tb_questionnaire_answers_v1";

function loadState() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || "{}"); }
    catch { return {}; }
}
function saveState(state) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

let idx = 0;
let state = loadState();

function counts() {
    const vals = Object.values(state);
    const by = (v) => vals.filter(x => x?.answer === v).length;
    $("#kYes").textContent = by("YES");
    $("#kPartial").textContent = by("PARTIAL");
    $("#kNo").textContent = by("NO");
    $("#kNA").textContent = by("NA");
}

function setProgress() {
    const total = FLAT.length;
    $("#qProgressText").textContent = `${Math.min(idx + 1, total)}/${total}`;
    const p = total ? ((idx) / (total - 1)) * 100 : 0;
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

function render() {
    const q = FLAT[idx];
    if (!q) return;

    fadeSwap(() => {
        $("#qSectionChip").textContent = q.sectionTitle;

        $("#qMetaTop").innerHTML = `
      <span class="chip">ID: <b>${q.id}</b></span>
      <span class="chip">QNRCS: <b>${q.controls || "—"}</b></span>
      <span class="chip">NIS2: <b>${q.nis2 || "—"}</b></span>
      <span class="chip">Risco se “Não”: <b>${q.risk_if_no || "—"}</b></span>
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
            question: q.question,
            controls: q.controls,
            nis2: q.nis2,
            risk_if_no: q.risk_if_no,
            action: q.action,
            section: q.sectionTitle
        }
    };

    saveState(state);
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

    // só itens relevantes pro plano: NÃO / PARCIAL (e opcionalmente YES como “coberto”)
    const gaps = answers.filter(a => a.answer === "NO" || a.answer === "PARTIAL");
    const nas = answers.filter(a => a.answer === "NA");

    const bySection = {};
    gaps.forEach(a => {
        const s = a.meta.section || "Outros";
        bySection[s] = bySection[s] || [];
        bySection[s].push(a);
    });

    const now = new Date().toISOString().slice(0, 10);

    let html = `
    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
      <div>
        <div style="font-size:18px; font-weight:900;">Plano de Segurança — Draft</div>
        <div class="muted">Gerado em ${now} (mock)</div>
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
        Este relatório é um <b>mock</b> (RF13). No futuro, você pode renderizar isto via backend e exportar com DomPDF.
      </p>
    </div>

    <div style="height:12px"></div>
  `;

    Object.keys(bySection).forEach(sec => {
        html += `<div class="panel">
      <div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">
        <div style="font-size:16px; font-weight:900;">${sec}</div>
        <span class="chip">Itens: <b>${bySection[sec].length}</b></span>
      </div>
      <div style="height:10px"></div>
    `;

        bySection[sec].forEach(a => {
            const tag = a.answer === "NO" ? `<span class="tag bad"><span class="s"></span> GAP</span>` : `<span class="tag warn"><span class="s"></span> PARTIAL</span>`;
            html += `
        <div class="panel" style="margin-bottom:10px">
          <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
            <div style="font-weight:800;">${a.meta.question}</div>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
              ${tag}
              <span class="chip">QNRCS: <b>${a.meta.controls || "—"}</b></span>
              <span class="chip">NIS2: <b>${a.meta.nis2 || "—"}</b></span>
              <span class="chip">Risco: <b>${a.meta.risk_if_no || "—"}</b></span>
            </div>
          </div>

          <div style="height:8px"></div>
          <div><b>Ação sugerida</b></div>
          <div class="muted" style="margin-top:6px">${a.meta.action || "—"}</div>

          ${a.notes ? `
            <div style="height:10px"></div>
            <div><b>Observações</b></div>
            <div class="muted" style="margin-top:6px; white-space:pre-wrap;">${escapeHtml(a.notes)}</div>
          ` : ""}
        </div>
      `;
        });

        html += `</div><div style="height:10px"></div>`;
    });

    if (nas.length) {
        html += `<div class="panel">
      <div style="font-weight:900;">Itens marcados como “Não aplicável”</div>
      <div class="muted" style="margin-top:6px">${nas.map(a => `• ${a.meta.question}`).join("<br>")}</div>
    </div>`;
    }

    return html;
}

function escapeHtml(str) {
    return String(str)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function showReport() {
    $("#reportBody").innerHTML = buildReportHtml();
    openModal("reportModal");
}

function wire() {
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

    $("#btnCloseReport").addEventListener("click", () => closeModal("reportModal"));

    $("#btnPrintReport").addEventListener("click", () => {
        // opção simples de “PDF”: print do browser (salvar como PDF)
        const w = window.open("", "_blank");
        if (!w) return;

        const html = `
      <html>
      <head>
        <meta charset="utf-8" />
        <title>Plano de Segurança — Techbase</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 24px; }
          h1,h2,h3 { margin: 0 0 8px; }
          .box { border: 1px solid #ddd; border-radius: 10px; padding: 12px 14px; margin: 10px 0; }
          .muted { color:#444; }
          .small { font-size: 12px; color:#555; }
          .row { display:flex; gap:10px; flex-wrap:wrap; }
          .pill { display:inline-block; padding:4px 10px; border:1px solid #ddd; border-radius:999px; font-size:12px; }
        </style>
      </head>
      <body>
        <h2>Plano de Segurança — Draft</h2>
        <div class="small">Gerado via mock (RF13) • ${new Date().toISOString().slice(0, 10)}</div>
        <div style="height:10px"></div>
        ${$("#reportBody").innerText
                ? $("#reportBody").innerHTML
                    .replaceAll('class="panel"', 'class="box"')
                    .replaceAll('class="muted"', 'class="muted"')
                    .replaceAll('class="chip"', 'class="pill"')
                    .replaceAll('class="tag bad"', 'class="pill"')
                    .replaceAll('class="tag warn"', 'class="pill"')
                : "<div class='box'>Sem dados.</div>"
            }
        <script>window.onload = () => window.print();</script>
      </body>
      </html>
    `;
        w.document.open();
        w.document.write(html);
        w.document.close();
    });
}

(function init() {
    // se tiver ?q=Q05 por exemplo, abre direto nessa pergunta
    const u = new URL(window.location.href);
    const qid = u.searchParams.get("q");
    if (qid) {
        const pos = FLAT.findIndex(x => x.id === qid);
        if (pos >= 0) idx = pos;
    }

    wire();
    render();
})();
