<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Techbase GRC')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
    <script>
        // Passa o array de permissões da sessão do Laravel para uma variável global JS
        window.TB_PERMISSIONS = @json(session('tb_user.permissions', []));
        
        // Passa os dados do utilizador logado (nome, email, etc)
        window.TB_USER = @json(session('tb_user', ['name' => 'Utilizador']));
    </script>
    @stack('styles') 
    {{-- Mock CSS inline (depois podes migrar para Vite/Tailwind) --}}
    <style>
        :root{
        /* DARK (default) */
        --bg: #080e1a;
        --panel: #0e1625;
        --panel2: #0b1220;
        --text: #dde8f8;
        --muted: #8097bb;
        --line: #1c2c45;
        --ok: #2dd4bf;
        --warn: #fbbf24;
        --bad: #fb7185;
        --info: #60a5fa;
        --chip: #132040;
        --radius: 14px;
        --shadow: 0 10px 30px rgba(0, 0, 0, .45);
        --font: 'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif;
        --font-mono: 'IBM Plex Mono', ui-monospace, monospace;

        /* fundo por tema */
        --bg-gradient: linear-gradient(180deg, #060b14, #080e1a 30%, #060b14);
        --modal-bg: rgba(12, 18, 30, .98);
        --modal-border: rgba(255,255,255,.08);
        --modal-overlay: rgba(0,0,0,.55);
        --modal-panel: rgba(255,255,255,.04);

        /* shared component vars */
        --input-bg:   rgba(0,0,0,.22);
        --input-border: rgba(255,255,255,.14);
        --border:     rgba(255,255,255,.09);
        --card-bg:    rgba(18,26,43,.70);
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
        --modal-bg: rgba(255,255,255,.99);
        --modal-border: rgba(15,23,42,.12);
        --modal-overlay: rgba(15,23,42,.45);
        --modal-panel: rgba(15,23,42,.04);

        /* shared component vars */
        --input-bg:     #ffffff;
        --input-border: rgba(15,23,42,.18);
        --border:       rgba(15,23,42,.11);
        --card-bg:      rgba(255,255,255,.72);
        }



        * {
            box-sizing: border-box
        }
        body{
        margin: 0;
        font-family: var(--font);
        font-size: 14px;
        line-height: 1.6;
        background: var(--bg-gradient);
        color: var(--text);
        -webkit-font-smoothing: antialiased;
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
            min-height: 100vh;
            transition: grid-template-columns .22s cubic-bezier(.4,0,.2,1);
        }
        .app.sidebar-collapsed {
            grid-template-columns: 52px 1fr;
        }

        .sidebar {
            padding: 18px 16px;
            border-right: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(18, 26, 43, .95), rgba(15, 23, 38, .9));
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: padding .22s cubic-bezier(.4,0,.2,1);
        }
        .app.sidebar-collapsed .sidebar {
            padding: 18px 7px;
            align-items: center;
        }

        .brand {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 10px 10px 18px;
            overflow: hidden;
            white-space: nowrap;
            width: 100%;
            transition: padding .22s;
        }
        .app.sidebar-collapsed .brand {
            padding: 10px 0 14px;
            justify-content: center;
        }
        .brand-text {
            overflow: hidden;
            opacity: 1;
            max-width: 200px;
            transition: opacity .15s, max-width .22s cubic-bezier(.4,0,.2,1);
        }
        .app.sidebar-collapsed .brand-text {
            opacity: 0;
            max-width: 0;
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
            padding: 9px 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid transparent;
            color: var(--muted);
            font-size: 13px;
            font-weight: 400;
            letter-spacing: .01em;
            transition: background .12s, color .12s, border-color .12s, padding .22s;
            overflow: hidden;
            white-space: nowrap;
            width: 100%;
        }
        .app.sidebar-collapsed .nav a {
            padding: 9px 0;
            justify-content: center;
            width: 34px;
            border-radius: 8px;
        }
        .nav-label, .nav-badge {
            transition: opacity .12s, max-width .22s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
        }
        .nav-badge { max-width: 80px; }
        .app.sidebar-collapsed .nav-label,
        .app.sidebar-collapsed .nav-badge {
            opacity: 0;
            max-width: 0;
            padding: 0;
        }
        .nav-icon{
        width: 18px;
        height: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        }

        .nav-icon svg{
        width: 18px;
        height: 18px;
        stroke-width: 1.8;
        }
        .app.sidebar-collapsed .nav-icon { margin: 0; }

        .nav a:hover {
            background: rgba(96, 165, 250, .07);
            border-color: rgba(96, 165, 250, .15);
            color: var(--text)
        }

        .nav a.active {
            background: rgba(45, 212, 191, .09);
            border-color: rgba(45, 212, 191, .22);
            color: var(--text);
            font-weight: 500;
        }

        .badge {
            font-size: 10px;
            font-family: var(--font-mono);
            font-weight: 500;
            padding: 2px 7px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .07);
            letter-spacing: .03em;
            color: var(--muted);
        }

        .sidebar .foot {
            margin-top: auto;
            padding: 14px 10px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            width: 100%;
            overflow: hidden;
        }
        .foot-text {
            transition: opacity .15s, max-width .22s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
            max-width: 200px;
            white-space: nowrap;
        }
        .app.sidebar-collapsed .foot-text { opacity: 0; max-width: 0; }
        .app.sidebar-collapsed .sidebar .foot { justify-content: center; padding: 14px 0; }

        /* ── Toggle button ── */
        .sidebar-toggle {
            display: flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
            border: 1px solid var(--line); background: rgba(255,255,255,.04);
            cursor: pointer; color: var(--muted); font-size: 13px;
            transition: background .12s, color .12s, transform .22s;
        }
        .sidebar-toggle:hover { background: rgba(255,255,255,.08); color: var(--text); }
        .app.sidebar-collapsed .sidebar-toggle { transform: rotate(180deg); }
        .sidebar-toggle-wrap {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 0 4px 0; width: 100%;
        }
        .app.sidebar-collapsed .sidebar-toggle-wrap { justify-content: center; }

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
            padding: 6px 10px;
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 999px;
            background: rgba(15, 23, 38, .65);
            color: var(--muted);
            font-size: 12px;
            font-family: var(--font-mono);
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
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, .09);
            background: rgba(255, 255, 255, .05);
            color: var(--text);
            font-size: 13px;
            font-family: var(--font);
            font-weight: 500;
            letter-spacing: .01em;
            transition: background .12s, border-color .12s, filter .1s;
        }

        .btn.primary {
            background: rgba(96, 165, 250, .18);
            border-color: rgba(96, 165, 250, .35)
        }

        .btn.ok {
            background: rgba(45, 212, 191, .14);
            border-color: rgba(45, 212, 191, .30)
        }

        .btn.warn {
            background: rgba(251,191,36,.14);
            border-color: rgba(251,191,36,.30);
            color: var(--text);
        }

        .btn.small {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 8px;
        }

        .btn:disabled {
            opacity: .38;
            cursor: not-allowed;
            filter: none;
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
            margin: 0 0 5px;
            font-size: 13px;
            color: var(--text);
            font-weight: 600;
            letter-spacing: .01em;
        }

        .big {
            font-size: 28px;
            margin: 4px 0 6px;
            font-weight: 700;
            letter-spacing: -.5px;
            font-family: var(--font-mono);
        }

        .sub {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
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
            margin: 20px 2px 10px;
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
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
            font-weight: 500;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            text-align: left
        }

        tr:hover td {
            background: rgba(255, 255, 255, .03)
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 999px;
            background: var(--chip);
            border: 1px solid rgba(255, 255, 255, .07);
            font-size: 11px;
            font-family: var(--font-mono);
            font-weight: 500;
            letter-spacing: .04em;
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
            font-size: 12px;
            line-height: 1.5;
        }

        .panel {
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: rgba(15, 23, 38, .60)
        }

        .panel h2 {
            margin: 0 0 10px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .01em;
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
            border: 1px solid rgba(255, 255, 255, .14);
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
            transition: border-color .15s, box-shadow .15s;
        }

        input:not([type="checkbox"]):focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: rgba(96,165,250,.55) !important;
            box-shadow: 0 0 0 3px rgba(96,165,250,.14);
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
    background: var(--modal-overlay);
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
    border: 1px solid var(--modal-border);
    background: var(--modal-bg);
    color: var(--text);
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
    background: #ffffff;
    border: 1.5px solid rgba(15,23,42,.18);
    color: var(--text);
    box-shadow: 0 1px 3px rgba(15,23,42,.07);
    }

    :root[data-theme="light"] input:not([type="checkbox"]):focus,
    :root[data-theme="light"] select:focus,
    :root[data-theme="light"] textarea:focus {
    border-color: rgba(37,99,235,.50) !important;
    box-shadow: 0 0 0 3px rgba(37,99,235,.10);
    }

    /* seta do select visível no light */
    :root[data-theme="light"] select {
    background-color: #ffffff;
    background-image:
        linear-gradient(45deg, transparent 50%, rgba(15,23,42,.55) 50%),
        linear-gradient(135deg, rgba(15,23,42,.55) 50%, transparent 50%);
    background-position: calc(100% - 18px) 55%, calc(100% - 12px) 55%;
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
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
    background: rgba(255,255,255,.80);
    border: 1px solid rgba(15,23,42,.12);
    color: var(--text);
    }

    /* ── Overrides de padrões hardcoded nas pages ── */

    :root[data-theme="light"] [style*="background:rgba(0,0,0,.62)"],
    :root[data-theme="light"] [style*="background: rgba(0, 0, 0, .62)"] {
        background: rgba(15,23,42,.45) !important;
    }
    :root[data-theme="light"] [style*="background:rgba(18,26,43,.96)"],
    :root[data-theme="light"] [style*="background: rgba(18, 26, 43, .96)"],
    :root[data-theme="light"] [style*="background:rgba(18,26,43,.98)"],
    :root[data-theme="light"] [style*="background: rgba(18, 26, 43, .98)"] {
        background: rgba(255,255,255,.98) !important;
        border-color: rgba(15,23,42,.12) !important;
    }
    :root[data-theme="light"] [style*="background:rgba(0,0,0,.1"],
    :root[data-theme="light"] [style*="background: rgba(0, 0, 0, .1"],
    :root[data-theme="light"] [style*="background:rgba(0,0,0,.12)"],
    :root[data-theme="light"] [style*="background: rgba(0, 0, 0, .12)"],
    :root[data-theme="light"] [style*="background:rgba(0,0,0,.14)"],
    :root[data-theme="light"] [style*="background: rgba(0, 0, 0, .14)"],
    :root[data-theme="light"] [style*="background:rgba(0,0,0,.16)"],
    :root[data-theme="light"] [style*="background: rgba(0, 0, 0, .16)"],
    :root[data-theme="light"] [style*="background:rgba(0,0,0,.18)"],
    :root[data-theme="light"] [style*="background: rgba(0, 0, 0, .18)"],
    :root[data-theme="light"] [style*="background:rgba(0,0,0,.22)"],
    :root[data-theme="light"] [style*="background: rgba(0, 0, 0, .22)"] {
        background: rgba(15,23,42,.04) !important;
        border-color: rgba(15,23,42,.10) !important;
    }
    :root[data-theme="light"] [style*="background:rgba(255,255,255,.06)"],
    :root[data-theme="light"] [style*="background: rgba(255, 255, 255, .06)"],
    :root[data-theme="light"] [style*="background:rgba(255,255,255,.08)"],
    :root[data-theme="light"] [style*="background: rgba(255, 255, 255, .08)"],
    :root[data-theme="light"] [style*="background:rgba(255,255,255,.14)"],
    :root[data-theme="light"] [style*="background: rgba(255, 255, 255, .14)"] {
        background: rgba(15,23,42,.05) !important;
        border-color: rgba(15,23,42,.10) !important;
    }
    :root[data-theme="light"] [style*="background:#0b1220"] {
        background: #f8faff !important;
        border-color: rgba(15,23,42,.12) !important;
    }
    :root[data-theme="light"] [style*="border:1px solid rgba(255,255,255,.10)"],
    :root[data-theme="light"] [style*="border:1px solid rgba(255,255,255,.08)"],
    :root[data-theme="light"] [style*="border:1px solid rgba(255,255,255,.12)"] {
        border-color: rgba(15,23,42,.12) !important;
    }
    :root[data-theme="light"] .chip {
        background: rgba(15,23,42,.06);
        border-color: rgba(15,23,42,.12);
        color: var(--muted);
    }
    :root[data-theme="light"] .chip.ok   { background: rgba(14,165,163,.10); border-color: rgba(14,165,163,.25); color: #0a6362; }
    :root[data-theme="light"] .chip.warn { background: rgba(217,119,6,.10);  border-color: rgba(217,119,6,.25);  color: #9a5c04; }
    :root[data-theme="light"] .chip.bad  { background: rgba(225,29,72,.10);  border-color: rgba(225,29,72,.25);  color: #a01535; }
    :root[data-theme="light"] .tag        { background: rgba(15,23,42,.06); border-color: rgba(15,23,42,.1); }
    :root[data-theme="light"] .tag.ok     { background: rgba(14,165,163,.10); border-color: rgba(14,165,163,.25); color: #0a6362; }
    :root[data-theme="light"] .tag.warn   { background: rgba(217,119,6,.10);  border-color: rgba(217,119,6,.25);  color: #9a5c04; }
    :root[data-theme="light"] .tag.bad    { background: rgba(225,29,72,.10);  border-color: rgba(225,29,72,.25);  color: #a01535; }
    :root[data-theme="light"] .pill {
        background: rgba(255,255,255,.80);
        border-color: rgba(15,23,42,.12);
        color: var(--muted);
    }
    :root[data-theme="light"] .badge {
        background: rgba(15,23,42,.06);
        border-color: rgba(15,23,42,.10);
    }
    :root[data-theme="light"] .foot { border-top-color: var(--line); }
    :root[data-theme="light"] .sidebar-toggle {
        background: rgba(15,23,42,.05);
        border-color: rgba(15,23,42,.12);
        color: var(--muted);
    }
    :root[data-theme="light"] .sidebar-toggle:hover {
        background: rgba(15,23,42,.09);
        color: var(--text);
    }
    :root[data-theme="light"] .nav a.active {
        background: rgba(14,165,163,.08);
        border-color: rgba(14,165,163,.20);
    }
    :root[data-theme="light"] .cs-menu {
        background: rgba(255,255,255,.98);
        border-color: rgba(15,23,42,.12);
        box-shadow: 0 10px 30px rgba(15,23,42,.12);
    }
    :root[data-theme="light"] .cs-btn {
        background: var(--field-bg);
        border-color: var(--field-border);
        color: var(--text);
    }
    :root[data-theme="light"] .cs-opt:hover {
        background: rgba(37,99,235,.06);
        border-color: rgba(37,99,235,.15);
    }
    :root[data-theme="light"] .cs-search {
        background: var(--field-bg);
        border-color: var(--field-border);
        color: var(--text);
    }
    :root[data-theme="light"] .kcard {
        background: rgba(255,255,255,.90);
        border-color: rgba(15,23,42,.12);
    }
    :root[data-theme="light"] .kanban-col {
        background: rgba(15,23,42,.03);
        border-color: rgba(15,23,42,.10);
    }

    /* painéis dentro de modais no light */
    :root[data-theme="light"] .modal-card .panel,
    :root[data-theme="light"] .modal-card .two > div {
        background: rgba(15,23,42,.04);
        border-color: rgba(15,23,42,.09);
        color: var(--text);
    }
    :root[data-theme="light"] .modal-card .muted { color: var(--muted); }
    :root[data-theme="light"] .modal-card input,
    :root[data-theme="light"] .modal-card select,
    :root[data-theme="light"] .modal-card textarea {
        background: rgba(255,255,255,.9);
        border-color: rgba(15,23,42,.15);
        color: var(--text);
    }
    :root[data-theme="light"] .modal-card .modal-header {
        border-bottom-color: rgba(15,23,42,.10);
    }

    /* ── Botão toggle de tema ── */
    .btn-theme-toggle {
        padding: 7px 10px;
        border-radius: 10px;
        font-size: 15px;
        line-height: 1;
        min-width: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background .15s, transform .1s;
    }
    .btn-theme-toggle:hover { transform: rotate(12deg); }
    :root[data-theme="light"] .btn-theme-toggle {
        background: rgba(15,23,42,.07);
        border-color: rgba(15,23,42,.12);
    }

    .search-icon{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width: 18px;
    height: 18px;
    margin-right: 8px;
    }
    .search-icon svg{
    width: 18px;
    height: 18px;
    stroke-width: 1.8;
    opacity: .9;
    }

    .theme-icon-light{ display:none; }
    :root[data-theme="light"] .theme-icon-dark{ display:none; }
    :root[data-theme="light"] .theme-icon-light{ display:inline-flex; }

    .btn-icon{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    }
    .btn-icon svg{
    width: 18px;
    height: 18px;
    stroke-width: 1.8;
    }

    </style>
</head>

<body>
@php
        $org = $org ?? 'Exemplo';
        $framework = $framework ?? 'QNRCS';
        $fwVersion = $fwVersion ?? '2.1';

        // 👇 Adicionámos a coluna 'perm' com a permissão exigida para cada menu
        $navItems = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'badge' => 'RF18', 'perm' => null], // Sem restrição
            ['route' => 'assets', 'label' => 'Ativos', 'badge' => 'RF1', 'perm' => 'assets.view'],
            ['route' => 'compliance', 'label' => 'Compliance', 'badge' => 'RF4', 'perm' => 'compliance.view'],
            ['route' => 'docs', 'label' => 'Documentos & Evidências', 'badge' => 'RF2–RF3', 'perm' => 'docs.view'],
            ['route' => 'assessments', 'label' => 'Avaliações', 'badge' => 'RF5–RF6', 'perm' => 'assessments.view'],
            ['route' => 'risks', 'label' => 'Riscos', 'badge' => 'RF7–RF9', 'perm' => 'risk.view'],
            ['route' => 'treatment', 'label' => 'Planos de Tratamento', 'badge' => 'RF10–RF11', 'perm' => 'treatment.view'],
            ['route' => 'questionnaire', 'label' => 'Questionário', 'badge' => 'RF13', 'perm' => 'questionnaire.view'],
            ['route' => 'chat', 'label' => 'Chat de Governação', 'badge' => 'RF14–RF15', 'perm' => 'chat.use'],
            ['route' => 'audit', 'label' => 'Auditoria', 'badge' => 'RNF5', 'perm' => 'audit.view'],
            ['route' => 'rbac', 'label' => 'RBAC', 'badge' => 'RF19', 'perm' => 'rbac.manage'], 
            ['route' => 'relatorios-cncs', 'label' => 'Relatórios CNCS', 'badge' => 'RF20', 'perm' => 'compliance.view'],
        ];
    @endphp

    <div class="app">
        <aside class="sidebar">
            <div class="brand">
                <div class="logo" style="flex-shrink:0;"></div>
                <div class="brand-text">
                    <h1>Techbase GRC • NIS2</h1>
                    <p>Conformidade • Risco • Evidências</p>
                </div>
            </div>

<nav class="nav"> 
            @php 
                // 1. Garantir que vamos buscar as permissões ao sítio certo da sessão
                $tbUser = session('tb_user');
                // 2. Forçar que seja sempre um array simples (lista de strings)
                $userPerms = is_array($tbUser) && isset($tbUser['permissions']) 
                             ? (array) $tbUser['permissions'] 
                             : [];
            @endphp

            @foreach($navItems as $item)
                @php
                    // 👇 Condição corrigida:
                    // Se o item exige permissão E essa permissão NÃO ESTÁ no array de permissões do utilizador
                    if (!empty($item['perm']) && !in_array($item['perm'], $userPerms, true)) {
                        continue; // Salta este item!
                    }

                    $navIcons = [
                        'dashboard'       => 'layout-dashboard',
                        'assets'          => 'server',
                        'compliance'      => 'check-square',
                        'docs'            => 'file-text',
                        'assessments'     => 'clipboard-check',
                        'risks'           => 'alert-triangle',
                        'treatment'       => 'list-checks',
                        'questionnaire'   => 'file-pen-line',
                        'chat'            => 'messages-square',
                        'audit'           => 'scroll-text',
                        'rbac'            => 'shield-check',
                        'relatorios-cncs' => 'file-output',
                    ];
                    $icon = $navIcons[$item['route']] ?? '·';
                @endphp
                
                <a href="{{ route($item['route']) }}"
                    class="{{ request()->routeIs($item['route']) ? 'active' : '' }}"
                    title="{{ $item['label'] }}">
                    <span style="display:flex;align-items:center;gap:9px;overflow:hidden;">
                        <span class="nav-icon">
                            <i data-lucide="{{ $icon }}"></i>
                        </span>
                        <span class="nav-label">{{ $item['label'] }}</span>
                    </span>
                    <span class="badge nav-badge">{{ $item['badge'] }}</span>
                </a>
            @endforeach
            </nav>

            <div class="foot">
                <div class="sidebar-toggle-wrap">
                    <span class="foot-text" style="display:flex;gap:12px;">
                        <span>Estado: <b style="color:var(--ok)">Online</b></span>
                        <span>v0.1</span>
                    </span>
                    <button class="sidebar-toggle" id="sidebarToggle" title="Minimizar sidebar" aria-label="Minimizar sidebar">‹</button>
                </div>
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
                    <span class="search-icon">
                        <i data-lucide="search"></i>
                    </span>
                    <input placeholder="Pesquisar ativos, controlos, evidências, riscos..." />
                </div>

                <div class="actions">
                    <button class="btn ok" type="button">+ Nova avaliação</button>
                    <button class="btn primary" type="button">Upload documento</button>
                    <button class="btn" type="button">Perfil</button>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button class="btn" type="submit">Sair</button>
                    </form>

                    <button id="btnThemeToggle" class="btn btn-theme-toggle" type="button" title="Alternar tema claro/escuro" aria-label="Alternar tema">
                        <span class="theme-icon theme-icon-dark">
                            <i data-lucide="moon"></i>
                        </span>
                        <span class="theme-icon theme-icon-light">
                            <i data-lucide="sun"></i>
                        </span>
                    </button>

                </div>
            </header>

            <div style="margin-top:14px">
                @yield('content')
            </div>
        </main>
    </div>


    <script>
    (function () {
        const KEY = 'techbase_sidebar';
        const app = document.querySelector('.app');
        const btn = document.getElementById('sidebarToggle');

        function applyState(collapsed) {
            if (!app) return;
            app.classList.toggle('sidebar-collapsed', collapsed);
            if (btn) btn.title = collapsed ? 'Expandir sidebar' : 'Minimizar sidebar';
            localStorage.setItem(KEY, collapsed ? '1' : '0');
        }

        // Restore state immediately (before paint) to avoid flash
        const saved = localStorage.getItem(KEY);
        if (saved === '1') applyState(true);

        document.addEventListener('DOMContentLoaded', function () {
            const b = document.getElementById('sidebarToggle');
            if (b) b.addEventListener('click', function () {
                const isCollapsed = document.querySelector('.app').classList.contains('sidebar-collapsed');
                applyState(!isCollapsed);
            });
        });
    })();
    </script>
    @vite(['resources/js/app.js', 'resources/js/theme.js', 'resources/js/audit-store.js'])
    {{-- pdf.js: renderização com text layer (chat modal + highlight) --}}
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.min.mjs" type="module" id="pdfjs-script"></script>
    <script>
        // Expõe pdfjsLib globalmente para uso em módulos não-ES (chat.js, docs.js)
        // O script acima é type="module" então usamos dynamic import como bridge
        window.__pdfjs_ready = import('https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.min.mjs').then(mod => {
            window.pdfjsLib = mod;
            window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.worker.min.mjs';
            return mod;
        });
    </script>
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


        function renderLucideIcons() {
            if (window.lucide) window.lucide.createIcons();
        }

        document.addEventListener('DOMContentLoaded', renderLucideIcons);
        window.addEventListener('load', renderLucideIcons);

        // Se o teu mock muda páginas sem reload, isto ajuda muito:
        document.addEventListener('click', (e) => {
            if (e.target.closest('.sidebar a, .nav-item, [data-page], [data-nav]')) {
            setTimeout(renderLucideIcons, 0);
            }
        });

    </script>
    @vite(['resources/js/audit-store.js'])
</body>

</html>