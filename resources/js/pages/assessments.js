(() => {
  const $ = (s, root = document) => root.querySelector(s);

  function initAssessmentsPage() {
    const root = document.getElementById('page-assessments');
    if (!root) return false;
    if (root.dataset.init === '1') return true;
    root.dataset.init = '1';

    // ── Helpers ──────────────────────────────────────────────────────────────
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    async function apiGet(url) {
      const r = await fetch(url, { headers: { Accept: 'application/json' } });
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.json();
    }

    async function apiPost(url, body) {
      const r = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        body: JSON.stringify(body),
      });
      const d = await r.json().catch(() => ({}));
      if (!r.ok) throw new Error(d.error || d.message || `HTTP ${r.status}`);
      return d;
    }

    async function apiPatch(url) {
      const r = await fetch(url, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
      });
      const d = await r.json().catch(() => ({}));
      if (!r.ok) throw new Error(d.error || `HTTP ${r.status}`);
      return d;
    }

    function showToast(msg, type = 'ok') {
      let t = document.getElementById('assessToast');
      if (!t) {
        t = document.createElement('div');
        t.id = 'assessToast';
        t.style.cssText = 'position:fixed;right:20px;bottom:20px;z-index:99999;display:none;min-width:260px;';
        document.body.appendChild(t);
      }
      const bg = { ok: 'rgba(52,211,153,.15)', err: 'rgba(248,113,113,.15)', warn: 'rgba(251,191,36,.15)' };
      t.innerHTML = `<div class="panel" style="border-left:3px solid ${type === 'err' ? '#f87171' : type === 'warn' ? '#fbbf24' : '#34d399'};font-size:13px;">${msg}</div>`;
      t.style.display = '';
      clearTimeout(t._t);
      t._t = setTimeout(() => (t.style.display = 'none'), 4000);
    }

    function matColor(v) {
      if (v >= 70) return 'var(--ok)';
      if (v >= 50) return 'var(--warn)';
      return 'var(--bad)';
    }

    function statusHtml(s) {
      if (s === 'COVERED' || s === 'compliant') return '<span class="st ok"><span class="dot"></span>COVERED</span>';
      if (s === 'PARTIAL' || s === 'partial') return '<span class="st warn"><span class="dot"></span>PARTIAL</span>';
      if (s === 'GAP' || s === 'non_compliant') return '<span class="st bad"><span class="dot"></span>GAP</span>';
      return `<span class="st neu"><span class="dot"></span>${s ?? '—'}</span>`;
    }

    // ── Estado ────────────────────────────────────────────────────────────────
    let ASSETS = [];
    let HISTORY = [];
    let FRAMEWORKS = [];
    let currentAssessmentId = null;

    // ── Carregar dados iniciais ───────────────────────────────────────────────
    async function loadAll() {
      try {
        [ASSETS, HISTORY, FRAMEWORKS] = await Promise.all([
          apiGet('/api/assets'),
          apiGet('/api/assessments'),
          apiGet('/api/compliance'),   // devolve frameworks com grupos/controlos
        ]);

        // Processar ativos para o dropdown
        const getIcon = (type) => {
          const t = String(type || '').toLowerCase();
          if (t.includes('servidor') || t.includes('server')) return '<i data-lucide="server"></i>';
          if (t.includes('rede') || t.includes('network')) return '<i data-lucide="shield"></i>';
          if (t.includes('app') || t.includes('web')) return '<i data-lucide="globe"></i>';
          if (t.includes('db') || t.includes('base')) return '<i data-lucide="database"></i>';
          return '<i data-lucide="box"></i>';
        };

        ASSETS = ASSETS.map(a => ({
          id: String(a.id_asset),
          name: a.display_name || a.hostname || 'Ativo Desconhecido',
          subtitle: `${a.asset_type || a.type || 'Desconhecido'} • ${a.ip_address || a.ip || 'Sem IP'}`,
          type: a.asset_type || a.type || 'Desconhecido',
          icon: getIcon(a.asset_type || a.type),
        }));

        // Frameworks disponíveis para os chips
        FRAMEWORKS = FRAMEWORKS.map(f => ({ id: f.id, name: f.name }));

        renderHistory();
        loadKpis();
        await loadDynamicFrameworks();

        if (window.lucide) window.lucide.createIcons();

      } catch (e) {
        console.error('Erro ao carregar avaliações:', e);
        showToast('Erro ao carregar dados: ' + e.message, 'err');
      }
    }

    // ── KPIs ──────────────────────────────────────────────────────────────────
    async function loadKpis() {
      try {
        const k = await apiGet('/api/assessments/kpis');
        const mat = document.getElementById('kpiMaturity');
        const cov = document.getElementById('kpiCovered');
        const par = document.getElementById('kpiPartial');
        const gap = document.getElementById('kpiGap');
        const sub = document.getElementById('kpiMatSub');

        if (mat) { mat.textContent = k.maturity + '%'; }
        if (cov) cov.textContent = k.covered;
        if (par) par.textContent = k.partial;
        if (gap) gap.textContent = k.gap;
        if (sub) sub.textContent = `${k.period} · ${k.frameworks.join(' + ')}`;

        // Actualizar barras
        ['kpiMatFill', 'kpiCovFill', 'kpiParFill', 'kpiGapFill'].forEach((id, i) => {
          const el = document.getElementById(id);
          if (!el) return;
          const total = (k.covered + k.partial + k.gap) || 1;
          const vals = [
            k.maturity,
            (k.covered / total) * 100,
            (k.partial / total) * 100,
            (k.gap / total) * 100,
          ];
          setTimeout(() => { el.style.width = vals[i] + '%'; }, 100);
        });

        currentAssessmentId = k.assessment_id;

      } catch (e) {
        console.warn('KPIs: sem dados ainda.', e.message);
      }
    }


    // ── Frameworks: popular IDs ocultos ───────────────────────────────────────
    // ── Frameworks Dinâmicas ──────────────────────────────────────────────────
    async function loadDynamicFrameworks() {
      const container = document.getElementById('fwChips');
      const hiddenInput = document.getElementById('frameworksSelected');
      const summaryText = document.getElementById('fwSummary');

      if (!container) return;

      try {
        // Vai buscar as frameworks à BD
        const fws = await apiGet('/api/frameworks-list');

        // Desenhar os botões dinamicamente
        container.innerHTML = fws.map(f => `
                <button type="button" class="fw-chip" data-fw-id="${f.id}" data-fw-name="${f.name}" aria-pressed="false">
                    ${f.name}
                </button>
            `).join('');

        const buttons = container.querySelectorAll('.fw-chip');

        const updateSelection = () => {
          const selectedNames = [];
          const selectedIds = [];

          buttons.forEach(b => {
            if (b.getAttribute('aria-pressed') === 'true') {
              selectedNames.push(b.getAttribute('data-fw-name'));
              selectedIds.push(b.getAttribute('data-fw-id'));
            }
          });

          if (hiddenInput) hiddenInput.value = selectedIds.join(',');

          if (summaryText) {
            summaryText.innerHTML = selectedNames.length
              ? `Selecionados: <b>${selectedNames.join(' • ')}</b>`
              : `Selecionados: <b style="color:var(--warn)">Nenhuma</b>`;
          }
        };

        buttons.forEach(btn => {
          btn.addEventListener('click', () => {
            const isPressed = btn.getAttribute('aria-pressed') === 'true';
            btn.setAttribute('aria-pressed', !isPressed);
            updateSelection();
          });
        });

        // Pré-selecionar a primeira framework automaticamente
        if (buttons.length > 0) {
          buttons[0].setAttribute('aria-pressed', 'true');
          updateSelection();
        }

      } catch (e) {
        console.error("Erro a carregar frameworks:", e);
        container.innerHTML = '<div style="color:#f87171; font-size:12px;">Erro ao carregar frameworks.</div>';
      }
    }


    // ── Asset dropdown ────────────────────────────────────────────────────────
    const input = $('#assetSearch', root);
    const dd = $('#assetDropdown', root);
    const selectedId = $('#assetSelectedId', root);

    function filterAssets(q) {
      const t = (q || '').toLowerCase().trim();
      if (!t) return ASSETS.slice(0, 8);
      return ASSETS.filter(a =>
        `${a.id} ${a.name} ${a.subtitle} ${a.type}`.toLowerCase().includes(t)
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
            <div class="aopt-sub">${a.subtitle}</div>
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

    // ── Framework chips ───────────────────────────────────────────────────────
    const fwChips = $('#fwChips', root);
    const fwHidden = $('#frameworksSelected', root);
    const fwSummary = $('#fwSummary', root);


    fwChips?.addEventListener('click', e => {
      const btn = e.target?.closest?.('[data-fw]');
      if (!btn) return;
      const was = btn.getAttribute('aria-pressed') === 'true';
      btn.setAttribute('aria-pressed', was ? 'false' : 'true');
    });

    // ── Histórico ─────────────────────────────────────────────────────────────
    function renderHistory() {
      const tbody = document.getElementById('historyRows');
      if (!tbody) return;

      if (!HISTORY.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="muted" style="text-align:center;padding:20px;">Sem avaliações registadas. Clica em "▶ Iniciar" para criar a primeira.</td></tr>`;
        return;
      }

      tbody.innerHTML = HISTORY.map((a, i) => {
        const prev = HISTORY[i + 1];
        const delta = prev ? a.maturity - prev.maturity : null;
        const trend = delta !== null
          ? `<span class="trend ${delta >= 0 ? 'up' : 'down'}">${delta >= 0 ? '▲' : '▼'} ${Math.abs(delta)}%</span>`
          : `<span style="color:var(--muted);font-size:11px;">—</span>`;

        const fwStr = (a.frameworks || []).join(' + ') || '—';
        const closedAt = a.closed_at ? a.closed_at.slice(0, 10) : (a.status === 'running' ? '⟳ Em curso' : '—');

        const createdAt = a.created_at ? a.created_at.slice(0, 10) : '—';

        return `<tr>
          <td><b style="font-family:var(--font-mono,monospace);font-size:13px;">${a.period ?? '—'}</b></td>
          <td class="muted" style="font-family:var(--font-mono,monospace);font-size:12px;">${createdAt}</td> <td class="muted">${a.scope ?? '—'}</td>
          <td class="muted">${fwStr}</td>
          <td>
            <div class="mat-cell">
              <div class="mat-bar"><div class="mat-fill" style="width:${a.maturity}%;background:${matColor(a.maturity)};"></div></div>
              <span class="mat-pct">${a.maturity}%</span>
            </div>
          </td>
          <td>${trend}</td>
          <td class="muted" style="font-family:var(--font-mono,monospace);font-size:12px;">${closedAt}</td>
          <td style="text-align:right;">
            <button type="button" class="btn" style="padding:5px 11px;font-size:12px;" data-open-assessment="${a.id}">Detalhes</button>
          </td>
        </tr>`;
      }).join('');
    }

    // ── Modal ─────────────────────────────────────────────────────────────────
    const modal = document.getElementById('assessmentDetailsModal');
    const modalClose = document.getElementById('assessmentDetailsClose');
    function openModal(p) {
      if (!modal) return;
      modal.style.display = 'flex';
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';

      document.getElementById('assessmentDetailsTitle').textContent = p.title ?? '—';
      document.getElementById('assessmentDetailsSubtitle').textContent = p.subtitle ?? '—';
      document.getElementById('detStatus').innerHTML = p.statusHtml ?? '—';
      document.getElementById('detFrameworkCross').textContent = p.frameworkCross ?? '—';
      document.getElementById('detEvidenceUsed').textContent = p.evidenceUsed ?? '—';
      document.getElementById('detRationale').textContent = p.rationale ?? '—';
      document.getElementById('detRisks').textContent = p.risks ?? '—';
      document.getElementById('detAlerts').textContent = p.alerts ?? '—';
      document.getElementById('detTreatments').textContent = p.treatments ?? '—';

      // Confiança IA
      const confEl = document.getElementById('detAiConfidence');
      if (confEl) {
        confEl.innerHTML = p.aiConfidence
          ? `<span class="conf-pill">🤖 ${p.aiConfidence} <span style="color:var(--muted);font-weight:400;">(${p.aiLevel ?? ''})</span></span>`
          : '—';
      }

      // Recomendações
      const recEl = document.getElementById('detRecommendations');
      if (recEl) {
        if (p.recommendations?.length) {
          recEl.innerHTML = p.recommendations.map(r => `<div style="padding:4px 0;font-size:13px">• ${r}</div>`).join('');
          recEl.parentElement.style.display = 'block';
        } else {
          recEl.parentElement.style.display = 'none';
        }
      }

      // Análise narrativa completa
      const analysisEl = document.getElementById('detFullAnalysis');
      if (analysisEl) {
        if (p.fullAnalysis) {
          const formatted = p.fullAnalysis
            .replace(/\*\*(.+?)\*\*/g, '<b>$1</b>')
            .replace(/^## (.+)$/gm, '<div style="font-size:13px;font-weight:700;margin:12px 0 4px">$1</div>')
            .replace(/^[•\-] (.+)$/gm, '<div style="padding:2px 0;padding-left:12px">• $1</div>')
            .replace(/\n\n/g, '<br>');
          analysisEl.innerHTML = formatted;
          analysisEl.parentElement.style.display = 'block';
        } else {
          analysisEl.parentElement.style.display = 'none';
        }
      }

      // Domain bars
      const domSection = document.getElementById('domainSection');
      const domList = document.getElementById('domainList');
      if (p.domains?.length && domList && domSection) {
        domSection.style.display = 'block';
        domList.innerHTML = p.domains.map(d => `
          <div class="dom-row">
            <div class="dom-name">${d.domain}</div>
            <div class="dom-bar"><div class="dom-fill" style="width:${d.value}%;background:${matColor(d.value)};"></div></div>
            <div class="dom-pct">${d.value}%</div>
          </div>`).join('');
      } else if (domSection) {
        domSection.style.display = 'none';
      }
    }

    function closeModal() {
      if (!modal) return;
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    modalClose?.addEventListener('click', closeModal);

    modal?.addEventListener('click', e => {
      if (!modal.querySelector('.modal-card')?.contains(e.target)) closeModal();
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });


    // ── Event delegation ──────────────────────────────────────────────────────
    root.addEventListener('click', async e => {
      // Detalhes do histórico → carregar da API
      const histBtn = e.target?.closest?.('[data-open-assessment]');
      if (histBtn) {
        const id = histBtn.getAttribute('data-open-assessment');
        histBtn.textContent = '...';
        histBtn.disabled = true;
        try {
          const data = await apiGet(`/api/assessments/${id}`);
          const result = data.results?.[0] ?? {};

          // Controlar resultados detalhados
          const controls = result.controls ?? [];
          const controlsText = controls.length
            ? controls.map(c => `${c.code} (${c.framework}): ${c.status} — ${c.rationale ?? ''}`).join('\n')
            : '—';

          openModal({
            title: `Avaliação · ${data.period ?? '—'}`,
            subtitle: `${data.scope} · ${(data.frameworks || []).join(' + ')}`,
            statusHtml: statusHtml(result.status ?? data.maturity >= 70 ? 'COVERED' : data.maturity >= 40 ? 'PARTIAL' : 'GAP'),
            aiConfidence: null,
            aiLevel: null,
            frameworkCross: (data.frameworks || []).join(' + '),
            evidenceUsed: 'Ver evidências em Documentos & Evidências.',
            rationale: result.summary ?? `Maturidade calculada por IA: ${data.maturity}%.`,
            risks: '—',
            alerts: '—',
            treatments: controlsText,
            domains: result.domains ?? [],
            recommendations: result.recommendations ?? [],
            fullAnalysis: result.ai_analysis ?? null,
          });
        } catch (err) {
          showToast('Erro ao carregar detalhes: ' + err.message, 'err');
        } finally {
          histBtn.textContent = 'Detalhes';
          histBtn.disabled = false;
        }
        return;
      }
    });

    // ── Botão Iniciar ─────────────────────────────────────────────────────────
    const btnStart = document.getElementById('btnStartAssessment');

    btnStart?.addEventListener('click', async () => {
      const assetId = $('#assetSelectedId', root)?.value;

      // LER OS IDs DIRETAMENTE DO INPUT OCULTO
      const fwIdsString = document.getElementById('frameworksSelected')?.value;
      const fwIds = fwIdsString ? fwIdsString.split(',').map(id => parseInt(id)) : [];

      const year = document.getElementById('periodYear')?.value || '2026';
      const quarter = document.getElementById('periodQuarter')?.value || 'Q1';
      const period = `${quarter} ${year}`;
      if (!assetId) {
        showToast('Selecciona um ativo antes de iniciar.', 'warn');
        return;
      }
      if (!fwIds.length) {
        showToast('Selecciona pelo menos um framework.', 'warn');
        return;
      }

      btnStart.disabled = true;
      btnStart.textContent = '⟳ A analisar... (pode demorar)';

      try {
        const result = await apiPost('/api/assessments', {
          scopetype: 'asset',
          asset_id: assetId ? Number(assetId) : null,
          framework_ids: fwIds,
          period,
        });

        showToast(`✓ Avaliação ${period} concluída — maturidade: ${result.maturity}%`);

        // Recarregar tudo
        HISTORY = await apiGet('/api/assessments');
        renderHistory();
        loadKpis();
        await loadDynamicFrameworks();

      } catch (err) {
        showToast('Erro ao iniciar avaliação: ' + err.message, 'err');
      } finally {
        btnStart.disabled = false;
        btnStart.textContent = '▶ Iniciar';
      }
    });

    // ── Botão Fechar avaliação ────────────────────────────────────────────────
    const btnClose = document.getElementById('btnCloseAssessment');
    btnClose?.addEventListener('click', async () => {
      if (!currentAssessmentId) {
        showToast('Sem avaliação activa para fechar.', 'warn');
        return;
      }
      if (!confirm('Fechar esta avaliação? Ficará arquivada e não poderá ser editada.')) return;

      try {
        await apiPatch(`/api/assessments/${currentAssessmentId}/close`);
        showToast('Avaliação fechada.');

        // 👉 A LINHA QUE FALTAVA PARA FECHAR O MODAL:
        closeModal();

        // Atualizar os dados por trás
        HISTORY = await apiGet('/api/assessments');
        renderHistory();
        loadKpis();
      } catch (e) {
        showToast('Erro: ' + e.message, 'err');
      }
    });

    // ── Botão Comparar ────────────────────────────────────────────────────────
    const btnCompare = document.getElementById('btnCompare');
    btnCompare?.addEventListener('click', () => {
      if (HISTORY.length < 2) {
        showToast('Precisas de pelo menos 2 avaliações para comparar.', 'warn');
        return;
      }
      const [a, b] = HISTORY;
      const delta = a.maturity - b.maturity;
      openModal({
        title: 'Comparação de avaliações',
        subtitle: `${b.period} → ${a.period}`,
        statusHtml: delta >= 0
          ? '<span class="st ok"><span class="dot"></span>MELHORIA</span>'
          : '<span class="st bad"><span class="dot"></span>REGRESSÃO</span>',
        frameworkCross: `${b.period}: ${b.maturity}% → ${a.period}: ${a.maturity}% (${delta >= 0 ? '+' : ''}${delta}pp)`,
        evidenceUsed: '—',
        rationale: `Variação: ${delta >= 0 ? '+' : ''}${delta} pontos percentuais entre as últimas duas avaliações.`,
        risks: '—', alerts: '—', treatments: '—',
        domains: a.domains ?? [],
      });
    });

    // ── Boot ──────────────────────────────────────────────────────────────────
    loadAll();
    return true;
  }

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