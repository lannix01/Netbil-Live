@extends('inventory::layout')

@section('page-title', 'Receipt Details')
@section('page-subtitle', 'View receipt header info and the items received into store.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.receipts.index') }}" data-inv-loading>Back</a>
@endsection

@section('inventory-content')
@php
    $ref = trim((string)($receipt->reference ?? ''));
    $refShow = $ref !== '' ? $ref : 'Receipt #'.$receipt->id;

    $dateShow = $receipt->received_date?->format('Y-m-d') ?? '—';
    $supplier = trim((string)($receipt->supplier_name ?? ''));
    $supplierShow = $supplier !== '' ? $supplier : '—';

    $receiver = $receipt->receiver?->name ?? '—';
    $notes = trim((string)($receipt->notes ?? ''));

    $lines = $receipt->lines ?? collect();
    $lineCount = method_exists($lines, 'count') ? $lines->count() : 0;

    $totalQty = 0;
    foreach($lines as $l){
        $totalQty += (int)($l->qty_received ?? 0);
    }
@endphp

<style>
    /* Receipt show (scoped) */
    .inv-receipt-head{
        padding: 16px !important;
    }
    .inv-receipt-title{
        font-weight: 900;
        font-size: 16px;
        margin:0;
        letter-spacing:.2px;
    }
    .inv-receipt-sub{
        color: var(--muted);
        font-size: 12px;
        margin-top: 4px;
    }

    .inv-meta-grid{
        display:grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-top: 14px;
    }
    @media (max-width: 1199.98px){ .inv-meta-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575.98px){ .inv-meta-grid{ grid-template-columns: 1fr; } }

    .inv-meta{
        border: 1px solid var(--border);
        background: #fff;
        border-radius: 14px;
        padding: 12px;
    }
    .inv-meta .k{ font-size:12px; color: var(--muted); font-weight:900; letter-spacing:.08em; text-transform:uppercase; }
    .inv-meta .v{ margin-top:6px; font-weight: 900; }
    .inv-meta .s{ margin-top:4px; font-size:12px; color: var(--muted); }

    .inv-notes{
        padding: 14px 16px;
        border-top: 1px solid var(--border);
        background: #fff;
    }

    .inv-lines-card{ overflow:hidden; }
    .inv-lines-head{
        padding: 14px 16px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        border-bottom: 1px solid var(--border);
        background: #fbfcff;
        flex-wrap:wrap;
    }
    .inv-lines-title{ font-weight: 900; margin:0; font-size: 14px; }
    .inv-lines-sub{ color: var(--muted); font-size: 12px; margin-top: 3px; }

    .inv-lines-tools{
        display:flex;
        gap: 8px;
        flex-wrap:wrap;
        align-items:center;
        justify-content:flex-end;
    }

    .inv-qty-badge{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width: 54px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: #f8fafc;
        font-weight: 900;
    }

    .inv-row-muted{ font-size:12px; color: var(--muted); margin-top:4px; }
</style>

<div class="inv-card inv-receipt-head">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
            <h3 class="inv-receipt-title mb-0">{{ $refShow }}</h3>
            <div class="inv-receipt-sub">
                Receipt ID: <span class="inv-chip">#{{ $receipt->id }}</span>
                <span class="inv-chip">Received: {{ $dateShow }}</span>
                <span class="inv-chip">Lines: {{ $lineCount }}</span>
                <span class="inv-chip">Total Qty: {{ $totalQty }}</span>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            {{-- Placeholder actions (safe, no route changes) --}}
            <button class="btn btn-sm btn-outline-dark" type="button" onclick="window.print()">Print</button>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.receipts.index') }}" data-inv-loading>Back</a>
        </div>
    </div>

    <div class="inv-meta-grid">
        <div class="inv-meta">
            <div class="k">Received Date</div>
            <div class="v">{{ $dateShow }}</div>
            <div class="s">Date stock was captured into store.</div>
        </div>

        <div class="inv-meta">
            <div class="k">Supplier</div>
            <div class="v">{{ $supplierShow }}</div>
            <div class="s">{{ $supplierShow === '—' ? 'No supplier recorded.' : 'Recorded for traceability.' }}</div>
        </div>

        <div class="inv-meta">
            <div class="k">Received By</div>
            <div class="v">{{ $receiver }}</div>
            <div class="s">{{ $receipt->created_at?->format('M j, Y') ?? '' }}</div>
        </div>

        <div class="inv-meta">
            <div class="k">Reference</div>
            <div class="v">{{ trim((string)($receipt->reference ?? '')) !== '' ? $receipt->reference : '—' }}</div>
            <div class="s">PO / Invoice / Delivery note (optional).</div>
        </div>
    </div>

    <div class="inv-notes">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <div style="font-weight:900;">Notes</div>
            <div class="inv-muted" style="font-size:12px;">Optional</div>
        </div>

        @if($notes !== '')
            <div class="inv-muted" style="margin-top:6px;">{{ $notes }}</div>
        @else
            <div class="inv-muted" style="margin-top:6px;">No notes were recorded for this receipt.</div>
        @endif
    </div>
</div>

<div class="inv-card inv-lines-card mt-3">
    <div class="inv-lines-head">
        <div>
            <p class="inv-lines-title mb-0">Receipt lines</p>
            <div class="inv-lines-sub">Items and quantities received.</div>
        </div>

        <div class="inv-lines-tools">
            <span class="inv-chip">Lines: {{ $lineCount }}</span>
            <span class="inv-chip">Total Qty: {{ $totalQty }}</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle table-hover mb-0">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="width:140px;">Qty</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $l)
                    @php
                        $itemName = $l->item?->name ?? '—';
                        $unit = $l->item?->unit ?? '';
                        $qty = (int)($l->qty_received ?? 0);
                    @endphp

                    <tr>
                        <td>
                            <div style="font-weight:900;">{{ $itemName }}</div>
                            <div class="inv-row-muted">
                                @if($unit !== '')
                                    Unit: <span class="inv-chip">{{ $unit }}</span>
                                @else
                                    Unit: <span class="inv-chip">—</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="inv-qty-badge">{{ $qty }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="p-0">
                            <div class="inv-empty">
                                <div class="inv-empty-ico">🧾</div>
                                <p class="inv-empty-title mb-0">No lines found</p>
                                <div class="inv-empty-sub">
                                    This receipt has no line items recorded. If this is unexpected, check how it was created.
                                </div>
                                <div class="mt-3">
                                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.receipts.index') }}" data-inv-loading>Back to receipts</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
