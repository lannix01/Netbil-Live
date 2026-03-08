@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-3">Edit Plan</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $profileValue = $plan->mk_profile ?? $plan->mikrotik_profile ?? '';
    @endphp

    <form method="POST" action="{{ route('services.update', $plan) }}" class="card shadow-sm p-3">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Plan Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required>
            </div>

            @if($schema['has_category'])
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category" id="planCategory" class="form-select" required>
                        <option value="hotspot" @selected(old('category', $plan->category ?? 'hotspot') === 'hotspot')>Hotspot</option>
                        <option value="metered" @selected(old('category', $plan->category ?? 'hotspot') === 'metered')>Metered</option>
                    </select>
                </div>
            @endif

            @if($schema['has_profile_column'])
                <div class="col-md-6 field-profile">
                    <label class="form-label">MikroTik Profile</label>
                    <input type="text" name="profile" class="form-control" value="{{ old('profile', $profileValue) }}">
                    <small class="text-muted">Leave blank only if auto-create profile is enabled.</small>
                </div>
            @endif

            @if($schema['has_speed'])
                <div class="col-md-4">
                    <label class="form-label">Speed</label>
                    <input type="text" name="speed" class="form-control" value="{{ old('speed', $plan->speed) }}" required>
                    <small class="text-muted">Required for both hotspot and metered plans.</small>
                </div>
            @endif

            @if($schema['has_rate_limit'])
                <div class="col-md-4 field-hotspot">
                    <label class="form-label">Rate Limit</label>
                    <input type="text" name="rate_limit" class="form-control" value="{{ old('rate_limit', $plan->rate_limit) }}">
                </div>
            @endif

            @if($schema['has_price'])
                <div class="col-md-4">
                    <label class="form-label" id="planPriceLabel">Price (KES)</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $plan->price) }}" required>
                    <small class="text-muted" id="planPriceHint">Set category to determine whether pricing is per package or per MB.</small>
                </div>
            @endif

            @if($schema['has_duration_minutes'])
                <div class="col-md-4 field-hotspot">
                    <label class="form-label">Duration (Minutes)</label>
                    <input type="number" min="1" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $plan->duration_minutes) }}">
                </div>
            @endif

            @if($schema['has_duration'])
                <div class="col-md-4 field-hotspot">
                    <label class="form-label">Duration (Hours)</label>
                    <input type="number" min="1" name="duration" class="form-control" value="{{ old('duration', $plan->duration) }}">
                </div>
            @endif

            @if($schema['has_data_limit'])
                <div class="col-md-4 field-hotspot">
                    <label class="form-label">Data Limit (MB)</label>
                    <input type="number" step="0.01" min="0" name="data_limit_mb" class="form-control" value="{{ old('data_limit_mb', $dataLimitMb) }}" placeholder="Leave blank for unlimited">
                </div>
            @endif

            @if($schema['has_status'])
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $plan->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $plan->status) === 'inactive')>Inactive</option>
                        <option value="archived" @selected(old('status', $plan->status) === 'archived')>Archived</option>
                    </select>
                </div>
            @endif

            @if($schema['has_is_active'])
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="isActive" name="is_active" value="1" @checked(old('is_active', (int)($plan->is_active ?? 0)))>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>
                </div>
            @endif

            @if($schema['has_auto_create_profile'])
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="autoProfile" name="auto_create_profile" value="1" @checked(old('auto_create_profile', (int)($plan->auto_create_profile ?? 0)))>
                        <label class="form-check-label" for="autoProfile">Auto-create profile name</label>
                    </div>
                </div>
            @endif

            @if($schema['has_description'])
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $plan->description) }}</textarea>
                </div>
            @endif
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary">Update Plan</button>
            <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </form>
</div>

<script>
(() => {
    const categoryEl = document.getElementById('planCategory');
    if (!categoryEl) return;

    function refreshByCategory() {
        const isHotspot = (categoryEl.value || 'hotspot') === 'hotspot';
        document.querySelectorAll('.field-hotspot').forEach((el) => {
            el.style.display = isHotspot ? '' : 'none';
        });

        document.querySelectorAll('.field-hotspot input, .field-hotspot select, .field-hotspot textarea').forEach((input) => {
            if (!Object.prototype.hasOwnProperty.call(input.dataset, 'requiredDefault')) {
                input.dataset.requiredDefault = input.required ? '1' : '0';
            }

            input.disabled = !isHotspot;
            input.required = isHotspot && input.dataset.requiredDefault === '1';
        });

        const priceLabel = document.getElementById('planPriceLabel');
        const priceHint = document.getElementById('planPriceHint');
        if (priceLabel) {
            priceLabel.textContent = isHotspot ? 'Price (KES per package)' : 'Price (KES per MB)';
        }
        if (priceHint) {
            priceHint.textContent = isHotspot
                ? 'Hotspot: flat package price for configured duration/data parameters.'
                : 'Metered: unit price per MB used for usage billing.';
        }
    }

    categoryEl.addEventListener('change', refreshByCategory);
    refreshByCategory();
})();
</script>
@endsection
