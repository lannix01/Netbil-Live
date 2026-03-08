@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header"><strong>Edit Item Group</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('inventory.item-groups.update', $group) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input class="form-control" name="name" value="{{ old('name', $group->name) }}" required />
            </div>

            <div class="mb-3">
                <label class="form-label">Code (optional)</label>
                <input class="form-control" name="code" value="{{ old('code', $group->code) }}" />
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $group->description) }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary">Update</button>
                <a class="btn btn-outline-secondary" href="{{ route('inventory.item-groups.index') }}">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection
