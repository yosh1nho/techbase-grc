(() => {
  const $ = (s, root = document) => root.querySelector(s);

  function initAssessmentsPage() {
    const root = document.getElementById('page-assessments');
    if (!root) return false;
    if (root.dataset.init === '1') return true;
    root.dataset.init = '1';

    // ──────────────────────────────────────────────────────────
    // ──────────────────────────────────────────────────────────
    // Data
    // ──────────────────────────────────────────────────────────
    let ASSETS = [];

    async function loadAssets() {
      try {
        const res = await fetch('/api/assets');
        if (!res.ok) return;
        const data = await res.json();
        
        const getIcon = (type) => {
          const t = String(type || '').toLowerCase();
          if (t.includes('servidor') || t.includes('server') || t.includes('host') || t.includes('vm')) return '<i data-lucide="server"></i>';
          if (t.includes('rede') || t.includes('network') || t.includes('fw') || t.includes('router')) return '<i data-lucide="shield"></i>';
          if (t.includes('app') || t.includes('web')) return '<i data-lucide="globe"></i>';
          if (t.includes('bd') || t.includes('db') || t.includes('database')) return '<i data-lucide="database"></i>';
          return '<i data-lucide="box"></i>';
        };

        ASSETS = data.map(a => ({
          id: String(a.id_asset),
          name: a.display_name || a.hostname || 'Ativo Desconhecido',
          subtitle: `${a.type || 'Desconhecido'} • ${a.ip || 'Sem IP'}`,
          type: a.type || 'Desconhecido',
          owner: a.source === 'acronis' ? 'Acronis Sync' : 'Manual',
          icon: getIcon(a.type)
        }));
      } catch (err) {
        console.error('Erro a carregar ativos:', err);
      }
    }
    
    // Iniciar o carregamento
    loadAssets();

    const HISTORY = [
      {
        id: 'AS-2026Q1-SRV-DB-01', period: 'Q1 2026',
        scope: 'Por ativo: SRV-DB-01', frameworks: ['QNRCS v2.1', 'NIS2'],
        maturity: 62, closedAt: '2026-02-02',
        ai: { maturityByDomain: [
          { domain: 'Governança',    value: 70 },
          { domain: 'Identificação', value: 58 },
          { domain: 'Proteção',      value: 61 },
          { domain: 'Resposta',      value: 49 },
        ]},
      },
      {
        id: 'AS-2025Q4-SRV-DB-01', period: 'Q4 2025',
        scope: 'Por ativo: SRV-DB-01', frameworks: ['QNRCS v2.1'],
        maturity: 54, closedAt: '2025-11-20',
      },
    ];

    const CONTROL_DETAILS = {
      'ID.GA-1': {
        title: 'ID.GA-1 • Inventário atualizado',
        subtitle: 'QNRCS v2.1 • Q1 2026 • SRV-DB-01',
        statusHtml: '<span class="st warn"><span class="dot"></span>PARTIAL</span>',
        aiConfidence: '0.72', aiLevel: 'médio',
        frameworkCross: 'QNRCS ID.GA-1 + NIS2 (gestão de ativos / inventário)',
        evidenceUsed: 'Procedimento v1.0 • Export inventário (CSV) • Screenshot CMDB',
        rationale: 'Foi identificado um processo documentado e evidências de execução, mas falta prova periódica/recorrente (ex.: registo mensal/trimestral) para fechar a maturidade.',
        risks: 'R-12: Ativos não inventariados (P×I = 12) • R-03: Shadow IT',
        alerts: 'Wazuh: Novo host não registado (últimos 30 dias) • Acronis: Endpoint sem agente',
        treatments: 'PT-07: Automatizar discovery/CMDB • PT-02: Rotina de revisão trimestral',
      },
      'ID.AR-1': {
        title: 'ID.AR-1 • Análise de risco anual',
        subtitle: 'QNRCS v2.1 • Q1 2026 • SRV-DB-01',
        statusHtml: '<span class="st bad"><span class="dot"></span>GAP</span>',
        aiConfidence: '0.66', aiLevel: 'médio',
        frameworkCross: 'QNRCS ID.AR-1 + NIS2 (risk management)',
        evidenceUsed: '—',
        rationale: 'Não foram encontradas evidências formais suficientes para comprovar execução periódica da análise.',
        risks: 'R-01: Falha de gestão de risco (P×I = 16)',
        alerts: '—', treatments: '—',
      },
      'PR.IP-4': {
        title: 'PR.IP-4 • Backups e testes',
        subtitle: 'QNRCS v2.1 • Q1 2026 • SRV-DB-01',
        statusHtml: '<span class="st ok"><span class="dot"></span>COVERED</span>',
        aiConfidence: '0.90', aiLevel: 'alto',
        frameworkCross: 'QNRCS PR.IP-4 + ISO 27001 (backups) + NIS2 (resiliência)',
        evidenceUsed: 'Relatório Jan/2026 • Política de backups • Evidência de restore test',
        rationale: 'Evidências consistentes e atuais: política + relatório + teste de restauração. Cobertura alinhada com os requisitos.',
        risks: 'R-09: Perda de dados (P×I = 15)',
        alerts: 'Acronis: Backup falhado (resolvido) • Wazuh: Falha de escrita em volume',
        treatments: 'PT-03: Monitorizar falhas de backup • PT-05: Teste de restore mensal',
      },
    };

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────
    function matColor(v) {
      if (v >= 70) return 'var(--ok)';
      if (v >= 50) return 'var(--warn)';
      return 'var(--bad)';
    }

    // ──────────────────────────────────────────────────────────
    // Scope toggle
    // ──────────────────────────────────────────────────────────
    const scopeRow    = $('#scopeRow', root);
    const scopeHidden = $('#scopeSelect', root);
    const assetField  = $('#assetField', root);

    function setScope(scope) {
      if (scopeHidden) scopeHidden.value = scope;
      scopeRow?.querySelectorAll('[data-scope]')?.forEach(btn => {
        const active = btn.getAttribute('data-scope') === scope;
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
      if (assetField) assetField.style.opacity = scope === 'asset' ? '1' : '.6';
    }

    scopeRow?.addEventListener('click', e => {
      const btn = e.target?.closest?.('[data-scope]');
      if (btn) setScope(btn.getAttribute('data-scope'));
    });
    setScope(scopeHidden?.value || 'org');

    // ──────────────────────────────────────────────────────────
    // Asset search dropdown
    // ──────────────────────────────────────────────────────────
    const input      = $('#assetSearch', root);
    const dd         = $('#assetDropdown', root);
    const selectedId = $('#assetSelectedId', root);
    const norm = v => String(v ?? '').toLowerCase();

    function filterAssets(q) {
      const t = norm(q).trim();
      if (!t) return ASSETS.slice(0, 8);
      return ASSETS.filter(a =>
        `${a.id} ${a.name} ${a.subtitle} ${a.type} ${a.owner}`.toLowerCase().includes(t)
      ).slice(0, 12);
    }

    function renderDropdown(items) {
      if (!dd) return;
      if (!items.length) {
        dd.innerHTML = `<div class="muted" style="padding:10px 12px;font-size:13px;">Sem resultados.</div>`;
        dd.style.display = 'block'; return;
      }
      dd.innerHTML = items.map(a => `
        <button type="button" class="asset-opt" data-asset-id="${a.id}">
          <div class="aopt-icon">${a.icon}</div>
          <div>
            <div class="aopt-name">${a.name} <span style="color:var(--muted);font-size:11px;font-weight:400;">${a.type}</span></div>
            <div class="aopt-sub">${a.subtitle} · ${a.owner}</div>
          </div>
        </button>`).join('');
      dd.style.display = 'block';
      if (window.lucide) window.lucide.createIcons();

      dd.querySelectorAll('[data-asset-id]').forEach(b => {
        b.addEventListener('click', () => {
          const a = ASSETS.find(x => x.id === b.dataset.assetId);
          if (!a) return;
          if (selectedId) selectedId.value = a.id;
          if (input) input.value = a.name;
          dd.style.display = 'none';
          setScope('asset');
        });
      });
    }

    if (input && dd) {
      input.addEventListener('focus', () => renderDropdown(filterAssets(input.value)));
      input.addEventListener('input', () => renderDropdown(filterAssets(input.value)));
      document.addEventListener('click', e => {
        if (!dd.contains(e.target) && !input.contains(e.target)) dd.style.display = 'none';
      });
    }

    // ──────────────────────────────────────────────────────────
    // Framework chips
    // ──────────────────────────────────────────────────────────
    const fwChips   = $('#fwChips', root);
    const fwHidden  = $('#frameworksSelected', root);
    const fwSummary = $('#fwSummary', root);

    function getSelectedFW() {
      return Array.from(fwChips?.querySelectorAll('[data-fw]') ?? [])
        .filter(b => b.getAttribute('aria-pressed') === 'true')
        .map(b => b.getAttribute('data-fw'));
    }

    function syncFW() {
      const sel = getSelectedFW();
      if (fwHidden)  fwHidden.value = sel.join(', ');
      if (fwSummary) fwSummary.innerHTML = sel.length
        ? `Selecionados: <b>${sel.join(' + ')}</b>`
        : `Selecionados: <b>—</b>`;
    }

    fwChips?.addEventListener('click', e => {
      const btn = e.target?.closest?.('[data-fw]');
      if (!btn) return;
      const was = btn.getAttribute('aria-pressed') === 'true';
      btn.setAttribute('aria-pressed', was ? 'false' : 'true');
      if (!getSelectedFW().length) btn.setAttribute('aria-pressed', 'true');
      syncFW();
    });
    syncFW();

    // ──────────────────────────────────────────────────────────
    // History table
    // ──────────────────────────────────────────────────────────
    function renderHistory() {
      const tbody = document.getElementById('historyRows');
      if (!tbody) return;
      tbody.innerHTML = HISTORY.map((a, i) => {
        const prev  = HISTORY[i + 1];
        const delta = prev ? a.maturity - prev.maturity : null;
        const trend = delta !== null
          ? `<span class="trend ${delta >= 0 ? 'up' : 'down'}">${delta >= 0 ? '▲' : '▼'} ${Math.abs(delta)}%</span>`
          : `<span style="color:var(--muted);font-size:11px;">—</span>`;

        return `<tr>
          <td><b style="font-family:var(--font-mono,monospace);font-size:13px;">${a.period}</b></td>
          <td class="muted">${a.scope}</td>
          <td class="muted">${a.frameworks.join(' + ')}</td>
          <td>
            <div class="mat-cell">
              <div class="mat-bar"><div class="mat-fill" style="width:${a.maturity}%;background:${matColor(a.maturity)};"></div></div>
              <span class="mat-pct">${a.maturity}%</span>
            </div>
          </td>
          <td>${trend}</td>
          <td class="muted" style="font-family:var(--font-mono,monospace);font-size:12px;">${a.closedAt ?? '—'}</td>
          <td style="text-align:right;">
            <button type="button" class="btn" style="padding:5px 11px;font-size:12px;" data-open-assessment="${a.id}">Detalhes</button>
          </td>
        </tr>`;
      }).join('');
    }

    // ──────────────────────────────────────────────────────────
    // Modal
    // ──────────────────────────────────────────────────────────
    const modal      = document.getElementById('assessmentDetailsModal');
    const modalClose = document.getElementById('assessmentDetailsClose');

    function openModal(p) {
      if (!modal) return;
      modal.style.display = 'flex';
      modal.setAttribute('aria-hidden', 'false');

      document.getElementById('assessmentDetailsTitle').textContent    = p.title;
      document.getElementById('assessmentDetailsSubtitle').textContent = p.subtitle;
      document.getElementById('detStatus').innerHTML     = p.statusHtml ?? '—';
      document.getElementById('detFrameworkCross').textContent = p.frameworkCross ?? '—';
      document.getElementById('detEvidenceUsed').textContent  = p.evidenceUsed ?? '—';
      document.getElementById('detRationale').textContent     = p.rationale ?? '—';
      document.getElementById('detRisks').textContent         = p.risks ?? '—';
      document.getElementById('detAlerts').textContent        = p.alerts ?? '—';
      document.getElementById('detTreatments').textContent    = p.treatments ?? '—';

      // Confidence pill
      const confEl = document.getElementById('detAiConfidence');
      if (p.aiConfidence && p.aiConfidence !== '—') {
        confEl.innerHTML = `<span class="conf-pill">🤖 ${p.aiConfidence} <span style="color:var(--muted);font-weight:400;">(${p.aiLevel})</span></span>`;
      } else {
        confEl.textContent = '—';
      }

      // Domain bars
      const domSection = document.getElementById('domainSection');
      const domList    = document.getElementById('domainList');
      if (p.domains?.length) {
        domSection.style.display = 'block';
        domList.innerHTML = p.domains.map(d => `
          <div class="dom-row">
            <div class="dom-name">${d.domain}</div>
            <div class="dom-bar"><div class="dom-fill" style="width:${d.value}%;background:${matColor(d.value)};"></div></div>
            <div class="dom-pct">${d.value}%</div>
          </div>`).join('');
      } else {
        domSection.style.display = 'none';
      }
    }

    function closeModal() {
      if (!modal) return;
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
    }

    modalClose?.addEventListener('click', closeModal);
    modal?.addEventListener('click', e => {
      if (!modal.querySelector('.modal-card')?.contains(e.target)) closeModal();
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    // ──────────────────────────────────────────────────────────
    // Event delegation (detalhes controlo + histórico)
    // ──────────────────────────────────────────────────────────
    root.addEventListener('click', e => {
      // Botão "Detalhes" dos controlos
      const detBtn = e.target?.closest?.('[data-open-details]');
      if (detBtn) {
        const key = detBtn.getAttribute('data-open-details');
        const d = CONTROL_DETAILS[key] ?? {};
        openModal({
          title: d.title ?? `Detalhes • ${key}`,
          subtitle: d.subtitle ?? '—',
          statusHtml: d.statusHtml ?? '—',
          aiConfidence: d.aiConfidence,
          aiLevel: d.aiLevel,
          frameworkCross: d.frameworkCross ?? '—',
          evidenceUsed: d.evidenceUsed ?? '—',
          rationale: d.rationale ?? '—',
          risks: d.risks ?? '—',
          alerts: d.alerts ?? '—',
          treatments: d.treatments ?? '—',
        });
        return;
      }

      // Botão "Detalhes" do histórico
      const histBtn = e.target?.closest?.('[data-open-assessment]');
      if (histBtn) {
        const a = HISTORY.find(x => x.id === histBtn.getAttribute('data-open-assessment'));
        if (!a) return;
        openModal({
          title: `Avaliação · ${a.period}`,
          subtitle: `${a.scope} · ${a.frameworks.join(' + ')}`,
          statusHtml: '<span class="st neu"><span class="dot"></span>HISTÓRICO</span>',
          aiConfidence: null,
          frameworkCross: a.frameworks.join(' + ') + ' — controlos carregados e validados por evidências.',
          evidenceUsed: 'Ver evidências em Documentos & Evidências.',
          rationale: `Maturidade calculada por IA: ${a.maturity}%.`,
          risks: 'Riscos associados ao escopo.',
          alerts: 'Alertas Wazuh/Acronis associados ao ativo.',
          treatments: 'Planos de tratamento relacionados.',
          domains: a.ai?.maturityByDomain,
        });
        return;
      }
    });

    // ──────────────────────────────────────────────────────────
    // Botões Iniciar / Fechar / Comparar
    // ──────────────────────────────────────────────────────────
    const btnStart   = document.getElementById('btnStartAssessment');
    const btnClose   = document.getElementById('btnCloseAssessment');
    const btnCompare = document.getElementById('btnCompare');

    btnStart?.addEventListener('click', () => {
      btnStart.textContent = 'Iniciado ✓';
      setTimeout(() => (btnStart.textContent = '▶ Iniciar'), 900);
    });
    btnClose?.addEventListener('click', () => {
      btnClose.textContent = 'Fechado ✓';
      setTimeout(() => (btnClose.textContent = 'Fechar avaliação'), 900);
    });
    btnCompare?.addEventListener('click', () => {
      openModal({
        title: 'Comparação',
        subtitle: 'RF9 · evolução entre avaliações',
        statusHtml: '<span class="st neu"><span class="dot"></span>RF9</span>',
        aiConfidence: null,
        frameworkCross: 'Comparação por período/escopo/framework (mock).',
        evidenceUsed: '—',
        rationale: 'Q4 2025 → 54%  |  Q1 2026 → 62%  (+8pp)',
        risks: '—', alerts: '—', treatments: '—',
      });
    });

    renderHistory();
    return true;
  }

  // ── Boot ──
  function boot() {
    try { initAssessmentsPage(); }
    catch (err) { console.error('[assessments] init error:', err); }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  const obs = new MutationObserver(boot);
  obs.observe(document.documentElement, { childList: true, subtree: true });
})();
