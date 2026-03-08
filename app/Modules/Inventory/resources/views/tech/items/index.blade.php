@extends('inventory::layout')

@section('page-title', 'My Assigned Items')
@section('page-subtitle', 'Only items allocated to you. Deploy from here to a site.')

@section('page-actions')
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.logs.index') }}" data-inv-loading>Logs</a>
@endsection

@section('inventory-content')
@php
    $assignments = $assignments ?? collect();
    $loading = request()->boolean('loading');
@endphp

<style>
    /* Tech items index (scoped) */
    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }

    .inv-kpi{
        display:flex;
        gap: 8px;
        flex-wrap:wrap;
        align-items:center;
    }
    .inv-kpi .chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: #fff;
        font-weight: 900;
        font-size: 12px;
        white-space:nowrap;
    }
    .inv-kpi .chip span{ color: var(--muted); font-weight: 800; }

    @media (max-width: 575.98px){
        .hide-xs{ display:none; }
    }
</style>

<div class="inv-card inv-table-card">
    <div class="inv-table-head">
        <div>
            <p class="inv-table-title mb-0">Assigned items</p>
            <div class="inv-table-sub">Allocated vs deployed vs what’s still deployable.</div>
        </div>

        <div class="inv-table-tools">
            <div class="inv-kpi">
                <div class="chip">
                    {{ method_exists($assignments, 'total') ? $assignments->total() : (method_exists($assignments, 'count') ? $assignments->count() : 0) }}
                    <span>items</span>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.tech.items.index') }}" data-inv-loading>Refresh</a>
            </div>
        </div>
    </div>

    {{-- Optional skeleton preview: /inventory/tech/items?loading=1 --}}
    <div class="inv-skel {{ $loading ? 'show' : '' }}">
        <div class="inv-skeleton inv-skel-row lg" style="width:260px;"></div>
        <div class="inv-skeleton inv-skel-row" style="width:420px;"></div>
        <div class="inv-divider"></div>
        @for($r=0;$r<7;$r++)
            <div class="d-flex gap-2 align-items-center mb-2">
                <div class="inv-skeleton" style="width:260px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton hide-xs" style="width:160px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="width:100px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="width:100px; height:14px; border-radius:10px;"></div>
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
                            <th style="width:110px;">Allocated</th>
                            <th style="width:110px;">Deployed</th>
                            <th style="width:120px;">Available</th>
                            <th class="text-end" style="width:120px;">Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $a)
                            @php
                                $itemName = $a->item?->name ?? '—';
                                $groupName = $a->item?->group?->name ?? '—';
                                $alloc = (int)($a->qty_allocated ?? 0);
                                $dep = (int)($a->qty_deployed ?? 0);
                                $avail = method_exists($a, 'availableToDeploy') ? (int)$a->availableToDeploy() : max(0, $alloc - $dep);

                                $danger = $avail <= 0;
                            @endphp

                            <tr>
                                <td>
                                    <div style="font-weight:900;">{{ $itemName }}</div>
                                    <div class="inv-muted" style="font-size:12px;">
                                        {{ $a->item?->has_serial ? 'Serialized' : 'Bulk' }}
                                    </div>
                                </td>

                                <td class="hide-xs">{{ $groupName }}</td>

                                <td>{{ $alloc }}</td>
                                <td>{{ $dep }}</td>

                                <td>
                                    <span class="badge {{ $danger ? 'bg-secondary' : 'bg-dark' }}">{{ $avail }}</span>
                                </td>

                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.tech.items.show', $a->item_id) }}" data-inv-loading>
                                        Open
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-0">
                                    <div class="inv-empty">
                                        <div class="inv-empty-ico">🧰</div>
                                        <p class="inv-empty-title mb-0">No assigned items</p>
                                        <div class="inv-empty-sub">
                                            Ask an admin to assign stock to you. Once assigned, you’ll deploy items from here.
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($assignments, 'links'))
                <div class="p-3">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
