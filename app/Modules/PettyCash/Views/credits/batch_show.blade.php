@extends('pettycash::layouts.app')

@section('title', 'Batch ' . $batch->batch_no)

@push('styles')
<style>
    .wrap{max-width:1100px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .row{display:flex;gap:12px;flex-wrap:wrap}
    .mini{flex:1;min-width:220px}
    .k{font-size:12px;color:#667085}
    .v{font-size:18px;font-weight:800;margin-top:4px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
    th{font-size:12px;color:#475467;text-align:left}
    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:700}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px}
    .right{text-align:right}
    .warn{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:10px;border-radius:10px;margin-top:12px}
</style>
@endpush

@section('content')
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <div>
            <h2 style="margin:0">Batch: {{ $batch->batch_no }}</h2>
            <div style="color:#667085;font-size:12px">
                Batch-only view. In rollover mode, overflow is deducted from the next batch.
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn2" href="{{ route('petty.batches.index') }}">Back</a>
            <a class="btn2" href="{{ route('petty.credits.index') }}">Credits</a>
        </div>
    </div>

    @php
        $creditedNet = (float)($batch->credited_net ?? 0);
        $spentNet = (float)($batch->spent_net ?? 0);
        $effectiveSpent = (float)($effectiveSpent ?? 0);
        $overdraw = (float)($overdraw ?? 0);
    @endphp

    @if($overdraw > 0)
        <div class="warn">
            This batch was overdrawn by <strong>{{ number_format($overdraw, 2) }}</strong>.
            In rollover display, that overflow is deducted from the next batch.
        </div>
    @endif

    <div class="card row">
        <div class="mini">
            <div class="k">Credited (Net)</div>
            <div class="v">{{ number_format($creditedNet, 2) }}</div>
        </div>
        <div class="mini">
            <div class="k">Spent (Net) (clamped)</div>
            <div class="v">{{ number_format($effectiveSpent, 2) }}</div>
        </div>
        <div class="mini">
            <div class="k">Balance (Net)</div>
            <div class="v"><span class="pill">{{ number_format(max(0, $creditedNet - $spentNet), 2) }}</span></div>
        </div>
        <div class="mini">
            <div class="k">Created</div>
            <div class="v">{{ $batch->created_at?->format('Y-m-d') }}</div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin:0 0 6px">Credits in this Batch</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th class="right">Amount</th>
                    <th class="right">Fee</th>
                    <th class="right">Net In</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                @forelse($credits as $c)
                    @php
                        $amt = (float)$c->amount;
                        $fee = (float)($c->transaction_cost ?? 0);
                        $net = $amt - $fee;
                    @endphp
                    <tr>
                        <td>{{ $c->date?->format('Y-m-d') }}</td>
                        <td>{{ $c->reference ?? '-' }}</td>
                        <td class="right">{{ number_format($amt, 2) }}</td>
                        <td class="right">{{ number_format($fee, 2) }}</td>
                        <td class="right"><strong>{{ number_format($net, 2) }}</strong></td>
                        <td>{{ $c->description ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:#667085">No credits found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3 style="margin:0 0 6px">Spendings in this Batch</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Subtype</th>
                    <th>Reference</th>
                    <th class="right">Amount</th>
                    <th class="right">Fee</th>
                    <th class="right">Net Out</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tbody>
                @forelse($spendings as $s)
                    @php
                        $amt = (float)$s->amount;
                        $fee = (float)($s->transaction_cost ?? 0);
                        $net = $amt + $fee;
                    @endphp
                    <tr>
                        <td>{{ $s->date?->format('Y-m-d') }}</td>
                        <td>{{ strtoupper($s->type) }}</td>
                        <td>{{ $s->sub_type ? strtoupper($s->sub_type) : '-' }}</td>
                        <td>{{ $s->reference ?? '-' }}</td>
                        <td class="right">{{ number_format($amt, 2) }}</td>
                        <td class="right">{{ number_format($fee, 2) }}</td>
                        <td class="right"><strong>{{ number_format($net, 2) }}</strong></td>
                        <td>{{ $s->description ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="color:#667085">No spendings yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

