@extends('pettycash::layouts.app')

@section('title','Others')

@push('styles')
<style>
    .wrap{max-width:1100px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
    th{font-size:12px;color:#475467;text-align:left;white-space:nowrap}
    .btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:700;border:none;cursor:pointer}
    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:700}
    input,select{border:1px solid #d0d5dd;padding:9px 10px;border-radius:10px}
    .muted{color:#667085;font-size:12px}
    .row{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px}
    .success{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px;border-radius:10px;margin-top:12px}
    .num{text-align:right;white-space:nowrap}
</style>
@endpush

@section('content')
@php
    $canEditOther = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'others.edit');
@endphp
<div class="wrap">
    <div class="top">
        <div>
            <h2 style="margin:0">Others</h2>
            <div class="muted">
                Net total in range (amount + fees):
                <span class="pill">{{ number_format($total, 2) }}</span>
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn" href="{{ route('petty.others.create') }}">+ New Other</a>
            @include('pettycash::partials.export_select', [
                'options' => [
                    'PDF' => route('petty.others.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'pdf']),
                    'CSV' => route('petty.others.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'csv']),
                    'Excel' => route('petty.others.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'excel']),
                ],
            ])
        </div>
    </div>

    <div class="card">
        <div class="pc-filter-dock">
            <details class="pc-filter-panel" @if(filled($from) || filled($to) || filled($batchId)) open @endif>
                <summary>
                    <span class="pc-filter-title">Filters</span>
                    <span class="pc-filter-state">{{ filled($from) || filled($to) || filled($batchId) ? 'active' : 'optional' }}</span>
                </summary>
                <div class="pc-filter-body">
                    <form method="GET" class="row pc-filter-row" action="{{ route('petty.others.index') }}">
                        <div>
                            <div class="muted">From</div>
                            <input type="date" name="from" value="{{ $from }}">
                        </div>
                        <div>
                            <div class="muted">To</div>
                            <input type="date" name="to" value="{{ $to }}">
                        </div>
                        <div>
                            <div class="muted">Batch</div>
                            <select name="batch_id">
                                <option value="">All</option>
                                @foreach($batches as $b)
                                    <option value="{{ $b->id }}" @selected((string)$batchId === (string)$b->id)>
                                        {{ $b->batch_no }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button class="btn" type="submit">Filter</button>
                        <a class="btn2" href="{{ route('petty.others.index') }}">Reset</a>
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
                    <th>Description</th>
                    <th class="num">Amount</th>
                    <th class="num">Fee</th>
                    <th class="num">Total</th>
                    <th>Respondent</th>
                    <th>Batch</th>
                    @if($canEditOther)
                        <th>Actions</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse($others as $o)
                    @php
                        $amt = (float)$o->amount;
                        $fee = (float)($o->transaction_cost ?? 0);
                    @endphp
                    <tr>
                        <td>{{ $o->date?->format('Y-m-d') }}</td>
                        <td>{{ $o->reference ?? '-' }}</td>
                        <td>{{ $o->description ?? '-' }}</td>
                        <td class="num">{{ number_format($amt, 2) }}</td>
                        <td class="num">{{ number_format($fee, 2) }}</td>
                        <td class="num"><strong>{{ number_format($amt + $fee, 2) }}</strong></td>
                        <td>{{ $o->respondent?->name ?? '-' }}</td>
                        <td>{{ $o->batch?->batch_no ?? ($o->batch_id ?? '-') }}</td>
                        @if($canEditOther)
                            <td><a href="{{ route('petty.others.edit', $o->id) }}">Edit</a></td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canEditOther ? 9 : 8 }}" class="muted">No other spendings yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:12px;">{{ $others->onEachSide(1)->links('pettycash::partials.pagination') }}</div>
    </div>
</div>
@endsection
