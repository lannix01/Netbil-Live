@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Assign New Service</h2>

    <form method="POST" action="{{ route('services.store') }}">
        @csrf

        <div class="mb-3">
            <label>Customer</label>
            <select name="customer_id" class="form-control" required>
                <option value="">-- Select Customer --</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Package</label>
            <select name="package_id" class="form-control" required>
                <option value="">-- Select Package --</option>
                @foreach($packages as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>End Date</label>
            <input type="date" name="end_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-control" required>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="expired">Expired</option>
            </select>
        </div>

        <div class="mb-3">
            <label>MAC Address (optional)</label>
            <input type="text" name="mac_address" class="form-control">
        </div>

        <button class="btn btn-success">Assign Service</button>
        <a href="{{ route('services.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
