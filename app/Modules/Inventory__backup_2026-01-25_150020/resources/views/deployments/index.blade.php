@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header"><strong>Deployments</strong></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Technician</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Site</th>
                        <th>Ref</th>
                        <th>Logged By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deployments as $d)
                        <tr>
                            <td>{{ $d->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $d->technician?->name }}</td>
                            <td>{{ $d->item?->name }}</td>
                            <td><span class="badge bg-dark">{{ $d->qty }}</span></td>
                            <td>{{ $d->site_name }}</td>
                            <td>{{ $d->reference }}</td>
                            <td>{{ $d->creator?->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No deployments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $deployments->links() }}
    </div>
</div>
@endsection
