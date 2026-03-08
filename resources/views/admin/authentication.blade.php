@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- ===================== --}}
    {{-- STATS ROW --}}
    {{-- ===================== --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="p-3 bg-light border rounded">
                <h6 class="text-muted mb-1">Total Users</h6>
                <h3 class="mb-0">{{ $verifiedUsers->count() + $unverifiedUsers->count() }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-warning bg-opacity-10 border border-warning rounded">
                <h6 class="text-muted mb-1">Pending Verification</h6>
                <h3 class="mb-0 text-warning">{{ $unverifiedUsers->count() }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-success bg-opacity-10 border border-success rounded">
                <h6 class="text-muted mb-1">Verified Users</h6>
                <h3 class="mb-0 text-success">{{ $verifiedUsers->count() }}</h3>
            </div>
        </div>
    </div>

    {{-- ===================== --}}
    {{-- SEARCH + FILTER --}}
    {{-- ===================== --}}
    <div class="row mb-3">
        <div class="col-md-6">
            <input type="text" id="userSearch" class="form-control" placeholder="Search by email or name…">
        </div>
        <div class="col-md-3">
            <select id="statusFilter" class="form-select">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="verified">Verified</option>
            </select>
        </div>
    </div>

    {{-- ===================== --}}
    {{-- UNVERIFIED USERS --}}
    {{-- ===================== --}}
    <h4 class="mb-3">Pending Unverified Requests</h4>

    <table class="table table-bordered table-hover align-middle user-table">
        <thead class="table-light">
            <tr>
                <th>Email</th>
                <th>Name</th>
                <th>Registered</th>
                <th>Status</th>
                <th width="160">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($unverifiedUsers as $user)
                <tr data-status="pending">
                    <td class="user-email">{{ $user->email }}</td>
                    <td class="user-name">{{ $user->name ?? '-' }}</td>
                    <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <span class="badge bg-warning text-dark">Pending</span>
                    </td>
                    <td>
                        <button
                            class="btn btn-sm btn-success ajax-verify"
                            data-id="{{ $user->id }}"
                        >
                            Verify
                        </button>

                        <button
                            class="btn btn-sm btn-outline-danger ajax-delete"
                            data-id="{{ $user->id }}"
                        >
                            Delete
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        No pending users 🎉
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===================== --}}
    {{-- VERIFIED USERS --}}
    {{-- ===================== --}}
    <h4 class="mt-5 mb-3">Verified Users</h4>

    <table class="table table-bordered table-hover align-middle user-table">
        <thead class="table-light">
            <tr>
                <th>Email</th>
                <th>Verified At</th>
                <th>Role</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($verifiedUsers as $user)
                <tr data-status="verified">
                    <td class="user-email">{{ $user->email }}</td>
                    <td>{{ optional($user->email_verified_at)->format('Y-m-d H:i') }}</td>
                    <td>
                        <span class="badge bg-info">
                            {{ $user->role ?? 'user' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-success">Verified</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        No verified users yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ===================== --}}
{{-- SCRIPTS --}}
{{-- ===================== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

    /* SEARCH */
    const searchInput = document.getElementById('userSearch');
    const filterSelect = document.getElementById('statusFilter');

    function filterTable() {
        const q = searchInput.value.toLowerCase();
        const status = filterSelect.value;

        document.querySelectorAll('.user-table tbody tr').forEach(row => {
            const email = row.querySelector('.user-email')?.textContent.toLowerCase() || '';
            const name = row.querySelector('.user-name')?.textContent.toLowerCase() || '';
            const rowStatus = row.dataset.status;

            const matchesText = email.includes(q) || name.includes(q);
            const matchesStatus = !status || rowStatus === status;

            row.style.display = (matchesText && matchesStatus) ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', filterTable);
    filterSelect.addEventListener('change', filterTable);

    /* AJAX VERIFY */
    document.querySelectorAll('.ajax-verify').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Verify this user?')) return;

            const res = await fetch(`/authentication/verify/${btn.dataset.id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (res.ok) location.reload();
            else alert('Verification failed');
        });
    });

    /* AJAX DELETE (SOFT DELETE BACKEND EXPECTED) */
    document.querySelectorAll('.ajax-delete').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Remove this user?')) return;

            const res = await fetch(`/authentication/delete/${btn.dataset.id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-HTTP-Method-Override': 'DELETE'
                }
            });

            if (res.ok) location.reload();
            else alert('Delete failed');
        });
    });

});
</script>
@endsection
