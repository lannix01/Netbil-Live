@extends('inventory::layout')

@section('inventory-content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Deploy From Team Stock (Bulk)</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.team_deployments.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Team</label>
                        <select class="form-select" name="team_id" id="teamSelect" required onchange="syncTeamItems()">
                            <option value="">-- Select Team --</option>
                            @foreach($teams as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} {{ $t->code ? '(' . $t->code . ')' : '' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Deployment reduces team available allocation.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Item</label>
                        <select class="form-select" name="item_id" id="itemSelect" required>
                            <option value="">-- Select Team first --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Qty</label>
                        <input class="form-control" type="number" min="1" name="qty" value="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Site Name</label>
                        <input class="form-control" name="site_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Site Code (optional)</label>
                        <input class="form-control" name="site_code">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reference (optional)</label>
                        <input class="form-control" name="reference">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>

                    <button class="btn btn-dark w-100">Deploy</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Notes</strong></div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    This is bulk deployment from a team pool. Serialized deployment from team pool comes next (requires team-based unit assignment).
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header"><strong>Quick Links</strong></div>
            <div class="card-body d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-secondary" href="{{ route('inventory.team_assignments.index') }}">Team Assignments</a>
                <a class="btn btn-outline-dark" href="{{ route('inventory.logs.index') }}">Logs</a>
            </div>
        </div>
    </div>
</div>

<script>
const teamAssignments = @json($teamAssignments);

function syncTeamItems() {
    const teamId = document.getElementById('teamSelect').value;
    const itemSelect = document.getElementById('itemSelect');
    itemSelect.innerHTML = '';

    if (!teamId || !teamAssignments[teamId] || teamAssignments[teamId].length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- No assigned items for this team --';
        itemSelect.appendChild(opt);
        return;
    }

    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = '-- Select Item --';
    itemSelect.appendChild(opt0);

    teamAssignments[teamId].forEach(i => {
        const opt = document.createElement('option');
        opt.value = i.item_id;
        opt.textContent = `${i.item_name} (Available: ${i.available})`;
        itemSelect.appendChild(opt);
    });
}
</script>
@endsection