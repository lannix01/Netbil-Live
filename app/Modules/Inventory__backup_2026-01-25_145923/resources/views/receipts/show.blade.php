@extends('inventory::layout')

@section('inventory-content')
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <strong>Receipt: {{ $receipt->reference }}</strong>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.receipts.index') }}">Back</a>
    </div>

    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3"><strong>Date:</strong> {{ $receipt->received_date?->format('Y-m-d') }}</div>
            <div class="col-md-3"><strong>Supplier:</strong> {{ $receipt->supplier_name }}</div>
            <div class="col-md-3"><strong>Received By:</strong> {{ $receipt->receiver?->name }}</div>
        </div>

        <div class="mb-3">
            <strong>Notes:</strong>
            <div class="text-muted">{{ $receipt->notes }}</div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipt->lines as $l)
                        <tr>
                            <td>{{ $l->item?->name }}</td>
                            <td>{{ $l->qty_received }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
