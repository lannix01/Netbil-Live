
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="container py-3">
    <h5 class="mb-3">Edit Package</h5>

    <form method="POST" action="{{ route('packages.update', $package) }}">
        @csrf
        @method('PUT')

        <div class="mb-2">
            <label>Name</label>
            <input type="text" name="name" value="{{ $package->name }}" class="form-control" required>
        </div>

        <div class="mb-2">
            <label>Speed</label>
            <input type="text" name="speed" value="{{ $package->speed }}" class="form-control">
        </div>
         <div class="mb-2">
    <label>MikroTik Profile</label>
    <input type="text"
           class="form-control"
           value="{{ $package->mk_profile }}"
           readonly>
    <small class="text-muted">
        Auto-generated. Used internally by MikroTik.
    </small>
</div>
</div>

        <div class="mb-2">
            <label>Duration (hrs)</label>
            <input type="number" name="duration" value="{{ $package->duration }}" class="form-control" required>
        </div>

        <div class="mb-2">
            <label>Price (KES)</label>
            <input type="number" name="price" value="{{ $package->price }}" step="0.01" class="form-control" required>
        </div>

        <div class="mb-2">
            <label>Description</label>
            <textarea name="description" class="form-control">{{ $package->description }}</textarea>
        </div>

        <button class="btn btn-success">Update Package</button>
        <a href="{{ route('settings.index', ['tab'=>'packages']) }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
