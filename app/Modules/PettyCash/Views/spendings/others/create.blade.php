@extends('pettycash::layouts.app')

@section('title','New Other Spending')

@section('content')
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>New Other Spending</h2>
            <div class="form-subtitle">Capture non-bike and non-meal expenses in one place.</div>
        </div>
        <a class="btn2" href="{{ route('petty.others.index') }}">Back</a>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.others.store') }}">
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
                {{-- no required here; controller enforces when single --}}
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
                <label>Reference (MPESA Ref)</label>
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

            <div class="pc-field full">
                <label>Description</label>
                <input class="pc-input" name="description" value="{{ old('description') }}">
            </div>

            <div class="pc-actions">
                <button class="btn" type="submit">Save Other Spending</button>
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

    if (funding) funding.addEventListener('change', syncFunding);
    syncFunding();
})();
</script>
@endpush
