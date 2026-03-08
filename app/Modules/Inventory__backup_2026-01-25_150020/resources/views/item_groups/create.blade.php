@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header"><strong>Create Item Group</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('inventory.item-groups.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input class="form-control" name="name" value="{{ old('name') }}" required />
            </div>

            <div class="mb-3">
                <label class="form-label">Code (optional)</label>
                <input class="form-control" name="code" value="{{ old('code') }}" />
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary">Save</button>
                <a class="btn btn-outline-secondary" href="{{ route('inventory.item-groups.index') }}">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection
