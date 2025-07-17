@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Add New Package</h2>

    <form method="POST" action="{{ route('packages.store') }}">
        @csrf

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Speed (e.g. 10Mbps)</label>
            <input type="text" name="speed" class="form-control">
        </div>

        <div class="mb-3">
            <label>Duration (in hours)</label>
            <input type="number" name="duration" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Price (KES)</label>
            <input type="number" step="0.01" name="price" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <button class="btn btn-success">Create Package</button>
        <a href="{{ route('packages.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
