@extends('inventory::layout')

@php
    $sections = $sections ?? [];
    $section = $section ?? 'batches';
    $sectionMeta = $sectionMeta ?? ['label' => 'Routers', 'subtitle' => ''];
    $routerData = $routerData ?? ['available' => false, 'message' => 'No data loaded.', 'pagination' => ['total' => 0, 'page_count' => 1]];
    $routerPaginator = $routerPaginator ?? null;
    $workspace = $workspace ?? ['type' => 'table'];
    $filters = $filters ?? [];
    $rows = $rows ?? collect();
    $isTechnicianScoped = $isTechnicianScoped ?? false;
    $fetchedAt = $routerData['fetched_at'] ?? null;
    $pagination = $routerData['pagination'] ?? ['total' => 0, 'page_count' => 1];
    $total = (int) ($pagination['total'] ?? 0);
    $pageCount = max(1, (int) ($pagination['page_count'] ?? 1));
    $recordLabel = match ($workspace['type'] ?? 'table') {
        'batches' => 'Batches',
        'technicians' => 'Technicians',
        'technician-routers' => 'Routers',
        'deployments' => 'Sites',
        default => $section === 'deployed' ? 'Routers' : 'Rows',
    };
    $recordSub = match ($workspace['type'] ?? 'table') {
        'batches', 'technicians' => 'Current grouped results.',
        'technician-routers' => 'Routers assigned to you.',
        default => $section === 'deployed' ? 'Current deployed router rows.' : 'Current API rows.',
    };
    $routerCount = (int) ($workspace['router_count'] ?? $rows->count());
    $access = \App\Modules\Inventory\Support\InventoryAccess::class;
    $authUser = auth('inventory')->user();
    $can = fn (string $permission) => $access::allows($authUser, $permission);
    $techSiteLookupUrl = $isTechnicianScoped ? route('inventory.tech.sites.lookup') : null;
    $tabQueryBase = array_filter([
        'search' => $filters['search'] ?? '',
        'page_size' => $filters['page_size'] ?? 20,
    ], fn ($value) => $value !== null && $value !== '');
    $focusDismissQuery = request()->query();
    unset($focusDismissQuery['focus_batch']);
    $buildAdminDeployUrl = function (?array $inventory, array $prefill = []) use ($can): ?string {
        if (!$can('deployments.admin_deploy')) {
            return null;
        }

        if (!$inventory || empty($inventory['assigned_to']) || empty($inventory['item_id'])) {
            return null;
        }

        $params = array_filter([
            'technician_id' => $inventory['assigned_to'],
            'item_id' => $inventory['item_id'],
            'unit_id' => $inventory['unit_id'] ?? null,
            'site_name' => $prefill['site_name'] ?? null,
            'site_code' => $prefill['site_code'] ?? null,
            'reference' => $prefill['reference'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return route('inventory.admin.deploy.create', $params);
    };
@endphp

@section('page-title', 'Routers')
@section('page-subtitle', 'Router workspace')

@section('page-actions')
@if($can('tech_inventory.view') && !$access::isAdmin($authUser))
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.tech.dashboard') }}" data-inv-loading>
        <i class="bi bi-briefcase me-1"></i> My Dashboard
    </a>
@endif
    @if($can('items.view'))
        <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.items.index') }}" data-inv-loading>
            <i class="bi bi-box-seam me-1"></i> Items
        </a>
    @endif
    @if($can('deployments.view'))
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.deployments.index') }}" data-inv-loading>
            <i class="bi bi-geo-alt me-1"></i> Deployments
        </a>
    @endif
    @if($can('deployments.admin_deploy'))
        <a class="btn btn-dark btn-sm" href="{{ route('inventory.admin.deploy.create') }}" data-inv-loading>
            <i class="bi bi-rocket-takeoff me-1"></i> Admin Deploy
        </a>
    @endif
@endsection

@section('page-toolbar')
    @if(!$isTechnicianScoped)
        <div class="router-inline-summary">
            <span class="router-inline-pill"><strong>{{ $sectionMeta['label'] }}</strong> {{ number_format($total) }}</span>
            <span class="router-inline-pill"><strong>Routers</strong> {{ number_format($routerCount) }}</span>
            @if(($workspace['type'] ?? '') === 'technician-routers')
                <span class="router-inline-pill"><strong>Batches</strong> {{ number_format((int) ($workspace['batch_count'] ?? 0)) }}</span>
                <span class="router-inline-pill"><strong>Latest Assigned</strong> {{ ($workspace['latest_assignment_at'] ?? null) ?: 'Not assigned yet' }}</span>
            @endif
            <span class="router-inline-pill"><strong>Fetched</strong> {{ $fetchedAt ? \Illuminate\Support\Carbon::parse($fetchedAt)->format('Y-m-d H:i:s') : 'Not fetched' }}</span>
        </div>

        @if(count($sections) > 1)
            <div class="router-tabs">
                @foreach($sections as $key => $meta)
                    <a
                        class="router-tab {{ $section === $key ? 'active' : '' }}"
                        href="{{ route('inventory.routers.index', array_merge($tabQueryBase, ['section' => $key, 'page' => 1])) }}"
                        data-inv-loading
                    >
                        {{ $meta['label'] }}
                    </a>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('inventory.routers.index') }}" data-inv-loading data-inv-autofilter>
            <input type="hidden" name="section" value="{{ $section }}">

            <div class="router-filter-grid">
                <div>
                    <label class="form-label">Search</label>
                    <input
                        class="form-control form-control-sm"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Serial, router, batch, site"
                    >
                </div>

                <div>
                    <label class="form-label">Page Size</label>
                    <select class="form-select form-select-sm" name="page_size">
                        @foreach([10, 20, 50, 100] as $pageSize)
                            <option value="{{ $pageSize }}" @selected((int) ($filters['page_size'] ?? 20) === $pageSize)>{{ $pageSize }}</option>
                        @endforeach
                    </select>
                </div>

                @if($section === 'in-stock')
                    <div>
                        <label class="form-label">Batch Number</label>
                        <input
                            class="form-control form-control-sm"
                            name="batch_number"
                            value="{{ $filters['batch_number'] ?? '' }}"
                            placeholder="BATCH-202603..."
                        >
                    </div>
                @elseif($section === 'with-techs')
                    <div>
                        <label class="form-label">Technician ID</label>
                        <input
                            class="form-control form-control-sm"
                            type="number"
                            min="1"
                            name="technician_id"
                            value="{{ $filters['technician_id'] ?? '' }}"
                            placeholder="24"
                        >
                    </div>
                @elseif($section === 'deployed')
                    <div>
                        <label class="form-label">Site ID</label>
                        <input
                            class="form-control form-control-sm"
                            type="number"
                            min="1"
                            name="site_id"
                            value="{{ $filters['site_id'] ?? '' }}"
                            placeholder="421378"
                        >
                    </div>
                @endif

                <div class="router-filter-actions">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.routers.index', ['section' => $section]) }}" data-inv-loading>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    @endif
@endsection

@section('inventory-content')

<style>
    .router-shell{ display:grid; gap:18px; }
    .router-summary{
        padding:18px;
        background:
            radial-gradient(circle at top right, rgba(29, 155, 108, .18), transparent 24%),
            radial-gradient(circle at bottom left, rgba(227, 194, 113, .16), transparent 26%),
            linear-gradient(145deg, #fffdf6 0%, #f4fbf0 58%, #eef8f1 100%);
    }
    .router-summary-grid{
        display:grid;
        grid-template-columns:minmax(0, 1.35fr) repeat(3, minmax(0, .9fr));
        gap:12px;
    }
    .router-kpi{
        min-height:100%;
        padding:16px;
        border-radius:22px;
        border:1px solid var(--line);
        background:rgba(255,255,255,.94);
        box-shadow:0 16px 30px rgba(22, 52, 37, .08);
    }
    .router-kpi-label{
        color:var(--muted);
        font-size:11px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.16em;
    }
    .router-kpi-value{
        margin-top:10px;
        font-family:"Space Grotesk", sans-serif;
        font-size:30px;
        line-height:1;
        letter-spacing:-.05em;
        color:var(--text);
    }
    .router-kpi-value.small{
        font-size:18px;
        line-height:1.25;
    }
    .router-kpi-sub{
        margin-top:8px;
        color:var(--muted);
        font-size:12px;
        line-height:1.45;
    }

    .router-filter-card{ padding:18px; }
    .router-inline-summary{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        align-items:center;
        margin-bottom:16px;
    }
    .router-inline-pill{
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
    .router-inline-pill strong{
        font-family:"Space Grotesk", sans-serif;
        font-size:14px;
        color:var(--text);
    }
    .router-tabs{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        margin-bottom:16px;
    }
    .router-filter-grid{
        display:grid;
        grid-template-columns:minmax(240px, 1.6fr) repeat(4, minmax(0, 1fr));
        gap:12px;
        align-items:end;
    }
    .router-filter-actions{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        align-items:center;
    }
    .router-badge{
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
    .router-badge.ok{
        background:#ebf7ef;
        border-color:rgba(77, 138, 102, .22);
        color:var(--brand-strong);
    }
    .router-badge.warn{
        background:#fff7ea;
        border-color:rgba(165, 109, 24, .22);
        color:#8c6318;
    }
    .router-badge.dark{
        background:linear-gradient(180deg, #335a49 0%, #1e382d 100%);
        border-color:#1e382d;
        color:#ffffff;
    }
    .router-focus-link{
        display:inline-flex;
        align-items:center;
        gap:8px;
        justify-content:space-between;
        min-height:34px;
        min-width:0;
        max-width:100%;
        padding:6px 10px;
        border-radius:14px;
        border:1px solid rgba(29, 155, 108, .24);
        background:linear-gradient(180deg, rgba(227, 248, 238, .96) 0%, rgba(205, 237, 222, .92) 100%);
        color:var(--brand-strong);
        font-weight:800;
        letter-spacing:.01em;
        box-shadow:0 10px 20px rgba(29, 155, 108, .10);
        transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease, background .16s ease;
        white-space:nowrap;
    }
    .router-focus-code{
        min-width:0;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .router-focus-text{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:24px;
        padding:0 8px;
        border-radius:999px;
        background:rgba(255,255,255,.72);
        color:var(--brand-strong);
        font-size:10px;
        font-weight:800;
        letter-spacing:.14em;
        text-transform:uppercase;
        white-space:nowrap;
    }
    .router-focus-link:hover,
    .router-focus-link:focus-visible{
        color:var(--brand-strong);
        border-color:rgba(18, 116, 81, .34);
        background:linear-gradient(180deg, rgba(236, 252, 244, .98) 0%, rgba(220, 244, 231, .95) 100%);
        box-shadow:0 14px 26px rgba(29, 155, 108, .14);
        transform:translateY(-1px);
        text-decoration:none;
    }
    .router-focus-link:focus-visible{
        outline:2px solid rgba(29, 155, 108, .18);
        outline-offset:2px;
    }
    .router-section{
        overflow:hidden;
    }
    .router-section-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
        padding:16px 18px;
        border-bottom:1px solid var(--line);
        background:linear-gradient(180deg, #fff9ef 0%, #eef8f0 100%);
    }
    .router-section-head.router-section-head-compact{
        align-items:stretch;
    }
    .router-section-title{
        margin:0;
        font-family:"Space Grotesk", sans-serif;
        font-size:20px;
        letter-spacing:-.04em;
    }
    .router-section-sub{
        margin-top:4px;
        color:var(--muted);
        font-size:13px;
        line-height:1.45;
    }
    .router-section-body{
        padding:18px;
        display:grid;
        gap:16px;
    }
    .router-section-tools{
        display:grid;
        gap:12px;
        min-width:min(100%, 620px);
    }
    .router-table-summary{
        display:flex;
        flex-wrap:wrap;
        justify-content:flex-end;
        gap:8px;
    }
    .router-table-filter{
        width:100%;
    }
    .router-table-filter-grid{
        display:grid;
        grid-template-columns:minmax(220px, 1.8fr) minmax(120px, .7fr) auto;
        gap:12px;
        align-items:end;
    }
    .router-table-filter-actions{
        display:flex;
        justify-content:flex-end;
        align-items:center;
    }
    .router-tech-table{
        table-layout:auto;
    }
    .router-tech-table thead th{
        white-space:nowrap;
        font-size:10px;
        letter-spacing:.16em;
        padding:8px 12px !important;
    }
    .router-tech-table tbody td{
        white-space:nowrap;
        padding:8px 12px !important;
        vertical-align:middle;
    }
    .router-tech-table .router-cell-title{
        white-space:nowrap;
        line-height:1.2;
    }
    .router-tech-table .router-badge{
        padding:5px 10px;
        white-space:nowrap;
    }
    .router-action-summary{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:36px;
        padding:0 12px;
        border-radius:999px;
        border:1px solid var(--line);
        background:#ffffff;
        color:var(--text);
        font-size:12px;
        font-weight:700;
        box-shadow:0 8px 16px rgba(22, 52, 37, .06);
    }
    .router-action-menu{
        min-width:180px;
    }
    .router-modal-shell{
        position:fixed;
        inset:0;
        z-index:1400;
        display:none;
        align-items:center;
        justify-content:center;
        padding:20px;
    }
    .router-modal-shell.show{
        display:flex;
    }
    .router-modal-backdrop{
        position:absolute;
        inset:0;
        background:rgba(15, 25, 20, .34);
        backdrop-filter:blur(6px);
    }
    .router-deploy-modal{
        position:relative;
        width:min(620px, calc(100vw - 32px));
        border-radius:22px;
        border:1px solid rgba(22, 52, 37, .10);
        background:#ffffff;
        box-shadow:0 28px 56px rgba(22, 52, 37, .20);
        overflow:hidden;
    }
    .router-deploy-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:18px 20px;
        border-bottom:1px solid var(--line);
    }
    .router-deploy-title{
        margin:0;
        font-family:"Space Grotesk", sans-serif;
        font-size:28px;
        line-height:1;
        letter-spacing:-.05em;
        color:var(--text);
    }
    .router-deploy-close{
        width:38px;
        height:38px;
        border-radius:12px;
        border:1px solid var(--line);
        background:#ffffff;
        color:var(--text-soft);
        display:inline-flex;
        align-items:center;
        justify-content:center;
        font-size:18px;
        cursor:pointer;
    }
    .router-deploy-body{
        display:grid;
        gap:16px;
        padding:20px;
    }
    .router-deploy-note{
        color:var(--text-soft);
        font-size:14px;
        line-height:1.55;
    }
    .router-deploy-field{
        display:grid;
        gap:8px;
    }
    .router-deploy-label{
        font-size:12px;
        font-weight:800;
        letter-spacing:.06em;
        color:var(--text);
    }
    .router-deploy-search{
        position:relative;
    }
    .router-deploy-search input{
        padding-right:42px;
    }
    .router-deploy-search-ico{
        position:absolute;
        top:50%;
        right:14px;
        transform:translateY(-50%);
        color:var(--muted);
        pointer-events:none;
    }
    .router-deploy-results{
        display:none;
        margin-top:10px;
        border:1px solid var(--line);
        border-radius:16px;
        background:#ffffff;
        box-shadow:0 18px 34px rgba(22, 52, 37, .12);
        overflow:hidden;
    }
    .router-deploy-results.show{
        display:block;
    }
    .router-deploy-option{
        width:100%;
        border:0;
        border-bottom:1px solid rgba(22, 52, 37, .07);
        background:#ffffff;
        padding:12px 14px;
        text-align:left;
        cursor:pointer;
    }
    .router-deploy-option:last-child{
        border-bottom:0;
    }
    .router-deploy-option:hover{
        background:linear-gradient(180deg, #fff9ef 0%, #eef8f0 100%);
    }
    .router-deploy-option strong{
        display:block;
        color:var(--text);
        font-size:14px;
    }
    .router-deploy-option span{
        display:block;
        margin-top:4px;
        color:var(--muted);
        font-size:12px;
    }
    .router-deploy-status{
        min-height:20px;
        color:var(--muted);
        font-size:12px;
        line-height:1.45;
    }
    .router-deploy-selected{
        display:none;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:10px;
        padding:14px;
        border-radius:16px;
        border:1px solid var(--line);
        background:linear-gradient(180deg, #fffdf7 0%, #f3fbf3 100%);
    }
    .router-deploy-selected.show{
        display:grid;
    }
    .router-deploy-box{
        display:grid;
        gap:6px;
    }
    .router-deploy-box-label{
        font-size:10px;
        font-weight:800;
        letter-spacing:.16em;
        text-transform:uppercase;
        color:var(--muted);
    }
    .router-deploy-box-value{
        color:var(--text);
        font-size:13px;
        font-weight:700;
        line-height:1.4;
    }
    .router-deploy-check{
        display:flex;
        align-items:center;
        gap:10px;
        color:var(--text);
        font-weight:700;
        font-size:14px;
    }
    .router-deploy-check input{
        width:18px;
        height:18px;
        accent-color:var(--brand);
    }
    .router-deploy-actions{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        flex-wrap:wrap;
    }
    .router-deploy-submit{
        min-width:148px;
    }
    .router-detail{
        padding:18px;
        border-radius:24px;
        border:1px solid var(--line);
        background:linear-gradient(180deg, #fffdf7 0%, #f3fbf3 100%);
        box-shadow:0 16px 30px rgba(22, 52, 37, .07);
    }
    .router-site-list-section{
        padding:18px;
        border-radius:24px;
        border:1px solid var(--line);
        background:linear-gradient(180deg, #fffdf7 0%, #f3fbf3 100%);
        box-shadow:0 16px 30px rgba(22, 52, 37, .07);
    }
    .router-site-list-head{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
        margin-bottom:14px;
    }
    .router-site-list{
        display:grid;
        gap:10px;
    }
    .router-site-summary{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:14px;
        flex-wrap:wrap;
        padding:16px 18px;
        border-radius:18px;
        border:1px solid var(--line);
        background:rgba(255,255,255,.92);
        box-shadow:0 10px 20px rgba(22, 52, 37, .05);
    }
    .router-site-summary-actions{
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }
    .router-site-list-item{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:14px;
        padding:14px 16px;
        border-radius:18px;
        border:1px solid var(--line);
        background:rgba(255,255,255,.92);
        box-shadow:0 10px 20px rgba(22, 52, 37, .05);
        transition:transform .16s ease, border-color .16s ease, box-shadow .16s ease, background .16s ease;
    }
    .router-site-list-item:hover,
    .router-site-list-item:focus-visible{
        transform:translateY(-1px);
        border-color:rgba(29, 155, 108, .24);
        background:linear-gradient(180deg, #fffdf8 0%, #f0fbf3 100%);
        box-shadow:0 14px 26px rgba(22, 52, 37, .08);
        text-decoration:none;
    }
    .router-site-list-main{
        min-width:0;
    }
    .router-site-list-title{
        font-weight:800;
        color:var(--text);
        line-height:1.3;
    }
    .router-site-list-sub{
        margin-top:4px;
        color:var(--muted);
        font-size:12px;
        line-height:1.4;
    }
    .router-site-list-meta{
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
        justify-content:flex-end;
        flex:0 0 auto;
    }
    .router-site-list-cta{
        display:inline-flex;
        align-items:center;
        gap:6px;
        color:var(--brand-strong);
        font-size:12px;
        font-weight:800;
        letter-spacing:.08em;
        text-transform:uppercase;
        white-space:nowrap;
    }
    .router-focus-panel{
        overflow:hidden;
        border:1px solid var(--line);
    }
    .router-focus-head{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:16px;
        flex-wrap:wrap;
        padding:18px 20px;
        border-bottom:1px solid var(--line);
        background:linear-gradient(180deg, #fff9ef 0%, #eef8f0 100%);
    }
    .router-focus-title{
        margin:10px 0 0;
        font-family:"Space Grotesk", sans-serif;
        font-size:30px;
        line-height:1;
        letter-spacing:-.05em;
        color:var(--text);
    }
    .router-focus-sub{
        margin-top:8px;
        color:var(--muted);
        font-size:13px;
        line-height:1.45;
    }
    .router-focus-actions{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        align-items:center;
    }
    .router-focus-body{
        padding:18px;
        display:grid;
        gap:18px;
    }
    .router-sites-details,
    .router-site-details{
        border:0;
    }
    .router-sites-details summary,
    .router-site-details summary{
        list-style:none;
        cursor:pointer;
    }
    .router-sites-details summary::-webkit-details-marker,
    .router-site-details summary::-webkit-details-marker{
        display:none;
    }
    .router-site-list-item-summary{
        margin-top:12px;
    }
    .router-site-serials{
        margin-top:10px;
        padding:14px 16px;
        border-radius:16px;
        border:1px dashed var(--line);
        background:rgba(255,255,255,.75);
    }
    .router-batch-table{
        table-layout:fixed;
        width:100%;
    }
    .router-batch-table th,
    .router-batch-table td{
        vertical-align:middle;
    }
    .router-batch-table .router-cell-title{
        display:block;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .router-detail-grid{
        display:grid;
        grid-template-columns:repeat(4, minmax(0, 1fr));
        gap:12px;
        margin-top:14px;
    }
    .router-stat{
        padding:14px;
        border-radius:18px;
        border:1px solid var(--line);
        background:#ffffff;
    }
    .router-stat-label{
        font-size:11px;
        font-weight:700;
        letter-spacing:.16em;
        text-transform:uppercase;
        color:var(--muted);
    }
    .router-stat-value{
        margin-top:8px;
        font-family:"Space Grotesk", sans-serif;
        font-size:24px;
        line-height:1;
        letter-spacing:-.04em;
    }
    .router-grid{
        display:grid;
        gap:14px;
    }
    .router-tech-card,
    .router-site-card{
        border:1px solid var(--line);
        border-radius:24px;
        background:linear-gradient(180deg, #fffdf7 0%, #f4faf1 100%);
        box-shadow:0 16px 30px rgba(22, 52, 37, .07);
        overflow:hidden;
    }
    .router-site-card summary{
        list-style:none;
        cursor:pointer;
    }
    .router-site-card summary::-webkit-details-marker{
        display:none;
    }
    .router-tech-head,
    .router-site-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
        padding:16px 18px;
        border-bottom:1px solid var(--line);
        background:linear-gradient(180deg, #fff9ef 0%, #eef8f0 100%);
    }
    .router-tech-title,
    .router-site-title{
        margin:0;
        font-family:"Space Grotesk", sans-serif;
        font-size:22px;
        letter-spacing:-.04em;
    }
    .router-tech-sub,
    .router-site-sub{
        margin-top:4px;
        color:var(--muted);
        font-size:13px;
        line-height:1.45;
    }
    .router-site-metrics{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        align-items:center;
    }
    .router-site-body,
    .router-tech-body{
        padding:18px;
        display:grid;
        gap:16px;
    }
    .router-main-card{
        padding:16px;
        border-radius:22px;
        border:1px solid rgba(29, 155, 108, .18);
        background:linear-gradient(180deg, #fcfff8 0%, #eef9f1 100%);
    }
    .router-main-grid{
        display:grid;
        grid-template-columns:minmax(0, 1.3fr) repeat(4, minmax(0, .85fr));
        gap:12px;
        align-items:start;
    }
    .router-main-label{
        font-size:11px;
        font-weight:700;
        letter-spacing:.16em;
        text-transform:uppercase;
        color:var(--muted);
    }
    .router-main-value{
        margin-top:6px;
        font-size:14px;
        line-height:1.45;
        color:var(--text-soft);
    }
    .router-inline-meta{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        align-items:center;
        margin-top:10px;
    }
    .router-cell-title{
        font-weight:700;
        color:var(--text);
    }
    .router-cell-sub{
        margin-top:4px;
        font-size:12px;
        line-height:1.4;
        color:var(--muted);
    }
    .router-actions{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        align-items:center;
    }
    .router-table-wrap{ width:100%; overflow:auto; }
    .router-table-wrap-fit{ overflow:visible; }
    .router-deployed-table{
        min-width:0 !important;
        table-layout:fixed;
    }
    .router-deployed-table th,
    .router-deployed-table td{
        white-space:normal;
        overflow-wrap:anywhere;
    }
    .router-empty{
        padding:18px;
        border-radius:22px;
        border:1px dashed var(--line-strong);
        background:rgba(255,255,255,.7);
        color:var(--muted);
        text-align:center;
    }
    .router-focus-link{
        font-weight:700;
    }
    .router-modal-layer{
        position:fixed;
        inset:0;
        z-index:70;
        display:none;
        place-items:center;
        padding:24px;
    }
    .router-modal-layer.show{
        display:grid;
    }
    .router-modal-backdrop{
        position:absolute;
        inset:0;
        background:rgba(15, 30, 22, .42);
        backdrop-filter:blur(8px);
    }
    .router-modal-card{
        position:relative;
        width:min(1160px, calc(100vw - 48px));
        max-height:calc(100vh - 48px);
        overflow-x:hidden;
        overflow-y:auto;
        border-radius:30px;
        border:1px solid rgba(255,255,255,.36);
        background:
            radial-gradient(circle at top right, rgba(29, 155, 108, .18), transparent 24%),
            radial-gradient(circle at bottom left, rgba(227, 194, 113, .16), transparent 26%),
            linear-gradient(180deg, rgba(255,253,247,.98) 0%, rgba(244,251,240,.98) 100%);
        box-shadow:0 34px 70px rgba(14, 32, 24, .26);
    }
    .router-site-modal{
        width:min(760px, calc(100vw - 48px));
    }
    .router-modal-head{
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
    .router-modal-title{
        margin:0;
        font-family:"Space Grotesk", sans-serif;
        font-size:34px;
        line-height:.95;
        letter-spacing:-.06em;
    }
    .router-modal-sub{
        margin-top:8px;
        color:var(--muted);
        font-size:14px;
        line-height:1.45;
    }
    .router-modal-close{
        flex:0 0 auto;
    }
    .router-modal-body{
        padding:22px 24px 24px;
        display:grid;
        gap:18px;
    }
    .router-serial-modal{
        width:min(520px, calc(100vw - 48px));
    }
    .router-serial-modal .router-modal-head{
        display:grid;
        grid-template-columns:minmax(0, 1fr) auto;
        align-items:start;
    }
    .router-serial-title{
        margin:10px 0 0;
        font-family:"Space Grotesk", sans-serif;
        font-size:26px;
        line-height:1;
        letter-spacing:-.04em;
        color:var(--text);
    }
    .router-serial-site{
        margin-top:10px;
        color:var(--text-soft);
        font-size:14px;
        font-weight:700;
        line-height:1.45;
        overflow-wrap:anywhere;
        word-break:break-word;
    }
    .router-serial-meta{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        margin-top:12px;
    }
    .router-serial-list{
        display:grid;
        gap:12px;
    }
    .router-serial-item{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:10px 14px;
        border-radius:16px;
        border:1px solid var(--line);
        background:rgba(255,255,255,.94);
        overflow:hidden;
        box-shadow:0 8px 16px rgba(22, 52, 37, .04);
    }
    .router-serial-item-label{
        color:var(--text-soft);
        font-size:12px;
        font-weight:700;
        letter-spacing:.02em;
        text-transform:none;
        white-space:nowrap;
    }
    .router-serial-value{
        font-family:"IBM Plex Mono", monospace;
        font-size:13px;
        font-weight:700;
        color:var(--text);
        line-height:1.4;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .router-tech-mobile{
        display:none;
    }
    .router-mobile-group{
        margin-bottom:16px;
    }
    .router-mobile-date{
        font-size:11px;
        font-weight:700;
        letter-spacing:.16em;
        text-transform:uppercase;
        color:var(--muted);
        margin-bottom:8px;
    }
    .router-mobile-list{
        display:grid;
        gap:10px;
    }
    .router-mobile-item{
        border:1px solid var(--line);
        border-radius:20px;
        background:rgba(255,255,255,.96);
        box-shadow:0 10px 18px rgba(22, 52, 37, .06);
        overflow:hidden;
    }
    .router-mobile-summary{
        display:flex;
        align-items:center;
        gap:10px;
        padding:12px 14px;
        cursor:pointer;
        list-style:none;
    }
    .router-mobile-summary::marker{
        content:'';
    }
    .router-mobile-summary::-webkit-details-marker{
        display:none;
    }
    .router-mobile-toggle{
        width:24px;
        height:24px;
        border-radius:8px;
        border:1px solid var(--line);
        display:grid;
        place-items:center;
        color:var(--muted);
        flex:0 0 auto;
    }
    .router-mobile-toggle::before{
        content:'›';
        font-size:16px;
        line-height:1;
        transition:transform .2s ease;
    }
    details[open] .router-mobile-toggle::before{
        transform:rotate(90deg);
    }
    .router-mobile-serial{
        font-family:"IBM Plex Mono", monospace;
        font-size:13px;
        font-weight:700;
        color:var(--text);
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .router-mobile-body{
        border-top:1px solid var(--line);
        padding:12px 14px 14px;
        display:grid;
        gap:8px;
        background:rgba(252, 255, 250, .7);
    }
    .router-mobile-row{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        font-size:12px;
    }
    .router-mobile-row span{
        color:var(--muted);
        font-size:10px;
        font-weight:700;
        letter-spacing:.12em;
        text-transform:uppercase;
    }
    .router-mobile-row strong{
        color:var(--text);
        font-weight:600;
        text-align:right;
    }
    .router-mobile-actions{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        margin-top:6px;
    }
    .router-tech-head-compact{
        display:none;
        align-items:flex-start;
        gap:12px;
        margin-bottom:12px;
    }
    .router-burger{
        width:42px;
        height:42px;
        border-radius:14px;
        border:1px solid var(--line);
        background:#ffffff;
        color:var(--text);
        display:grid;
        place-items:center;
        flex:0 0 auto;
        box-shadow:0 10px 18px rgba(22, 52, 37, .06);
    }
    .router-tech-head-main{
        flex:1;
        min-width:0;
        display:grid;
        gap:8px;
    }
    .router-tech-title{
        margin:0;
        font-family:"Space Grotesk", sans-serif;
        font-size:20px;
        letter-spacing:-.03em;
        color:var(--text);
    }
    .router-tech-search{
        margin:0;
    }
    .router-tech-search input{
        border-radius:16px;
        min-height:42px;
    }

    @media (max-width: 1199.98px){
        .router-summary-grid,
        .router-detail-grid,
        .router-main-grid{
            grid-template-columns:repeat(2, minmax(0, 1fr));
        }
        .router-filter-grid{
            grid-template-columns:repeat(2, minmax(0, 1fr));
        }
        .router-table-filter-grid{
            grid-template-columns:repeat(2, minmax(0, 1fr));
        }
        .router-modal-card{
            width:min(100%, calc(100vw - 28px));
        }
    }
    @media (max-width: 991.98px){
        body.inv-routers-compact .inv-page-header{
            display:none;
        }
        .router-tech-head-compact{
            display:flex;
        }
        .router-tech-desktop-head{
            display:none;
        }
        .router-tech-desktop{
            display:none;
        }
        .router-tech-mobile{
            display:block;
        }
        .router-deployed-table thead{
            display:none;
        }
        .router-deployed-table,
        .router-deployed-table tbody,
        .router-deployed-table tr,
        .router-deployed-table td{
            display:block;
            width:100%;
        }
        .router-deployed-table tr{
            border:1px solid var(--line);
            border-radius:22px;
            margin-bottom:12px;
            background:rgba(255,255,255,.94);
            box-shadow:0 12px 22px rgba(22, 52, 37, .06);
            overflow:hidden;
        }
        .router-deployed-table td{
            display:grid;
            grid-template-columns:96px minmax(0, 1fr);
            gap:12px;
            padding:12px 14px !important;
            border-bottom:1px solid var(--line) !important;
        }
        .router-deployed-table td:last-child{
            border-bottom:0 !important;
        }
        .router-deployed-table td::before{
            content:attr(data-label);
            color:var(--muted);
            font-size:11px;
            font-weight:700;
            letter-spacing:.12em;
            text-transform:uppercase;
            padding-top:2px;
        }
    }
    @media (max-width: 767.98px){
        .router-main-grid{
            grid-template-columns:1fr;
        }
        .router-site-summary{
            align-items:flex-start;
        }
        .router-site-summary-actions{
            width:100%;
            justify-content:flex-start;
        }
        .router-site-list-item{
            align-items:flex-start;
            flex-direction:column;
        }
        .router-site-list-meta{
            width:100%;
            justify-content:flex-start;
        }
        .router-section-tools,
        .router-table-filter-grid{
            min-width:0;
            grid-template-columns:1fr;
        }
        .router-table-summary{
            justify-content:flex-start;
        }
        .router-table-filter-actions{
            justify-content:stretch;
        }
        .router-table-filter-actions .btn{
            width:100%;
        }
        .router-modal-layer{
            padding:14px;
        }
        .router-modal-card{
            width:100%;
            max-height:calc(100vh - 28px);
        }
        .router-site-modal,
        .router-serial-modal{
            width:100%;
        }
        .router-serial-modal .router-modal-head{
            grid-template-columns:1fr;
        }
        .router-modal-head,
        .router-modal-body{
            padding-left:18px;
            padding-right:18px;
        }
        .router-modal-title{
            font-size:28px;
        }
        .router-deploy-modal{
            width:100%;
        }
        .router-deploy-selected{
            grid-template-columns:1fr;
        }
    }
    @media (max-width: 575.98px){
        .router-summary-grid,
        .router-detail-grid,
        .router-filter-grid{
            grid-template-columns:1fr;
        }
        .router-filter-actions{
            width:100%;
        }
        .router-filter-actions .btn{
            flex:1 1 auto;
        }
    }
</style>

<div class="router-shell">
    @if(!($routerData['available'] ?? false))
        <div class="inv-alert warning">
            <div class="inv-alert-ico"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <strong>Skybrix query unavailable</strong>
                <div>{{ $routerData['message'] ?? 'The upstream request failed.' }}</div>
            </div>
        </div>
    @endif

    @if(($workspace['type'] ?? 'table') === 'batches')
        <section class="inv-card router-section">
            <div class="router-section-head">
                <div>
                    <h2 class="router-section-title">Stock Batches</h2>
                    <div class="router-section-sub">Newest live router activity stays on top. Open a batch without losing your table position.</div>
                </div>
            </div>
            <div class="router-section-body">
                <div class="router-table-wrap">
                    <table class="table table-sm align-middle table-hover mb-0 router-batch-table">
                        <thead>
                            <tr>
                                <th style="width:240px;">Batch Number</th>
                                <th style="width:240px;">Router</th>
                                <th style="width:120px;">Live Routers</th>
                                <th style="width:110px;">Available</th>
                                <th style="width:110px;">With Techs</th>
                                <th style="width:110px;">Deployed</th>
                                <th style="width:110px;">Faulty</th>
                                <th style="width:170px;">Latest</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($routerPaginator as $group)
                                @php $group = (array) $group; @endphp
                                <tr>
                                    <td>
                                        <a
                                            class="router-focus-link"
                                            href="{{ route('inventory.routers.index', array_merge(request()->query(), ['focus_batch' => $group['batch_number'], 'page' => request('page', 1)])) }}"
                                            data-inv-loading
                                        >
                                            <span class="router-focus-code">{{ $group['batch_number'] }}</span>
                                            <span class="router-focus-text">View</span>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="router-cell-title">{{ $group['brand'] ?: 'Router' }} {{ $group['model_number'] ?: '' }}</div>
                                    </td>
                                    <td>{{ $group['quantity'] }}</td>
                                    <td>{{ $group['available_count'] }}</td>
                                    <td>{{ $group['with_techs_count'] }}</td>
                                    <td>{{ $group['deployed_count'] }}</td>
                                    <td>{{ $group['faulty_count'] }}</td>
                                    <td>{{ $group['latest_activity_at'] ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-0">
                                        <div class="inv-empty">
                                            <div class="inv-empty-ico"><i class="bi bi-box-seam"></i></div>
                                            <p class="inv-empty-title mb-0">No batches found</p>
                                            <div class="inv-empty-sub">Adjust the search and try again.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($routerPaginator && $routerPaginator->hasPages())
                    <div>{{ $routerPaginator->links() }}</div>
                @endif
            </div>
        </section>
    @elseif(($workspace['type'] ?? 'table') === 'technician-routers')
        <section class="inv-card router-section">
            <div class="router-tech-head-compact">
                <button class="router-burger" type="button" onclick="invToggleSidebar()" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <div class="router-tech-head-main">
                    <h2 class="router-tech-title">My Assigned Routers</h2>
                    <form method="GET" action="{{ route('inventory.routers.index') }}" class="router-tech-search" data-inv-loading data-inv-autofilter>
                        <input type="hidden" name="section" value="{{ $section }}">
                        <input type="hidden" name="page_size" value="{{ $filters['page_size'] ?? 20 }}">
                        <input
                            class="form-control form-control-sm"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Search serial"
                        >
                    </form>
                </div>
            </div>
            <div class="router-section-head router-section-head-compact router-tech-desktop-head">
                <div>
                    <h2 class="router-section-title">My Assigned Routers</h2>
                    <div class="router-section-sub">Only routers assigned to your account are visible here.</div>
                </div>
                <div class="router-section-tools">
                    <form method="GET" action="{{ route('inventory.routers.index') }}" class="router-table-filter" data-inv-loading data-inv-autofilter>
                        <input type="hidden" name="section" value="{{ $section }}">
                        <div class="router-table-filter-grid">
                            <div>
                                <label class="form-label">Search</label>
                                <input
                                    class="form-control form-control-sm"
                                    name="search"
                                    value="{{ $filters['search'] ?? '' }}"
                                    placeholder="Serial, router, batch"
                                >
                            </div>
                            <div>
                                <label class="form-label">Page Size</label>
                                <select class="form-select form-select-sm" name="page_size">
                                    @foreach([10, 20, 50, 100] as $pageSize)
                                        <option value="{{ $pageSize }}" @selected((int) ($filters['page_size'] ?? 20) === $pageSize)>{{ $pageSize }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="router-table-filter-actions">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.routers.index') }}" data-inv-loading>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="router-section-body">
                <div class="router-table-wrap router-table-wrap-fit router-tech-desktop">
                    <table class="table table-sm align-middle table-hover mb-0 router-deployed-table router-tech-table">
                        <thead>
                            <tr>
                                <th style="width:210px;">Serial</th>
                                <th style="width:185px;">Batch</th>
                                <th style="width:220px;">Router</th>
                                <th style="width:180px;">Assigned</th>
                                <th style="width:165px;">Expected Deploy</th>
                                <th style="width:120px;">Urgency</th>
                                <th style="width:110px; text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($routerPaginator as $row)
                                @php $row = (array) $row; @endphp
                                <tr>
                                    <td data-label="Serial">{{ $row['_serial'] ?? '—' }}</td>
                                    <td data-label="Batch">{{ $row['batch_number'] ?? '—' }}</td>
                                    <td data-label="Router">
                                        <div class="router-cell-title">{{ trim(($row['brand'] ?? 'Router') . ' ' . ($row['model_number'] ?? '')) }}</div>
                                    </td>
                                    <td data-label="Assigned">{{ $row['assigned_date'] ?? '—' }}</td>
                                    <td data-label="Expected">{{ $row['expected_deployment_date'] ?? '—' }}</td>
                                    <td data-label="Urgency">
                                        @if(!empty($row['urgency']))
                                            <span class="router-badge {{ mb_strtolower((string) $row['urgency']) === 'overdue' ? 'warn' : 'ok' }}">
                                                {{ ucfirst((string) $row['urgency']) }}
                                            </span>
                                        @else
                                            <span class="router-badge">Assigned</span>
                                        @endif
                                    </td>
                                    <td data-label="Actions" style="text-align:right;">
                                        <details class="action-menu">
                                            <summary class="router-action-summary">Actions</summary>
                                            <div class="action-menu-list router-action-menu">
                                                @if($can('deployments.manage'))
                                                    <button
                                                        class="action-menu-item is-highlight"
                                                        type="button"
                                                        data-router-deploy-open
                                                        data-router-serial="{{ $row['_serial'] ?? '' }}"
                                                        data-router-batch="{{ $row['batch_number'] ?? '' }}"
                                                        data-router-label="{{ trim(($row['brand'] ?? 'Router') . ' ' . ($row['model_number'] ?? '')) }}"
                                                    >
                                                        Deploy to Site
                                                    </button>
                                                @endif
                                                @if($can('deployments.view'))
                                                    <a class="action-menu-item" href="{{ route('inventory.deployments.index', ['section' => 'sites']) }}" data-inv-loading>
                                                        Open Deployments
                                                    </a>
                                                @endif
                                                @if(!$can('deployments.manage') && !$can('deployments.view'))
                                                    <span class="action-menu-item is-disabled">No actions available</span>
                                                @endif
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-0">
                                        <div class="inv-empty">
                                            <div class="inv-empty-ico">
                                                <i class="bi bi-hdd-network" style="font-size:22px;"></i>
                                            </div>
                                            <p class="inv-empty-title mb-0">No routers assigned</p>
                                            <div class="inv-empty-sub">Once routers are assigned to you, they will appear here with the batch and assignment date.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="router-tech-mobile">
                    @php
                        $mobileRows = collect(
                            (is_object($routerPaginator) && method_exists($routerPaginator, 'items'))
                                ? $routerPaginator->items()
                                : (is_iterable($routerPaginator) ? $routerPaginator : [])
                        )->map(fn ($row) => (array) $row);
                        $mobileGroups = $mobileRows->groupBy(function (array $row) {
                            $assigned = trim((string) ($row['assigned_date'] ?? ''));
                            if ($assigned === '') {
                                return 'No assigned date';
                            }
                            return substr($assigned, 0, 10);
                        });
                    @endphp

                    @forelse($mobileGroups as $dateLabel => $groupRows)
                        <div class="router-mobile-group">
                            <div class="router-mobile-date">{{ $dateLabel }}</div>
                            <div class="router-mobile-list">
                                @foreach($groupRows as $row)
                                    <details class="router-mobile-item">
                                        <summary class="router-mobile-summary">
                                            <span class="router-mobile-toggle"></span>
                                            <span class="router-mobile-serial">{{ $row['_serial'] ?? '—' }}</span>
                                        </summary>
                                        <div class="router-mobile-body">
                                            <div class="router-mobile-row">
                                                <span>Router</span>
                                                <strong>{{ trim(($row['brand'] ?? 'Router') . ' ' . ($row['model_number'] ?? '')) }}</strong>
                                            </div>
                                            <div class="router-mobile-row">
                                                <span>Batch</span>
                                                <strong>{{ $row['batch_number'] ?? '—' }}</strong>
                                            </div>
                                            <div class="router-mobile-row">
                                                <span>Assigned</span>
                                                <strong>{{ $row['assigned_date'] ?? '—' }}</strong>
                                            </div>
                                            <div class="router-mobile-row">
                                                <span>Expected</span>
                                                <strong>{{ $row['expected_deployment_date'] ?? '—' }}</strong>
                                            </div>
                                            <div class="router-mobile-row">
                                                <span>Urgency</span>
                                                <div>
                                                    @if(!empty($row['urgency']))
                                                        <span class="router-badge {{ mb_strtolower((string) $row['urgency']) === 'overdue' ? 'warn' : 'ok' }}">
                                                            {{ ucfirst((string) $row['urgency']) }}
                                                        </span>
                                                    @else
                                                        <span class="router-badge">Assigned</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="router-mobile-actions">
                                                @if($can('deployments.manage'))
                                                    <button
                                                        class="btn btn-sm btn-outline-dark"
                                                        type="button"
                                                        data-router-deploy-open
                                                        data-router-serial="{{ $row['_serial'] ?? '' }}"
                                                        data-router-batch="{{ $row['batch_number'] ?? '' }}"
                                                        data-router-label="{{ trim(($row['brand'] ?? 'Router') . ' ' . ($row['model_number'] ?? '')) }}"
                                                    >
                                                        Deploy to Site
                                                    </button>
                                                @endif
                                                @if($can('deployments.view'))
                                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.deployments.index', ['section' => 'sites']) }}" data-inv-loading>
                                                        Open Deployments
                                                    </a>
                                                @endif
                                                @if(!$can('deployments.manage') && !$can('deployments.view'))
                                                    <span class="router-badge">No actions</span>
                                                @endif
                                            </div>
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="inv-empty">
                            <div class="inv-empty-ico">
                                <i class="bi bi-hdd-network" style="font-size:22px;"></i>
                            </div>
                            <p class="inv-empty-title mb-0">No routers assigned</p>
                            <div class="inv-empty-sub">Once routers are assigned to you, they will appear here with the assignment date.</div>
                        </div>
                    @endforelse
                </div>
                @if($routerPaginator && $routerPaginator->hasPages())
                    <div>{{ $routerPaginator->links() }}</div>
                @endif
            </div>
        </section>
        @if($can('deployments.manage'))
            <div class="router-modal-shell" id="routerDeployModal" aria-hidden="true">
                <div class="router-modal-backdrop" data-router-deploy-close></div>
                <section class="router-deploy-modal" role="dialog" aria-modal="true" aria-labelledby="routerDeployTitle">
                    <div class="router-deploy-head">
                        <h3 class="router-deploy-title" id="routerDeployTitle">Deploy Router to Site</h3>
                        <button class="router-deploy-close" type="button" data-router-deploy-close aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="router-deploy-body" data-router-site-lookup data-site-url="{{ $techSiteLookupUrl }}">
                        <div class="router-deploy-note">
                            You are deploying the router with Serial Number:
                            <strong id="routerDeploySerialText">—</strong>
                        </div>

                        <div class="router-deploy-field">
                            <label class="router-deploy-label" for="routerDeploySiteLookup">Deployment Site</label>
                            <div class="router-deploy-search">
                                <input
                                    class="form-control form-control-sm"
                                    type="text"
                                    id="routerDeploySiteLookup"
                                    autocomplete="off"
                                    placeholder="Select site to deploy"
                                >
                                <span class="router-deploy-search-ico">
                                    <i class="bi bi-search"></i>
                                </span>
                            </div>
                            <div class="router-deploy-status" id="routerDeployStatus">Search by site name or ONT serial to select a registered site.</div>
                            <div class="router-deploy-results" id="routerDeployResults"></div>
                        </div>

                        <div class="router-deploy-selected" id="routerDeploySelectedCard">
                            <div class="router-deploy-box">
                                <div class="router-deploy-box-label">Site</div>
                                <div class="router-deploy-box-value" id="routerDeploySelectedName">No site selected</div>
                            </div>
                            <div class="router-deploy-box">
                                <div class="router-deploy-box-label">Site ID</div>
                                <div class="router-deploy-box-value" id="routerDeploySelectedId">Not selected</div>
                            </div>
                            <div class="router-deploy-box">
                                <div class="router-deploy-box-label">ONT Serial</div>
                                <div class="router-deploy-box-value" id="routerDeploySelectedSerial">Not selected</div>
                            </div>
                            <div class="router-deploy-box">
                                <div class="router-deploy-box-label">Status / Signal</div>
                                <div class="router-deploy-box-value" id="routerDeploySelectedSignal">Waiting for lookup details</div>
                            </div>
                        </div>

                        <label class="router-deploy-check">
                            <input type="checkbox" id="routerDeployMainToggle">
                            <span>Set as Main Router for this Site</span>
                        </label>

                        <div class="router-deploy-actions">
                            <button class="btn btn-outline-secondary btn-sm" type="button" data-router-deploy-close>Cancel</button>
                            <button class="btn btn-dark btn-sm router-deploy-submit" type="button" id="routerDeploySubmit" disabled>
                                Deploy Router
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        @endif
    @elseif(($workspace['type'] ?? 'table') === 'technicians')
        <section class="inv-card router-section">
            <div class="router-section-head">
                <div>
                    <h2 class="router-section-title">Undeployed Routers</h2>
                    <div class="router-section-sub">Routers grouped under technicians, ready for admin deployment.</div>
                </div>
            </div>
            <div class="router-section-body">
                <div class="router-grid">
                    @forelse($routerPaginator as $group)
                        @php $group = (array) $group; @endphp
                        <article class="router-tech-card">
                            <div class="router-tech-head">
                                <div>
                                    <h3 class="router-tech-title">{{ $group['technician_name'] ?: 'Unknown Technician' }}</h3>
                                    <div class="router-tech-sub">
                                        {{ $group['technician_phone'] ?: 'No phone' }}
                                        @if(!empty($group['technician_email']))
                                            • {{ $group['technician_email'] }}
                                        @endif
                                    </div>
                                </div>
                                <div class="router-actions">
                                    <span class="router-badge">Routers {{ $group['router_count'] }}</span>
                                    @if((int) ($group['overdue_count'] ?? 0) > 0)
                                        <span class="router-badge warn">Overdue {{ $group['overdue_count'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="router-tech-body">
                                <div class="router-table-wrap">
                                    <table class="table table-sm align-middle table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:170px;">Serial</th>
                                                <th style="width:180px;">Batch</th>
                                                <th>Router</th>
                                                <th style="width:170px;">Assigned</th>
                                                <th style="width:170px;">Expected Deploy</th>
                                                <th style="width:180px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(($group['routers'] ?? []) as $row)
                                                @php
                                                    $row = (array) $row;
                                                    $inventory = $row['_inventory'] ?? null;
                                                    $actionUrl = $buildAdminDeployUrl($inventory);
                                                @endphp
                                                <tr>
                                                    <td>{{ $row['_serial'] ?? '—' }}</td>
                                                    <td><span class="inv-chip">{{ $row['batch_number'] ?? '—' }}</span></td>
                                                    <td>
                                                        <div class="router-cell-title">{{ trim(($row['brand'] ?? '—') . ' ' . ($row['model_number'] ?? '')) }}</div>
                                                        <div class="router-cell-sub">{{ $row['urgency'] ?? '—' }}{{ isset($row['days_assigned']) ? ' • ' . $row['days_assigned'] . ' days' : '' }}</div>
                                                    </td>
                                                    <td>{{ $row['assigned_date'] ?? '—' }}</td>
                                                    <td>{{ $row['expected_deployment_date'] ?? '—' }}</td>
                                                    <td>
                                                        @if($actionUrl)
                                                            <a class="btn btn-sm btn-dark" href="{{ $actionUrl }}" data-inv-loading>Deploy for Tech</a>
                                                        @else
                                                            <span class="inv-muted">No local serial match</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="router-empty">No technician router allocations found.</div>
                    @endforelse
                </div>
                @if($routerPaginator && $routerPaginator->hasPages())
                    <div>{{ $routerPaginator->links() }}</div>
                @endif
            </div>
        </section>
    @else
        <section class="inv-card router-section">
            <div class="router-section-head">
                <div>
                    <h2 class="router-section-title">{{ $sectionMeta['label'] }}</h2>
                    <div class="router-section-sub">{{ $sectionMeta['subtitle'] }}</div>
                </div>
            </div>
            <div class="router-section-body">
                <div class="router-table-wrap {{ $section === 'deployed' ? 'router-table-wrap-fit' : '' }}">
                    <table class="table table-sm align-middle table-hover mb-0 {{ $section === 'deployed' ? 'router-deployed-table' : '' }}">
                        <thead>
                            <tr>
                                @if($section === 'in-stock')
                                    <th style="width:170px;">Serial</th>
                                    <th style="width:180px;">Batch</th>
                                    <th>Router</th>
                                    <th style="width:170px;">MAC</th>
                                    <th style="width:120px;">Status</th>
                                    <th style="width:150px;">Location</th>
                                    <th style="width:180px;">Received</th>
                                @elseif($section === 'deployed')
                                    <th style="width:220px;">Router</th>
                                    <th style="width:170px;">Serial</th>
                                    <th style="width:170px;">Batch</th>
                                    <th>Site</th>
                                    <th style="width:180px;">Installed</th>
                                @else
                                    <th style="width:170px;">Serial</th>
                                    <th style="width:180px;">Batch</th>
                                    <th>Router</th>
                                    <th style="width:140px;">Status</th>
                                    <th style="width:120px;">Location</th>
                                    <th style="width:220px;">Notes</th>
                                    <th style="width:180px;">Updated</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                @php $row = (array) $row; @endphp
                                @if($section === 'in-stock')
                                    <tr>
                                        <td>{{ $row['serial_number'] ?? '—' }}</td>
                                        <td><span class="inv-chip">{{ $row['batch_number'] ?? '—' }}</span></td>
                                        <td>
                                            <div class="router-cell-title">{{ trim(($row['brand'] ?? '—') . ' ' . ($row['model_number'] ?? '')) }}</div>
                                            <div class="router-cell-sub">{{ ($row['description'] ?? '') !== '' ? $row['description'] : 'No description' }}</div>
                                        </td>
                                        <td>{{ $row['mac_address'] ?? '—' }}</td>
                                        <td><span class="router-badge ok">{{ $row['status'] ?? '—' }}</span></td>
                                        <td>{{ $row['location_name'] ?? '—' }}</td>
                                        <td>{{ $row['created_at'] ?? '—' }}</td>
                                    </tr>
                                @elseif($section === 'deployed')
                                    @php
                                        $isPrimary = (string) ($row['is_primary'] ?? '0') === '1';
                                        $apiStatus = trim((string) ($row['status'] ?? ''));
                                        $routerStatus = trim((string) ($row['router_status'] ?? ''));
                                    @endphp
                                    <tr>
                                        <td data-label="Router">
                                            <div class="router-cell-title">{{ trim(($row['brand'] ?? '—') . ' ' . ($row['model_number'] ?? '')) }}</div>
                                            <div class="router-cell-sub">
                                                @if($isPrimary)
                                                    <span class="router-badge dark">Main</span>
                                                @else
                                                    <span class="router-badge">Router</span>
                                                @endif
                                                <span class="ms-1">{{ $apiStatus !== '' ? ucfirst($apiStatus) : '—' }}</span>
                                                @if($routerStatus !== '')
                                                    • {{ ucfirst($routerStatus) }}
                                                @endif
                                            </div>
                                        </td>
                                        <td data-label="Serial">{{ $row['serial_number'] ?? '—' }}</td>
                                        <td data-label="Batch"><span class="inv-chip">{{ $row['batch_number'] ?? '—' }}</span></td>
                                        <td data-label="Site">
                                            <div class="router-cell-title">{{ $row['site_name'] ?? '—' }}</div>
                                            <div class="router-cell-sub">Site ID: {{ $row['site_id'] ?? '—' }}</div>
                                        </td>
                                        <td data-label="Installed">{{ $row['installed_date'] ?? '—' }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td>{{ $row['serial_number'] ?? '—' }}</td>
                                        <td><span class="inv-chip">{{ $row['batch_number'] ?? '—' }}</span></td>
                                        <td>
                                            <div class="router-cell-title">{{ trim(($row['brand'] ?? '—') . ' ' . ($row['model_number'] ?? '')) }}</div>
                                            <div class="router-cell-sub">MAC: {{ $row['mac_address'] ?? '—' }}</div>
                                        </td>
                                        <td><span class="router-badge warn">{{ $row['status'] ?? '—' }}</span></td>
                                        <td>{{ ucfirst((string) ($row['location_type'] ?? '—')) }}</td>
                                        <td>{{ ($row['notes'] ?? '') !== '' ? $row['notes'] : '—' }}</td>
                                        <td>{{ $row['updated_at'] ?? '—' }}</td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="{{ $section === 'deployed' ? 5 : 7 }}" class="p-0">
                                        <div class="inv-empty">
                                            <div class="inv-empty-ico">
                                                <i class="bi bi-hdd-network" style="font-size:22px;"></i>
                                            </div>
                                            <p class="inv-empty-title mb-0">No router records</p>
                                            <div class="inv-empty-sub">Adjust filters or switch workspace.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($routerPaginator && $routerPaginator->hasPages())
                    <div>{{ $routerPaginator->links() }}</div>
                @endif
            </div>
        </section>
    @endif

    @if(($workspace['type'] ?? 'table') === 'batches' && !empty($workspace['focus']))
        @php
            $focus = (array) $workspace['focus'];
            $deployedSites = collect($focus['routers'] ?? [])
                ->map(fn ($row) => (array) $row)
                ->filter(function (array $row) {
                    return ($row['_state_key'] ?? '') === 'deployed'
                        && trim((string) ($row['site_name'] ?? '')) !== '';
                })
                ->groupBy(function (array $row) {
                    $siteId = trim((string) ($row['site_id'] ?? ''));
                    $siteName = trim((string) ($row['site_name'] ?? ''));

                    return $siteId !== '' ? 'site:' . $siteId : 'name:' . $siteName;
                })
                ->map(function ($siteRows, string $siteKey) {
                    $first = (array) $siteRows->first();
                    $serials = $siteRows
                        ->map(fn ($row) => trim((string) ($row['_serial'] ?? $row['serial_number'] ?? '')))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    return [
                        'site_key' => $siteKey,
                        'site_name' => trim((string) ($first['site_name'] ?? 'Unknown Site')),
                        'site_id' => trim((string) ($first['site_id'] ?? '')),
                        'router_count' => count($serials),
                        'serials' => $serials,
                    ];
                })
                ->sort(function (array $left, array $right) {
                    $countCompare = ($right['router_count'] ?? 0) <=> ($left['router_count'] ?? 0);
                    if ($countCompare !== 0) {
                        return $countCompare;
                    }

                    return strcmp((string) ($left['site_name'] ?? ''), (string) ($right['site_name'] ?? ''));
                })
                ->values();
        @endphp
        <div class="router-modal-layer" id="routerBatchModal" aria-hidden="true">
            <div class="router-modal-backdrop" data-router-modal-close></div>
            <section class="router-modal-card">
                <div class="router-modal-head">
                    <div>
                        <div class="router-badge ok">Batch Workspace</div>
                        <h2 class="router-modal-title mt-2">{{ $focus['batch_number'] }}</h2>
                        <div class="router-modal-sub">
                            {{ $focus['brand'] ?: 'Router' }} {{ $focus['model_number'] ?: '' }}
                        </div>
                    </div>
                    <div class="router-actions">
                        <span class="router-badge">Available {{ $focus['available_count'] }}</span>
                        <span class="router-badge">With Techs {{ $focus['with_techs_count'] }}</span>
                        <span class="router-badge">Deployed {{ $focus['deployed_count'] }}</span>
                        <span class="router-badge warn">Faulty {{ $focus['faulty_count'] }}</span>
                        <button class="btn btn-sm btn-outline-dark router-modal-close" type="button" data-router-modal-close>
                            <i class="bi bi-x-lg me-1"></i> <span data-router-modal-close-label>Close</span>
                        </button>
                    </div>
                </div>
                <div class="router-modal-body">
                    <section class="router-site-list-section">
                        <div class="router-site-list-head">
                            <div>
                                <h3 class="router-section-title mb-0">Deployed Sites</h3>
                                <div class="router-section-sub">Open the deployed sites list when you need site names.</div>
                            </div>
                        </div>

                        <div class="router-site-summary">
                            <div>
                                <div class="router-site-list-title">{{ $deployedSites->count() }} deployed site{{ $deployedSites->count() === 1 ? '' : 's' }}</div>
                                <div class="router-site-list-sub">
                                    {{ number_format((int) ($focus['deployed_count'] ?? 0)) }} router{{ (int) ($focus['deployed_count'] ?? 0) === 1 ? '' : 's' }} from this batch are currently on deployed sites.
                                </div>
                            </div>
                            <div class="router-site-summary-actions">
                                <span class="router-badge ok">{{ $deployedSites->count() }} Sites</span>
                                <span class="router-badge">{{ number_format((int) ($focus['deployed_count'] ?? 0)) }} Routers</span>
                                @if($deployedSites->isNotEmpty())
                                    <button class="btn btn-sm btn-outline-dark" type="button" data-router-open-sites>
                                        <i class="bi bi-buildings me-1"></i> View Deployed Sites
                                    </button>
                                @endif
                            </div>
                        </div>
                        @if($deployedSites->isEmpty())
                            <div class="router-empty">No deployed sites are attached to this batch yet.</div>
                        @endif
                    </section>

                    <div class="router-table-wrap">
                        <table class="table table-sm align-middle table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width:150px;">State</th>
                                    <th style="width:180px;">Serial</th>
                                    <th>Router</th>
                                    <th style="width:220px;">Current Owner / Site</th>
                                    <th style="width:200px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($focus['routers'] ?? []) as $row)
                                    @php
                                        $row = (array) $row;
                                        $inventory = $row['_inventory'] ?? null;
                                        $actionUrl = ($row['_state_key'] ?? '') === 'with-techs'
                                            ? $buildAdminDeployUrl($inventory)
                                            : null;
                                    @endphp
                                    <tr>
                                        <td><span class="router-badge {{ ($row['_state_key'] ?? '') === 'faulty' ? 'warn' : (($row['_state_key'] ?? '') === 'deployed' ? 'dark' : 'ok') }}">{{ $row['_state_label'] ?? '—' }}</span></td>
                                        <td>{{ $row['_serial'] ?? '—' }}</td>
                                        <td>
                                            <div class="router-cell-title">{{ trim(($row['brand'] ?? '—') . ' ' . ($row['model_number'] ?? '')) }}</div>
                                            <div class="router-cell-sub">MAC: {{ $row['mac_address'] ?? '—' }}</div>
                                        </td>
                                        <td>
                                            @if(($row['_state_key'] ?? '') === 'with-techs')
                                                <div class="router-cell-title">{{ $row['technician_name'] ?? '—' }}</div>
                                                <div class="router-cell-sub">{{ $row['assigned_date'] ?? '—' }}</div>
                                            @elseif(($row['_state_key'] ?? '') === 'deployed')
                                                <div class="router-cell-title">{{ $row['site_name'] ?? '—' }}</div>
                                                <div class="router-cell-sub">Site ID: {{ $row['site_id'] ?? '—' }}</div>
                                            @else
                                                <div class="router-cell-title">{{ $row['location_name'] ?? ucfirst((string) ($row['location_type'] ?? '—')) }}</div>
                                                <div class="router-cell-sub">{{ $row['status'] ?? '—' }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($actionUrl)
                                                <a class="btn btn-sm btn-dark" href="{{ $actionUrl }}" data-inv-loading>Deploy for Tech</a>
                                            @else
                                                <span class="inv-muted">No action</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <div class="router-modal-layer" id="routerSitesModal" aria-hidden="true">
            <div class="router-modal-backdrop" data-router-modal-close></div>
            <section class="router-modal-card router-site-modal">
                <div class="router-modal-head">
                    <div>
                        <div class="router-badge ok">Deployed Sites</div>
                        <h3 class="router-modal-title mt-2">{{ $focus['batch_number'] }}</h3>
                        <div class="router-modal-sub">
                            {{ $deployedSites->count() }} site{{ $deployedSites->count() === 1 ? '' : 's' }} with
                            {{ number_format((int) ($focus['deployed_count'] ?? 0)) }} deployed router{{ (int) ($focus['deployed_count'] ?? 0) === 1 ? '' : 's' }}.
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-dark router-modal-close" type="button" data-router-modal-close>
                        <i class="bi bi-x-lg me-1"></i> <span data-router-modal-close-label>Close</span>
                    </button>
                </div>
                <div class="router-modal-body">
                    @if($deployedSites->isNotEmpty())
                        <div class="router-site-list">
                            @foreach($deployedSites as $site)
                                <button class="router-site-list-item" type="button" data-router-site-key="{{ $site['site_key'] }}">
                                    <div class="router-site-list-main">
                                        <div class="router-site-list-title">{{ $site['site_name'] }}</div>
                                        <div class="router-site-list-sub">
                                            Site ID: {{ $site['site_id'] !== '' ? $site['site_id'] : '—' }}
                                        </div>
                                    </div>
                                    <div class="router-site-list-meta">
                                        <span class="router-badge ok">{{ $site['router_count'] }} Routers</span>
                                        <span class="router-site-list-cta">
                                            <i class="bi bi-eye"></i>
                                            View Serials
                                        </span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="router-empty">No deployed sites are attached to this batch yet.</div>
                    @endif
                </div>
            </section>
        </div>

        <div class="router-modal-layer" id="routerSerialModal" aria-hidden="true">
            <div class="router-modal-backdrop" data-router-modal-close></div>
            <section class="router-modal-card router-serial-modal">
                <div class="router-modal-head">
                    <div>
                        <div class="router-badge ok">Site Router Serials</div>
                        <h3 class="router-serial-title">Router Serials</h3>
                        <div class="router-serial-site" data-router-serial-site>—</div>
                        <div class="router-serial-meta">
                            <span class="router-badge" data-router-serial-site-id>Site ID —</span>
                            <span class="router-badge ok" data-router-serial-count>0 routers</span>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-dark router-modal-close" type="button" data-router-modal-close>
                        <i class="bi bi-x-lg me-1"></i> <span data-router-modal-close-label>Close</span>
                    </button>
                </div>
                <div class="router-modal-body">
                    <div class="router-serial-list" data-router-serial-list></div>
                    <div class="router-empty" data-router-serial-empty>No router serial numbers were returned for this site.</div>
                </div>
            </section>
        </div>
    @endif
</div>

@if(($workspace['type'] ?? 'table') === 'technician-routers' && $can('deployments.manage'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('routerDeployModal');
        if (!modal) {
            return;
        }

        const lookupRoot = modal.querySelector('[data-router-site-lookup]');
        const lookupUrl = lookupRoot?.getAttribute('data-site-url') || '';
        const serialText = document.getElementById('routerDeploySerialText');
        const searchInput = document.getElementById('routerDeploySiteLookup');
        const status = document.getElementById('routerDeployStatus');
        const results = document.getElementById('routerDeployResults');
        const selectedCard = document.getElementById('routerDeploySelectedCard');
        const selectedName = document.getElementById('routerDeploySelectedName');
        const selectedId = document.getElementById('routerDeploySelectedId');
        const selectedSerial = document.getElementById('routerDeploySelectedSerial');
        const selectedSignal = document.getElementById('routerDeploySelectedSignal');
        const submitButton = document.getElementById('routerDeploySubmit');
        const mainToggle = document.getElementById('routerDeployMainToggle');

        let debounceId = null;
        let activeController = null;
        let currentRouter = null;
        let selectedSite = null;

        const setStatus = function (message) {
            if (status) {
                status.textContent = message || '';
            }
        };

        const hideResults = function () {
            if (!results) {
                return;
            }

            results.classList.remove('show');
            results.innerHTML = '';
        };

        const resetSelection = function () {
            selectedSite = null;
            if (selectedCard) {
                selectedCard.classList.remove('show');
            }
            if (selectedName) {
                selectedName.textContent = 'No site selected';
            }
            if (selectedId) {
                selectedId.textContent = 'Not selected';
            }
            if (selectedSerial) {
                selectedSerial.textContent = 'Not selected';
            }
            if (selectedSignal) {
                selectedSignal.textContent = 'Waiting for lookup details';
            }
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Deploy Router';
            }
        };

        const applySelection = function (site) {
            selectedSite = site;
            if (selectedCard) {
                selectedCard.classList.add('show');
            }
            if (selectedName) {
                selectedName.textContent = site.site_name || 'No site selected';
            }
            if (selectedId) {
                selectedId.textContent = site.site_id || 'Not selected';
            }
            if (selectedSerial) {
                selectedSerial.textContent = site.site_serial || 'Not selected';
            }
            if (selectedSignal) {
                const signalBits = [];
                if (site.status) {
                    signalBits.push(site.status);
                }
                if (site.rx_power) {
                    signalBits.push('RX ' + site.rx_power);
                }
                if (site.tx_power) {
                    signalBits.push('TX ' + site.tx_power);
                }
                selectedSignal.textContent = signalBits.length ? signalBits.join(' • ') : 'Waiting for lookup details';
            }
            if (searchInput) {
                searchInput.value = site.site_name || '';
            }
            if (submitButton) {
                submitButton.disabled = false;
            }
            hideResults();
            setStatus('Selected ' + (site.site_name || 'site') + '.');
        };

        const renderResults = function (sites) {
            if (!results) {
                return;
            }

            if (!sites.length) {
                hideResults();
                setStatus('No registered site matched that search.');
                return;
            }

            results.innerHTML = '';

            sites.forEach(function (site) {
                const option = document.createElement('button');
                const title = document.createElement('strong');
                const meta = document.createElement('span');

                option.type = 'button';
                option.className = 'router-deploy-option';

                title.textContent = site.site_name || 'Site';
                meta.textContent = 'Site ID ' + (site.site_id || 'N/A') + ' • ONT ' + (site.site_serial || 'N/A');

                option.appendChild(title);
                option.appendChild(meta);
                option.addEventListener('click', function () {
                    applySelection(site);
                });
                results.appendChild(option);
            });

            results.classList.add('show');
            setStatus('Select the site you want to deploy this router to.');
        };

        const runLookup = function (term) {
            if (!lookupUrl) {
                setStatus('Site lookup is unavailable.');
                return;
            }

            if (activeController) {
                activeController.abort();
            }

            activeController = new AbortController();
            setStatus('Searching registered sites...');
            hideResults();

            fetch(lookupUrl + '?q=' + encodeURIComponent(term), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: activeController.signal
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Lookup failed.');
                    }

                    return response.json();
                })
                .then(function (payload) {
                    renderResults(Array.isArray(payload.data) ? payload.data : []);
                })
                .catch(function (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    setStatus('Could not load registered sites right now.');
                });
        };

        const openModal = function (trigger) {
            currentRouter = {
                serial: trigger.getAttribute('data-router-serial') || '',
                batch: trigger.getAttribute('data-router-batch') || '',
                label: trigger.getAttribute('data-router-label') || 'Router'
            };

            if (serialText) {
                serialText.textContent = currentRouter.serial || '—';
            }

            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            resetSelection();
            hideResults();
            setStatus('Search by site name or ONT serial to select a registered site.');
            if (searchInput) {
                searchInput.value = '';
                window.setTimeout(function () {
                    searchInput.focus();
                }, 50);
            }
            if (mainToggle) {
                mainToggle.checked = false;
            }
        };

        const closeModal = function () {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            if (activeController) {
                activeController.abort();
            }
            hideResults();
            resetSelection();
            currentRouter = null;
        };

        document.querySelectorAll('[data-router-deploy-open]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                openModal(trigger);
            });
        });

        modal.querySelectorAll('[data-router-deploy-close]').forEach(function (trigger) {
            trigger.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('show')) {
                closeModal();
            }
        });

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const term = searchInput.value.trim();
                resetSelection();

                if (debounceId) {
                    window.clearTimeout(debounceId);
                }

                if (term.length < 2) {
                    hideResults();
                    setStatus('Type at least 2 characters to search a registered site.');
                    return;
                }

                debounceId = window.setTimeout(function () {
                    runLookup(term);
                }, 320);
            });
        }

        if (submitButton) {
            submitButton.addEventListener('click', function () {
                if (!selectedSite) {
                    return;
                }

                submitButton.disabled = true;
                submitButton.textContent = 'Preparing...';
                setStatus('Preparing router deployment for ' + (selectedSite.site_name || 'selected site') + '...');
                if (typeof invShowLoader === 'function') {
                    invShowLoader();
                }

                window.setTimeout(function () {
                    if (typeof invHideLoader === 'function') {
                        invHideLoader();
                    }
                    submitButton.disabled = false;
                    submitButton.textContent = 'Deploy Router';
                    setStatus('Site selected. Final router deployment submit will be wired next.');
                }, 1100);
            });
        }
    });
</script>
@endif

@if(($workspace['type'] ?? 'table') === 'batches' && !empty($workspace['focus']))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modals = {
            batch: document.getElementById('routerBatchModal'),
            sites: document.getElementById('routerSitesModal'),
            serial: document.getElementById('routerSerialModal')
        };
        const openSitesButtons = document.querySelectorAll('[data-router-open-sites]');
        const siteButtons = document.querySelectorAll('[data-router-site-key]');
        const closeButtons = document.querySelectorAll('[data-router-modal-close]');
        const closeLabels = document.querySelectorAll('[data-router-modal-close-label]');
        const serialList = document.querySelector('[data-router-serial-list]');
        const serialEmpty = document.querySelector('[data-router-serial-empty]');
        const serialSite = document.querySelector('[data-router-serial-site]');
        const serialSiteId = document.querySelector('[data-router-serial-site-id]');
        const serialCount = document.querySelector('[data-router-serial-count]');
        const sites = @json($deployedSites);

        let current = null;
        const stack = [];

        const setBodyLocked = function (locked) {
            document.body.style.overflow = locked ? 'hidden' : '';
        };

        const hideModal = function (modal) {
            if (!modal) {
                return;
            }

            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        };

        const updateCloseLabels = function () {
            const label = stack.length ? 'Back' : 'Close';
            closeLabels.forEach(function (node) {
                node.textContent = label;
            });
        };

        const showModal = function (name, push = true) {
            const modal = modals[name];
            if (!modal) {
                return;
            }

            if (current && push) {
                stack.push(current);
            }

            Object.values(modals).forEach(hideModal);
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            current = name;
            setBodyLocked(true);
            updateCloseLabels();
        };

        const clearFocusQuery = function () {
            try {
                const url = new URL(window.location.href);
                url.searchParams.delete('focus_batch');
                history.replaceState({}, '', url.toString());
            } catch (error) {
                // Keep current URL if parsing fails.
            }
        };

        const closeModal = function () {
            if (stack.length) {
                const previous = stack.pop();
                showModal(previous, false);
                return;
            }

            Object.values(modals).forEach(hideModal);
            current = null;
            setBodyLocked(false);
            clearFocusQuery();
            updateCloseLabels();
        };

        const renderSerials = function (site) {
            if (!serialList || !serialEmpty) {
                return;
            }

            serialList.innerHTML = '';
            const serials = Array.isArray(site.serials) ? site.serials : [];
            if (!serials.length) {
                serialEmpty.style.display = 'block';
                return;
            }

            serialEmpty.style.display = 'none';
            serials.forEach(function (serial) {
                const item = document.createElement('div');
                const label = document.createElement('div');
                const value = document.createElement('div');

                item.className = 'router-serial-item';
                label.className = 'router-serial-item-label';
                value.className = 'router-serial-value';

                label.textContent = 'routerserial:';
                value.textContent = serial;

                item.appendChild(label);
                item.appendChild(value);
                serialList.appendChild(item);
            });
        };

        const openSerialModal = function (siteKey) {
            const site = sites.find(function (entry) {
                return entry.site_key === siteKey;
            });
            if (!site) {
                return;
            }

            if (serialSite) {
                serialSite.textContent = site.site_name || 'Site';
            }
            if (serialSiteId) {
                serialSiteId.textContent = 'Site ID ' + (site.site_id || '—');
            }
            if (serialCount) {
                const count = site.router_count || 0;
                serialCount.textContent = count + ' router' + (count === 1 ? '' : 's');
            }
            renderSerials(site);
            showModal('serial', true);
        };

        if (modals.batch) {
            showModal('batch', false);
        }

        openSitesButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                showModal('sites', true);
            });
        });

        siteButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const key = button.getAttribute('data-router-site-key') || '';
                openSerialModal(key);
            });
        });

        closeButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && current) {
                closeModal();
            }
        });
    });
</script>
@endif

@if($isTechnicianScoped)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.classList.add('inv-routers-compact');
    });
</script>
@endif
@endsection
