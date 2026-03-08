<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-semibold mb-0">
        <i class="bi bi-box-seam"></i> Packages / Data Plans
    </h5>

    <a href="{{ route('settings.index', ['tab' => 'packages', 'action' => 'create']) }}" 
       class="btn btn-sm btn-success">
        <i class="bi bi-plus-circle"></i> Add Package
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-striped table-bordered table-sm" id="packagesTable">
    <thead>
<tr>
    <th>Name</th>
    <th>Speed</th>
    <th>Duration (hrs)</th>
    <th>Price (KES)</th>
    <th>MK Profile</th>
    <th>Status</th>
    <th width="140">Actions</th>
</tr>
</thead>
<tbody>
@forelse($packages as $package)
<tr>
    <td>{{ $package->name }}</td>
    <td>{{ $package->speed ?? '-' }}</td>
    <td>{{ $package->duration }}</td>
    <td>{{ number_format($package->price, 2) }}</td>
    <td>
        <code>{{ $package->mk_profile }}</code>
    </td>
    <td>
        <span class="badge bg-{{ $package->status === 'active' ? 'success' : 'secondary' }}">
            {{ ucfirst($package->status) }}
        </span>
    </td>
    <td>
        <a href="{{ route('settings.index', [
            'tab' => 'packages',
            'action' => 'edit',
            'id' => $package->id
        ]) }}" class="btn btn-sm btn-primary">Edit</a>

        <form action="{{ route('packages.destroy', $package) }}"
              method="POST"
              class="d-inline">
            @csrf
            @method('DELETE')
            <button class="btn btn-sm btn-danger"
                    onclick="return confirm('Delete this package?')">
                Delete
            </button>
        </form>
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="text-center text-muted">
        No packages found.
    </td>
</tr>
@endforelse
</tbody>

</table>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.$ && $.fn.DataTable) {
        $('#packagesTable').DataTable({
            responsive: true,
            pageLength: 25
        });
    }
});
</script>
