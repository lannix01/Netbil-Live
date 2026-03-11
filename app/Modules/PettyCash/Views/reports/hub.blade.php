@extends('pettycash::layouts.app')

@section('title','Reports')

@push('styles')
<style>
    .wrap{max-width:1200px;margin:0 auto}

    .top{
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:10px;
        flex-wrap:wrap;
    }

    .h{font-size:18px;font-weight:900;margin:0}
    .sub{font-size:12px;color:#667085;margin-top:4px}

    .card{
        background:#fff;
        border:1px solid #e7e9f2;
        border-radius:14px;
        padding:16px;
        box-shadow:0 8px 30px rgba(16,24,40,.06);
        margin-top:12px;
    }

    .btn{
        display:inline-block;
        padding:10px 14px;
        border-radius:10px;
        background:#7f56d9;
        color:#fff;
        text-decoration:none;
        font-weight:900;
        border:none;
        cursor:pointer;
    }
    .btn2{
        display:inline-block;
        padding:10px 14px;
        border-radius:10px;
        border:1px solid #d0d5dd;
        background:#fff;
        color:#344054;
        text-decoration:none;
        font-weight:900;
    }

    .muted{color:#667085;font-size:12px}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px}

    .row{display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-top:10px}
    input,select{border:1px solid #d0d5dd;padding:9px 10px;border-radius:10px}

    .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:12px}
    @media(max-width:1000px){.grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:680px){.grid{grid-template-columns:1fr}}

    .title{font-weight:900;margin:0 0 6px}
    .desc{font-size:12px;color:#667085;margin:0 0 10px}
    .divider{height:1px;background:#eef2f6;margin:14px 0}
</style>
@endpush

@section('content')
<div class="wrap">

    <div class="top">
        <div>
            <div class="h">Reports</div>
            <div class="sub">Your board-ready report lives here. Filters + quick exports too.</div>
        </div>
    </div>

    {{-- 1) Board report CTA --}}
    <div class="card">
        <div class="title">Board Report (Main)</div>
        <div class="desc">
            Credits first → Debits by category → Totals + Balance → Analysis/Chart page.
            Uses MPESA reference codes as your “Ref”.
        </div>
        <a class="btn" href="{{ route('petty.reports.general.form') }}">Build & Print Board Report</a>
    </div>

    {{-- 2) Quick Filters (used for quick exports below) --}}
    <div class="card">
        <div class="title">Quick Filters</div>
        <div class="desc">These apply to quick exports (Credits/Bikes/Meals/Others). Board report uses its own builder.</div>

        <div class="pc-filter-dock">
            <details class="pc-filter-panel" @if(filled($from) || filled($to) || filled($batchId)) open @endif>
                <summary>
                    <span class="pc-filter-title">Filters</span>
                    <span class="pc-filter-state">{{ filled($from) || filled($to) || filled($batchId) ? 'active' : 'optional' }}</span>
                </summary>
                <div class="pc-filter-body">
                    <form method="GET" action="{{ route('petty.reports.index') }}" class="row pc-filter-row">
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
                                    <option value="{{ $b->id }}" @selected((string)$batchId === (string)$b->id)>{{ $b->batch_no }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button class="btn" type="submit">Apply</button>
                        <a class="btn2" href="{{ route('petty.reports.index') }}">Reset</a>

                        <div class="muted" style="flex-basis:100%">
                            Current:
                            <span class="pill">{{ $from ?: 'All' }}</span> → <span class="pill">{{ $to ?: 'All' }}</span>
                            @if($batchId) <span class="pill">Batch {{ $batchId }}</span> @endif
                        </div>
                    </form>
                </div>
            </details>
        </div>

        <div class="divider"></div>

        <div class="title">Quick Exports (Optional)</div>
        <div class="desc">Convenience exports. The board report above also supports PDF/CSV/Excel.</div>

        <div class="grid">
            <div class="card" style="margin-top:0">
                <div class="title">Credits Export</div>
                <p class="desc">All credits (supports date filter).</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @include('pettycash::partials.export_select', [
                        'options' => [
                            'PDF' => route('petty.credits.pdf', ['from'=>$from,'to'=>$to,'format'=>'pdf']),
                            'CSV' => route('petty.credits.pdf', ['from'=>$from,'to'=>$to,'format'=>'csv']),
                            'Excel' => route('petty.credits.pdf', ['from'=>$from,'to'=>$to,'format'=>'excel']),
                        ],
                    ])
                </div>
            </div>

            <div class="card" style="margin-top:0">
                <div class="title">Bikes Spendings Export</div>
                <p class="desc">Fuel + maintenance (supports date + batch filter).</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @include('pettycash::partials.export_select', [
                        'options' => [
                            'PDF' => route('petty.bikes.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'pdf']),
                            'CSV' => route('petty.bikes.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'csv']),
                            'Excel' => route('petty.bikes.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'excel']),
                        ],
                    ])
                </div>
            </div>

            <div class="card" style="margin-top:0">
                <div class="title">Meals (Lunch) Export</div>
                <p class="desc">Lunch spendings (supports date + batch filter).</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @include('pettycash::partials.export_select', [
                        'options' => [
                            'PDF' => route('petty.meals.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'pdf']),
                            'CSV' => route('petty.meals.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'csv']),
                            'Excel' => route('petty.meals.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'excel']),
                        ],
                    ])
                </div>
            </div>

            <div class="card" style="margin-top:0">
                <div class="title">Token Hostels Export</div>
                <p class="desc">Hostels summary (last payment tags included).</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @include('pettycash::partials.export_select', [
                        'options' => [
                            'PDF' => route('petty.tokens.pdf', ['format'=>'pdf']),
                            'CSV' => route('petty.tokens.pdf', ['format'=>'csv']),
                            'Excel' => route('petty.tokens.pdf', ['format'=>'excel']),
                        ],
                    ])
                </div>
            </div>

            <div class="card" style="margin-top:0">
                <div class="title">Others Export</div>
                <p class="desc">Other spendings (supports date + batch filter).</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @include('pettycash::partials.export_select', [
                        'options' => [
                            'PDF' => route('petty.others.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'pdf']),
                            'CSV' => route('petty.others.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'csv']),
                            'Excel' => route('petty.others.pdf', ['from'=>$from,'to'=>$to,'batch_id'=>$batchId,'format'=>'excel']),
                        ],
                    ])
                </div>
            </div>

            <div class="card" style="margin-top:0">
                <div class="title">Token Hostel Payments Export</div>
                <p class="desc">Pick a hostel → export full payment history.</p>

                <select id="hostelSelect" style="width:100%;margin:0 0 10px;">
                    <option value="">Select hostel</option>
                    @foreach($hostels as $h)
                        <option value="{{ $h->id }}">{{ $h->hostel_name }}</option>
                    @endforeach
                </select>

                <label style="display:inline-flex;align-items:center;gap:8px">
                    <span class="muted">Export</span>
                    <select onchange="if(this.value){downloadHostelPayments(this.value);this.selectedIndex=0;}">
                        <option value="">Select format</option>
                        <option value="pdf">PDF</option>
                        <option value="csv">CSV</option>
                        <option value="excel">Excel</option>
                    </select>
                </label>
            </div>
        </div>
    </div>

</div>
<script>
function downloadHostelPayments(format) {
    const id = document.getElementById('hostelSelect').value;
    if (!id) {
        alert('Select a hostel first');
        return false;
    }

    window.location.href = '{{ url('/pettycash/spendings/tokens/hostels') }}/' + id + '/pdf?format=' + encodeURIComponent(format);
    return false;
}
</script>
@endsection
