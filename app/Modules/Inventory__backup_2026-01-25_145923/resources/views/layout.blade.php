@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Fix these issues:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">Inventory</h4>
            <small class="text-muted">Store → Assign → Deploy → Logs → Movements → Teams</small>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            @can('inventory.manage')
                <a class="btn btn-outline-primary" href="{{ route('inventory.items.index') }}">Items</a>
                <a class="btn btn-outline-primary" href="{{ route('inventory.item-groups.index') }}">Groups</a>
                <a class="btn btn-outline-primary" href="{{ route('inventory.receipts.index') }}">Receipts</a>
                <a class="btn btn-outline-primary" href="{{ route('inventory.assignments.index') }}">Tech Assign</a>

                <a class="btn btn-outline-success" href="{{ route('inventory.team_assignments.index') }}">Team Assign</a>
                <a class="btn btn-outline-success" href="{{ route('inventory.team_deployments.index') }}">Team Deploy</a>

                <a class="btn btn-outline-secondary" href="{{ route('inventory.admin.deploy.create') }}">Admin Deploy</a>
                <a class="btn btn-outline-dark" href="{{ route('inventory.movements.index') }}">Movements</a>
                <a class="btn btn-outline-success" href="{{ route('inventory.teams.index') }}">Teams</a>

                <a class="btn btn-outline-danger" href="{{ route('inventory.alerts.low_stock') }}">Low Stock</a>
                <a class="btn btn-outline-dark" href="{{ route('inventory.logs.index') }}">Logs</a>
            @endcan

            @can('inventory.viewAssigned')
                <a class="btn btn-outline-success" href="{{ route('inventory.tech.items.index') }}">My Items</a>
            @endcan

            @can('inventory.deploy')
                <a class="btn btn-outline-secondary" href="{{ route('inventory.deployments.index') }}">Deployments</a>
            @endcan
        </div>
    </div>

    @yield('inventory-content')
</div>
@endsection