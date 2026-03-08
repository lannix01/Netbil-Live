@extends('inventory::layout')

@section('inventory-content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Admin Deployment</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.deployments.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Technician (deploying under)</label>
                        <select class="form-select" name="technician_id" id="techSelect" required onchange="syncItemsForTech()">
                            <option value="">-- Select Technician --</option>
                            @foreach($technicians as $t)
                                <option value="{{ $t->id }}">
                                    {{ $t->name }} ({{ $t->department?->name ?? 'No Dept' }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Deployment will count against that technician’s allocated stock.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Item</label>
                        <select class="form-select" name="item_id" id="itemSelect" required onchange="toggleDeployMode()">
                            <option value="">-- Select Technician first --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deploy Mode</label>
                        <select class="form-select" id="modeSelect" onchange="toggleDeployMode()">
                            <option value="bulk">Bulk Qty</option>
                            <option value="serial">Serialized Unit</option>
                        </select>
                        <small class="text-muted">If item is non-serialized, use Bulk. If serialized, use Serial mode.</small>
                    </div>

                    <div class="mb-3" id="bulkBox">
                        <label class="form-label">Qty</label>
                        <input class="form-control" type="number" min="1" name="qty" id="qtyInput" value="1">
                    </div>

                    <div class="mb-3 d-none" id="serialBox">
                        <label class="form-label">Select Serial</label>
                        <select class="form-select" name="unit_id" id="serialSelect">
                            <option value="">-- Select item first --</option>
                        </select>
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
            <div class="card-header"><strong>How it works</strong></div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <ul class="mb-0">
                        <li>Admin chooses a technician (deployment is recorded under them).</li>
                        <li>System checks allocation and prevents over-deploy.</li>
                        <li>Serialized deployment updates the unit status to <code>deployed</code> and stores site fields.</li>
                        <li>Everything is written to <strong>Inventory Logs</strong>.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header"><strong>Quick links</strong></div>
            <div class="card-body d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-secondary" href="{{ route('inventory.deployments.index') }}">Deployments List</a>
                <a class="btn btn-outline-dark" href="{{ route('inventory.logs.index') }}">Logs</a>
                <a class="btn btn-outline-primary" href="{{ route('inventory.assignments.index') }}">Assignments</a>
            </div>
        </div>
    </div>
</div>

<script>
const assignmentsByTech = @json(
    $assignments->map(function($rows) {
        return $rows->map(function($a) {
            return [
                'item_id' => $a->item_id,
                'item_name' => $a->item?->name,
                'available' => $a->availableToDeploy(),
                'has_serial' => (bool)($a->item?->has_serial),
            ];
        })->values();
    })
);

const assignedUnitsByTech = @json($assignedUnits);

function syncItemsForTech() {
    const techId = document.getElementById('techSelect').value;
    const itemSelect = document.getElementById('itemSelect');
    itemSelect.innerHTML = '';

    if (!techId || !assignmentsByTech[techId] || assignmentsByTech[techId].length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- No assigned items for this technician --';
        itemSelect.appendChild(opt);
        toggleDeployMode();
        return;
    }

    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = '-- Select Item --';
    itemSelect.appendChild(opt0);

    assignmentsByTech[techId].forEach(i => {
        const opt = document.createElement('option');
        opt.value = i.item_id;
        opt.textContent = `${i.item_name} (Available: ${i.available}) ${i.has_serial ? '[SERIAL]' : ''}`;
        opt.dataset.hasSerial = i.has_serial ? '1' : '0';
        itemSelect.appendChild(opt);
    });

    toggleDeployMode();
}

function toggleDeployMode() {
    const itemSelect = document.getElementById('itemSelect');
    const selected = itemSelect.options[itemSelect.selectedIndex];
    const hasSerial = selected && selected.dataset && selected.dataset.hasSerial === '1';

    const mode = document.getElementById('modeSelect').value;

    const bulkBox = document.getElementById('bulkBox');
    const serialBox = document.getElementById('serialBox');
    const qtyInput = document.getElementById('qtyInput');
    const serialSelect = document.getElementById('serialSelect');

    // If item is serialized, default to serial mode.
    if (hasSerial && mode !== 'serial') {
        document.getElementById('modeSelect').value = 'serial';
    }
    if (!hasSerial && mode === 'serial') {
        document.getElementById('modeSelect').value = 'bulk';
    }

    const effectiveMode = document.getElementById('modeSelect').value;

    if (effectiveMode === 'serial') {
        bulkBox.classList.add('d-none');
        serialBox.classList.remove('d-none');
        qtyInput.value = '';
        qtyInput.removeAttribute('required');
        serialSelect.setAttribute('required', 'required');
        loadSerialsForSelected();
    } else {
        serialBox.classList.add('d-none');
        bulkBox.classList.remove('d-none');
        serialSelect.value = '';
        serialSelect.removeAttribute('required');
        qtyInput.setAttribute('required', 'required');
    }
}

function loadSerialsForSelected() {
    const techId = document.getElementById('techSelect').value;
    const itemId = document.getElementById('itemSelect').value;
    const serialSelect = document.getElementById('serialSelect');

    serialSelect.innerHTML = '';

    const list = (assignedUnitsByTech[techId] && assignedUnitsByTech[techId][itemId]) ? assignedUnitsByTech[techId][itemId] : [];

    if (!techId || !itemId || list.length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- No assigned serials for this item --';
        serialSelect.appendChild(opt);
        return;
    }

    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = '-- Select Serial --';
    serialSelect.appendChild(opt0);

    list.forEach(u => {
        const opt = document.createElement('option');
        opt.value = u.id;
        opt.textContent = u.serial_no;
        serialSelect.appendChild(opt);
    });
}
</script>
@endsection
