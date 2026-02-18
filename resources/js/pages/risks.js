// resources/js/pages/risks.js

const MOCK_ALERTS = [
    {
        id: "WZ-1001",
        date: "2026-02-18 10:12",
        severity: "critical",
        asset: "SRV-DB-01",
        category: "Malware",
        summary: "Comportamento suspeito + execução não assinada",
        raw: { rule: "100200", agent: "srv-db-01", ip: "10.0.2.15" }
    },
    {
        id: "WZ-1002",
        date: "2026-02-18 09:41",
        severity: "high",
        asset: "FW-EDGE-01",
        category: "Brute force",
        summary: "Múltiplas tentativas de login (SSH)",
        raw: { rule: "5712", srcip: "185.111.x.x" }
    }
];

const MOCK_ASSETS = {
    "SRV-DB-01": { criticality: "Crítico", owner: "IT Ops", notes: "Servidor de base de dados (PII)" },
    "FW-EDGE-01": { criticality: "Alto", owner: "Network", notes: "Firewall perímetro" }
};

function severityChipClass(sev) {
    if (sev === "critical") return "chip bad";
    if (sev === "high") return "chip warn";
    return "chip";
}

function mockAIRecommendation(alert) {
    const asset = MOCK_ASSETS[alert.asset] || { criticality: "—", owner: "—", notes: "" };

    // mock simples: decide risco e ações por categoria/severidade
    let risk = "Risco operacional";
    let actions = [];
    let confidence = 0.72;

    if (alert.category.toLowerCase().includes("malware")) {
        risk = "Comprometimento do ativo / execução maliciosa";
        actions = [
            "Isolar o host e recolher indicadores (hash/processos).",
            "Executar varredura completa + confirmar integridade.",
            "Rever controlos de execução (allowlist) e EDR.",
            "Validar backups e preparar restauração se necessário."
        ];
        confidence = 0.82;
    } else if (alert.category.toLowerCase().includes("brute")) {
        risk = "Acesso não autorizado por força bruta";
        actions = [
            "Bloquear IPs e ajustar rate-limit / fail2ban.",
            "Rever MFA e endurecer política de passwords.",
            "Auditar contas com tentativas e logs de autenticação."
        ];
        confidence = 0.77;
    }

    // “classe risco” mock
    const riskClass = alert.severity === "critical" ? "Alto" : (alert.severity === "high" ? "Médio" : "Baixo");

    return { risk, actions: actions.join("\n"), confidence, riskClass, asset };
}

function getQuery() {
    const url = new URL(window.location.href);
    return {
        alertId: url.searchParams.get("alert_id"),
        from: url.searchParams.get("from"),
    };
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

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


function loadTreatments() {
    try { return JSON.parse(localStorage.getItem("tb_mock_treatments") || "[]"); }
    catch { return []; }
}
function saveTreatment(plan) {
    const arr = loadTreatments();
    arr.unshift(plan);
    localStorage.setItem("tb_mock_treatments", JSON.stringify(arr));
}

let selectedAlert = null;
let selectedAI = null;

function renderAlerts() {
    const tbody = document.getElementById("wazuhRiskTbody");
    if (!tbody) return;

    tbody.innerHTML = MOCK_ALERTS.map(a => {
        const ai = mockAIRecommendation(a);
        return `
      <tr class="row-click" data-alert-id="${a.id}">
        <td>${a.date}</td>
        <td><span class="${severityChipClass(a.severity)}">${a.severity}</span></td>
        <td>${a.asset}</td>
        <td>${ai.risk}</td>
        <td class="muted">${ai.actions.split("\n")[0]}</td>
        <td><button class="btn" type="button" data-open-plan="${a.id}">Plano</button></td>
      </tr>
    `;
    }).join("");

    tbody.querySelectorAll("tr[data-alert-id]").forEach(tr => {
        tr.addEventListener("click", (e) => {
            // se clicou no botão, não duplicar
            if (e.target && e.target.matches("[data-open-plan]")) return;

            const id = tr.getAttribute("data-alert-id");
            selectAlert(id);
        });
    });

    tbody.querySelectorAll("[data-open-plan]").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            selectAlert(btn.getAttribute("data-open-plan"), true);
        });
    });
}

function selectAlert(alertId, openPlan = false) {
    selectedAlert = MOCK_ALERTS.find(x => x.id === alertId);
    if (!selectedAlert) return;

    selectedAI = mockAIRecommendation(selectedAlert);

    // habilita CNCS só se critical
    const btnCNCS = document.getElementById("btnCNCS24h");
    if (btnCNCS) btnCNCS.disabled = !(selectedAlert.severity === "critical");

    const ctx = document.getElementById("alertContextBox");
    if (ctx) {
        ctx.innerHTML = `
      <div><b>${selectedAlert.id}</b> — ${selectedAlert.summary}</div>
      <div class="muted" style="margin-top:6px">
        Ativo: <b>${selectedAlert.asset}</b> • Criticidade: <b>${selectedAI.asset.criticality}</b> • Owner: <b>${selectedAI.asset.owner}</b>
      </div>
      <div class="muted" style="margin-top:6px">
        Contexto: ${selectedAI.asset.notes || "—"}
      </div>
    `;
    }

    const confChip = document.getElementById("aiConfidenceChip");
    if (confChip) confChip.textContent = `Confiança IA: ${selectedAI.confidence.toFixed(2)}`;

    const clsChip = document.getElementById("suggestedStatusChip");
    if (clsChip) clsChip.textContent = `Classe risco: ${selectedAI.riskClass}`;

    const btn = document.getElementById("btnCreateTreatmentFromAlert");
    if (btn) btn.disabled = false;

    if (openPlan) {
        openTreatmentModalPrefilled();
    }
}

function openTreatmentModalPrefilled() {
    if (!selectedAlert || !selectedAI) return;

    document.getElementById("ta_alert").value = `${selectedAlert.id} (${selectedAlert.severity})`;
    document.getElementById("ta_asset").value = selectedAlert.asset;
    document.getElementById("ta_risk").value = selectedAI.risk;
    document.getElementById("ta_actions").value = selectedAI.actions;

    openModal("treatmentAlertModal");
}

function wireUI() {
    document.getElementById("btnCNCS24h")?.addEventListener("click", openCNCSModal);
    document.getElementById("btnCloseCNCS")?.addEventListener("click", () => closeModal("cncs24hModal"));
    document.getElementById("btnPrintCNCS")?.addEventListener("click", printCNCS);

    document.getElementById("btnCreateTreatmentFromAlert")?.addEventListener("click", () => {
        openTreatmentModalPrefilled();
    });

    document.getElementById("treatmentAlertClose")?.addEventListener("click", () => closeModal("treatmentAlertModal"));

    document.getElementById("ta_save")?.addEventListener("click", () => {
        if (!selectedAlert || !selectedAI) return;

        const plan = {
            id: `TP-${Date.now()}`,
            source: "wazuh",
            alertId: selectedAlert.id,
            asset: selectedAlert.asset,

            // imutáveis
            risk: document.getElementById("ta_risk").value.trim(),
            aiActions: document.getElementById("ta_actions").value.trim(),

            // input do user
            planDescription: document.getElementById("ta_plan_desc").value.trim(),

            strategy: document.getElementById("ta_strategy").value,
            due: document.getElementById("ta_due").value.trim(),
            owner: document.getElementById("ta_owner").value.trim(),
            priority: document.getElementById("ta_priority").value,
            status: "Pendente",
            createdAt: new Date().toISOString(),
        };


        saveTreatment(plan);

        closeModal("treatmentAlertModal");

        const go = document.getElementById("btnGoTreatment");
        if (go) go.style.display = "inline-flex";
        alert("Plano criado (mock) e guardado em Tratamento.");
    });

    // auto-selecionar se veio do dashboard
    const q = getQuery();
    if (q.from === "wazuh" && q.alertId) {
        // scroll até a secção
        document.getElementById("wazuh")?.scrollIntoView({ behavior: "smooth", block: "start" });
        selectAlert(q.alertId);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    renderAlerts();
    wireUI();
});

document.getElementById("treatmentAlertModal")?.addEventListener("click", (e) => {
    if (e.target?.id === "treatmentAlertModal") {
        closeModal("treatmentAlertModal");
    }
});

document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeModal("treatmentAlertModal");
});


function inferIncidentNature(alert) {
    const cat = (alert.category || "").toLowerCase();
    if (cat.includes("malware")) return "Malware";
    if (cat.includes("brute")) return "Acesso Não Autorizado";
    return "Outro";
}


function inferAffectedSystems(alert, assetNotes) {
    const out = [];
    if ((assetNotes || "").toLowerCase().includes("base de dados")) out.push("Bases de dados");
    if ((alert.category || "").toLowerCase().includes("brute")) out.push("Redes de comunicação");
    if (!out.length) out.push("Sistemas de informação essenciais");
    return out;
}

function inferImpactLevel(alert, assetCrit) {
    if (alert.severity === "critical") return "Muito Alto";
    if (alert.severity === "high") return "Alto";
    if (assetCrit && assetCrit.toLowerCase().includes("crít")) return "Alto";
    return "Médio";
}

function buildCNCS24hDraftHtml(alert, ai) {
    const org = {
        name: "Clínica Exemplo",
        type: "Importante (Anexo II)",
        sector: "Saúde",
        nif: "—",
        phone: "",
        contact: "",
        email: "",
        address: "",
    };

    const detection = alert.date;
    const nature = inferIncidentNature(alert);
    const impact = inferImpactLevel(alert, ai.asset.criticality);
    const systems = inferAffectedSystems(alert, ai.asset.notes);
    const pii = (ai.asset.notes || "").toLowerCase().includes("pii") ? "Sim" : "Ainda desconhecido";

    const immediate = ai.actions.split("\n").slice(0, 3);

    const ioc = [];
    if (alert.raw?.ip) ioc.push(`IP interno/agent: ${alert.raw.ip}`);
    if (alert.raw?.srcip) ioc.push(`IP origem: ${alert.raw.srcip}`);
    if (alert.raw?.rule) ioc.push(`Regra: ${alert.raw.rule}`);

    const summary = `${alert.summary} (Ativo: ${alert.asset})`.slice(0, 500);

    return `
    <div class="panel">
      <div style="font-weight:900; margin-bottom:8px">1) Informações da Entidade</div>

      <div class="two">
        <div>
          <span class="muted">Entidade</span>
          <div><b>${org.name}</b></div>
        </div>
        <div>
          <span class="muted">Tipo</span>
          <div><b>${org.type}</b></div>
        </div>
      </div>

      <div class="two" style="margin-top:10px">
        <div><span class="muted">Setor</span><div><b>${org.sector}</b></div></div>
        <div><span class="muted">NIF/NIPC</span><div><b>${org.nif}</b></div></div>
      </div>

      <div class="two" style="margin-top:10px">
        <div>
          <label class="muted">Telefone</label>
          <input id="cncs_phone" class="input" placeholder="ex.: +351 ..." value="${org.phone}">
        </div>
        <div>
          <label class="muted">Email</label>
          <input id="cncs_email" class="input" placeholder="ex.: soc@empresa.pt" value="${org.email}">
        </div>
      </div>

      <div class="two" style="margin-top:10px">
        <div>
          <label class="muted">Pessoa contacto</label>
          <input id="cncs_contact" class="input" placeholder="Nome / cargo" value="${org.contact}">
        </div>
        <div>
          <label class="muted">Endereço</label>
          <input id="cncs_address" class="input" placeholder="Morada" value="${org.address}">
        </div>
      </div>
    </div>

    <div style="height:10px"></div>

    <div class="panel">
      <div style="font-weight:900; margin-bottom:8px">2) Informações do Incidente</div>

      <div class="two">
        <div><span class="muted">Deteção</span><div><b>${detection}</b></div></div>
        <div><span class="muted">Natureza</span><div><b>${nature}</b></div></div>
      </div>

      <div style="margin-top:10px">
        <span class="muted">Descrição sumária (≤ 500)</span>
        <div><b>${summary}</b></div>
      </div>

      <div class="two" style="margin-top:10px">
        <div>
          <label class="muted">Estado atual</label>
          <select id="cncs_status" class="select">
            <option>Em Investigação</option>
            <option>Ativo</option>
            <option>Contido</option>
            <option>Resolvido</option>
          </select>
        </div>
        <div>
          <label class="muted">Incidente ativo?</label>
          <select id="cncs_active" class="select">
            <option>Sim</option>
            <option>Não</option>
            <option>Desconhecido</option>
          </select>
        </div>
      </div>
    </div>

    <div style="height:10px"></div>

    <div class="panel">
      <div style="font-weight:900; margin-bottom:8px">3) Sistemas e Serviços Afetados</div>

      <div class="muted">Sistemas afetados (pré-preenchido):</div>
      <div style="margin-top:6px">${systems.map(s => `• <b>${s}</b>`).join("<br>")}</div>

      <div class="two" style="margin-top:10px">
        <div>
          <label class="muted">Utilizadores afetados (estimativa)</label>
          <input id="cncs_users" class="input" placeholder="ex.: 120" value="">
        </div>
        <div>
          <label class="muted">Âmbito geográfico</label>
          <select id="cncs_geo" class="select">
            <option>Local</option>
            <option>Nacional</option>
            <option>Transfronteiriço</option>
          </select>
        </div>
      </div>

      <div style="margin-top:10px">
        <label class="muted">Países (se transfronteiriço)</label>
        <input id="cncs_countries" class="input" placeholder="ex.: ES, FR" value="">
      </div>
    </div>

    <div style="height:10px"></div>

    <div class="panel">
      <div style="font-weight:900; margin-bottom:8px">4) Avaliação Preliminar de Impacto</div>
      <div class="two">
        <div><span class="muted">Impacto inicial</span><div><b>${impact}</b></div></div>
        <div><span class="muted">Dados pessoais comprometidos?</span><div><b>${pii}</b></div></div>
      </div>

      <div class="two" style="margin-top:10px">
        <div>
          <label class="muted">Registos comprometidos (estimativa)</label>
          <input id="cncs_records" class="input" placeholder="ex.: 5000" value="">
        </div>
        <div>
          <label class="muted">Duração interrupção (estimativa)</label>
          <input id="cncs_duration" class="input" placeholder="ex.: 2h" value="">
        </div>
      </div>
    </div>

    <div style="height:10px"></div>

    <div class="panel">
      <div style="font-weight:900; margin-bottom:8px">5) Medidas Imediatas Tomadas</div>
      <div class="muted">Ações (derivadas da recomendação IA):</div>
      <div style="margin-top:6px">${immediate.map(x => `• ${x}`).join("<br>")}</div>

      <div style="margin-top:10px">
        <label class="muted">Medidas adicionais (editável)</label>
        <textarea id="cncs_extra_measures" class="textarea" rows="3" placeholder="O que já foi feito/será feito nas próximas horas"></textarea>
      </div>
    </div>

    <div style="height:10px"></div>

    <div class="panel">
      <div style="font-weight:900; margin-bottom:8px">7) Informações Adicionais</div>
      <div class="muted">IoCs (pré-preenchido):</div>
      <textarea id="cncs_iocs" class="textarea" rows="3">${(ioc.join("\n") || "—")}</textarea>

      <div style="margin-top:10px">
        <label class="muted">Observações (editável)</label>
        <textarea id="cncs_notes" class="textarea" rows="3">Origem: Wazuh/Acronis (mock). Evidências a anexar via módulo Documentos.</textarea>
      </div>
    </div>

    <div style="height:10px"></div>

    <div class="panel">
      <div style="font-weight:900; margin-bottom:8px">Declaração</div>

      <div class="two">
        <div>
          <label class="muted">Nome</label>
          <input id="cncs_decl_name" class="input" placeholder="Nome do declarante">
        </div>
        <div>
          <label class="muted">Cargo</label>
          <input id="cncs_decl_role" class="input" placeholder="Cargo">
        </div>
      </div>

      <div style="margin-top:10px">
        <span class="muted">Data</span>
        <div><b>${new Date().toISOString().slice(0, 10)}</b></div>
      </div>
    </div>
  `;
}


function openCNCSModal() {
    if (!selectedAlert || !selectedAI) return;
    const body = document.getElementById("cncs24hBody");
    if (body) body.innerHTML = buildCNCS24hDraftHtml(selectedAlert, selectedAI);
    openModal("cncs24hModal");
}

function val(id) {
    const el = document.getElementById(id);
    if (!el) return "";
    return (el.value ?? "").trim();
}

function printCNCS() {
    if (!selectedAlert || !selectedAI) return;

    // pega valores editáveis
    const payload = {
        phone: val("cncs_phone"),
        email: val("cncs_email"),
        contact: val("cncs_contact"),
        address: val("cncs_address"),

        status: val("cncs_status"),
        active: val("cncs_active"),

        users: val("cncs_users"),
        geo: val("cncs_geo"),
        countries: val("cncs_countries"),

        records: val("cncs_records"),
        duration: val("cncs_duration"),

        extra: val("cncs_extra_measures"),
        iocs: val("cncs_iocs"),
        notes: val("cncs_notes"),

        declName: val("cncs_decl_name"),
        declRole: val("cncs_decl_role"),
    };

    const html = buildCNCS24hPdfHtml(selectedAlert, selectedAI, payload);

    const w = window.open("", "_blank");
    if (!w) return;
    w.document.open();
    w.document.write(html);
    w.document.close();
}

function esc(s) {
    return String(s || "")
        .replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;").replaceAll("'", "&#039;");
}

function buildCNCS24hPdfHtml(alert, ai, p) {
    const detection = alert.date;
    const nature = inferIncidentNature(alert);
    const impact = inferImpactLevel(alert, ai.asset.criticality);
    const systems = inferAffectedSystems(alert, ai.asset.notes);
    const pii = (ai.asset.notes || "").toLowerCase().includes("pii") ? "Sim" : "Ainda desconhecido";
    const immediate = ai.actions.split("\n").slice(0, 3);
    const summary = `${alert.summary} (Ativo: ${alert.asset})`.slice(0, 500);

    return `
  <html><head><meta charset="utf-8"><title>Notificação CNCS 24h (Draft)</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 22px; }
    h2 { margin: 0 0 6px; }
    .muted { color:#444; }
    .box { border:1px solid #ddd; border-radius:10px; padding:12px 14px; margin:10px 0; }
    .two { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .lbl { font-size: 12px; color:#555; }
    .val { font-weight: 700; }
    .pre { white-space: pre-wrap; }
  </style>
  </head><body>
    <h2>Notificação Inicial (24h) — Draft</h2>
    <div class="muted">Gerado automaticamente a partir do incidente (mock)</div>

    <div class="box">
      <div class="val">1) Informações da Entidade</div>
      <div class="two" style="margin-top:8px">
        <div><div class="lbl">Entidade</div><div class="val">Clínica Exemplo</div></div>
        <div><div class="lbl">Tipo</div><div class="val">Importante (Anexo II)</div></div>
      </div>
      <div class="two" style="margin-top:8px">
        <div><div class="lbl">Setor</div><div class="val">Saúde</div></div>
        <div><div class="lbl">NIF/NIPC</div><div class="val">—</div></div>
      </div>
      <div class="two" style="margin-top:8px">
        <div><div class="lbl">Telefone</div><div class="val">${esc(p.phone) || "—"}</div></div>
        <div><div class="lbl">Email</div><div class="val">${esc(p.email) || "—"}</div></div>
      </div>
      <div class="two" style="margin-top:8px">
        <div><div class="lbl">Pessoa contacto</div><div class="val">${esc(p.contact) || "—"}</div></div>
        <div><div class="lbl">Endereço</div><div class="val">${esc(p.address) || "—"}</div></div>
      </div>
    </div>

    <div class="box">
      <div class="val">2) Informações do Incidente</div>
      <div class="two" style="margin-top:8px">
        <div><div class="lbl">Deteção</div><div class="val">${esc(detection)}</div></div>
        <div><div class="lbl">Natureza</div><div class="val">${esc(nature)}</div></div>
      </div>
      <div style="margin-top:8px"><div class="lbl">Descrição sumária (≤ 500)</div><div class="val">${esc(summary)}</div></div>
      <div class="two" style="margin-top:8px">
        <div><div class="lbl">Estado atual</div><div class="val">${esc(p.status) || "Em Investigação"}</div></div>
        <div><div class="lbl">Incidente ativo?</div><div class="val">${esc(p.active) || "Sim"}</div></div>
      </div>
    </div>

    <div class="box">
      <div class="val">3) Sistemas e Serviços Afetados</div>
      <div style="margin-top:8px" class="pre">${systems.map(s => `• ${s}`).join("\n")}</div>
      <div class="two" style="margin-top:8px">
        <div><div class="lbl">Utilizadores afetados</div><div class="val">${esc(p.users) || "—"}</div></div>
        <div><div class="lbl">Âmbito geográfico</div><div class="val">${esc(p.geo) || "Local"}</div></div>
      </div>
      <div style="margin-top:8px"><div class="lbl">Países (se aplicável)</div><div class="val">${esc(p.countries) || "—"}</div></div>
    </div>

    <div class="box">
      <div class="val">4) Avaliação Preliminar de Impacto</div>
      <div class="two" style="margin-top:8px">
        <div><div class="lbl">Impacto inicial</div><div class="val">${esc(impact)}</div></div>
        <div><div class="lbl">Dados pessoais comprometidos?</div><div class="val">${esc(pii)}</div></div>
      </div>
      <div class="two" style="margin-top:8px">
        <div><div class="lbl">Registos comprometidos</div><div class="val">${esc(p.records) || "—"}</div></div>
        <div><div class="lbl">Duração interrupção</div><div class="val">${esc(p.duration) || "—"}</div></div>
      </div>
    </div>

    <div class="box">
      <div class="val">5) Medidas Imediatas Tomadas</div>
      <div style="margin-top:8px" class="pre">${immediate.map(x => `• ${x}`).join("\n")}</div>
      <div style="margin-top:8px"><div class="lbl">Medidas adicionais</div><div class="val pre">${esc(p.extra) || "—"}</div></div>
    </div>

    <div class="box">
      <div class="val">7) Informações Adicionais</div>
      <div style="margin-top:8px"><div class="lbl">IoCs</div><div class="val pre">${esc(p.iocs) || "—"}</div></div>
      <div style="margin-top:8px"><div class="lbl">Observações</div><div class="val pre">${esc(p.notes) || "—"}</div></div>
    </div>

    <div class="box">
      <div class="val">Declaração</div>
      <div class="two" style="margin-top:8px">
        <div><div class="lbl">Nome</div><div class="val">${esc(p.declName) || "—"}</div></div>
        <div><div class="lbl">Cargo</div><div class="val">${esc(p.declRole) || "—"}</div></div>
      </div>
      <div style="margin-top:8px"><div class="lbl">Data</div><div class="val">${new Date().toISOString().slice(0, 10)}</div></div>
    </div>

    <script>window.onload = () => window.print();</script>
  </body></html>`;
}


