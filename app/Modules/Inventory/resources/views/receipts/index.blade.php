@extends('inventory::layout')

@section('page-title', 'Stock Receipts')
@section('page-subtitle', 'Track what came into store, when it arrived, and who received it.')

@section('page-actions')
    <a class="btn btn-dark btn-sm" href="{{ route('inventory.receipts.create') }}" data-inv-loading>+ Receive Stock</a>
@endsection

@section('inventory-content')
@php
    $q = $q ?? request('q', '');
    $from = $from ?? request('from', '');
    $to = $to ?? request('to', '');
    $loading = request()->boolean('loading');
@endphp

<style>
    /* Receipts index (scoped) */
    .inv-ref{
        font-weight: 900;
    }
    .inv-muted-sm{
        font-size: 12px;
        color: var(--muted);
    }
    .inv-receipt-meta{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        margin-top: 4px;
    }
    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }

    @media (max-width: 575.98px){
        .hide-xs{ display:none; }
    }
</style>

<div class="inv-card inv-table-card">

    <div class="inv-table-head">
        <div>
            <p class="inv-table-title mb-0">Receipts</p>
            <div class="inv-table-sub">
                History of received stock entries
                @if(trim($q) !== '')
                    • Search: <span class="inv-chip">“{{ $q }}”</span>
                @endif
                @if($from || $to)
                    • Range:
                    <span class="inv-chip">{{ $from ?: '…' }}</span>
                    <span class="inv-chip">{{ $to ?: '…' }}</span>
                @endif
            </div>
        </div>

        <div class="inv-table-tools">
            <form class="d-flex gap-2 flex-wrap align-items-center" method="GET" action="{{ route('inventory.receipts.index') }}" data-inv-loading>
                <input
                    class="form-control form-control-sm"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search reference, supplier…"
                    style="min-width:220px;"
                >

                <input
                    class="form-control form-control-sm"
                    type="date"
                    name="from"
                    value="{{ $from }}"
                    title="From date"
                    style="min-width:150px;"
                >

                <input
                    class="form-control form-control-sm"
                    type="date"
                    name="to"
                    value="{{ $to }}"
                    title="To date"
                    style="min-width:150px;"
                >

                <button class="btn btn-sm btn-dark">Filter</button>

                @if(trim($q) !== '' || $from || $to)
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.receipts.index') }}" data-inv-loading>Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Optional skeleton preview: /inventory/receipts?loading=1 --}}
    <div class="inv-skel {{ $loading ? 'show' : '' }}">
        <div class="inv-skeleton inv-skel-row lg" style="width:260px;"></div>
        <div class="inv-skeleton inv-skel-row" style="width:420px;"></div>
        <div class="inv-divider"></div>

        @for($r=0;$r<6;$r++)
            <div class="d-flex gap-2 align-items-center mb-2">
                <div class="inv-skeleton" style="width:170px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton hide-xs" style="width:120px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="flex:1; height:14px; border-radius:10px;"></div>
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
                            <th style="width:260px;">Reference</th>
                            <th class="hide-xs" style="width:140px;">Date</th>
                            <th>Supplier</th>
                            <th style="width:220px;">Received By</th>
                            <th class="text-end" style="width:120px;">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receipts as $r)
                            @php
                                $ref = trim((string)($r->reference ?? ''));
                                $refShow = $ref !== '' ? $ref : '—';
                                $dateShow = $r->received_date?->format('Y-m-d') ?? '—';
                                $supplier = trim((string)($r->supplier_name ?? ''));
                                $supplierShow = $supplier !== '' ? $supplier : '—';
                                $receiver = $r->receiver?->name ?? '—';
                            @endphp

                            <tr>
                                <td>
                                    <div class="inv-ref">{{ $refShow }}</div>
                                    <div class="inv-receipt-meta">
                                        <span class="inv-chip">#{{ $r->id }}</span>
                                        <span class="inv-chip">Date: {{ $dateShow }}</span>
                                    </div>
                                </td>

                                <td class="hide-xs">{{ $dateShow }}</td>

                                <td>
                                    <div style="font-weight:800;">{{ $supplierShow }}</div>
                                    <div class="inv-muted-sm">
                                        {{ $supplierShow === '—' ? 'No supplier recorded.' : 'Supplier recorded for traceability.' }}
                                    </div>
                                </td>

                                <td>
                                    <div style="font-weight:800;">{{ $receiver }}</div>
                                    <div class="inv-muted-sm">
                                        {{ $r->created_at?->format('M j, Y') ?? '' }}
                                    </div>
                                </td>

                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.receipts.show', $r) }}" data-inv-loading>Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-0">
                                    <div class="inv-empty">
                                        <div class="inv-empty-ico">🧾</div>
                                        <p class="inv-empty-title mb-0">No receipts yet</p>
                                        <div class="inv-empty-sub">
                                            Receiving stock creates a receipt history and updates store quantities.
                                        </div>
                                        <div class="mt-3">
                                            <a class="btn btn-dark btn-sm" href="{{ route('inventory.receipts.create') }}" data-inv-loading>+ Receive first stock</a>
                                            <a class="btn btn-outline-secondary btn-sm ms-1" href="{{ route('inventory.items.index') }}" data-inv-loading>View items</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($receipts, 'links'))
                <div class="p-3">
                    {{ $receipts->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
