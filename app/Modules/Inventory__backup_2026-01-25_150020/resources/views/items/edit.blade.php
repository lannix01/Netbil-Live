@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header"><strong>Edit Item</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('inventory.items.update', $item) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Group</label>
                <select class="form-select" name="item_group_id" required>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" @selected(old('item_group_id', $item->item_group_id)==$g->id)>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Item Name</label>
                <input class="form-control" name="name" value="{{ old('name', $item->name) }}" required />
            </div>

            <div class="mb-3">
                <label class="form-label">SKU (optional)</label>
                <input class="form-control" name="sku" value="{{ old('sku', $item->sku) }}" />
            </div>

            <div class="mb-3">
                <label class="form-label">Unit</label>
                <input class="form-control" name="unit" value="{{ old('unit', $item->unit) }}" required />
            </div>

            <div class="mb-3">
                <label class="form-label">Has Serial Numbers?</label>
                <select class="form-select" name="has_serial">
                    <option value="0" @selected(old('has_serial', $item->has_serial ? 1 : 0)==0)>No</option>
                    <option value="1" @selected(old('has_serial', $item->has_serial ? 1 : 0)==1)>Yes</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Reorder Level</label>
                <input class="form-control" type="number" min="0" name="reorder_level" value="{{ old('reorder_level', $item->reorder_level) }}" required />
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $item->description) }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Active?</label>
                <select class="form-select" name="is_active">
                    <option value="1" @selected(old('is_active', $item->is_active ? 1 : 0)==1)>Yes</option>
                    <option value="0" @selected(old('is_active', $item->is_active ? 1 : 0)==0)>No</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary">Update</button>
                <a class="btn btn-outline-secondary" href="{{ route('inventory.items.index') }}">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection
