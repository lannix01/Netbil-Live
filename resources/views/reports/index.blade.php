{{-- /root/Marcep/netbil/resources/views/reports/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Billing Reports</h1>
        <div>
            <a href="{{ route('reports.create') }}" class="btn btn-primary">New Report</a>
            <button id="exportCsv" type="button" class="btn btn-outline-secondary">Export CSV</button>
            <button id="exportPdf" type="button" class="btn btn-outline-secondary">Export PDF</button>
        </div>
    </div>

    {{-- Filters --}}
    <form id="filters" class="card card-body mb-4" method="GET" action="{{ route('reports.index') }}">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">User</label>
                <select name="user_id" id="user_id" class="form-select">
                    <option value="">All users</option>
                    @foreach($users ?? [] as $user)
                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control" value="{{ request('from') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control" value="{{ request('to') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="">Any</option>
                    <option value="invoice" @selected(request('type')=='invoice')>Invoice</option>
                    <option value="payment" @selected(request('type')=='payment')>Payment</option>
                    <option value="adjustment" @selected(request('type')=='adjustment')>Adjustment</option>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    {{-- Selected user summary --}}
    @if(isset($selectedUser))
    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                    {{ $selectedUser->name }}
                    <small class="text-muted">({{ $selectedUser->email }})</small>
                </h5>
                <div class="text-muted small">User ID: {{ $selectedUser->id }}</div>
            </div>

            <div class="d-flex gap-4 align-items-center">
                <div class="text-center">
                    <div class="text-muted small">Credits</div>
                    <div class="fs-5">{{ number_format($selectedSummary['credits'] ?? 0, 2) }}</div>
                </div>
                <div class="text-center">
                    <div class="text-muted small">Balance</div>
                    <div class="fs-5">{{ number_format($selectedSummary['balance'] ?? 0, 2) }}</div>
                </div>
                <div class="text-center">
                    <div class="text-muted small">Reports</div>
                    <div class="fs-5">{{ $selectedSummary['reports_count'] ?? 0 }}</div>
                </div>
                <div>
                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="viewUserDetails"
                        data-user-id="{{ $selectedUser->id }}"
                    >
                        View Details
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Reports table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Credits Used</th>
                            <th class="text-end">Balance</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports ?? [] as $report)
                        <tr>
                            <td>{{ $report->id }}</td>
                            <td>
                                <div>{{ $report->user->name ?? '—' }}</div>
                                <small class="text-muted">{{ $report->user->email ?? '' }}</small>
                            </td>
                            <td>{{ ucfirst($report->type) }}</td>
                            <td>{{ $report->description ?? '-' }}</td>
                            <td class="text-end">{{ number_format($report->amount, 2) }}</td>
                            <td class="text-end">{{ number_format($report->credits_used ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($report->balance_after ?? 0, 2) }}</td>
                            <td>{{ optional($report->created_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('reports.show', $report->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                <a href="{{ route('reports.download', $report->id) }}" class="btn btn-sm btn-outline-success">Download</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center p-4">No reports found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($reports ?? null, 'links'))
            <div class="p-3">
                {{ $reports->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- User details modal --}}
<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">User details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="userDetailsBody">
        <div class="text-center py-5">Loading...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const csrf = @json(csrf_token());

    const filtersForm = document.getElementById('filters');
    const exportCsvBtn = document.getElementById('exportCsv');
    const exportPdfBtn = document.getElementById('exportPdf');

    function currentQueryString() {
        if (!filtersForm) return '';
        const q = new URLSearchParams(new FormData(filtersForm)).toString();
        return q ? ('&' + q) : '';
    }

    // Export buttons preserve current filters
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function () {
            window.location = '{{ route("reports.export") }}?format=csv' + currentQueryString();
        });
    }

    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function () {
            window.open('{{ route("reports.export") }}?format=pdf' + currentQueryString(), '_blank');
        });
    }

    // View user details (AJAX load into modal)
    const userDetailsBtn = document.getElementById('viewUserDetails');
    if (userDetailsBtn) {
        userDetailsBtn.addEventListener('click', function () {
            const userId = this.getAttribute('data-user-id');
            if (!userId) return;

            const modalEl = document.getElementById('userDetailsModal');
            const body = document.getElementById('userDetailsBody');
            if (!modalEl || !body) return;

            body.innerHTML = '<div class="text-center py-5">Loading...</div>';

            // bootstrap is expected globally (your layout must include bootstrap JS)
            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            fetch('{{ route("reports.userDetails") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(r => r.ok ? r.json() : r.text().then(t => Promise.reject(t)))
            .then(data => {
                let html = `<div>
                    <h6 class="mb-1">${data.user?.name ?? 'User'} <small class="text-muted">${data.user?.email ?? ''}</small></h6>
                    <div class="row mt-3">
                        <div class="col-md-4"><strong>Credits</strong><div>${Number(data.credits_total || 0).toFixed(2)}</div></div>
                        <div class="col-md-4"><strong>Balance</strong><div>${Number(data.balance || 0).toFixed(2)}</div></div>
                        <div class="col-md-4"><strong>Total Reports</strong><div>${(data.reports || []).length}</div></div>
                    </div>
                    <hr/>`;

                if ((data.reports || []).length) {
                    html += `<div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    data.reports.forEach(r => {
                        html += `<tr>
                            <td>${r.id}</td>
                            <td>${r.type}</td>
                            <td>${r.description || ''}</td>
                            <td class="text-end">${Number(r.amount || 0).toFixed(2)}</td>
                            <td>${r.created_at || ''}</td>
                        </tr>`;
                    });
                    html += `</tbody></table></div>`;
                } else {
                    html += `<div class="text-muted">No reports for this user.</div>`;
                }

                html += `</div>`;
                body.innerHTML = html;
            })
            .catch((err) => {
                console.error(err);
                body.innerHTML = '<div class="text-danger text-center p-4">Failed to load user details.</div>';
            });
        });
    }
})();
</script>
@endpush
