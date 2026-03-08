@extends('pettycash::layouts.app')

@section('title','Bike Report')

@push('styles')
<style>
    .wrap{max-width:900px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    label{display:block;font-size:12px;color:#475467;margin:12px 0 6px}
    input,select{width:100%;border:1px solid #d0d5dd;padding:10px;border-radius:10px}
    .btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:800;border:none;cursor:pointer}
    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:800}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    @media(max-width:720px){.row{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <h2 style="margin:0">Bike Report</h2>
        <a class="btn2" href="{{ route('petty.reports.index') }}">Back</a>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('petty.reports.bike.pdf') }}">
            <label>Select Bike</label>
            <select name="bike_id" required>
                <option value="">Choose</option>
                @foreach($bikes as $b)
                    <option value="{{ $b->id }}">{{ $b->plate_no }}</option>
                @endforeach
            </select>

            <label>Category (optional)</label>
            <select name="sub_type">
                <option value="">Fuel + Maintenance</option>
                <option value="fuel">Fuel only</option>
                <option value="maintenance">Maintenance only</option>
            </select>

            <div class="row">
                <div><label>From</label><input type="date" name="from"></div>
                <div><label>To</label><input type="date" name="to"></div>
            </div>

            <div style="margin-top:14px"><button class="btn" type="submit">Download PDF</button></div>
        </form>
    </div>
</div>
@endsection

