@extends('pettycash::layouts.app')
@section('title','Edit Meal')

@section('content')
<div class="form-wrap">
  <div class="form-header">
    <div>
      <h2>Edit Meal (Lunch)</h2>
      <div class="form-subtitle">Update amount and MPESA fee to keep balances accurate.</div>
    </div>
    <a class="btn2" href="{{ route('petty.meals.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.meals.update', $spending->id) }}">
      @csrf
      @method('PUT')

      <div class="pc-field">
        <label>Batch</label>
        <select class="pc-select" name="batch_id" required>
          @foreach($batches as $b)
            <option value="{{ $b->id }}" @selected((string)old('batch_id', $spending->batch_id) === (string)$b->id)>
              {{ $b->batch_no ?? $b->id }} (Balance: {{ number_format((float)$b->available_balance, 2) }})
            </option>
          @endforeach
        </select>
      </div>

      <div class="pc-field">
          <label>Date Paid</label>
          <input class="pc-input" type="date" name="date" value="{{ old('date', optional($spending->date)->format('Y-m-d')) }}" required>
      </div>

      <div class="pc-field">
          <label>Reference (MPESA code)</label>
          <input class="pc-input" name="reference" required value="{{ old('reference', $spending->reference) }}">
      </div>

      <div class="pc-field">
          <label>Amount</label>
          <input class="pc-input" type="number" step="0.01" name="amount" value="{{ old('amount', $spending->amount) }}" required>
      </div>

      <div class="pc-field">
          <label>Transaction Cost (MPESA fee)</label>
          <input class="pc-input" type="number" step="0.01" name="transaction_cost" value="{{ old('transaction_cost', $spending->transaction_cost ?? 0) }}">
      </div>

      <div class="pc-field full">
        <label>Description</label>
        <input class="pc-input" name="description" value="{{ old('description', $spending->description) }}">
      </div>

      <div class="pc-actions">
        <button class="btn" type="submit">Update Meal</button>
      </div>
    </form>
  </div>
</div>
@endsection
