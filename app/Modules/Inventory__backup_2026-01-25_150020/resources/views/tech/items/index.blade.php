@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header"><strong>My Assigned Items</strong></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Group</th>
                        <th>Allocated</th>
                        <th>Deployed</th>
                        <th>Available</th>
                        <th class="text-end">Open</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $a)
                        <tr>
                            <td>{{ $a->item?->name }}</td>
                            <td>{{ $a->item?->group?->name }}</td>
                            <td>{{ $a->qty_allocated }}</td>
                            <td>{{ $a->qty_deployed }}</td>
                            <td><span class="badge bg-dark">{{ $a->availableToDeploy() }}</span></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.tech.items.show', $a->item_id) }}">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No assigned items.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $assignments->links() }}
    </div>
</div>
@endsection
@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header"><strong>My Assigned Items</strong></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Group</th>
                        <th>Allocated</th>
                        <th>Deployed</th>
                        <th>Available</th>
                        <th class="text-end">Open</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $a)
                        <tr>
                            <td>{{ $a->item?->name }}</td>
                            <td>{{ $a->item?->group?->name }}</td>
                            <td>{{ $a->qty_allocated }}</td>
                            <td>{{ $a->qty_deployed }}</td>
                            <td><span class="badge bg-dark">{{ $a->availableToDeploy() }}</span></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.tech.items.show', $a->item_id) }}">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No assigned items.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $assignments->links() }}
    </div>
</div>
@endsection
