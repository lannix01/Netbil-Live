@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <strong>Movements</strong>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-sm btn-outline-primary" href="{{ route('inventory.movements.transfer') }}">Transfer Tech ➜ Tech</a>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('inventory.movements.return_to_store') }}">Return Tech ➜ Store</a>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Ref</th>
                        <th>Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Site</th>
                        <th>Lines</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        <tr>
                            <td style="white-space:nowrap;">{{ $m->movement_at?->format('Y-m-d H:i') }}</td>
                            <td><span class="badge bg-dark">{{ $m->reference }}</span></td>
                            <td>{{ str_replace('_',' ', strtoupper($m->type)) }}</td>
                            <td>{{ $m->fromUser?->name ?? '—' }}</td>
                            <td>{{ $m->toUser?->name ?? '—' }}</td>
                            <td>
                                @if($m->site_name || $m->site_code)
                                    <div class="fw-semibold">{{ $m->site_name }}</div>
                                    <small class="text-muted">{{ $m->site_code }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @foreach($m->lines as $l)
                                    <div>
                                        <span class="fw-semibold">{{ $l->item?->name }}</span>
                                        @if($l->serial_no)
                                            <span class="badge bg-info">{{ $l->serial_no }}</span>
                                        @elseif(!is_null($l->qty))
                                            <span class="badge bg-secondary">{{ $l->qty }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                            <td>{{ $m->creator?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $movements->links() }}
    </div>
</div>
@endsection
