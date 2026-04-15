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
    let AVAILABLE_DOCS = [];
    let ALL_DB_TAGS = [];

    async function loadDbTags() {
        try {
            const r = await fetch('/api/asset-tags', { headers: { Accept: 'application/json' } });
            if (r.ok) {
                ALL_DB_TAGS = await r.json();

                // Preencher o filtro da tabela com as tags disponíveis!
                const filterSelect = $("#tagFilter");
                if (filterSelect) {
                    filterSelect.innerHTML = '<option value="all">Tag (Todas)</option>' +
                        ALL_DB_TAGS.map(t => `<option value="${t.name}">${t.name}</option>`).join('');
                }
            }
        } catch (e) { console.error("Erro a carregar lista global de tags:", e); }
    }

    async function loadDocumentsData() {
        const list = $('#acEvidenceList');
        try {
            const r = await fetch('/api/documents', { headers: { Accept: 'application/json' } });
            if (!r.ok) throw new Error("Erro na API: " + r.status);
            const res = await r.json();

            AVAILABLE_DOCS = res.data || res;
            if (!Array.isArray(AVAILABLE_DOCS)) AVAILABLE_DOCS = [];

            renderEvidenceSelector();
        } catch (e) {
            console.error("Erro a carregar documentos:", e);
            if (list) list.innerHTML = `<div style="color:var(--bad);font-size:12px;padding:8px;">Erro de conexão ao carregar ficheiros.</div>`;
        }
    }

    function renderEvidenceSelector() {
        const list = $('#acEvidenceList');
        if (!list) return;
        if (!AVAILABLE_DOCS.length) {
            list.innerHTML = `<div class="muted" style="font-size:12px;">Nenhum documento disponível.</div>`;
            return;
        }
        list.innerHTML = AVAILABLE_DOCS.map(d => `
        <label class="evi" style="cursor:pointer; display:flex; align-items:center; justify-content:space-between; padding: 8px; border: 1px solid var(--modal-border); border-radius: 6px; margin-bottom: 4px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" value="${d.title || d.file_name}" class="ac-doc-checkbox" />
                <span class="chip" style="font-size:11px;">${d.title || d.original_name || d.file_name}</span>
            </div>
            <span class="muted" style="font-size:10px;">${d.status === 'approved' ? 'Aprovado' : 'Pendente'}</span>
        </label>
    `).join('');
    }

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
    let assets = [];


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
        if (!filteredAssets) return;

        const total = filteredAssets.length;
        const wazuhCount = filteredAssets.filter(a => a.source === 'wazuh').length;

        // Verifica criticidade alta/crítica (suporta os dados da BD e os mocks em PT)
        const highRiskCount = filteredAssets.filter(a =>
            ['high', 'critical', 'Alto', 'Crítico'].includes(a.criticality) ||
            ['high', 'critical', 'Alto', 'Crítico'].includes(a.criticity)
        ).length;

        // Conta os ativos do Wazuh cujo agente não está ativo/online
        const offlineCount = filteredAssets.filter(a => {
            if (a.source !== 'wazuh') return false;
            const st = (a.agent_status || '').toLowerCase();
            return st !== 'active' && st !== 'online';
        }).length;

        if ($("#kpiTotal")) $("#kpiTotal").textContent = total;
        if ($("#kpiWazuh")) $("#kpiWazuh").textContent = wazuhCount;
        if ($("#kpiHighRisk")) $("#kpiHighRisk").textContent = highRiskCount;
        if ($("#kpiOffline")) $("#kpiOffline").textContent = offlineCount;
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
        const tag = $("#tagFilter")?.value || "all";
        const src = $("#sourceFilter")?.value || "all";

        return assets.filter((a) => {
            const text = normalize(`${a.name} ${a.subtitle} ${a.type} ${a.owner} ${a.notes} ${a.hostname || ""}`);
            const matchesQ = !q || text.includes(q);
            const matchesCrit = crit === "all" || a.criticity === crit;
            const matchesType = type === "all" || a.type === type;
            const matchesSrc = src === "all" || (a.source || "manual") === src;

            // Nova lógica de pesquisa por Tag
            let matchesTag = true;
            if (tag !== "all") {
                if (!a.tags || a.tags.length === 0) {
                    matchesTag = false;
                } else {
                    matchesTag = a.tags.some(t => {
                        const tName = typeof t === 'object' ? t.name : String(t);
                        return tName.toLowerCase() === tag.toLowerCase();
                    });
                }
            }

            return matchesQ && matchesCrit && matchesType && matchesSrc && matchesTag;
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

            const sourceClass = a.source === 'wazuh' ? 'src-wazuh' : 'src-manual';
            const sourceLbl = a.source === 'wazuh' ? 'Wazuh' : 'Manual';

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
<td>
          ${a.source === 'wazuh'
                    ? (['active', 'online'].includes((a.agent_status || '').toLowerCase())
                        ? `<div style="display:flex; align-items:center; gap:6px;">
                        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background-color:var(--ok); box-shadow: 0 0 6px var(--ok);"></span>
                        <span style="font-size:12px; font-weight:500;">Online</span>
                     </div>`
                        : `<div style="display:flex; align-items:center; gap:6px;">
                        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background-color:var(--bad);"></span>
                        <span style="font-size:12px; font-weight:500; color:var(--muted);">Offline</span>
                     </div>`)
                    : `<span class="muted" style="font-size:11px;">Não aplicável</span>`
                }
      </td>
    <td style="text-align:right;">
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

                    type: a.type || "Endpoint",
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
    // ========= ANÁLISE DE POSTURA IA =========
    async function loadAssetAnalyses(assetId) {
        const box = $("#aiAnalysisHistory");
        if (!box) return;

        box.innerHTML = `<div class="spinner" style="margin:20px auto;width:24px;height:24px;border:3px solid var(--muted);border-top-color:transparent;border-radius:50%;animation:spin 1s linear infinite;"></div>`;

        try {
            const res = await fetch(`/api/assets/${assetId}/analyses`);
            const data = await res.json();

            if (!data || data.length === 0) {
                box.innerHTML = `<div class="muted" style="font-size:13px; text-align:center; padding:30px; border: 1px dashed var(--modal-border); border-radius:12px;">Nenhuma análise efetuada para este ativo. Clica no botão acima para gerar o primeiro relatório.</div>`;
                return;
            }

            box.innerHTML = data.map((an, index) => `
                <details class="ai-history-card" ${index === 0 ? 'open' : ''} style="background:rgba(255,255,255,0.02); border:1px solid var(--modal-border); border-radius:12px; margin-bottom:10px;">
                    <summary style="display:flex; justify-content:space-between; align-items:center; padding:14px 16px; cursor:pointer; user-select:none; outline:none;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg class="ai-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--muted); transition:transform 0.2s;"><path d="m6 9 6 6 6-6"/></svg>
                            <span style="font-size:11px; font-weight:700; color:var(--text); text-transform:uppercase;">Análise de Postura</span>
                        </div>
                        <span style="font-size:11px; color:var(--muted); font-family:var(--font-mono,monospace);">${new Date(an.created_at).toLocaleString('pt-PT')}</span>
                    </summary>
                    <div style="padding: 0 16px 16px 16px; font-size:13px; color:var(--text); line-height:1.6; border-top: 1px solid var(--modal-border); margin-top:4px; padding-top:12px;">
                        ${an.analysis_text}
                    </div>
                </details>
            `).join('');

        } catch (e) {
            box.innerHTML = `<div style="color:var(--bad); font-size:12px; text-align:center; padding:20px;">Erro ao carregar histórico: ${e.message}</div>`;
        }
    }

    async function generateAiAnalysis() {
        if (!currentAssetId) return;

        const btn = $("#btnGenerateAiAnalysis");
        const originalHtml = btn.innerHTML;
        btn.innerHTML = `<span class="spinner" style="width:14px;height:14px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:spin 1s linear infinite;display:inline-block;margin-right:6px;"></span> A analisar...`;
        btn.disabled = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
            const r = await fetch(`/api/assets/${currentAssetId}/analyze`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken || '', 'Accept': 'application/json' }
            });
            const data = await r.json();

            if (!r.ok) throw new Error(data.message || 'Erro desconhecido');

            // Recarregar a lista para mostrar a nova
            loadAssetAnalyses(currentAssetId);

        } catch (err) {
            alert(`❌ Erro da IA: ${err.message}`);
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    }
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
            const isWazuh = asset.source === 'wazuh';
            srcBadge.textContent = isWazuh ? 'Wazuh' : 'Manual';
            srcBadge.className = 'src-badge ' + (isWazuh ? 'src-wazuh' : 'src-manual');
        }

        // agent chip in header
        const agentChip = $("#mAgentChip");
        if (agentChip) {
            const st = (asset.agent_status || '').toLowerCase();
            if (['active', 'online'].includes(st)) {
                agentChip.innerHTML = `<span style="display:inline-block; width:6px; height:6px; border-radius:50%; background-color: var(--ok); margin-right:6px;"></span>Online`;
            } else if (['offline', 'disconnected'].includes(st)) {
                agentChip.innerHTML = `<span style="display:inline-block; width:6px; height:6px; border-radius:50%; background-color: var(--bad); margin-right:6px;"></span>Offline`;
            } else {
                agentChip.innerHTML = '—';
            }
            agentChip.className = 'meta-chip meta-agent'; // Remove cores de fundo antigas
        }

        // ── Overview: Geral ──
        setTxt("#mOwner", asset.owner);
        setTxt("#mCreatedBy", asset.createdBy);
        setTxt("#mSource", asset.source === 'wazuh' ? 'Sincronizado via Wazuh' : 'Registo manual');
        setTxt("#mSyncedAt", asset.wazuh_synced_at ? new Date(asset.wazuh_synced_at).toLocaleString('pt-PT') : '—');
        renderModalTags(asset.tags);
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
            const st = (asset.agent_status || '').toLowerCase();
            if (['active', 'online'].includes(st)) {
                agentStatusEl.innerHTML = `<div style="display:flex;align-items:center;gap:6px;"><span style="width:8px;height:8px;border-radius:50%;background:var(--ok);box-shadow:0 0 6px var(--ok);"></span><span style="font-weight:500;color:var(--text);">Online</span></div>`;
            }
            else if (['offline', 'disconnected'].includes(st)) {
                agentStatusEl.innerHTML = `<div style="display:flex;align-items:center;gap:6px;"><span style="width:8px;height:8px;border-radius:50%;background:var(--bad);"></span><span style="font-weight:500;color:var(--text);">Offline</span></div>`;
            }
            else { agentStatusEl.textContent = '—'; }
        }
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

        renderRiskTreat(asset);
        loadAssetRisks(assetId);
        loadAssetAnalyses(assetId);
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
        // CORREÇÃO 1: Forçar conversão para String para evitar falhas no find()
        const a = assets.find(x => String(x.id) === String(assetId));
        if (!a) return;

        editingAssetId = assetId;
        createControlsWorking = [];

        $("#assetEditSubtitle").textContent = "Editar ativo (mock)";
        $("#assetEditTitle").textContent = a.name;

        $("#fName").value = a.name;
        $("#fType").value = a.type;
        $("#fIp").value = a.ip || "";
        toggleIpFieldByType($("#fType").value);

        // CORREÇÃO 2: Ler as tags corretamente quer venham da BD (objetos) ou do mock (strings)
        const tagsStr = Array.isArray(a.tags)
            ? a.tags.map(t => typeof t === 'object' ? t.name : t).join(', ')
            : (a.tags || "");
        $("#fTags").value = tagsStr;

        $("#fCrit").value = a.criticity;
        $("#fOwner").value = a.owner;
        $("#fProb").value = String(a.prob);
        $("#fImpact").value = String(a.impact);
        $("#fNotes").value = a.notes || "";

        $("#btnDeleteAsset").style.display = "inline-flex";

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
        const tagsInput = ($("#fTags")?.value || "").trim();
        const tagNamesArray = tagsInput ? tagsInput.split(',').map(t => t.trim()).filter(Boolean) : [];
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
                tags: tagNamesArray
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


    // ========= nav buttons (mock) =========
    function goTo(routeName) {
        // se tu usa route() no blade do menu, aqui é só mock.
        // Ajusta para a tua rota real.
        window.location.href = routeName;
    }

    // ========= GESTÃO DE TAGS (MODAL) =========
    function renderModalTags(tags) {
        const wrap = $("#mTagsWrap");
        if (!wrap) return;

        if (!tags || tags.length === 0) {
            wrap.innerHTML = '<span class="muted" style="font-size:12px;">Sem tags associadas.</span>';
            return;
        }

        wrap.innerHTML = tags.map(t => {
            const name = typeof t === 'object' ? t.name : t;
            const id = typeof t === 'object' ? (t.id || t.id_tag) : null;
            const color = (typeof t === 'object' && t.color) ? t.color : '#60a5fa';

            return `<span style="display:inline-flex; align-items:center; gap:6px; padding: 3px 8px; border-radius: 6px; background: ${color}15; color: ${color}; font-size: 11px; border: 1px solid ${color}30; font-weight:600;">
                ${name}
                ${id ? `<svg class="remove-tag-btn" data-tag-id="${id}" style="width:12px;height:12px;cursor:pointer;opacity:0.6;transition:opacity 0.2s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>` : ''}
            </span>`;
        }).join('');
    }

    async function addTagToAsset() {
        const input = $("#mNewTagInput");
        if (!input || !currentAssetId) return;

        const val = input.value.trim();
        if (!val) return;

        const btn = $("#btnAddTagBtn");
        const ogText = btn.innerHTML;
        btn.innerHTML = "...";
        btn.disabled = true; input.disabled = true;

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
            const r = await fetch(`/api/assets/${currentAssetId}/tags`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf || '', 'Accept': 'application/json' },
                body: JSON.stringify({ tag_names: [val] })
            });

            const data = await r.json();

            if (r.ok) {
                input.value = "";
                $("#mTagSuggestions").style.display = "none";

                const a = assets.find(x => String(x.id) === String(currentAssetId));
                if (a) {
                    a.tags = data.tags.map(t => ({ id: t.id_tag || t.id, name: t.name, color: t.color }));
                    renderModalTags(a.tags);
                    renderAssetsTable();
                }
                loadDbTags();
            } else {
                alert(data.message || 'Erro ao adicionar tag');
            }
        } catch (e) {
            alert('Erro de conexão: ' + e.message);
        } finally {
            btn.innerHTML = ogText;
            btn.disabled = false; input.disabled = false;
            input.focus(); // Devolve o cursor para adicionar mais rapidamente
        }
    }

    // ========= AUTOCOMPLETE DE TAGS =========
    function setupTagAutocomplete() {
        const input = $("#mNewTagInput");
        const suggBox = $("#mTagSuggestions");
        if (!input || !suggBox) return;

        const handleSuggest = () => {
            const val = input.value.toLowerCase().trim();

            if (ALL_DB_TAGS.length === 0) {
                suggBox.style.display = "none";
                return;
            }

            const currentAsset = assets.find(x => String(x.id) === String(currentAssetId));
            const currentTagNames = currentAsset && currentAsset.tags
                ? currentAsset.tags.map(t => (typeof t === 'object' ? t.name : String(t)).toLowerCase())
                : [];

            // Remove as tags que o ativo já tem
            let matches = ALL_DB_TAGS.filter(t => !currentTagNames.includes(t.name.toLowerCase()));

            // Se o utilizador escreveu algo, filtra. Se não, mostra as disponíveis!
            if (val) {
                matches = matches.filter(t => t.name.toLowerCase().includes(val));
            }

            if (matches.length === 0) {
                suggBox.style.display = "none";
                return;
            }

            suggBox.innerHTML = matches.map(t => `
                <div class="tag-sugg-item" data-name="${t.name}">
                    <span style="width:8px;height:8px;border-radius:50%;background:${t.color || '#60a5fa'}; box-shadow: 0 0 4px ${t.color || '#60a5fa'}80;"></span>
                    <span style="color:var(--text); font-weight:500;">${t.name}</span>
                </div>
            `).join('');

            suggBox.style.display = "block";
        };

        // Dispara ao escrever, ao clicar e ao focar na caixa!
        input.addEventListener("input", handleSuggest);
        input.addEventListener("focus", handleSuggest);
        input.addEventListener("click", handleSuggest);

        // Quando clica numa sugestão
        suggBox.addEventListener("click", (e) => {
            const item = e.target.closest('.tag-sugg-item');
            if (!item) return;

            input.value = item.dataset.name;
            suggBox.style.display = "none";
            addTagToAsset(); // Adiciona automaticamente ao clicar
        });

        // Fechar a caixa se clicar noutro sítio qualquer do ecrã
        document.addEventListener("click", (e) => {
            if (!input.contains(e.target) && !suggBox.contains(e.target)) {
                suggBox.style.display = "none";
            }
        });
    }

    // ========= init =========

    function init() {
        // Render inicial com dados mock enquanto o fetch não termina
        renderAssetsTable();
        loadDocumentsData();
        loadAssetsFromDB();
        loadDbTags();
        setupTagAutocomplete();

        // 🛡️ HELPER À PROVA DE BALA (TEM DE ESTAR ANTES DE SER USADO!)
        const bindClick = (sel, fn) => { const el = $(sel); if (el) el.addEventListener("click", fn); };
        const bindChange = (sel, fn) => { const el = $(sel); if (el) el.addEventListener("change", fn); };
        const bindInput = (sel, fn) => { const el = $(sel); if (el) el.addEventListener("input", fn); };

        // Ligar o botão da nova IA
        bindClick("#btnGenerateAiAnalysis", generateAiAnalysis);

        // Filtros da tabela
        bindInput("#assetSearch", renderAssetsTable);
        bindChange("#critFilter", renderAssetsTable);
        bindChange("#typeFilter", renderAssetsTable);
        bindChange("#tagFilter", renderAssetsTable);
        bindChange("#controlStatusFilter", renderAssetsTable);
        bindChange("#sourceFilter", renderAssetsTable);

        // Botões de Ação Principais
        bindClick("#btnSyncWazuh", async () => {
            const btn = $("#btnSyncWazuh");
            const originalHtml = btn.innerHTML;
            btn.innerHTML = `<span class="spinner" style="width:14px;height:14px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:spin 1s linear infinite;display:inline-block;"></span> A sincronizar...`;
            btn.disabled = true;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
                const res = await fetch('/api/assets/sync-wazuh', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' }
                });

                const data = await res.json();

                if (res.ok) {
                    alert(`✅ Wazuh sincronizado com sucesso!\n${data.synced_count} ativos processados.`);
                    loadAssetsFromDB();
                } else {
                    alert(`❌ Erro ${res.status} ao sincronizar:\n\n${data.message || "Erro desconhecido"}\n${data.error || ""}`);
                }
            } catch (err) {
                alert(`❌ Erro de conexão ao sincronizar Wazuh.\n\nVerifica se a API está acessível.\nErro: ${err.message}`);
            } finally {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });

        bindClick("#btnOpenCreateAsset", openCreateAsset);
        bindClick("#btnEditAsset", () => { if (currentAssetId) openEditAsset(currentAssetId); });
        bindClick("#btnCreateRiskFromAssetTab", () => { if (currentAssetId) goTo(`/riscos?new_risk_for=${currentAssetId}`); });

        // Fechar Modais
        bindClick("#assetModalClose", () => closeModal("#assetModal"));
        bindClick("#assetEditClose", () => closeModal("#assetEditModal"));

        // ── GESTÃO DE TAGS ──
        bindClick("#btnAddTagBtn", addTagToAsset);

        const tagInp = $("#mNewTagInput");
        if (tagInp) {
            tagInp.addEventListener("keydown", (e) => {
                if (e.key === "Enter") { e.preventDefault(); addTagToAsset(); }
            });
        }

        // Lógica para apagar a tag quando se clica no Xzinho
        bindClick("#mTagsWrap", async (e) => {
            const btn = e.target.closest('.remove-tag-btn');
            if (!btn || !currentAssetId) return;

            const tagId = btn.dataset.tagId;
            btn.style.opacity = "0.2";

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
                const r = await fetch(`/api/assets/${currentAssetId}/tags/${tagId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf || '', 'Accept': 'application/json' }
                });

                if (r.ok) {
                    const a = assets.find(x => String(x.id) === String(currentAssetId));
                    if (a) {
                        a.tags = a.tags.filter(t => String(t.id) !== String(tagId));
                        renderModalTags(a.tags);
                        renderAssetsTable();
                    }
                } else { alert('Erro ao remover tag'); btn.style.opacity = "0.6"; }
            } catch (err) { alert('Erro de conexão'); btn.style.opacity = "0.6"; }
        });

        // Fechar clicando fora da caixa ou no ESC
        const mAsset = $("#assetModal");
        if (mAsset) mAsset.addEventListener("click", (e) => { if (e.target === e.currentTarget) closeModal("#assetModal"); });

        const mEdit = $("#assetEditModal");
        if (mEdit) mEdit.addEventListener("click", (e) => { if (e.target.id === "assetEditModal") closeModal("#assetEditModal"); });

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                closeModal("#assetModal");
                closeModal("#assetEditModal");
            }
        });

        // Eventos gerais dos formulários
        bindChange("#fType", () => toggleIpFieldByType($("#fType")?.value));
        bindClick("#btnSaveAsset", saveAssetFromForm);
        bindClick("#btnDeleteAsset", deleteAssetMock);

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
