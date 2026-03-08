@extends('inventory::layout')

@section('inventory-content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header"><strong>Assign Bulk Items</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.assignments.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Technician</label>
                        <select class="form-select" name="technician_id" required>
                            <option value="">-- Select Technician --</option>
                            @foreach($technicians as $t)
                                <option value="{{ $t->id }}">
                                    {{ $t->name }} ({{ $t->department?->name ?? 'No Dept' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Item (Bulk)</label>
                        <select class="form-select" name="item_id" required>
                            <option value="">-- Select Item --</option>
                            @foreach($items->where('has_serial', false) as $i)
                                <option value="{{ $i->id }}">{{ $i->name }} (Store: {{ $i->qty_on_hand }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">This list only shows NON-serialized items.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Qty to Assign</label>
                        <input class="form-control" type="number" min="1" name="qty_allocated" value="1" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reference (optional)</label>
                        <input class="form-control" name="reference" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>

                    <button class="btn btn-primary w-100">Assign Bulk</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header"><strong>Assign Serialized Items</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.assignments.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Technician</label>
                        <select class="form-select" name="technician_id" required>
                            <option value="">-- Select Technician --</option>
                            @foreach($technicians as $t)
                                <option value="{{ $t->id }}">
                                    {{ $t->name }} ({{ $t->department?->name ?? 'No Dept' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Item (Serialized)</label>
                        <select class="form-select" name="item_id" id="serialItemSelect" required onchange="updateSerialOptions()">
                            <option value="">-- Select Serialized Item --</option>
                            @foreach($serialItems as $si)
                                <option value="{{ $si->id }}">{{ $si->name }} (Store: {{ $si->qty_on_hand }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Pick item then select serial(s) below.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Serial Numbers (multi-select)</label>
                        <select class="form-select" name="unit_ids[]" id="serialUnitsSelect" multiple size="8" required>
                            <option value="">-- Select item first --</option>
                        </select>
                        <small class="text-muted">Hold Ctrl/⌘ to select multiple serials.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reference (optional)</label>
                        <input class="form-control" name="reference" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>

                    <button class="btn btn-dark w-100">Assign Serialized</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Assignments</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Technician</th>
                                <th>Dept</th>
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
                                <tr>
                                    <td>{{ $a->technician?->name }}</td>
                                    <td>{{ $a->technician?->department?->name }}</td>
                                    <td>{{ $a->item?->name }}</td>
                                    <td>{{ $a->qty_allocated }}</td>
                                    <td>{{ $a->qty_deployed }}</td>
                                    <td><span class="badge bg-dark">{{ $a->availableToDeploy() }}</span></td>
                                    <td>{{ $a->assigner?->name }}</td>
                                    <td>{{ $a->assigned_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">No assignments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $assignments->links() }}
            </div>
        </div>
    </div>
</div>

<script>
const availableUnitsByItem = @json(
    $availableUnits->map(function($units) {
        return $units->map(function($u) {
            return ['id' => $u->id, 'serial_no' => $u->serial_no];
        })->values();
    })
);

function updateSerialOptions() {
    const itemId = document.getElementById('serialItemSelect').value;
    const select = document.getElementById('serialUnitsSelect');

    select.innerHTML = '';

    if (!itemId || !availableUnitsByItem[itemId] || availableUnitsByItem[itemId].length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- No available serials in store --';
        select.appendChild(opt);
        return;
    }

    availableUnitsByItem[itemId].forEach(u => {
        const opt = document.createElement('option');
        opt.value = u.id;
        opt.textContent = u.serial_no;
        select.appendChild(opt);
    });
}
</script>
@endsection
