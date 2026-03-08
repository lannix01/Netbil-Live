@extends('pettycash::layouts.app')

@section('title','New Credit')

@section('content')
<div class="form-wrap form-wrap-sm">
    <div class="form-header">
        <div>
            <h2>New Credit</h2>
            <div class="form-subtitle">Create a new credit entry and auto-generate a fresh batch.</div>
        </div>
        <a class="btn2" href="{{ route('petty.credits.index') }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.credits.store') }}">
            @csrf

            <div class="pc-field">
                <label>Mpesa REF</label>
                <input class="pc-input" name="reference" value="{{ old('reference') }}">
            </div>

            <div class="pc-field">
                <label>Amount</label>
                <input class="pc-input" type="number" step="0.01" name="amount" required value="{{ old('amount') }}">
            </div>

            <div class="pc-field">
                <label>Transaction Cost</label>
                <input class="pc-input" type="number" step="0.01" name="transaction_cost" value="{{ old('transaction_cost', 0) }}">
            </div>

            <div class="pc-field">
                <label>Date</label>
                <input class="pc-input" type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}">
            </div>

            <div class="pc-field full">
                <label>Description (optional)</label>
                <textarea class="pc-textarea" name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="pc-actions">
                <button class="btn" type="submit">Save Credit & Create Batch</button>
            </div>
        </form>
    </div>
</div>
@endsection
