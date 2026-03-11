@extends('pettycash::layouts.app')

@section('title','New Bike Spending')

@section('content')
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>New Expense</h2>
            <div class="form-subtitle">Fuel or maintenance spending linked to bike activity.</div>
        </div>
        <a class="btn2" href="{{ route('petty.bikes.index') }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.bikes.store') }}">
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
                <label>Subtype</label>
                <select class="pc-select" name="sub_type" required>
                    <option value="fuel" @selected(old('sub_type')==='fuel')>Fuel</option>
                    <option value="maintenance" @selected(old('sub_type')==='maintenance')>Maintenance</option>
                </select>
            </div>

            <div class="pc-field">
                <label>Vehicle / Bike</label>
                @if(!empty($prefBikeId))
                    <input type="hidden" name="bike_id" value="{{ $prefBikeId }}">
                    @php $lockedBike = $bikes->firstWhere('id', (int)$prefBikeId); @endphp
                    <input class="pc-input" value="{{ $lockedBike?->plate_no ?? ('Bike #'.$prefBikeId) }}" disabled>
                    <div class="pc-help">Locked to selected bike from the bike profile page.</div>
                @else
                    <select class="pc-select" name="bike_id" required>
                        <option value="">Select bike</option>
                        @foreach($bikes as $bk)
                            <option value="{{ $bk->id }}" @selected((string)old('bike_id') === (string)$bk->id)>
                                {{ $bk->plate_no }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="pc-field">
                <label>Mpesa REF</label>
                <input class="pc-input" name="reference" required value="{{ old('reference') }}">
            </div>

            <div class="pc-field">
                <label>Amount</label>
                <input class="pc-input" type="number" step="0.01" name="amount" required value="{{ old('amount') }}">
            </div>

            <div class="pc-field">
                <label>Transaction Cost</label>
                <input class="pc-input" type="number" step="0.01" name="transaction_cost" value="{{ old('transaction_cost', 0) }}">
            </div>

            <div class="pc-field">
                <label>Date</label>
                <input class="pc-input" type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}">
            </div>

            <div class="pc-field">
                <label>Respondent (optional)</label>
                <select class="pc-select" name="respondent_id">
                    <option value="">Select respondent</option>
                    @foreach($respondents as $r)
                        <option value="{{ $r->id }}" @selected((string)old('respondent_id') === (string)$r->id)>
                            {{ $r->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pc-field">
                <label>Description (optional)</label>
                <input class="pc-input" name="description" value="{{ old('description') }}">
            </div>

            <div class="pc-field full">
                <label>Particulars (required for maintenance)</label>
                <textarea class="pc-textarea" name="particulars" rows="3">{{ old('particulars') }}</textarea>
            </div>

            <div class="pc-actions">
                <button class="btn" type="submit">Save</button>
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

    function sync(){
        const isSingle = funding.value === 'single';
        batchWrap.style.display = isSingle ? 'block' : 'none';
        if (!isSingle && batchId) batchId.value = '';
    }

    if (funding) funding.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
