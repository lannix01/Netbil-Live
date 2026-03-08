@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <strong>Items</strong>
        <a class="btn btn-sm btn-primary" href="{{ route('inventory.items.create') }}">+ New Item</a>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Group</th>
                        <th>SKU</th>
                        <th>Unit</th>
                        <th>Serial?</th>
                        <th>Reorder</th>
                        <th>Store Qty</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $i)
                        <tr>
                            <td>{{ $i->id }}</td>
                            <td>{{ $i->name }}</td>
                            <td>{{ $i->group?->name }}</td>
                            <td>{{ $i->sku }}</td>
                            <td>{{ $i->unit }}</td>
                            <td>
                                @if($i->has_serial)
                                    <span class="badge bg-info">Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </td>
                            <td>{{ $i->reorder_level }}</td>
                            <td>
                                @if($i->isLowStock())
                                    <span class="badge bg-danger">{{ $i->qty_on_hand }}</span>
                                @else
                                    <span class="badge bg-success">{{ $i->qty_on_hand }}</span>
                                @endif
                            </td>
                            <td>
                                @if($i->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.items.edit', $i) }}">Edit</a>
                                <form class="d-inline" method="POST" action="{{ route('inventory.items.destroy', $i) }}" onsubmit="return confirm('Delete this item?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">No items yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $items->links() }}
    </div>
</div>
@endsection
