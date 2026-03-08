@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header"><strong>Create Item</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('inventory.items.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Group</label>
                <select class="form-select" name="item_group_id" required>
                    <option value="">-- Select Group --</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" @selected(old('item_group_id')==$g->id)>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Item Name</label>
                <input class="form-control" name="name" value="{{ old('name') }}" required />
            </div>

            <div class="mb-3">
                <label class="form-label">SKU (optional)</label>
                <input class="form-control" name="sku" value="{{ old('sku') }}" />
            </div>

            <div class="mb-3">
                <label class="form-label">Unit (pcs, meters, rolls...)</label>
                <input class="form-control" name="unit" value="{{ old('unit', 'pcs') }}" required />
            </div>

            <div class="mb-3">
                <label class="form-label">Has Serial Numbers?</label>
                <select class="form-select" name="has_serial">
                    <option value="0" @selected(old('has_serial')==='0')>No</option>
                    <option value="1" @selected(old('has_serial')==='1')>Yes</option>
                </select>
                <small class="text-muted">If Yes, receiving/assigning/deploying will track serial numbers.</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Reorder Level</label>
                <input class="form-control" type="number" min="0" name="reorder_level" value="{{ old('reorder_level', 0) }}" required />
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Active?</label>
                <select class="form-select" name="is_active">
                    <option value="1" selected>Yes</option>
                    <option value="0">No</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary">Save</button>
                <a class="btn btn-outline-secondary" href="{{ route('inventory.items.index') }}">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection
