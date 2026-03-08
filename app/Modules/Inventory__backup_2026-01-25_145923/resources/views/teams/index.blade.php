@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <strong>Teams</strong>

        <div class="d-flex gap-2 flex-wrap">
            <form class="d-flex gap-2" method="GET" action="{{ route('inventory.teams.index') }}">
                <input class="form-control form-control-sm" name="q" value="{{ $q }}" placeholder="Search name/code..." style="min-width: 220px;">
                <select class="form-select form-select-sm" name="status" style="min-width: 150px;">
                    <option value="active" @selected($status==='active')>Active</option>
                    <option value="inactive" @selected($status==='inactive')>Inactive</option>
                    <option value="all" @selected($status==='all')>All</option>
                </select>
                <button class="btn btn-sm btn-dark">Filter</button>
                @if($q !== '' || $status !== 'active')
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.teams.index') }}">Reset</a>
                @endif
            </form>

            <a class="btn btn-sm btn-primary" href="{{ route('inventory.teams.create') }}">+ New Team</a>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Team</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teams as $t)
                        <tr>
                            <td>{{ $t->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $t->name }}</div>
                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($t->description, 80) }}</small>
                            </td>
                            <td>{{ $t->code ?? '—' }}</td>
                            <td>
                                @if($t->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $t->creator?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.teams.edit', $t) }}">Manage</a>
                                <form class="d-inline" method="POST" action="{{ route('inventory.teams.destroy', $t) }}" onsubmit="return confirm('Delete this team?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No teams yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $teams->links() }}
    </div>
</div>
@endsection
