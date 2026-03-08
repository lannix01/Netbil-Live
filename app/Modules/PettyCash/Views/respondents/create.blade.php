@extends('pettycash::layouts.app')
@section('title','Add Respondent')

@section('content')
<div class="form-wrap form-wrap-sm">
  <div class="form-header">
    <div>
      <h2>Add Respondent</h2>
      <div class="form-subtitle">Create a reusable respondent for spendings and reports.</div>
    </div>
    <a class="btn2" href="{{ route('petty.respondents.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.respondents.store') }}">
      @csrf

      <div class="pc-field">
        <label>Name</label>
        <input class="pc-input" name="name" value="{{ old('name') }}" required>
      </div>

      <div class="pc-field">
        <label>Phone (optional)</label>
        <input class="pc-input" name="phone" value="{{ old('phone') }}">
      </div>

      <div class="pc-field full">
        <label>Category (optional)</label>
        <input class="pc-input" name="category" value="{{ old('category') }}">
      </div>

      <div class="pc-actions">
        <button class="btn" type="submit">Save Respondent</button>
      </div>
    </form>
  </div>
</div>
@endsection
