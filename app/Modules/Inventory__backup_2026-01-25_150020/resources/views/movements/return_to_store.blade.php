@extends('inventory::layout')

@section('inventory-content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Return (Tech ➜ Store)</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.movements.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="return_to_store">

                    <div class="mb-3">
                        <label class="form-label">Technician</label>
                        <select class="form-select" name="from_user_id" id="fromTech" required onchange="loadTechItems()">
                            <option value="">-- Select --</option>
                            @foreach($technicians as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->department?->name ?? 'No Dept' }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Only available (not deployed) allocation can be returned.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Item</label>
                        <select class="form-select" name="item_id" id="itemSelect" required onchange="toggleModeForItem()">
                            <option value="">-- Select technician first --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mode</label>
                        <select class="form-select" id="modeSelect" onchange="toggleModeForItem()">
                            <option value="bulk">Bulk Qty</option>
                            <option value="serial">Serialized Unit</option>
                        </select>
                    </div>

                    <div class="mb-3" id="bulkBox">
                        <label class="form-label">Qty</label>
                        <input class="form-control" type="number" min="1" name="qty" id="qtyInput" value="1">
                    </div>

                    <div class="mb-3 d-none" id="serialBox">
                        <label class="form-label">Select Serial (one per return)</label>
                        <select class="form-select" name="item_unit_id" id="serialSelect">
                            <option value="">-- Select item first --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>

                    <button class="btn btn-dark w-100">Return to Store</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header"><strong>What happens</strong></div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    Returning increases <strong>store qty_on_hand</strong>, decreases technician allocation, updates unit status back to <code>in_store</code>, and writes logs.
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header"><strong>Quick Links</strong></div>
            <div class="card-body d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-secondary" href="{{ route('inventory.movements.index') }}">Movements List</a>
                <a class="btn btn-outline-dark" href="{{ route('inventory.logs.index') }}">Logs</a>
            </div>
        </div>
    </div>
</div>

<script>
const assignmentsByTech = @json($assignmentsByTech);
const assignedUnitsByTech = @json($assignedUnitsByTech);

function loadTechItems() {
    const fromId = document.getElementById('fromTech').value;
    const itemSelect = document.getElementById('itemSelect');
    itemSelect.innerHTML = '';

    if (!fromId || !assignmentsByTech[fromId] || assignmentsByTech[fromId].length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- No assigned items --';
        itemSelect.appendChild(opt);
        toggleModeForItem();
        return;
    }

    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = '-- Select Item --';
    itemSelect.appendChild(opt0);

    assignmentsByTech[fromId].forEach(i => {
        const opt = document.createElement('option');
        opt.value = i.item_id;
        opt.textContent = `${i.item_name} (Available: ${i.available}) ${i.has_serial ? '[SERIAL]' : ''}`;
        opt.dataset.hasSerial = i.has_serial ? '1' : '0';
        itemSelect.appendChild(opt);
    });

    toggleModeForItem();
}

function toggleModeForItem() {
    const fromId = document.getElementById('fromTech').value;
    const itemSelect = document.getElementById('itemSelect');
    const selected = itemSelect.options[itemSelect.selectedIndex];
    const hasSerial = selected && selected.dataset && selected.dataset.hasSerial === '1';

    const modeSelect = document.getElementById('modeSelect');
    if (hasSerial && modeSelect.value !== 'serial') modeSelect.value = 'serial';
    if (!hasSerial && modeSelect.value === 'serial') modeSelect.value = 'bulk';

    const mode = modeSelect.value;

    const bulkBox = document.getElementById('bulkBox');
    const serialBox = document.getElementById('serialBox');
    const qtyInput = document.getElementById('qtyInput');
    const serialSelect = document.getElementById('serialSelect');

    if (mode === 'serial') {
        bulkBox.classList.add('d-none');
        serialBox.classList.remove('d-none');
        qtyInput.value = '';
        qtyInput.removeAttribute('required');
        serialSelect.setAttribute('required', 'required');
        loadSerials(fromId, itemSelect.value);
    } else {
        serialBox.classList.add('d-none');
        bulkBox.classList.remove('d-none');
        serialSelect.innerHTML = '<option value="">-- Select item first --</option>';
        serialSelect.removeAttribute('required');
        qtyInput.setAttribute('required', 'required');
    }
}

function loadSerials(fromId, itemId) {
    const serialSelect = document.getElementById('serialSelect');
    serialSelect.innerHTML = '';

    const list = (assignedUnitsByTech[fromId] && assignedUnitsByTech[fromId][itemId]) ? assignedUnitsByTech[fromId][itemId] : [];

    if (!fromId || !itemId || list.length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- No assigned serials --';
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
