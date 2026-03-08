@extends('pettycash::layouts.app')

@section('title','New Lunch')

@section('content')
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>New Lunch Spending</h2>
            <div class="form-subtitle">Create a single payment or a mass disbursement over a date range.</div>
        </div>
        <a class="btn2" href="{{ route('petty.meals.index') }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.meals.store') }}">
            @csrf

            <div class="pc-field">
                <label>Funding</label>
                <select class="pc-select" name="funding" id="funding" required>
                    <option value="auto" @selected(old('funding','auto')==='auto')>
                        Auto (Use TOTAL balance )
                    </option>
                    <option value="single" @selected(old('funding')==='single')>
                        Single Batch
                    </option>
                </select>
                <div class="pc-help">
                    Total available (net): <strong>{{ number_format((float)$totalBalance, 2) }}</strong>
                </div>
            </div>

            <div class="pc-field" id="batchWrap" style="display:none;">
                <label>Batch</label>
                <select class="pc-select" name="batch_id" id="batch_id">
                    <option value="">Select batch</option>
                    @foreach($batches as $b)
                        <option value="{{ $b->id }}" @selected((string)old('batch_id', $prefBatchId) === (string)$b->id)>
                            {{ $b->batch_no }} (Balance: {{ number_format((float)$b->available_balance, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pc-field">
                <label>Mpesa REF</label>
                <input class="pc-input" name="reference" value="{{ old('reference') }}">
            </div>

            <div class="pc-field">
                <label>Amount (per day if mass)</label>
                <input class="pc-input" type="number" step="0.01" name="amount" required value="{{ old('amount') }}">
            </div>

            <div class="pc-field">
                <label>Transaction Cost</label>
                <input class="pc-input" type="number" step="0.01" name="transaction_cost" value="{{ old('transaction_cost', 0) }}">
            </div>

            <div class="pc-field">
                <label>Description (optional)</label>
                <input class="pc-input" name="description" value="{{ old('description') }}">
            </div>

            <div class="pc-field full">
                <label class="pc-check">
                    <input type="checkbox" name="mass" value="1" id="mass" {{ old('mass') ? 'checked' : '' }}>
                    Mass disbursement (choose date range)
                </label>
            </div>

            <div class="pc-field" id="singleDate">
                <label>Date Paid (single)</label>
                <input class="pc-input" type="date" name="date" value="{{ old('date', date('Y-m-d')) }}">
            </div>

            <div id="rangeDates" class="pc-inline-grid" style="display:none;">
                <div class="pc-field">
                    <label>Range From</label>
                    <input class="pc-input" type="date" name="range_from" value="{{ old('range_from') }}">
                </div>
                <div class="pc-field">
                    <label>Range To</label>
                    <input class="pc-input" type="date" name="range_to" value="{{ old('range_to') }}">
                </div>
            </div>

            <div class="pc-actions">
                <button class="btn" type="submit">Save Lunch</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
const funding = document.getElementById('funding');
const batchWrap = document.getElementById('batchWrap');
const batchId = document.getElementById('batch_id');

function syncFunding(){
  const isSingle = funding.value === 'single';
  batchWrap.style.display = isSingle ? 'block' : 'none';
  if (!isSingle && batchId) batchId.value = '';
}
funding.addEventListener('change', syncFunding);
syncFunding();

    const mass = document.getElementById('mass');
    const singleDate = document.getElementById('singleDate');
    const rangeDates = document.getElementById('rangeDates');

    function sync(){
        if (mass && mass.checked){
            singleDate.style.display = 'none';
            rangeDates.style.display = 'grid';
        } else {
            singleDate.style.display = 'block';
            rangeDates.style.display = 'none';
        }
    }

    if (mass) mass.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
