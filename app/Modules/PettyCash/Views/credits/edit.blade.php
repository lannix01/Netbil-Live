@extends('pettycash::layouts.app')
@section('title','Edit Credit')

@section('content')
<div class="form-wrap form-wrap-sm">
  <div class="form-header">
    <div>
      <h2>Edit Credit</h2>
      <div class="form-subtitle">Update amount and fees so balances match the actual transaction.</div>
    </div>
    <a class="btn2" href="{{ route('petty.credits.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.credits.update', $credit->id) }}">
      @csrf
      @method('PUT')

      <div class="pc-field">
        <label>Date</label>
        <input class="pc-input" type="date" name="date" value="{{ old('date', optional($credit->date)->format('Y-m-d')) }}" required>
      </div>

      <div class="pc-field">
        <label>Reference (MPESA code)</label>
        <input class="pc-input" name="reference" value="{{ old('reference', $credit->reference) }}">
      </div>

      <div class="pc-field">
        <label>Amount</label>
        <input class="pc-input" type="number" step="0.01" name="amount" value="{{ old('amount', $credit->amount) }}" required>
      </div>

      <div class="pc-field">
        <label>Transaction Cost</label>
        <input class="pc-input" type="number" step="0.01" name="transaction_cost" value="{{ old('transaction_cost', $credit->transaction_cost ?? 0) }}">
      </div>

      <div class="pc-field full">
        <label>Description</label>
        <input class="pc-input" name="description" value="{{ old('description', $credit->description) }}">
      </div>

      <div class="pc-actions">
        <button class="btn" type="submit">Update Credit</button>
      </div>
    </form>
  </div>
</div>
@endsection
