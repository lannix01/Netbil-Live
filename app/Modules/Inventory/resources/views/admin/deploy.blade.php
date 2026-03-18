@extends('inventory::layout')

@section('page-title', 'Admin Deployment')
@section('page-subtitle', 'Deploy under a technician’s allocation (bulk or serialized). Everything is audited.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.deployments.index') }}" data-inv-loading>Deployments List</a>
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.logs.index') }}" data-inv-loading>Logs</a>
    <a class="btn btn-outline-primary btn-sm" href="{{ route('inventory.assignments.index') }}" data-inv-loading>Assignments</a>
@endsection

@section('inventory-content')
@php
    $technicians = $technicians ?? collect();
    $assignments = $assignments ?? collect();
    $assignedUnits = $assignedUnits ?? [];

    $hasErr = fn($key) => $errors->has($key);
    $errMsg = fn($key) => $errors->first($key);

    // Old input preservation (after validation failure)
    $oldTech = old('technician_id', request()->query('technician_id'));
    $oldItem = old('item_id', request()->query('item_id'));
    $oldQty = old('qty', request()->query('qty', 1));
    $oldUnit = old('unit_id', request()->query('unit_id'));

    $oldSite = old('site_name', request()->query('site_name'));
    $oldSiteCode = old('site_code', request()->query('site_code'));
    $oldRef = old('reference', request()->query('reference'));
    $oldNotes = old('notes', request()->query('notes'));

    $loading = request()->boolean('loading');
@endphp

<style>
    /* Admin deploy (scoped) */
    .inv-panel{ padding:0; overflow:hidden; }
    .inv-panel-head{
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        background: #fbfcff;
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
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

    .inv-note{
        padding: 14px 16px;
        border-radius: 14px;
        border: 1px solid var(--infoBd);
        background: var(--infoBg);
        color: var(--infoTx);
        font-size: 13px;
        line-height: 1.45;
        font-weight: 700;
    }

    .inv-warn{
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid var(--warnBd);
        background: var(--warnBg);
        color: var(--warnTx);
        font-size: 12px;
        line-height: 1.45;
        font-weight: 900;
    }

    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }

    #serialSelect{
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
    }
</style>

<div class="row g-3">
    {{-- Left: Form --}}
    <div class="col-lg-5">
        <div class="inv-card inv-panel">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Admin Deployment</p>
                    <div class="inv-panel-sub">Deploy stock under a technician (counts against their allocation).</div>
                </div>
                <span class="inv-chip">Admin</span>
            </div>

            {{-- Optional skeleton preview: ?loading=1 --}}
            <div class="inv-skel {{ $loading ? 'show' : '' }}">
                <div class="inv-skeleton inv-skel-row lg" style="width:220px;"></div>
                <div class="inv-skeleton inv-skel-row" style="width:340px;"></div>
                <div class="inv-divider"></div>
                @for($i=0;$i<6;$i++)
                    <div class="inv-skeleton inv-skel-row"></div>
                @endfor
            </div>

            @if(!$loading)
                <div class="inv-panel-body">
                    <form method="POST" action="{{ route('inventory.deployments.store') }}" data-inv-loading>
                        @csrf

                        <div class="inv-field">
                            <label class="form-label">Technician (deploying under) <span class="text-danger">*</span></label>
                            <select
                                class="form-select {{ $hasErr('technician_id') ? 'inv-input-invalid' : '' }}"
                                name="technician_id"
                                id="techSelect"
                                required
                                onchange="syncItemsForTech()"
                            >
                                <option value="">-- Select Technician --</option>
                                @foreach($technicians as $t)
                                    <option value="{{ $t->id }}" @selected((string)$oldTech === (string)$t->id)>
                                        {{ $t->name }} ({{ $t->department?->name ?? 'No Dept' }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="inv-hint">Deployment will count against this technician’s available allocation.</div>
                            @if($hasErr('technician_id'))
                                <div class="inv-error">{{ $errMsg('technician_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Item <span class="text-danger">*</span></label>
                            <select
                                class="form-select {{ $hasErr('item_id') ? 'inv-input-invalid' : '' }}"
                                name="item_id"
                                id="itemSelect"
                                required
                                onchange="toggleDeployMode()"
                            >
                                <option value="">-- Select Technician first --</option>
                            </select>
                            <div class="inv-hint">Only items assigned to that technician will appear.</div>
                            @if($hasErr('item_id'))
                                <div class="inv-error">{{ $errMsg('item_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Deploy Mode</label>
                            <select class="form-select" id="modeSelect" onchange="toggleDeployMode()">
                                <option value="bulk">Bulk Qty</option>
                                <option value="serial">Serialized Unit</option>
                            </select>
                            <div class="inv-hint">Serialized items will auto-switch to Serial mode.</div>
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
                            <div class="inv-hint">Bulk mode moves quantity from technician allocation.</div>
                            @if($hasErr('qty'))
                                <div class="inv-error">{{ $errMsg('qty') }}</div>
                            @endif
                        </div>

                        <div class="inv-field d-none" id="serialBox">
                            <label class="form-label">Select Serial <span class="text-danger">*</span></label>
                            <select
                                class="form-select {{ $hasErr('unit_id') ? 'inv-input-invalid' : '' }}"
                                name="unit_id"
                                id="serialSelect"
                            >
                                <option value="">-- Select item first --</option>
                            </select>
                            <div class="inv-hint">Only serials assigned to that technician appear here.</div>
                            @if($hasErr('unit_id'))
                                <div class="inv-error">{{ $errMsg('unit_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Site Name <span class="text-danger">*</span></label>
                            <input
                                class="form-control {{ $hasErr('site_name') ? 'inv-input-invalid' : '' }}"
                                name="site_name"
                                value="{{ $oldSite }}"
                                required
                                placeholder="e.g. Gigiri Node 2"
                            >
                            @if($hasErr('site_name'))
                                <div class="inv-error">{{ $errMsg('site_name') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Site Code (optional)</label>
                            <input
                                class="form-control {{ $hasErr('site_code') ? 'inv-input-invalid' : '' }}"
                                name="site_code"
                                value="{{ $oldSiteCode }}"
                                placeholder="e.g. NBI-GGR-002"
                            >
                            @if($hasErr('site_code'))
                                <div class="inv-error">{{ $errMsg('site_code') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Reference (optional)</label>
                            <input
                                class="form-control {{ $hasErr('reference') ? 'inv-input-invalid' : '' }}"
                                name="reference"
                                value="{{ $oldRef }}"
                                placeholder="Ticket / job card / request…"
                            >
                            @if($hasErr('reference'))
                                <div class="inv-error">{{ $errMsg('reference') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Notes (optional)</label>
                            <textarea class="form-control {{ $hasErr('notes') ? 'inv-input-invalid' : '' }}" name="notes" rows="2">{{ $oldNotes }}</textarea>
                            @if($hasErr('notes'))
                                <div class="inv-error">{{ $errMsg('notes') }}</div>
                            @endif
                        </div>

                        <button class="btn btn-dark w-100">Deploy</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Right: How it works + links --}}
    <div class="col-lg-7">
        <div class="inv-card inv-panel">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">How it works</p>
                    <div class="inv-panel-sub">What will happen after you submit.</div>
                </div>
                <span class="inv-chip">Info</span>
            </div>
            <div class="inv-panel-body">
                <div class="inv-note">
                    <ul class="mb-0" style="margin-left: 18px;">
                        <li>Admin chooses a technician (deployment is recorded under them).</li>
                        <li>System checks allocation and prevents over-deploy.</li>
                        <li>Serialized deployment updates unit status to <code>deployed</code> and stores site fields.</li>
                        <li>Everything is written to <strong>Inventory Logs</strong>.</li>
                    </ul>
                </div>

                <div class="inv-divider"></div>

                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.deployments.index') }}" data-inv-loading>Deployments List</a>
                    <a class="btn btn-outline-dark" href="{{ route('inventory.logs.index') }}" data-inv-loading>Logs</a>
                    <a class="btn btn-outline-primary" href="{{ route('inventory.assignments.index') }}" data-inv-loading>Assignments</a>
                </div>
            </div>
        </div>

        <div class="inv-card inv-panel mt-3">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Admin safety</p>
                    <div class="inv-panel-sub">Avoid “wrong tech / wrong site” mistakes.</div>
                </div>
                <span class="inv-chip">⚠️</span>
            </div>
            <div class="inv-panel-body">
                <div class="inv-warn">
                    Double-check technician + item + site before deploying. This affects stock accountability and audits.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Keep your structure intact; just add old-value restoration + init.

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

    // Old values
    const oldTechId = @json($oldTech);
    const oldItemId = @json($oldItem);
    const oldUnitId = @json($oldUnit);

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

            if (oldItemId && String(oldItemId) === String(i.item_id)) {
                opt.selected = true;
            }

            itemSelect.appendChild(opt);
        });

        toggleDeployMode();
    }

    function toggleDeployMode() {
        const itemSelect = document.getElementById('itemSelect');
        const selected = itemSelect.options[itemSelect.selectedIndex];
        const hasSerial = selected && selected.dataset && selected.dataset.hasSerial === '1';

        const modeSelect = document.getElementById('modeSelect');
        const bulkBox = document.getElementById('bulkBox');
        const serialBox = document.getElementById('serialBox');
        const qtyInput = document.getElementById('qtyInput');
        const serialSelect = document.getElementById('serialSelect');

        // Auto-correct mode based on item type
        if (hasSerial && modeSelect.value !== 'serial') modeSelect.value = 'serial';
        if (!hasSerial && modeSelect.value === 'serial') modeSelect.value = 'bulk';

        const mode = modeSelect.value;

        if (mode === 'serial') {
            bulkBox.classList.add('d-none');
            serialBox.classList.remove('d-none');

            qtyInput.value = '';
            qtyInput.removeAttribute('required');

            serialSelect.setAttribute('required', 'required');
            loadSerialsForSelected();
        } else {
            serialBox.classList.add('d-none');
            bulkBox.classList.remove('d-none');

            serialSelect.innerHTML = '<option value="">-- Select item first --</option>';
            serialSelect.removeAttribute('required');

            qtyInput.setAttribute('required', 'required');
            if (!qtyInput.value) qtyInput.value = '1';
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

            if (oldUnitId && String(oldUnitId) === String(u.id)) {
                opt.selected = true;
            }

            serialSelect.appendChild(opt);
        });
    }

    document.addEventListener('DOMContentLoaded', function(){
        const techSelect = document.getElementById('techSelect');
        if(techSelect && oldTechId){
            techSelect.value = String(oldTechId);
        }
        syncItemsForTech();
    });
</script>
@endsection
