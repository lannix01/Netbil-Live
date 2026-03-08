@extends('inventory::layout')

@section('page-title', 'Movements')
@section('page-subtitle', 'Transfers and returns between technicians and store, with full line-level traceability.')

@section('page-actions')
    <a class="btn btn-outline-primary btn-sm" href="{{ route('inventory.movements.transfer') }}" data-inv-loading>Transfer Tech ➜ Tech</a>
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.movements.return_to_store') }}" data-inv-loading>Return Tech ➜ Store</a>
@endsection

@section('inventory-content')
@php
    $movements = $movements ?? collect();

    // Safe filters (won’t break if controller ignores them)
    $q = $q ?? request('q', '');
    $type = $type ?? request('type', 'all'); // transfer / return_to_store / all (or whatever you use)
    $from = $from ?? request('from', '');
    $to = $to ?? request('to', '');

    $loading = request()->boolean('loading');

    $fmtType = function($t){
        $t = (string)$t;
        if($t === '') return '—';
        return str_replace('_',' ', strtoupper($t));
    };

    $typeBadge = function($t){
        $t = (string)$t;
        if(str_contains($t, 'transfer')) return 'primary';
        if(str_contains($t, 'return')) return 'dark';
        return 'secondary';
    };
@endphp

<style>
    /* Movements (scoped) */
    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }

    .inv-ref{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #0b1220;
        color:#fff;
        font-weight:900;
        font-size: 12px;
        letter-spacing:.02em;
        white-space:nowrap;
    }

    .inv-site-name{ font-weight:900; }
    .inv-site-code{ font-size:12px; color: var(--muted); margin-top:4px; }

    .inv-lines{
        display:flex;
        flex-direction:column;
        gap:6px;
        min-width: 240px;
    }
    .inv-line{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        padding: 8px 10px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
    }
    .inv-line .name{
        font-weight:900;
        font-size: 13px;
        line-height: 1.2;
    }
    .inv-line .meta{
        font-size:12px;
        color: var(--muted);
        margin-top: 3px;
    }
    .inv-line .pill{
        white-space:nowrap;
        font-weight:900;
        border-radius: 999px;
        padding: 6px 10px;
        border: 1px solid var(--border);
        background: #f8fafc;
        font-size: 12px;
    }

    .inv-person{
        font-weight:900;
    }
    .inv-person-sub{
        font-size:12px;
        color: var(--muted);
        margin-top:4px;
    }

    /* Responsive: hide heavy columns on small screens */
    @media (max-width: 991.98px){
        .hide-md{ display:none; }
        .inv-lines{ min-width: 200px; }
    }
    @media (max-width: 575.98px){
        .hide-xs{ display:none; }
        .inv-lines{ min-width: 180px; }
    }
</style>

<div class="inv-card inv-table-card">

    <div class="inv-table-head">
        <div>
            <p class="inv-table-title mb-0">Movement History</p>
            <div class="inv-table-sub">
                Transfers + returns with per-line details.
                @if(trim($q) !== '')
                    • Search: <span class="inv-chip">“{{ $q }}”</span>
                @endif
                @if($type !== 'all')
                    • Type: <span class="inv-chip">{{ $fmtType($type) }}</span>
                @endif
                @if($from || $to)
                    • Range:
                    <span class="inv-chip">{{ $from ?: '…' }}</span>
                    <span class="inv-chip">{{ $to ?: '…' }}</span>
                @endif
            </div>
        </div>

        <div class="inv-table-tools">
            <form class="d-flex gap-2 flex-wrap align-items-center" method="GET" action="{{ route('inventory.movements.index') }}" data-inv-loading>
                <input
                    class="form-control form-control-sm"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search ref, site, user, item…"
                    style="min-width:220px;"
                >

                <select class="form-select form-select-sm" name="type" style="min-width:170px;">
                    <option value="all" @selected($type==='all')>Type: Any</option>
                    <option value="transfer" @selected($type==='transfer')>Transfer</option>
                    <option value="return_to_store" @selected($type==='return_to_store')>Return to Store</option>
                </select>

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

                <button class="btn btn-sm btn-dark">Filter</button>

                @if(trim($q) !== '' || $type !== 'all' || $from || $to)
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.movements.index') }}" data-inv-loading>Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Optional skeleton preview: /inventory/movements?loading=1 --}}
    <div class="inv-skel {{ $loading ? 'show' : '' }}">
        <div class="inv-skeleton inv-skel-row lg" style="width:260px;"></div>
        <div class="inv-skeleton inv-skel-row" style="width:420px;"></div>
        <div class="inv-divider"></div>

        @for($r=0;$r<6;$r++)
            <div class="d-flex gap-2 align-items-center mb-2">
                <div class="inv-skeleton" style="width:150px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="width:120px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton hide-md" style="width:160px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="flex:1; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton hide-xs" style="width:140px; height:14px; border-radius:10px;"></div>
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
                            <th style="width:190px;">Ref</th>
                            <th style="width:160px;">Type</th>
                            <th style="width:200px;">From</th>
                            <th style="width:200px;">To</th>
                            <th class="hide-md" style="width:220px;">Site</th>
                            <th>Lines</th>
                            <th class="hide-xs" style="width:200px;">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $m)
                            @php
                                $dt = $m->movement_at?->format('Y-m-d H:i') ?? '—';
                                $ref = trim((string)($m->reference ?? ''));
                                $refShow = $ref !== '' ? $ref : ('MOV-#'.$m->id);

                                $t = (string)($m->type ?? '');
                                $tLabel = $fmtType($t);
                                $tClass = $typeBadge($t);

                                $fromName = $m->fromUser?->name ?? '—';
                                $toName = $m->toUser?->name ?? '—';

                                $siteName = trim((string)($m->site_name ?? ''));
                                $siteCode = trim((string)($m->site_code ?? ''));

                                $by = $m->creator?->name ?? '—';
                                $lines = $m->lines ?? collect();
                            @endphp

                            <tr>
                                <td class="text-muted" style="white-space:nowrap;">{{ $dt }}</td>

                                <td>
                                    <span class="inv-ref">{{ $refShow }}</span>
                                    <div class="inv-person-sub">#{{ $m->id }}</div>
                                </td>

                                <td>
                                    <span class="badge bg-{{ $tClass }}">{{ $tLabel }}</span>
                                </td>

                                <td>
                                    <div class="inv-person">{{ $fromName }}</div>
                                    <div class="inv-person-sub">Source</div>
                                </td>

                                <td>
                                    <div class="inv-person">{{ $toName }}</div>
                                    <div class="inv-person-sub">Destination</div>
                                </td>

                                <td class="hide-md">
                                    @if($siteName !== '' || $siteCode !== '')
                                        <div class="inv-site-name">{{ $siteName !== '' ? $siteName : '—' }}</div>
                                        <div class="inv-site-code">{{ $siteCode !== '' ? $siteCode : '—' }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if(method_exists($lines, 'count') && $lines->count() === 0)
                                        <span class="inv-muted">No lines</span>
                                    @else
                                        <div class="inv-lines">
                                            @foreach($lines as $l)
                                                @php
                                                    $itemName = $l->item?->name ?? '—';
                                                    $serial = trim((string)($l->serial_no ?? ''));
                                                    $qty = $l->qty;
                                                @endphp

                                                <div class="inv-line">
                                                    <div>
                                                        <div class="name">{{ $itemName }}</div>
                                                        <div class="meta">
                                                            @if($serial !== '')
                                                                Serial tracked
                                                            @elseif(!is_null($qty))
                                                                Bulk qty movement
                                                            @else
                                                                —
                                                            @endif
                                                        </div>
                                                    </div>

                                                    @if($serial !== '')
                                                        <span class="badge bg-info">{{ $serial }}</span>
                                                    @elseif(!is_null($qty))
                                                        <span class="pill">{{ $qty }}</span>
                                                    @else
                                                        <span class="pill">—</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>

                                <td class="hide-xs">{{ $by }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-0">
                                    <div class="inv-empty">
                                        <div class="inv-empty-ico">🔁</div>
                                        <p class="inv-empty-title mb-0">No movements yet</p>
                                        <div class="inv-empty-sub">
                                            Movements appear here when technicians transfer items or return items to store.
                                        </div>
                                        <div class="mt-3">
                                            <a class="btn btn-outline-primary btn-sm" href="{{ route('inventory.movements.transfer') }}" data-inv-loading>Transfer Tech ➜ Tech</a>
                                            <a class="btn btn-outline-dark btn-sm ms-1" href="{{ route('inventory.movements.return_to_store') }}" data-inv-loading>Return Tech ➜ Store</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($movements, 'links'))
                <div class="p-3">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
