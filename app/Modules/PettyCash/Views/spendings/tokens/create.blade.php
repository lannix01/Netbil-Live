@extends('pettycash::layouts.app')

@section('title','Add Hostel')

@section('content')
@php
    $canRecordPayment = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.record_payment');
@endphp
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>Add Hostel</h2>
            <div class="form-subtitle">Capture hostel profile details before token payment tracking begins.</div>
        </div>
        <a class="btn2" href="{{ route('petty.tokens.index') }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.tokens.hostels.store') }}">
            @csrf

            <div class="pc-field">
                <label>Hostel Name</label>
                <input class="pc-input" name="hostel_name" required value="{{ old('hostel_name') }}">
            </div>

            <div class="pc-field">
                <label>Meter No</label>
                <input class="pc-input" name="meter_no" value="{{ old('meter_no') }}">
            </div>

            <div class="pc-field">
                <label>Phone No (payment number)</label>
                <input class="pc-input" name="phone_no" value="{{ old('phone_no') }}">
            </div>

            <div class="pc-field">
                <label>No of Routers</label>
                <input class="pc-input" type="number" name="no_of_routers" value="{{ old('no_of_routers', 0) }}">
            </div>

            <div class="pc-field">
                <label>Stake</label>
                <select class="pc-select" name="stake" required>
                    <option value="monthly" @selected(old('stake')==='monthly')>Monthly</option>
                    <option value="semester" @selected(old('stake')==='semester')>Semester</option>
                </select>
            </div>

            <div class="pc-field">
                <label>Amount Due</label>
                <input class="pc-input" type="number" step="0.01" name="amount_due" required value="{{ old('amount_due', 0) }}">
            </div>

            <div class="pc-actions">
                <button class="btn" type="submit">Save Hostel</button>
                @if($canRecordPayment)
                    <button class="btn2" type="submit" name="after_save" value="record_payment">Save Hostel and Record Payment</button>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
