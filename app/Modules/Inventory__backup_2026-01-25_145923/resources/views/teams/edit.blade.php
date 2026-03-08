@extends('inventory::layout')

@section('inventory-content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <strong>Edit Team</strong>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.teams.index') }}">Back</a>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('inventory.teams.update', $team) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Team Name</label>
                        <input class="form-control" name="name" value="{{ old('name', $team->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Code (optional)</label>
                        <input class="form-control" name="code" value="{{ old('code', $team->code) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description (optional)</label>
                        <textarea class="form-control" name="description" rows="3">{{ old('description', $team->description) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Active?</label>
                        <select class="form-select" name="is_active">
                            <option value="1" @selected($team->is_active)>Yes</option>
                            <option value="0" @selected(!$team->is_active)>No</option>
                        </select>
                    </div>

                    <button class="btn btn-primary">Save</button>
                </form>

                <hr>

                <strong>Add Member</strong>
                <form method="POST" action="{{ route('inventory.teams.members.store', $team) }}" class="mt-2">
                    @csrf

                    <div class="mb-2">
                        <label class="form-label">Technician</label>
                        <select class="form-select" name="technician_id" required>
                            <option value="">-- Select Technician --</option>
                            @foreach($technicians as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->department?->name ?? 'No Dept' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="member" selected>Member</option>
                            <option value="leader">Leader</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Active?</label>
                        <select class="form-select" name="is_active">
                            <option value="1" selected>Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>

                    <button class="btn btn-dark w-100">Add / Update Member</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Members</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Technician</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-end">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($team->members as $m)
                                <tr>
                                    <td>{{ $m->technician?->name ?? '—' }}</td>
                                    <td>
                                        @if($m->role === 'leader')
                                            <span class="badge bg-primary">Leader</span>
                                        @else
                                            <span class="badge bg-secondary">Member</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($m->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('inventory.teams.members.destroy', [$team, $m]) }}" onsubmit="return confirm('Remove this member?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No members yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header"><strong>Next</strong></div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    Next we will update Assignments + Deployments to allow selecting <strong>Team</strong> (system can assign to team, then members can deploy).
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
