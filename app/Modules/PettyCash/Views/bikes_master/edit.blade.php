@extends('pettycash::layouts.app')
@section('title','Edit transport details')

@section('content')
<div class="form-wrap form-wrap-sm">
  <div class="form-header">
    <div>
      <h2>Edit {{ old('plate_no', $bike->plate_no) }}</h2>
      <div class="form-subtitle">Update transport details used across expenses and service history.</div>
    </div>
    <a class="btn2" href="{{ route('petty.bikes_master.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.bikes_master.update', $bike->id) }}">
      @csrf
      @method('PUT')

      <div class="pc-field">
        <label>Plate No</label>
        <input class="pc-input" name="plate_no" value="{{ old('plate_no', $bike->plate_no) }}" required>
      </div>

      <div class="pc-field">
        <label>Model (optional)</label>
        <input class="pc-input" name="model" value="{{ old('model', $bike->model) }}">
      </div>

      <div class="pc-field full">
        <label>Status (optional)</label>
        <input class="pc-input" name="status" value="{{ old('status', $bike->status) }}">
      </div>

      <div class="pc-actions">
        <button class="btn" type="submit">Update </button>
      </div>
    </form>
  </div>
</div>
@endsection
