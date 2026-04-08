// public/js/pages/assets.js
(() => {
    // ========= helpers =========
    const $ = (s) => document.querySelector(s);
    const $$ = (s) => Array.from(document.querySelectorAll(s));
    // ========= Definição de tipos de ativos que possuem IP =========
    const TYPES_WITH_IP = new Set(["Servidor", "Firewall", "Router", "Switch", "Access Point", "VPN Gateway"]);
    function shouldShowIp(type) {
        return TYPES_WITH_IP.has(type);
    }
    function toggleIpFieldByType(type) {
        const wrap = $("#ipFieldWrap");
        if (!wrap) return;
        const show = shouldShowIp(type);
        wrap.style.display = show ? "" : "none";
        if (!show && $("#fIp")) $("#fIp").value = "";
    }




    // ========= RBAC (mock) =========
    const USER_ROLE = (window.APP_USER_ROLE || 'Viewer').trim();
    const CAN_OVERRIDE_STATUS = (USER_ROLE === 'Admin' || USER_ROLE === 'GRC Manager');

    function canOverrideStatus() { return CAN_OVERRIDE_STATUS; }

    function iaStatusFromConfidence(conf) {
        const c = Number(conf);
        if (c >= 0.80) return 'COVERED';
        if (c >= 0.55) return 'PARTIAL';
        return 'GAP';
    }

    function applyStatusGuard(selectEl, hintEl, confidence = null, suggestChipEl = null) {
        if (!selectEl) return;

        const suggested = (confidence == null) ? null : iaStatusFromConfidence(Number(confidence));

        if (suggestChipEl) {
            suggestChipEl.textContent = suggested || '—';
            suggestChipEl.className = 'chip ' + (suggested === 'COVERED' ? 'ok' : (suggested === 'PARTIAL' ? 'warn' : 'bad'));
        }

        if (canOverrideStatus()) {
            selectEl.disabled = false;
            if (hintEl) hintEl.textContent = 'Podes declarar o status; a IA vai sugerir um status e sinalizar divergências.';
            return;
        }

        selectEl.disabled = true;
        if (hintEl) hintEl.textContent = 'Somente GRC Manager/Admin podem alterar (RF19).';
    }


    function openModal(id) {
        const m = $(id);
        if (!m) return;

        m.classList.add("open"); // O teu CSS precisa da classe "open"
        m.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden"; // Bloqueia o fundo
    }


    function closeModal(id) {
        const m = $(id);
        if (!m) return;

        // Tira o foco para evitar o erro de acessibilidade do Chrome
        if (document.activeElement && m.contains(document.activeElement)) {
            document.activeElement.blur();
        }

        m.classList.remove("open");
        m.setAttribute("aria-hidden", "true");
        document.body.style.overflow = ""; // Liberta o fundo
    }

    function normalize(s) {
        return (s || "").toLowerCase().trim();
    }

    function updateDiffChip(declaredStatus, suggestedStatus, diffChipEl) {
        if (!diffChipEl) return;
        const suggested = suggestedStatus;
        const diff = (declaredStatus && suggested && declaredStatus !== suggested);
        diffChipEl.style.display = diff ? 'inline-flex' : 'none';
        if (diff) {
            diffChipEl.className = 'chip warn';
            diffChipEl.innerHTML = `Divergência: <b>${declaredStatus}</b> vs <b>${suggested}</b>`;
        }
    }


    // ========= control catalog (mock) =========
    const CONTROL_CATALOG = {
        "ID.GA-1": {
            title: "Inventário de ativos",
            desc: "Manter inventário atualizado com dono, criticidade, periodicidade e evidências."
        },
        "PR.IP-4": {
            title: "Backups (testados)",
            desc: "Executar e testar backups; manter relatórios e evidência de restauração."
        },
        "ID.AR-1": {
            title: "Análise de risco",
            desc: "Realizar avaliação de risco formal e manter histórico e revisões."
        },
        "PR.AC-1": {
            title: "Controlo de acesso",
            desc: "Gestão de acessos, mínimo privilégio, revisão periódica e evidências."
        }
    };

    // ========= IA mock (sugestão de status + confiança + justificativa) =========
    function mockAiSuggest(assetDesc, controlKey) {
        const base = (assetDesc || '').toLowerCase();
        const ctrl = CONTROL_CATALOG[controlKey] || { title: controlKey, desc: '' };
        const text = (ctrl.title + ' ' + ctrl.desc).toLowerCase();

        // palavras-chave simples por controlo (mock)
        const keywords = {
            "ID.GA-1": ["inventário", "inventario", "asset", "cmdb", "etiqueta", "dono", "responsável", "crit"],
            "PR.IP-4": ["backup", "cópia", "copia", "restore", "restauro", "retenção", "retencao", "teste", "rto", "rpo"],
            "ID.AR-1": ["risco", "matriz", "impacto", "probabilidade", "avaliação", "avaliacao", "ameaça", "ameaça"],
            "PR.AC-1": ["acesso", "login", "mfa", "2fa", "permiss", "rbac", "least privilege", "privilégio", "privilegio"]
        };

        const k = keywords[controlKey] || [];
        let hits = 0;
        for (const w of k) if (base.includes(w)) hits++;

        // sinalizadores de maturidade (mock)
        const hasEvidenceWords = /(procedimento|política|politica|registo|registro|evidência|evidencia|relatório|relatorio|teste|auditoria)/.test(base);
        const hasGapsWords = /(não existe|nao existe|informal|ad hoc|sem|falta|desatualizado|incompleto)/.test(base);

        // confiança base por hits
        let conf = 0.35 + Math.min(0.45, hits * 0.12);
        if (hasEvidenceWords) conf += 0.12;
        if (hasGapsWords) conf -= 0.10;
        conf = Math.max(0.15, Math.min(0.95, Number(conf.toFixed(2))));

        const suggestedStatus = iaStatusFromConfidence(conf);

        const why = [];
        if (hits) why.push(`detetou ${hits} palavra(s)-chave relacionadas com ${controlKey}`);
        if (hasEvidenceWords) why.push('há indícios de evidência/processo formal');
        if (hasGapsWords) why.push('existem sinais de lacunas ou informalidade');

        return {
            suggestedStatus,
            confidence: conf,
            justification: why.length ? why.join('; ') : 'heurística simples com base na descrição do ativo'
        };
    }


    function syncControlInfo(selectEl, infoEl, textEl) {
        if (!selectEl) return;
        const key = selectEl.value;
        const meta = CONTROL_CATALOG[key] || { title: '—', desc: '' };
        if (infoEl) infoEl.setAttribute('data-tip', `${key} — ${meta.title}: ${meta.desc}`);
        if (textEl) textEl.textContent = `${meta.title} — ${meta.desc}`;
    }

    // ========= assets (mock state) =========
    // statuses: GAP | PARTIAL | COVERED
    let assets = [
        {
            id: "A1",
            name: "SRV-DB-01",
            subtitle: "PostgreSQL • Produção",
            type: "Servidor",
            criticity: "Crítico",
            source: "acronis",
            owner: "TI • João",
            createdBy: "acronis-sync",
            ip: "192.168.10.5",
            mac_address: "00:1B:44:05:0F:23",
            hostname: "SRV-DB01",
            domain: "WORKGROUP",
            os_platform: "linux",
            os_Name: "Ubuntu 22.04 LTS",
            os_Version: "22.04",
            os_arch: "x86_64",
            os_build: "jammy",
            os_patch_level: "2026-02",
            agent_status: "online",
            agent_version: "Acronis Agent 15.2",
            agent_last_seen: "2026-03-12T10:22:00Z",
            backup_enabled: true,
            antimalware_enabled: true,
            patch_mgmt_enabled: false,
            acronis_synced_at: "2026-03-12T10:22:00Z",
            notes: "Servidor de base de dados de produção. Monitorizado por Wazuh.",
            prob: 4,
            impact: 4,
            controls: [
                { key: "ID.GA-1", status: "PARTIAL", confidence: 0.72, note: "Inventário existe, mas sem periodicidade formal.", evidences: ["Procedimento Inventário v1.0"] },
                { key: "PR.IP-4", status: "GAP", confidence: 0.55, note: "Backups sem evidência de testes.", evidences: ["Relatório Backups (Jan)"] }
            ],
            risks: [
                { id: "R-12", title: "Servidor exposto sem hardening", severity: "Alto", status: "Aberto", lastSeen: "2026-02-18" }
            ],
            treatments: [
                { id: "TP-7", riskId: "R-12", title: "Aplicar CIS baseline + validar portas", status: "Em progresso", owner: "SecOps • Ana", due: "2026-03-10" }
            ]
        },

        {
            id: "A2",
            name: "APP-GRC",
            subtitle: "Laravel • Web",
            type: "Aplicação",
            criticity: "Alto",
            source: "manual",
            owner: "SecOps • Ana",
            createdBy: "ana",
            ip: "",
            mac_address: "",
            hostname: "",
            domain: "",
            os_platform: "",
            os_Name: "",
            os_Version: "",
            os_arch: "",
            os_build: "",
            os_patch_level: "",
            agent_status: "",
            agent_version: "",
            agent_last_seen: "",
            backup_enabled: null,
            antimalware_enabled: null,
            patch_mgmt_enabled: null,
            acronis_synced_at: null,
            notes: "Aplicação de governança. Evidências e controlos centralizados.",
            prob: 3,
            impact: 2,
            controls: [
                { key: "PR.AC-1", status: "PARTIAL", confidence: 0.61, note: "Existe login, mas falta revisão periódica.", evidences: [] },
                { key: "ID.AR-1", status: "COVERED", confidence: 0.82, note: "Há processo de risco e registos.", evidences: ["Matriz de Risco v2026"] }
            ],
            risks: [],
            treatments: []
        }
    ];


    // Create/edit modal working state
    let editingAssetId = null;
    let createControlsWorking = []; // [{key,status,note}]

    // Details modal state
    let currentAssetId = null;

    // ========= risk helpers =========
    function classify(score) {
        if (score === 1) return { label: "Muito Baixo", cellClass: "vlow" };
        if (score <= 4) return { label: "Baixo", cellClass: "low" };
        if (score <= 10) return { label: "Médio", cellClass: "med" };
        if (score <= 16) return { label: "Alto", cellClass: "high" };
        return { label: "Muito Alto", cellClass: "vhigh" };
    }

    function buildMatrix(selectedProb, selectedImpact) {
        const cells = $("#riskMatrix");
        const probLabels = $("#probLabels");
        const impactLabels = $("#impactLabels");
        if (!cells || !probLabels || !impactLabels) return;

        cells.innerHTML = "";
        probLabels.innerHTML = "";
        impactLabels.innerHTML = "";

        const probText = ["(1) Muito Baixo", "(2) Baixo", "(3) Médio", "(4) Alto", "(5) Muito Alto"];
        const impactText = ["(5) Muito Alto", "(4) Alto", "(3) Médio", "(2) Baixo", "(1) Muito Baixo"];

        for (let p = 1; p <= 5; p++) {
            const d = document.createElement("div");
            d.className = "lbl";
            d.textContent = probText[p - 1];
            probLabels.appendChild(d);
        }

        for (let i = 0; i < 5; i++) {
            const d = document.createElement("div");
            d.className = "lbl";
            d.textContent = impactText[i];
            impactLabels.appendChild(d);
        }

        // impacto 5->1, prob 1->5
        for (let impact = 5; impact >= 1; impact--) {
            for (let prob = 1; prob <= 5; prob++) {
                const score = prob * impact;
                const cls = classify(score);

                const div = document.createElement("div");
                div.className = `mcell ${cls.cellClass}`;
                div.dataset.prob = String(prob);
                div.dataset.impact = String(impact);

                div.innerHTML = `
          <div>
            <small>${cls.label}</small>
            <div class="score">${score}</div>
          </div>
        `;

                cells.appendChild(div);
            }
        }

        // remove markers
        cells.querySelectorAll(".marker").forEach((m) => m.remove());

        // anchor marker inside the target cell
        const row = 5 - selectedImpact; // impact 5 => row 0
        const col = selectedProb - 1;
        const index = row * 5 + col;
        const target = cells.children[index];

        if (target) {
            const marker = document.createElement("div");
            marker.className = "marker";
            marker.textContent = `${selectedProb}×${selectedImpact}`;
            target.appendChild(marker);
        }
    }

    // Função para carregar os riscos do ativo aberto
    async function loadAssetRisks(assetId) {
        const list = document.getElementById("assetRiskTreatList");
        if (!list) return;

        list.innerHTML = '<div class="muted" style="font-size:12px; padding: 12px; text-align: center;">A carregar riscos...</div>';

        try {
            const res = await fetch("/api/risks", { headers: { Accept: "application/json" } });
            if (!res.ok) throw new Error("HTTP " + res.status);
            const allRisks = await res.json();
            const assetRisks = allRisks.filter(r => r.id_asset == assetId);

            if (assetRisks.length === 0) {
                list.innerHTML = '<div class="muted" style="font-size:12px; padding: 16px; background: rgba(255,255,255,0.02); border-radius: 8px; text-align: center; border: 1px dashed var(--border);">Nenhum risco registado para este ativo.</div>';
                return;
            }

            list.innerHTML = assetRisks.map(r => {
                const score = r.score ?? (r.probability * r.impact) ?? 1;

                // Cores baseadas no score
                let lvlColor = "#34d399"; // Baixo
                if (score >= 17) lvlColor = "#f87171"; // Muito Alto
                else if (score >= 10) lvlColor = "#fb923c"; // Alto
                else if (score >= 5) lvlColor = "#fbbf24"; // Médio

                return `
            <div style="display:flex; justify-content:space-between; align-items:center; padding: 12px 14px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px;">
                <div>
                    <div style="font-size:13px; font-weight:600; margin-bottom:4px; color: var(--text-primary);">${r.title || r.description || "Risco sem título"}</div>
                    <div style="font-size:11px; color: var(--muted); display: flex; gap: 6px; align-items: center;">
                        <span style="display:inline-block; padding: 2px 8px; border-radius: 99px; background: rgba(148,163,184,.12); color:#94a3b8;">${r.status}</span>
                        <span>${r.threat ? `• ⚡ ${r.threat}` : ''}</span>
                    </div>
                </div>
                <div style="text-align:right">
                    <span style="display:inline-block; padding: 4px 10px; border-radius: 99px; font-size:11px; font-weight:700; background: ${lvlColor}15; color: ${lvlColor}">
                        Score: ${score}
                    </span>
                </div>
            </div>`;
            }).join("");

        } catch (e) {
            list.innerHTML = `<div style="color:#f87171; font-size:12px; padding: 12px; text-align: center;">Erro ao carregar riscos: ${e.message}</div>`;
        }
    }

    // ========= computed summary =========
    function summarizeControls(asset) {
        const total = asset.controls.length || 0;
        const gap = asset.controls.filter((c) => c.declaredStatus === "GAP").length;
        const partial = asset.controls.filter((c) => c.declaredStatus === "PARTIAL").length;
        const covered = asset.controls.filter((c) => c.declaredStatus === "COVERED").length;
        return { total, gap, partial, covered };
    }

    function globalKpis(filteredAssets) {
        const totalAssets = filteredAssets.length;
        let gap = 0, partial = 0, covered = 0;

        filteredAssets.forEach((a) => {
            const s = summarizeControls(a);
            gap += s.gap;
            partial += s.partial;
            covered += s.covered;
        });

        $("#kpiAssets").textContent = String(totalAssets);
        $("#kpiGap").textContent = String(gap);
        $("#kpiPartial").textContent = String(partial);
        $("#kpiCovered").textContent = String(covered);
    }

    // ========= render table =========
    function criticityTag(c) {
        if (c === "Crítico") return `<span class="tag bad"><span class="s"></span> Crítico</span>`;
        if (c === "Alto") return `<span class="tag warn"><span class="s"></span> Alto</span>`;
        if (c === "Médio") return `<span class="tag"><span class="s"></span> Médio</span>`;
        return `<span class="tag ok"><span class="s"></span> Baixo</span>`;
    }

    function complianceTag(asset) {
        const s = summarizeControls(asset);
        // regra simples p/ mock:
        // - se tiver GAP => "GAP"
        // - senão se tiver PARTIAL => "PARTIAL"
        // - senão => "COVERED"
        let label = "COVERED";
        let cls = "ok";
        if (s.gap > 0) { label = "GAP"; cls = "bad"; }
        else if (s.partial > 0) { label = "PARTIAL"; cls = "warn"; }

        return `
      <span class="tag ${cls}"><span class="s"></span> ${label}</span>
      <div class="muted" style="margin-top:4px">${s.covered} covered • ${s.partial} partial • ${s.gap} gap</div>
    `;
    }

    function filterAssets() {
        const q = normalize($("#assetSearch").value);
        const crit = $("#critFilter").value;
        const type = $("#typeFilter").value;
        const st = $("#controlStatusFilter").value;
        const src = $("#sourceFilter")?.value || "all";

        return assets.filter((a) => {
            const text = normalize(`${a.name} ${a.subtitle} ${a.type} ${a.owner} ${a.notes} ${a.hostname || ""}`);
            const matchesQ = !q || text.includes(q);
            const matchesCrit = crit === "all" || a.criticity === crit;
            const matchesType = type === "all" || a.type === type;
            const matchesSrc = src === "all" || (a.source || "manual") === src;
            let matchesControl = true;
            if (st !== "all") {
                if (!a.controls || a.controls.length === 0) {
                    matchesControl = true;
                } else {
                    matchesControl = a.controls.some((c) => c.declaredStatus === st);
                }
            }
            return matchesQ && matchesCrit && matchesType && matchesSrc && matchesControl;
        });
    }

    function renderAssetsTable() {
        const tbody = $("#assetsTbody");
        tbody.innerHTML = "";

        const filtered = filterAssets();
        globalKpis(filtered);

        filtered.forEach((a) => {
            const tr = document.createElement("tr");

            // ✅ 1) calcula score e nível (usa tua função classify(score))
            const score = (a.prob || 0) * (a.impact || 0);
            const cls = classify(score); // cls.label -> "Baixo/Médio/Alto/Muito Alto"

            let tagsHtml = '<span class="muted" style="font-size:11px">Sem tags</span>';
            if (a.tags && a.tags.length > 0) {
                const tagsArray = Array.isArray(a.tags) ? a.tags : String(a.tags).split(',');

                tagsHtml = tagsArray
                    .map(t => {
                        const tagName = typeof t === 'object' ? (t.name || '') : String(t);
                        const tagColor = typeof t === 'object' && t.color ? t.color : '#60a5fa';
                        return tagName.trim() !== ''
                            ? `<span style="display:inline-block; padding: 2px 8px; border-radius: 4px; background: ${tagColor}15; color: ${tagColor}; font-size: 10px; margin-right: 4px; border: 1px solid ${tagColor}30;">${tagName.trim()}</span>`
                            : '';
                    })
                    .join('');
                if (!tagsHtml.trim()) tagsHtml = '<span class="muted" style="font-size:11px">Sem tags</span>';
            }

            const sourceClass = a.source === 'acronis' ? 'src-acronis' : 'src-manual';
            const sourceLbl = a.source === 'acronis' ? 'Acronis' : 'Manual';

            tr.innerHTML = `
      <td>
        <div class="asset-name">${a.hostname}</div>
        <div class="asset-sub">${a.status || a.subtitle}${a.ip ? ` · ${a.ip}` : ""}</div>
      </td>
      <td>${a.type}</td>
      <td>${criticityTag(a.criticity)}</td>
      <td>${tagsHtml}</td> <td><span class="src-badge ${sourceClass}">${sourceLbl}</span></td>
      <td>${a.owner}</td>
      <td>
        <span style="font-weight:600;color:${cls.cellClass === 'high' || cls.cellClass === 'vhigh' ? 'var(--bad)' : cls.cellClass === 'med' ? 'var(--warn)' : 'var(--ok)'}">${cls.label}</span>
        <div class="asset-sub">P=${a.prob} × I=${a.impact} = ${score}</div>
      </td>
      <td>${complianceTag(a)}</td>
      <td>
        <button class="btn-ghost btn-sm" type="button" data-open-asset="${a.id}">Ver detalhes</button>
      </td>
    `;

            tbody.appendChild(tr);
        });

        $$("[data-open-asset]").forEach((b) =>
            b.addEventListener("click", () => openAssetModal(b.dataset.openAsset))
        );
    }


    //Helpers mapear criticalidade
    function mapCriticality(val) {
        const map = { critical: "Crítico", high: "Alto", medium: "Médio", low: "Baixo" };
        return map[val?.toLowerCase()] || val || "Médio";
    }
    //========= Fetch de Ativos no API Fake do Acronis(só para mock)
    async function loadAssetsFromDB() {

        try {

            const res = await fetch("/api/assets");

            const data = await res.json();

            if (!res.ok) {
                console.error("Bloqueado pelo backend:", data);
                const tbody = document.getElementById("assetsTbody");
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; color:var(--bad); padding:20px;">Bloqueado: ${data.message}</td></tr>`;
                }
                return;
            }

            assets = data.map((a) => {

                let type = "Workstation";

                if (a.name?.includes("SRV"))
                    type = "Servidor";

                return {

                    id: a.id_asset,

                    name: a.display_name ?? a.hostname ?? "Asset",

                    subtitle: a.os_name || "Unknown OS",

                    type: type,
                    criticality: a.criticality || a.status || "medium",
                    tags: a.tags || [],
                    criticity: mapCriticality(a.criticality || a.status),
                    owner: a.owner || "—",
                    source: a.source || "manual",

                    ip: a.ip,
                    mac_address: a.mac_address,
                    hostname: a.hostname,

                    os_Name: a.os_name,
                    os_Version: a.os_version,

                    agent_status: a.agent_status,
                    agent_version: a.agent_version,
                    agent_last_seen: a.agent_last_seen,
                    os_arch: a.os_arch,
                    os_build: a.os_build,
                    os_patch_level: a.os_patch_level,
                    domain: a.domain ?? null,
                    createdBy: a.create_by ?? (a.source === 'acronis' ? 'Inserido automaticamente pelo Acronis' : 'Registo manual'),
                    acronis_synced_at: a.updatedat,   // usar updatedat como proxy do sync

                    prob: 3,
                    impact: 3,

                    controls: [],
                    risks: [],
                    treatments: []
                };

            });

            renderAssetsTable();

        } catch (err) {

            console.error("Erro ao carregar ativos:", err);

        }

    }

    // ========= details modal =========
    function renderControlsList(asset) {
        const wrap = $("#assetControlsList");
        wrap.innerHTML = "";

        if (!asset.controls.length) {
            wrap.innerHTML = `<div class="muted">Nenhum controlo associado ainda.</div>`;
            return;
        }

        asset.controls.forEach((c, idx) => {
            const meta = CONTROL_CATALOG[c.key] || { title: "—", desc: "" };

            const statusClass =
                c.declaredStatus === "GAP" ? "st-gap" :
                    c.declaredStatus === "PARTIAL" ? "st-partial" :
                        "st-covered";

            const sug = iaStatusFromConfidence(Number(c.confidence ?? 0));
            const diff = (c.declaredStatus !== sug);
            const sugClass = (sug === "COVERED" ? "ok" : (sug === "PARTIAL" ? "warn" : "bad"));

            const evidences = (c.evidences || []).map(e => `<span class="chip">${e}</span>`).join(" ");

            const row = document.createElement("div");
            row.className = "control-row";
            row.innerHTML = `
        <div class="control-left">
          <div class="control-title">
            <span class="control-code">${c.key}</span>
            <span class="status-pill ${statusClass}">${c.declaredStatus}</span>
            <span class="chip">Confiança: <b>${(c.confidence ?? 0).toFixed(2)}</b></span>
            <span class="chip ${sugClass}">IA: <b>${sug}</b></span>
            ${diff ? `<span class="chip warn">Revisão</span>` : ``}
            <span class="chip ${sugClass}">IA: <b>${sug}</b></span>
            ${diff ? `<span class="chip warn">Revisão</span>` : ``}
          </div>

          <div class="muted"><b>${meta.title}</b> — ${meta.desc}</div>

          <div class="muted" style="margin-top:6px">
            Nota: ${c.note ? c.note : "—"}
          </div>

          <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px">
            ${evidences ? `<div style="display:flex; gap:8px; flex-wrap:wrap">${evidences}</div>` : `<span class="muted">Sem evidências ligadas.</span>`}
          </div>
        </div>

        <div class="control-actions">
          <select class="mini" data-set-status="${asset.id}:${idx}" ${canOverrideStatus() ? "" : "disabled title='Somente GRC/Admin pode alterar status'"}>
            <option value="GAP" ${c.declaredStatus === "GAP" ? "selected" : ""}>GAP</option>
            <option value="PARTIAL" ${c.declaredStatus === "PARTIAL" ? "selected" : ""}>PARTIAL</option>
            <option value="COVERED" ${c.declaredStatus === "COVERED" ? "selected" : ""}>COVERED</option>
          </select>
          <button class="btn mini" type="button" data-edit-control="${asset.id}:${idx}">Editar nota</button>
          <button class="btn mini" type="button" data-add-evidence="${asset.id}:${idx}">+ Evidência</button>
          <button class="btn warn mini" type="button" data-remove-control="${asset.id}:${idx}">Remover</button>
        </div>
      `;
            wrap.appendChild(row);
        });

        // handlers
        $$("[data-set-status]").forEach((sel) => {
            sel.addEventListener("change", () => {
                const [assetId, idxStr] = sel.dataset.setStatus.split(":");
                const a = assets.find(x => x.id === assetId);
                const idx = Number(idxStr);
                a.controls[idx].declaredStatus = sel.value;
                const ai = mockAiSuggest(a.notes || a.name, a.controls[idx].key);
                a.controls[idx].aiSuggestedStatus = ai.suggestedStatus;
                a.controls[idx].aiConfidence = ai.confidence;
                a.controls[idx].aiJustification = ai.justification;
                renderAssetsTable();         // update summary
                renderControlsList(a);       // keep consistent
                renderAiSuggestions(a);
            });
        });

        $$("[data-edit-control]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const [assetId, idxStr] = btn.dataset.editControl.split(":");
                const a = assets.find(x => x.id === assetId);
                const idx = Number(idxStr);

                const current = a.controls[idx].note || "";
                const next = prompt("Editar nota/justificação (mock):", current);
                if (next === null) return;
                a.controls[idx].note = next.trim();
                renderControlsList(a);
                renderAiSuggestions(a);
            });
        });

        $$("[data-add-evidence]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const [assetId, idxStr] = btn.dataset.addEvidence.split(":");
                const a = assets.find(x => x.id === assetId);
                const idx = Number(idxStr);

                const ev = prompt("Nome da evidência (mock):", "Procedimento Inventário v1.0");
                if (!ev) return;

                a.controls[idx].evidences = a.controls[idx].evidences || [];
                a.controls[idx].evidences.push(ev.trim());
                renderControlsList(a);
            });
        });

        $$("[data-remove-control]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const [assetId, idxStr] = btn.dataset.removeControl.split(":");
                const a = assets.find(x => x.id === assetId);
                const idx = Number(idxStr);

                a.controls.splice(idx, 1);
                renderAssetsTable();
                renderControlsList(a);
                renderAiSuggestions(a);
            });
        });
    }

    function renderAiSuggestions(asset) {
        const box = $("#aiSuggestions");
        box.innerHTML = "";

        // heurística simples (mock):
        // se status = GAP => "Falta evidência / processo"
        // se PARTIAL => "Melhorar evidência / periodicidade"
        // se COVERED => "Ok"
        asset.controls.forEach((c) => {
            const meta = CONTROL_CATALOG[c.key] || { title: "—", desc: "" };

            let badge = "OK";
            let text = "Parece alinhado com o controlo.";
            if (c.declaredStatus === "GAP") {
                badge = "LACUNA";
                text = "O ativo menciona o tema, mas não há evidência/procedimento suficiente. Sugestão: criar evidência e ligar documento.";
            } else if (c.declaredStatus === "PARTIAL") {
                badge = "MELHORAR";
                text = "Há sinais de implementação, mas falta completar requisitos (ex.: periodicidade, dono, teste/relatório).";
            }

            const item = document.createElement("div");
            item.className = "ai-item";
            item.innerHTML = `
        <div class="top">
          <div>
            <div class="title">${c.key} — ${meta.title}</div>
            <div class="desc">${text}</div>
          </div>
          <span class="badg">${badge}</span>
        </div>
        <div class="muted" style="margin-top:8px">
          Confiança (mock): <b>${(c.confidence ?? 0).toFixed(2)}</b> • Status atual: <b>${c.declaredStatus}</b>
        </div>
      `;
            box.appendChild(item);
        });

        if (!asset.controls.length) {
            box.innerHTML = `<div class="muted">Sem controlos associados — a IA ainda não tem base para sugerir melhorias.</div>`;
        }
    }
    function renderRiskTreat(asset) {
        const wrap = $("#assetRiskTreatList");
        if (!wrap) return;
        const risks = asset.risks || [];
        const tps = asset.treatments || [];

        if (!risks.length && !tps.length) {
            wrap.innerHTML = `<div class="muted" style="font-size:12px;">Sem riscos/planos ligados a este ativo.</div>`;
            return;
        }

        const sevColor = { 'Crítico': 'var(--bad)', 'Alto': 'var(--bad)', 'Médio': 'var(--warn)', 'Baixo': 'var(--ok)' };

        const riskRows = risks.map(r => {
            const relatedTreatments = tps.filter(t => t.riskId === r.id);
            const treatHtml = relatedTreatments.map(t => `
                <div class="am-treat-row">
                    <span class="am-treat-id">${t.id}</span>
                    <span style="font-weight:600;color:var(--text);">${t.title}</span>
                    <span class="muted" style="font-size:11px;"> · ${t.status} · ${t.owner} · até ${t.due}</span>
                </div>`).join('');

            return `
            <div class="am-risk-row">
                <div class="am-risk-top">
                    <span class="am-risk-id">${r.id}</span>
                    <span style="font-size:13px;font-weight:600;color:var(--text);">${r.title}</span>
                    <span style="font-size:11px;font-weight:600;color:${sevColor[r.severity] || 'var(--muted)'};">${r.severity}</span>
                    <span class="muted" style="font-size:11px;margin-left:auto;">${r.status} · ${r.lastSeen}</span>
                </div>
                ${treatHtml || '<div class="muted" style="font-size:11px;">Sem planos de tratamento.</div>'}
            </div>`;
        }).join('');

        wrap.innerHTML = riskRows;

        const goBtn = $("#btnGoRisksForAsset");
        if (goBtn) goBtn.addEventListener("click", () => goTo(`/riscos?asset=${asset.id}`));
    }

    function setTxt(id, val) { const el = $(id); if (el) el.textContent = val || "—"; }

    function openAssetModal(assetId) {
        const asset = assets.find((a) => String(a.id) === String(assetId));
        if (!asset) return;
        currentAssetId = assetId;

        // ── Header ──
        const typeIcons = { 'Servidor': '🖥', 'Aplicação': '🌐', 'Rede': '🔒', 'Cloud': '☁', 'Endpoint': '💻', 'Workstation': '🖥' };
        setTxt("#mAssetIcon", typeIcons[asset.type] || '⬡');
        setTxt("#assetModalTitle", asset.name);
        setTxt("#mTypeChip", asset.type);
        setTxt("#mCritChip", asset.criticity);
        setTxt("#mIpChip", asset.ip || '—');

        // source badge in header
        const srcBadge = $("#mSourceBadge");
        if (srcBadge) {
            const isAcronis = asset.source === 'acronis';
            srcBadge.textContent = isAcronis ? 'Acronis' : 'Manual';
            srcBadge.className = 'src-badge ' + (isAcronis ? 'src-acronis' : 'src-manual');
        }

        // agent chip in header
        const agentChip = $("#mAgentChip");
        if (agentChip) {
            if (asset.agent_status) {
                agentChip.textContent = asset.agent_status === 'online' ? '● Online' : '○ Offline';
                agentChip.className = 'meta-chip meta-agent ' + asset.agent_status;
            } else {
                agentChip.textContent = '—';
                agentChip.className = 'meta-chip meta-agent';
            }
        }

        // ── Overview: Geral ──
        setTxt("#mOwner", asset.owner);
        setTxt("#mCreatedBy", asset.createdBy);
        setTxt("#mSource", asset.source === 'acronis' ? 'Sincronizado via Acronis' : 'Registo manual');
        setTxt("#mSyncedAt", asset.acronis_synced_at ? new Date(asset.acronis_synced_at).toLocaleString('pt-PT') : '—');

        // ── Rede ──
        setTxt("#mIpDetail", asset.ip);
        setTxt("#mMac", asset.mac_address);
        setTxt("#mHostname", asset.hostname);
        setTxt("#mDomain", asset.domain);

        // ── OS ──
        setTxt("#mOsName", asset.os_Name);
        setTxt("#mOsVersion", asset.os_Version);
        setTxt("#mOsBuild", asset.os_build);
        setTxt("#mOsArch", asset.os_arch);
        setTxt("#mOsPatch", asset.os_patch_level);

        // ── Agent ──
        const agentStatusEl = $("#mAgentStatus");
        if (agentStatusEl) {
            const st = asset.agent_status;
            if (st === 'online') { agentStatusEl.innerHTML = `<span style="color:var(--ok);font-weight:600;">● Online</span>`; }
            else if (st === 'offline') { agentStatusEl.innerHTML = `<span style="color:var(--bad);font-weight:600;">○ Offline</span>`; }
            else { agentStatusEl.textContent = '—'; }
        }
        setTxt("#mAgentVersion", asset.agent_version);
        setTxt("#mAgentLastSeen", asset.agent_last_seen ? new Date(asset.agent_last_seen).toLocaleString('pt-PT') : '—');

        // ── Protection ──
        const protGrid = $("#mProtectionGrid");
        if (protGrid) {
            if (asset.source === 'acronis') {
                const items = [
                    { lbl: 'Backup', val: asset.backup_enabled },
                    { lbl: 'Antimalware', val: asset.antimalware_enabled },
                    { lbl: 'Patch Management', val: asset.patch_mgmt_enabled },
                ];
                protGrid.innerHTML = items.map(i => `
                    <div class="prot-row">
                        <span class="prot-lbl">${i.lbl}</span>
                        <span class="${i.val ? 'prot-on' : 'prot-off'}">${i.val ? '✓ Activo' : '✗ Inactivo'}</span>
                    </div>`).join('');
            } else {
                protGrid.innerHTML = `<div class="prot-row"><span class="prot-lbl" style="font-style:italic;">Não disponível para ativos manuais</span></div>`;
            }
        }

        // ── Risk score ──
        setTxt("#mProb", String(asset.prob));
        setTxt("#mImpact", String(asset.impact));
        const score = asset.prob * asset.impact;
        const cls = classify(score);
        const scoreEl = $("#mScoreVal");
        if (scoreEl) {
            scoreEl.textContent = score;
            const scoreColors = { vlow: 'var(--muted)', low: 'var(--ok)', med: 'var(--warn)', high: 'var(--bad)', vhigh: 'var(--bad)' };
            scoreEl.style.color = scoreColors[cls.cellClass] || 'var(--text)';
        }
        const classEl = $("#mClassChip");
        if (classEl) {
            classEl.textContent = cls.label;
            const cc = { vlow: 'var(--muted)', low: 'var(--ok)', med: 'var(--warn)', high: 'var(--bad)', vhigh: 'var(--bad)' };
            classEl.style.color = cc[cls.cellClass] || 'var(--text)';
        }

        setTxt("#mNotes", asset.notes);

        buildMatrix(asset.prob, asset.impact);
        renderControlsList(asset);
        renderAiSuggestions(asset);
        renderRiskTreat(asset);
        loadAssetRisks(assetId);

        // update controls tab badge
        const badge = $("#tabBadgeControls");
        if (badge) badge.textContent = String(asset.controls.length);

        // reset to overview tab
        activateTab('overview');

        openModal("#assetModal");
    }

    function activateTab(tabId) {
        $$(".am-tab").forEach(t => t.classList.toggle("active", t.dataset.tab === tabId));
        $$(".am-tab-panel").forEach(p => p.classList.toggle("active", p.id === "tab-" + tabId));
    }

    // ========= create/edit asset modal =========
    function resetCreateControlsPreview() {
        const wrap = $("#createControlsPreview");
        wrap.innerHTML = "";

        if (!createControlsWorking.length) {
            wrap.innerHTML = `<div class="muted">Ainda não adicionaste controlos.</div>`;
            return;
        }

        createControlsWorking.forEach((c, idx) => {
            const meta = CONTROL_CATALOG[c.key] || { title: "—" };
            const row = document.createElement("div");
            const sug = iaStatusFromConfidence(Number(c.confidence ?? 0));
            const diff = (c.declaredStatus !== sug);
            const sugClass = (sug === "COVERED" ? "ok" : (sug === "PARTIAL" ? "warn" : "bad"));
            row.className = "control-row";
            row.innerHTML = `
        <div class="control-left">
          <div class="control-title">
            <span class="control-code">${c.key}</span>
            <span class="status-pill ${c.declaredStatus === "GAP" ? "st-gap" : c.declaredStatus === "PARTIAL" ? "st-partial" : "st-covered"}">${c.declaredStatus}</span>
            <span class="chip">Confiança: <b>${(c.confidence ?? 0).toFixed(2)}</b></span>
          </div>
          <div class="muted"><b>${meta.title}</b></div>
          <div class="muted">Nota: ${c.note ? c.note : "—"}</div>
        </div>
        <div class="control-actions">
          <button class="btn warn mini" type="button" data-remove-create-control="${idx}">Remover</button>
        </div>
      `;
            wrap.appendChild(row);
        });

        $$("[data-remove-create-control]").forEach((b) => {
            b.addEventListener("click", () => {
                const idx = Number(b.dataset.removeCreateControl);
                createControlsWorking.splice(idx, 1);
                resetCreateControlsPreview();
            });
        });
    }

    function openCreateAsset() {
        editingAssetId = null;
        createControlsWorking = [];

        $("#assetEditSubtitle").textContent = "Registar ativo (mock)";
        $("#assetEditTitle").textContent = "Novo ativo";

        $("#fName").value = "";
        $("#fType").value = "Servidor";
        $("#fIp").value = "";
        $("#fTags").value = "";
        toggleIpFieldByType($("#fType").value);
        $("#fCrit").value = "Médio";
        $("#fOwner").value = "";
        $("#fProb").value = "3";
        $("#fImpact").value = "3";
        $("#fNotes").value = "";

        $("#btnDeleteAsset").style.display = "none";

        // defaults do bloco de controlos
        $("#fControlPick").value = "ID.GA-1";
        syncControlInfo($("#fControlPick"), $("#fControlInfo"), $("#fControlInfoText"));
        applyStatusGuard($("#fControlStatus"), $("#fStatusHint"));
        $("#fControlNote").value = "";

        resetCreateControlsPreview();

        openModal("#assetEditModal");
    }

    function openEditAsset(assetId) {
        const a = assets.find(x => x.id === assetId);
        if (!a) return;

        editingAssetId = assetId;
        createControlsWorking = []; // edit controls via details modal; aqui mantém simples

        $("#assetEditSubtitle").textContent = "Editar ativo (mock)";
        $("#assetEditTitle").textContent = a.name;

        $("#fName").value = a.name;
        $("#fType").value = a.type;
        $("#fIp").value = a.ip || "";
        toggleIpFieldByType($("#fType").value);
        const tagsStr = Array.isArray(a.tags) ? a.tags.join(', ') : (a.tags || "");
        $("#fTags").value = tagsStr;
        $("#fCrit").value = a.criticity;
        $("#fOwner").value = a.owner;
        $("#fProb").value = String(a.prob);
        $("#fImpact").value = String(a.impact);
        $("#fNotes").value = a.notes || "";

        $("#btnDeleteAsset").style.display = "inline-flex";

        // defaults do bloco de controlos
        $("#fControlPick").value = "ID.GA-1";
        syncControlInfo($("#fControlPick"), $("#fControlInfo"), $("#fControlInfoText"));
        applyStatusGuard($("#fControlStatus"), $("#fStatusHint"));
        $("#fControlNote").value = "";

        resetCreateControlsPreview();

        openModal("#assetEditModal");
    }

    async function saveAssetFromForm() {
        const name = $("#fName").value.trim();
        if (!name) return alert("Nome é obrigatório.");

        const type = $("#fType").value;
        const criticity = $("#fCrit").value;
        const owner = $("#fOwner").value.trim() || "—";
        const prob = Number($("#fProb").value);
        const impact = Number($("#fImpact").value);
        const notes = $("#fNotes").value.trim();
        const ip = ($("#fIp")?.value || "").trim();
        const tags = ($("#fTags")?.value || "").trim();
        if (!editingAssetId) {
            // ── Persiste na base de dados ──
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

            const payload = {
                name: name,
                type: type,
                criticity: criticity,
                owner: owner,
                ip: ip || null,
                prob: prob,
                impact: impact,
                criticality: mapCriticality(criticity) || "medium",
                notes: notes || null,
                tags: tags
            };

            try {
                const res = await fetch("/api/assets", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken || ""
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (!res.ok) {
                    alert(`❌ Erro ao guardar ativo:\n${data.message || "Erro desconhecido"}`);
                    return;
                }

                // Adiciona ao estado local com o id real da BD
                const newAsset = {
                    id: data.id_asset,
                    name,
                    subtitle: `${type} • Manual`,
                    type,
                    source: "manual",
                    criticity,
                    owner,
                    ip,
                    mac_address: "",
                    hostname: name,
                    domain: "",
                    os_Name: "",
                    os_Version: "",
                    os_arch: "",
                    os_build: "",
                    os_patch_level: "",
                    agent_status: "",
                    agent_version: "",
                    agent_last_seen: null,
                    backup_enabled: false,
                    antimalware_enabled: false,
                    patch_mgmt_enabled: false,
                    acronis_synced_at: null,
                    createdBy: "Registo manual",
                    notes,
                    prob,
                    impact,
                    controls: createControlsWorking.map(c => {
                        const ai = mockAiSuggest(notes || name, c.key);
                        return {
                            key: c.key,
                            declaredStatus: c.declaredStatus,
                            aiSuggestedStatus: ai.suggestedStatus,
                            aiConfidence: ai.confidence,
                            aiJustification: ai.justification,
                            note: c.note || "",
                            evidences: []
                        };
                    }),
                    risks: [],
                    treatments: []
                };

                assets.unshift(newAsset);

            } catch (err) {
                alert(`❌ Erro de conexão ao guardar ativo.\n${err.message}`);
                return;
            }
        } else {
            const a = assets.find(x => x.id === editingAssetId);
            a.name = name;
            a.type = type;
            a.criticity = criticity;
            a.owner = owner;
            a.ip = ip;
            a.notes = notes;
            a.prob = prob;
            a.impact = impact;
            // (controls ficam como estavam; mock)
        }

        renderAssetsTable();
        closeModal("#assetEditModal");

        // se estava com detalhes aberto do mesmo ativo, re-render
        if (currentAssetId) {
            const a = assets.find(x => x.id === currentAssetId);
            if (a) openAssetModal(a.id);
        }
    }

    function deleteAssetMock() {
        if (!editingAssetId) return;
        const a = assets.find(x => x.id === editingAssetId);
        if (!a) return;

        const ok = confirm(`Eliminar o ativo "${a.name}"? (mock)`);
        if (!ok) return;

        assets = assets.filter(x => x.id !== editingAssetId);
        editingAssetId = null;

        renderAssetsTable();
        closeModal("#assetEditModal");

        // se estava com detalhes aberto
        if (currentAssetId === a.id) {
            closeModal("#assetModal");
            currentAssetId = null;
        }
    }

    function addControlToCreatePreview() {
        const key = $("#fControlPick").value;
        if (!canOverrideStatus()) return alert('Somente GRC Manager/Admin podem associar controlos (RF19).');
        const declaredStatus = $("#fControlStatus").value;
        const note = $("#fControlNote").value.trim();
        const exists = createControlsWorking.some(c => c.key === key);
        if (exists) return alert("Este controlo já foi adicionado (mock).");

        createControlsWorking.unshift({ key, declaredStatus, note });
        $("#fControlNote").value = "";
        resetCreateControlsPreview();
    }

    // ========= add control modal (from asset details) =========
    let addControlAssetId = null;

    function openAddControlModal(assetId) {
        const a = assets.find(x => x.id === assetId);
        if (!a) return;

        addControlAssetId = assetId;
        $("#addControlTitle").textContent = a.name;

        $("#acControl").value = "ID.GA-1";
        const ai0 = mockAiSuggest(a.notes || a.name, $("#acControl").value);
        // default: status declarado começa igual à sugestão IA
        $("#acStatus").value = ai0.suggestedStatus;
        syncControlInfo($("#acControl"), $("#acControlInfo"), $("#acControlInfoText"));
        // status: IA sugere por confiança (somente GRC/Admin pode alterar)
        applyStatusGuard($("#acStatus"), $("#acStatusHint"));
        updateDiffChip($("#acStatus").value, ai0.suggestedStatus, $("#acAiDiffChip"));

        $("#acNote").value = "";

        openModal("#addControlModal");
    }

    function addControlConfirm() {
        const a = assets.find(x => x.id === addControlAssetId);
        if (!a) return;

        const key = $("#acControl").value;
        if (a.controls.some(c => c.key === key)) {
            return alert("Este controlo já está associado a este ativo (mock).");
        }

        const confidence = 0.55;
        const status = canOverrideStatus() ? $("#acStatus").value : iaStatusFromConfidence(confidence);
        const note = $("#acNote").value.trim();

        a.controls.unshift({
            key,
            declaredStatus: status,
            confidence,
            note,
            evidences: []
        });
        renderAssetsTable();
        renderControlsList(a);
        renderAiSuggestions(a);

        closeModal("#addControlModal");
    }

    // ========= nav buttons (mock) =========
    function goTo(routeName) {
        // se tu usa route() no blade do menu, aqui é só mock.
        // Ajusta para a tua rota real.
        window.location.href = routeName;
    }

    // ========= sync acronis =========
    async function syncAcronis() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
            if (!csrfToken) {
                console.warn("CSRF token não encontrado no DOM.");
            }

            const res = await fetch("/api/assets/sync-acronis", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken || ""
                }
            });

            const text = await res.text();

            console.group("🔄 Sync Acronis — Resposta do servidor");
            console.log("Status:", res.status, res.statusText);
            console.log("Body (raw):", text);
            console.groupEnd();

            let data;

            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error("❌ Resposta não é JSON válido. Body completo:", text);
                alert(
                    `Erro ${res.status}: O servidor não devolveu JSON válido.\n\n` +
                    `Resposta:\n${text.substring(0, 500)}`
                );
                return;
            }

            if (res.ok) {
                alert(`✅ Sync concluído! ${data.count} ativos importados/atualizados.`);
                loadAssetsFromDB();
            } else {
                console.error("❌ Erro do servidor:", data);
                alert(
                    `❌ Erro ${res.status} ao sincronizar:\n\n` +
                    `Mensagem: ${data.message || "Sem mensagem"}\n` +
                    `Detalhe: ${data.error || data.exception || "Sem detalhe"}\n` +
                    (data.file ? `Ficheiro: ${data.file}:${data.line}\n` : "") +
                    (data.trace ? `\nStack (primeiros 300 chars):\n${JSON.stringify(data.trace).substring(0, 300)}` : "")
                );
            }

        } catch (err) {
            console.error("❌ Erro de rede/conexão ao sincronizar Acronis:", err);
            console.error("Stack:", err.stack);
            alert(
                `❌ Erro de conexão ao sincronizar Acronis.\n\n` +
                `Tipo: ${err.name}\n` +
                `Mensagem: ${err.message}\n\n` +
                `Verifica se o servidor Laravel está a correr.`
            );
        }
    }

    // ========= init =========
    function init() {
        // Render inicial com dados mock enquanto o fetch não termina
        renderAssetsTable();

        // Carrega ativos reais da API
        loadAssetsFromDB();

        // 🛡️ HELPER À PROVA DE BALA: 
        // Só adiciona o evento se o elemento existir no HTML (não estiver bloqueado pelo RBAC)
        const bindClick = (sel, fn) => { const el = $(sel); if (el) el.addEventListener("click", fn); };
        const bindChange = (sel, fn) => { const el = $(sel); if (el) el.addEventListener("change", fn); };
        const bindInput = (sel, fn) => { const el = $(sel); if (el) el.addEventListener("input", fn); };

        // Filtros da tabela
        bindInput("#assetSearch", renderAssetsTable);
        bindChange("#critFilter", renderAssetsTable);
        bindChange("#typeFilter", renderAssetsTable);
        bindChange("#controlStatusFilter", renderAssetsTable);
        bindChange("#sourceFilter", renderAssetsTable);

        // Botões de Ação (Que podem estar escondidos pelo RBAC!)
        bindClick("#btnSyncAcronis", syncAcronis);
        bindClick("#btnOpenCreateAsset", openCreateAsset);
        bindClick("#btnEditAsset", () => { if (currentAssetId) openEditAsset(currentAssetId); });
        bindClick("#btnAddControlToAsset", () => { if (currentAssetId) openAddControlModal(currentAssetId); });
        bindClick("#btnCreateRiskFromAssetTab", () => { if (currentAssetId) goTo(`/riscos?new_risk_for=${currentAssetId}`); });

        // Fechar Modais
        bindClick("#assetModalClose", () => closeModal("#assetModal"));
        bindClick("#assetEditClose", () => closeModal("#assetEditModal"));
        bindClick("#addControlClose", () => closeModal("#addControlModal"));

        // Fechar clicando fora da caixa
        const mAsset = $("#assetModal");
        if (mAsset) mAsset.addEventListener("click", (e) => { if (e.target === e.currentTarget) closeModal("#assetModal"); });

        const mEdit = $("#assetEditModal");
        if (mEdit) mEdit.addEventListener("click", (e) => { if (e.target.id === "assetEditModal") closeModal("#assetEditModal"); });

        const mCtrl = $("#addControlModal");
        if (mCtrl) mCtrl.addEventListener("click", (e) => { if (e.target.id === "addControlModal") closeModal("#addControlModal"); });

        // Fechar com a tecla ESC
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                closeModal("#assetModal");
                closeModal("#assetEditModal");
                closeModal("#addControlModal");
            }
        });

        // Eventos gerais dos formulários
        bindChange("#fType", () => toggleIpFieldByType($("#fType")?.value));
        bindClick("#btnAddControlConfirm", addControlConfirm);
        bindClick("#btnAddControlCancel", () => closeModal("#addControlModal"));
        bindClick("#btnSaveAsset", saveAssetFromForm);
        bindClick("#btnDeleteAsset", deleteAssetMock);
        bindClick("#btnAddControlInline", addControlToCreatePreview);

        // Tooltips e guards do RBAC nos controlos
        bindChange("#fControlPick", () => syncControlInfo($("#fControlPick"), $("#fControlInfo"), $("#fControlInfoText")));
        if ($("#fControlStatus")) applyStatusGuard($("#fControlStatus"), $("#fStatusHint"));

        bindChange("#acControl", () => {
            syncControlInfo($("#acControl"), $("#acControlInfo"), $("#acControlInfoText"));
            const a = assets.find(x => String(x.id) === String(addControlAssetId));
            if (!a) return;
            const ai = mockAiSuggest(a.notes || a.name, $("#acControl").value);
            if (!canOverrideStatus()) {
                const acStatus = $("#acStatus");
                if (acStatus) acStatus.value = ai.suggestedStatus;
            }
            updateDiffChip($("#acStatus")?.value, ai.suggestedStatus, $("#acAiDiffChip"));
        });

        bindChange("#acStatus", () => {
            const a = assets.find(x => String(x.id) === String(addControlAssetId));
            if (!a) return;
            const ai = mockAiSuggest(a.notes || a.name, $("#acControl").value);
            updateDiffChip($("#acStatus")?.value, ai.suggestedStatus, $("#acAiDiffChip"));
        });

        if ($("#acStatus")) applyStatusGuard($("#acStatus"), $("#acStatusHint"));

        // Botões de navegação (Mock)
        bindClick("#btnGoRisks", () => goTo("/riscos"));
        bindClick("#btnGoDocs", () => goTo("/documentos"));
        bindClick("#btnGoAssessments", () => goTo("/avaliacoes"));
        bindClick("#btnGoRisksForAsset", () => goTo(`/riscos?asset=${currentAssetId}`));

        // Abas dentro do Modal do ativo
        $$(".am-tab").forEach(tab => {
            tab.addEventListener("click", () => activateTab(tab.dataset.tab));
        });
    }

    document.addEventListener("DOMContentLoaded", init);
})();
