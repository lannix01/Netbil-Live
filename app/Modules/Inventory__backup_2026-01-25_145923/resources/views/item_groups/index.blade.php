@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <strong>Item Groups</strong>
        <a class="btn btn-sm btn-primary" href="{{ route('inventory.item-groups.create') }}">+ New Group</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $g)
                        <tr>
                            <td>{{ $g->id }}</td>
                            <td>{{ $g->name }}</td>
                            <td>{{ $g->code }}</td>
                            <td>{{ $g->description }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.item-groups.edit', $g) }}">Edit</a>
                                <form class="d-inline" method="POST" action="{{ route('inventory.item-groups.destroy', $g) }}" onsubmit="return confirm('Delete this group?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No groups yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $groups->links() }}
    </div>
</div>
@endsection
