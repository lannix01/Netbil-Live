@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <strong>Create Team</strong>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.teams.index') }}">Back</a>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('inventory.teams.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Team Name</label>
                <input class="form-control" name="name" value="{{ old('name') }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Code (optional)</label>
                <input class="form-control" name="code" value="{{ old('code') }}">
                <small class="text-muted">Unique short code, e.g. NOC-A, FIELD-1</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Description (optional)</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Active?</label>
                <select class="form-select" name="is_active">
                    <option value="1" selected>Yes</option>
                    <option value="0">No</option>
                </select>
            </div>

            <button class="btn btn-primary">Create</button>
        </form>
    </div>
</div>
@endsection
