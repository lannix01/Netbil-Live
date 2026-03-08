@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header"><strong>Receive Stock</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('inventory.receipts.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Reference (optional)</label>
                    <input class="form-control" name="reference" value="{{ old('reference') }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Received Date</label>
                    <input class="form-control" type="date" name="received_date" value="{{ old('received_date', date('Y-m-d')) }}" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Supplier (optional)</label>
                    <input class="form-control" name="supplier_name" value="{{ old('supplier_name') }}" />
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
            </div>

            <hr>

            <strong>Receipt Lines</strong>
            <small class="text-muted d-block mb-2">
                For serialized items, you will add serials later via API/UI improvement. For now receive bulk qty.
            </small>

            <div id="lines">
                <div class="row g-2 align-items-end mb-2 line">
                    <div class="col-md-8">
                        <label class="form-label">Item</label>
                        <select class="form-select" name="lines[0][item_id]" required>
                            <option value="">-- Select Item --</option>
                            @foreach($items as $i)
                                <option value="{{ $i->id }}">{{ $i->name }} ({{ $i->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Qty Received</label>
                        <input class="form-control" type="number" min="1" name="lines[0][qty_received]" value="1" required />
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger w-100" onclick="removeLine(this)">X</button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addLine()">+ Add Line</button>

            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary">Receive</button>
                <a class="btn btn-outline-secondary" href="{{ route('inventory.receipts.index') }}">Back</a>
            </div>
        </form>
    </div>
</div>

<script>
let lineIndex = 1;

function addLine() {
    const lines = document.getElementById('lines');
    const template = document.querySelector('.line').cloneNode(true);

    template.querySelectorAll('select,input').forEach(el => {
        if (el.name.includes('[item_id]')) el.name = `lines[${lineIndex}][item_id]`;
        if (el.name.includes('[qty_received]')) el.name = `lines[${lineIndex}][qty_received]`;
        if (el.tagName === 'INPUT') el.value = 1;
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
    });

    lines.appendChild(template);
    lineIndex++;
}

function removeLine(btn) {
    const all = document.querySelectorAll('.line');
    if (all.length <= 1) return;
    btn.closest('.line').remove();
}
</script>
@endsection
