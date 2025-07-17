@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4 text-primary text-center fw-bold"> MikroTik System Logs</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Time</th>
                        <th>Topics</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $index => $log)
                        <tr>
                            <td>{{ ($logs->firstItem() ?? 0) + $index }}</td>
                            <td>{{ $log['time'] ?? 'N/A' }}</td>
                            <td>{{ $log['topics'] ?? 'N/A' }}</td>
                            <td>{{ $log['message'] ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No logs found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3 d-flex justify-content-center">
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<script>
    // Live search filter
    document.getElementById('logSearch').addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll("#logTable tbody tr");

        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? "" : "none";
        });
    });
</script>
@endsection


