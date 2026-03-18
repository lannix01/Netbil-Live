@extends('inventory::layout')

@section('page-title', 'Inventory Logs')
@section('page-subtitle', 'Audit trail of receipts, assignments, deployments, transfers, and returns.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.movements.index') }}" data-inv-loading>Movements</a>
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.receipts.index') }}" data-inv-loading>Receipts</a>
@endsection

@section('inventory-content')
@php
    $logs = $logs ?? collect();

    // Keep existing controller-provided values; fallback safely
    $q = $q ?? request('q', '');
    $action = $action ?? request('action', '');

    // Optional skeleton preview: /inventory/logs?loading=1
    $loading = request()->boolean('loading');

    $badgeClass = function($a){
        $a = (string)$a;
        return match($a){
            'received' => 'success',
            'assigned' => 'primary',
            'deployed' => 'dark',
            'transfer' => 'info',
            'return_to_store' => 'secondary',
            default => 'secondary',
        };
    };

    $prettyAction = function($a){
        $a = (string)$a;
        if($a === '') return '—';
        return strtoupper(str_replace('_', ' ', $a));
    };
@endphp

<style>
    /* Logs index (scoped) */
    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }

    .inv-mono{
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
        font-size: 12px;
    }
    .inv-notes{
        max-width: 280px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--muted);
        font-size: 12px;
    }

    /* Responsive hiding */
    @media (max-width: 1199.98px){
        .hide-lg{ display:none; }
    }
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
            <p class="inv-table-title mb-0">Audit Logs</p>
            <div class="inv-table-sub">
                Search by serial, site, reference, item, or user.
                @if($action !== '')
                    • Action: <span class="inv-chip">{{ strtoupper($action) }}</span>
                @endif
                @if(trim($q) !== '')
                    • Search: <span class="inv-chip">“{{ $q }}”</span>
                @endif
            </div>
        </div>

        <div class="inv-table-tools">
            <form class="d-flex gap-2 flex-wrap align-items-center" method="GET" action="{{ route('inventory.logs.index') }}" data-inv-loading data-inv-autofilter>
                <select class="form-select form-select-sm" name="action" style="min-width: 170px;">
                    <option value="" @selected($action==='')>All Actions</option>
                    <option value="received" @selected($action==='received')>Received</option>
                    <option value="assigned" @selected($action==='assigned')>Assigned</option>
                    <option value="deployed" @selected($action==='deployed')>Deployed</option>
                    <option value="transfer" @selected($action==='transfer')>Transfer</option>
                    <option value="return_to_store" @selected($action==='return_to_store')>Return</option>
                </select>

                <input
                    class="form-control form-control-sm"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search serial, site, ref, item, user..."
                    style="min-width: 260px;"
                >

                <button class="btn btn-sm btn-dark">Filter</button>

                @if($q !== '' || $action !== '')
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.logs.index') }}" data-inv-loading>Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Optional skeleton preview --}}
    <div class="inv-skel {{ $loading ? 'show' : '' }}">
        <div class="inv-skeleton inv-skel-row lg" style="width:240px;"></div>
        <div class="inv-skeleton inv-skel-row" style="width:460px;"></div>
        <div class="inv-divider"></div>

        @for($r=0;$r<8;$r++)
            <div class="d-flex gap-2 align-items-center mb-2">
                <div class="inv-skeleton" style="width:140px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="width:110px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="flex:1; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton hide-md" style="width:140px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton hide-lg" style="width:220px; height:14px; border-radius:10px;"></div>
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
                            <th style="width:140px;">Action</th>
                            <th style="width:260px;">Item</th>
                            <th style="width:160px;">Serial</th>
                            <th style="width:100px;">Qty</th>
                            <th class="hide-md" style="width:200px;">From</th>
                            <th class="hide-md" style="width:200px;">To</th>
                            <th class="hide-lg" style="width:240px;">Site</th>
                            <th class="hide-lg" style="width:200px;">Ref</th>
                            <th class="hide-xs" style="width:200px;">Logged By</th>
                            <th class="hide-lg">Notes</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $dt = $log->created_at?->format('Y-m-d H:i') ?? '—';
                                $act = (string)($log->action ?? '');
                                $actLabel = $prettyAction($act);
                                $actCls = $badgeClass($act);

                                $itemName = $log->item?->name ?? '—';
                                $itemSku = $log->item?->sku ?? '';

                                $serial = trim((string)($log->serial_no ?? ''));
                                $qty = $log->qty;

                                $fromName = $log->fromUser?->name ?? '—';
                                $toName = $log->toUser?->name ?? '—';

                                $siteName = trim((string)($log->site_name ?? ''));
                                $siteCode = trim((string)($log->site_code ?? ''));

                                $ref = trim((string)($log->reference ?? ''));
                                $refShow = $ref !== '' ? $ref : '—';

                                $by = $log->creator?->name ?? '—';

                                $notes = trim((string)($log->notes ?? ''));
                                $notesShow = $notes !== '' ? $notes : '—';
                            @endphp

                            <tr>
                                <td class="text-muted" style="white-space:nowrap;">{{ $dt }}</td>

                                <td>
                                    <span class="badge bg-{{ $actCls }}">{{ $actLabel }}</span>
                                </td>

                                <td>
                                    <div style="font-weight:900;">{{ $itemName }}</div>
                                    <div class="inv-mono text-muted">{{ $itemSku !== '' ? $itemSku : '—' }}</div>
                                </td>

                                <td>
                                    @if($serial !== '')
                                        <span class="badge bg-info inv-mono">{{ $serial }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if(!is_null($qty))
                                        <span class="badge bg-secondary">{{ $qty }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="hide-md">{{ $fromName }}</td>
                                <td class="hide-md">{{ $toName }}</td>

                                <td class="hide-lg">
                                    @if($siteName !== '' || $siteCode !== '')
                                        <div style="font-weight:900;">{{ $siteName !== '' ? $siteName : '—' }}</div>
                                        <div class="text-muted" style="font-size:12px;">{{ $siteCode !== '' ? $siteCode : '—' }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="hide-lg">
                                    <span class="inv-chip">{{ $refShow }}</span>
                                </td>

                                <td class="hide-xs">{{ $by }}</td>

                                <td class="hide-lg">
                                    <span class="inv-notes" title="{{ $notesShow }}">{{ $notesShow }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="p-0">
                                    <div class="inv-empty">
                                        <div class="inv-empty-ico">🧾</div>
                                        <p class="inv-empty-title mb-0">No logs yet</p>
                                        <div class="inv-empty-sub">
                                            Logs will appear as you receive stock, assign to technicians/teams, deploy to sites, transfer, and return.
                                        </div>
                                        <div class="mt-3">
                                            <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.receipts.create') }}" data-inv-loading>Receive stock</a>
                                            <a class="btn btn-outline-secondary btn-sm ms-1" href="{{ route('inventory.assignments.index') }}" data-inv-loading>Assign to tech</a>
                                            <a class="btn btn-outline-secondary btn-sm ms-1" href="{{ route('inventory.movements.index') }}" data-inv-loading>Movements</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($logs, 'links'))
                <div class="p-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
