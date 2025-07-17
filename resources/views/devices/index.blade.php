@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h4 class="mb-3">Connected Devices</h4>

    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>IP Address</th>
                <th>MAC Address</th>
                <th>Hostname</th>
                <th>Status</th>
                <th>Server</th>
                <th>Expires In</th>
                <th>Last Seen</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leases as $index => $lease)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $lease['address'] ?? '-' }}</td>
                    <td>{{ $lease['mac-address'] ?? '-' }}</td>
                    <td>{{ $lease['host-name'] ?? 'N/A' }}</td>
                    <td>
                        @if($lease['status'] === 'bound')
                            <span class="badge bg-success">{{ $lease['status'] }}</span>
                        @else
                            <span class="badge bg-danger">{{ $lease['status'] }}</span>
                        @endif
                    </td>
                    <td>{{ $lease['server'] ?? '-' }}</td>
                    <td>{{ $lease['expires-after'] ?? '-' }}</td>
                    <td>{{ $lease['last-seen'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">No devices found on the network.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
