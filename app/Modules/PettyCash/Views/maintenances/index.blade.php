@extends('pettycash::layouts.app')

@section('title','Bike Service')

@push('styles')
<style>
    .wrap{max-width:1200px;margin:0 auto}
    .tabs{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}
    .tab{padding:9px 12px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;color:#344054;font-weight:800;text-decoration:none}
    .tab.active{background:#111827;color:#fff;border-color:#111827}

    .topbar{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .muted{color:#667085;font-size:12px}

    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .card-head{display:flex;align-items:flex-end;justify-content:space-between;gap:10px;flex-wrap:wrap}
    .card-head h3{margin:0}
    .card-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}

    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px;vertical-align:top}
    th{font-size:12px;color:#475467;text-align:left}
    .text-end{text-align:right}

    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px;font-weight:800}
    .pill.ok{background:#ecfdf3;color:#027a48}
    .pill.soon{background:#fffaeb;color:#b54708}
    .pill.bad{background:#fef3f2;color:#b42318}
    .pill.never{background:#f2f4f7;color:#475467}
    .pill.ur{background:#111827;color:#fff}

    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:800}
    .btnDark{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #111827;background:#111827;color:#fff;text-decoration:none;font-weight:800}

    .empty{
        border:1px dashed #d0d5dd;
        background:#fafafa;
        padding:18px;
        border-radius:14px;
        margin-top:12px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:14px;
        flex-wrap:wrap;
    }
    .empty .big{font-weight:900;color:#101828}
    .empty .sub{color:#667085;font-size:12px;margin-top:4px}
    .empty .actions{display:flex;gap:10px;flex-wrap:wrap}

    /* Tiny shimmer loader (pure CSS, no JS needed) */
    .shimmer{
        position:relative;
        overflow:hidden;
        border-radius:12px;
        border:1px solid #eef2f6;
        background:#fff;
        margin-top:12px;
    }
    .shimmer::after{
        content:"";
        position:absolute;top:0;left:-40%;
        width:40%;height:100%;
        background:linear-gradient(90deg, transparent, rgba(17,24,39,.06), transparent);
        animation:sh 1.1s infinite;
    }
    @keyframes sh{0%{left:-40%}100%{left:120%}}
    .sh-row{display:flex;gap:10px;padding:12px;border-bottom:1px solid #eef2f6}
    .sh-cell{height:12px;border-radius:999px;background:#f2f4f7;flex:1}
    .sh-cell.sm{flex:.4}
    .sh-cell.md{flex:.7}
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="topbar">
        <div>
            <h2 style="margin:0">Service Management</h2>
            <div class="muted">Service schedule + service history + unroadworthy list.</div>
        </div>
        {{-- Global quick action (safe, takes you to schedule) --}}
        <div class="card-actions">
            <a class="btn2" href="{{ route('petty.maintenances.index', ['tab'=>'schedule']) }}">Open Schedule</a>
        </div>
    </div>

    <div class="tabs">
        <a class="tab {{ $tab==='schedule' ? 'active' : '' }}" href="{{ route('petty.maintenances.index', ['tab'=>'schedule']) }}">Service Schedule</a>
        <a class="tab {{ $tab==='history' ? 'active' : '' }}" href="{{ route('petty.maintenances.index', ['tab'=>'history']) }}">Service History</a>
        <a class="tab {{ $tab==='unroadworthy' ? 'active' : '' }}" href="{{ route('petty.maintenances.index', ['tab'=>'unroadworthy']) }}">Unroadworthy</a>
    </div>

    @if($tab === 'history')
        <div class="card">
            <div class="card-head">
                <div>
                    <h3>Service History</h3>
                    <div class="muted">Latest services recorded (from <code>petty_bike_services</code>).</div>
                </div>
                <div class="card-actions">
                    <a class="btn2" href="{{ route('petty.maintenances.index', ['tab'=>'schedule']) }}">Go to Schedule</a>
                </div>
            </div>

            @if(($services ?? null) && $services->count())
                <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th style="width:110px">Date</th>
                        <th style="width:110px">Bike</th>
                        <th style="width:160px">Reference</th>
                        <th>Work Done</th>
                        <th class="text-end" style="width:110px">Amount</th>
                        <th class="text-end" style="width:90px">Fee</th>
                        <th class="text-end" style="width:110px">Total</th>
                        <th style="width:110px">Next Due</th>
                        <th style="width:120px">Recorded By</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($services as $s)
                        @php
                            $fee = (float)($s->transaction_cost ?? 0);
                            $amt = (float)($s->amount ?? 0);
                            $total = $amt + $fee;
                        @endphp
                        <tr>
                            <td>{{ $s->service_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                <a href="{{ route('petty.maintenances.show', $s->bike_id) }}">
                                    {{ $s->bike?->plate_no ?? ('Bike #'.$s->bike_id) }}
                                </a>
                            </td>
                            <td>{{ $s->reference ?? '-' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($s->work_done, 80) }}</td>
                            <td class="text-end">{{ number_format($amt,2) }}</td>
                            <td class="text-end">{{ number_format($fee,2) }}</td>
                            <td class="text-end"><strong>{{ number_format($total,2) }}</strong></td>
                            <td>{{ $s->next_due_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>{{ $s->recorded_by ?? '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>

                <div style="margin-top:12px;">{{ $services->onEachSide(1)->links('pettycash::partials.pagination') }}</div>
            @else
                <div class="empty">
                    <div>
                        <div class="big">No services recorded yet 🧾</div>
                        <div class="sub">Start by opening a bike, then hit “Record Service”. Your schedule will also update automatically.</div>
                    </div>
                    <div class="actions">
                        <a class="btnDark" href="{{ route('petty.maintenances.index', ['tab'=>'schedule']) }}">Open Schedule</a>
                    </div>
                </div>

                {{-- Optional shimmer to make empty state feel intentional --}}
                <div class="shimmer" aria-hidden="true">
                    @for($i=0;$i<4;$i++)
                        <div class="sh-row">
                            <div class="sh-cell sm"></div>
                            <div class="sh-cell sm"></div>
                            <div class="sh-cell md"></div>
                            <div class="sh-cell"></div>
                            <div class="sh-cell sm"></div>
                        </div>
                    @endfor
                </div>
            @endif
        </div>

    @elseif($tab === 'unroadworthy')
        <div class="card">
            <div class="card-head">
                <div>
                    <h3>Unroadworthy Bikes</h3>
                    <div class="muted">Bikes flagged as unroadworthy.</div>
                </div>
            </div>

            @if(($unroadworthy ?? null) && count($unroadworthy))
                <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Bike</th>
                        <th>Notes</th>
                        <th style="width:170px">Flagged At</th>
                        <th style="width:110px"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($unroadworthy as $b)
                        <tr>
                            <td><a href="{{ route('petty.maintenances.show', $b->id) }}">{{ $b->plate_no }}</a></td>
                            <td>{{ $b->unroadworthy_notes ?? '-' }}</td>
                            <td>{{ $b->unroadworthy_at ? \Carbon\Carbon::parse($b->unroadworthy_at)->format('Y-m-d H:i') : '-' }}</td>
                            <td class="text-end"><a class="btn2" href="{{ route('petty.maintenances.show', $b->id) }}">Open</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            @else
                <div class="empty">
                    <div>
                        <div class="big">No unroadworthy bikes 🎉</div>
                        <div class="sub">Nothing flagged. That’s… suspiciously healthy. Keep it that way.</div>
                    </div>
                </div>
            @endif
        </div>

    @else
        <div class="card">
            <div class="card-head">
                <div>
                    <h3>Service Schedule</h3>
                    <div class="muted">Status is computed from each bike’s next due date.</div>
                </div>
            </div>

            @if(($bikes ?? null) && $bikes->count())
                <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Bike</th>
                        <th style="width:140px">Last Service</th>
                        <th style="width:140px">Next Due</th>
                        <th style="width:140px">Status</th>
                        <th style="width:220px"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($bikes as $b)
                        @php
                            $status = $b->computed_status;
                            $label = match($status) {
                                'ok' => ['OK','ok'],
                                'due_soon' => ['Due soon','soon'],
                                'overdue' => ['Overdue','bad'],
                                'unroadworthy' => ['Unroadworthy','ur'],
                                default => ['Never serviced','never'],
                            };
                        @endphp
                        <tr>
                            <td><a href="{{ route('petty.maintenances.show', $b->id) }}">{{ $b->plate_no }}</a></td>
                            <td>{{ $b->computed_last_service ?? '-' }}</td>
                            <td>{{ $b->computed_next_due ?? '-' }}</td>
                            <td><span class="pill {{ $label[1] }}">{{ $label[0] }}</span></td>
                            <td class="text-end">
                                <a class="btn2" href="{{ route('petty.maintenances.show', $b->id) }}">Open</a>
                                <a class="btnDark" href="{{ route('petty.maintenances.service.create', $b->id) }}">Record Service</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            @else
                <div class="empty">
                    <div>
                        <div class="big">No bikes found </div>
                        <div class="sub">bikes first, then schedule.</div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
