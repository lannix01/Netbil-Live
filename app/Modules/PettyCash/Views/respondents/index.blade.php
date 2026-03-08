@extends('pettycash::layouts.app')
@section('title','Respondents')

@push('styles')
<style>
.wrap{max-width:1100px;margin:0 auto}
.card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
.top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:800}
.muted{color:#667085;font-size:12px}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
th{font-size:12px;color:#475467;text-align:left}
.success{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px;border-radius:10px;margin-top:12px}
</style>
@endpush

@section('content')
<div class="wrap">
  <div class="top">
    <div>
      <h2 style="margin:0">Respondents</h2>
      <!-- <div class="muted">People asking for payments for items.</div> -->
    </div>
    <a class="btn" href="{{ route('petty.respondents.create') }}">+ Add Respondent</a>
  </div>

  <div class="card">
    <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Phone</th>
          <th>Category</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($respondents as $r)
        <tr>
          <td>{{ $r->name }}</td>
          <td>{{ $r->phone ?? '-' }}</td>
          <td>{{ $r->category ?? '-' }}</td>
          <td><a href="{{ route('petty.respondents.edit', $r->id) }}">Edit</a></td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted">No respondents added yet.</td></tr>
      @endforelse
      </tbody>
    </table>
    </div>

    <div style="margin-top:12px">{{ $respondents->onEachSide(1)->links('pettycash::partials.pagination') }}</div>
  </div>
</div>
@endsection
