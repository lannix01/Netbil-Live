@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Record Payment</h2>

    <form method="POST" action="{{ route('payments.store') }}">
        @csrf

        <div class="mb-3">
            <label>User</label>
            <select name="user_id" class="form-select" required>
                <option value="">-- select --</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Invoice (optional)</label>
            <select name="invoice_id" class="form-select">
                <option value="">-- none --</option>
                @foreach($invoices as $invoice)
                    <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} - KES {{ $invoice->amount }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Amount (KES)</label>
            <input type="number" name="amount" step="0.01" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Method</label>
            <select name="method" class="form-select" required>
                <option value="mpesa">MPESA</option>
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="airtel_money">Airtel Money</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Reference (e.g. MPESA code)</label>
            <input type="text" name="reference" class="form-control">
        </div>

        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-select">
                <option value="success">Success</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <button class="btn btn-success">Save Payment</button>
        <a href="{{ route('payments.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
