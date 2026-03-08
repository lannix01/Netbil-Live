@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <strong>Stock Receipts</strong>
        <a class="btn btn-sm btn-primary" href="{{ route('inventory.receipts.create') }}">+ Receive Stock</a>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Received By</th>
                        <th class="text-end">View</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $r)
                        <tr>
                            <td>{{ $r->reference }}</td>
                            <td>{{ $r->received_date?->format('Y-m-d') }}</td>
                            <td>{{ $r->supplier_name }}</td>
                            <td>{{ $r->receiver?->name }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.receipts.show', $r) }}">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No receipts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $receipts->links() }}
    </div>
</div>
@endsection
