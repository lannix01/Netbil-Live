@extends('pettycash::layouts.app')
@section('title','Add Bike')

@section('content')
<div class="form-wrap form-wrap-sm">
  <div class="form-header">
    <div>
      <h2>Add Bike</h2>
      <div class="form-subtitle">Register a new bike in master data for spending and service logs.</div>
    </div>
    <a class="btn2" href="{{ route('petty.bikes_master.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.bikes_master.store') }}">
      @csrf

      <div class="pc-field">
        <label>Plate No</label>
        <input class="pc-input" name="plate_no" value="{{ old('plate_no') }}" required>
      </div>

      <div class="pc-field">
        <label>Model</label>
        <input class="pc-input" name="model" value="{{ old('model') }}">
      </div>

      <div class="pc-field full">
        <label>Status (optional)</label>
        <input class="pc-input" name="status" value="{{ old('status') }}">
      </div>

      <div class="pc-actions">
        <button class="btn" type="submit">Save Bike</button>
      </div>
    </form>
  </div>
</div>
@endsection
