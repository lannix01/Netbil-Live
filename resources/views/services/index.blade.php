@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Services</h2>
    <a href="{{ route('services.create') }}" class="btn btn-primary mb-3">Assign New Service</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Package</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th>MAC</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($services as $s)
            <tr>
                <td>{{ $s->customer->name }}</td>
                <td>{{ $s->package->name }}</td>
                <td>{{ $s->start_date }}</td>
                <td>{{ $s->end_date }}</td>
                <td><span class="badge bg-{{ $s->status === 'active' ? 'success' : ($s->status === 'suspended' ? 'warning' : 'secondary') }}">{{ ucfirst($s->status) }}</span></td>
                <td>{{ $s->mac_address ?? 'N/A' }}</td>
                <td>
                    <form action="{{ route('services.destroy', $s) }}" method="POST">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete service?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $services->links() }}
</div>
@endsection
