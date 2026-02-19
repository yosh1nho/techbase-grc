<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>@yield('title', 'Techbase GRC')</title>

    {{-- Mock CSS inline (depois podes migrar para Vite/Tailwind) --}}
    <style>
        :root{
        /* DARK (default) — mantém o que tu já tinha */
        --bg: #0b1220;
        --panel: #121a2b;
        --panel2: #0f1726;
        --text: #e6eefc;
        --muted: #9fb0d0;
        --line: #22304a;
        --ok: #2dd4bf;
        --warn: #fbbf24;
        --bad: #fb7185;
        --info: #60a5fa;
        --chip: #1b2742;
        --radius: 14px;
        --shadow: 0 10px 30px rgba(0, 0, 0, .35);
        --font: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;

        /* fundo por tema */
        --bg-gradient: linear-gradient(180deg, #070c16, #0b1220 30%, #070c16);
        }

        :root[data-theme="light"]{
        /* LIGHT — mesmos nomes, só mudam os valores */
        --bg: #f4f7ff;
        --panel: #ffffff;
        --panel2: #f2f5ff;
        --text: #0b1220;
        --muted: #53637a;
        --line: #d6deef;
        --ok: #0ea5a3;
        --warn: #d97706;
        --bad: #e11d48;
        --info: #2563eb;
        --chip: #eef2ff;
        --shadow: 0 10px 30px rgba(255, 0, 0, 0);

        --bg-gradient: linear-gradient(180deg, #ffffff, #f4f7ff 35%, #eef3ff);
        }
        :root[data-theme="light"]{
        /* superfícies */
        --sidebar-bg: linear-gradient(180deg, rgba(255,255,255,.92), rgba(244,247,255,.88));
        --topbar-bg: rgba(255,255,255,.78);
        --card-bg: rgba(255,255,255,.72);
        --panel-bg: rgba(255,255,255,.70);

        /* campos */
        --field-bg: rgba(255,255,255,.78);
        --field-border: rgba(15, 23, 42, .12);

        /* hovers */
        --hover-row: rgba(2, 6, 23, .03);
        --hover-link: rgba(37, 99, 235, .08);
        }



        * {
            box-sizing: border-box
        }
        body{
        margin: 0;
        font-family: var(--font);   
        background: var(--bg-gradient); 
        color: var(--text);
        }

        .card,
        .panel {
        background: var(--panel);
        border: 1px solid var(--line);
        }


        .muted {
            color: var(--muted);
        }


        a {
            color: inherit;
            text-decoration: none
        }

        .app {
            display: grid;
            grid-template-columns: 290px 1fr;
            min-height: 100vh
        }

        .sidebar {
            padding: 18px 16px;
            border-right: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(18, 26, 43, .95), rgba(15, 23, 38, .9));
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .brand {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 10px 10px 18px
        }

        .logo {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: radial-gradient(circle at 30% 30%, #60a5fa, #2dd4bf);
            box-shadow: var(--shadow)
        }

        .brand h1 {
            font-size: 14px;
            margin: 0;
            letter-spacing: .3px
        }

        .brand p {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 12px
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 6px
        }

        .nav a {
            padding: 10px 12px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid transparent;
            color: var(--muted);
            font-size: 13px;
        }

        .nav a:hover {
            background: rgba(96, 165, 250, .08);
            border-color: rgba(96, 165, 250, .18);
            color: var(--text)
        }

        .nav a.active {
            background: rgba(45, 212, 191, .10);
            border-color: rgba(45, 212, 191, .25);
            color: var(--text)
        }

        .badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .07);
            border: 1px solid rgba(255, 255, 255, .08)
        }

        .sidebar .foot {
            margin-top: auto;
            padding: 14px 10px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 12px;
            display: flex;
            justify-content: space-between
        }

        .main {
            padding: 18px 22px 28px
        }

        .topbar {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: rgba(18, 26, 43, .65);
            box-shadow: var(--shadow);
        }

        .top-left {
            display: flex;
            gap: 10px;
            align-items: center
        }

        .pill {
            display: flex;
            gap: 8px;
            align-items: center;
            padding: 8px 10px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 999px;
            background: rgba(15, 23, 38, .65);
            color: var(--muted);
            font-size: 12px;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 99px;
            background: var(--ok)
        }

        .search {
            min-width: 320px;
            max-width: 520px;
            flex: 1;
            display: flex;
            gap: 8px;
            align-items: center;
            padding: 10px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, .10);
            background: rgba(0, 0, 0, .18);
            color: var(--muted);
        }

        .search input {
            all: unset;
            flex: 1;
            color: var(--text);
            font-size: 13px
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center
        }

        .btn {
            cursor: pointer;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, .10);
            background: rgba(255, 255, 255, .06);
            color: var(--text);
            font-size: 13px;
        }

        .btn.primary {
            background: rgba(96, 165, 250, .18);
            border-color: rgba(96, 165, 250, .35)
        }

        .btn.ok {
            background: rgba(45, 212, 191, .14);
            border-color: rgba(45, 212, 191, .30)
        }

        .btn:hover {
            filter: brightness(1.08)
        }

        .grid {
            display: grid;
            gap: 14px;
            margin-top: 14px
        }

        .grid.cards {
            grid-template-columns: repeat(4, minmax(0, 1fr))
        }

        .card {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: rgba(18, 26, 43, .70);
            box-shadow: var(--panel);
            padding: 14px 14px 12px;
        }

        .card h3 {
            margin: 0 0 6px;
            font-size: 13px;
            color: var(--text);
            font-weight: 600
        }

        .big {
            font-size: 22px;
            margin: 0 0 6px;
            font-weight: 750;
            letter-spacing: .2px
        }

        .sub {
            margin: 0;
            color: var(--muted);
            font-size: 12px
        }

        .kpirow {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px
        }

        .chip {
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .08);
            font-size: 12px;
            color: var(--muted)
        }

        .chip.ok {
            color: #b9fff5;
            border-color: rgba(45, 212, 191, .35);
            background: rgba(45, 212, 191, .10)
        }

        .chip.warn {
            color: #ffe9b5;
            border-color: rgba(251, 191, 36, .35);
            background: rgba(251, 191, 36, .10)
        }

        .chip.bad {
            color: #ffd0d8;
            border-color: rgba(251, 113, 133, .35);
            background: rgba(251, 113, 133, .10)
        }

        .section-title {
            margin: 18px 2px 8px;
            font-size: 13px;
            color: var(--muted)
        }

        .split {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 14px
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px
        }

        th,
        td {
            padding: 10px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
            vertical-align: top
        }

        th {
            color: var(--muted);
            font-weight: 600;
            text-align: left
        }

        tr:hover td {
            background: rgba(255, 255, 255, .03)
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 999px;
            background: var(--chip);
            border: 1px solid rgba(255, 255, 255, .08);
            font-size: 12px;
            color: var(--muted)
        }

        .tag .s {
            width: 7px;
            height: 7px;
            border-radius: 99px;
            background: var(--info)
        }

        .tag.ok .s {
            background: var(--ok)
        }

        .tag.warn .s {
            background: var(--warn)
        }

        .tag.bad .s {
            background: var(--bad)
        }

        .muted {
            color: var(--muted);
            font-size: 12px
        }

        .panel {
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: rgba(15, 23, 38, .60)
        }

        .panel h2 {
            margin: 0 0 10px;
            font-size: 14px
        }

        .row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap
        }

        .field {
            flex: 1;
            min-width: 180px
        }

        label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin: 0 0 6px
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px 10px;
            border-radius: 12px;
            background: rgba(0, 0, 0, .18);
            border: 1px solid rgba(255, 255, 255, .10);
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
        }

        select {
            padding: 10px 40px 10px 12px;
            /* espaço pra seta */
            cursor: pointer;

            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;

            background-color: rgba(0, 0, 0, .22);

            background-image:
                linear-gradient(45deg, transparent 50%, rgba(255, 255, 255, .75) 50%),
                linear-gradient(135deg, rgba(255, 255, 255, .75) 50%, transparent 50%);
            background-position:
                calc(100% - 18px) 55%,
                calc(100% - 12px) 55%;
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
        }

        select:focus {
            border-color: rgba(96, 165, 250, .55);
            box-shadow: 0 0 0 4px rgba(96, 165, 250, .18);
        }

        textarea {
            min-height: 92px;
            resize: vertical
        }

        .hint {
            margin-top: 6px;
            font-size: 12px;
            color: var(--muted)
        }

        .two {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px
        }

        @media (max-width:1100px) {
            .app {
                grid-template-columns: 1fr
            }

            .sidebar {
                position: relative;
                height: auto
            }

            .grid.cards {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }

            .split {
                grid-template-columns: 1fr
            }

            .search {
                min-width: 0
            }
        }

        /* ===== Custom Select (reutilizável) ===== */
        .cs {
            position: relative;
            width: 100%;
        }

        .cs-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;

            padding: 10px 12px;
            border-radius: 12px;

            background: rgba(0, 0, 0, .22);
            border: 1px solid rgba(255, 255, 255, .12);
            color: var(--text);
            font-size: 13px;

            cursor: pointer;
            user-select: none;
        }

        .cs-btn:focus {
            outline: none;
            border-color: rgba(96, 165, 250, .55);
            box-shadow: 0 0 0 4px rgba(96, 165, 250, .18);
        }

        .cs-value {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cs-muted {
            color: var(--muted);
        }

        .cs-caret {
            width: 10px;
            height: 10px;
            border-right: 2px solid rgba(255, 255, 255, .75);
            border-bottom: 2px solid rgba(255, 255, 255, .75);
            transform: rotate(45deg);
            margin-left: auto;
            opacity: .9;
        }

        .cs.open .cs-caret {
            transform: rotate(-135deg);
        }

        .cs-menu {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 8px);
            z-index: 10000;

            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, .12);
            background: rgba(18, 26, 43, .98);
            box-shadow: 0 20px 40px rgba(0, 0, 0, .45);

            padding: 8px;
            display: none;
        }

        .cs.open .cs-menu {
            display: block;
        }

        .cs-search {
            width: 100%;
            padding: 10px 10px;
            border-radius: 12px;
            background: rgba(0, 0, 0, .18);
            border: 1px solid rgba(255, 255, 255, .10);
            color: var(--text);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .cs-list {
            max-height: 220px;
            overflow: auto;
            padding: 2px;
        }

        .cs-opt {
            padding: 10px 10px;
            border-radius: 12px;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .cs-opt:hover {
            background: rgba(96, 165, 250, .10);
            border-color: rgba(96, 165, 250, .20);
        }

        .cs-opt[aria-selected="true"] {
            background: rgba(45, 212, 191, .12);
            border-color: rgba(45, 212, 191, .25);
        }

        .cs-opt.cs-active {
            outline: 2px solid rgba(96, 165, 250, .40);
            outline-offset: 2px;
        }

        .cs-hidden {
            display: none;
        }

        /* Modal overlay (Techbase) */
    .modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(0,0,0,.55);
    backdrop-filter: blur(2px);
    }

    .modal-overlay.is-hidden {
    display: none !important;
    }

    .modal-card {
    width: min(920px, 96vw);
    max-height: 88vh;
    overflow: auto;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,.08);
    background: rgba(12, 18, 30, .98);
    box-shadow: 0 20px 60px rgba(0,0,0,.55);
    padding: 16px;
    }

    .modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    }

    .kanban {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    }

    .kanban-col {
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 14px;
    background: rgba(255,255,255,.02);
    overflow: hidden;
    }

    .kanban-col-head {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding: 10px 12px;
    border-bottom: 1px solid rgba(255,255,255,.06);
    }

    .kanban-drop {
    min-height: 240px;
    padding: 10px;
    display:flex;
    flex-direction:column;
    gap:10px;
    }

    .kcard {
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 14px;
    padding: 10px;
    background: rgba(10,15,25,.85);
    cursor: grab;
    }

    .kcard:active { cursor: grabbing; }

    .kcard:hover { background: rgba(10,15,25,.95); }

    .kcard-top {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    }

    .kmeta { font-size: 12px; opacity: .85; }
    .kdesc { margin-top:8px; opacity:.9; }
    .krow { margin-top:8px; display:flex; flex-wrap:wrap; gap:8px; }

    /* ===== Light theme overrides (sem quebrar o dark) ===== */
    :root[data-theme="light"] .sidebar{
    background: var(--sidebar-bg);
    border-right: 1px solid var(--line);
    }

    :root[data-theme="light"] .topbar{
    background: var(--topbar-bg);
    border: 1px solid var(--line);
    }

    :root[data-theme="light"] .card{
    background: var(--card-bg);
    border: 1px solid var(--line);
    }

    :root[data-theme="light"] .panel{
    background: var(--panel-bg);
    border: 1px solid var(--line);
    }

    :root[data-theme="light"] .search{
    background: rgba(255,255,255,.70);
    border: 1px solid var(--field-border);
    color: var(--muted);
    }

    :root[data-theme="light"] input,
    :root[data-theme="light"] select,
    :root[data-theme="light"] textarea{
    background: var(--field-bg);
    border: 1px solid var(--field-border);
    color: var(--text);
    }

    :root[data-theme="light"] th,
    :root[data-theme="light"] td{
    border-bottom: 1px solid rgba(15,23,42,.08);
    }

    :root[data-theme="light"] tr:hover td{
    background: var(--hover-row);
    }

    :root[data-theme="light"] .nav a:hover{
    background: var(--hover-link);
    border-color: rgba(37,99,235,.18);
    }

    :root[data-theme="light"] .btn{
    background: rgba(255,255,255,.66);
    border: 1px solid rgba(15,23,42,.12);
    color: var(--text);
    }

    </style>
</head>

<body>
    @php
        $org = $org ?? 'Exemplo';
        $framework = $framework ?? 'QNRCS';
        $fwVersion = $fwVersion ?? '2.1';

        $navItems = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'badge' => 'RF18'],
            ['route' => 'assets', 'label' => 'Ativos', 'badge' => 'RF1'],
            ['route' => 'docs', 'label' => 'Documentos & Evidências', 'badge' => 'RF2–RF3'],
            ['route' => 'assessments', 'label' => 'Avaliações', 'badge' => 'RF5–RF6'],
            ['route' => 'risks', 'label' => 'Riscos', 'badge' => 'RF7–RF9'],
            ['route' => 'treatment', 'label' => 'Planos de Tratamento', 'badge' => 'RF10–RF11'],
            ['route' => 'questionnaire', 'label' => 'Questionário', 'badge' => 'RF13'],
            ['route' => 'chat', 'label' => 'Chat de Governação', 'badge' => 'RF14–RF15'],
            ['route' => 'audit', 'label' => 'Auditoria', 'badge' => 'RNF5'],
            ['route' => 'rbac', 'label' => 'RBAC', 'badge' => 'RF19'],
            ['route' => 'relatorios-cncs', 'label' => 'Relatórios CNCS', 'badge' => 'RF20'],
        ];
      @endphp

    <div class="app">
        <aside class="sidebar">
            <div class="brand">
                <div class="logo"></div>
                <div>
                    <h1>Techbase GRC • NIS2</h1>
                    <p>Conformidade • Risco • Evidências</p>
                </div>
            </div>

            <nav class="nav"> @foreach($navItems as $item) <a href="{{ route($item['route']) }}"
                    class="{{ request()->routeIs($item['route']) ? 'active' : '' }}">
                    <span>{{ $item['label'] }}</span>
                    <span class="badge">{{ $item['badge'] }}</span>
                </a>
            @endforeach
            </nav>

            <div class="foot">
                <span>Estado: <b style="color:var(--ok)">Online</b></span>
                <span>v0.1 mock</span>
            </div>
        </aside>

        <main class="main">
            <header class="topbar">
                <div class="top-left">
                    <div class="pill"><span class="dot"></span> Org: <b style="color:var(--text)">{{ $org }}</b></div>
                    <div class="pill">Framework: <b style="color:var(--text)">{{ $framework }}</b> • v{{ $fwVersion }}
                    </div>
                </div>

                <div class="search" title="Pesquisa global (mock)">
                    🔎 <input placeholder="Pesquisar ativos, controlos, evidências, riscos..." />
                </div>

                <div class="actions">
                    <button class="btn ok" type="button">+ Nova avaliação</button>
                    <button class="btn primary" type="button">Upload documento</button>
                    <button class="btn" type="button">Perfil</button>
                    {{-- <button id="btnThemeToggle" class="btn" type="button" title="Alternar tema">
                        🌙
                    </button> --}}

                </div>
            </header>

            <div style="margin-top:14px">
                @yield('content')
            </div>
        </main>
    </div>

    @vite(['resources/js/theme.js'])
    @stack('scripts')

    @stack('scripts')
    <script>
        (function () {
            function parseOptions(str) {
                // aceita "1,2,3" ou JSON: [{"value":"1","label":"Muito Baixo"}]
                str = (str || '').trim();
                if (!str) return [];
                if (str.startsWith('[')) {
                    try { return JSON.parse(str); } catch (e) { return []; }
                }
                return str.split(',').map(x => {
                    const v = x.trim();
                    return { value: v, label: v };
                });
            }

            function closeAll(except) {
                document.querySelectorAll('.cs.open').forEach(cs => {
                    if (cs !== except) cs.classList.remove('open');
                });
            }

            function initCustomSelect(cs) {
                if (cs.dataset.csInit === '1') return;
                cs.dataset.csInit = '1';

                const btn = cs.querySelector('.cs-btn');
                const menu = cs.querySelector('.cs-menu');
                const list = cs.querySelector('.cs-list');
                const valueEl = cs.querySelector('.cs-value');
                const hidden = cs.querySelector('input[type="hidden"]');
                const search = cs.querySelector('.cs-search');

                const options = parseOptions(cs.dataset.options);

                let activeIndex = -1;

                function render(filter = '') {
                    list.innerHTML = '';
                    const f = filter.toLowerCase();
                    const filtered = options.filter(o => (o.label ?? o.value).toLowerCase().includes(f));

                    filtered.forEach((o, idx) => {
                        const opt = document.createElement('div');
                        opt.className = 'cs-opt';
                        opt.setAttribute('role', 'option');
                        opt.dataset.value = o.value;
                        opt.dataset.index = String(idx);

                        const label = (o.label ?? o.value);
                        opt.innerHTML = `<span>${label}</span>`;

                        const selected = hidden.value === String(o.value);
                        opt.setAttribute('aria-selected', selected ? 'true' : 'false');

                        opt.addEventListener('click', () => {
                            setValue(o.value, label);
                            cs.classList.remove('open');
                        });

                        list.appendChild(opt);
                    });

                    // reset active
                    activeIndex = filtered.length ? 0 : -1;
                    setActive(activeIndex);

                    return filtered;
                }

                function setActive(i) {
                    const opts = list.querySelectorAll('.cs-opt');
                    opts.forEach(o => o.classList.remove('cs-active'));
                    if (i >= 0 && opts[i]) opts[i].classList.add('cs-active');
                }

                function setValue(val, label) {
                    hidden.value = String(val);
                    valueEl.innerHTML = `<span>${label}</span>`;
                    // evento pra tu usar em lógica (risk calc etc.)
                    cs.dispatchEvent(new CustomEvent('cs:change', { detail: { value: val, label } }));
                    // atualizar aria-selected
                    list.querySelectorAll('.cs-opt').forEach(o => {
                        o.setAttribute('aria-selected', o.dataset.value === String(val) ? 'true' : 'false');
                    });
                }

                // valor inicial
                const initial = hidden.value || (options[0]?.value ?? '');
                const initialLabel = (options.find(o => String(o.value) === String(initial))?.label) ?? String(initial);
                if (initial) setValue(initial, initialLabel);

                btn.addEventListener('click', () => {
                    const isOpen = cs.classList.contains('open');
                    closeAll(cs);
                    cs.classList.toggle('open', !isOpen);
                    if (!isOpen) {
                        render(search ? search.value : '');
                        setTimeout(() => search?.focus(), 0);
                    }
                });

                btn.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        btn.click();
                    }
                    if (e.key === 'Escape') {
                        cs.classList.remove('open');
                    }
                });

                if (search) {
                    search.addEventListener('input', () => render(search.value));
                    search.addEventListener('keydown', (e) => {
                        const opts = list.querySelectorAll('.cs-opt');
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            activeIndex = Math.min(activeIndex + 1, opts.length - 1);
                            setActive(activeIndex);
                            opts[activeIndex]?.scrollIntoView({ block: 'nearest' });
                        }
                        if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            activeIndex = Math.max(activeIndex - 1, 0);
                            setActive(activeIndex);
                            opts[activeIndex]?.scrollIntoView({ block: 'nearest' });
                        }
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            const opt = opts[activeIndex];
                            if (opt) {
                                opt.click();
                            }
                        }
                        if (e.key === 'Escape') {
                            cs.classList.remove('open');
                            btn.focus();
                        }
                    });
                }

                document.addEventListener('click', (e) => {
                    if (!cs.contains(e.target)) cs.classList.remove('open');
                });
            }

            // init todos
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('.cs').forEach(initCustomSelect);
            });

            // expõe pra caso tu crie dinamicamente
            window.initCustomSelect = initCustomSelect;
        })();
    </script>

</body>

</html>