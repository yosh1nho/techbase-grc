// public/js/pages/assets.js
(() => {
    // ========= helpers =========
    const $ = (s) => document.querySelector(s);
    const $$ = (s) => Array.from(document.querySelectorAll(s));

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
        m.classList.add("open");
        m.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
    }

    function closeModal(id) {
        const m = $(id);
        if (!m) return;
        m.classList.remove("open");
        m.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
    }

    function normalize(s) {
        return (s || "").toLowerCase().trim();
    }

    function updateDiffChip(declaredStatus, confidence, diffChipEl) {
        if (!diffChipEl) return;
        const suggested = iaStatusFromConfidence(Number(confidence));
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
            owner: "TI • João",
            createdBy: "rita (admin)",
            notes: "Servidor de base de dados de produção. Monitorizado por Wazuh.",
            prob: 4,
            impact: 4,
            controls: [
                { key: "ID.GA-1", status: "PARTIAL", confidence: 0.72, note: "Inventário existe, mas sem periodicidade formal.", evidences: ["Procedimento Inventário v1.0"] },
                { key: "PR.IP-4", status: "GAP", confidence: 0.55, note: "Backups sem evidência de testes.", evidences: ["Relatório Backups (Jan)"] }
            ]
        },
        {
            id: "A2",
            name: "APP-GRC",
            subtitle: "Laravel • Web",
            type: "Aplicação",
            criticity: "Alto",
            owner: "SecOps • Ana",
            createdBy: "ana",
            notes: "Aplicação de governança. Evidências e controlos centralizados.",
            prob: 3,
            impact: 2,
            controls: [
                { key: "PR.AC-1", status: "PARTIAL", confidence: 0.61, note: "Existe login, mas falta revisão periódica.", evidences: [] },
                { key: "ID.AR-1", status: "COVERED", confidence: 0.82, note: "Há processo de risco e registos.", evidences: ["Matriz de Risco v2026"] }
            ]
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

    // ========= computed summary =========
    function summarizeControls(asset) {
        const total = asset.controls.length || 0;
        const gap = asset.controls.filter((c) => c.status === "GAP").length;
        const partial = asset.controls.filter((c) => c.status === "PARTIAL").length;
        const covered = asset.controls.filter((c) => c.status === "COVERED").length;
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

        return assets.filter((a) => {
            const text = normalize(`${a.name} ${a.subtitle} ${a.type} ${a.owner} ${a.notes}`);
            const matchesQ = !q || text.includes(q);
            const matchesCrit = crit === "all" || a.criticity === crit;
            const matchesType = type === "all" || a.type === type;

            let matchesControl = true;
            if (st !== "all") {
                matchesControl = a.controls.some((c) => c.status === st);
            }

            return matchesQ && matchesCrit && matchesType && matchesControl;
        });
    }

    function renderAssetsTable() {
        const tbody = $("#assetsTbody");
        tbody.innerHTML = "";

        const filtered = filterAssets();
        globalKpis(filtered);

        filtered.forEach((a) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
        <td>
          <b>${a.name}</b>
          <div class="muted">${a.subtitle}</div>
        </td>
        <td>${a.type}</td>
        <td>${criticityTag(a.criticity)}</td>
        <td>${a.owner}</td>
        <td class="muted">P=${a.prob} • I=${a.impact}</td>
        <td>${complianceTag(a)}</td>
        <td>
          <button class="btn" type="button" data-open-asset="${a.id}">Ver detalhes</button>
        </td>
      `;
            tbody.appendChild(tr);
        });

        $$("[data-open-asset]").forEach((b) =>
            b.addEventListener("click", () => openAssetModal(b.dataset.openAsset))
        );
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
                c.status === "GAP" ? "st-gap" :
                    c.status === "PARTIAL" ? "st-partial" :
                        "st-covered";

            const sug = iaStatusFromConfidence(Number(c.confidence ?? 0));
            const diff = (c.status !== sug);
            const sugClass = (sug === "COVERED" ? "ok" : (sug === "PARTIAL" ? "warn" : "bad"));

            const evidences = (c.evidences || []).map(e => `<span class="chip">${e}</span>`).join(" ");

            const row = document.createElement("div");
            row.className = "control-row";
            row.innerHTML = `
        <div class="control-left">
          <div class="control-title">
            <span class="control-code">${c.key}</span>
            <span class="status-pill ${statusClass}">${c.status}</span>
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
            <option value="GAP" ${c.status === "GAP" ? "selected" : ""}>GAP</option>
            <option value="PARTIAL" ${c.status === "PARTIAL" ? "selected" : ""}>PARTIAL</option>
            <option value="COVERED" ${c.status === "COVERED" ? "selected" : ""}>COVERED</option>
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
                a.controls[idx].status = sel.value;
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
            if (c.status === "GAP") {
                badge = "LACUNA";
                text = "O ativo menciona o tema, mas não há evidência/procedimento suficiente. Sugestão: criar evidência e ligar documento.";
            } else if (c.status === "PARTIAL") {
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
          Confiança (mock): <b>${(c.confidence ?? 0).toFixed(2)}</b> • Status atual: <b>${c.status}</b>
        </div>
      `;
            box.appendChild(item);
        });

        if (!asset.controls.length) {
            box.innerHTML = `<div class="muted">Sem controlos associados — a IA ainda não tem base para sugerir melhorias.</div>`;
        }
    }

    function openAssetModal(assetId) {
        const asset = assets.find((a) => a.id === assetId);
        if (!asset) return;

        currentAssetId = assetId;

        $("#assetModalTitle").textContent = asset.name;
        $("#mType").textContent = asset.type;
        $("#mOwner").textContent = asset.owner;
        $("#mCrit").textContent = asset.criticity;
        $("#mCreatedBy").textContent = asset.createdBy || "—";
        $("#mNotes").textContent = asset.notes || "—";

        $("#mProb").textContent = String(asset.prob);
        $("#mImpact").textContent = String(asset.impact);

        const score = asset.prob * asset.impact;
        const cls = classify(score);
        $("#mClassChip").textContent = `Classe: ${cls.label} (${score})`;

        buildMatrix(asset.prob, asset.impact);
        renderControlsList(asset);
        renderAiSuggestions(asset);

        openModal("#assetModal");
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
            const diff = (c.status !== sug);
            const sugClass = (sug === "COVERED" ? "ok" : (sug === "PARTIAL" ? "warn" : "bad"));
            row.className = "control-row";
            row.innerHTML = `
        <div class="control-left">
          <div class="control-title">
            <span class="control-code">${c.key}</span>
            <span class="status-pill ${c.status === "GAP" ? "st-gap" : c.status === "PARTIAL" ? "st-partial" : "st-covered"}">${c.status}</span>
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
        $("#fCrit").value = "Médio";
        $("#fOwner").value = "";
        $("#fProb").value = "3";
        $("#fImpact").value = "3";
        $("#fNotes").value = "";

        $("#btnDeleteAsset").style.display = "none";

        // defaults do bloco de controlos
        $("#fControlPick").value = "ID.GA-1";
        $("#fControlConfidence").value = "0.55";
        syncControlInfo($("#fControlPick"), $("#fControlInfo"), $("#fControlInfoText"));
        applyStatusGuard($("#fControlStatus"), $("#fStatusHint"), Number($("#fControlConfidence").value), $("#fAiSuggestChip"));
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
        $("#fCrit").value = a.criticity;
        $("#fOwner").value = a.owner;
        $("#fProb").value = String(a.prob);
        $("#fImpact").value = String(a.impact);
        $("#fNotes").value = a.notes || "";

        $("#btnDeleteAsset").style.display = "inline-flex";

        // defaults do bloco de controlos
        $("#fControlPick").value = "ID.GA-1";
        $("#fControlConfidence").value = "0.55";
        syncControlInfo($("#fControlPick"), $("#fControlInfo"), $("#fControlInfoText"));
        applyStatusGuard($("#fControlStatus"), $("#fStatusHint"), Number($("#fControlConfidence").value), $("#fAiSuggestChip"));
        $("#fControlNote").value = "";

        resetCreateControlsPreview();

        openModal("#assetEditModal");
    }

    function saveAssetFromForm() {
        const name = $("#fName").value.trim();
        if (!name) return alert("Nome é obrigatório.");

        const type = $("#fType").value;
        const criticity = $("#fCrit").value;
        const owner = $("#fOwner").value.trim() || "—";
        const prob = Number($("#fProb").value);
        const impact = Number($("#fImpact").value);
        const notes = $("#fNotes").value.trim();

        if (!editingAssetId) {
            const id = "A" + (Math.floor(Math.random() * 9000) + 1000);
            const subtitle = `${type} • (novo)`;

            const newAsset = {
                id,
                name,
                subtitle,
                type,
                criticity,
                owner,
                createdBy: "mock-user",
                notes,
                prob,
                impact,
                controls: createControlsWorking.map(c => ({
                    key: c.key,
                    status: c.status,
                    confidence: 0.55,
                    note: c.note || "",
                    evidences: []
                }))
            };

            assets.unshift(newAsset);
        } else {
            const a = assets.find(x => x.id === editingAssetId);
            a.name = name;
            a.type = type;
            a.criticity = criticity;
            a.owner = owner;
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
        const confidence = Number($("#fControlConfidence").value || 0.55);
        const status = canOverrideStatus() ? $("#fControlStatus").value : iaStatusFromConfidence(confidence);
        const note = $("#fControlNote").value.trim();
const exists = createControlsWorking.some(c => c.key === key);
        if (exists) return alert("Este controlo já foi adicionado (mock).");

        createControlsWorking.unshift({ key, status, confidence, note });
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
        $("#acConfidence").value = "0.55";
        // default: status declarado começa igual à sugestão IA
        const s0 = iaStatusFromConfidence(Number($("#acConfidence").value));
        $("#acStatus").value = s0;
        syncControlInfo($("#acControl"), $("#acControlInfo"), $("#acControlInfoText"));
        // status: IA sugere por confiança (somente GRC/Admin pode alterar)
        applyStatusGuard($("#acStatus"), $("#acStatusHint"), Number($("#acConfidence").value), $("#acAiSuggestChip"));
                updateDiffChip($("#acStatus").value, Number($("#acConfidence").value), $("#acAiDiffChip"));

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

        const confidence = Number($("#acConfidence").value);
        const status = canOverrideStatus() ? $("#acStatus").value : iaStatusFromConfidence(confidence);
        const note = $("#acNote").value.trim();

        a.controls.unshift({ key, status, confidence, note, evidences: [] });

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

    // ========= init =========
    function init() {
        // table
        renderAssetsTable();

        // filters
        $("#assetSearch").addEventListener("input", renderAssetsTable);
        $("#critFilter").addEventListener("change", renderAssetsTable);
        $("#typeFilter").addEventListener("change", renderAssetsTable);
        $("#controlStatusFilter").addEventListener("change", renderAssetsTable);

        // open create asset
        $("#btnOpenCreateAsset").addEventListener("click", openCreateAsset);

        // close modals
        $("#assetModalClose").addEventListener("click", () => closeModal("#assetModal"));
        $("#assetEditClose").addEventListener("click", () => closeModal("#assetEditModal"));
        $("#addControlClose").addEventListener("click", () => closeModal("#addControlModal"));

        // click outside closes
        $("#assetModal").addEventListener("click", (e) => { if (e.target.id === "assetModal") closeModal("#assetModal"); });
        $("#assetEditModal").addEventListener("click", (e) => { if (e.target.id === "assetEditModal") closeModal("#assetEditModal"); });
        $("#addControlModal").addEventListener("click", (e) => { if (e.target.id === "addControlModal") closeModal("#addControlModal"); });

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                closeModal("#assetModal");
                closeModal("#assetEditModal");
                closeModal("#addControlModal");
            }
        });

        // edit button inside details modal
        $("#btnEditAsset").addEventListener("click", () => {
            if (!currentAssetId) return;
            openEditAsset(currentAssetId);
        });

        // “associar controlo” inside details modal
        $("#btnAddControlToAsset").addEventListener("click", () => {
            if (!currentAssetId) return;
            openAddControlModal(currentAssetId);
        });

        $("#btnAddControlConfirm").addEventListener("click", addControlConfirm);
        $("#btnAddControlCancel").addEventListener("click", () => closeModal("#addControlModal"));

        // create/edit modal save/delete
        $("#btnSaveAsset").addEventListener("click", saveAssetFromForm);
        $("#btnDeleteAsset").addEventListener("click", deleteAssetMock);

        // add control in create modal
        $("#btnAddControlInline").addEventListener("click", addControlToCreatePreview);


        // RBAC guard + tooltips dos controlos
        if ($("#fControlPick")) {
            $("#fControlPick").addEventListener("change", () => {
                syncControlInfo($("#fControlPick"), $("#fControlInfo"), $("#fControlInfoText"));
            });
        }

        if ($("#fControlConfidence")) {
            $("#fControlConfidence").addEventListener("change", () => {
                applyStatusGuard($("#fControlStatus"), $("#fStatusHint"), Number($("#fControlConfidence").value), $("#fAiSuggestChip"));
            });
        }

        if ($("#fControlStatus")) {
            // aplicar guard na primeira carga (caso o modal já esteja aberto)
            applyStatusGuard($("#fControlStatus"), $("#fStatusHint"), Number($("#fControlConfidence")?.value || 0.55), $("#fAiSuggestChip"));
        }

        if ($("#acControl")) {
            $("#acControl").addEventListener("change", () => {
                syncControlInfo($("#acControl"), $("#acControlInfo"), $("#acControlInfoText"));
            });
        }

        if ($("#acConfidence")) {
            $("#acConfidence").addEventListener("change", () => {
                applyStatusGuard($("#acStatus"), $("#acStatusHint"), Number($("#acConfidence").value), $("#acAiSuggestChip"));
                updateDiffChip($("#acStatus").value, Number($("#acConfidence").value), $("#acAiDiffChip"));
            });
        }

        if ($("#acStatus")) {
            $("#acStatus").addEventListener("change", () => {
                updateDiffChip($("#acStatus").value, Number($("#acConfidence")?.value || 0.55), $("#acAiDiffChip"));
            });

            applyStatusGuard($("#acStatus"), $("#acStatusHint"), Number($("#acConfidence")?.value || 0.55), $("#acAiSuggestChip"));
            updateDiffChip($("#acStatus")?.value, Number($("#acConfidence")?.value || 0.55), $("#acAiDiffChip"));
        }

        // nav mock buttons
        $("#btnGoRisks").addEventListener("click", () => goTo("/riscos"));
        $("#btnGoDocs").addEventListener("click", () => goTo("/documentos"));
        $("#btnGoAssessments").addEventListener("click", () => goTo("/avaliacoes"));
    }

    document.addEventListener("DOMContentLoaded", init);
})();
