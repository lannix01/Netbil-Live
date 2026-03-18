@extends('inventory::layout')

@section('page-title', 'Dashboard')
@section('page-subtitle', 'Overview')

@php
    $access = \App\Modules\Inventory\Support\InventoryAccess::class;
    $authUser = auth('inventory')->user();
    $can = fn (string $permission) => $access::allows($authUser, $permission);
@endphp

@section('page-actions')
    @if($can('receipts.manage'))
        <a class="btn btn-dark btn-sm" href="{{ route('inventory.receipts.create') }}" data-inv-loading>
            <i class="bi bi-inbox-arrow-down me-1"></i> Receive
        </a>
    @endif
    @if($can('routers.view'))
        <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.routers.index') }}" data-inv-loading>
            <i class="bi bi-hdd-network me-1"></i> Routers
        </a>
    @endif
    @if($can('logs.view'))
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.logs.index') }}" data-inv-loading>
            <i class="bi bi-journal-text me-1"></i> Logs
        </a>
    @endif
@endsection

@section('inventory-content')
@php
    $items = (int) ($items ?? 0);
    $lowStock = (int) ($lowStock ?? 0);
    $teams = (int) ($teams ?? 0);
    $logs7d = (int) ($logs7d ?? 0);
    $loading = request()->boolean('loading');
@endphp

<style>
    .dash-shell{ display:grid; gap:18px; }
    .dash-stats{
        display:grid;
        grid-template-columns:repeat(4, minmax(0, 1fr));
        gap:14px;
    }
    .dash-stat{
        position:relative;
        display:grid;
        gap:14px;
        min-height:160px;
        padding:18px;
        overflow:hidden;
        background:
            radial-gradient(circle at top right, rgba(77, 138, 102, .10), transparent 28%),
            linear-gradient(180deg, #ffffff 0%, #f6fbf5 100%);
    }
    .dash-stat::before{
        content:"";
        position:absolute;
        top:0;
        left:0;
        right:0;
        height:5px;
        background:linear-gradient(90deg, var(--brand) 0%, var(--brand-strong) 100%);
    }
    .dash-stat:nth-child(2)::before{
        background:linear-gradient(90deg, #b88c38 0%, #8c6621 100%);
    }
    .dash-stat:nth-child(3)::before{
        background:linear-gradient(90deg, #6f7a70 0%, #4f594f 100%);
    }
    .dash-stat:nth-child(4)::before{
        background:linear-gradient(90deg, #54827b 0%, #3b5f59 100%);
    }
    .dash-stat-head{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
    }
    .dash-stat-label{
        font-size:11px;
        font-weight:700;
        letter-spacing:.16em;
        text-transform:uppercase;
        color:var(--muted);
    }
    .dash-stat-value{
        margin-top:10px;
        font-family:"Space Grotesk", sans-serif;
        font-size:42px;
        line-height:.95;
        letter-spacing:-.06em;
        color:var(--text);
    }
    .dash-stat-sub{
        color:var(--muted);
        font-size:13px;
        line-height:1.45;
    }
    .dash-stat-ico{
        width:48px;
        height:48px;
        border-radius:16px;
        display:grid;
        place-items:center;
        border:1px solid var(--line);
        background:#ffffff;
        color:var(--brand-strong);
        font-size:18px;
        box-shadow:0 10px 20px rgba(28, 46, 34, .06);
    }

    .dash-grid{
        display:grid;
        grid-template-columns:minmax(0, 1.15fr) minmax(320px, .85fr);
        gap:16px;
    }
    .dash-panel{
        overflow:hidden;
    }
    .dash-panel-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:18px 20px;
        border-bottom:1px solid var(--line);
        background:linear-gradient(180deg, #fbfdf8 0%, #eef5ee 100%);
    }
    .dash-panel-title{
        margin:0;
        font-family:"Space Grotesk", sans-serif;
        font-size:22px;
        letter-spacing:-.04em;
    }
    .dash-panel-sub{
        color:var(--muted);
        font-size:13px;
    }
    .dash-panel-body{
        padding:18px;
    }

    .dash-actions{
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:12px;
    }
    .dash-action{
        display:grid;
        grid-template-columns:auto 1fr auto;
        align-items:center;
        gap:12px;
        padding:15px 16px;
        border-radius:22px;
        border:1px solid var(--line);
        background:linear-gradient(180deg, #ffffff 0%, #f5faf4 100%);
        text-decoration:none;
        transition:transform .16s ease, border-color .16s ease, box-shadow .16s ease;
    }
    .dash-action:hover{
        transform:translateY(-2px);
        border-color:rgba(77, 138, 102, .26);
        color:inherit;
        box-shadow:0 14px 24px rgba(28, 46, 34, .08);
    }
    .dash-action-ico{
        width:44px;
        height:44px;
        border-radius:15px;
        display:grid;
        place-items:center;
        background:linear-gradient(180deg, var(--brand) 0%, var(--brand-strong) 100%);
        color:#ffffff;
        font-size:17px;
        box-shadow:0 12px 22px rgba(77, 138, 102, .18);
    }
    .dash-action-title{
        font-weight:700;
        font-size:14px;
        line-height:1.25;
    }
    .dash-action-sub{
        margin-top:3px;
        color:var(--muted);
        font-size:12px;
        line-height:1.4;
    }
    .dash-action-arrow{
        color:var(--muted);
        font-size:14px;
    }

    .dash-status{
        display:grid;
        gap:12px;
    }
    .dash-status-item{
        padding:16px 18px;
        border-radius:22px;
        border:1px solid var(--line);
        background:linear-gradient(180deg, #ffffff 0%, #f7fbf5 100%);
    }
    .dash-status-label{
        font-size:11px;
        font-weight:700;
        letter-spacing:.16em;
        text-transform:uppercase;
        color:var(--muted);
    }
    .dash-status-value{
        margin-top:8px;
        font-size:15px;
        line-height:1.5;
        color:var(--text-soft);
    }
    .dash-status-actions{
        margin-top:12px;
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }

    .dash-skeleton{
        padding:20px;
    }

    @media (max-width: 1199.98px){
        .dash-stats{ grid-template-columns:repeat(2, minmax(0, 1fr)); }
        .dash-grid{ grid-template-columns:1fr; }
    }
    @media (max-width: 767.98px){
        .dash-actions{ grid-template-columns:1fr; }
    }
    @media (max-width: 575.98px){
        .dash-stats{ grid-template-columns:1fr; }
    }
</style>

<div class="dash-shell">
    @if($loading)
        <div class="inv-card dash-skeleton">
            <div class="inv-skeleton inv-skel-row lg" style="width:180px;"></div>
            <div class="inv-skeleton inv-skel-row" style="width:320px;"></div>
            <div class="inv-skeleton inv-skel-row sm" style="width:220px;"></div>
        </div>
    @endif

    <section class="dash-stats">
        <article class="inv-card dash-stat">
            <div class="dash-stat-head">
                <div>
                    <div class="dash-stat-label">Items</div>
                    <div class="dash-stat-value">{{ number_format($items) }}</div>
                </div>
                <div class="dash-stat-ico"><i class="bi bi-box-seam"></i></div>
            </div>
            <div class="dash-stat-sub">Tracked stock lines.</div>
        </article>

        <article class="inv-card dash-stat">
            <div class="dash-stat-head">
                <div>
                    <div class="dash-stat-label">Low Stock</div>
                    <div class="dash-stat-value">{{ number_format($lowStock) }}</div>
                </div>
                <div class="dash-stat-ico"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
            <div class="dash-stat-sub">Needs restock.</div>
        </article>

        <article class="inv-card dash-stat">
            <div class="dash-stat-head">
                <div>
                    <div class="dash-stat-label">Teams</div>
                    <div class="dash-stat-value">{{ number_format($teams) }}</div>
                </div>
                <div class="dash-stat-ico"><i class="bi bi-people"></i></div>
            </div>
            <div class="dash-stat-sub">Active field groups.</div>
        </article>

        <article class="inv-card dash-stat">
            <div class="dash-stat-head">
                <div>
                    <div class="dash-stat-label">Logs, 7 Days</div>
                    <div class="dash-stat-value">{{ number_format($logs7d) }}</div>
                </div>
                <div class="dash-stat-ico"><i class="bi bi-journal-text"></i></div>
            </div>
            <div class="dash-stat-sub">Recent movement trail.</div>
        </article>
    </section>

    <section class="dash-grid">
        <article class="inv-card dash-panel">
            <div class="dash-panel-head">
                <div>
                    <h2 class="dash-panel-title">Quick Actions</h2>
                    <div class="dash-panel-sub">Open the main work areas.</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="dash-actions">
                    @if($can('receipts.manage'))
                        <a class="dash-action" href="{{ route('inventory.receipts.create') }}" data-inv-loading>
                            <div class="dash-action-ico"><i class="bi bi-inbox-arrow-down"></i></div>
                            <div>
                                <div class="dash-action-title">Receive</div>
                                <div class="dash-action-sub">Add incoming stock.</div>
                            </div>
                            <div class="dash-action-arrow"><i class="bi bi-arrow-up-right"></i></div>
                        </a>
                    @endif

                    @if($can('items.view'))
                        <a class="dash-action" href="{{ route('inventory.items.index') }}" data-inv-loading>
                            <div class="dash-action-ico"><i class="bi bi-box-seam"></i></div>
                            <div>
                                <div class="dash-action-title">Items</div>
                                <div class="dash-action-sub">Manage catalog lines.</div>
                            </div>
                            <div class="dash-action-arrow"><i class="bi bi-arrow-up-right"></i></div>
                        </a>
                    @endif

                    @if($can('assignments.view'))
                        <a class="dash-action" href="{{ route('inventory.assignments.index') }}" data-inv-loading>
                            <div class="dash-action-ico"><i class="bi bi-person-check"></i></div>
                            <div>
                                <div class="dash-action-title">Assignments</div>
                                <div class="dash-action-sub">Allocate to techs.</div>
                            </div>
                            <div class="dash-action-arrow"><i class="bi bi-arrow-up-right"></i></div>
                        </a>
                    @endif

                    @if($can('deployments.view'))
                        <a class="dash-action" href="{{ route('inventory.deployments.index') }}" data-inv-loading>
                            <div class="dash-action-ico"><i class="bi bi-geo-alt"></i></div>
                            <div>
                                <div class="dash-action-title">Deployments</div>
                                <div class="dash-action-sub">Review site installs.</div>
                            </div>
                            <div class="dash-action-arrow"><i class="bi bi-arrow-up-right"></i></div>
                        </a>
                    @endif

                    @if($can('routers.view'))
                        <a class="dash-action" href="{{ route('inventory.routers.index') }}" data-inv-loading>
                            <div class="dash-action-ico"><i class="bi bi-hdd-network"></i></div>
                            <div>
                                <div class="dash-action-title">Routers</div>
                                <div class="dash-action-sub">Query Skybrix data.</div>
                            </div>
                            <div class="dash-action-arrow"><i class="bi bi-arrow-up-right"></i></div>
                        </a>
                    @endif

                    @if($can('movements.view'))
                        <a class="dash-action" href="{{ route('inventory.movements.index') }}" data-inv-loading>
                            <div class="dash-action-ico"><i class="bi bi-arrow-left-right"></i></div>
                            <div>
                                <div class="dash-action-title">Transfers</div>
                                <div class="dash-action-sub">Move stock fast.</div>
                            </div>
                            <div class="dash-action-arrow"><i class="bi bi-arrow-up-right"></i></div>
                        </a>
                    @endif
                </div>
            </div>
        </article>

        <article class="inv-card dash-panel">
            <div class="dash-panel-head">
                <div>
                    <h2 class="dash-panel-title">Status</h2>
                    <div class="dash-panel-sub">Current checks.</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="dash-status">
                    <div class="dash-status-item">
                        <div class="dash-status-label">Restock</div>
                        <div class="dash-status-value">
                            @if($lowStock > 0)
                                {{ $lowStock }} item{{ $lowStock === 1 ? '' : 's' }} need attention.
                            @else
                                No low-stock items.
                            @endif
                        </div>
                        @if($can('alerts.view'))
                            <div class="dash-status-actions">
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('inventory.alerts.low_stock') }}" data-inv-loading>Open Alerts</a>
                            </div>
                        @endif
                    </div>

                    <div class="dash-status-item">
                        <div class="dash-status-label">Audit</div>
                        <div class="dash-status-value">{{ $logs7d }} log entr{{ $logs7d === 1 ? 'y' : 'ies' }} in the last 7 days.</div>
                        @if($can('logs.view'))
                            <div class="dash-status-actions">
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('inventory.logs.index') }}" data-inv-loading>Open Logs</a>
                            </div>
                        @endif
                    </div>

                    <div class="dash-status-item">
                        <div class="dash-status-label">Teams</div>
                        <div class="dash-status-value">{{ $teams }} team{{ $teams === 1 ? '' : 's' }} available for allocation and deployment.</div>
                        @if($can('teams.view'))
                            <div class="dash-status-actions">
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('inventory.teams.index') }}" data-inv-loading>Open Teams</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </article>
    </section>
</div>
@endsection
