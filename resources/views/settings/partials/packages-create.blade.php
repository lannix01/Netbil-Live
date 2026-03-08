<div class="container py-3">
    <h5 class="mb-3">Add New Package</h5>

    <form method="POST" action="{{ route('packages.store') }}">
        @csrf

        <div class="mb-2">
            <label>Name</label>
            <input type="text" name="name"
                   class="form-control"
                   required>
        </div>

        <div class="mb-2">
            <label>Speed (Rate Limit)</label>
            <input type="text"
                   name="speed"
                   class="form-control"
                   placeholder="e.g. 2M/2M">
        </div>

        <div class="mb-2">
            <label>Duration (hours)</label>
            <input type="number"
                   name="duration"
                   class="form-control"
                   min="1"
                   required>
        </div>

        <div class="mb-2">
            <label>Price (KES)</label>
            <input type="number"
                   step="0.01"
                   name="price"
                   class="form-control"
                   required>
        </div>

        <div class="mb-2">
            <label>Description</label>
            <textarea name="description"
                      class="form-control"></textarea>
        </div>

        <button class="btn btn-success">Create Package</button>
        <a href="{{ route('settings.index', ['tab'=>'packages']) }}"
           class="btn btn-secondary">
            Cancel
        </a>
    </form>
</div>
