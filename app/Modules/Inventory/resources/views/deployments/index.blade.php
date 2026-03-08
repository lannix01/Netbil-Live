@extends('inventory::layout')

@section('page-title', 'Deployments')
@section('page-subtitle', 'Audit deployments to sites by technicians, item, and reference.')

@section('page-actions')
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.assignments.index') }}" data-inv-loading>Tech Assignments</a>
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.team_deployments.index') }}" data-inv-loading>Team Deploy</a>
@endsection

@section('inventory-content')
@php
    $deployments = $deployments ?? collect();

    // Safe filter defaults (view won’t break if controller doesn’t implement them yet)
    $q = $q ?? request('q', '');
    $from = $from ?? request('from', '');
    $to = $to ?? request('to', '');
    $technician = $technician ?? request('technician', '');

    $loading = request()->boolean('loading');
@endphp

<style>
    /* Deployments index (scoped) */
    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }

    .inv-site{
        font-weight: 900;
    }
    .inv-subline{
        font-size: 12px;
        color: var(--muted);
        margin-top: 4px;
    }

    /* Responsive hiding */
    @media (max-width: 991.98px){
        .hide-md{ display:none; }
    }
    @media (max-width: 575.98px){
        .hide-xs{ display:none; }
    }
</style>

<div class="inv-card inv-table-card">

    <div class="inv-table-head">
        <div>
            <p class="inv-table-title mb-0">Deployment History</p>
            <div class="inv-table-sub">
                Track when and where items were deployed.
                @if(trim($q) !== '')
                    • Search: <span class="inv-chip">“{{ $q }}”</span>
                @endif
                @if($from || $to)
                    • Range:
                    <span class="inv-chip">{{ $from ?: '…' }}</span>
                    <span class="inv-chip">{{ $to ?: '…' }}</span>
                @endif
                @if(trim($technician) !== '')
                    • Tech: <span class="inv-chip">{{ $technician }}</span>
                @endif
            </div>
        </div>

        <div class="inv-table-tools">
            <form class="d-flex gap-2 flex-wrap align-items-center" method="GET" action="{{ route('inventory.deployments.index') }}" data-inv-loading>
                <input
                    class="form-control form-control-sm"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search site, reference, item…"
                    style="min-width:220px;"
                >

                <input
                    class="form-control form-control-sm"
                    type="date"
                    name="from"
                    value="{{ $from }}"
                    title="From"
                    style="min-width:150px;"
                >

                <input
                    class="form-control form-control-sm"
                    type="date"
                    name="to"
                    value="{{ $to }}"
                    title="To"
                    style="min-width:150px;"
                >

                <input
                    class="form-control form-control-sm"
                    name="technician"
                    value="{{ $technician }}"
                    placeholder="Technician (name)…"
                    style="min-width:170px;"
                >

                <button class="btn btn-sm btn-dark">Filter</button>

                @if(trim($q) !== '' || $from || $to || trim($technician) !== '')
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.deployments.index') }}" data-inv-loading>Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Optional skeleton preview: /inventory/deployments?loading=1 --}}
    <div class="inv-skel {{ $loading ? 'show' : '' }}">
        <div class="inv-skeleton inv-skel-row lg" style="width:260px;"></div>
        <div class="inv-skeleton inv-skel-row" style="width:420px;"></div>
        <div class="inv-divider"></div>

        @for($r=0;$r<8;$r++)
            <div class="d-flex gap-2 align-items-center mb-2">
                <div class="inv-skeleton" style="width:140px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton hide-xs" style="width:160px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="flex:1; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="width:80px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton hide-md" style="width:200px; height:14px; border-radius:10px;"></div>
            </div>
        @endfor
    </div>

    @if(!$loading)
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
                            <th class="hide-md" style="width:200px;">Ref</th>
                            <th class="hide-xs" style="width:200px;">Logged By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deployments as $d)
                            @php
                                $dt = $d->created_at?->format('Y-m-d H:i') ?? '—';
                                $tech = $d->technician?->name ?? '—';
                                $item = $d->item?->name ?? '—';
                                $qty = (int)($d->qty ?? 0);
                                $site = trim((string)($d->site_name ?? ''));
                                $siteShow = $site !== '' ? $site : '—';
                                $ref = trim((string)($d->reference ?? ''));
                                $refShow = $ref !== '' ? $ref : '—';
                                $by = $d->creator?->name ?? '—';
                            @endphp

                            <tr>
                                <td class="text-muted">{{ $dt }}</td>

                                <td>
                                    <div style="font-weight:900;">{{ $tech }}</div>
                                    <div class="inv-subline">Tech deployment</div>
                                </td>

                                <td>
                                    <div style="font-weight:900;">{{ $item }}</div>
                                    <div class="inv-subline">
                                        Ref: <span class="inv-chip">{{ $refShow }}</span>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge bg-dark">{{ $qty }}</span>
                                </td>

                                <td>
                                    <div class="inv-site">{{ $siteShow }}</div>
                                    @if($refShow !== '—')
                                        <div class="inv-subline">Ref: {{ $refShow }}</div>
                                    @endif
                                </td>

                                <td class="hide-md">{{ $refShow }}</td>
                                <td class="hide-xs">{{ $by }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-0">
                                    <div class="inv-empty">
                                        <div class="inv-empty-ico">📍</div>
                                        <p class="inv-empty-title mb-0">No deployments yet</p>
                                        <div class="inv-empty-sub">
                                            Deployments will appear here once technicians deploy assigned stock to sites.
                                        </div>
                                        <div class="mt-3">
                                            <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.assignments.index') }}" data-inv-loading>Go to assignments</a>
                                            <a class="btn btn-outline-secondary btn-sm ms-1" href="{{ route('inventory.team_deployments.index') }}" data-inv-loading>Team deploy</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($deployments, 'links'))
                <div class="p-3">
                    {{ $deployments->links() }}
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
