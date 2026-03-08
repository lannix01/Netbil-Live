@extends('pettycash::layouts.app')
@section('title','Edit Respondent')

@section('content')
<div class="form-wrap form-wrap-sm">
  <div class="form-header">
    <div>
      <h2>Edit {{ old('name', $respondent->name) }}</h2>
      <div class="form-subtitle">Keep respondent contact details clean for payment records.</div>
    </div>
    <a class="btn2" href="{{ route('petty.respondents.index') }}">Back</a>
  </div>

  <div class="form-card">
    @if($errors->any())
      <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form class="pc-form" method="POST" action="{{ route('petty.respondents.update', $respondent->id) }}">
      @csrf
      @method('PUT')

      <div class="pc-field">
        <label>Name</label>
        <input class="pc-input" name="name" value="{{ old('name', $respondent->name) }}" required>
      </div>

      <div class="pc-field">
        <label>Phone (optional)</label>
        <input class="pc-input" name="phone" value="{{ old('phone', $respondent->phone) }}">
      </div>

      <div class="pc-field full">
        <label>Category (optional)</label>
        <input class="pc-input" name="category" value="{{ old('category', $respondent->category) }}">
      </div>

      <div class="pc-actions">
        <button class="btn" type="submit">Update Respondent</button>
      </div>
    </form>
  </div>
</div>
@endsection
