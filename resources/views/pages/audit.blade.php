@extends('layouts.app')
@section('title', 'Auditoria • Techbase GRC')
@section('content')

<section id="page-audit" class="page">
<div class="card" style="padding:18px 20px">
<h3 style="margin-bottom:16px">Auditoria / Logs (RNF5)</h3>

{{-- ── Métricas ─────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:20px">
    <div class="audit-metric">
        <div class="audit-metric-label">Eventos hoje</div>
        <div class="audit-metric-val" id="mToday">—</div>
        <div class="audit-metric-sub" id="mTodaySub"></div>
    </div>
    <div class="audit-metric">
        <div class="audit-metric-label">Últimas 24h</div>
        <div class="audit-metric-val" id="m24h">—</div>
        <div class="audit-metric-sub" id="m24hSub"></div>
    </div>
    <div class="audit-metric">
        <div class="audit-metric-label">Ações de risco alto</div>
        <div class="audit-metric-val" id="mRisk" style="color:var(--bad)">—</div>
        <div class="audit-metric-sub">Delete · Export · Purge</div>
    </div>
    <div class="audit-metric">
        <div class="audit-metric-label">Total registos</div>
        <div class="audit-metric-val" id="mTotal">—</div>
        <div class="audit-metric-sub">localStorage (mock)</div>
    </div>
</div>

{{-- ── Barras de distribuição ───────────────────────────────── --}}
<div class="panel" style="margin-bottom:14px">
    <h2>Distribuição por tipo de ação</h2>
    <div id="auditBars"></div>
</div>

{{-- ── Tabela principal ─────────────────────────────────────── --}}
<div class="panel">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:14px">
        <h2 style="margin:0">Registos recentes</h2>
        <button id="auditExportPdf" class="btn" type="button" disabled>Exportar PDF</button>
    </div>

    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <input id="auditSearch"
               placeholder="Pesquisar por utilizador, ação, entidade, detalhe…"
               style="flex:1;min-width:240px;background:var(--input-bg);border:1px solid var(--input-border);color:var(--text);border-radius:8px;padding:7px 11px;font-size:13px;font-family:var(--font)" />

        <select id="auditFilterAction" class="audit-select">
            <option value="">Todas as ações</option>
            <option>Chat</option>
            <option>Upload</option>
            <option>Avaliação</option>
            <option>Login</option>
            <option>Logout</option>
            <option>Delete</option>
            <option>Edit</option>
            <option>Export</option>
        </select>

        <select id="auditFilterRisk" class="audit-select">
            <option value="">Todos os riscos</option>
            <option value="high">Alto</option>
            <option value="med">Médio</option>
            <option value="low">Baixo</option>
        </select>

        <span class="muted" id="auditCount"></span>
    </div>

    <div style="overflow-x:auto">
    <table id="auditTable">
        <thead>
            <tr>
                <th>Data / hora</th>
                <th>Utilizador</th>
                <th>Função</th>
                <th>Ação</th>
                <th>Entidade</th>
                <th>Detalhe</th>
                <th>Risco</th>
            </tr>
        </thead>
        <tbody id="auditTbody">
            {{-- Dados mock — substituir por @foreach($logs as $log) no sistema real --}}
            <tr data-risk="low">
                <td class="muted" style="white-space:nowrap;font-size:12px">2026-02-16 09:22</td>
                <td><span class="audit-avatar av-ana">AN</span> ana</td>
                <td class="muted">Auditor</td>
                <td><span class="audit-badge b-chat">Chat</span></td>
                <td><span class="audit-entity">RF15</span></td>
                <td class="muted">Pergunta sobre ID.AR-1, fontes: QNRCS</td>
                <td><span class="audit-risk risk-low">Baixo</span></td>
            </tr>
            <tr data-risk="med">
                <td class="muted" style="white-space:nowrap;font-size:12px">2026-02-16 09:10</td>
                <td><span class="audit-avatar av-joao">JO</span> joao</td>
                <td class="muted">Gestor</td>
                <td><span class="audit-badge b-upload">Upload</span></td>
                <td><span class="audit-entity">Documento</span></td>
                <td class="muted">Procedimento Inventário v1.0</td>
                <td><span class="audit-risk risk-med">Médio</span></td>
            </tr>
            <tr data-risk="med">
                <td class="muted" style="white-space:nowrap;font-size:12px">2026-02-15 18:05</td>
                <td><span class="audit-avatar av-pedro">PE</span> pedro</td>
                <td class="muted">Analista</td>
                <td><span class="audit-badge b-avaliacao">Avaliação</span></td>
                <td><span class="audit-entity">QNRCS</span></td>
                <td class="muted">Status PR.IP-4 = COVERED</td>
                <td><span class="audit-risk risk-med">Médio</span></td>
            </tr>
        </tbody>
    </table>
    </div>
    <p id="auditEmpty" class="muted" style="display:none;text-align:center;padding:1.5rem 0">
        Nenhum registo encontrado para este filtro.
    </p>
</div>

</div>{{-- .card --}}
</section>

<style>
/* ── Todos os valores usam as variáveis do app_blade.php ────── */

/* Métricas */
.audit-metric {
    background: var(--chip);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 14px 16px;
}
.audit-metric-label { font-size: 12px; color: var(--muted); margin-bottom: 6px; letter-spacing:.03em }
.audit-metric-val   { font-size: 22px; font-weight: 600; color: var(--text); font-family: var(--font-mono) }
.audit-metric-sub   { font-size: 11px; color: var(--muted); margin-top: 4px }

/* Barras */
.audit-bar-row  { display:flex; align-items:center; gap:8px; margin-bottom:7px; font-size:12px; color:var(--muted) }
.audit-bar-key  { width:76px; text-align:right }
.audit-bar-track{ flex:1; height:5px; background:var(--line); border-radius:3px; overflow:hidden }
.audit-bar-fill { height:100%; border-radius:3px; transition:width .4s ease }
.audit-bar-num  { width:20px; text-align:right }

/* Select */
.audit-select {
    font-size: 13px;
    padding: 7px 10px;
    border: 1px solid var(--input-border);
    border-radius: 8px;
    background: var(--input-bg);
    color: var(--text);
    font-family: var(--font);
}
.audit-select:focus { outline: none; border-color: var(--info) }

/* Avatar */
.audit-avatar {
    display: inline-flex; align-items: center; justify-content: center;
    width: 24px; height: 24px; border-radius: 50%;
    font-size: 10px; font-weight: 600; vertical-align: middle; margin-right: 5px;
}
/* Cores usam rgba para funcionar bem em dark e light */
.av-ana   { background: rgba(96,165,250,.22);  color: var(--info) }
.av-joao  { background: rgba(45,212,191,.18);  color: var(--ok) }
.av-pedro { background: rgba(251,113,133,.18); color: var(--bad) }
.av-maria { background: rgba(251,191,36,.18);  color: var(--warn) }
.av-sys   { background: rgba(255,255,255,.08); color: var(--muted) }

/* Badges de ação — fundo semi-transparente + cor semântica do sistema */
.audit-badge {
    display: inline-block; font-size: 11px; font-weight: 500;
    padding: 2px 9px; border-radius: 999px;
    border: 1px solid transparent;
}
.b-chat      { background: rgba(96,165,250,.15);  border-color: rgba(96,165,250,.30);  color: var(--info) }
.b-upload    { background: rgba(45,212,191,.13);  border-color: rgba(45,212,191,.28);  color: var(--ok) }
.b-avaliacao { background: rgba(251,191,36,.13);  border-color: rgba(251,191,36,.28);  color: var(--warn) }
.b-login     { background: rgba(255,255,255,.07); border-color: rgba(255,255,255,.12); color: var(--muted) }
.b-logout    { background: rgba(255,255,255,.07); border-color: rgba(255,255,255,.12); color: var(--muted) }
.b-delete    { background: rgba(251,113,133,.15); border-color: rgba(251,113,133,.30); color: var(--bad) }
.b-edit      { background: rgba(45,212,191,.10);  border-color: rgba(45,212,191,.22);  color: var(--ok) }
.b-export    { background: rgba(251,113,133,.10); border-color: rgba(251,113,133,.22); color: var(--bad) }

/* Entity pill */
.audit-entity {
    font-size: 11px; color: var(--muted);
    background: var(--chip);
    border: 1px solid var(--line);
    border-radius: 6px; padding: 2px 7px;
    font-family: var(--font-mono);
}

/* Risco */
.audit-risk { display:inline-flex; align-items:center; gap:5px; font-size:12px }
.audit-risk::before {
    content:''; display:inline-block;
    width:7px; height:7px; border-radius:50%;
}
.risk-high { color: var(--bad) }
.risk-high::before { background: var(--bad) }
.risk-med  { color: var(--warn) }
.risk-med::before  { background: var(--warn) }
.risk-low  { color: var(--ok) }
.risk-low::before  { background: var(--ok) }
</style>

<script>
(function () {
    const BADGE_CLASS = {
        Chat:'b-chat', Upload:'b-upload', 'Avaliação':'b-avaliacao',
        Login:'b-login', Logout:'b-logout', Delete:'b-delete',
        Edit:'b-edit', Export:'b-export'
    };
    const RISK_LABEL = { high:'Alto', med:'Médio', low:'Baixo' };
    const RISK_CLASS = { high:'risk-high', med:'risk-med', low:'risk-low' };
    const ACTION_COLOR = {
        Chat:'var(--info)', Upload:'var(--ok)', 'Avaliação':'var(--warn)',
        Login:'var(--muted)', Logout:'var(--muted)', Delete:'var(--bad)',
        Edit:'var(--ok)', Export:'var(--bad)'
    };
    const AVATAR_CLASS = u => ({ ana:'av-ana', joao:'av-joao', pedro:'av-pedro', maria:'av-maria' }[u] || 'av-sys');
    const initials = u => (u||'??').slice(0,2).toUpperCase();

    const srch      = document.getElementById('auditSearch');
    const fAction   = document.getElementById('auditFilterAction');
    const fRisk     = document.getElementById('auditFilterRisk');
    const exportBtn = document.getElementById('auditExportPdf');
    const countLbl  = document.getElementById('auditCount');
    const tbody     = document.getElementById('auditTbody');
    const emptyMsg  = document.getElementById('auditEmpty');
    const barsEl    = document.getElementById('auditBars');
    if (!srch || !tbody) return;

    /* ── localStorage ──────────────────────────────────── */
    function loadAudit() {
        try { return JSON.parse(localStorage.getItem('tb_audit_v1') || '[]'); }
        catch { return []; }
    }

    function populateFromStorage() {
        const records = loadAudit();
        if (!records.length) return;
        tbody.innerHTML = records.map(r => {
            const risk   = r.risk || 'low';
            const action = r.action || '—';
            const bc     = BADGE_CLASS[action] || 'b-chat';
            const av     = AVATAR_CLASS(r.user);
            return `<tr data-risk="${risk}">
                <td class="muted" style="white-space:nowrap;font-size:12px">${r.at}</td>
                <td><span class="audit-avatar ${av}">${initials(r.user)}</span> ${r.user}</td>
                <td class="muted">${r.role}</td>
                <td><span class="audit-badge ${bc}">${action}</span></td>
                <td><span class="audit-entity">${r.entity}</span></td>
                <td class="muted">${r.detail}</td>
                <td><span class="audit-risk ${RISK_CLASS[risk]}">${RISK_LABEL[risk]}</span></td>
            </tr>`;
        }).join('');
    }

    /* ── métricas ──────────────────────────────────────── */
    function updateMetrics() {
        const rows    = loadAudit();
        const today   = new Date().toISOString().slice(0,10);
        const cut24   = Date.now() - 86400000;
        const todayCt = rows.filter(r => r.at?.startsWith(today)).length;
        const last24  = rows.filter(r => { try { return new Date(r.at).getTime() >= cut24; } catch { return false; }}).length;
        const users24 = new Set(rows.filter(r => { try { return new Date(r.at).getTime() >= cut24; } catch { return false; }}).map(r=>r.user)).size;
        const riskHigh = rows.filter(r => r.risk === 'high').length;

        document.getElementById('mToday').textContent = todayCt || tbody.querySelectorAll('tr').length;
        document.getElementById('m24h').textContent   = last24 || '—';
        document.getElementById('m24hSub').textContent = users24 ? `${users24} utilizadores` : '';
        document.getElementById('mRisk').textContent  = riskHigh;
        document.getElementById('mTotal').textContent = rows.length || tbody.querySelectorAll('tr').length;
    }

    /* ── barras ────────────────────────────────────────── */
    function buildBars() {
        const allRows = Array.from(tbody.querySelectorAll('tr'));
        const counts  = {};
        allRows.forEach(tr => {
            const badge = tr.querySelector('.audit-badge');
            if (badge) counts[badge.textContent] = (counts[badge.textContent] || 0) + 1;
        });
        const max = Math.max(...Object.values(counts), 1);
        barsEl.innerHTML = Object.entries(counts)
            .sort((a,b) => b[1]-a[1])
            .map(([k,v]) => `
                <div class="audit-bar-row">
                    <span class="audit-bar-key">${k}</span>
                    <div class="audit-bar-track">
                        <div class="audit-bar-fill" style="width:${Math.round(v/max*100)}%;background:${ACTION_COLOR[k]||'var(--muted)'}"></div>
                    </div>
                    <span class="audit-bar-num">${v}</span>
                </div>`).join('');
    }

    /* ── filtro ────────────────────────────────────────── */
    function apply() {
        const q  = (srch.value || '').toLowerCase().trim();
        const fa = fAction.value;
        const fr = fRisk.value;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        let vis = 0;

        rows.forEach(tr => {
            const text   = tr.innerText.toLowerCase();
            const action = tr.querySelector('.audit-badge')?.textContent || '';
            const risk   = tr.dataset.risk || '';
            const ok = (!q || text.includes(q)) && (!fa || action === fa) && (!fr || risk === fr);
            tr.style.display = ok ? '' : 'none';
            if (ok) vis++;
        });

        countLbl.textContent = `Mostrando ${vis} de ${rows.length}`;
        emptyMsg.style.display = vis === 0 ? 'block' : 'none';
        if (exportBtn) {
            exportBtn.disabled = vis === 0;
            exportBtn.textContent = vis === 0 ? 'Exportar PDF' : `Exportar PDF (${vis})`;
        }
    }

    /* ── exportação PDF ────────────────────────────────── */
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            const visibleRows = Array.from(tbody.querySelectorAll('tr')).filter(tr => tr.style.display !== 'none');
            if (!visibleRows.length) return;

            const w = window.open('', '_blank', 'width=960,height=720');
            if (!w) return;

            const ts = new Date().toLocaleString('pt-PT');
            const q  = (srch.value || '').trim();
            const riskColors = { high:'#e11d48', med:'#d97706', low:'#0ea5a3' };
            const RISK_LABEL_PDF = { high:'Alto', med:'Médio', low:'Baixo' };

            w.document.write(`<!doctype html><html><head><title>Auditoria — Techbase GRC</title>
            <style>
                body{font-family:system-ui,sans-serif;margin:24px;color:#0b1220}
                h1{font-size:17px;margin:0 0 4px}
                .meta{font-size:12px;color:#555;margin:0 0 16px}
                table{width:100%;border-collapse:collapse;font-size:12px}
                th,td{border-bottom:1px solid #eee;padding:7px 8px;text-align:left;vertical-align:top}
                th{background:#f6f7fb;font-weight:600}
                @media print{body{margin:12mm}}
            </style></head><body>
            <h1>Relatório de Auditoria — Techbase GRC</h1>
            <div class="meta">${q ? `Filtro: "${q}" • ` : ''}Gerado em ${ts} • ${visibleRows.length} registos</div>
            <table>
                <thead><tr>
                    <th>Data / hora</th><th>Utilizador</th><th>Função</th>
                    <th>Ação</th><th>Entidade</th><th>Detalhe</th><th>Risco</th>
                </tr></thead>
                <tbody>
                    ${visibleRows.map(tr => {
                        const cells = tr.querySelectorAll('td');
                        const risk  = tr.dataset.risk || 'low';
                        return `<tr>
                            <td>${cells[0]?.innerText||''}</td>
                            <td>${cells[1]?.innerText?.trim()||''}</td>
                            <td>${cells[2]?.innerText||''}</td>
                            <td>${cells[3]?.innerText||''}</td>
                            <td>${cells[4]?.innerText||''}</td>
                            <td>${cells[5]?.innerText||''}</td>
                            <td style="color:${riskColors[risk]};font-weight:500">${RISK_LABEL_PDF[risk]}</td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
            <div class="meta" style="margin-top:14px;color:#888">Mock — no sistema real, exportação viria do backend com paginação e assinatura digital.</div>
            </body></html>`);
            w.document.close();
            setTimeout(() => { w.focus(); w.print(); }, 250);
        });
    }

    /* ── init ──────────────────────────────────────────── */
    srch.addEventListener('input', apply);
    fAction.addEventListener('change', apply);
    fRisk.addEventListener('change', apply);

    populateFromStorage();
    updateMetrics();
    buildBars();
    apply();
})();
</script>

@endsection
