<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','Inventory')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Bootstrap Icons (no local icons directory needed) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root{
            --bg: #F0F8FF;
            --card: #ffffff;
            --border: #e6e9f2;
            --text: #0f172a;
            --muted: #64748b;

            --side: #f7faff;
            --side2: #f7faff;
            --sideText: #000000;
            --sideMuted: rgba(0, 0, 0, 0.7);
            --sideHover: rgba(255,255,255,.08);
            --sideActive: rgba(255,255,255,.12);

            --radius: 16px;

            --successBg: #ecfdf3;
            --successBd: #a7f3d0;
            --successTx: #065f46;

            --dangerBg: #fef2f2;
            --dangerBd: #fecaca;
            --dangerTx: #7f1d1d;

            --warnBg: #fffbeb;
            --warnBd: #fde68a;
            --warnTx: #7c2d12;

            --infoBg: #eff6ff;
            --infoBd: #bfdbfe;
            --infoTx: #1e3a8a;
        }

        *{ box-sizing:border-box; }
        body{ background: var(--bg); color: var(--text); }

        .inv-shell{ min-height:100vh; display:flex; }

        /* Sidebar */
        .inv-sidebar{
            width: 280px;
            background: linear-gradient(180deg, var(--side) 0%, var(--side2) 100%);
            border-right: 1px solid rgba(255,255,255,.06);
            color: var(--sideText);
            padding: 16px 14px;
            position: sticky;
            top:0;
            height:100vh;
        }

        .inv-brand{
            display:flex;
            align-items:center;
            gap:10px;
            padding: 10px 10px 14px 10px;
        }

        .inv-logo{
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: rgba(255,255,255,.10);
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:900;
            letter-spacing:.08em;
            box-shadow: 0 16px 30px rgba(0,0,0,.25);
            user-select:none;
        }

        .inv-brand strong{ display:block; font-size:15px; }
        .inv-brand small{ color: var(--sideMuted); font-size:12px; }

        .inv-section{
            margin-top: 14px;
            padding: 0 8px;
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--sideMuted);
        }

        .inv-link{
            display:flex;
            align-items:center;
            gap:10px;
            padding: 10px 10px;
            margin: 6px 6px;
            border-radius: 12px;
            color: var(--sideText);
            text-decoration:none;
            transition: .15s ease;
            font-size: 14px;
        }
        .inv-link:hover{ background: var(--sideHover); color: var(--sideText); }
        .inv-link.active{
            background: var(--sideActive);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.12);
        }

        .inv-ico{
            width: 28px;
            height: 28px;
            border-radius: 10px;
            background: rgba(255,255,255,.10);
            display:flex;
            align-items:center;
            justify-content:center;
            flex: 0 0 28px;
        }
        .inv-ico i{
            font-size: 15px;
            line-height: 1;
            opacity: .95;
        }

        .inv-sidefoot{
            position:absolute;
            left:14px;
            right:14px;
            bottom:14px;
            padding: 12px;
            border-radius: 14px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.10);
        }
        .inv-sidefoot .name{ font-weight:800; font-size:13px; }
        .inv-sidefoot .meta{ color: var(--sideMuted); font-size:12px; }

        /* Content */
        .inv-content{ flex:1; min-width:0; display:flex; flex-direction:column; }

        .inv-topbar{
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255,255,255,.80);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 12px 18px;
        }

        .inv-topbar-inner{
            display:flex;
            align-items:center;
            justify-content: space-between;
            gap: 12px;
        }

        .inv-userpill{
            display:flex;
            align-items:center;
            gap:10px;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--card);
            box-shadow: 0 14px 35px rgba(2,6,23,.06);
        }

        .inv-avatar{
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: #0b1220;
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:900;
            font-size: 13px;
            user-select:none;
        }

        .inv-role{
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #f1f5f9;
        }

        .inv-main{ padding: 18px; }

        /* Cards & sections */
        .inv-card{
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 18px 45px rgba(2,6,23,.06);
        }

        .inv-page-header{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .inv-page-header-card{
            padding: 16px;
        }

        .inv-h1{
            margin:0;
            font-weight: 900;
            font-size: 22px;
            letter-spacing:.2px;
        }

        .inv-sub{
            color: var(--muted);
            font-size: 13px;
            margin-top: 4px;
            min-height: 18px;
        }

        .inv-page-actions{
            display:flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items:center;
            justify-content:flex-end;
        }

        /* Alerts */
        .inv-alert{
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--card);
            padding: 12px 14px;
            display:flex;
            gap: 10px;
            align-items:flex-start;
        }
        .inv-alert .inv-alert-ico{
            width: 34px;
            height: 34px;
            border-radius: 12px;
            display:flex;
            align-items:center;
            justify-content:center;
            flex: 0 0 34px;
            user-select:none;
            font-weight:900;
        }
        .inv-alert strong{ display:block; margin-bottom:4px; }
        .inv-alert ul{ margin:6px 0 0 18px; }

        .inv-alert.success{ background: var(--successBg); border-color: var(--successBd); color: var(--successTx); }
        .inv-alert.success .inv-alert-ico{ background: rgba(6,95,70,.12); color: var(--successTx); }

        .inv-alert.danger{ background: var(--dangerBg); border-color: var(--dangerBd); color: var(--dangerTx); }
        .inv-alert.danger .inv-alert-ico{ background: rgba(127,29,29,.10); color: var(--dangerTx); }

        .inv-alert.warning{ background: var(--warnBg); border-color: var(--warnBd); color: var(--warnTx); }
        .inv-alert.warning .inv-alert-ico{ background: rgba(124,45,18,.10); color: var(--warnTx); }

        .inv-alert.info{ background: var(--infoBg); border-color: var(--infoBd); color: var(--infoTx); }
        .inv-alert.info .inv-alert-ico{ background: rgba(30,58,138,.10); color: var(--infoTx); }

        /* Tables (Bootstrap .table friendly) */
        .table{ border-color: var(--border) !important; }
        .table thead th{
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
        }
        .table td{ vertical-align: middle; }
        .table-hover tbody tr:hover{ background: rgba(2,6,23,.02); }

        /* “Nice table wrapper” */
        .inv-table-card{ overflow:hidden; }
        .inv-table-head{
            padding: 14px 16px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            border-bottom: 1px solid var(--border);
            background: #fbfcff;
        }
        .inv-table-title{ font-weight: 900; margin:0; font-size: 14px; letter-spacing:.2px; }
        .inv-table-sub{ color: var(--muted); font-size: 12px; margin-top: 3px; }
        .inv-table-tools{
            display:flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items:center;
            justify-content:flex-end;
        }
        .inv-table-body{ padding: 0; }

        /* Empty state */
        .inv-empty{ padding: 26px 18px; text-align:center; }
        .inv-empty .inv-empty-ico{
            width: 58px;
            height: 58px;
            border-radius: 18px;
            margin: 0 auto 10px auto;
            display:flex;
            align-items:center;
            justify-content:center;
            background: #f1f5f9;
            border: 1px solid var(--border);
            font-weight: 900;
            color:#0b1220;
            user-select:none;
        }
        .inv-empty .inv-empty-title{ font-weight: 900; margin: 0; font-size: 16px; }
        .inv-empty .inv-empty-sub{ margin-top: 6px; color: var(--muted); font-size: 13px; }

        /* Skeleton loader */
        .inv-skeleton{
            position: relative;
            overflow: hidden;
            background: #eef2f6;
            border-radius: 10px;
        }
        .inv-skeleton:after{
            content:'';
            position:absolute;
            inset:0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, rgba(238,242,246,0) 0%, rgba(255,255,255,.65) 50%, rgba(238,242,246,0) 100%);
            animation: invShimmer 1.05s infinite;
        }
        @keyframes invShimmer{ 100%{ transform: translateX(100%); } }
        .inv-skel-row{ height: 14px; margin: 10px 0; }
        .inv-skel-row.sm{ height: 10px; }
        .inv-skel-row.lg{ height: 18px; }

        /* Forms */
        .form-control, .form-select{ border-radius: 12px; border-color: var(--border); }
        .btn{ border-radius: 12px; }

        /* Global “page loading” overlay */
        .inv-page-loader{
            position: fixed;
            inset: 0;
            background: rgba(246,247,251,.75);
            backdrop-filter: blur(6px);
            display:none;
            align-items:center;
            justify-content:center;
            z-index: 1000;
        }
        .inv-page-loader.show{ display:flex; }
        .inv-loader-card{ width: min(420px, calc(100vw - 34px)); padding: 16px; }
        .inv-loader-top{ display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .inv-spinner{
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 4px solid #e2e8f0;
            border-top-color: #0b1220;
            animation: invSpin .9s linear infinite;
        }
        @keyframes invSpin{ to{ transform: rotate(360deg); } }
        .inv-loader-title{ font-weight: 900; margin:0; font-size: 14px; }
        .inv-loader-sub{ color: var(--muted); margin-top:4px; font-size: 12px; }

        /* Mobile */
        .inv-toggle{ display:none; }
        @media (max-width: 992px){
            .inv-sidebar{ position:fixed; left:0; top:0; transform:translateX(-105%); transition:.18s ease; z-index:50; }
            .inv-sidebar.open{ transform:translateX(0); }
            .inv-overlay{ display:none; position:fixed; inset:0; background:rgba(2,6,23,.55); z-index:40; }
            .inv-overlay.show{ display:block; }
            .inv-toggle{
                display:inline-flex;
                width: 40px;
                height: 40px;
                border-radius: 12px;
                border: 1px solid var(--border);
                background: var(--card);
                align-items:center;
                justify-content:center;
                margin-right: 10px;
            }
        }

        /* Little niceties */
        .inv-muted{ color: var(--muted); }
        .inv-chip{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #f8fafc;
            font-size: 12px;
            color: var(--text);
            font-weight: 700;
        }
        .inv-divider{ height:1px; background: var(--border); margin: 12px 0; }
    </style>
</head>
<body>

@php
    $u = auth('inventory')->user();
    $name = $u?->name ?? 'Inventory';
    $role = $u?->inventory_role ?? '';
    $initials = collect(explode(' ', trim($name)))->filter()->map(fn($p)=>mb_substr($p,0,1))->take(2)->implode('');
    $path = request()->path();
    $isActive = fn(string $needle) => str_contains($path, $needle);

    // Friendlier page label in topbar (no route changes)
    $pageLabel = $path === 'inventory'
        ? 'Dashboard'
        : ucfirst(trim(str_replace(['inventory/','-'], ['',' '], $path)));
@endphp

<div class="inv-shell">
    <div class="inv-overlay" id="invOverlay" onclick="invCloseSidebar()"></div>

    <aside class="inv-sidebar" id="invSidebar">
        <div class="inv-brand">
            <div class="inv-logo">IN</div>
            <div>
                <strong>Inventory</strong>
                <small>Store • Assign • Deploy</small>
            </div>
        </div>

        <div class="inv-section">Core</div>
        <a class="inv-link {{ $isActive('inventory/dashboard') || $path === 'inventory' ? 'active' : '' }}" href="{{ route('inventory.dashboard') }}">
            <span class="inv-ico"><i class="bi bi-speedometer2"></i></span> Dashboard
        </a>
        <a class="inv-link {{ $isActive('inventory/items') ? 'active' : '' }}" href="{{ route('inventory.items.index') }}">
            <span class="inv-ico"><i class="bi bi-box-seam"></i></span> Items
        </a>
        <a class="inv-link {{ $isActive('inventory/item-groups') ? 'active' : '' }}" href="{{ route('inventory.item-groups.index') }}">
            <span class="inv-ico"><i class="bi bi-collection"></i></span> Item Groups
        </a>

        <div class="inv-section">Stock</div>
        <a class="inv-link {{ $isActive('inventory/receipts') ? 'active' : '' }}" href="{{ route('inventory.receipts.index') }}">
            <span class="inv-ico"><i class="bi bi-inbox"></i></span> Receipts
        </a>
        <a class="inv-link {{ $isActive('inventory/assignments') ? 'active' : '' }}" href="{{ route('inventory.assignments.index') }}">
            <span class="inv-ico"><i class="bi bi-person-check"></i></span> Tech Assignments
        </a>
        <a class="inv-link {{ $isActive('inventory/team-assignments') ? 'active' : '' }}" href="{{ route('inventory.team_assignments.index') }}">
            <span class="inv-ico"><i class="bi bi-people"></i></span> Team Assignments
        </a>

        <div class="inv-section">Field</div>
        <a class="inv-link {{ $isActive('inventory/deployments') ? 'active' : '' }}" href="{{ route('inventory.deployments.index') }}">
            <span class="inv-ico"><i class="bi bi-geo-alt"></i></span> Deployments
        </a>
        <a class="inv-link {{ $isActive('inventory/team-deployments') ? 'active' : '' }}" href="{{ route('inventory.team_deployments.index') }}">
            <span class="inv-ico"><i class="bi bi-pin-map"></i></span> Team Deploy
        </a>
        <a class="inv-link {{ $isActive('inventory/movements') ? 'active' : '' }}" href="{{ route('inventory.movements.index') }}">
            <span class="inv-ico"><i class="bi bi-arrow-left-right"></i></span> Transfers / Returns
        </a>

        <div class="inv-section">Audit</div>
        <a class="inv-link {{ $isActive('inventory/logs') ? 'active' : '' }}" href="{{ route('inventory.logs.index') }}">
            <span class="inv-ico"><i class="bi bi-journal-text"></i></span> Logs
        </a>
        <a class="inv-link {{ $isActive('inventory/teams') ? 'active' : '' }}" href="{{ route('inventory.teams.index') }}">
            <span class="inv-ico"><i class="bi bi-shield-lock"></i></span> Teams
        </a>

        <div class="inv-sidefoot">
            <div class="meta">Signed in as</div>
            <div class="name">{{ $name }}</div>
            <div class="meta">Role: {{ $role ?: '—' }}</div>
        </div>
    </aside>

    <div class="inv-content">
        <div class="inv-topbar">
            <div class="inv-topbar-inner">
                <div class="d-flex align-items-center">
                    <button class="inv-toggle" type="button" onclick="invOpenSidebar()"><i class="bi bi-list"></i></button>
                    <div>
                        <div style="font-weight:900;">{{ $pageLabel }}</div>
                        <div style="color:var(--muted); font-size:12px;">{{ now()->format('D, M j') }}</div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="inv-userpill">
                        <div class="inv-avatar">{{ $initials ?: 'U' }}</div>

                        <form method="POST" action="{{ route('inventory.auth.logout') }}" class="ms-2" data-inv-loading>
                            @csrf
                            <button class="btn btn-sm btn-outline-dark">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="inv-main">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="inv-alert success inv-card mb-3">
                    <div class="inv-alert-ico"><i class="bi bi-check2"></i></div>
                    <div>
                        <strong>Success</strong>
                        <div>{{ session('success') }}</div>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="inv-alert warning inv-card mb-3">
                    <div class="inv-alert-ico"><i class="bi bi-exclamation"></i></div>
                    <div>
                        <strong>Heads up</strong>
                        <div>{{ session('warning') }}</div>
                    </div>
                </div>
            @endif

            @if(session('info'))
                <div class="inv-alert info inv-card mb-3">
                    <div class="inv-alert-ico"><i class="bi bi-info-lg"></i></div>
                    <div>
                        <strong>Info</strong>
                        <div>{{ session('info') }}</div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="inv-alert danger inv-card mb-3">
                    <div class="inv-alert-ico"><i class="bi bi-x-lg"></i></div>
                    <div>
                        <strong>Error</strong>
                        <div>{{ session('error') }}</div>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="inv-alert danger inv-card mb-3">
                    <div class="inv-alert-ico"><i class="bi bi-x-lg"></i></div>
                    <div>
                        <strong>Fix these issues</strong>
                        <ul class="mb-0">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</div>

{{-- Global Page Loader --}}
<div class="inv-page-loader" id="invPageLoader" aria-hidden="true">
    <div class="inv-card inv-loader-card">
        <div class="inv-loader-top">
            <div>
                <p class="inv-loader-title mb-0">Working…</p>
                <div class="inv-loader-sub">Processing your request. Please hold.</div>
            </div>
            <div class="inv-spinner" aria-hidden="true"></div>
        </div>

        <div class="inv-divider"></div>

        <div class="inv-skeleton inv-skel-row lg"></div>
        <div class="inv-skeleton inv-skel-row"></div>
        <div class="inv-skeleton inv-skel-row sm" style="width:65%;"></div>
    </div>
</div>

<script>
    function invOpenSidebar(){
        const sb = document.getElementById('invSidebar');
        const ov = document.getElementById('invOverlay');
        if(sb) sb.classList.add('open');
        if(ov) ov.classList.add('show');
    }
    function invCloseSidebar(){
        const sb = document.getElementById('invSidebar');
        const ov = document.getElementById('invOverlay');
        if(sb) sb.classList.remove('open');
        if(ov) ov.classList.remove('show');
    }

    // Global loader helpers
    function invShowLoader(msg){
        const el = document.getElementById('invPageLoader');
        if(!el) return;
        if(msg){
            const t = el.querySelector('.inv-loader-sub');
            if(t) t.textContent = msg;
        }
        el.classList.add('show');
        el.setAttribute('aria-hidden', 'false');
    }
    function invHideLoader(){
        const el = document.getElementById('invPageLoader');
        if(!el) return;
        el.classList.remove('show');
        el.setAttribute('aria-hidden', 'true');
    }

    // Auto-show loader on forms/links that opt-in
    document.addEventListener('click', function(e){
        const a = e.target.closest('a[data-inv-loading]');
        if(a && a.getAttribute('href') && a.getAttribute('href') !== '#'){
            invShowLoader('Loading page…');
        }
    });

    document.addEventListener('submit', function(e){
        const f = e.target.closest('form[data-inv-loading]');
        if(f){
            invShowLoader('Saving…');
        }
    });

    // Close sidebar on desktop width
    window.addEventListener('resize', function(){
        if(window.innerWidth > 992){
            invCloseSidebar();
        }
    });
</script>

</body>
</html>
