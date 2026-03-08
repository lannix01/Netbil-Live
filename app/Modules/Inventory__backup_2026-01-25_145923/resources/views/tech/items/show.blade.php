@extends('inventory::layout')

@section('inventory-content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header"><strong>{{ $assignment->item?->name }}</strong></div>
            <div class="card-body">
                <p class="mb-1"><strong>Group:</strong> {{ $assignment->item?->group?->name }}</p>
                <p class="mb-1"><strong>Allocated:</strong> {{ $assignment->qty_allocated }}</p>
                <p class="mb-1"><strong>Deployed:</strong> {{ $assignment->qty_deployed }}</p>
                <p class="mb-3"><strong>Available:</strong> <span class="badge bg-dark">{{ $assignment->availableToDeploy() }}</span></p>

                <hr>

                @if($assignment->item?->has_serial)
                    <strong>Deploy Serialized Unit</strong>
                    <form method="POST" action="{{ route('inventory.deployments.store') }}">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $assignment->item_id }}">

                        <div class="mb-2">
                            <label class="form-label">Select Serial</label>
                            <select class="form-select" name="unit_id" required>
                                <option value="">-- Select Serial --</option>
                                @foreach($assignedUnits as $u)
                                    <option value="{{ $u->id }}">{{ $u->serial_no }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Only serials assigned to you appear here.</small>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Site Name</label>
                            <input class="form-control" name="site_name" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Site Code (optional)</label>
                            <input class="form-control" name="site_code">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Reference (optional)</label>
                            <input class="form-control" name="reference">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes (optional)</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>

                        <button class="btn btn-dark w-100">Deploy Serial</button>
                    </form>
                @else
                    <strong>Deploy Bulk Qty</strong>
                    <form method="POST" action="{{ route('inventory.deployments.store') }}">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $assignment->item_id }}">

                        <div class="mb-2">
                            <label class="form-label">Site Name</label>
                            <input class="form-control" name="site_name" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Site Code (optional)</label>
                            <input class="form-control" name="site_code">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Qty</label>
                            <input class="form-control" type="number" min="1" name="qty" value="1" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Reference (optional)</label>
                            <input class="form-control" name="reference">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes (optional)</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>

                        <button class="btn btn-dark w-100">Deploy Qty</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <strong>Notes</strong>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.tech.items.index') }}">Back</a>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    Deployment updates:
                    <ul class="mb-0">
                        <li><strong>Serialized:</strong> unit status becomes <code>deployed</code> and stores site details.</li>
                        <li><strong>Bulk:</strong> assignment’s deployed qty increments; store qty already reduced at assignment.</li>
                        <li>Every action is written to <strong>Inventory Logs</strong> for audits.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
