@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2>Packages / Data Plans</h2>

    <a href="{{ route('packages.create') }}" class="btn btn-success mb-3">Add New Package</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-striped table-bordered" id="packagesTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Speed</th>
                <th>Duration (hrs)</th>
                <th>Price (KES)</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($packages as $package)
            <tr>
                <td>{{ $package->name }}</td>
                <td>{{ $package->speed ?? '-' }}</td>
                <td>{{ $package->duration }}</td>
                <td>{{ number_format($package->price, 2) }}</td>
                <td>{{ $package->description ?? '-' }}</td>
                <td>
                    <a href="{{ route('packages.edit', $package) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('packages.destroy', $package) }}" method="POST" style="display:inline-block">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this package?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#packagesTable').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10,25,50,-1],[10,25,50,"All"]]
        });
    });
</script>
@endsection
