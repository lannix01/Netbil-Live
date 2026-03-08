@extends('inventory::layout')

@section('page-title', 'Low Stock Alerts')
@section('page-subtitle', 'Items at or below reorder level — restock before field work gets spicy.')

@section('page-actions')
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.items.index') }}" data-inv-loading>View Items</a>
    <a class="btn btn-dark btn-sm" href="{{ route('inventory.receipts.create') }}" data-inv-loading>Receive Stock</a>
@endsection

@section('inventory-content')
@php
    $items = $items ?? collect();

    // Safe filter defaults (won’t break if controller ignores them)
    $q = $q ?? request('q', '');
    $group = $group ?? request('group', '');

    $loading = request()->boolean('loading');
@endphp

<style>
    /* Low stock alerts (scoped) */
    .inv-alert-hero{
        padding: 16px !important;
        border: 1px solid var(--warnBd);
        background: var(--warnBg);
        color: var(--warnTx);
    }
    .inv-alert-title{
        font-weight: 900;
        margin:0;
        font-size: 16px;
    }
    .inv-alert-sub{
        font-size: 12px;
        opacity: .9;
        margin-top: 4px;
        line-height: 1.35;
    }

    .inv-sev{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border-radius: 999px;
        padding: 6px 10px;
        font-weight: 900;
        font-size: 12px;
        border: 1px solid rgba(0,0,0,.08);
        background: #fff;
        color: var(--text);
        white-space:nowrap;
    }
    .inv-sev.danger{
        border-color: rgba(220,38,38,.35);
        background: rgba(220,38,38,.10);
        color: #991b1b;
    }

    .inv-muted-sm{
        font-size: 12px;
        color: var(--muted);
        margin-top: 4px;
    }

    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }

    @media (max-width: 575.98px){
        .hide-xs{ display:none; }
    }
</style>

<div class="inv-card inv-alert-hero mb-3">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
            <p class="inv-alert-title mb-0">Restock watchlist</p>
            <div class="inv-alert-sub">
                These items are at/below reorder level. Receive stock to raise store quantity.
                @if(trim($q) !== '')
                    • Search: <span class="inv-chip">“{{ $q }}”</span>
                @endif
                @if(trim($group) !== '')
                    • Group: <span class="inv-chip">{{ $group }}</span>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.receipts.create') }}" data-inv-loading>+ Receive</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.items.index') }}" data-inv-loading>Manage items</a>
        </div>
    </div>
</div>

<div class="inv-card inv-table-card">
    <div class="inv-table-head">
        <div>
            <p class="inv-table-title mb-0">Low stock items</p>
            <div class="inv-table-sub">Prioritize items with qty = 0 first.</div>
        </div>

        <div class="inv-table-tools">
            <form class="d-flex gap-2 flex-wrap align-items-center" method="GET" action="{{ url()->current() }}" data-inv-loading>
                <input
                    class="form-control form-control-sm"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search item/group…"
                    style="min-width:220px;"
                >
                <input
                    class="form-control form-control-sm"
                    name="group"
                    value="{{ $group }}"
                    placeholder="Group (optional)…"
                    style="min-width:160px;"
                >
                <button class="btn btn-sm btn-dark">Filter</button>
                @if(trim($q) !== '' || trim($group) !== '')
                    <a class="btn btn-sm btn-outline-secondary" href="{{ url()->current() }}" data-inv-loading>Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Optional skeleton preview: /inventory/alerts/low-stock?loading=1 --}}
    <div class="inv-skel {{ $loading ? 'show' : '' }}">
        <div class="inv-skeleton inv-skel-row lg" style="width:240px;"></div>
        <div class="inv-skeleton inv-skel-row" style="width:420px;"></div>
        <div class="inv-divider"></div>
        @for($r=0;$r<7;$r++)
            <div class="d-flex gap-2 align-items-center mb-2">
                <div class="inv-skeleton" style="width:240px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton hide-xs" style="width:160px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="width:130px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="width:120px; height:14px; border-radius:10px;"></div>
            </div>
        @endfor
    </div>

    @if(!$loading)
        <div class="inv-table-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="hide-xs" style="width:200px;">Group</th>
                            <th style="width:140px;">Reorder</th>
                            <th style="width:140px;">Store Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $i)
                            @php
                                $name = $i->name ?? '—';
                                $grp = $i->group?->name ?? '—';
                                $reorder = (int)($i->reorder_level ?? 0);
                                $qty = (int)($i->qty_on_hand ?? 0);

                                // Severity: 0 is critical, otherwise low
                                $sevLabel = $qty <= 0 ? 'CRITICAL' : 'LOW';
                                $sevClass = $qty <= 0 ? 'danger' : '';
                            @endphp

                            <tr>
                                <td>
                                    <div style="font-weight:900;">{{ $name }}</div>
                                    <div class="inv-muted-sm">
                                        <span class="inv-sev {{ $sevClass }}">{{ $sevLabel }}</span>
                                    </div>
                                </td>

                                <td class="hide-xs">{{ $grp }}</td>

                                <td>
                                    <span class="inv-chip">{{ $reorder }}</span>
                                </td>

                                <td>
                                    <span class="badge {{ $qty <= 0 ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $qty }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-0">
                                    <div class="inv-empty">
                                        <div class="inv-empty-ico"></div>
                                        <p class="inv-empty-title mb-0">No low stock items</p>
                                        <div class="inv-empty-sub">
                                            Everything is above reorder level. Your store is behaving. 😄
                                        </div>
                                        <div class="mt-3">
                                            <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.items.index') }}" data-inv-loading>View items</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($items, 'links'))
                <div class="p-3">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
