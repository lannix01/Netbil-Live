@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm border-danger">
    <div class="card-header bg-danger text-white">
        <strong>Low Stock Alerts</strong>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Group</th>
                        <th>Reorder Level</th>
                        <th>Store Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $i)
                        <tr>
                            <td>{{ $i->name }}</td>
                            <td>{{ $i->group?->name }}</td>
                            <td>{{ $i->reorder_level }}</td>
                            <td><span class="badge bg-danger">{{ $i->qty_on_hand }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No low stock items 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $items->links() }}
    </div>
</div>
@endsection
