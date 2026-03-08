@extends('pettycash::layouts.app')

@section('title', $hostel->hostel_name . ' Payments')

@push('styles')
<style>
    .wrap{max-width:1100px;margin:0 auto}
    .top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px}
    .muted{color:#667085;font-size:12px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
    th{font-size:12px;color:#475467;text-align:left}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:900px){.grid{grid-template-columns:1fr}}
    .success{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px 12px;border-radius:12px;margin-top:12px;display:flex;justify-content:space-between;gap:10px;align-items:flex-start;box-shadow:0 8px 24px rgba(16,24,40,.08);font-weight:700}
    .flash-close{border:none;background:transparent;color:inherit;cursor:pointer;font-size:16px;line-height:1;padding:0}
    .err{background:#fef3f2;color:#b42318;border:1px solid #fecdca;padding:10px;border-radius:10px;margin-top:12px}
    .readonly{background:#f9fafb;color:#111827}
</style>
@endpush

@section('content')
@php
    $canRecordPayment = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.record_payment');
@endphp
<div class="wrap">
    <div class="top">
        <div>
            <h2 style="margin:0">{{ $hostel->hostel_name }}</h2>
            <div class="muted">
                Meter: <span class="pill">{{ $hostel->meter_no ?? '-' }}</span>
                Phone: <span class="pill">{{ $hostel->phone_no ?? '-' }}</span>
                Stake: <span class="pill">{{ strtoupper($hostel->stake) }}</span>
                Due: <span class="pill">{{ number_format((float)$hostel->amount_due,2) }}</span>
            </div>
            @if($lastPayment)
                <div class="muted" style="margin-top:6px">
                    Last payment: <span class="pill">{{ number_format((float)$lastPayment['amount'],2) }}</span>
                    <span class="pill">{{ $lastPayment['date'] }}</span>
                </div>
            @endif
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn2" href="{{ route('petty.tokens.index') }}">Back</a>
            <a class="btn2" href="{{ route('petty.tokens.hostels.pdf', ['hostel' => $hostel->id, 'format' => 'pdf']) }}">PDF</a>
            <a class="btn2" href="{{ route('petty.tokens.hostels.pdf', ['hostel' => $hostel->id, 'format' => 'csv']) }}">CSV</a>
            <a class="btn2" href="{{ route('petty.tokens.hostels.pdf', ['hostel' => $hostel->id, 'format' => 'excel']) }}">Excel</a>
        </div>
    </div>

    @if(session('success'))
        <div class="success" id="hostelFlashSuccess">
            <span>{{ session('success') }}</span>
            <button class="flash-close" type="button" onclick="dismissHostelFlash()">×</button>
        </div>
    @endif
    @if($errors->any())
        <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <div class="grid">
        <div class="card">
            <h3 style="margin:0 0 6px">Record Payment</h3>

            @if($canRecordPayment)
                <form class="pc-form" method="POST" action="{{ route('petty.tokens.payments.store', $hostel->id) }}">
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
                        <label>Batch (where money comes from)</label>
                        <select class="pc-select" name="batch_id" id="batch_id">
                            <option value="">Select batch</option>
                            @foreach($batches as $b)
                                <option value="{{ $b->id }}" @selected((string)old('batch_id') === (string)$b->id)>
                                    {{ $b->batch_no }} (Balance: {{ number_format((float)$b->available_balance,2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pc-field">
                        <label>Meter No</label>
                        <input class="pc-input readonly" name="meter_no" value="{{ old('meter_no', $hostel->meter_no) }}" readonly>
                    </div>

                    <div class="pc-field">
                        <label>Mpesa REF</label>
                        <input class="pc-input" name="reference" value="{{ old('reference') }}">
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
                        <label>Receiver Name (optional)</label>
                        <input class="pc-input" name="receiver_name" value="{{ old('receiver_name') }}">
                    </div>

                    <div class="pc-field">
                        <label>Receiver Phone (optional)</label>
                        <input class="pc-input" name="receiver_phone" value="{{ old('receiver_phone') }}">
                    </div>

                    <div class="pc-field full">
                        <label>Notes (optional)</label>
                        <input class="pc-input" name="notes" value="{{ old('notes') }}">
                    </div>

                    <div class="pc-actions">
                        <button class="btn" type="submit">Save Payment</button>
                    </div>
                </form>
            @else
                <div class="muted">
                    You currently have view-only access on token payments. Recent payments are visible, but recording new payments is disabled.
                </div>
            @endif
        </div>

        <div class="card">
            <h3 style="margin:0 0 6px">Payment History (Grouped by Batch)</h3>

            @forelse($paymentsByBatch as $batchId => $rows)
                @php
                    $batchNo = $rows->first()?->batch?->batch_no ?? ('Batch #'.$batchId);
                    $sumAmt = (float) $rows->sum('amount');
                    $sumFee = (float) $rows->sum('transaction_cost');
                    $sumTotal = $sumAmt + $sumFee;
                @endphp

                <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
                    <div>
                        <strong>{{ $batchNo }}</strong>
                        <div class="muted">
                            Amount: <span class="pill">{{ number_format($sumAmt,2) }}</span>
                            Fees: <span class="pill">{{ number_format($sumFee,2) }}</span>
                            Total: <span class="pill">{{ number_format($sumTotal,2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Ref</th>
                            <th>Amount</th>
                            <th>Fee</th>
                            <th>Total</th>
                            <th>Receiver</th>
                            <th>Notes</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $p)
                            @php
                                $fee = (float)($p->transaction_cost ?? 0);
                                $amt = (float)$p->amount;
                            @endphp
                            <tr>
                                <td>{{ $p->date?->format('Y-m-d') }}</td>
                                <td>{{ $p->reference }}</td>
                                <td>{{ number_format($amt, 2) }}</td>
                                <td>{{ number_format($fee, 2) }}</td>
                                <td><strong>{{ number_format($amt + $fee, 2) }}</strong></td>
                                <td>{{ $p->receiver_name }} {{ $p->receiver_phone ? '('.$p->receiver_phone.')' : '' }}</td>
                                <td>{{ $p->notes }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="muted">No payments yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dismissHostelFlash() {
    const flash = document.getElementById('hostelFlashSuccess');
    if (flash) {
        flash.remove();
    }
}

window.setTimeout(dismissHostelFlash, 5000);

(function(){
    const funding = document.getElementById('funding');
    const batchWrap = document.getElementById('batchWrap');
    const batchSel = document.getElementById('batch_id');

    function syncFunding(){
        if (!funding || !batchWrap) return;
        const isSingle = funding.value === 'single';
        batchWrap.style.display = isSingle ? 'block' : 'none';
        if (!isSingle && batchSel) batchSel.value = '';
    }

    if (funding) funding.addEventListener('change', syncFunding);
    syncFunding();
})();
</script>
@endpush
