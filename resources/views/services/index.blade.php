@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Service Plans</h3>
            <p class="text-muted mb-0">Manage hotspot plan profiles, speeds, durations, and limits.</p>
        </div>
        <a href="{{ route('services.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>New Plan
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Total Plans</div>
                    <div class="fs-4 fw-bold">{{ number_format((int)($stats['total_plans'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Hotspot</div>
                    <div class="fs-4 fw-bold text-primary">{{ number_format((int)($stats['hotspot_plans'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Metered</div>
                    <div class="fs-4 fw-bold text-info">{{ number_format((int)($stats['metered_plans'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Assigned Users</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format((int)($stats['assigned_customers'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Active Subs</div>
                    <div class="fs-4 fw-bold text-warning">{{ number_format((int)($stats['active_subscriptions'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Router Profile Users</div>
                    <div class="fs-4 fw-bold text-dark">{{ number_format((int)($stats['router_profile_users'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        @if($schema['has_category'])<th>Category</th>@endif
                        @if($schema['has_profile_column'])<th>Profile</th>@endif
                        @if($schema['has_speed'])<th>Speed</th>@endif
                        @if($schema['has_rate_limit'])<th>Rate Limit</th>@endif
                        @if($schema['has_duration_minutes'])<th>Duration (min)</th>@elseif($schema['has_duration'])<th>Duration (hr)</th>@endif
                        @if($schema['has_data_limit'])<th>Data Limit</th>@endif
                        @if($schema['has_price'])<th>Price</th>@endif
                        <th>Assigned Users</th>
                        <th>Active Subs</th>
                        <th>Router Users</th>
                        @if($schema['has_status'])<th>Status</th>@elseif($schema['has_is_active'])<th>Active</th>@endif
                        <th style="width: 190px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        @php
                            $profile = $plan->mk_profile ?? $plan->mikrotik_profile ?? '-';
                            $planCategory = strtolower((string)($plan->category ?? 'hotspot'));
                            $dataLimit = null;
                            if ($schema['has_data_limit'] && !is_null($plan->data_limit)) {
                                $dataLimit = ((float)$plan->data_limit) / (1024 * 1024);
                            }
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $plan->name }}</div>
                                @if($schema['has_description'] && !empty($plan->description))
                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit($plan->description, 60) }}</small>
                                @endif
                            </td>
                            @if($schema['has_category'])
                                <td>
                                    <span class="badge bg-{{ ($plan->category ?? 'hotspot') === 'metered' ? 'info' : 'primary' }}">
                                        {{ ucfirst($plan->category ?? 'hotspot') }}
                                    </span>
                                </td>
                            @endif
                            @if($schema['has_profile_column'])<td>{{ $profile }}</td>@endif
                            @if($schema['has_speed'])<td>{{ $plan->speed ?: '-' }}</td>@endif
                            @if($schema['has_rate_limit'])<td>{{ $plan->rate_limit ?: '-' }}</td>@endif
                            @if($schema['has_duration_minutes'])
                                <td>{{ $plan->duration_minutes ?: '-' }}</td>
                            @elseif($schema['has_duration'])
                                <td>{{ $plan->duration ?: '-' }}</td>
                            @endif
                            @if($schema['has_data_limit'])
                                <td>{{ is_null($dataLimit) ? '-' : number_format($dataLimit, 2) . ' MB' }}</td>
                            @endif
                            @if($schema['has_price'])<td>KES {{ number_format((float)($plan->price ?? 0), 2) }} {{ $planCategory === 'metered' ? '/ MB' : '/ package' }}</td>@endif
                            <td>{{ number_format((int)($plan->assigned_customers ?? 0)) }}</td>
                            <td>{{ number_format((int)($plan->active_subscriptions ?? 0)) }}</td>
                            <td>{{ number_format((int)($plan->router_profile_users ?? 0)) }}</td>
                            @if($schema['has_status'])
                                <td>
                                    <span class="badge bg-{{ $plan->status === 'active' ? 'success' : ($plan->status === 'archived' ? 'secondary' : 'warning') }}">
                                        {{ ucfirst($plan->status ?: 'unknown') }}
                                    </span>
                                </td>
                            @elseif($schema['has_is_active'])
                                <td>
                                    <span class="badge bg-{{ (bool)$plan->is_active ? 'success' : 'secondary' }}">
                                        {{ (bool)$plan->is_active ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                            @endif
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('services.edit', $plan) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('services.destroy', $plan) }}" method="POST" onsubmit="return confirm('Archive/Delete this plan?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="20" class="text-center text-muted py-3">No plans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $plans->links() }}
    </div>
</div>
@endsection
