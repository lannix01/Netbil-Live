@extends('pettycash::layouts.app')

@section('title','Token - Hostels')

@push('styles')
<style>
    .wrap{max-width:none;margin:0}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px;overflow:visible}
    .top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .muted{color:#667085;font-size:12px}
    .success{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px;border-radius:10px;margin-top:12px}

    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px;vertical-align:top;overflow-wrap:anywhere}
    th{font-size:12px;color:#475467;text-align:left;white-space:normal}

    .btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:700;border:none;cursor:pointer}
    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:700}
    .btn2:hover{background:#f9fafb}

    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px;white-space:nowrap}
    .pill-link{display:inline;text-decoration:none;overflow-wrap:anywhere;word-break:break-word}
    .pill-link:hover{opacity:.9}

    /* Status badges */
    .badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap}
    .b-upcoming{background:#eff8ff;border:1px solid #b2ddff;color:#175cd3}
    .b-due{background:#fffaeb;border:1px solid #fedf89;color:#b54708}
    .b-overdue{background:#fef3f2;border:1px solid #fecdca;color:#b42318}
    .b-unknown{background:#f2f4f7;border:1px solid #eaecf0;color:#344054}

    /* Reminder cards */
    .rem-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
        gap:12px;
        align-items:stretch;
    }
    .rem-grid > *{min-width:0}
    .rem-card{
        border:1px solid #e7e9f2;
        background:#fff;
        border-radius:14px;
        padding:12px;
        box-shadow:0 6px 18px rgba(16,24,40,.06);
        min-height:96px;
    }
    .rem-title{font-size:12px;color:#475467;margin:0}
    .rem-count{font-size:20px;font-weight:900;margin:3px 0 0}
    .rem-hint{margin:6px 0 0;font-size:12px;color:#667085}
    .rem-card.due{border-color:#fedf89}
    .rem-card.overdue{border-color:#fecdca}
    .rem-card.soon{border-color:#b2ddff}

    /* Search bar */
    .filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:12px}
    .input{width:min(420px,100%);padding:10px 12px;border:1px solid #d0d5dd;border-radius:12px;font-size:13px;outline:none}
    .input:focus{border-color:#7f56d9;box-shadow:0 0 0 4px rgba(127,86,217,.12)}
    .mini{font-size:12px;color:#667085}
    .right{margin-left:auto;display:flex;gap:10px;flex-wrap:wrap}

    /* Responsive table */
    .table-wrap{
        overflow-x:hidden;
        overflow-y:visible;
        border-radius:14px;
        border:1px solid #eef2f6;
        margin-top:12px;
        background:#fff;
    }
    .table-wrap table{
        margin-top:0;
        width:100%;
        min-width:100% !important;
        table-layout:auto;
    }
    .table-wrap th,.table-wrap td{padding:12px 10px}
    .table-wrap td{white-space:normal}
    .table-wrap tbody tr:hover{background:#f9fbff}
    .table-wrap .nowrap{white-space:normal}

    .section-title{
        font-weight:900;
        font-size:16px;
        display:flex;
        align-items:center;
        gap:8px;
    }
    .section-title::before{
        content:"";
        width:6px;
        height:18px;
        border-radius:99px;
        background:#7f56d9;
    }
    .footer-note{
        margin-top:10px;
        border-top:1px dashed #d0d5dd;
        padding-top:10px;
    }
    .pager{
        margin-top:12px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }
    .pager-meta{
        font-size:12px;
        color:#667085;
    }
    .pager-nav{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:wrap;
    }
    .pg-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:36px;
        height:36px;
        padding:0 12px;
        border-radius:10px;
        border:1px solid #d0d5dd;
        background:#fff;
        color:#344054;
        text-decoration:none;
        font-size:13px;
        font-weight:700;
        line-height:1;
        transition:.16s ease;
    }
    .pg-btn:hover{
        border-color:#98a2b3;
        background:#f9fafb;
    }
    .pg-btn.active{
        border-color:#111827;
        background:#111827;
        color:#fff;
    }
    .pg-btn.disabled{
        color:#98a2b3;
        background:#f9fafb;
        border-color:#eaecf0;
        pointer-events:none;
    }

    /* Stats row */
    .stat-grid{display:grid;grid-template-columns:repeat(3, minmax(0, 1fr));gap:10px;margin-top:12px}
    .stat-card{border:1px solid #e7e9f2;background:#fff;border-radius:14px;padding:12px;box-shadow:0 6px 18px rgba(16,24,40,.06)}
    .stat-label{font-size:12px;color:#475467;margin:0}
    .stat-value{font-size:22px;font-weight:900;margin:4px 0 0}
    .stat-sub{font-size:12px;color:#667085;margin-top:6px}

    @media(max-width:900px){
        .stat-grid{grid-template-columns:1fr}
    }

    @media(max-width:520px){
        .rem-grid{grid-template-columns:repeat(auto-fit, minmax(150px, 1fr))}
    }

    @media(max-width:760px){
        .table-wrap{border:none;background:transparent}
        .table-wrap table{display:block}
        .table-wrap thead{display:none}
        .table-wrap tbody{display:grid;gap:10px}
        .table-wrap tr{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:8px 12px;
            border:1px solid #e7e9f2;
            border-radius:14px;
            background:#fff;
            box-shadow:0 4px 14px rgba(16,24,40,.05);
            padding:12px;
        }
        .table-wrap td{
            border-bottom:none;
            padding:0;
            display:flex;
            flex-direction:column;
            gap:4px;
            min-width:0;
        }
        .table-wrap td::before{
            content:attr(data-label);
            font-size:11px;
            font-weight:800;
            letter-spacing:.04em;
            text-transform:uppercase;
            color:#667085;
        }
        .table-wrap td:first-child{
            grid-column:1 / -1;
            padding-bottom:6px;
            border-bottom:1px dashed #eaecf0;
            margin-bottom:2px;
        }
        .table-wrap td:first-child::before{display:none}
        .pill,.badge{width:max-content;max-width:100%}
    }
</style>
@endpush

@section('content')
@php
    // Total hostels (unfiltered) - safe if controller didn't provide it:
    // If you want true total even when filtered, pass $totalHostels from controller.
    $canAddHostel = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.create_hostel');
    $filteredCount = isset($hostels)
        ? (method_exists($hostels, 'total') ? $hostels->total() : $hostels->count())
        : 0;
    $shownCount = isset($hostels) ? $hostels->count() : 0;
    $totalCount = isset($totalHostels) ? (int)$totalHostels : $filteredCount;
    $currentPerPage = isset($perPage) ? (int)$perPage : 25;
    $currentSortDue = $sortDue ?? 'asc';
    $pageSizes = $perPageOptions ?? [15, 25, 30, 50, 100];

    $hasSearch = !empty($q);
    $exportBase = [
        'q' => $q ?? '',
    ];
@endphp

<div class="wrap">
    <div class="top">
        <div>
            <h2 style="margin:0">Token (Hostels)</h2>
            {{-- <div class="muted">Click meter/phone → compact view to record payments. Reminders are based on last payment date.</div> --}}
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn2" href="{{ route('petty.tokens.pdf', array_merge($exportBase, ['format' => 'pdf'])) }}">PDF</a>
            <a class="btn2" href="{{ route('petty.tokens.pdf', array_merge($exportBase, ['format' => 'csv'])) }}">CSV</a>
            <a class="btn2" href="{{ route('petty.tokens.pdf', array_merge($exportBase, ['format' => 'excel'])) }}">Excel</a>
            @if($canAddHostel)
                <a class="btn" href="{{ route('petty.tokens.create') }}">+ Add Hostel</a>
            @endif
        </div>
    </div>

    {{-- Counter cards --}}
    <div class="stat-grid">
        <div class="stat-card">
            <p class="stat-label">Total Hostels</p>
            <div class="stat-value">{{ number_format($totalCount) }}</div>
            <div class="stat-sub">All hostels</div>
        </div>

        <div class="stat-card">
            <p class="stat-label">Listed</p>
            <div class="stat-value">{{ number_format($filteredCount) }}</div>
            <div class="stat-sub">
                @if($hasSearch)
                    Filtered results (search active)
                @else
                    All listed records
                @endif
            </div>
        </div>

        <div class="stat-card">
            <p class="stat-label">Today's Date</p>
            <div class="stat-value">{{ isset($today) ? $today->format('Y-m-d') : now()->format('Y-m-d') }}</div>
            {{-- <div class="stat-sub">Snapshot date</div> --}}
        </div>
    </div>

    {{-- Reminders summary --}}
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:end">
            <div>
                <div class="section-title">Payment Reminders</div>
                {{-- <div class="muted">Today: <strong>{{ isset($today) ? $today->format('Y-m-d') : now()->format('Y-m-d') }}</strong></div> --}}
            </div>
            {{-- <div class="mini">Due buckets: 3 days, 2 days, 1 day, due today, overdue.</div> --}}
        </div>

        @php
            $cDueToday = isset($reminders['due_today']) ? $reminders['due_today']->count() : 0;
            $c1 = isset($reminders['due_1']) ? $reminders['due_1']->count() : 0;
            $c2 = isset($reminders['due_2']) ? $reminders['due_2']->count() : 0;
            $c3 = isset($reminders['due_3']) ? $reminders['due_3']->count() : 0;
            $cOver = isset($reminders['overdue']) ? $reminders['overdue']->count() : 0;
        @endphp

        <div class="rem-grid" style="margin-top:12px">
            <div class="rem-card due">
                <p class="rem-title">Due Today</p>
                <div class="rem-count">{{ $cDueToday }}</div>
                <p class="rem-hint">Pay now.</p>
            </div>
            <div class="rem-card soon">
                <p class="rem-title">Due Tomorrow</p>
                <div class="rem-count">{{ $c1 }}</div>
                <p class="rem-hint">1 day left.</p>
            </div>
            <div class="rem-card soon">
                <p class="rem-title">Due in 2 Days</p>
                <div class="rem-count">{{ $c2 }}</div>
                <p class="rem-hint">Plan ahead.</p>
            </div>
            <div class="rem-card soon">
                <p class="rem-title">Due in 3 Days</p>
                <div class="rem-count">{{ $c3 }}</div>
                <p class="rem-hint">Early reminder.</p>
            </div>
            <div class="rem-card overdue">
                <p class="rem-title">Overdue</p>
                <div class="rem-count">{{ $cOver }}</div>
                <p class="rem-hint">Date Passed.</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filters">
        <form method="GET" action="{{ route('petty.tokens.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;width:100%">
            <input class="input" type="text" name="q" value="{{ $q ?? '' }}"
                   placeholder="Search hostel name, meter no, phone…">
            <select class="input" name="sort_due" style="width:190px" onchange="this.form.submit()">
                <option value="asc" @selected($currentSortDue === 'asc')>Due Date: Earliest First</option>
                <option value="desc" @selected($currentSortDue === 'desc')>Due Date: Latest First</option>
            </select>
            <select class="input" name="per_page" style="width:140px" onchange="this.form.submit()">
                @foreach($pageSizes as $size)
                    <option value="{{ $size }}" @selected((int)$currentPerPage === (int)$size)>{{ $size }} / page</option>
                @endforeach
            </select>
            <div class="right">
                <button class="btn2" type="submit">Search</button>
                @if(!empty($q) || (int)$currentPerPage !== 25 || $currentSortDue !== 'asc')
                    <a class="btn2" href="{{ route('petty.tokens.index') }}">Clear</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Hostel</th>
                <th>Meter No</th>
                <th>Phone</th>
                <th>Routers</th>
                <th>Stake</th>
                <th>Amount Due</th>
                <th>Last Payment</th>
                <th>Next Due</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @forelse($hostels as $h)
                @php
                    $status = $h->due_status ?? 'unknown';
                    $badge = $h->due_badge ?? '—';
                    $nextDue = $h->next_due_date ?? null;

                    $badgeClass = 'b-unknown';
                    if($status === 'overdue') $badgeClass = 'b-overdue';
                    elseif($status === 'due_today') $badgeClass = 'b-due';
                    elseif($status === 'upcoming') $badgeClass = 'b-upcoming';
                @endphp

                <tr>
                    <td data-label="Hostel">
                        <div style="font-weight:900">{{ $h->hostel_name }}</div>
                        <div class="muted">ID: {{ $h->id }}</div>
                    </td>
                    <td data-label="Meter No">
                        <a class="pill-link" href="{{ route('petty.tokens.hostels.show', $h->id) }}">
                            {{ $h->meter_no ?? '-' }}
                        </a>
                    </td>
                    <td data-label="Phone">
                        <a class="pill-link" href="{{ route('petty.tokens.hostels.show', $h->id) }}">
                            {{ $h->phone_no ?? '-' }}
                        </a>
                    </td>
                    <td data-label="Routers">{{ $h->no_of_routers }}</td>
                    <td data-label="Stake"><span class="pill">{{ strtoupper($h->stake) }}</span></td>
                    <td data-label="Amount Due">{{ number_format((float)$h->amount_due, 2) }}</td>
                    <td data-label="Last Payment">
                        @if($h->last_payment_amount !== null)
                            <span class="pill">{{ number_format((float)$h->last_payment_amount, 2) }}</span>
                            <div class="muted">{{ $h->last_payment_date }}</div>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td data-label="Next Due">
                        @if($nextDue)
                            <span class="pill">{{ $nextDue }}</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td data-label="Status">
                        <span class="badge {{ $badgeClass }}">{{ $badge }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted" style="padding:16px">No hostels yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="muted footer-note">
        Showing {{ number_format($shownCount) }} of {{ number_format($filteredCount) }} hostel{{ $filteredCount === 1 ? '' : 's' }}.
        Page size: <strong>{{ $currentPerPage }}</strong>.
    </div>

    @if(method_exists($hostels, 'links'))
        @php
            $current = $hostels->currentPage();
            $last = $hostels->lastPage();
            $start = max(1, $current - 2);
            $end = min($last, $current + 2);
        @endphp
        <div class="pager">
            <div class="pager-meta">
                Page <strong>{{ $current }}</strong> of <strong>{{ $last }}</strong>
            </div>

            <div class="pager-nav">
                @if($hostels->onFirstPage())
                    <span class="pg-btn disabled">Previous</span>
                @else
                    <a class="pg-btn" href="{{ $hostels->previousPageUrl() }}" rel="prev">Previous</a>
                @endif

                @if($start > 1)
                    <a class="pg-btn" href="{{ $hostels->url(1) }}">1</a>
                    @if($start > 2)
                        <span class="pg-btn disabled">...</span>
                    @endif
                @endif

                @for($page = $start; $page <= $end; $page++)
                    @if($page === $current)
                        <span class="pg-btn active">{{ $page }}</span>
                    @else
                        <a class="pg-btn" href="{{ $hostels->url($page) }}">{{ $page }}</a>
                    @endif
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)
                        <span class="pg-btn disabled">...</span>
                    @endif
                    <a class="pg-btn" href="{{ $hostels->url($last) }}">{{ $last }}</a>
                @endif

                @if($hostels->hasMorePages())
                    <a class="pg-btn" href="{{ $hostels->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="pg-btn disabled">Next</span>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
