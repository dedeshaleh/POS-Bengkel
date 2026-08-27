<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e8edf4;
            --panel: #ffffff;
            --page: #f1f5f9;
            --brand: #f97316;
            --brand-dark: #ea580c;
            --brand-light: #fff7ed;
            --danger: #dc2626;
            --success: #16a34a;
            --radius: 16px;
            --radius-sm: 11px;
            --shadow-sm: 0 1px 2px rgba(15, 23, 42, .04), 0 1px 3px rgba(15, 23, 42, .06);
            --shadow-md: 0 4px 6px -1px rgba(15, 23, 42, .06), 0 12px 28px -8px rgba(15, 23, 42, .12);
            --shadow-lg: 0 20px 48px -12px rgba(15, 23, 42, .22);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Inter", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 600px at 100% -10%, rgba(249, 115, 22, .06), transparent 60%),
                radial-gradient(900px 500px at -10% 0%, rgba(59, 130, 246, .05), transparent 55%),
                var(--page);
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
        a { color: inherit; text-decoration: none; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; border: 2px solid transparent; background-clip: content-box; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; background-clip: content-box; }
        .shell { display: grid; grid-template-columns: 272px minmax(0, 1fr); min-height: 100vh; }
        .shell.sidebar-collapsed { grid-template-columns: 76px minmax(0, 1fr); }
        .sidebar { background: linear-gradient(185deg, #0b1220 0%, #111827 45%, #1e2533 100%); color: #e2e8f0; padding: 22px 14px; border-right: 1px solid rgba(148, 163, 184, .08); overflow-x: hidden; position: relative; z-index: 50; }
        .shell.sidebar-collapsed .sidebar { padding-left: 12px; padding-right: 12px; overflow: visible; }
        .shell.sidebar-collapsed .sidebar .nav { gap: 4px; }
        .brand { display: flex; align-items: center; gap: 12px; line-height: 1.1; margin-bottom: 26px; padding: 4px 6px; }
        .brand-logo {
            flex: 0 0 auto;
            width: 42px;
            height: 42px;
            border-radius: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            color: #fff;
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            box-shadow: 0 8px 18px -6px rgba(249, 115, 22, .6);
        }
        .brand-text { display: flex; flex-direction: column; font-family: "Plus Jakarta Sans", "Inter", sans-serif; font-size: 19px; font-weight: 800; letter-spacing: -.01em; color: #f8fafc; }
        .brand-text small { font-size: 10.5px; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: #f59e0b; margin-top: 2px; }
        .shell.sidebar-collapsed .brand { justify-content: center; gap: 0; margin-bottom: 20px; }
        .shell.sidebar-collapsed .brand-text { display: none; }
        .nav {
            display: grid;
            gap: 8px;
        }
        .nav-group {
            display: grid;
            gap: 6px;
        }
        .nav-item-outer {
            min-width: 0;
        }
        .nav a {
            color: #cbd5e1;
            padding: 10px 10px;
            border-radius: 9px;
            font-weight: 650;
            display: flex;
            align-items: flex-start;
            gap: 9px;
            min-width: 0;
            line-height: 1.22;
        }
        .nav a { transition: background .15s ease, color .15s ease; }
        .nav a:hover { background: rgba(148, 163, 184, .12); color: #fff; }
        .nav a.active { background: linear-gradient(90deg, rgba(249, 115, 22, .22), rgba(249, 115, 22, .06)); color: #fff; box-shadow: inset 3px 0 0 var(--brand); }
        .nav a.loading {
            opacity: .85;
            pointer-events: none;
        }
        .nav a .spinner {
            width: 13px;
            height: 13px;
            border: 2px solid rgba(203, 213, 225, .5);
            border-top-color: #fff;
            border-radius: 999px;
            display: inline-block;
            margin-right: 8px;
            animation: spin .8s linear infinite;
        }
        .nav .menu-parent {
            width: 100%;
            border: 0;
            background: transparent;
            color: #cbd5e1;
            padding: 10px 10px;
            border-radius: 9px;
            font-weight: 650;
            text-align: left;
            display: flex;
            align-items: flex-start;
            gap: 9px;
            cursor: pointer;
            min-width: 0;
            line-height: 1.22;
        }
        .nav .menu-parent { transition: background .15s ease, color .15s ease; }
        .nav .menu-parent:hover { background: rgba(148, 163, 184, .12); color: #fff; }
        .nav .menu-parent.active { background: linear-gradient(90deg, rgba(249, 115, 22, .22), rgba(249, 115, 22, .06)); color: #fff; box-shadow: inset 3px 0 0 var(--brand); }
        .nav a i,
        .nav .menu-parent i.menu-icon {
            width: 18px;
            min-width: 18px;
            margin-right: 0;
            text-align: center;
            line-height: 1.25;
            padding-top: 1px;
        }
        .nav a > span,
        .nav .menu-parent > span {
            min-width: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 3px;
            white-space: normal;
            overflow-wrap: break-word;
            word-break: normal;
            hyphens: auto;
        }
        .nav .menu-parent i.chev { margin-left: auto; margin-right: 0; transition: transform .2s ease; padding-top: 2px; }
        .nav .menu-parent[aria-expanded="true"] i.chev { transform: rotate(90deg); }
        .nav .submenu {
            display: none;
            margin-top: 6px;
            gap: 6px;
        }
        .nav .submenu.open {
            display: grid;
        }
        .shell:not(.sidebar-collapsed) .nav .submenu .nav-group {
            gap: 4px;
        }
        .shell:not(.sidebar-collapsed) .nav .submenu .nav a,
        .shell:not(.sidebar-collapsed) .nav .submenu .nav .menu-parent {
            font-size: 13px;
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .shell.sidebar-collapsed .nav a { text-align: center; justify-content: center; align-items: center; gap: 0; }
        .shell.sidebar-collapsed .nav .menu-parent { justify-content: center; align-items: center; gap: 0; }
        .shell.sidebar-collapsed .nav a > span { display: none; }
        .shell.sidebar-collapsed .nav .menu-parent > span { display: none; }
        .shell.sidebar-collapsed .nav .menu-parent i.chev { display: none; }
        .shell.sidebar-collapsed .nav a i {
            margin-right: 0;
            padding-top: 0;
        }
        .shell.sidebar-collapsed .nav .menu-parent i.menu-icon {
            padding-top: 0;
        }
        .shell.sidebar-collapsed .nav .submenu { display: none !important; }
        .main { padding: 28px 30px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; background: rgba(255, 255, 255, .8); backdrop-filter: saturate(180%) blur(8px); border: 1px solid var(--line); border-radius: var(--radius); padding: 14px 18px; box-shadow: var(--shadow-sm); position: sticky; top: 14px; z-index: 20; }
        .top-actions { display: flex; align-items: center; gap: 10px; }
        .top-left { display: grid; gap: 8px; min-width: 0; }
        .top-line { display: flex; align-items: center; gap: 10px; }
        .burger {
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 11px;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #334155;
            transition: background .15s ease, color .15s ease, border-color .15s ease;
        }
        .burger:hover { background: var(--brand-light); color: var(--brand-dark); border-color: #fed7aa; }
        .sidebar-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }
        .sidebar-close {
            display: none;
            border: 0;
            background: transparent;
            color: #cbd5e1;
            font-size: 18px;
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 8px;
        }
        .sidebar-close:hover { background: #374151; color: #fff; }
        .history { display: flex; gap: 8px; overflow-x: auto; white-space: nowrap; max-width: 100%; padding-bottom: 2px; }
        .history a {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            background: #fff;
            transition: all .15s ease;
        }
        .history a:hover { border-color: #fed7aa; color: var(--brand-dark); }
        .history a.active { background: var(--brand-light); border-color: #fdba74; color: #c2410c; }
        h1 { font-family: "Plus Jakarta Sans", "Inter", sans-serif; font-size: 27px; margin: 0; letter-spacing: -.02em; font-weight: 800; }
        h2 { font-family: "Plus Jakarta Sans", "Inter", sans-serif; font-size: 18px; margin: 0 0 14px; letter-spacing: -.01em; font-weight: 700; }
        .muted { color: var(--muted); }
        .grid { display: grid; gap: 18px; }
        .stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .two { grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr); align-items: start; }
        .panel, .card { background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow-sm); transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease; }
        .card:hover { box-shadow: var(--shadow-md); }
        .stat { position: relative; overflow: hidden; }
        .stat::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 4px; background: linear-gradient(180deg, var(--brand), var(--brand-dark)); }
        .stat:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .stat .muted { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .stat strong { display: block; font-family: "Plus Jakarta Sans", "Inter", sans-serif; font-size: 28px; margin-top: 8px; letter-spacing: -.02em; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border-bottom: 1px solid var(--line); padding: 12px 10px; text-align: left; vertical-align: top; }
        .table tbody tr { transition: background .12s ease; }
        .table tbody tr:hover { background: #f8fafc; }
        .table th { color: var(--muted); font-size: 11.5px; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; }
        .form-grid { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        label { display: grid; gap: 6px; font-weight: 650; font-size: 13px; color: #334039; }
        input, select, textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            font: inherit;
            background: #fff;
            color: var(--ink);
            outline: none;
            transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #fb923c;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, .14);
        }
        select {
            appearance: none;
            -webkit-appearance: none;
            padding-right: 40px;
            cursor: pointer;
            background-image:
                linear-gradient(45deg, transparent 50%, #64748b 50%),
                linear-gradient(135deg, #64748b 50%, transparent 50%),
                linear-gradient(to right, #e2e8f0, #e2e8f0);
            background-position:
                calc(100% - 20px) 50%,
                calc(100% - 14px) 50%,
                calc(100% - 36px) 50%;
            background-size:
                6px 6px,
                6px 6px,
                1px 22px;
            background-repeat: no-repeat;
        }
        select:hover {
            border-color: #cbd5e1;
            background-color: #fff7ed;
        }
        select:disabled {
            cursor: not-allowed;
            color: #94a3b8;
            background-color: #f8fafc;
        }
        textarea { min-height: 82px; resize: vertical; }
        .full { grid-column: 1 / -1; }
        .btn { border: 0; border-radius: 11px; padding: 10px 16px; background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%); color: #fff; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 6px 16px -6px rgba(249, 115, 22, .55); transition: transform .12s ease, box-shadow .15s ease, filter .15s ease; }
        .btn:hover { filter: brightness(1.05); box-shadow: 0 10px 22px -8px rgba(249, 115, 22, .65); transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        .btn.secondary { background: #1e293b; box-shadow: 0 6px 16px -8px rgba(15, 23, 42, .5); }
        .btn.secondary:hover { background: #0f172a; }
        .btn.ghost { background: #fff; color: #334155; border: 1px solid var(--line); box-shadow: var(--shadow-sm); }
        .btn.ghost:hover { background: #f8fafc; border-color: #cbd5e1; filter: none; }
        .btn.loading,
        button.loading {
            opacity: .78;
            pointer-events: none;
        }
        .btn .spinner,
        button .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, .45);
            border-top-color: #fff;
            border-radius: 999px;
            display: inline-block;
            margin-right: 8px;
            animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .badge { display: inline-flex; align-items: center; gap: 5px; border-radius: 999px; padding: 4px 11px; background: var(--brand-light); color: #c2410c; font-size: 12px; font-weight: 700; border: 1px solid rgba(249, 115, 22, .18); }
        .badge.danger { background: #fef2f2; color: var(--danger); border-color: rgba(220, 38, 38, .18); }
        .badge.success { background: #f0fdf4; color: var(--success); border-color: rgba(22, 163, 74, .18); }
        .badge.muted { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }
        .bell-wrap { position: relative; }
        .bell-btn { position: relative; width: 40px; height: 40px; padding: 0; display: grid; place-items: center; }
        .bell-btn .fa-bell { font-size: 18px; }
        .bell-badge { position: absolute; top: 4px; right: 5px; min-width: 18px; height: 18px; border-radius: 999px; background: var(--danger); color: #fff; font-size: 10px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; padding: 0 5px; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,.12); }
        .bell-badge.empty { display: none; }
        .bell-dropdown { position: absolute; top: calc(100% + 10px); right: 0; width: 360px; background: #fff; border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow-lg); opacity: 0; visibility: hidden; transform: translateY(-8px); transition: all .18s ease; z-index: 100; }
        .bell-wrap.open .bell-dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
        .bell-head { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid var(--line); }
        .bell-head strong { font-size: 15px; }
        .bell-head .muted { font-size: 12px; }
        .bell-body { max-height: 360px; overflow-y: auto; padding: 8px; }
        .bell-foot { display: flex; gap: 10px; padding: 10px 14px; border-top: 1px solid var(--line); }
        .bell-foot a { font-size: 13px; font-weight: 650; color: var(--brand-dark); padding: 6px 10px; border-radius: 8px; background: var(--brand-light); }
        .bell-foot a:hover { background: #ffedd5; }
        .bell-empty { text-align: center; padding: 26px; color: var(--muted); font-size: 13px; }
        .bell-item { display: flex; gap: 12px; align-items: flex-start; padding: 10px 12px; border-radius: var(--radius-sm); transition: background .12s ease; }
        .bell-item:hover { background: #f8fafc; }
        .bell-item i { width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; flex: 0 0 auto; font-size: 13px; }
        .bell-item.danger i { background: #fef2f2; color: var(--danger); }
        .bell-item.warning i { background: #fffbeb; color: #d97706; }
        .bell-item.info i { background: #eff6ff; color: #2563eb; }
        .bell-item .line { font-size: 13px; line-height: 1.4; }
        .bell-item .line strong { color: var(--ink); }
        .bell-item .line .muted { font-size: 11px; display: block; margin-top: 2px; }
        @media (max-width: 480px) { .bell-dropdown { width: calc(100vw - 32px); right: -70px; } }
        .badge.success { background: #ecfdf5; color: #047857; border-color: rgba(16, 185, 129, .2); }
        .menu-progress {
            display: inline-flex;
            margin-left: 0;
            width: max-content;
            max-width: 100%;
            font-size: 10px;
            line-height: 1;
            border-radius: 999px;
            padding: 3px 6px;
            background: #fef3c7;
            color: #92400e;
            font-weight: 700;
            white-space: nowrap;
        }
        .danger { color: var(--danger); font-weight: 750; }
        .notice { border-left: 4px solid var(--brand); background: var(--brand-light); padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; }
        .row-actions { display: flex; gap: 8px; align-items: end; }
        .loading-indicator {
            position: fixed;
            right: 14px;
            bottom: 14px;
            z-index: 60;
            display: none;
            align-items: center;
            gap: 8px;
            background: #0f172a;
            color: #f8fafc;
            border-radius: 10px;
            padding: 10px 12px;
            box-shadow: 0 10px 30px rgba(2, 6, 23, .35);
            font-size: 12px;
            max-width: min(88vw, 340px);
        }
        .loading-indicator.show { display: inline-flex; }
        .loading-indicator .spinner {
            width: 13px;
            height: 13px;
            border: 2px solid rgba(248, 250, 252, .35);
            border-top-color: #fff;
            border-radius: 999px;
            animation: spin .8s linear infinite;
            margin-right: 0;
        }

        /* ---- Flyout (D365-style) ---- */
        .sidebar .flyout {
            display: none !important;
        }
        .shell.sidebar-collapsed .sidebar .flyout {
            position: fixed;
            left: 76px;
            min-width: 240px;
            max-width: 340px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 6px;
            box-shadow: 0 8px 32px rgba(0,0,0,.45);
            z-index: 10000;
            pointer-events: auto;
        }
        .shell.sidebar-collapsed .sidebar .flyout.open {
            display: block !important;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-bottom: 1px solid #334155;
            font-weight: 700;
            font-size: 14px;
            color: #f1f5f9;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-header i {
            width: 16px;
            text-align: center;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-body {
            max-height: 65vh;
            overflow-y: auto;
            padding: 4px 0;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-node {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-row,
        .shell.sidebar-collapsed .sidebar .flyout .f-row-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 6px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 13px;
            background: transparent;
            border: 0;
            width: 100%;
            cursor: pointer;
            text-decoration: none;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-row-wrap {
            padding: 0;
            gap: 0;
            overflow: hidden;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-link {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 0;
            padding: 8px 10px 8px 14px;
            color: inherit;
            text-decoration: none;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            align-self: stretch;
            border: 0;
            background: transparent;
            color: inherit;
            cursor: pointer;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-row i,
        .shell.sidebar-collapsed .sidebar .flyout .f-link i {
            width: 14px;
            min-width: 14px;
            text-align: center;
            font-size: 12px;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-row span,
        .shell.sidebar-collapsed .sidebar .flyout .f-link span {
            display: inline !important;
            min-width: 0;
            overflow-wrap: break-word;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-row:hover,
        .shell.sidebar-collapsed .sidebar .flyout .f-row.active,
        .shell.sidebar-collapsed .sidebar .flyout .f-row-wrap:hover,
        .shell.sidebar-collapsed .sidebar .flyout .f-row-wrap.active {
            background: #374151;
            color: #fff;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-chev {
            transition: transform .2s;
            font-size: 10px;
        }
        .shell.sidebar-collapsed .sidebar .flyout [aria-expanded="true"] .f-chev {
            transform: rotate(90deg);
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-children {
            display: none;
            width: 100%;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-children.open {
            display: block;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-children .f-row,
        .shell.sidebar-collapsed .sidebar .flyout .f-children .f-link {
            padding-left: 30px;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-children .f-children .f-row,
        .shell.sidebar-collapsed .sidebar .flyout .f-children .f-children .f-link {
            padding-left: 46px;
        }
        .shell.sidebar-collapsed .sidebar .flyout .f-children .f-children .f-children .f-row,
        .shell.sidebar-collapsed .sidebar .flyout .f-children .f-children .f-children .f-link {
            padding-left: 62px;
        }
        .ft-backdrop {
            position: fixed;
            inset: 0;
            z-index: 40;
            display: none;
        }
        .ft-backdrop.show {
            display: block;
        }

        .mobile-backdrop { display: none; }
        @media (max-width: 1100px) {
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                width: 280px;
                z-index: 60;
                transform: translateX(-100%);
                transition: transform .25s ease;
                box-shadow: var(--shadow-lg);
            }
            .shell.mobile-sidebar-open .sidebar { transform: translateX(0); }
            .shell.mobile-sidebar-open .mobile-backdrop { display: block; position: fixed; inset: 0; background: rgba(2, 6, 23, .45); z-index: 55; }
            .sidebar-close { display: inline-flex; }
            .main { padding: 16px; }
            .topbar { top: 8px; padding: 12px 14px; }
            .stats, .two, .form-grid { grid-template-columns: 1fr; }
            .table { display: block; overflow-x: auto; white-space: nowrap; }
            h1 { font-size: 21px; }
            .top-actions .btn span { display: none; }
            .top-actions .btn { padding: 10px 12px; }
        }
        @media (max-width: 560px) {
            .main { padding: 12px; }
            .panel, .card { padding: 16px; }
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $roleIds = $user->roles()->pluck('roles.id');
        if ($roleIds->isEmpty() && $user->role_id) {
            $roleIds = collect([$user->role_id]);
        }

        $allowedMenuIds = \App\Models\RolePermission::query()
            ->whereIn('role_id', $roleIds)
            ->where('can_read', true)
            ->pluck('menu_id')
            ->unique();

        $allowedFlatMenus = \App\Models\Menu::query()
            ->whereIn('id', $allowedMenuIds)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('id');

        $childrenMap = [];
        foreach ($allowedFlatMenus as $menu) {
            $parentKey = $menu->parent_id ?: 0;
            $childrenMap[$parentKey] ??= [];
            $childrenMap[$parentKey][] = $menu;
        }

        $requestPath = trim(request()->path(), '/');
        $isMenuActive = function ($menu) use ($requestPath) {
            $menuPath = trim((string) $menu->url, '/');
            if ($menuPath === '' && $requestPath === '') {
                return true;
            }
            return $menuPath !== '' && request()->is($menuPath);
        };

        $hasActiveDescendant = function ($menuId) use (&$hasActiveDescendant, $childrenMap, $isMenuActive) {
            $children = $childrenMap[$menuId] ?? [];
            foreach ($children as $child) {
                if ($isMenuActive($child) || $hasActiveDescendant($child->id)) {
                    return true;
                }
            }
            return false;
        };

        $renderFlyoutTree = function ($parentId = 0, $depth = 0) use (&$renderFlyoutTree, $childrenMap, $isMenuActive, $hasActiveDescendant) {
            $nodes = $childrenMap[$parentId] ?? [];
            if (empty($nodes)) {
                return '';
            }
            $html = '';
            foreach ($nodes as $node) {
                $children = $childrenMap[$node->id] ?? [];
                $active = $isMenuActive($node);
                $activeDesc = $hasActiveDescendant($node->id);
                $open = $active || $activeDesc;
                $icon = $node->icon ?: 'fa-regular fa-circle';
                $label = e($node->name);
                $hasKids = ! empty($children);

                $progressHtml2 = $node->is_progress ? '<span class="menu-progress">On Progress</span>' : '';

                if ($hasKids) {
                    $url = trim((string) $node->url);
                    $href = $url !== '' ? e($node->url) : '#';
                    $html .= '<div class="f-node">';
                    $html .= '<div class="f-row-wrap' . ($active ? ' active' : '') . '">';
                    $html .= '<a class="f-link" href="' . $href . '"><i class="' . e($icon) . '"></i><span>' . $label . $progressHtml2 . '</span></a>';
                    $html .= '<button type="button" class="f-toggle" data-ft-toggle data-no-loading aria-expanded="' . ($open ? 'true' : 'false') . '" aria-label="Toggle ' . $label . '">';
                    $html .= '<i class="fa-solid fa-chevron-right f-chev"></i>';
                    $html .= '</button>';
                    $html .= '</div>';
                    $html .= '<div class="f-children ' . ($open ? 'open' : '') . '">';
                    $html .= $renderFlyoutTree($node->id, $depth + 1);
                    $html .= '</div></div>';
                } else {
                    $html .= '<a class="f-row' . ($active ? ' active' : '') . '" href="' . e($node->url) . '">';
                    $html .= '<i class="' . e($icon) . '"></i><span>' . $label . $progressHtml2 . '</span>';
                    $html .= '</a>';
                }
            }
            return $html;
        };

        $renderMenuTree = function ($parentId = 0, $depth = 0) use (&$renderMenuTree, $childrenMap, $isMenuActive, $hasActiveDescendant, $renderFlyoutTree) {
            $nodes = $childrenMap[$parentId] ?? [];
            if (empty($nodes)) {
                return;
            }

            echo '<div class="nav-group">';
            foreach ($nodes as $node) {
                $children = $childrenMap[$node->id] ?? [];
                $active = $isMenuActive($node);
                $activeDesc = $hasActiveDescendant($node->id);
                $open = $active || $activeDesc;
                $padding = 12 + ($depth * 20);
                $icon = $node->icon ?: 'fa-regular fa-circle';
                $label = e($node->name);
                $progressHtml = $node->is_progress ? '<span class="menu-progress">On Progress</span>' : '';

                if (! empty($children)) {
                    echo '<div class="nav-item-outer">';
                    echo '<div class="menu-group" data-menu-group>';
                    echo '<button type="button" class="menu-parent ' . ($open ? 'active' : '') . '" data-menu-toggle data-no-loading aria-expanded="' . ($open ? 'true' : 'false') . '" style="padding-left:' . $padding . 'px">';
                    echo '<i class="menu-icon ' . e($icon) . '"></i><span>' . $label . $progressHtml . '</span><i class="fa-solid fa-chevron-right chev"></i>';
                    echo '</button>';
                    echo '<div class="submenu ' . ($open ? 'open' : '') . '" data-submenu>';
                    $renderMenuTree($node->id, $depth + 1);
                    echo '</div></div>'; // close menu-group
                    // Flyout for collapsed mode - tree container
                    echo '<div class="flyout" data-flyout data-flyout-parent="' . $node->id . '">';
                    echo '<div class="f-header"><i class="' . e($icon) . '"></i><span>' . $label . '</span></div>';
                    echo '<div class="f-body">' . $renderFlyoutTree($node->id) . '</div>';
                    echo '</div>';
                    echo '</div>'; // close nav-item-outer
                } else {
                    echo '<div class="nav-item-outer">';
                    echo '<a class="nav-link-wrap ' . ($active ? 'active' : '') . '" href="' . e($node->url) . '" style="padding-left:' . $padding . 'px">';
                    echo '<i class="' . e($icon) . '"></i><span>' . $label . $progressHtml . '</span>';
                    echo '</a>';
                    // Flyout for collapsed mode - leaf
                    echo '<div class="flyout" data-flyout>';
                    echo '<div class="f-body"><a class="f-row' . ($active ? ' active' : '') . '" href="' . e($node->url) . '"><i class="' . e($icon) . '"></i><span>' . $label . '</span></a></div>';
                    echo '</div>';
                    echo '</div>'; // close nav-item-outer
                }
            }
            echo '</div>';
        };
    @endphp
    @php
        $iconMap = [
            '/' => 'fa-solid fa-gauge-high',
            '/pos' => 'fa-solid fa-cash-register',
            '/purchases' => 'fa-solid fa-truck-ramp-box',
            '/debts' => 'fa-solid fa-wallet',
            '/master-data' => 'fa-solid fa-database',
            '/master' => 'fa-solid fa-layer-group',
            '/master/users' => 'fa-solid fa-users',
            '/master/menus' => 'fa-solid fa-sitemap',
            '/master/roles' => 'fa-solid fa-user-shield',
        ];
    @endphp
    <div class="shell" id="appShell">
        <div class="mobile-backdrop" id="mobileBackdrop"></div>
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="brand" data-no-loading>
                    <span class="brand-logo"><i class="fa-solid fa-screwdriver-wrench"></i></span>
                    <span class="brand-text">Bengkel Berkah<small>Workshop POS</small></span>
                </a>
                <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close Sidebar" data-no-loading>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <nav class="nav">
                @php $renderMenuTree(0, 0); @endphp
            </nav>
        </aside>
        <main class="main">
            <div class="topbar">
                <div class="top-left">
                    <div class="top-line">
                        <button class="burger" id="sidebarToggle" type="button" aria-label="Toggle Sidebar" data-no-loading>
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div>
                            <h1>@yield('title')</h1>
                            <div class="muted">@yield('subtitle')</div>
                        </div>
                    </div>
                    <div class="history" id="historyBar"></div>
                </div>
                <div class="top-actions">
                    <div class="bell-wrap" id="appBell">
                        <button type="button" class="btn ghost bell-btn" id="bellBtn" aria-label="Notifikasi" data-no-loading>
                            <i class="fa-solid fa-bell"></i>
                            <span class="bell-badge empty" id="bellBadge"></span>
                        </button>
                        <div class="bell-dropdown" id="bellDropdown">
                            <div class="bell-head">
                                <strong>Notifikasi</strong>
                                <span class="muted" id="bellStatus">Memuat...</span>
                            </div>
                            <div class="bell-body" id="bellBody">
                                <div class="bell-empty">Belum ada notifikasi.</div>
                            </div>
                            <div class="bell-foot">
                                <a href="{{ route('modules.reporting.stock') }}">Stok</a>
                                <a href="{{ route('modules.reporting.outstanding') }}">Piutang</a>
                            </div>
                        </div>
                    </div>
                    <a class="btn" href="{{ route('modules.pos.open-cashier') }}"><i class="fa-solid fa-cash-register"></i><span>New Sale</span></a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn secondary" type="submit"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></button>
                    </form>
                </div>
            </div>

            @yield('content')
        </main>
    </div>
    <div class="loading-indicator" id="globalLoadingIndicator" aria-live="polite"></div>
    <script>
        (function () {
            const shell = document.getElementById('appShell');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarClose = document.getElementById('sidebarClose');
            const historyBar = document.getElementById('historyBar');
            const globalLoadingIndicator = document.getElementById('globalLoadingIndicator');
            const historyKey = 'bb_recent_pages_v1';
            const sidebarKey = 'bb_sidebar_state_v1';
            const currentPath = window.location.pathname;
            const heading = document.querySelector('.top-line h1');
            const currentLabel = (heading ? heading.textContent : document.title.replace(/\s*-\s*.*$/, '')).trim() || currentPath;
            const isMobile = window.matchMedia('(max-width: 900px)').matches;

            try {
                const state = localStorage.getItem(sidebarKey);
                if (!isMobile && state === 'collapsed') {
                    shell.classList.add('sidebar-collapsed');
                }
            } catch (e) {}

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    if (window.matchMedia('(max-width: 900px)').matches) {
                        shell.classList.toggle('mobile-sidebar-open');
                        return;
                    }

                    shell.classList.toggle('sidebar-collapsed');
                    document.querySelectorAll('[data-flyout].open').forEach(function (flyout) {
                        flyout.classList.remove('open');
                    });
                    document.querySelectorAll('.ft-backdrop.show').forEach(function (backdrop) {
                        backdrop.classList.remove('show');
                    });
                    try {
                        localStorage.setItem(sidebarKey, shell.classList.contains('sidebar-collapsed') ? 'collapsed' : 'expanded');
                    } catch (e) {}
                });
            }
            if (sidebarClose) {
                sidebarClose.addEventListener('click', function () {
                    shell.classList.remove('mobile-sidebar-open');
                });
            }

            const menuToggles = shell.querySelectorAll('[data-menu-toggle]');
            menuToggles.forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    if (shell.classList.contains('sidebar-collapsed')) {
                        return;
                    }

                    const group = toggle.closest('[data-menu-group]');
                    if (!group) return;
                    const submenu = group.querySelector(':scope > [data-submenu]');
                    if (!submenu) return;

                    const willOpen = !submenu.classList.contains('open');
                    submenu.classList.toggle('open', willOpen);
                    toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });
            });

            document.addEventListener('click', function (event) {
                if (!window.matchMedia('(max-width: 900px)').matches) {
                    return;
                }
                if (!shell.classList.contains('mobile-sidebar-open')) {
                    return;
                }

                const clickedInsideSidebar = event.target.closest('.sidebar') || event.target.closest('#sidebarToggle');
                if (!clickedInsideSidebar) {
                    shell.classList.remove('mobile-sidebar-open');
                }
            });

            let pages = [];
            try {
                pages = JSON.parse(localStorage.getItem(historyKey) || '[]');
            } catch (e) {}
            pages = pages.filter(p => p && p.path && p.label);
            pages = pages.filter(p => p.path !== currentPath);
            pages.unshift({ path: currentPath, label: currentLabel });
            pages = pages.slice(0, 6);

            try {
                localStorage.setItem(historyKey, JSON.stringify(pages));
            } catch (e) {}

            if (historyBar) {
                historyBar.innerHTML = pages.map(p => {
                    const active = p.path === currentPath ? 'active' : '';
                    return `<a class="${active}" href="${p.path}">${p.label}</a>`;
                }).join('');
            }

            @if (session('status'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: @json(session('status')),
                    timer: 2200,
                    showConfirmButton: false
                });
            @endif

            @if ($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: @json($errors->first())
                });
            @endif

            function showGlobalLoading(message) {
                if (!globalLoadingIndicator) return;
                globalLoadingIndicator.classList.add('show');
                globalLoadingIndicator.innerHTML = `<span class="spinner"></span><span>${message}</span>`;
            }

            function pickLabel(el) {
                const explicit = el.dataset.loadingText;
                if (explicit) return explicit;
                const text = (el.textContent || '').replace(/\s+/g, ' ').trim();
                if (text) return `Loading ${text}...`;
                return 'Processing request...';
            }

            function setLoadingState(el) {
                if (!el || el.dataset.noLoading !== undefined || el.classList.contains('loading')) return;
                const loadingText = pickLabel(el);
                el.dataset.originalHtml = el.innerHTML;
                el.innerHTML = `<span class="spinner"></span>${loadingText}`;
                el.classList.add('loading');
                if ('disabled' in el) el.disabled = true;
                showGlobalLoading(loadingText);
            }

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;
                const submit = form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
                if (submit instanceof HTMLElement) {
                    setLoadingState(submit);
                }
            });

            document.addEventListener('click', function (event) {
                const target = event.target.closest('.btn, button');
                if (!(target instanceof HTMLElement)) return;
                if (target.dataset.noLoading !== undefined) return;

                const tag = target.tagName.toLowerCase();
                if (tag === 'button') {
                    const type = (target.getAttribute('type') || 'submit').toLowerCase();
                    if (type === 'button') return;
                    if (type === 'submit') return;
                }

                if (tag === 'a') {
                    const href = target.getAttribute('href') || '';
                    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                }

                setLoadingState(target);
            });

            // Loading sidebar link clicks (exclude flyout links)
            document.addEventListener('click', function (event) {
                const link = event.target.closest('.sidebar .nav > .nav-group > .nav-item-outer > a');
                if (!(link instanceof HTMLElement)) return;
                if (link.classList.contains('loading')) return;
                if (link.getAttribute('href') === window.location.pathname) return;

                const menuName = (link.textContent || '').replace(/\s+/g, ' ').trim() || 'menu';
                const loadingText = `Opening ${menuName}...`;
                link.dataset.originalHtml = link.innerHTML;
                link.innerHTML = `<span class="spinner"></span><span>${loadingText}</span>`;
                link.classList.add('loading');
                showGlobalLoading(loadingText);
            });

            /* ---- Flyout: click-based (D365 style) ---- */
            const FT_SIDEBAR_W = 76;
            let ftOpenFlyout = null;
            const ftBackdrop = document.createElement('div');
            ftBackdrop.className = 'ft-backdrop';
            document.body.appendChild(ftBackdrop);

            function ftPosition(flyout, triggerEl) {
                const rect = triggerEl.getBoundingClientRect();
                flyout.style.left = FT_SIDEBAR_W + 'px';
                flyout.style.top = rect.top + 'px';
                const fh = flyout.offsetHeight;
                const vh = window.innerHeight;
                if (rect.top + fh > vh) {
                    flyout.style.top = Math.max(8, vh - fh - 8) + 'px';
                }
            }

            function ftCloseAll() {
                if (ftOpenFlyout) {
                    ftOpenFlyout.classList.remove('open');
                    ftOpenFlyout = null;
                }
                ftBackdrop.classList.remove('show');
            }

            // Flyout link handler: parent toggles children, leaf navigates
            document.addEventListener('click', function (e) {
                const link = e.target.closest('.sidebar .flyout a[href]');
                if (!(link instanceof HTMLAnchorElement)) return;

                // Jika link ada di dalam .f-children → leaf navigasi, bukan toggle parent
                if (link.closest('.f-children')) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    showGlobalLoading(`Opening ${(link.textContent || 'menu').replace(/\s+/g, ' ').trim()}...`);
                    window.location.href = link.href;
                    return;
                }

                const fNode = link.closest('.f-node');
                const hasChildren = fNode && fNode.querySelector(':scope > .f-children');

                if (hasChildren) {
                    // Parent node → toggle children, no navigation
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    const children = fNode.querySelector(':scope > .f-children');
                    const toggleBtn = fNode.querySelector(':scope > .f-row-wrap > .f-toggle, :scope > .f-row-wrap > button[data-ft-toggle]');
                    const willOpen = !children.classList.contains('open');
                    children.classList.toggle('open', willOpen);
                    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    return;
                }

                // Top-level leaf node → navigate
                const href = link.getAttribute('href') || '';
                if (!href || href === '#' || href.startsWith('javascript:')) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                showGlobalLoading(`Opening ${(link.textContent || 'menu').replace(/\s+/g, ' ').trim()}...`);
                window.location.href = link.href;
            }, true);

            // Tree toggle inside flyout (click on parent node)
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-ft-toggle]');
                if (!btn) return;
                e.stopPropagation();
                const parentNode = btn.closest('.f-node');
                if (!parentNode) return;
                const children = parentNode.querySelector('.f-children');
                if (!children) return;
                const willOpen = !children.classList.contains('open');
                children.classList.toggle('open', willOpen);
                btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });

            // Backdrop close
            ftBackdrop.addEventListener('click', ftCloseAll);

            // Prevent default tooltip on collapsed sidebar links
            document.addEventListener('mouseover', function (e) {
                const link = e.target.closest('.sidebar a');
                if (!link) return;
                if (!shell.classList.contains('sidebar-collapsed')) return;
                link.removeAttribute('title');
            });

            // Remove title attribute from sidebar links when collapsed
            function cleanTitles() {
                const links = document.querySelectorAll('.sidebar a');
                links.forEach(function (l) {
                    l.removeAttribute('title');
                });
            }
            cleanTitles();

            // Observe for dynamically added links
            const titleObserver = new MutationObserver(cleanTitles);
            titleObserver.observe(document.querySelector('.sidebar') || document.body, { subtree: true, childList: true });

            function directChild(el, selector) {
                return Array.from(el.children).find(function (child) {
                    return child.matches(selector);
                }) || null;
            }

            document.addEventListener('click', function (e) {
                if (!shell.classList.contains('sidebar-collapsed')) return;

                const triggerEl = e.target.closest('.sidebar .menu-parent');
                if (!triggerEl) return;

                const outer = triggerEl.closest('.nav-item-outer');
                if (!outer) return;

                const flyout = directChild(outer, '[data-flyout]');
                if (!flyout) return;

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                if (ftOpenFlyout === flyout) {
                    ftCloseAll();
                    return;
                }

                ftCloseAll();
                flyout.classList.add('open');
                ftOpenFlyout = flyout;
                ftBackdrop.classList.add('show');
                ftPosition(flyout, triggerEl);
            }, true);

            window.addEventListener('scroll', function () {
                if (!ftOpenFlyout) return;
                const outer = ftOpenFlyout.closest('.nav-item-outer');
                const triggerEl = outer ? outer.querySelector('.menu-parent') : null;
                if (triggerEl) ftPosition(ftOpenFlyout, triggerEl);
            }, true);

            window.addEventListener('resize', function () {
                if (!ftOpenFlyout) return;
                const outer = ftOpenFlyout.closest('.nav-item-outer');
                const triggerEl = outer ? outer.querySelector('.menu-parent') : null;
                if (triggerEl) ftPosition(ftOpenFlyout, triggerEl);
            });
        })();

        // Global notification bell — lightweight polling (30s), pauses when tab hidden
        (function () {
            const POLL_INTERVAL = 30000;
            const url = '{{ route('api.alerts') }}';
            const bell = document.getElementById('appBell');
            const bellBtn = document.getElementById('bellBtn');
            const dropdown = document.getElementById('bellDropdown');
            const badge = document.getElementById('bellBadge');
            const body = document.getElementById('bellBody');
            const status = document.getElementById('bellStatus');
            const fmt = new Intl.NumberFormat('id-ID');
            const rp = (v) => 'Rp ' + fmt.format(Math.round(v || 0));

            if (!bell || !bellBtn) return;

            function toggleDropdown(show) {
                bell.classList.toggle('open', show);
            }

            bellBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleDropdown(!bell.classList.contains('open'));
            });

            document.addEventListener('click', function (e) {
                if (!bell.contains(e.target)) toggleDropdown(false);
            });

            function buildItem(icon, cls, title, meta) {
                return '<div class="bell-item ' + cls + '"><i class="' + icon + '"></i><div class="line"><strong>' + title + '</strong><span class="muted">' + meta + '</span></div></div>';
            }

            function render(data) {
                const total = (data.out_of_stock || 0) + (data.low_stock || 0) + (data.overdue_bills || 0);
                const totalOutstanding = (data.outstanding_bills || 0);

                if (badge) {
                    badge.textContent = total > 99 ? '99+' : (total || '');
                    badge.classList.toggle('empty', !total);
                }
                bell.classList.toggle('dash-pulse', total > 0);

                if (status) {
                    status.textContent = total ? (total + ' perlu perhatian') : 'Tidak ada notifikasi';
                }

                if (!body) return;
                if (!total && !totalOutstanding) {
                    body.innerHTML = '<div class="bell-empty">Semua kondisi aman.</div>';
                    return;
                }

                let html = '';
                if (data.out_of_stock > 0) {
                    html += buildItem('fa-solid fa-circle-exclamation', 'danger', data.out_of_stock + ' stok habis', 'Segera restock agar penjualan tidak terganggu');
                }
                if (data.low_stock > 0) {
                    html += buildItem('fa-solid fa-triangle-exclamation', 'warning', data.low_stock + ' stok menipis', 'Di bawah minimum level');
                }
                if (data.overdue_bills > 0) {
                    html += buildItem('fa-solid fa-file-invoice-dollar', 'danger', data.overdue_bills + ' piutang jatuh tempo', 'Total ' + rp(data.overdue_items.reduce((s, d) => s + (d.remaining || 0), 0)));
                }
                if (totalOutstanding > 0 && data.overdue_bills !== totalOutstanding) {
                    html += buildItem('fa-solid fa-wallet', 'info', totalOutstanding + ' piutang berjalan', 'Termasuk yang belum jatuh tempo');
                }
                body.innerHTML = html;
            }

            async function fetchAlerts() {
                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                    if (!res.ok) return;
                    render(await res.json());
                } catch (e) {
                    // silent fail
                }
            }

            fetchAlerts();
            let timer = setInterval(fetchAlerts, POLL_INTERVAL);
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    clearInterval(timer);
                } else {
                    fetchAlerts();
                    timer = setInterval(fetchAlerts, POLL_INTERVAL);
                }
            });
        })();
    </script>
</body>
</html>
