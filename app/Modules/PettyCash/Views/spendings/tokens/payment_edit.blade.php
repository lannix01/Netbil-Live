@extends('pettycash::layouts.app')
@section('title','Edit Token Payment')

@section('content')
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>Edit Token Payment</h2>
            <div class="form-subtitle">Update payment metadata without changing allocated amounts.</div>
        </div>
        <a class="btn2" href="{{ route('petty.tokens.hostels.show', $hostel->id) }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.tokens.payments.update', $payment->id) }}">
            @csrf
            @method('PUT')

            <div class="pc-field">
                <label>Hostel</label>
                <input class="pc-input" value="{{ $hostel->hostel_name }}" readonly>
            </div>

            <div class="pc-field">
                <label>Batch</label>
                <input class="pc-input" value="{{ $payment->batch?->batch_no ?? ($payment->batch_id ? ('Batch #'.$payment->batch_id) : 'No Batch') }}" readonly>
            </div>

            <div class="pc-field">
                <label>Amount (read only)</label>
                <input class="pc-input" value="{{ number_format((float) $payment->amount, 2) }}" readonly>
            </div>

            <div class="pc-field">
                <label>Transaction Cost (read only)</label>
                <input class="pc-input" value="{{ number_format((float) ($payment->transaction_cost ?? 0), 2) }}" readonly>
            </div>

            <div class="pc-field">
                <label>Mpesa REF</label>
                <input class="pc-input" name="reference" required value="{{ old('reference', $payment->reference) }}">
            </div>

            <div class="pc-field">
                <label>Meter No</label>
                <input class="pc-input" name="meter_no" required value="{{ old('meter_no', $payment->spending?->meter_no ?? $hostel->meter_no) }}">
            </div>

            <div class="pc-field">
                <label>Date</label>
                <input class="pc-input" type="date" name="date" required value="{{ old('date', optional($payment->date)->format('Y-m-d')) }}">
            </div>

            <div class="pc-field">
                <label>Receiver Name (optional)</label>
                <input class="pc-input" name="receiver_name" value="{{ old('receiver_name', $payment->receiver_name) }}">
            </div>

            <div class="pc-field">
                <label>Receiver Phone</label>
                <input class="pc-input" name="receiver_phone" required value="{{ old('receiver_phone', $payment->receiver_phone) }}">
            </div>

            <div class="pc-field full">
                <label>Notes (optional)</label>
                <input class="pc-input" name="notes" value="{{ old('notes', $payment->notes) }}">
            </div>

            <div class="pc-actions">
                <button class="btn" type="submit">Update Payment</button>
            </div>
        </form>
    </div>
</div>
@endsection
