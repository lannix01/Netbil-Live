@extends('pettycash::layouts.app')

@section('title','Record Service - '.$bike->plate_no)

@push('styles')
<style>
    .hint{font-size:13px;color:#667085}
</style>
@endpush

@section('content')
<div class="form-wrap">

    <div class="form-header">
        <div>
            <h2>Record Service</h2>
            <div class="hint">Bike: <strong>{{ $bike->plate_no }}</strong></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn2" href="{{ route('petty.maintenances.show', [$bike->id, 'tab' => 'overview']) }}">Back</a>
            <a class="btn2" href="{{ route('petty.maintenances.index', ['tab' => 'history']) }}">History</a>
        </div>
    </div>

    @if($errors->any())
        <div class="err">
            <strong>Fix these errors:</strong>
            <ul style="margin:8px 0 0 18px">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card">
        <form class="pc-form" method="POST" action="{{ route('petty.maintenances.service.store', $bike->id) }}">
            @csrf

            <div class="pc-field">
                    <label>Service Date</label>
                    <input class="pc-input" type="date" name="service_date" value="{{ old('service_date', $defaultServiceDate ?? now()->format('Y-m-d')) }}" required>
            </div>

            <div class="pc-field">
                    <label>Next Due Date (optional)</label>
                    <input class="pc-input" type="date" name="next_due_date" value="{{ old('next_due_date') }}">
                    <div class="pc-help">This updates the bike's next service due in schedule.</div>
            </div>

            <div class="pc-field full">
                <label>Reference (optional)</label>
                <input class="pc-input" type="text" name="reference" value="{{ old('reference') }}" placeholder="e.g. invoice no / ref code">
            </div>

            <div class="pc-field full">
                <label>Work Done (optional)</label>
                <textarea class="pc-textarea" name="work_done" rows="4" placeholder="Describe what was done...">{{ old('work_done') }}</textarea>
            </div>

            <div class="pc-field">
                    <label>Amount</label>
                    <input class="pc-input" type="number" step="0.01" min="0" name="amount" value="{{ old('amount', '0') }}">
            </div>

            <div class="pc-field">
                    <label>Transaction Cost</label>
                    <input class="pc-input" type="number" step="0.01" min="0" name="transaction_cost" value="{{ old('transaction_cost', '0') }}">
            </div>

            <div class="pc-actions">
                <button class="btn" type="submit">Save Service </button>
                <a class="btn2" href="{{ route('petty.maintenances.show', [$bike->id, 'tab' => 'services']) }}">View Services</a>
            </div>
        </form>
    </div>

</div>
@endsection
