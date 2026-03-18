@php
    $pettyUser = auth('petty')->user();
    $pettyCan = static fn (string $permission): bool => \App\Modules\PettyCash\Support\PettyAccess::allows($pettyUser, $permission);
    $pettyCanViewNotifications = $pettyCan('notifications.view') || $pettyCan('notifications.manage');
    $pettyCanManageSettings = \App\Modules\PettyCash\Support\PettyAccess::isAdmin($pettyUser);

    $pettyUnread = $pettyCanViewNotifications
        ? \App\Modules\PettyCash\Models\PettyNotification::where('module', 'pettycash')->where('is_read', false)->count()
        : 0;

    $pettyName = trim((string) ($pettyUser->name ?? 'User'));
    $pettyParts = preg_split('/\s+/', $pettyName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $pettyInitials = strtoupper(substr($pettyParts[0] ?? 'U', 0, 1) . substr($pettyParts[1] ?? '', 0, 1));
@endphp


<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'PettyCash')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/pettycash ico.png') }}">
    <link rel="shortcut icon" href="{{ asset('assets/pettycash ico.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

    <style>
        :root{
            --bg:#f6f7fb;
            --card:#ffffff;
            --border:#e7e9f2;
            --text:#101828;
            --muted:#667085;
            --brand:#7f56d9;
            --thead:#f8fafc;
            --row-alt:#fcfcfd;
            --row-hover:#f9fafb;
            --shadow:0 8px 30px rgba(16,24,40,.06);
            --radius:14px;
        }
        *{box-sizing:border-box}
        body{font-family:system-ui;background:var(--bg);margin:0;color:var(--text)}
        a{text-decoration:none}

        /* ===== App shell ===== */
        .app{
            display:grid;
            grid-template-columns: 244px 1fr;
            min-height:100vh;
        }

        /* ===== Sidebar ===== */
        .sidebar{
            background:var(--card);
            border-right:1px solid var(--border);
            padding:12px;
            position:sticky;
            top:0;
            height:100vh;
            overflow:auto;
        }
        .brand{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            padding:10px 10px 14px 10px;
            border-bottom:1px solid var(--border);
            margin-bottom:12px;
        }
        .brand .name{
            font-weight:900;
            letter-spacing:.2px;
        }
        .brand .sub{
            font-size:12px;
            color:var(--muted);
            margin-top:2px;
        }

        /* ===== Main ===== */
        .main{
            display:flex;
            flex-direction:column;
            min-width:0;
        }

        /* Topbar */
        .topbar{
            background:var(--card);
            border-bottom:1px solid var(--border);
            padding:12px 16px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            position:sticky;
            top:0;
            z-index:10;
        }
        .topbar-left{
            display:flex;
            align-items:center;
            gap:10px;
            min-width:0;
        }
        .topbar-right{
            display:flex;
            align-items:center;
            gap:8px;
        }
        .topbar-title{
            font-weight:900;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        /* Mobile menu button */
        .menu-btn{
            display:none;
            border:1px solid var(--border);
            background:#fff;
            color:#344054;
            padding:8px 10px;
            border-radius:10px;
            font-weight:800;
            cursor:pointer;
        }

        .top-icon-btn{
            border:1px solid var(--border);
            background:#fff;
            color:#344054;
            width:38px;
            height:38px;
            border-radius:10px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
            position:relative;
        }
        .top-icon-btn:hover{border-color:#cbd5e1;background:#f9fafb}
        .notify-badge{
            position:absolute;
            top:-6px;
            right:-6px;
            background:#b42318;
            color:#fff;
            border-radius:999px;
            min-width:18px;
            height:18px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:11px;
            font-weight:900;
            padding:0 4px;
        }

        .profile-menu{position:relative}
        .avatar-btn{
            border:1px solid var(--border);
            background:#fff;
            color:#344054;
            height:38px;
            border-radius:10px;
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:0 8px 0 6px;
            cursor:pointer;
        }
        .avatar-btn:hover{border-color:#cbd5e1;background:#f9fafb}
        .avatar-dot{
            width:24px;
            height:24px;
            border-radius:50%;
            background:#eef4ff;
            color:#1849a9;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:11px;
            font-weight:900;
        }
        .avatar-caret{
            font-size:11px;
            color:#667085;
            line-height:1;
        }
        .profile-dropdown{
            position:absolute;
            right:0;
            top:44px;
            min-width:220px;
            background:#fff;
            border:1px solid #e4e7ec;
            border-radius:12px;
            box-shadow:0 16px 40px rgba(16,24,40,.18);
            padding:8px;
            z-index:40;
        }
        .profile-head{
            padding:8px;
            border-bottom:1px solid #f1f3f7;
            margin-bottom:6px;
        }
        .profile-head .name{
            font-size:13px;
            font-weight:900;
            color:#101828;
        }
        .profile-head .sub{
            font-size:11px;
            color:#667085;
            margin-top:2px;
        }
        .profile-item{
            width:100%;
            border:none;
            background:transparent;
            text-align:left;
            padding:9px 10px;
            border-radius:8px;
            color:#344054;
            font-size:13px;
            font-weight:700;
            text-decoration:none;
            display:block;
            cursor:pointer;
        }
        .profile-item:hover{background:#f2f4f7}

        /* Content area */
        .content{
            padding:20px 24px 28px;
        }
        .container{
            width:100%;
            max-width:none;
            margin:0;
        }

        /* Default card utility for pages */
        .card{
            background:var(--card);
            border:1px solid var(--border);
            border-radius:var(--radius);
            padding:16px;
            box-shadow:var(--shadow);
            overflow-x:auto;
        }
        .muted{color:var(--muted);font-size:12px}
        .content .wrap{
            width:100% !important;
            max-width:none !important;
            margin:0 !important;
        }

        /* Shared form UX */
        .form-wrap{
            width:100%;
            max-width:none;
            margin:0;
        }
        .form-wrap.form-wrap-sm{max-width:none}
        .form-header{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
            flex-wrap:wrap;
        }
        .form-header h2{
            margin:0;
            font-size:22px;
            line-height:1.2;
            letter-spacing:-.02em;
        }
        .form-subtitle{
            color:#667085;
            font-size:13px;
            margin-top:4px;
        }
        .form-card{
            background:linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            border:1px solid #e4e7ec;
            border-radius:16px;
            padding:18px;
            box-shadow:0 14px 34px rgba(16,24,40,.08);
            margin-top:12px;
        }
        .pc-form{
            display:grid;
            grid-template-columns:repeat(12, minmax(0, 1fr));
            gap:14px;
        }
        .pc-field{
            grid-column:span 6;
            min-width:0;
        }
        .pc-field.full{
            grid-column:1 / -1;
        }
        .pc-field label{
            display:block;
            font-size:11px;
            font-weight:800;
            letter-spacing:.04em;
            color:#475467;
            text-transform:uppercase;
            margin-bottom:6px;
        }
        .pc-input,
        .pc-select,
        .pc-textarea{
            width:100%;
            border:1px solid #d0d5dd;
            background:#fff;
            border-radius:12px;
            padding:10px 12px;
            font-size:14px;
            color:#101828;
            transition:border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
        }
        .pc-input:focus,
        .pc-select:focus,
        .pc-textarea:focus{
            border-color:#6941c6;
            box-shadow:0 0 0 4px rgba(105,65,198,.12);
            outline:none;
            background:#fff;
        }
        .pc-input[disabled],
        .pc-input[readonly],
        .pc-select[disabled],
        .pc-textarea[readonly]{
            background:#f8fafc;
            color:#475467;
        }
        .pc-help{
            color:#667085;
            font-size:12px;
            margin-top:6px;
        }
        .pc-actions{
            grid-column:1 / -1;
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
            padding-top:2px;
        }
        .pc-inline-grid{
            grid-column:1 / -1;
            display:grid;
            grid-template-columns:repeat(12, minmax(0, 1fr));
            gap:14px;
        }
        .pc-inline-grid .pc-field{
            grid-column:span 6;
        }
        .pc-check{
            display:inline-flex;
            align-items:center;
            gap:8px;
            color:#344054;
            font-size:13px;
            font-weight:600;
        }
        .pc-field label.pc-check{
            text-transform:none;
            letter-spacing:0;
            font-size:13px;
            font-weight:700;
            margin-bottom:0;
        }
        .pc-check input{
            width:16px;
            height:16px;
            margin:0;
            accent-color:#6941c6;
        }
        .btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:6px;
            min-height:38px;
            padding:0 14px;
            border-radius:10px;
            border:1px solid #6941c6;
            background:#6941c6;
            color:#fff;
            text-decoration:none;
            font-size:13px;
            font-weight:800;
            line-height:1;
            cursor:pointer;
        }
        .btn:hover{
            background:#5b36b5;
            border-color:#5b36b5;
        }
        .btn2{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:6px;
            min-height:38px;
            padding:0 14px;
            border-radius:10px;
            border:1px solid #d0d5dd;
            background:#fff;
            color:#344054;
            text-decoration:none;
            font-size:13px;
            font-weight:800;
            line-height:1;
            cursor:pointer;
        }
        .btn2:hover{
            border-color:#98a2b3;
            background:#f9fafb;
        }
        .err{
            grid-column:1 / -1;
            background:#fef3f2;
            color:#b42318;
            border:1px solid #fecdca;
            padding:10px 12px;
            border-radius:10px;
            font-size:13px;
            font-weight:700;
        }
        .content .container{
            max-width:none !important;
            width:100% !important;
            margin:0 !important;
        }
        .content .form-wrap,
        .content .form-wrap.form-wrap-sm{
            max-width:none !important;
            width:100% !important;
            margin:0 !important;
        }

        /* Compact filters: keep filters secondary to main cards/tables */
        .pc-filter-dock{
            display:flex;
            justify-content:flex-end;
            margin:0 0 12px;
        }
        .pc-filter-panel{
            width:min(100%, 760px);
            border:1px solid #e4e7ec;
            border-radius:12px;
            background:#fff;
            box-shadow:0 8px 20px rgba(16,24,40,.06);
        }
        .pc-filter-panel summary{
            list-style:none;
            cursor:pointer;
            padding:10px 12px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            font-size:12px;
            font-weight:800;
            color:#344054;
        }
        .pc-filter-panel summary::-webkit-details-marker{display:none}
        .pc-filter-panel[open] summary{
            border-bottom:1px solid #eaecf0;
            background:#f8fafc;
            border-radius:12px 12px 0 0;
        }
        .pc-filter-title{
            display:inline-flex;
            align-items:center;
            gap:8px;
        }
        .pc-filter-title::before{
            content:"";
            width:6px;
            height:6px;
            border-radius:999px;
            background:#6941c6;
        }
        .pc-filter-state{
            font-size:11px;
            font-weight:800;
            color:#667085;
            text-transform:uppercase;
            letter-spacing:.04em;
        }
        .pc-filter-body{
            padding:12px;
        }
        .pc-filter-row{
            display:flex;
            gap:10px;
            align-items:flex-end;
            flex-wrap:wrap;
        }
        .pc-filter-body .row,
        .pc-filter-body .filters{
            margin-top:0;
        }
        .pc-filter-row .muted{
            font-size:11px;
            margin-bottom:4px;
        }
        .pc-filter-row input,
        .pc-filter-row select{
            min-width:120px;
        }
        .pc-filter-grow{
            flex:1 1 300px;
            min-width:220px;
        }
        @media(max-width:900px){
            .pc-field{grid-column:1 / -1}
            .pc-inline-grid .pc-field{grid-column:1 / -1}
        }

        /* ===== Mobile behaviour ===== */
        @media(max-width: 980px){
            .app{grid-template-columns:1fr;}
            .sidebar{
                position:fixed;
                z-index:30;
                left:0; top:0;
                width:280px;
                height:100vh;
                transform:translateX(-105%);
                transition:transform .22s ease;
                box-shadow:0 20px 60px rgba(16,24,40,.22);
            }
            body.sidebar-open .sidebar{transform:translateX(0);}
            body.sidebar-open{overflow:hidden;}

            .menu-btn{display:inline-block}
            .overlay{
                display:none;
                position:fixed;
                inset:0;
                background:rgba(16,24,40,.45);
                z-index:25;
            }
            body.sidebar-open .overlay{display:block}
            .content{padding:14px}
            .container{max-width:100%}
            .form-card{padding:14px}
        }

        /* Shared table UX */
        .content .table-wrap{
            overflow:auto;
            -webkit-overflow-scrolling:touch;
            width:100%;
            margin-top:12px;
            border:1px solid var(--border);
            border-radius:12px;
            background:#fff;
        }

        .content .card table,
        .content .table-wrap table{
            width:100%;
            border-collapse:separate;
            border-spacing:0;
            margin-top:12px;
            min-width:820px;
        }

        .content .table-wrap table{
            margin-top:0;
        }

        .content .card > table{
            border:1px solid var(--border);
            border-radius:12px;
            overflow:hidden;
        }

        .content .card table th,
        .content .card table td,
        .content .table-wrap table th,
        .content .table-wrap table td{
            padding:11px 12px;
            border-bottom:1px solid #eaecf0;
            font-size:13px;
            vertical-align:middle;
            text-align:left;
        }

        .content .card table th,
        .content .table-wrap table th{
            background:var(--thead);
            color:#475467;
            font-size:11px;
            font-weight:800;
            letter-spacing:.04em;
            text-transform:uppercase;
            white-space:nowrap;
        }

        .content .table-wrap table th{
            position:sticky;
            top:0;
            z-index:2;
        }

        .content .card table tbody tr:nth-child(even),
        .content .table-wrap table tbody tr:nth-child(even){
            background:var(--row-alt);
        }

        .content .card table tbody tr:hover,
        .content .table-wrap table tbody tr:hover{
            background:var(--row-hover);
        }

        .content .card table tbody tr:last-child td,
        .content .table-wrap table tbody tr:last-child td{
            border-bottom:none;
        }

        .content .card table .num,
        .content .card table .text-end,
        .content .table-wrap table .num,
        .content .table-wrap table .text-end{
            text-align:right;
            white-space:nowrap;
        }

        .petty-pager{
            display:flex;
            align-items:center;
            justify-content:flex-start;
            gap:8px;
            flex-wrap:wrap;
        }
        .petty-pager-btn,
        .petty-page-link{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:34px;
            height:34px;
            padding:0 10px;
            border:1px solid #d0d5dd;
            border-radius:8px;
            background:#fff;
            color:#344054;
            font-size:13px;
            font-weight:800;
            text-decoration:none;
        }
        .petty-pager-btn:hover,
        .petty-page-link:hover{
            border-color:#98a2b3;
            background:#f9fafb;
        }
        .petty-pager-btn.is-disabled{
            color:#98a2b3;
            background:#f9fafb;
            pointer-events:none;
        }
        .petty-page-link.is-active{
            background:#111827;
            border-color:#111827;
            color:#fff;
        }
        .petty-page-list{
            display:flex;
            align-items:center;
            gap:6px;
            flex-wrap:wrap;
        }
        .petty-page-gap{
            color:#98a2b3;
            padding:0 2px;
            font-size:13px;
            font-weight:800;
        }

        .pc-toast-stack{
            position:fixed;
            top:74px;
            left:50%;
            transform:translateX(-50%);
            z-index:120;
            display:grid;
            gap:10px;
            width:min(540px, calc(100vw - 24px));
            pointer-events:none;
        }
        .pc-toast{
            pointer-events:auto;
            border-radius:14px;
            border:1px solid #d0d5dd;
            background:#fff;
            color:#101828;
            box-shadow:0 18px 42px rgba(16,24,40,.20);
            padding:12px 14px 10px;
            animation:pcToastIn .22s ease-out;
            display:grid;
            gap:8px;
        }
        @keyframes pcToastIn{
            from{opacity:0;transform:translateY(-10px) scale(.98)}
            to{opacity:1;transform:translateY(0) scale(1)}
        }
        .pc-toast-main{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:10px;
        }
        .pc-toast-content{
            display:flex;
            align-items:flex-start;
            gap:10px;
            min-width:0;
        }
        .pc-toast-icon{
            width:22px;
            height:22px;
            border-radius:50%;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:12px;
            font-weight:900;
            flex:0 0 auto;
            margin-top:1px;
        }
        .pc-toast-title{
            font-size:12px;
            font-weight:900;
            letter-spacing:.02em;
            text-transform:uppercase;
            margin-bottom:2px;
        }
        .pc-toast-message{
            font-size:13px;
            font-weight:700;
            line-height:1.4;
            color:#101828;
            word-break:break-word;
        }
        .pc-toast-close{
            border:none;
            background:transparent;
            color:#667085;
            font-weight:900;
            line-height:1;
            cursor:pointer;
            padding:0;
            font-size:16px;
        }
        .pc-toast-close:hover{color:#101828}
        .pc-toast-progress{
            height:3px;
            border-radius:999px;
            background:rgba(16,24,40,.09);
            overflow:hidden;
        }
        .pc-toast-progress > i{
            display:block;
            height:100%;
            width:100%;
            transform-origin:left center;
            animation:pcToastBar var(--toast-ms, 5600ms) linear forwards;
        }
        @keyframes pcToastBar{
            from{transform:scaleX(1)}
            to{transform:scaleX(0)}
        }
        .pc-toast-success{border-color:#abefc6;background:#f0fdf4}
        .pc-toast-success .pc-toast-icon{background:#d1fadf;color:#067647}
        .pc-toast-success .pc-toast-title{color:#067647}
        .pc-toast-success .pc-toast-progress > i{background:#12b76a}
        .pc-toast-error{border-color:#fecdca;background:#fef3f2}
        .pc-toast-error .pc-toast-icon{background:#fee4e2;color:#b42318}
        .pc-toast-error .pc-toast-title{color:#b42318}
        .pc-toast-error .pc-toast-progress > i{background:#f04438}
        .pc-toast-warning{border-color:#fedf89;background:#fffaeb}
        .pc-toast-warning .pc-toast-icon{background:#fef0c7;color:#b54708}
        .pc-toast-warning .pc-toast-title{color:#b54708}
        .pc-toast-warning .pc-toast-progress > i{background:#f79009}

        @media(max-width:980px){
            .pc-toast-stack{top:66px}
            .petty-pager{
                justify-content:center;
            }
        }


    </style>

    @stack('styles')
</head>
<body>
<div class="app">
    {{-- Sidebar --}}
    @include('pettycash::partials.nav')

    {{-- Main --}}
    <div class="main">
        @php
            $pettyToasts = [];
            if (session('success')) {
                $pettyToasts[] = ['type' => 'success', 'title' => 'Success', 'message' => (string) session('success'), 'dismiss_ms' => 5600];
            }
            if (session('error')) {
                $pettyToasts[] = ['type' => 'error', 'title' => 'Error', 'message' => (string) session('error'), 'dismiss_ms' => 7600];
            }
            if (session('warning')) {
                $pettyToasts[] = ['type' => 'warning', 'title' => 'Warning', 'message' => (string) session('warning'), 'dismiss_ms' => 6800];
            }
            if ($errors->any() && !session('error')) {
                $pettyToasts[] = ['type' => 'error', 'title' => 'Validation Error', 'message' => (string) $errors->first(), 'dismiss_ms' => 7600];
            }
        @endphp
        @if(!empty($pettyToasts))
            <div id="pettyToastStack" class="pc-toast-stack" role="status" aria-live="polite">
                @foreach($pettyToasts as $toast)
                    @php
                        $toastType = in_array($toast['type'], ['success', 'error', 'warning'], true) ? $toast['type'] : 'success';
                        $toastDismissMs = (int) ($toast['dismiss_ms'] ?? 5600);
                        $toastIcon = $toastType === 'success' ? '✓' : ($toastType === 'warning' ? '!' : '×');
                    @endphp
                    <div class="pc-toast pc-toast-{{ $toastType }}" data-auto-dismiss="1" data-dismiss-ms="{{ $toastDismissMs }}">
                        <div class="pc-toast-main">
                            <div class="pc-toast-content">
                                <span class="pc-toast-icon" aria-hidden="true">{{ $toastIcon }}</span>
                                <div>
                                    <div class="pc-toast-title">{{ $toast['title'] }}</div>
                                    <div class="pc-toast-message">{{ $toast['message'] }}</div>
                                </div>
                            </div>
                            <button class="pc-toast-close" type="button" onclick="dismissToast(this)" aria-label="Dismiss notification">×</button>
                        </div>
                        <div class="pc-toast-progress" style="--toast-ms: {{ $toastDismissMs }}ms"><i></i></div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-btn" type="button" onclick="toggleSidebar()">Menu</button>
                <div class="topbar-title">@yield('title','PettyCash')</div>
            </div>

            <div class="topbar-right">
                @if($pettyCanViewNotifications)
                    <a href="{{ route('petty.notifications.index') }}" class="top-icon-btn" aria-label="Notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M14.5 19a2.5 2.5 0 0 1-5 0m9-3H5.5a1 1 0 0 1-.76-1.65l1.27-1.48V10a6 6 0 1 1 12 0v2.87l1.27 1.48A1 1 0 0 1 18.5 16Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        @if($pettyUnread > 0)
                            <span class="notify-badge">
                                {{ $pettyUnread }}
                            </span>
                        @endif
                    </a>
                @endif

                <div class="profile-menu" id="pettyProfileMenu">
                    <button class="avatar-btn" type="button" aria-haspopup="menu" aria-expanded="false" onclick="toggleProfileMenu(event)">
                        <span class="avatar-dot">{{ $pettyInitials }}</span>
                        <span class="avatar-caret">▾</span>
                    </button>
                    <div class="profile-dropdown" id="pettyProfileDropdown" hidden>
                        <div class="profile-head">
                            <div class="name">{{ $pettyUser?->name ?? 'Petty User' }}</div>
                            <div class="sub">{{ $pettyUser?->email ?? '' }}</div>
                        </div>
                        <a class="profile-item" href="{{ route('petty.profile.index') }}">Profile</a>
                        @if($pettyCanManageSettings)
                            <a class="profile-item" href="{{ route('petty.settings.index') }}">Settings</a>
                        @endif
                        <form method="POST" action="{{ route('petty.logout') }}" style="margin:0">
                            @csrf
                            <button class="profile-item" type="submit" onclick="return confirm('Logout from PettyCash?')">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container">
                @yield('content')
            </div>
        </div>
    </div>
</div>

{{-- mobile overlay --}}
<div class="overlay" onclick="toggleSidebar()"></div>

<script>
    function toggleSidebar(){
        document.body.classList.toggle('sidebar-open');
    }

    function dismissToast(button){
        const toast = button?.closest('.pc-toast');
        if (toast) {
            toast.remove();
        }
    }

    document.querySelectorAll('.pc-toast[data-auto-dismiss="1"]').forEach(function (toast) {
        const timeoutMs = Math.max(2200, Number(toast.getAttribute('data-dismiss-ms') || 5600));
        window.setTimeout(function () {
            toast.remove();
        }, timeoutMs);
    });

    function closeActionMenus(exceptMenu){
        document.querySelectorAll('details.action-menu[open]').forEach(function (menu) {
            if (exceptMenu && menu === exceptMenu) return;
            menu.removeAttribute('open');
        });
    }

    document.addEventListener('toggle', function (event) {
        const menu = event.target;
        if (!(menu instanceof HTMLDetailsElement)) return;
        if (!menu.matches('details.action-menu')) return;
        if (menu.open) closeActionMenus(menu);
    }, true);

    document.addEventListener('click', function (event) {
        const clickedMenu = event.target.closest('details.action-menu');
        if (!clickedMenu) {
            closeActionMenus();
            return;
        }

        const clickedAction = event.target.closest('.action-menu-item');
        if (clickedAction && clickedAction.getAttribute('aria-disabled') !== 'true') {
            window.setTimeout(function () {
                clickedMenu.removeAttribute('open');
            }, 0);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeActionMenus();
        }
    });

    function toggleProfileMenu(event){
        event.stopPropagation();
        const menu = document.getElementById('pettyProfileMenu');
        const dropdown = document.getElementById('pettyProfileDropdown');
        const trigger = menu ? menu.querySelector('.avatar-btn') : null;
        if (!dropdown || !trigger) return;

        const isOpen = !dropdown.hasAttribute('hidden');
        if (isOpen) {
            dropdown.setAttribute('hidden', '');
            trigger.setAttribute('aria-expanded', 'false');
        } else {
            dropdown.removeAttribute('hidden');
            trigger.setAttribute('aria-expanded', 'true');
        }
    }

    function closeProfileMenu(){
        const menu = document.getElementById('pettyProfileMenu');
        const dropdown = document.getElementById('pettyProfileDropdown');
        const trigger = menu ? menu.querySelector('.avatar-btn') : null;
        if (!dropdown || !trigger) return;
        dropdown.setAttribute('hidden', '');
        trigger.setAttribute('aria-expanded', 'false');
    }

    document.addEventListener('click', function (event) {
        const menu = document.getElementById('pettyProfileMenu');
        if (!menu || menu.contains(event.target)) return;
        closeProfileMenu();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeProfileMenu();
        }
    });
</script>

@include('partials.back_iconize')

@stack('scripts')
</body>
</html>
