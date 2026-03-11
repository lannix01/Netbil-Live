@extends('pettycash::layouts.app')

@section('title','Credits')

@push('styles')
<style>
    .wrap{max-width:1100px;margin:0 auto}
    .top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
    th{font-size:12px;color:#475467;text-align:left}
    .btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:700;border:none;cursor:pointer}
    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:700}
    input{border:1px solid #d0d5dd;padding:9px 10px;border-radius:10px}
    .muted{color:#667085;font-size:12px}
    .row{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px}
</style>
@endpush

@section('content')
@php
    $canEditCredit = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'credits.edit');
@endphp
<div class="wrap">
    <div class="top">
        <div>
            <h2 style="margin:0">Credits</h2>
            <div class="muted">Total credited in range: <span class="pill">{{ number_format($total, 2) }}</span></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn" href="{{ route('petty.credits.create') }}">+ New Credit</a>
            @include('pettycash::partials.export_select', [
                'options' => [
                    'PDF' => route('petty.credits.pdf', ['from' => $from, 'to' => $to, 'format' => 'pdf']),
                    'CSV' => route('petty.credits.pdf', ['from' => $from, 'to' => $to, 'format' => 'csv']),
                    'Excel' => route('petty.credits.pdf', ['from' => $from, 'to' => $to, 'format' => 'excel']),
                ],
            ])
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
                    <form method="GET" class="row pc-filter-row" action="{{ route('petty.credits.index') }}">
                        <div>
                            <div class="muted">From</div>
                            <input type="date" name="from" value="{{ $from }}">
                        </div>
                        <div>
                            <div class="muted">To</div>
                            <input type="date" name="to" value="{{ $to }}">
                        </div>
                        <button class="btn" type="submit">Filter</button>
                        <a class="btn2" href="{{ route('petty.credits.index') }}">Reset</a>
                    </form>
                </div>
            </details>
        </div>

        <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>MPESA Ref</th>
                <th>Amount</th>
                <th>Batch</th>
                <th>Description</th>
                @if($canEditCredit)
                    <th>Actions</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse($credits as $c)
                <tr>
                    <td>{{ $c->date?->format('Y-m-d') }}</td>
                    <td>{{ $c->reference }}</td>
                    <td>{{ number_format((float)$c->amount, 2) }}</td>
                    <td>
                        <a href="{{ route('petty.batches.show', $c->batch_id) }}">{{ $c->batch?->batch_no ?? ('Batch #'.$c->batch_id) }}</a>
                    </td>
                    <td>{{ $c->description }}</td>
                    @if($canEditCredit)
                        <td><a href="{{ route('petty.credits.edit', $c->id) }}">Edit</a></td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $canEditCredit ? 6 : 5 }}" class="muted">No credits yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>

        <div style="margin-top:12px;">
            {{ $credits->onEachSide(1)->links('pettycash::partials.pagination') }}
        </div>
    </div>
</div>
@endsection
