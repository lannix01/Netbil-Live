@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <strong>Inventory Logs</strong>

        <form class="d-flex gap-2" method="GET" action="{{ route('inventory.logs.index') }}">
            <select class="form-select form-select-sm" name="action" style="min-width: 160px;">
                <option value="" @selected($action==='')>All Actions</option>
                <option value="received" @selected($action==='received')>Received</option>
                <option value="assigned" @selected($action==='assigned')>Assigned</option>
                <option value="deployed" @selected($action==='deployed')>Deployed</option>
            </select>

            <input class="form-control form-control-sm" name="q" value="{{ $q }}" placeholder="Search serial, site, ref, item, user..." style="min-width: 260px;">
            <button class="btn btn-sm btn-dark">Filter</button>

            @if($q !== '' || $action !== '')
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.logs.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Item</th>
                        <th>Serial</th>
                        <th>Qty</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Site</th>
                        <th>Ref</th>
                        <th>Logged By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td style="white-space:nowrap;">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($log->action === 'received')
                                    <span class="badge bg-success">RECEIVED</span>
                                @elseif($log->action === 'assigned')
                                    <span class="badge bg-primary">ASSIGNED</span>
                                @elseif($log->action === 'deployed')
                                    <span class="badge bg-dark">DEPLOYED</span>
                                @else
                                    <span class="badge bg-secondary">{{ strtoupper($log->action) }}</span>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $log->item?->name }}</div>
                                <small class="text-muted">{{ $log->item?->sku }}</small>
                            </td>

                            <td>
                                @if($log->serial_no)
                                    <span class="badge bg-info">{{ $log->serial_no }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if(!is_null($log->qty))
                                    <span class="badge bg-secondary">{{ $log->qty }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>{{ $log->fromUser?->name ?? '—' }}</td>
                            <td>{{ $log->toUser?->name ?? '—' }}</td>

                            <td>
                                @if($log->site_name || $log->site_code)
                                    <div class="fw-semibold">{{ $log->site_name }}</div>
                                    <small class="text-muted">{{ $log->site_code }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>{{ $log->reference ?? '—' }}</td>
                            <td>{{ $log->creator?->name ?? '—' }}</td>
                            <td style="max-width:240px;">{{ $log->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted">No logs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </div>
</div>
@endsection
