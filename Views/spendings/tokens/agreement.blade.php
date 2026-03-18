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
    .hostel-picker{position:relative}
    .hostel-picker-menu{
        position:absolute;
        inset:auto 0 0 0;
        transform:translateY(calc(100% + 6px));
        background:#fff;
        border:1px solid #e4e7ec;
        border-radius:12px;
        box-shadow:0 18px 40px rgba(16,24,40,.12);
        z-index:40;
        max-height:260px;
        overflow:auto;
    }
    .hostel-picker-item{
        width:100%;
        border:none;
        background:transparent;
        text-align:left;
        padding:10px 12px;
        cursor:pointer;
        font-size:13px;
        font-weight:700;
        color:#101828;
    }
    .hostel-picker-item:hover{background:#f2f4f7}
    .hostel-picker-meta{display:block;font-size:11px;color:#667085;font-weight:600;margin-top:4px}
    .hostel-picker-empty{padding:10px 12px;font-size:12px;color:#667085}
    .hostel-picker-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
    .hostel-chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:6px 10px;
        border-radius:999px;
        border:1px solid #d0d5dd;
        background:#fff;
        font-size:12px;
        font-weight:700;
        color:#344054;
    }
    .hostel-chip button{
        border:none;
        background:transparent;
        color:#667085;
        font-weight:900;
        cursor:pointer;
        line-height:1;
        padding:0;
    }
    .hostel-selected{
        margin-top:12px;
        border:1px solid #eaecf0;
        border-radius:12px;
        background:#fcfcfd;
        padding:12px;
    }
    .hostel-selected h4{margin:0 0 8px;font-size:14px}
    .hostel-selected .muted{color:#667085;font-size:12px}
    @media(max-width:900px){.summary-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
    $selectedType = (string) old('agreement_type', $agreementType ?? 'none');
    $selectedApply = collect(old('apply_to_hostels', []))->map(fn ($id) => (int) $id)->all();
    $agreementTerminated = (bool) ($terminationSupported ?? false) && !empty($hostel->agreement_terminated_at);
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
        @if($agreementTerminated)
            <div class="err" style="margin-top:0;margin-bottom:12px">
                Agreement was previously terminated. Saving will reactivate it.
            </div>
        @endif
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

            <div class="pc-field full">
                <label>Apply This Agreement To Other Hostels (optional)</label>
                <div class="hostel-picker" id="agreementHostelPicker"
                     data-search-url="{{ route('petty.tokens.hostels.search', [], false) }}"
                     data-exclude="{{ $hostel->id }}"
                     data-hostel-base="{{ url('/pettycash/spendings/tokens/hostels') }}">
                    <input class="pc-input hostel-picker-input" type="text" id="agreementHostelInput" placeholder="Type hostel name, meter, or phone">
                    <div class="hostel-picker-menu" id="agreementHostelMenu" hidden></div>
                </div>
                <div class="hostel-picker-chips" id="agreementHostelChips"></div>
                <div class="hostel-selected" id="agreementHostelSummary" hidden>
                    <h4>Selected Hostels</h4>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Hostel</th>
                                <th>Site S.N</th>
                                <th>Meter / Phone</th>
                                <th>Payments</th>
                            </tr>
                            </thead>
                            <tbody id="agreementHostelSummaryBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="pc-help">Type to search and pick hostels. Selected hostels will copy this agreement (type, meter/phone, contact, stake, amount).</div>
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
    const picker = document.getElementById('agreementHostelPicker');
    const searchInput = document.getElementById('agreementHostelInput');
    const searchMenu = document.getElementById('agreementHostelMenu');
    const chips = document.getElementById('agreementHostelChips');
    const summaryCard = document.getElementById('agreementHostelSummary');
    const summaryBody = document.getElementById('agreementHostelSummaryBody');
    const preselectedIds = @json($selectedApply);

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

    if (!picker || !searchInput || !searchMenu || !chips) {
        return;
    }

    const searchUrl = String(picker.dataset.searchUrl || '');
    const excludeId = String(picker.dataset.exclude || '');
    const hostelBaseUrl = String(picker.dataset.hostelBase || '');
    const selectedMap = new Map();
    let searchTimer = null;

    function formatHostelLabel(hostel) {
        const name = hostel && hostel.hostel_name ? hostel.hostel_name : ('Hostel #' + String(hostel.id || ''));
        const meter = hostel && hostel.meter_no ? hostel.meter_no : '-';
        const phone = hostel && hostel.phone_no ? hostel.phone_no : '-';
        return {
            title: name,
            meta: 'Meter: ' + meter + ' • Phone: ' + phone,
        };
    }

    function addSelected(hostel) {
        if (!hostel || !hostel.id) return;
        const id = Number(hostel.id);
        if (selectedMap.has(id)) return;
        selectedMap.set(id, hostel);

        const chip = document.createElement('div');
        chip.className = 'hostel-chip';
        chip.dataset.id = String(id);

        const label = document.createElement('span');
        label.textContent = formatHostelLabel(hostel).title;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.textContent = '×';
        remove.addEventListener('click', () => removeSelected(id));

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'apply_to_hostels[]';
        hidden.value = String(id);

        chip.appendChild(label);
        chip.appendChild(remove);
        chip.appendChild(hidden);
        chips.appendChild(chip);
        renderSelectedList();
    }

    function removeSelected(id) {
        selectedMap.delete(id);
        const chip = chips.querySelector('[data-id="' + String(id) + '"]');
        if (chip) chip.remove();
        renderSelectedList();
    }

    function formatMoney(value) {
        const num = Number(value || 0);
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
    }

    function renderSelectedList() {
        if (!summaryCard || !summaryBody) return;
        summaryBody.innerHTML = '';
        const items = Array.from(selectedMap.values());
        if (items.length === 0) {
            summaryCard.hidden = true;
            return;
        }

        items.sort((a, b) => {
            const nameA = String(a.hostel_name || '');
            const nameB = String(b.hostel_name || '');
            return nameA.localeCompare(nameB);
        });

        items.forEach((hostel) => {
            const row = document.createElement('tr');
            const hostelName = hostel.hostel_name || ('Hostel #' + String(hostel.id || ''));

            const hostelCell = document.createElement('td');
            if (hostelBaseUrl) {
                const link = document.createElement('a');
                link.href = hostelBaseUrl.replace(/\\/$/, '') + '/' + String(hostel.id);
                link.textContent = hostelName;
                hostelCell.appendChild(link);
            } else {
                hostelCell.textContent = hostelName;
            }
            row.appendChild(hostelCell);

            const snCell = document.createElement('td');
            const siteSn = hostel.ont_site_sn ? String(hostel.ont_site_sn) : '-';
            const siteId = hostel.ont_site_id ? String(hostel.ont_site_id) : '-';
            const snMain = document.createElement('div');
            snMain.textContent = siteSn;
            const snMeta = document.createElement('div');
            snMeta.className = 'muted';
            snMeta.textContent = 'Site ID: ' + siteId;
            snCell.appendChild(snMain);
            snCell.appendChild(snMeta);
            row.appendChild(snCell);

            const meterCell = document.createElement('td');
            const meter = hostel.meter_no ? String(hostel.meter_no) : '-';
            const phone = hostel.phone_no ? String(hostel.phone_no) : '-';
            const meterMain = document.createElement('div');
            meterMain.textContent = 'Meter: ' + meter;
            const phoneMeta = document.createElement('div');
            phoneMeta.className = 'muted';
            phoneMeta.textContent = 'Phone: ' + phone;
            meterCell.appendChild(meterMain);
            meterCell.appendChild(phoneMeta);
            row.appendChild(meterCell);

            const paymentsCell = document.createElement('td');
            const paymentsCount = Number(hostel.payments_count || 0);
            const paymentsAmount = Number(hostel.payments_amount || 0);
            const paymentsFee = Number(hostel.payments_fee || 0);
            const lastPaymentDate = hostel.last_payment_date ? String(hostel.last_payment_date) : null;
            const paymentsMain = document.createElement('div');
            if (paymentsCount > 0) {
                paymentsMain.textContent = paymentsCount + ' payment' + (paymentsCount === 1 ? '' : 's');
                const paymentsMeta = document.createElement('div');
                paymentsMeta.className = 'muted';
                const feeText = paymentsFee > 0 ? (' • Fees: ' + formatMoney(paymentsFee)) : '';
                paymentsMeta.textContent = 'Last: ' + (lastPaymentDate || '-') + ' • Amount: ' + formatMoney(paymentsAmount) + feeText;
                paymentsCell.appendChild(paymentsMain);
                paymentsCell.appendChild(paymentsMeta);
            } else {
                paymentsMain.textContent = 'No payments yet';
                paymentsCell.appendChild(paymentsMain);
            }
            row.appendChild(paymentsCell);

            summaryBody.appendChild(row);
        });

        summaryCard.hidden = false;
    }

    function renderMenu(items) {
        searchMenu.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'hostel-picker-empty';
            empty.textContent = 'No hostels found';
            searchMenu.appendChild(empty);
            searchMenu.hidden = false;
            return;
        }

        items.forEach((hostel) => {
            const id = Number(hostel.id || 0);
            if (!id || selectedMap.has(id)) return;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'hostel-picker-item';
            const formatted = formatHostelLabel(hostel);
            btn.innerHTML = formatted.title + '<span class="hostel-picker-meta">' + formatted.meta + '</span>';
            btn.addEventListener('click', () => {
                addSelected(hostel);
                searchMenu.hidden = true;
                searchInput.value = '';
            });
            searchMenu.appendChild(btn);
        });

        if (!searchMenu.childElementCount) {
            const empty = document.createElement('div');
            empty.className = 'hostel-picker-empty';
            empty.textContent = 'All matching hostels already selected';
            searchMenu.appendChild(empty);
        }
        searchMenu.hidden = false;
    }

    async function fetchHostels(query) {
        if (!searchUrl) return;
        const url = new URL(searchUrl, window.location.origin);
        url.searchParams.set('q', query);
        url.searchParams.set('limit', '20');
        if (excludeId) url.searchParams.set('exclude', excludeId);

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                renderMenu([]);
                return;
            }
            renderMenu(Array.isArray(payload.hostels) ? payload.hostels : []);
        } catch (error) {
            renderMenu([]);
        }
    }

    async function fetchHostelsByIds(ids) {
        if (!searchUrl || !Array.isArray(ids) || ids.length === 0) return;
        const url = new URL(searchUrl, window.location.origin);
        url.searchParams.set('ids', ids.join(','));
        if (excludeId) url.searchParams.set('exclude', excludeId);
        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) return;
            (payload.hostels || []).forEach(addSelected);
        } catch (error) {
            // silent
        }
    }

    searchInput.addEventListener('input', () => {
        const q = searchInput.value.trim();
        if (searchTimer) window.clearTimeout(searchTimer);
        if (q === '') {
            searchMenu.hidden = true;
            return;
        }
        searchTimer = window.setTimeout(() => fetchHostels(q), 220);
    });

    searchInput.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const first = searchMenu.querySelector('.hostel-picker-item');
        if (first) first.click();
    });

    document.addEventListener('click', (event) => {
        if (!picker.contains(event.target)) {
            searchMenu.hidden = true;
        }
    });

    if (Array.isArray(preselectedIds) && preselectedIds.length > 0) {
        const ids = preselectedIds.map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0);
        fetchHostelsByIds(ids);
    }
})();
</script>
@endpush
