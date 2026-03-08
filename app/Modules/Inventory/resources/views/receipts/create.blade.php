@extends('inventory::layout')

@section('page-title', 'Receive Stock')
@section('page-subtitle', 'Record incoming stock into store. Serialized items require one serial per unit.')

@section('page-actions')
    <a class="inv-pillbtn" href="{{ route('inventory.receipts.index') }}" data-inv-loading>
        <i class="bi bi-receipt"></i> Receipts
    </a>
@endsection

@section('inventory-content')
@php
    $items = $items ?? collect();

    // Map item_id => has_serial for JS
    $itemFlags = $items->mapWithKeys(fn($i) => [$i->id => [
        'name' => $i->name,
        'has_serial' => (bool)$i->has_serial,
    ]]);

    $defaultDate = old('received_date', now()->toDateString());
@endphp

<style>
    .rc-wrap{ display:grid; gap:14px; }
    .rc-card{ padding:16px; border-radius:16px; border:1px solid var(--border); background:#fff; box-shadow:0 12px 28px rgba(2,6,23,.06); }
    .rc-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
    .rc-title{ font-weight:900; margin:0; font-size:14px; }
    .rc-sub{ color:var(--muted); font-size:12px; margin-top:4px; }

    .rc-line{ border:1px solid var(--border); border-radius:16px; padding:14px; background:#fff; box-shadow:0 10px 22px rgba(2,6,23,.05); }
    .rc-line-top{ display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .rc-badge{ display:inline-flex; gap:8px; align-items:center; font-size:12px; font-weight:900; color:#0b1220; }
    .rc-badge i{ opacity:.85; }
    .rc-remove{ border-radius:12px; }

    .rc-serials{ margin-top:10px; padding-top:10px; border-top:1px dashed rgba(2,6,23,.14); display:none; }
    .rc-serials.show{ display:block; }
    .rc-serial-grid{ display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:10px; }
    @media(max-width: 991.98px){ .rc-serial-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media(max-width: 575.98px){ .rc-serial-grid{ grid-template-columns: 1fr; } }

    .rc-help{ font-size:12px; color:var(--muted); margin-top:6px; }
</style>

<div class="rc-wrap">

    <div class="rc-card">
        <div class="rc-head">
            <div>
                <p class="rc-title mb-0">Receipt details</p>
                <div class="rc-sub">Fill header once, then add lines. Serialized items will request serial numbers.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('inventory.receipts.store') }}" data-inv-loading>
            @csrf

            <div class="row g-3 mt-1">
                <div class="col-md-3">
                    <label class="form-label">Received Date</label>
                    <input class="form-control" type="date" name="received_date" value="{{ $defaultDate }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Reference (optional)</label>
                    <input class="form-control" name="reference" value="{{ old('reference') }}" placeholder="e.g. PO-2026-001">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Supplier (optional)</label>
                    <input class="form-control" name="supplier" value="{{ old('supplier') }}" placeholder="Supplier name">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Notes (optional)</label>
                    <input class="form-control" name="notes" value="{{ old('notes') }}" placeholder="Any notes">
                </div>
            </div>

            <div class="inv-divider"></div>

            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div>
                    <p class="rc-title mb-0">Receipt lines</p>
                    <div class="rc-sub">Add one or more items with received quantity.</div>
                </div>
                <button type="button" class="inv-pillbtn inv-pillbtn--dark" id="addLineBtn">
                    <i class="bi bi-plus-circle"></i> Add line
                </button>
            </div>

            <div class="mt-3 d-grid gap-3" id="linesBox"></div>

            <div class="inv-divider"></div>

            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-dark">
                    <i class="bi bi-inbox-arrow-down"></i> Receive Stock
                </button>
                <a class="btn btn-outline-secondary" href="{{ route('inventory.receipts.index') }}" data-inv-loading>
                    Back
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    const itemFlags = @json($itemFlags);
    const oldLines = @json(old('lines', []));
    const linesBox = document.getElementById('linesBox');
    const addBtn = document.getElementById('addLineBtn');

    function makeOptionItems(selectedId = '') {
        let html = `<option value="">-- Select Item --</option>`;
        Object.keys(itemFlags).forEach(id => {
            const it = itemFlags[id];
            const sel = String(selectedId) === String(id) ? 'selected' : '';
            const tag = it.has_serial ? ' [SERIAL]' : '';
            html += `<option value="${id}" ${sel}>${it.name}${tag}</option>`;
        });
        return html;
    }

    function createSerialInputs(lineIndex, count, existing = []) {
        const safeCount = Math.max(0, parseInt(count || 0, 10) || 0);
        const html = [];
        for (let i = 0; i < safeCount; i++) {
            const val = existing[i] ? String(existing[i]) : '';
            html.push(`
                <div>
                    <label class="form-label">Serial ${i + 1}</label>
                    <input class="form-control" name="lines[${lineIndex}][serials][]" value="${val.replace(/"/g,'&quot;')}" placeholder="Enter serial">
                </div>
            `);
        }
        return html.join('');
    }

    function addLine(prefill = {}) {
        const lineIndex = document.querySelectorAll('[data-line]').length;

        const itemId = prefill.item_id || '';
        const qty = prefill.qty_received || 1;
        const unitCost = prefill.unit_cost || '';
        const serials = Array.isArray(prefill.serials) ? prefill.serials : [];

        const el = document.createElement('div');
        el.className = 'rc-line';
        el.dataset.line = '1';
        el.dataset.idx = lineIndex;

        el.innerHTML = `
            <div class="rc-line-top">
                <div class="rc-badge">
                    <i class="bi bi-bag"></i> Line #${lineIndex + 1}
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm rc-remove">
                    <i class="bi bi-trash"></i>
                </button>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-lg-6">
                    <label class="form-label">Item</label>
                    <select class="form-select js-item" name="lines[${lineIndex}][item_id]" required>
                        ${makeOptionItems(itemId)}
                    </select>
                    <div class="rc-help js-itemHelp"></div>
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Qty received</label>
                    <input class="form-control js-qty" type="number" min="1" name="lines[${lineIndex}][qty_received]" value="${qty}" required>
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Unit cost (optional)</label>
                    <input class="form-control" type="number" step="0.01" min="0" name="lines[${lineIndex}][unit_cost]" value="${unitCost}">
                </div>
            </div>

            <div class="rc-serials js-serialBox">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <div>
                        <div style="font-weight:900; font-size:13px;">Serial numbers</div>
                        <div class="rc-help">This item is serialized. Provide one serial per unit received.</div>
                    </div>
                </div>

                <div class="mt-2 rc-serial-grid js-serialGrid"></div>
            </div>
        `;

        const btnRemove = el.querySelector('.rc-remove');
        const selItem = el.querySelector('.js-item');
        const qtyInput = el.querySelector('.js-qty');
        const serialBox = el.querySelector('.js-serialBox');
        const serialGrid = el.querySelector('.js-serialGrid');
        const itemHelp = el.querySelector('.js-itemHelp');

        function refreshSerialUI() {
            const selected = selItem.value;
            const it = itemFlags[selected];
            const hasSerial = it && it.has_serial;
            const qtyVal = parseInt(qtyInput.value || '0', 10) || 0;

            if (hasSerial) {
                serialBox.classList.add('show');
                itemHelp.textContent = `Serialized item: ${it.name}. Qty = ${qtyVal}. Serials required = ${qtyVal}.`;
                serialGrid.innerHTML = createSerialInputs(lineIndex, qtyVal, serials);
            } else {
                serialBox.classList.remove('show');
                itemHelp.textContent = selected ? `Bulk item: ${it ? it.name : ''}. Serials not needed.` : '';
                serialGrid.innerHTML = '';
            }
        }

        selItem.addEventListener('change', refreshSerialUI);
        qtyInput.addEventListener('input', refreshSerialUI);

        btnRemove.addEventListener('click', () => el.remove());

        linesBox.appendChild(el);
        refreshSerialUI();
    }

    addBtn.addEventListener('click', () => addLine({ qty_received: 1 }));

    if (oldLines.length) {
        oldLines.forEach(l => addLine(l));
    } else {
        addLine({ qty_received: 1 });
    }
</script>
@endsection
