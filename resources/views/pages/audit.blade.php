@extends('layouts.app')
@section('title', 'Auditoria • Techbase GRC')
@section('content')
    <section id="page-audit" class="page">
        <div class="card">
            <h3>Auditoria / Logs (RNF5)</h3>
            <div style="display:flex; gap:10px; align-items:center; margin:10px 0 12px; flex-wrap:wrap;">
                <input id="auditSearch" placeholder="Pesquisar por data, utilizador, ação, entidade, detalhe..."
                    style="min-width:320px;" />
                <button id="auditExportPdf" class="btn" type="button" disabled
                    title="Exporta os registos filtrados para PDF (mock via impressão)">Exportar PDF</button>
                <span class="muted" id="auditCount"></span>
            </div>

            <div class="panel" style="margin-top:10px">
                <h2>Registos recentes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Utilizador</th>
                            <th>Ação</th>
                            <th>Entidade</th>
                            <th>Detalhe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="muted">2026-02-16 09:22</td>
                            <td>ana</td>
                            <td>Chat</td>
                            <td>RF15</td>
                            <td class="muted">Pergunta sobre ID.AR-1, fontes: QNRCS</td>
                        </tr>
                        <tr>
                            <td class="muted">2026-02-16 09:10</td>
                            <td>joao</td>
                            <td>Upload</td>
                            <td>Documento</td>
                            <td class="muted">Procedimento Inventário v1.0</td>
                        </tr>
                        <tr>
                            <td class="muted">2026-02-15 18:05</td>
                            <td>pedro</td>
                            <td>Avaliação</td>
                            <td>QNRCS</td>
                            <td class="muted">Status PR.IP-4 = COVERED</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

<script>
  (function () {
    const input = document.getElementById('auditSearch');
    const tbody = document.querySelector('#page-audit tbody');
    const count = document.getElementById('auditCount');
    const exportBtn = document.getElementById('auditExportPdf');
    if (!input || !tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr'));

    function getVisibleRows() {
      return rows.filter(tr => tr.style.display !== 'none');
    }

    function setExportState() {
      if (!exportBtn) return;
      const visible = getVisibleRows().length;
      exportBtn.disabled = visible === 0;
      exportBtn.textContent = visible === 0 ? 'Exportar PDF' : `Exportar PDF (${visible})`;
    }

    function apply() {
      const q = (input.value || '').toLowerCase().trim();
      let visible = 0;

      rows.forEach(tr => {
        const text = tr.innerText.toLowerCase();
        const ok = !q || text.includes(q);
        tr.style.display = ok ? '' : 'none';
        if (ok) visible++;
      });

      if (count) count.textContent = `Mostrando ${visible} de ${rows.length}`;
      setExportState();
    }

    input.addEventListener('input', apply);

    if (exportBtn) {
      exportBtn.addEventListener('click', () => {
        const visibleRows = getVisibleRows();
        if (!visibleRows.length) return;

        // Monta um HTML simples e imprimível (o utilizador escolhe “Guardar como PDF”).
        const w = window.open('', '_blank', 'width=900,height=700');
        if (!w) return;

        const title = 'Relatório de Auditoria (mock)';
        const q = (input.value || '').trim();
        const subtitle = q ? `Filtro: “${q}”` : 'Sem filtro';
        const ts = new Date().toLocaleString();

        const headHtml = `
          <style>
            body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:24px;color:#0b1220;}
            h1{font-size:18px;margin:0 0 6px;}
            .meta{font-size:12px;color:#445; margin:0 0 16px;}
            table{width:100%;border-collapse:collapse;font-size:12px;}
            th,td{border-bottom:1px solid #e6e8ee;padding:8px 6px;text-align:left;vertical-align:top;}
            th{background:#f6f7fb;font-weight:600;}
            .muted{color:#667;}
            @media print{ body{margin:12mm;} }
          </style>`;

        const thead = document.querySelector('#page-audit table thead')?.outerHTML || '';
        const bodyRows = visibleRows.map(tr => tr.outerHTML).join('');
        const table = `<table>${thead}<tbody>${bodyRows}</tbody></table>`;

        w.document.open();
        w.document.write(`<!doctype html><html><head><title>${title}</title>${headHtml}</head>
          <body>
            <h1>${title}</h1>
            <div class="meta">${subtitle} • Gerado em ${ts} • Registos: ${visibleRows.length}</div>
            ${table}
            <div class="meta muted" style="margin-top:14px;">Nota: exportação via impressão (mock). No sistema real, isto viria do backend com paginação e assinatura.</div>
          </body></html>`);
        w.document.close();

        // Dá um tiquinho para renderizar antes de abrir o diálogo.
        setTimeout(() => { w.focus(); w.print(); }, 250);
      });
    }

    apply();
  })();
</script>


@endsection