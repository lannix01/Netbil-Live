@extends('inventory::layout')

@section('inventory-content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Assign Items to Team (Bulk)</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.team_assignments.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Team</label>
                        <select class="form-select" name="team_id" required>
                            <option value="">-- Select Team --</option>
                            @foreach($teams as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} {{ $t->code ? '(' . $t->code . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Item</label>
                        <select class="form-select" name="item_id" required>
                            <option value="">-- Select Item --</option>
                            @foreach($items as $i)
                                <option value="{{ $i->id }}">{{ $i->name }} (Store: {{ $i->qty_on_hand }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Team assignment is bulk (serialized to teams comes next).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Qty to Assign</label>
                        <input class="form-control" type="number" min="1" name="qty_allocated" value="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reference (optional)</label>
                        <input class="form-control" name="reference">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>

                    <button class="btn btn-dark w-100">Assign to Team</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header"><strong>Quick Links</strong></div>
            <div class="card-body d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-secondary" href="{{ route('inventory.team_deployments.index') }}">Team Deploy</a>
                <a class="btn btn-outline-dark" href="{{ route('inventory.logs.index') }}">Logs</a>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Team Assignments</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>Item</th>
                                <th>Allocated</th>
                                <th>Deployed</th>
                                <th>Available</th>
                                <th>Assigned By</th>
                                <th>Assigned At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $a)
                                @php
                                    $item = \DB::table('inventory_items')->where('id', $a->item_id)->first();
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $a->team?->name }}</div>
                                        <small class="text-muted">{{ $a->team?->code }}</small>
                                    </td>
                                    <td>{{ $item?->name ?? ('Item #' . $a->item_id) }}</td>
                                    <td>{{ $a->qty_allocated }}</td>
                                    <td>{{ $a->qty_deployed }}</td>
                                    <td><span class="badge bg-dark">{{ $a->availableToDeploy() }}</span></td>
                                    <td>{{ $a->assigner?->name ?? '—' }}</td>
                                    <td>{{ $a->assigned_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No team assignments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $assignments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection