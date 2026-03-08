@extends('pettycash::layouts.app')
@section('title','Spendings Ledger')

@push('styles')
<style>
    .wrap{max-width:1200px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .row{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
    .muted{color:#667085;font-size:12px}
    .btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:800;border:none;cursor:pointer}
    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:800}
    input,select{border:1px solid #d0d5dd;padding:9px 10px;border-radius:10px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px;vertical-align:top}
    th{background:#fafafa;text-align:left;font-size:12px;color:#475467}
    .badge{display:inline-block;padding:4px 8px;border-radius:6px;background:#f2f4f7;font-size:12px}
    .num{text-align:right;white-space:nowrap}
    .small{font-size:12px;color:#667085}

    .topbar{display:flex;justify-content:space-between;align-items:flex-end;gap:10px;flex-wrap:wrap}
    .actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}

    /* non-blocking status */
    .statusdot{display:inline-flex;align-items:center;gap:8px}
    .dot{width:8px;height:8px;border-radius:999px;background:#d0d5dd}
    .dot.live{background:#7f56d9}

    /* calc toggle */
    .check{display:inline-flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid #e4e7ec;border-radius:10px;background:#fff}
    .check input{margin:0}
</style>
@endpush

@section('content')
@php $qs = request()->query(); @endphp

<div class="wrap">
    <div class="topbar">
        <div>
            <h2 style="margin:0">Unified Spendings Ledger</h2>
            <div class="muted">Includes: petty_spendings + petty_bike_services (service costs).</div>
        </div>

        <div class="actions">
            <div class="statusdot">
                <span id="liveDot" class="dot"></span>
                <span class="muted" id="liveText">Idle</span>
                <span class="muted">•</span>
                <span class="muted">Results: <strong id="resultCount">{{ isset($spendings) ? $spendings->count() : (isset($rows) ? $rows->count() : 0) }}</strong></span>
            </div>

            <label class="check" title="If checked, PDF shows TOTALs and category summaries (bikes/meals/tokens/fuel).">
                <input id="calcToggle" type="checkbox" {{ !empty($calc) ? 'checked' : '' }}>
                <span class="muted" style="font-weight:800;color:#344054">Include calculations in PDF</span>
            </label>

            <a id="exportPdfBtn" class="btn2" href="{{ route('petty.ledger.spendings', array_merge($qs, ['export' => 'pdf'])) }}">
                Export PDF
            </a>
            <a id="exportCsvBtn" class="btn2" href="{{ route('petty.ledger.spendings', array_merge($qs, ['export' => 'csv'])) }}">
                Export CSV
            </a>
            <a id="exportExcelBtn" class="btn2" href="{{ route('petty.ledger.spendings', array_merge($qs, ['export' => 'excel'])) }}">
                Export Excel
            </a>

            <a class="btn2" href="{{ route('petty.ledger.spendings') }}">Reset</a>
        </div>
    </div>

    <div class="card">
        <form id="ledgerForm" method="GET" class="row" action="{{ route('petty.ledger.spendings') }}">
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
                    <option value="">All batches</option>
                    @foreach($batches as $b)
                        <option value="{{ $b->id }}" @selected((string)$batchId === (string)$b->id)>{{ $b->batch_no }}</option>
                    @endforeach
                </select>
                {{-- <div class="small">Applies to spendings only</div> --}}
            </div>

            <div>
                <div class="muted">Category</div>
                <select name="type">
                    <option value="">All</option>
                    <option value="bike" @selected($type==='bike')>Bike</option>
                    <option value="fuel" @selected($type==='fuel')>Fuel</option>
                    <option value="meal" @selected($type==='meal')>Meal</option>
                    <option value="token" @selected($type==='token')>Token</option>
                    <option value="other" @selected($type==='other')>Other</option>
                </select>
            </div>

            <div style="flex:1;min-width:320px">
                {{-- <div class="muted">Search (Reference / Transaction Ref)</div> --}}
                <input id="qInput" type="text" name="q" value="{{ $q }}"
                       placeholder="Search MPESA ref" autocomplete="off">
            </div>

            <button type="submit" style="display:none"></button>
{{-- 
            <div class="muted" style="flex-basis:100%">
                Note: Service rows come from <code>petty_bike_services</code>.
            </div> --}}
        </form>

        <div id="ledgerTableWrap">
            @include('pettycash::ledger._table', ['rows' => ($rows ?? $spendings ?? collect())])
        </div>
    </div>
</div>

<script>
(function(){
    const form = document.getElementById('ledgerForm');
    const wrap = document.getElementById('ledgerTableWrap');
    const dot = document.getElementById('liveDot');
    const liveText = document.getElementById('liveText');
    const resultCount = document.getElementById('resultCount');

    const exportPdfBtn = document.getElementById('exportPdfBtn');
    const exportCsvBtn = document.getElementById('exportCsvBtn');
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    const calcToggle = document.getElementById('calcToggle');

    let activeController = null;

    function setBusy(isBusy){
        dot.classList.toggle('live', isBusy);
        liveText.textContent = isBusy ? 'Searching…' : 'Idle';
    }

    function debounce(fn, wait){
        let t;
        return function(...args){
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        }
    }

    function buildUrl(extra = {}){
        const fd = new FormData(form);
        const params = new URLSearchParams(fd);
        Object.keys(extra).forEach(k => params.set(k, extra[k]));
        return form.action + '?' + params.toString();
    }

    function syncExportHref(){
        const calc = calcToggle.checked ? 1 : 0;
        exportPdfBtn.href = buildUrl({ export: 'pdf', calc });
        exportCsvBtn.href = buildUrl({ export: 'csv', calc });
        exportExcelBtn.href = buildUrl({ export: 'excel', calc });
    }

    const doSearch = debounce(async () => {
        if (activeController) activeController.abort();
        activeController = new AbortController();

        const url = buildUrl();
        window.history.replaceState({}, '', url);
        syncExportHref();

        setBusy(true);

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: activeController.signal
            });

            if (!res.ok) throw new Error('Request failed: ' + res.status);

            const data = await res.json();
            if (typeof data.html === 'string') wrap.innerHTML = data.html;
            if (typeof data.count !== 'undefined') resultCount.textContent = data.count;

        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        } finally {
            setBusy(false);
        }
    }, 250);

    form.querySelectorAll('input, select').forEach(el => {
        if (el.type === 'text') el.addEventListener('input', doSearch);
        else el.addEventListener('change', doSearch);
    });

    calcToggle.addEventListener('change', syncExportHref);

    // initial
    syncExportHref();
})();
</script>
@endsection
