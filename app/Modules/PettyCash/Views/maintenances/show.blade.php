@extends('pettycash::layouts.app')

@section('title','Maintenance - '.$bike->plate_no)

@push('styles')
<style>
    .wrap{max-width:1200px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap}
    .muted{color:#667085;font-size:12px}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px;font-weight:800}
    .pill.bad{background:#fef3f2;color:#b42318}
    .pill.ok{background:#ecfdf3;color:#027a48}
    .pill.soon{background:#fffaeb;color:#b54708}
    .pill.ur{background:#111827;color:#fff}
    .btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:800;border:none;cursor:pointer}
    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:800}
    .tabs{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}
    .tab{padding:9px 12px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;color:#344054;font-weight:800;text-decoration:none}
    .tab.active{background:#111827;color:#fff;border-color:#111827}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
    th{font-size:12px;color:#475467;text-align:left}
    label{display:block;font-size:12px;color:#475467;margin:12px 0 6px}
    input,select,textarea{width:100%;border:1px solid #d0d5dd;padding:10px;border-radius:10px}
    .grid{display:grid;grid-template-columns: 1fr 1fr; gap:12px;}
    @media(max-width:900px){.grid{grid-template-columns:1fr}}
    .success{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px;border-radius:10px;margin-top:12px}
    .err{background:#fef3f2;color:#b42318;border:1px solid #fecdca;padding:10px;border-radius:10px;margin-top:12px}
</style>
@endpush

@section('content')
<div class="wrap">

    <div class="top">
        <div>
            <h2 style="margin:0">{{ $bike->plate_no }}</h2>
            <div class="muted" style="margin-top:6px">
                Last service: <span class="pill">{{ $lastService?->format('Y-m-d') ?? '—' }}</span>
                Next due: <span class="pill">{{ $nextDue?->format('Y-m-d') ?? '—' }}</span>

                @if($bike->is_unroadworthy)
                    <span class="pill ur">UNROADWORTHY</span>
                @endif
            </div>

            <div class="muted" style="margin-top:6px">
                Service total (net): <strong>{{ number_format($serviceTotalNet,2) }}</strong>
                &nbsp; | &nbsp;
                Maintenance total (net): <strong>{{ number_format($maintenanceTotalNet,2) }}</strong>
            </div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn2" href="{{ route('petty.maintenances.index') }}">Back</a>
            <a class="btn" href="{{ route('petty.maintenances.service.create', $bike->id) }}">+ Record Service</a>
        </div>
    </div>

    @if($errors->any())
        <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <div class="tabs">
        <a class="tab {{ $tab==='overview' ? 'active' : '' }}" href="{{ route('petty.maintenances.show', [$bike->id, 'tab'=>'overview']) }}">Overview</a>
        <a class="tab {{ $tab==='services' ? 'active' : '' }}" href="{{ route('petty.maintenances.show', [$bike->id, 'tab'=>'services']) }}">Services</a>
        <a class="tab {{ $tab==='maintenances' ? 'active' : '' }}" href="{{ route('petty.maintenances.show', [$bike->id, 'tab'=>'maintenances']) }}">Maintenances (Repairs)</a>
    </div>

    {{-- Overview --}}
    @if($tab === 'overview')
        <div class="grid">
            <div class="card">
                <h3 style="margin:0 0 6px">Service Status</h3>
                @php
                    $today = \Carbon\Carbon::today();
                    $statusPill = 'ok';
                    $statusText = 'OK';

                    if ($bike->is_unroadworthy) { $statusPill = 'ur'; $statusText = 'UNROADWORTHY'; }
                    else if (!$bike->next_service_due_date) { $statusPill='bad'; $statusText='Never serviced'; }
                    else {
                        $d = \Carbon\Carbon::parse($bike->next_service_due_date);
                        if ($d->lt($today)) { $statusPill='bad'; $statusText='Overdue'; }
                        else if ($d->lte($today->copy()->addDays(7))) { $statusPill='soon'; $statusText='Due soon'; }
                        else { $statusPill='ok'; $statusText='OK'; }
                    }
                @endphp
                <div class="muted">Current status</div>
                <div style="margin-top:8px"><span class="pill {{ $statusPill }}">{{ $statusText }}</span></div>

                @if(!$bike->is_unroadworthy)
                    <div class="muted" style="margin-top:10px">
                        Rule: due every <strong>21 days</strong>.
                    </div>
                @endif
            </div>

            <div class="card">
                <h3 style="margin:0 0 6px">Unroadworthy Flag</h3>
                <div class="muted">Admin can mark/unmark this bike.</div>

                <form method="POST" action="{{ route('petty.maintenances.unroadworthy', $bike->id) }}">
                    @csrf

                    <label>Status</label>
                    <select name="is_unroadworthy" required>
                        <option value="0" @selected(!$bike->is_unroadworthy)>Roadworthy</option>
                        <option value="1" @selected($bike->is_unroadworthy)>Unroadworthy</option>
                    </select>

                    <label>Notes</label>
                    <textarea name="unroadworthy_notes" rows="3">{{ old('unroadworthy_notes', $bike->unroadworthy_notes) }}</textarea>

                    <div style="margin-top:14px;">
                        <button class="btn2" type="submit">Save Flag</button>
                    </div>
                </form>
            </div>
        </div>

    {{-- Services --}}
    @elseif($tab === 'services')
        <div class="card">
            <h3 style="margin:0">Services</h3>
            <div class="muted">Routine schedule-based services.</div>

            <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Work Done</th>
                    <th>Ref</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Fee</th>
                    <th class="text-end">Total</th>
                    <th>Next Due</th>
                </tr>
                </thead>
                <tbody>
                @forelse($services as $s)
                    @php $fee=(float)($s->transaction_cost??0); $amt=(float)$s->amount; @endphp
                    <tr>
                        <td>{{ $s->service_date?->format('Y-m-d') }}</td>
                        <td>{{ $s->work_done }}</td>
                        <td>{{ $s->reference ?? '-' }}</td>
                        <td class="text-end">{{ number_format($amt,2) }}</td>
                        <td class="text-end">{{ number_format($fee,2) }}</td>
                        <td class="text-end"><strong>{{ number_format($amt+$fee,2) }}</strong></td>
                        <td>{{ $s->next_due_date?->format('Y-m-d') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No services recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>

    {{-- Maintenances (repairs) --}}
    @else
        <div class="card">
            <h3 style="margin:0">Maintenances (Repairs)</h3>
            <div class="muted">These are your existing bike spendings with sub_type=maintenance.</div>

            <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Work / Particulars</th>
                    <th>Ref</th>
                    <th>Batch</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Fee</th>
                    <th class="text-end">Total</th>
                </tr>
                </thead>
                <tbody>
                @forelse($maintenances as $m)
                    @php $fee=(float)($m->transaction_cost??0); $amt=(float)$m->amount; @endphp
                    <tr>
                        <td>{{ $m->date?->format('Y-m-d') }}</td>
                        <td>{{ $m->particulars ?? ($m->description ?? '-') }}</td>
                        <td>{{ $m->reference ?? '-' }}</td>
                        <td>{{ $m->batch?->batch_no ?? '-' }}</td>
                        <td class="text-end">{{ number_format($amt,2) }}</td>
                        <td class="text-end">{{ number_format($fee,2) }}</td>
                        <td class="text-end"><strong>{{ number_format($amt+$fee,2) }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No maintenance repairs recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    @endif

</div>
@endsection
