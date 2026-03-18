@extends('inventory::layout')

@section('page-title', 'Deployments')

@section('inventory-content')
@php
    $section = $section ?? 'sites';
    $deployments = $deployments ?? collect();
    $siteWorkspace = $siteWorkspace ?? ['paginator' => collect(), 'focus' => null, 'router_count' => 0, 'site_count' => 0];
    $siteData = $siteData ?? ['available' => false, 'message' => 'No site data loaded.', 'fetched_at' => null];
    $siteFilters = $siteFilters ?? [];
    $sitePaginator = $siteWorkspace['paginator'] ?? null;
    $q = $q ?? '';
    $from = $from ?? '';
    $to = $to ?? '';
    $technician = $technician ?? '';
    $loading = request()->boolean('loading');

    $siteTabQuery = array_filter([
        'section' => 'sites',
        'search' => $siteFilters['search'] ?? '',
        'page_size' => $siteFilters['page_size'] ?? 20,
        'site_id' => $siteFilters['site_id'] ?? '',
    ], fn ($value) => $value !== null && $value !== '');
    $historyTabQuery = array_filter([
        'section' => 'history',
        'q' => $q,
        'from' => $from,
        'to' => $to,
        'technician' => $technician,
    ], fn ($value) => $value !== null && $value !== '');
    $focusDismissQuery = request()->query();
    unset($focusDismissQuery['focus_site']);

    $siteCount = (int) ($siteWorkspace['site_count'] ?? 0);
    $routerCount = (int) ($siteWorkspace['router_count'] ?? 0);
    $fetchedAt = !empty($siteData['fetched_at'])
        ? \Illuminate\Support\Carbon::parse($siteData['fetched_at'])->format('Y-m-d H:i:s')
        : 'Live table';

    $buildSiteFocusUrl = function (array $group): string {
        return route('inventory.deployments.index', array_filter(array_merge(request()->query(), [
            'section' => 'sites',
            'focus_site' => $group['site_key'] ?? null,
        ]), fn ($value) => $value !== null && $value !== ''));
    };

    $buildUpdateDeploymentUrl = function (array $group): string {
        return route('inventory.admin.deploy.create', array_filter([
            'site_name' => $group['site_name'] ?? null,
            'site_code' => $group['site_id'] ?? null,
            'reference' => $group['serial'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));
    };

    $buildSiteRoutersUrl = function (array $group): string {
        return route('inventory.routers.index', array_filter([
            'section' => 'deployed',
            'site_id' => $group['site_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));
    };
@endphp

<style>
    .deploy-shell{ display:grid; gap:18px; }
    .deploy-toolbar{
        padding:18px;
        background:
            radial-gradient(circle at top right, rgba(47, 162, 120, .18), transparent 24%),
            radial-gradient(circle at bottom left, rgba(64, 145, 255, .12), transparent 26%),
            linear-gradient(145deg, #fffdf8 0%, #f4fbf1 54%, #edf8f5 100%);
    }
    .deploy-toolbar-row{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:14px;
        flex-wrap:wrap;
    }
    .deploy-toolbar-main{
        display:grid;
        gap:12px;
        min-width:0;
        flex:1 1 560px;
    }
    .deploy-tabs{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
    }
    .deploy-pill-row{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        align-items:center;
    }
    .deploy-stat-pill{
        display:inline-flex;
        align-items:center;
        gap:8px;
        min-height:42px;
        padding:8px 12px;
        border-radius:999px;
        border:1px solid var(--line);
        background:rgba(255,255,255,.94);
        box-shadow:0 10px 18px rgba(22, 52, 37, .06);
        color:var(--text-soft);
        font-size:13px;
        font-weight:700;
    }
    .deploy-stat-pill strong{
        font-family:"Space Grotesk", sans-serif;
        font-size:14px;
        color:var(--text);
    }
    .deploy-toolbar-actions{
        display:flex;
        gap:8px;
        align-items:center;
        flex-wrap:wrap;
        justify-content:flex-end;
        flex:0 0 auto;
    }
    .deploy-icon-btn{
        width:40px;
        height:40px;
        border-radius:999px;
        border:1px solid var(--line-strong);
        background:rgba(255,255,255,.98);
        color:var(--text);
        display:inline-flex;
        align-items:center;
        justify-content:center;
        box-shadow:0 10px 18px rgba(22, 52, 37, .06);
    }
    .deploy-info-list{
        min-width:280px;
        padding:10px;
    }
    .deploy-info-line{
        padding:8px 10px;
        border-radius:12px;
        background:linear-gradient(180deg, #fff9ef 0%, #eef8f0 100%);
        color:var(--text-soft);
        font-size:12px;
        line-height:1.45;
    }
    .deploy-filter-card{
        padding:18px;
    }
    .deploy-filter-grid{
        display:grid;
        grid-template-columns:minmax(260px, 1.8fr) minmax(160px, .8fr) minmax(120px, .55fr) auto;
        gap:12px;
        align-items:end;
    }
    .deploy-filter-actions{
        display:flex;
        gap:8px;
        align-items:center;
        flex-wrap:wrap;
    }
    .deploy-table-card{
        overflow:hidden;
    }
    .deploy-row-title{
        display:grid;
        gap:8px;
    }
    .deploy-site-name{
        font-family:"Space Grotesk", sans-serif;
        font-size:22px;
        line-height:.96;
        letter-spacing:-.05em;
        color:var(--text);
    }
    .deploy-site-meta,
    .deploy-row-sub{
        color:var(--muted);
        font-size:12px;
        line-height:1.45;
    }
    .deploy-inline-badges{
        display:flex;
        flex-wrap:wrap;
        gap:6px;
        align-items:center;
    }
    .deploy-badge{
        display:inline-flex;
        align-items:center;
        gap:.35rem;
        padding:6px 10px;
        border-radius:999px;
        border:1px solid var(--line);
        background:#f4fbf6;
        color:var(--text-soft);
        font-size:12px;
        font-weight:700;
        line-height:1;
    }
    .deploy-badge.ok{
        background:#ebf7ef;
        border-color:rgba(77, 138, 102, .22);
        color:var(--brand-strong);
    }
    .deploy-badge.warn{
        background:#fff7ea;
        border-color:rgba(165, 109, 24, .22);
        color:#8c6318;
    }
    .deploy-badge.dark{
        background:linear-gradient(180deg, #335a49 0%, #1e382d 100%);
        border-color:#1e382d;
        color:#ffffff;
    }
    .deploy-menu-trigger{
        min-width:auto;
        height:40px;
        padding:0 14px;
    }
    .deploy-modal-layer{
        position:fixed;
        inset:0;
        z-index:70;
        display:grid;
        place-items:center;
        padding:24px;
    }
    .deploy-modal-backdrop{
        position:absolute;
        inset:0;
        background:rgba(15, 30, 22, .42);
        backdrop-filter:blur(8px);
    }
    .deploy-modal-card{
        position:relative;
        width:min(1180px, calc(100vw - 48px));
        max-height:calc(100vh - 48px);
        overflow:auto;
        border-radius:30px;
        border:1px solid rgba(255,255,255,.36);
        background:
            radial-gradient(circle at top right, rgba(47, 162, 120, .18), transparent 24%),
            radial-gradient(circle at bottom left, rgba(64, 145, 255, .10), transparent 26%),
            linear-gradient(180deg, rgba(255,253,247,.98) 0%, rgba(244,251,240,.98) 100%);
        box-shadow:0 34px 70px rgba(14, 32, 24, .26);
    }
    .deploy-modal-head{
        position:sticky;
        top:0;
        z-index:2;
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:18px;
        flex-wrap:wrap;
        padding:22px 24px 18px;
        border-bottom:1px solid var(--line);
        background:rgba(255, 252, 244, .92);
        backdrop-filter:blur(12px);
    }
    .deploy-modal-title{
        margin:0;
        font-family:"Space Grotesk", sans-serif;
        font-size:34px;
        line-height:.95;
        letter-spacing:-.06em;
    }
    .deploy-modal-sub{
        margin-top:8px;
        color:var(--muted);
        font-size:14px;
        line-height:1.45;
    }
    .deploy-modal-actions{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        align-items:center;
        justify-content:flex-end;
    }
    .deploy-modal-body{
        padding:22px 24px 24px;
        display:grid;
        gap:18px;
    }
    .deploy-focus-grid{
        display:grid;
        grid-template-columns:repeat(4, minmax(0, 1fr));
        gap:12px;
    }
    .deploy-focus-card{
        min-height:100%;
        padding:16px;
        border-radius:22px;
        border:1px solid var(--line);
        background:rgba(255,255,255,.9);
        box-shadow:0 14px 26px rgba(22, 52, 37, .08);
    }
    .deploy-focus-label{
        color:var(--muted);
        font-size:11px;
        font-weight:700;
        letter-spacing:.16em;
        text-transform:uppercase;
    }
    .deploy-focus-value{
        margin-top:8px;
        color:var(--text);
        font-size:14px;
        line-height:1.5;
        font-weight:700;
    }
    .deploy-focus-sub{
        margin-top:4px;
        color:var(--muted);
        font-size:12px;
        line-height:1.4;
    }
    .deploy-table-wrap{
        width:100%;
        overflow:visible;
    }
    .deploy-site-table{
        min-width:0 !important;
        table-layout:fixed;
    }
    .deploy-site-table th,
    .deploy-site-table td{
        white-space:normal;
        overflow-wrap:anywhere;
    }
    .deploy-empty-row{
        padding:18px 0;
        text-align:center;
        color:var(--muted);
    }
    .deploy-history-filters{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        align-items:center;
    }
    @media (max-width: 1199.98px){
        .deploy-filter-grid,
        .deploy-focus-grid{
            grid-template-columns:repeat(2, minmax(0, 1fr));
        }
        .deploy-modal-card{
            width:min(100%, calc(100vw - 28px));
        }
    }
    @media (max-width: 991.98px){
        .deploy-site-table thead{
            display:none;
        }
        .deploy-site-table,
        .deploy-site-table tbody,
        .deploy-site-table tr,
        .deploy-site-table td{
            display:block;
            width:100%;
        }
        .deploy-site-table tr{
            border:1px solid var(--line);
            border-radius:22px;
            margin-bottom:12px;
            background:rgba(255,255,255,.94);
            box-shadow:0 12px 22px rgba(22, 52, 37, .06);
            overflow:hidden;
        }
        .deploy-site-table td{
            display:grid;
            grid-template-columns:92px minmax(0, 1fr);
            gap:12px;
            padding:12px 14px !important;
            border-bottom:1px solid var(--line) !important;
        }
        .deploy-site-table td:last-child{
            border-bottom:0 !important;
        }
        .deploy-site-table td::before{
            content:attr(data-label);
            color:var(--muted);
            font-size:11px;
            font-weight:700;
            letter-spacing:.12em;
            text-transform:uppercase;
            padding-top:2px;
        }
        .deploy-site-table td[data-label="Actions"]{
            display:flex;
            justify-content:flex-end;
            padding-top:4px !important;
        }
        .deploy-site-table td[data-label="Actions"]::before{
            display:none;
        }
    }
    @media (max-width: 767.98px){
        .deploy-filter-grid,
        .deploy-focus-grid{
            grid-template-columns:1fr;
        }
        .deploy-modal-layer{
            padding:14px;
        }
        .deploy-modal-card{
            width:100%;
            max-height:calc(100vh - 28px);
        }
        .deploy-modal-head,
        .deploy-modal-body{
            padding-left:18px;
            padding-right:18px;
        }
        .deploy-modal-title{
            font-size:28px;
        }
    }
</style>

<div class="deploy-shell">
    <section class="inv-card deploy-toolbar">
        <div class="deploy-toolbar-row">
            <div class="deploy-toolbar-main">
                <div class="deploy-tabs">
                    <a class="router-tab {{ $section === 'sites' ? 'active' : '' }}" href="{{ route('inventory.deployments.index', $siteTabQuery) }}" data-inv-loading>Sites</a>
                    <a class="router-tab {{ $section === 'history' ? 'active' : '' }}" href="{{ route('inventory.deployments.index', $historyTabQuery) }}" data-inv-loading>History</a>
                </div>

                @if($section === 'sites')
                    <div class="deploy-pill-row">
                        <span class="deploy-stat-pill"><strong>Sites</strong> {{ number_format($siteCount) }}</span>
                        <span class="deploy-stat-pill"><strong>Routers</strong> {{ number_format($routerCount) }}</span>
                        <span class="deploy-stat-pill"><strong>Reported</strong> {{ $fetchedAt }}</span>
                    </div>
                @else
                    <div class="deploy-pill-row">
                        <span class="deploy-stat-pill"><strong>Rows</strong> {{ number_format((int) ($deployments->total() ?? $deployments->count())) }}</span>
                        <span class="deploy-stat-pill"><strong>Technicians</strong> {{ number_format($deployments->pluck('technician_id')->filter()->unique()->count()) }}</span>
                        <span class="deploy-stat-pill"><strong>Scope</strong> Local deployment audit</span>
                    </div>
                @endif
            </div>

            <div class="deploy-toolbar-actions">
                @if($section === 'sites')
                    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.routers.index', ['section' => 'deployed']) }}" data-inv-loading>
                        <i class="bi bi-hdd-network me-1"></i> Deployed Routers
                    </a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.assignments.index') }}" data-inv-loading>
                        <i class="bi bi-person-check me-1"></i> Tech Assignments
                    </a>
                    <details class="action-menu">
                        <summary class="deploy-icon-btn" aria-label="Workspace info">
                            <i class="bi bi-info-lg"></i>
                        </summary>
                        <div class="action-menu-list deploy-info-list">
                            <div class="deploy-info-line">All sites listed here are from ONTs.</div>
                            <div class="deploy-info-line">Main router is resolved from the ONT serial automatically.</div>
                            <div class="deploy-info-line">Open any site to inspect the full router set and perform any actions.</div>
                        </div>
                    </details>
                @else
                    <details class="action-menu">
                        <summary class="deploy-icon-btn" aria-label="History info">
                            <i class="bi bi-info-lg"></i>
                        </summary>
                        <div class="action-menu-list deploy-info-list">
                            <div class="deploy-info-line">History.</div>
                        </div>
                    </details>
                @endif
            </div>
        </div>
    </section>

    <section class="inv-card deploy-filter-card">
        @if($section === 'sites')
            <form method="GET" action="{{ route('inventory.deployments.index') }}" data-inv-loading data-inv-autofilter>
                <input type="hidden" name="section" value="sites">
                <div class="deploy-filter-grid">
                    <div>
                        <label class="form-label">Search</label>
                        <input class="form-control form-control-sm" name="search" value="{{ $siteFilters['search'] ?? '' }}" placeholder="Search site, ONT, main router, serial">
                    </div>
                    <div>
                        <label class="form-label">Site ID</label>
                        <input class="form-control form-control-sm" type="number" min="1" name="site_id" value="{{ $siteFilters['site_id'] ?? '' }}" placeholder="421662">
                    </div>
                    <div>
                        <label class="form-label">Page Size</label>
                        <select class="form-select form-select-sm" name="page_size">
                            @foreach([10, 20, 50, 100] as $pageSize)
                                <option value="{{ $pageSize }}" @selected((int) ($siteFilters['page_size'] ?? 20) === $pageSize)>{{ $pageSize }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="deploy-filter-actions">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.deployments.index', ['section' => 'sites']) }}" data-inv-loading>Reset</a>
                    </div>
                </div>
            </form>
        @else
            <form class="deploy-history-filters" method="GET" action="{{ route('inventory.deployments.index') }}" data-inv-loading data-inv-autofilter>
                <input type="hidden" name="section" value="history">
                <input class="form-control form-control-sm" name="q" value="{{ $q }}" placeholder="Search site, reference, item..." style="min-width:220px;">
                <input class="form-control form-control-sm" type="date" name="from" value="{{ $from }}" title="From" style="min-width:150px;">
                <input class="form-control form-control-sm" type="date" name="to" value="{{ $to }}" title="To" style="min-width:150px;">
                <input class="form-control form-control-sm" name="technician" value="{{ $technician }}" placeholder="Technician..." style="min-width:170px;">
                @if(trim($q) !== '' || $from || $to || trim($technician) !== '')
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.deployments.index', ['section' => 'history']) }}" data-inv-loading>Reset</a>
                @endif
            </form>
        @endif
    </section>

    @if($section === 'sites')
        @if(!($siteData['available'] ?? false))
            <div class="inv-alert warning">
                <div class="inv-alert-ico"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <strong>Skybrix query unavailable</strong>
                    <div>{{ $siteData['message'] ?? 'The upstream request failed.' }}</div>
                </div>
            </div>
        @endif

        <section class="inv-card inv-table-card deploy-table-card">
            <div class="inv-table-head">
                <div>
                    <p class="inv-table-title mb-0">Site Deployments</p>
                    <div class="inv-table-sub">Recent sites first. Keep the list focused on the main router and ONT link, then open a site only when you need more detail.</div>
                </div>
            </div>

            <div class="inv-table-body">
                <div class="table-responsive deploy-table-wrap">
                    <table class="table table-sm align-middle table-hover mb-0 deploy-site-table">
                        <thead>
                            <tr>
                                <th>Site</th>
                                <th style="width:250px;">Main Router</th>
                                <th style="width:260px;">ONT / Link</th>
                                <th style="width:170px;">Latest</th>
                                <th style="width:92px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sitePaginator as $group)
                                @php
                                    $group = (array) $group;
                                    $mainRouter = (array) ($group['main_router'] ?? []);
                                    $mainSerial = trim((string) ($mainRouter['serial_number'] ?? $group['serial'] ?? ''));
                                    $mainName = trim((string) (($mainRouter['brand'] ?? '') . ' ' . ($mainRouter['model_number'] ?? '')));
                                    $mainSub = ($mainRouter['_synthetic_main'] ?? false)
                                        ? 'ONT-linked main'
                                        : ($mainName !== '' ? $mainName : 'Primary router');
                                    $status = mb_strtolower((string) ($group['status'] ?? ''));
                                    $siteFocusUrl = $buildSiteFocusUrl($group);
                                    $updateDeploymentUrl = $buildUpdateDeploymentUrl($group);
                                    $siteRoutersUrl = $buildSiteRoutersUrl($group);
                                @endphp
                                <tr>
                                    <td data-label="Site">
                                        <div class="deploy-row-title">
                                            <div class="deploy-site-name">{{ $group['site_name'] ?: 'Site' }}</div>
                                            <div class="deploy-site-meta">
                                                Site ID: {{ $group['site_id'] ?: '—' }}
                                                @if(!empty($group['serial']))
                                                    • ONT: {{ $group['serial'] }}
                                                @endif
                                            </div>
                                            <div class="deploy-inline-badges">
                                                <span class="deploy-badge {{ $status === 'online' ? 'ok' : ($status === 'offline' ? 'warn' : '') }}">{{ $group['status'] ?: 'Unknown' }}</span>
                                                <span class="deploy-badge">Routers {{ $group['router_count'] }}</span>
                                                <span class="deploy-badge dark">Main {{ $group['main_count'] }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Main Router">
                                        <div class="fw-bold text-dark">{{ $mainSerial !== '' ? $mainSerial : '—' }}</div>
                                        <div class="deploy-row-sub">{{ $mainSub }}</div>
                                        @if(!empty($mainRouter['batch_number']))
                                            <div class="deploy-row-sub">Batch {{ $mainRouter['batch_number'] }}</div>
                                        @endif
                                        @if(!empty($mainRouter['router_status']) || !empty($mainRouter['installed_date']))
                                            <div class="deploy-row-sub">
                                                {{ $mainRouter['router_status'] ?? 'Status pending' }}
                                                @if(!empty($mainRouter['installed_date']))
                                                    • {{ $mainRouter['installed_date'] }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="ONT / Link">
                                        <div class="fw-bold text-dark">
                                            RX {{ $group['rx_power'] ?? '—' }} / TX {{ $group['tx_power'] ?? '—' }}
                                        </div>
                                        <div class="deploy-row-sub">
                                            OLT {{ $group['olt'] ?? '—' }} • Slot {{ $group['slot'] ?? '—' }} • PON {{ $group['pon'] ?? '—' }}
                                        </div>
                                        <div class="deploy-row-sub">ACS {{ $group['acs_last_inform'] ?? '—' }}</div>
                                    </td>
                                    <td data-label="Latest">
                                        <div class="fw-bold text-dark">{{ $group['latest_activity_at'] ?? '—' }}</div>
                                        <div class="deploy-row-sub">Most recent site activity</div>
                                    </td>
                                    <td data-label="Actions" class="text-end">
                                        <details class="action-menu">
                                            <summary class="btn btn-sm btn-outline-dark deploy-menu-trigger">
                                                Actions <i class="bi bi-chevron-down ms-1"></i>
                                            </summary>
                                            <div class="action-menu-list">
                                                <a class="action-menu-item" href="{{ $siteFocusUrl }}" data-inv-loading>View Site</a>
                                                <a class="action-menu-item is-highlight" href="{{ $updateDeploymentUrl }}" data-inv-loading>Update Deployment</a>
                                                <a class="action-menu-item" href="{{ $siteRoutersUrl }}" data-inv-loading>Deployed Routers</a>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-0">
                                        <div class="inv-empty">
                                            <div class="inv-empty-ico"><i class="bi bi-geo-alt"></i></div>
                                            <p class="inv-empty-title mb-0">No sites found</p>
                                            <div class="inv-empty-sub">Adjust the filters and try again.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($sitePaginator && $sitePaginator->hasPages())
                    <div class="pt-3">{{ $sitePaginator->links() }}</div>
                @endif
            </div>
        </section>

        @if(!empty($siteWorkspace['focus']))
            @php
                $focus = (array) $siteWorkspace['focus'];
                $focusMainRouter = (array) ($focus['main_router'] ?? []);
                $focusStatus = mb_strtolower((string) ($focus['status'] ?? ''));
                $focusUpdateUrl = $buildUpdateDeploymentUrl($focus);
                $focusRoutersUrl = $buildSiteRoutersUrl($focus);
                $focusMainSerial = trim((string) ($focusMainRouter['serial_number'] ?? $focus['serial'] ?? ''));
                $focusMainName = trim((string) (($focusMainRouter['brand'] ?? '') . ' ' . ($focusMainRouter['model_number'] ?? '')));
                $focusMainSub = ($focusMainRouter['_synthetic_main'] ?? false)
                    ? 'ONT-linked main'
                    : ($focusMainName !== '' ? $focusMainName : 'Primary router');
            @endphp
            <div class="deploy-modal-layer">
                <a class="deploy-modal-backdrop" href="{{ route('inventory.deployments.index', $focusDismissQuery) }}" aria-label="Close site details" data-inv-loading></a>

                <section class="deploy-modal-card">
                    <div class="deploy-modal-head">
                        <div>
                            <h2 class="deploy-modal-title">{{ $focus['site_name'] ?: 'Site' }}</h2>
                            <div class="deploy-modal-sub">
                                Site ID: {{ $focus['site_id'] ?: '—' }}
                                @if(!empty($focus['serial']))
                                    • ONT Serial: {{ $focus['serial'] }}
                                @endif
                                @if(!empty($focus['latest_activity_at']))
                                    • Latest: {{ $focus['latest_activity_at'] }}
                                @endif
                            </div>
                        </div>

                        <div class="deploy-modal-actions">
                            <span class="deploy-badge {{ $focusStatus === 'online' ? 'ok' : ($focusStatus === 'offline' ? 'warn' : '') }}">{{ $focus['status'] ?: 'Unknown' }}</span>
                            <span class="deploy-badge">Routers {{ $focus['router_count'] }}</span>
                            <span class="deploy-badge dark">Main {{ $focus['main_count'] }}</span>
                            <a class="btn btn-sm btn-dark" href="{{ $focusUpdateUrl }}" data-inv-loading>
                                <i class="bi bi-pencil-square me-1"></i> Update Deployment
                            </a>
                            <details class="action-menu">
                                <summary class="btn btn-sm btn-outline-dark deploy-menu-trigger">
                                    Actions <i class="bi bi-chevron-down ms-1"></i>
                                </summary>
                                <div class="action-menu-list">
                                    <a class="action-menu-item" href="{{ $focusRoutersUrl }}" data-inv-loading>Open Deployed Routers</a>
                                    <a class="action-menu-item is-highlight" href="{{ $focusUpdateUrl }}" data-inv-loading>Update Deployment</a>
                                </div>
                            </details>
                            <a class="btn btn-sm btn-outline-dark" href="{{ route('inventory.deployments.index', $focusDismissQuery) }}" data-inv-loading>
                                <i class="bi bi-x-lg me-1"></i> Close
                            </a>
                        </div>
                    </div>

                    <div class="deploy-modal-body">
                        <div class="deploy-focus-grid">
                            <article class="deploy-focus-card">
                                <div class="deploy-focus-label">Main Router</div>
                                <div class="deploy-focus-value">{{ $focusMainSerial !== '' ? $focusMainSerial : '—' }}</div>
                                <div class="deploy-focus-sub">
                                    {{ $focusMainSub }}
                                    @if(!empty($focusMainRouter['batch_number']))
                                        • Batch {{ $focusMainRouter['batch_number'] }}
                                    @endif
                                </div>
                            </article>
                            <article class="deploy-focus-card">
                                <div class="deploy-focus-label">ONT Status</div>
                                <div class="deploy-focus-value">{{ $focus['status'] ?: '—' }}</div>
                                <div class="deploy-focus-sub">ACS {{ $focus['acs_last_inform'] ?? '—' }}</div>
                            </article>
                            <article class="deploy-focus-card">
                                <div class="deploy-focus-label">Signal</div>
                                <div class="deploy-focus-value">RX {{ $focus['rx_power'] ?? '—' }}</div>
                                <div class="deploy-focus-sub">TX {{ $focus['tx_power'] ?? '—' }}</div>
                            </article>
                            <article class="deploy-focus-card">
                                <div class="deploy-focus-label">PON</div>
                                <div class="deploy-focus-value">OLT {{ $focus['olt'] ?? '—' }}</div>
                                <div class="deploy-focus-sub">Slot {{ $focus['slot'] ?? '—' }} • PON {{ $focus['pon'] ?? '—' }}</div>
                            </article>
                        </div>

                        <div class="inv-card inv-table-card">
                            <div class="inv-table-head">
                                <div>
                                    <p class="inv-table-title mb-0">Routers at This Site</p>
                                    <div class="inv-table-sub">Main router first, then the remaining deployed routers in recent install order.</div>
                                </div>
                            </div>
                            <div class="inv-table-body">
                                <div class="deploy-table-wrap">
                                    <table class="table table-sm align-middle table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:180px;">Serial</th>
                                                <th>Router</th>
                                                <th style="width:130px;">Role</th>
                                                <th style="width:150px;">Status</th>
                                                <th style="width:180px;">Installed</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse(($focus['routers'] ?? []) as $row)
                                                @php
                                                    $row = (array) $row;
                                                    $isMain = $focusMainSerial !== '' && (string) ($row['serial_number'] ?? '') === $focusMainSerial;
                                                @endphp
                                                <tr>
                                                    <td>{{ $row['serial_number'] ?? '—' }}</td>
                                                    <td>
                                                        <div class="fw-bold text-dark">{{ trim(($row['brand'] ?? '—') . ' ' . ($row['model_number'] ?? '')) }}</div>
                                                        <div class="deploy-row-sub">Batch {{ $row['batch_number'] ?? '—' }}</div>
                                                    </td>
                                                    <td>
                                                        @if($isMain)
                                                            <span class="deploy-badge dark">Main</span>
                                                        @else
                                                            <span class="deploy-badge">Chain</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $row['router_status'] ?? '—' }}</td>
                                                    <td>{{ $row['installed_date'] ?? '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5">
                                                        <div class="deploy-empty-row">No deployed router rows were returned for this site yet.</div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        @endif
    @else
        <div class="inv-card inv-table-card">
            <div class="inv-table-head">
                <div>
                    <p class="inv-table-title mb-0">Deployment History</p>
                    <div class="inv-table-sub">Audit local item deployments by date, technician, site, and reference.</div>
                </div>
            </div>

            @if($loading)
                <div class="p-4">
                    @for($r = 0; $r < 8; $r++)
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <div class="inv-skeleton" style="width:140px; height:14px; border-radius:10px;"></div>
                            <div class="inv-skeleton" style="width:160px; height:14px; border-radius:10px;"></div>
                            <div class="inv-skeleton" style="flex:1; height:14px; border-radius:10px;"></div>
                            <div class="inv-skeleton" style="width:80px; height:14px; border-radius:10px;"></div>
                            <div class="inv-skeleton" style="width:200px; height:14px; border-radius:10px;"></div>
                        </div>
                    @endfor
                </div>
            @else
                <div class="inv-table-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width:170px;">Date</th>
                                    <th style="width:200px;">Technician</th>
                                    <th>Item</th>
                                    <th style="width:110px;">Qty</th>
                                    <th style="width:240px;">Site</th>
                                    <th style="width:200px;">Ref</th>
                                    <th style="width:200px;">Logged By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deployments as $d)
                                    @php
                                        $dt = $d->created_at?->format('Y-m-d H:i') ?? '—';
                                        $tech = $d->technician?->name ?? '—';
                                        $item = $d->item?->name ?? '—';
                                        $qty = (int) ($d->qty ?? 0);
                                        $site = trim((string) ($d->site_name ?? ''));
                                        $siteShow = $site !== '' ? $site : '—';
                                        $ref = trim((string) ($d->reference ?? ''));
                                        $refShow = $ref !== '' ? $ref : '—';
                                        $by = $d->creator?->name ?? '—';
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $dt }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $tech }}</div>
                                            <div class="deploy-row-sub">Tech deployment</div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $item }}</div>
                                            <div class="deploy-row-sub">Ref <span class="inv-chip">{{ $refShow }}</span></div>
                                        </td>
                                        <td><span class="badge bg-dark">{{ $qty }}</span></td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $siteShow }}</div>
                                            @if($refShow !== '—')
                                                <div class="deploy-row-sub">Ref {{ $refShow }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $refShow }}</td>
                                        <td>{{ $by }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="p-0">
                                            <div class="inv-empty">
                                                <div class="inv-empty-ico"><i class="bi bi-geo-alt"></i></div>
                                                <p class="inv-empty-title mb-0">No deployments yet</p>
                                                <div class="inv-empty-sub">Deployments will appear here once technicians deploy assigned stock to sites.</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($deployments, 'links'))
                        <div class="pt-3">{{ $deployments->links() }}</div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
