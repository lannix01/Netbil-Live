@extends('pettycash::layouts.app')

@section('title','Board Report Builder')

@push('styles')
<style>
    .wrap{max-width:1050px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .btn{display:inline-block;padding:10px 14px;border-radius:10px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:900;border:none;cursor:pointer}
    .btn2{display:inline-block;padding:10px 14px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:900}
    .h{font-size:18px;font-weight:900;margin:0}
    .sub{font-size:12px;color:#667085;margin-top:4px}
    label{display:block;font-size:12px;color:#475467;margin:12px 0 6px}
    input,select{width:100%;border:1px solid #d0d5dd;padding:10px;border-radius:10px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    @media(max-width:860px){.row{grid-template-columns:1fr}}
    .tiny{font-size:12px;color:#667085}
    .checkgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}
    @media(max-width:860px){.checkgrid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="top">
        <div>
            <div class="h">Board Report Builder</div>
            <div class="sub">Generate board reports as PDF, CSV, or Excel.</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn2" href="{{ route('petty.reports.index') }}">Back</a>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('petty.reports.general.pdf') }}">
            <div class="h" style="font-size:15px;">Scope</div>
            <div class="sub">Choose batches and dates. Leave blank to include everything.</div>

            <label>Batches (optional, multi-select)</label>
            <select name="batch_ids[]" multiple size="6">
                @foreach($batches as $b)
                    <option value="{{ $b->id }}">{{ $b->batch_no }}</option>
                @endforeach
            </select>
            <div class="tiny">Tip: Hold CTRL to select multiple. None selected = all batches.</div>

            <div class="row">
                <div>
                    <label>From</label>
                    <input type="date" name="from">
                </div>
                <div>
                    <label>To</label>
                    <input type="date" name="to">
                </div>
            </div>

            <div style="margin-top:12px" class="h">Optional Focus (if needed)</div>
            <div class="sub">Use these only when the board asks: “show me THIS bike” or “THIS respondent”.</div>

            <div class="row">
                <div>
                    <label>Specific Bike (optional)</label>
                    <select name="bike_id">
                        <option value="">All bikes</option>
                        @foreach($bikes as $bike)
                            <option value="{{ $bike->id }}">{{ $bike->plate_no }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Specific Respondent (optional)</label>
                    <select name="respondent_id">
                        <option value="">All respondents</option>
                        @foreach($respondents as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-top:12px" class="h">Include Categories in Debits</div>
            <div class="sub">These control what shows under Debits (Credits always show at the top).</div>

            <div class="checkgrid">
                <label><input type="checkbox" name="include[]" value="bike:fuel" checked> Bikes Fuel</label>
                <label><input type="checkbox" name="include[]" value="bike:maintenance" checked> Bikes Maintenance</label>
                <label><input type="checkbox" name="include[]" value="meal:lunch" checked> Meals (Lunch)</label>
                <label><input type="checkbox" name="include[]" value="token:hostel" checked> Token (Hostel)</label>
                <label><input type="checkbox" name="include[]" value="other" checked> Others</label>
            </div>

            <label>Layout</label>
            <select name="view">
                <option value="combined" selected>Combined (single debits table)</option>
                <option value="split">Split (separate tables per category)</option>
            </select>

            <label>Export Format</label>
            <select name="format">
                <option value="pdf" selected>PDF</option>
                <option value="csv">CSV</option>
                <option value="excel">Excel</option>
            </select>

            <div style="margin-top:16px;">
                <button class="btn" type="submit">Generate Report</button>
                <span class="tiny" style="margin-left:10px;">
                    Output includes totals + balance + analysis page with chart (if server supports GD).
                </span>
            </div>
        </form>
    </div>
</div>
@endsection
