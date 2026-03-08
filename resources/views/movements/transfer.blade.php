@extends('inventory::layout')

@section('page-title', 'Transfer Tech ➜ Tech')
@section('page-subtitle', 'Move available allocation between technicians. Deployed stock cannot be transferred.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.movements.index') }}" data-inv-loading>Movements List</a>
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.logs.index') }}" data-inv-loading>Logs</a>
@endsection

@section('inventory-content')
@php
    $technicians = $technicians ?? collect();
    $assignmentsByTech = $assignmentsByTech ?? [];
    $assignedUnitsByTech = $assignedUnitsByTech ?? [];

    $hasErr = fn($key) => $errors->has($key);
    $errMsg = fn($key) => $errors->first($key);

    // Old input preservation
    $oldFrom = old('from_user_id');
    $oldTo = old('to_user_id');
    $oldItem = old('item_id');
    $oldQty = old('qty', 1);
    $oldUnit = old('item_unit_id');
    $oldNotes = old('notes');

    // Optional skeleton preview: /inventory/movements/transfer?loading=1
    $loading = request()->boolean('loading');
@endphp

<style>
    /* Movement transfer (scoped) */
    .inv-panel{ padding:0; overflow:hidden; }
    .inv-panel-head{
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        background: #fbfcff;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        flex-wrap:wrap;
    }
    .inv-panel-title{ font-weight: 900; margin:0; font-size: 14px; }
    .inv-panel-sub{ color: var(--muted); font-size: 12px; margin-top: 3px; }
    .inv-panel-body{ padding: 16px; }

    .inv-field{
        background:#fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 12px;
        margin-bottom: 12px;
    }
    .inv-field .form-label{
        font-weight: 900;
        font-size: 12px;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 6px;
    }
    .inv-hint{
        font-size: 12px;
        color: var(--muted);
        margin-top: 6px;
        line-height: 1.35;
    }
    .inv-error{
        margin-top: 6px;
        font-size: 12px;
        color: #b91c1c;
        font-weight: 700;
    }
    .inv-input-invalid{
        border-color: rgba(220,38,38,.55) !important;
        box-shadow: 0 0 0 .15rem rgba(220,38,38,.10) !important;
    }

    .inv-btn-wide{ width:100%; border-radius: 14px; font-weight: 900; }

    .inv-note{
        padding: 14px 16px;
        border-radius: 14px;
        border: 1px solid var(--infoBd);
        background: var(--infoBg);
        color: var(--infoTx);
        font-size: 13px;
        line-height: 1.4;
        font-weight: 700;
    }

    .inv-warn{
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid var(--warnBd);
        background: var(--warnBg);
        color: var(--warnTx);
        font-size: 12px;
        line-height: 1.4;
        font-weight: 800;
        display:none;
    }
    .inv-warn.show{ display:block; }

    #serialSelect{
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
    }

    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }
</style>

<div class="row g-3">
    {{-- Left: form --}}
    <div class="col-lg-5">
        <div class="inv-card inv-panel">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Transfer (Tech ➜ Tech)</p>
                    <div class="inv-panel-sub">Choose source technician, destination technician, and what to transfer.</div>
                </div>
                <span class="inv-chip">Transfer</span>
            </div>

            <div class="inv-panel-body">
                @if($technicians->count() < 2)
                    <div class="inv-empty">
                        <div class="inv-empty-ico">🧑‍🔧</div>
                        <p class="inv-empty-title mb-0">Not enough technicians</p>
                        <div class="inv-empty-sub">
                            You need at least two technicians to perform a transfer.
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('inventory.movements.store') }}" data-inv-loading>
                        @csrf
                        <input type="hidden" name="type" value="transfer">

                        <div id="sameTechWarn" class="inv-warn">
                            From Technician and To Technician cannot be the same.
                        </div>

                        <div class="inv-field">
                            <label class="form-label">From Technician <span class="text-danger">*</span></label>
                            <select
                                class="form-select {{ $hasErr('from_user_id') ? 'inv-input-invalid' : '' }}"
                                name="from_user_id"
                                id="fromTech"
                                required
                                onchange="loadFromTechItems(); validateTechs();"
                            >
                                <option value="">-- Select --</option>
                                @foreach($technicians as $t)
                                    <option value="{{ $t->id }}" @selected((string)$oldFrom === (string)$t->id)>
                                        {{ $t->name }} ({{ $t->department?->name ?? 'No Dept' }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="inv-hint">This is the source allocation you’re moving from.</div>
                            @if($hasErr('from_user_id'))
                                <div class="inv-error">{{ $errMsg('from_user_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">To Technician <span class="text-danger">*</span></label>
                            <select
                                class="form-select {{ $hasErr('to_user_id') ? 'inv-input-invalid' : '' }}"
                                name="to_user_id"
                                id="toTech"
                                required
                                onchange="validateTechs();"
                            >
                                <option value="">-- Select --</option>
                                @foreach($technicians as $t)
                                    <option value="{{ $t->id }}" @selected((string)$oldTo === (string)$t->id)>
                                        {{ $t->name }} ({{ $t->department?->name ?? 'No Dept' }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="inv-hint">Destination technician receiving the allocation.</div>
                            @if($hasErr('to_user_id'))
                                <div class="inv-error">{{ $errMsg('to_user_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Item <span class="text-danger">*</span></label>
                            <select
                                class="form-select {{ $hasErr('item_id') ? 'inv-input-invalid' : '' }}"
                                name="item_id"
                                id="itemSelect"
                                required
                                onchange="toggleModeForItem()"
                            >
                                <option value="">-- Select FROM tech first --</option>
                            </select>
                            <div class="inv-hint">Only items with available allocation will appear.</div>
                            @if($hasErr('item_id'))
                                <div class="inv-error">{{ $errMsg('item_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Mode</label>
                            <select class="form-select" id="modeSelect" onchange="toggleModeForItem()">
                                <option value="bulk">Bulk Qty</option>
                                <option value="serial">Serialized Unit</option>
                            </select>
                            <div class="inv-hint">
                                If the selected item is serial-tracked, mode will auto-switch to Serialized.
                            </div>
                        </div>

                        <div class="inv-field" id="bulkBox">
                            <label class="form-label">Qty <span class="text-danger">*</span></label>
                            <input
                                class="form-control {{ $hasErr('qty') ? 'inv-input-invalid' : '' }}"
                                type="number"
                                min="1"
                                name="qty"
                                id="qtyInput"
                                value="{{ $oldQty }}"
                                required
                            >
                            <div class="inv-hint">Bulk transfer uses quantity.</div>
                            @if($hasErr('qty'))
                                <div class="inv-error">{{ $errMsg('qty') }}</div>
                            @endif
                        </div>

                        <div class="inv-field d-none" id="serialBox">
                            <label class="form-label">Select Serial (one per transfer) <span class="text-danger">*</span></label>
                            <select
                                class="form-select {{ $hasErr('item_unit_id') ? 'inv-input-invalid' : '' }}"
                                name="item_unit_id"
                                id="serialSelect"
                            >
                                <option value="">-- Select item first --</option>
                            </select>
                            <div class="inv-hint">Serialized transfers move one unit at a time.</div>
                            @if($hasErr('item_unit_id'))
                                <div class="inv-error">{{ $errMsg('item_unit_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Notes (optional)</label>
                            <textarea class="form-control {{ $hasErr('notes') ? 'inv-input-invalid' : '' }}" name="notes" rows="2">{{ $oldNotes }}</textarea>
                            @if($hasErr('notes'))
                                <div class="inv-error">{{ $errMsg('notes') }}</div>
                            @endif
                        </div>

                        <button class="btn btn-dark inv-btn-wide" id="submitBtn">Transfer</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Right: tips + links --}}
    <div class="col-lg-7">
        <div class="inv-card inv-panel">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Tip</p>
                    <div class="inv-panel-sub">How transfers behave</div>
                </div>
                <span class="inv-chip">Info</span>
            </div>
            <div class="inv-panel-body">
                <div class="inv-note">
                    Transfers only move <strong>available</strong> allocation (allocated − deployed). Deployed stock cannot be transferred.
                </div>

                <div class="inv-divider"></div>

                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.movements.index') }}" data-inv-loading>Movements List</a>
                    <a class="btn btn-outline-dark" href="{{ route('inventory.logs.index') }}" data-inv-loading>Logs</a>
                </div>
            </div>
        </div>

        <div class="inv-card inv-panel mt-3">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Sanity check</p>
                    <div class="inv-panel-sub">Avoid avoidable mistakes</div>
                </div>
                <span class="inv-chip">⚠️</span>
            </div>
            <div class="inv-panel-body">
                <div class="inv-muted" style="font-size:13px;">
                    • Don’t transfer to the same technician<br>
                    • Pick items with available allocation<br>
                    • Serialized transfers are one unit per movement
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const assignmentsByTech = @json($assignmentsByTech);
    const assignedUnitsByTech = @json($assignedUnitsByTech);

    const oldFrom = @json($oldFrom);
    const oldTo = @json($oldTo);
    const oldItem = @json($oldItem);
    const oldUnit = @json($oldUnit);

    function validateTechs(){
        const fromId = document.getElementById('fromTech')?.value || '';
        const toId = document.getElementById('toTech')?.value || '';
        const warn = document.getElementById('sameTechWarn');
        const btn = document.getElementById('submitBtn');

        const same = fromId && toId && String(fromId) === String(toId);
        if(warn) warn.classList.toggle('show', !!same);
        if(btn) btn.disabled = !!same;
    }

    function loadFromTechItems() {
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

            if (oldItem && String(oldItem) === String(i.item_id)) {
                opt.selected = true;
            }

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
            if (!qtyInput.value) qtyInput.value = '1';
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

            if (oldUnit && String(oldUnit) === String(u.id)) {
                opt.selected = true;
            }

            serialSelect.appendChild(opt);
        });
    }

    // Initialize from old inputs (after validation errors)
    document.addEventListener('DOMContentLoaded', function(){
        const from = document.getElementById('fromTech');
        const to = document.getElementById('toTech');

        if(from && oldFrom) from.value = String(oldFrom);
        if(to && oldTo) to.value = String(oldTo);

        validateTechs();
        loadFromTechItems();
    });
</script>
@endsection
