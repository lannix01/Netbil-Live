@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Package</h2>

    <form method="POST" action="{{ route('packages.update', $package) }}">
        @csrf @method('PUT')

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" value="{{ $package->name }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Speed</label>
            <input type="text" name="speed" value="{{ $package->speed }}" class="form-control">
        </div>

        <div class="mb-3">
            <label>Duration (hrs)</label>
            <input type="number" name="duration" value="{{ $package->duration }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Price (KES)</label>
            <input type="number" name="price" value="{{ $package->price }}" step="0.01" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control">{{ $package->description }}</textarea>
        </div>

        <button class="btn btn-success">Update Package</button>
        <a href="{{ route('packages.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
