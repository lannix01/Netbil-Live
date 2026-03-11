@extends('pettycash::layouts.app')

@section('title','Bike Spendings')

@push('styles')
<style>
    .wrap{max-width:1100px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
    th{font-size:12px;color:#475467;text-align:left}
    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:700}
    input{border:1px solid #d0d5dd;padding:9px 10px;border-radius:10px}
    .row{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px}
</style>
@endpush

@section('content')
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <div>
            <h2 style="margin:0">Bike: {{ $bike->plate_no }}</h2>
            <div style="color:#667085;font-size:12px">Total in range: <span class="pill">{{ number_format($total, 2) }}</span></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn2" href="{{ route('petty.bikes.index') }}">Back</a>
            <a class="btn2" href="{{ route('petty.bikes.create', ['bike_id' => $bike->id]) }}">+ Add Expense</a>
        </div>
    </div>

    <div class="card">
        <div class="pc-filter-dock">
            <details class="pc-filter-panel" @if(filled($from) || filled($to)) open @endif>
                <summary>
                    <span class="pc-filter-title">Filters</span>
                    <span class="pc-filter-state">{{ filled($from) || filled($to) ? 'active' : 'optional' }}</span>
                </summary>
                <div class="pc-filter-body">
                    <form method="GET" class="row pc-filter-row" action="{{ route('petty.bikes.byBike', $bike->id) }}">
                        <div>
                            <div style="color:#667085;font-size:12px">From</div>
                            <input type="date" name="from" value="{{ $from }}">
                        </div>
                        <div>
                            <div style="color:#667085;font-size:12px">To</div>
                            <input type="date" name="to" value="{{ $to }}">
                        </div>
                        <button class="btn2" type="submit">Filter</button>
                        <a class="btn2" href="{{ route('petty.bikes.byBike', $bike->id) }}">Reset</a>
                    </form>
                </div>
            </details>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Subtype</th>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Particulars</th>
                    <th>Batch</th>
                </tr>
                </thead>
                <tbody>
                @forelse($spendings as $s)
                    <tr>
                        <td>{{ $s->date?->format('Y-m-d') }}</td>
                        <td><span class="pill">{{ strtoupper($s->sub_type ?? '-') }}</span></td>
                        <td>{{ $s->reference }}</td>
                        <td>{{ number_format((float)$s->amount, 2) }}</td>
                        <td>{{ $s->description }}</td>
                        <td>{{ $s->particulars }}</td>
                        <td>{{ $s->batch?->batch_no ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="color:#667085">No spendings for this item.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:12px;">{{ $spendings->onEachSide(1)->links('pettycash::partials.pagination') }}</div>
    </div>
</div>
@endsection
