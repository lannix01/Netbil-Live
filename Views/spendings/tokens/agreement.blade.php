@extends('pettycash::layouts.app')

@section('title', 'Set Hostel Agreement')

@push('styles')
<style>
    .step-shell{max-width:980px;margin:0 auto}
    .step-top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .step-note{margin-top:6px;font-size:13px;color:#667085}
    .step-chip{display:inline-flex;align-items:center;gap:8px;background:#ecfdf3;border:1px solid #abefc6;color:#027a48;border-radius:999px;padding:6px 12px;font-size:12px;font-weight:800}
    .summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px}
    .summary-card{border:1px solid #eaecf0;border-radius:12px;background:#fcfcfd;padding:10px}
    .summary-label{font-size:11px;color:#667085;text-transform:uppercase;letter-spacing:.04em;font-weight:800}
    .summary-value{margin-top:4px;font-size:15px;font-weight:900;color:#101828}
    .agree-help{margin-top:10px;padding:10px;border:1px solid #d0d5dd;border-radius:10px;background:#f8fafc;color:#344054;font-size:13px}
    .agree-help.warn{border-color:#fecdca;background:#fef3f2;color:#b42318}
    @media(max-width:900px){.summary-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
    $selectedType = (string) old('agreement_type', $agreementType ?? 'none');
@endphp

<div class="step-shell">
    <div class="step-top">
        <div>
            <h2 style="margin:0">Set Hostel Agreement</h2>
            <div class="step-note">Step 2 of 2: choose agreement type and billing details.</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <span class="step-chip">Step 2 / 2</span>
            <a class="btn2" href="{{ route('petty.tokens.hostels.show', $hostel->id) }}">Back to Hostel</a>
        </div>
    </div>

    @if($errors->any())
        <div class="err" style="margin-top:12px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <div class="form-card" style="margin-top:12px">
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Hostel</div>
                <div class="summary-value">{{ $hostel->hostel_name }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Site S.N</div>
                <div class="summary-value">{{ $hostel->ont_site_sn ?: '-' }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Routers</div>
                <div class="summary-value">{{ (int) ($hostel->no_of_routers ?? 0) }}</div>
            </div>
        </div>

        <form class="pc-form" method="POST" action="{{ route('petty.tokens.hostels.agreement.update', $hostel->id) }}" style="margin-top:12px">
            @csrf
            @method('PUT')

            <div class="pc-field full">
                <label>Agreement Type</label>
                <select class="pc-select" name="agreement_type" id="agreementType" required>
                    <option value="token" @selected($selectedType === 'token')>Token</option>
                    <option value="send_money" @selected($selectedType === 'send_money')>Send Money</option>
                    <option value="package" @selected($selectedType === 'package')>Package</option>
                    <option value="none" @selected($selectedType === 'none')>No Agreement</option>
                </select>
                <div id="agreementTypeHelp" class="agree-help"></div>
            </div>

            <div class="pc-field js-agreement-field" data-types="token">
                <label>Meter No (Token)</label>
                <input class="pc-input" type="text" name="meter_no" id="meterNoInput" value="{{ old('meter_no', $hostel->meter_no) }}">
            </div>

            <div class="pc-field js-agreement-field" data-types="send_money,token,package,none">
                <label>Phone No</label>
                <input class="pc-input" type="text" name="phone_no" id="phoneNoInput" value="{{ old('phone_no', $hostel->phone_no) }}">
            </div>

            <div class="pc-field full js-agreement-field" data-types="package">
                <label>Package Name / Details</label>
                <input class="pc-input" type="text" name="agreement_label" id="agreementLabelInput" value="{{ old('agreement_label', $hostel->agreement_label) }}" placeholder="e.g. Campus Package / Voucher Bundle">
            </div>

            <div class="pc-field">
                <label>Contact Person</label>
                <input class="pc-input" type="text" name="contact_person" value="{{ old('contact_person', $hostel->contact_person) }}">
            </div>

            <div class="pc-field">
                <label>Billing Cycle</label>
                <select class="pc-select" name="stake" required>
                    <option value="monthly" @selected(old('stake', $hostel->stake) === 'monthly')>Monthly</option>
                    <option value="semester" @selected(old('stake', $hostel->stake) === 'semester')>Semester</option>
                </select>
            </div>

            <div class="pc-field">
                <label>Amount Due / Credit Value</label>
                <input class="pc-input" type="number" step="0.01" min="0" name="amount_due" required value="{{ old('amount_due', $hostel->amount_due) }}">
            </div>

            <div class="pc-actions">
                <button class="btn" type="submit">Save Agreement</button>
                <a class="btn2" href="{{ route('petty.tokens.hostels.show', $hostel->id) }}">Finish Later</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const typeSelect = document.getElementById('agreementType');
    const helpNode = document.getElementById('agreementTypeHelp');
    const meterInput = document.getElementById('meterNoInput');
    const phoneInput = document.getElementById('phoneNoInput');
    const packageInput = document.getElementById('agreementLabelInput');
    const groups = Array.from(document.querySelectorAll('.js-agreement-field'));

    if (!typeSelect || !helpNode) return;

    const helpTextByType = {
        token: {
            text: 'Token agreement: payments are deducted from petty balance and tracked with meter number.',
            warn: false,
        },
        send_money: {
            text: 'Send Money agreement: payments are deducted from petty balance and tracked via phone.',
            warn: false,
        },
        package: {
            text: 'Package agreement: payment records are allowed, but no deduction is made from petty balance.',
            warn: true,
        },
        none: {
            text: 'No Agreement: keep reminders active without enforcing a payment channel.',
            warn: false,
        },
    };

    function syncAgreementForm() {
        const type = String(typeSelect.value || 'none').toLowerCase();
        const help = helpTextByType[type] || helpTextByType.none;

        helpNode.textContent = help.text;
        helpNode.classList.toggle('warn', !!help.warn);

        groups.forEach((node) => {
            const types = String(node.dataset.types || '').split(',').map((v) => v.trim()).filter(Boolean);
            node.style.display = types.includes(type) ? '' : 'none';
        });

        if (meterInput) {
            meterInput.required = type === 'token';
        }
        if (phoneInput) {
            phoneInput.required = type === 'send_money';
        }
        if (packageInput) {
            packageInput.required = type === 'package';
        }
    }

    typeSelect.addEventListener('change', syncAgreementForm);
    syncAgreementForm();
})();
</script>
@endpush
