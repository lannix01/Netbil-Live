@extends('pettycash::layouts.app')

@section('title', $hostel->hostel_name . ' Payments')

@push('styles')
<style>
    .wrap{max-width:1100px;margin:0 auto}
    .top{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap}
    .headline{display:grid;gap:6px}
    .title-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .action-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px}
    .muted{color:#667085;font-size:12px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px}
    th{font-size:12px;color:#475467;text-align:left}
    .summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
    @media(max-width:900px){.summary-grid{grid-template-columns:1fr}}
    .err{background:#fef3f2;color:#b42318;border:1px solid #fecdca;padding:10px;border-radius:10px;margin-top:12px}
    .ont-smart{position:relative}
    .ont-select-native{display:none}
    .ont-smart-trigger{width:100%;display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 12px;border:1px solid #d0d5dd;border-radius:12px;background:#fff;color:#101828;font-size:13px;cursor:pointer;text-align:left}
    .ont-smart-trigger:disabled{background:#f9fafb;color:#98a2b3;cursor:not-allowed}
    .ont-smart-menu{position:absolute;z-index:40;left:0;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #d0d5dd;border-radius:12px;box-shadow:0 16px 30px rgba(16,24,40,.12);overflow:hidden}
    .ont-smart-search{width:100%;border:none;border-bottom:1px solid #eaecf0;padding:10px 12px;font-size:13px;outline:none}
    .ont-smart-list{max-height:280px;overflow:auto}
    .ont-smart-item{width:100%;border:none;background:#fff;text-align:left;padding:10px 12px;font-size:13px;color:#101828;cursor:pointer}
    .ont-smart-item:hover,.ont-smart-item.active{background:#eef4ff}
    .ont-smart-empty{padding:10px 12px;color:#667085;font-size:13px}
    .ont-smart-item-title{font-weight:800;color:#101828}
    .ont-smart-item-meta{margin-top:4px;display:grid;grid-template-columns:repeat(3,minmax(0,max-content));align-items:center;gap:8px}
    .ont-smart-item-site{font-size:12px;color:#667085}
    .ont-smart-item-sn{font-size:12px;color:#667085}
    .ont-status-chip{display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.02em;border:1px solid transparent}
    .ont-status-chip.success{background:#ecfdf3;border-color:#abefc6;color:#027a48}
    .ont-status-chip.muted{background:#f2f4f7;border-color:#d0d5dd;color:#475467}
    .meta-card{border:1px solid #eaecf0;border-radius:12px;padding:10px;background:#fcfcfd}
    .meta-label{font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#667085}
    .meta-value{margin-top:4px;font-size:14px;font-weight:700;color:#101828}
    .ont-sync-note{margin-top:10px;padding:10px;border-radius:10px;border:1px solid #d0d5dd;background:#f8fafc;color:#344054;font-size:13px}
    .ont-sync-note.error{border-color:#fecdca;background:#fef3f2;color:#b42318}
    .pc-modal{position:fixed;inset:0;z-index:2000;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:18px}
    .pc-modal.show{display:flex}
    .pc-modal-panel{width:min(900px,100%);max-height:92vh;overflow:auto;background:#fff;border-radius:14px;border:1px solid #e7e9f2;box-shadow:0 22px 50px rgba(16,24,40,.25)}
    .pc-modal-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid #eaecf0}
    .pc-modal-body{padding:14px 16px}
    .pc-close{border:1px solid #d0d5dd;background:#fff;border-radius:10px;padding:6px 10px;font-weight:700;cursor:pointer}
    body.pc-modal-open{overflow:hidden}
    .action-menu{position:relative;display:inline-block}
    .action-menu > summary{list-style:none;cursor:pointer;user-select:none}
    .action-menu > summary::-webkit-details-marker{display:none}
    .action-menu-list{
        position:absolute;
        right:0;
        top:calc(100% + 6px);
        z-index:35;
        min-width:220px;
        background:#fff;
        border:1px solid #d0d5dd;
        border-radius:12px;
        box-shadow:0 14px 28px rgba(16,24,40,.14);
        padding:6px;
        display:grid;
        gap:4px;
    }
    .action-menu-item{
        width:100%;
        display:block;
        text-align:left;
        padding:8px 10px;
        border-radius:8px;
        border:none;
        background:#fff;
        color:#344054;
        text-decoration:none;
        font-size:13px;
        font-weight:700;
        cursor:pointer;
    }
    .action-menu-item:hover{background:#f2f4f7}
    .action-menu-item.is-disabled{color:#98a2b3;background:#f9fafb;cursor:not-allowed}
    .pending-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px}
    .pending-form .pc-field{margin:0}
    .pending-form .pc-field.full{grid-column:1 / -1}
    .pending-status{
        display:inline-flex;
        align-items:center;
        gap:6px;
        border-radius:999px;
        padding:3px 9px;
        font-size:11px;
        font-weight:800;
        border:1px solid transparent;
    }
    .pending-status.pending{background:#fffaeb;border-color:#fedf89;color:#b54708}
    .pending-status.sorted{background:#ecfdf3;border-color:#abefc6;color:#027a48}
    @media(max-width:900px){
        .pending-form{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
@php
    $canRecordPayment = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.record_payment');
    $canEditHostel = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.edit_hostel');
    $canEditPayment = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.edit_payment');
    $agreementType = in_array(strtolower((string) ($agreementType ?? ($hostel->agreement_type ?? 'none'))), ['token', 'send_money', 'package', 'none'], true)
        ? strtolower((string) ($agreementType ?? ($hostel->agreement_type ?? 'none')))
        : 'none';
    $agreementTypeLabel = $agreementTypeLabel ?? match ($agreementType) {
        'token' => 'Token',
        'send_money' => 'Send Money',
        'package' => 'Package',
        default => 'No Agreement',
    };
    $agreementConfigured = $agreementType !== 'none'
        || trim((string) ($hostel->agreement_label ?? '')) !== '';
    $agreementActionLabel = $agreementConfigured ? 'Update Agreement' : 'Set Agreement';
    $isPackageAgreement = $agreementType === 'package';
    $isTokenAgreement = $agreementType === 'token';
    $ontHostels = (array) ($ontCatalog['hostels'] ?? []);
    $ontAvailable = (bool) ($ontCatalog['available'] ?? false);
    $ontMessage = (string) ($ontCatalog['message'] ?? '');
    $modalRequest = strtolower((string) request('modal', ''));
    $oldContext = (string) old('form_context', '');
    $openModal = match ($oldContext) {
        'hostel_edit' => 'hostel-edit',
        'hostel_merge' => 'hostel-merge',
        'record_payment' => 'payment-record',
        default => in_array($modalRequest, ['hostel-edit', 'hostel-merge', 'payment-record'], true) ? $modalRequest : '',
    };
    $pendingCredits = collect($pendingCredits ?? []);
    $pendingCreditOpen = $pendingCredits->filter(fn ($row) => strtolower((string) ($row->status ?? 'pending')) === 'pending')->values();
@endphp
<div class="wrap">
    <div class="top">
        <div class="headline">
            <div class="title-row">
                <h2 style="margin:0">{{ $hostel->hostel_name }}</h2>
                @if((bool) ($hostel->ont_merged ?? false))
                    <span class="pill" style="background:#ecfdf3;color:#027a48;border:1px solid #abefc6">Merged</span>
                @else
                    <span class="pill">Not Merged</span>
                @endif
            </div>
            <div class="muted">
                Agreement: <span class="pill">{{ $agreementTypeLabel }}</span>
                Meter: <span class="pill">{{ $hostel->meter_no ?? '-' }}</span>
                Site S.N: <span class="pill">{{ $hostel->ont_site_sn ?? '-' }}</span>
                Contact: <span class="pill">{{ $hostel->contact_person ?? '-' }}</span>
                Phone: <span class="pill">{{ $hostel->phone_no ?? '-' }}</span>
                Stake: <span class="pill">{{ strtoupper($hostel->stake) }}</span>
                Due: <span class="pill">{{ number_format((float)$hostel->amount_due,2) }}</span>
                @if($agreementType === 'package' && trim((string) ($hostel->agreement_label ?? '')) !== '')
                    Package: <span class="pill">{{ $hostel->agreement_label }}</span>
                @endif
            </div>
            @if($lastPayment)
                <div class="muted" style="margin-top:6px">
                    Last payment: <span class="pill">{{ number_format((float)$lastPayment['amount'],2) }}</span>
                    <span class="pill">{{ $lastPayment['date'] }}</span>
                </div>
            @endif
        </div>
        <div class="action-row">
            <a class="btn2" href="{{ route('petty.tokens.index') }}">Back</a>
            @include('pettycash::partials.export_select', [
                'options' => [
                    'PDF' => route('petty.tokens.hostels.pdf', ['hostel' => $hostel->id, 'format' => 'pdf']),
                    'CSV' => route('petty.tokens.hostels.pdf', ['hostel' => $hostel->id, 'format' => 'csv']),
                    'Excel' => route('petty.tokens.hostels.pdf', ['hostel' => $hostel->id, 'format' => 'excel']),
                ],
            ])
            @if($canEditHostel)
                <details class="action-menu">
                    <summary class="btn2">Management ▾</summary>
                    <div class="action-menu-list">
                        <a class="action-menu-item" href="{{ route('petty.tokens.hostels.agreement', $hostel->id) }}">{{ $agreementActionLabel }}</a>
                        <button class="action-menu-item" type="button" data-modal-target="hostel-edit">Edit Hostel Details</button>
                        @if((bool) ($hostel->ont_merged ?? false))
                            <span class="action-menu-item is-disabled" aria-disabled="true">Merged</span>
                        @else
                            <button class="action-menu-item" type="button" data-modal-target="hostel-merge">Merge/Update from ONT</button>
                        @endif
                    </div>
                </details>
            @endif
            @if($canRecordPayment)
                <button class="btn" type="button" data-modal-target="payment-record">Record Payment</button>
            @endif
        </div>
    </div>

    @if($errors->any() && $oldContext === '')
        <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <div class="card">
        <h3 style="margin:0 0 10px">Hostel Profile</h3>
        <div class="summary-grid">
            <div class="meta-card">
                <div class="meta-label">Hostel Name</div>
                <div class="meta-value">{{ $hostel->hostel_name }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Agreement</div>
                <div class="meta-value">{{ $agreementTypeLabel }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Agreement Details</div>
                <div class="meta-value">{{ $hostel->agreement_label ?: '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Site S.N</div>
                <div class="meta-value">{{ $hostel->ont_site_sn ?: '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Site ID</div>
                <div class="meta-value">{{ $hostel->ont_site_id ?: '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Meter No</div>
                <div class="meta-value">{{ $hostel->meter_no ?: '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Contact Person</div>
                <div class="meta-value">{{ $hostel->contact_person ?: '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Phone</div>
                <div class="meta-value">{{ $hostel->phone_no ?: '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Routers</div>
                <div class="meta-value">{{ (int) $hostel->no_of_routers }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Stake</div>
                <div class="meta-value">{{ strtoupper($hostel->stake) }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Amount Due</div>
                <div class="meta-value">{{ number_format((float)$hostel->amount_due,2) }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Next Due</div>
                <div class="meta-value">{{ $nextDue ? $nextDue->format('Y-m-d') : '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">Status</div>
                <div class="meta-value">{{ $dueBadge }}</div>
            </div>
        </div>
    </div>

    @if($isPackageAgreement && ($supportsPendingCredits ?? false))
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
                <h3 style="margin:0">Pending Credits</h3>
                <div class="muted">
                    Open: <span class="pill">{{ $pendingCreditOpen->count() }}</span>
                </div>
            </div>

            @if($canRecordPayment)
                <form method="POST" action="{{ route('petty.tokens.hostels.pending_credits.store', ['hostel' => $hostel->id]) }}" class="pending-form">
                    @csrf
                    <div class="pc-field">
                        <label>Amount</label>
                        <input
                            class="pc-input"
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="amount"
                            value="{{ old('amount', (float) ($hostel->amount_due ?? 0) > 0 ? number_format((float) $hostel->amount_due, 2, '.', '') : '') }}"
                            placeholder="e.g. 1000.00"
                        >
                    </div>
                    <div class="pc-field">
                        <label>Reference (optional)</label>
                        <input class="pc-input" name="reference" value="{{ old('reference') }}" placeholder="e.g. package-invoice-22">
                    </div>
                    <div class="pc-field">
                        <label>Notes (optional)</label>
                        <input class="pc-input" name="notes" value="{{ old('notes') }}" placeholder="Add context for this pending credit">
                    </div>
                    <div class="pc-field full">
                        <button class="btn" type="submit">Generate Pending Credit</button>
                    </div>
                </form>
            @endif

            @if($pendingCredits->isNotEmpty())
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Created</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Sorted Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($pendingCredits as $credit)
                            @php
                                $isSortedCredit = strtolower((string) ($credit->status ?? 'pending')) === 'sorted';
                            @endphp
                            <tr>
                                <td>{{ optional($credit->created_at)->format('Y-m-d') ?: '-' }}</td>
                                <td>{{ $credit->reference ?: ('Pending #' . $credit->id) }}</td>
                                <td>{{ number_format((float) ($credit->amount ?? 0), 2) }}</td>
                                <td>
                                    <span class="pending-status {{ $isSortedCredit ? 'sorted' : 'pending' }}">
                                        {{ $isSortedCredit ? 'Sorted' : 'Pending' }}
                                    </span>
                                </td>
                                <td>{{ optional($credit->sorted_at)->format('Y-m-d') ?: '-' }}</td>
                                <td>
                                    @if($isSortedCredit)
                                        <span class="muted">Posted</span>
                                    @elseif($canRecordPayment)
                                        <form method="POST" action="{{ route('petty.tokens.hostels.pending_credits.sort', ['hostel' => $hostel->id, 'credit' => $credit->id]) }}" style="margin:0">
                                            @csrf
                                            <button class="btn2" type="submit">Mark Sorted</button>
                                        </form>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="muted" style="margin-top:10px">No pending credits yet.</div>
            @endif
        </div>
    @endif

    <div class="card">
        <h3 style="margin:0 0 6px">Transaction History</h3>

        @forelse($paymentsByBatch as $batchId => $rows)
            @php
                $batchNo = $rows->first()?->batch?->batch_no ?? ($batchId ? ('Batch #'.$batchId) : 'No Batch');
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
                        @if($canEditPayment)
                            <th>Actions</th>
                        @endif
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
                            @if($canEditPayment)
                                <td><a href="{{ route('petty.tokens.payments.edit', $p->id) }}">Edit</a></td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="muted">No transactions yet.</div>
        @endforelse
    </div>

    @if($canEditHostel)
        <div class="pc-modal" data-modal="hostel-edit" aria-hidden="true">
            <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Edit hostel details">
                <div class="pc-modal-head">
                    <h3 style="margin:0">Edit Hostel Details</h3>
                    <button type="button" class="pc-close" data-modal-close>Close</button>
                </div>
                <div class="pc-modal-body">
                    @if($errors->any() && old('form_context') === 'hostel_edit')
                        <div class="err" style="margin-top:0">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                    @endif
                    @php
                        $editSelectedOntKey = old('form_context') === 'hostel_edit'
                            ? (string) old('ont_key', $selectedOntKey)
                            : (string) $selectedOntKey;
                    @endphp
                    <form class="pc-form" method="POST" action="{{ route('petty.tokens.hostels.update', $hostel->id) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="form_context" value="hostel_edit">

                        <div class="pc-field full">
                            <label>ONT / Site</label>
                            <select class="pc-select ont-select ont-select-native" name="ont_key" data-target-prefix="edit" @if(!$ontAvailable) disabled @endif required>
                                <option value="">Select ONT site</option>
                                @foreach($ontHostels as $candidate)
                                    @php
                                        $optKey = (string) ($candidate['key'] ?? '');
                                        $optName = (string) ($candidate['hostel_name'] ?? '');
                                        $optSiteId = (string) ($candidate['site_id'] ?? '');
                                        $optSiteSn = (string) ($candidate['site_sn'] ?? '');
                                        $optMergeStatus = (string) ($candidate['merge_status'] ?? 'unlinked');
                                        $optMergeLabel = (string) ($candidate['merge_status_label'] ?? 'Not Added');
                                        $optMergeTone = (string) ($candidate['merge_status_tone'] ?? 'muted');
                                    @endphp
                                    <option
                                        value="{{ $optKey }}"
                                        data-name="{{ $optName }}"
                                        data-site-id="{{ $optSiteId }}"
                                        data-site-sn="{{ $optSiteSn }}"
                                        data-merge-status="{{ $optMergeStatus }}"
                                        data-merge-status-label="{{ $optMergeLabel }}"
                                        data-merge-status-tone="{{ $optMergeTone }}"
                                        @selected($editSelectedOntKey === $optKey)
                                    >
                                        {{ $optName }} @if($optSiteId !== '') • Site {{ $optSiteId }} @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="ont-smart" data-target-prefix="edit" data-search-url="{{ route('petty.tokens.onts.search', [], false) }}"></div>
                            <input type="hidden" name="hostel_name" class="hostel-name-hidden" data-target-prefix="edit" value="{{ old('hostel_name', $hostel->hostel_name) }}">
                        </div>

                        <div class="pc-field full">
                            <div class="ont-sync-note @if(!$ontAvailable) error @endif">
                                @if($ontAvailable)
                                    Selected ONT name will be saved as hostel name.
                                @else
                                    {{ $ontMessage !== '' ? $ontMessage : 'ONT directory unavailable.' }}
                                @endif
                                <div style="margin-top:6px;font-weight:700">
                                    <span class="selected-hostel-preview" data-target-prefix="edit">-</span>
                                    <span style="margin-left:8px" class="selected-site-preview" data-target-prefix="edit"></span>
                                    <span style="margin-left:8px" class="selected-sn-preview" data-target-prefix="edit"></span>
                                    <span style="margin-left:8px" class="ont-status-chip muted selected-status-preview" data-target-prefix="edit">Not Added</span>
                                </div>
                            </div>
                        </div>

                        <div class="pc-field">
                            <label>Meter No</label>
                            <input class="pc-input" name="meter_no" required value="{{ old('meter_no', $hostel->meter_no) }}">
                        </div>

                        <div class="pc-field">
                            <label>Contact Person</label>
                            <input class="pc-input" name="contact_person" value="{{ old('contact_person', $hostel->contact_person) }}">
                        </div>

                        <div class="pc-field">
                            <label>Phone No (payment number)</label>
                            <input class="pc-input" name="phone_no" value="{{ old('phone_no', $hostel->phone_no) }}">
                        </div>

                        <div class="pc-field">
                            <label>No of Routers</label>
                            <input class="pc-input" type="number" min="0" name="no_of_routers" value="{{ old('no_of_routers', $hostel->no_of_routers) }}">
                        </div>

                        <div class="pc-field">
                            <label>Stake</label>
                            <select class="pc-select" name="stake" required>
                                <option value="monthly" @selected(old('stake', $hostel->stake) === 'monthly')>Monthly</option>
                                <option value="semester" @selected(old('stake', $hostel->stake) === 'semester')>Semester</option>
                            </select>
                        </div>

                        <div class="pc-field">
                            <label>Amount Due</label>
                            <input class="pc-input" type="number" step="0.01" min="0" name="amount_due" required value="{{ old('amount_due', $hostel->amount_due) }}">
                        </div>

                        <div class="pc-actions">
                            <button class="btn" type="submit" @disabled(!$ontAvailable)>Save Hostel Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($canEditHostel && !(bool) ($hostel->ont_merged ?? false))
        @php
            $mergeSelectedOntKey = old('form_context') === 'hostel_merge'
                ? (string) old('ont_key', $selectedOntKey)
                : (string) $selectedOntKey;
        @endphp
        <div class="pc-modal" data-modal="hostel-merge" aria-hidden="true">
            <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Merge hostel with ONT">
                <div class="pc-modal-head">
                    <h3 style="margin:0">Merge / Update from ONT</h3>
                    <button type="button" class="pc-close" data-modal-close>Close</button>
                </div>
                <div class="pc-modal-body">
                    @if($errors->any() && old('form_context') === 'hostel_merge')
                        <div class="err" style="margin-top:0">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                    @endif
                    <form class="pc-form" method="POST" action="{{ route('petty.tokens.hostels.merge_ont', $hostel->id) }}">
                        @csrf
                        <input type="hidden" name="form_context" value="hostel_merge">

                        <div class="pc-field full">
                            <label>Select ONT / Site</label>
                            <select class="pc-select ont-select ont-select-native" name="ont_key" data-target-prefix="merge" @if(!$ontAvailable) disabled @endif required>
                                <option value="">Select ONT site</option>
                                @foreach($ontHostels as $candidate)
                                    @php
                                        $optKey = (string) ($candidate['key'] ?? '');
                                        $optName = (string) ($candidate['hostel_name'] ?? '');
                                        $optSiteId = (string) ($candidate['site_id'] ?? '');
                                        $optSiteSn = (string) ($candidate['site_sn'] ?? '');
                                        $optMergeStatus = (string) ($candidate['merge_status'] ?? 'unlinked');
                                        $optMergeLabel = (string) ($candidate['merge_status_label'] ?? 'Not Added');
                                        $optMergeTone = (string) ($candidate['merge_status_tone'] ?? 'muted');
                                    @endphp
                                    <option
                                        value="{{ $optKey }}"
                                        data-name="{{ $optName }}"
                                        data-site-id="{{ $optSiteId }}"
                                        data-site-sn="{{ $optSiteSn }}"
                                        data-merge-status="{{ $optMergeStatus }}"
                                        data-merge-status-label="{{ $optMergeLabel }}"
                                        data-merge-status-tone="{{ $optMergeTone }}"
                                        @selected($mergeSelectedOntKey === $optKey)
                                    >
                                        {{ $optName }} @if($optSiteId !== '') • Site {{ $optSiteId }} @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="ont-smart" data-target-prefix="merge" data-search-url="{{ route('petty.tokens.onts.search', [], false) }}"></div>
                            <input type="hidden" name="hostel_name" class="hostel-name-hidden" data-target-prefix="merge" value="{{ old('hostel_name', $hostel->hostel_name) }}">
                        </div>

                        <div class="pc-field full">
                            <div class="ont-sync-note @if(!$ontAvailable) error @endif">
                                @if($ontAvailable)
                                    This process updates hostel name to match ONT data.
                                @else
                                    {{ $ontMessage !== '' ? $ontMessage : 'ONT directory unavailable.' }}
                                @endif
                                <div style="margin-top:6px;font-weight:700">
                                    <span class="selected-hostel-preview" data-target-prefix="merge">-</span>
                                    <span style="margin-left:8px" class="selected-site-preview" data-target-prefix="merge"></span>
                                    <span style="margin-left:8px" class="selected-sn-preview" data-target-prefix="merge"></span>
                                    <span style="margin-left:8px" class="ont-status-chip muted selected-status-preview" data-target-prefix="merge">Not Added</span>
                                </div>
                            </div>
                        </div>

                        <div class="pc-field">
                            <label>No of Routers</label>
                            <input class="pc-input" type="number" min="0" name="no_of_routers" value="{{ old('form_context') === 'hostel_merge' ? old('no_of_routers', $hostel->no_of_routers) : $hostel->no_of_routers }}">
                        </div>

                        <div class="pc-actions">
                            <button class="btn" type="submit" @disabled(!$ontAvailable)>Merge ONT into Hostel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($canRecordPayment)
        <div class="pc-modal" data-modal="payment-record" aria-hidden="true">
            <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Record payment">
                <div class="pc-modal-head">
                    <h3 style="margin:0">Record Payment</h3>
                    <button type="button" class="pc-close" data-modal-close>Close</button>
                </div>
                <div class="pc-modal-body">
                    @if($errors->any() && old('form_context') === 'record_payment')
                        <div class="err" style="margin-top:0">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                    @endif
                    <form class="pc-form" method="POST" action="{{ route('petty.tokens.payments.store', $hostel->id) }}">
                        @csrf
                        <input type="hidden" name="form_context" value="record_payment">

                        @if($isPackageAgreement)
                            <input type="hidden" name="funding" value="auto">
                            <div class="pc-field full">
                                <div class="ont-sync-note">
                                    Package agreement selected: this entry is recorded as credit and does not deduct petty balance.
                                </div>
                            </div>
                        @else
                            <div class="pc-field">
                                <label>Funding</label>
                                <select class="pc-select" name="funding" id="fundingModal" required>
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

                            <div class="pc-field" id="batchWrapModal" style="display:none;">
                                <label>Batch (where money comes from)</label>
                                <select class="pc-select" name="batch_id" id="batchIdModal">
                                    <option value="">Select batch</option>
                                    @foreach($batches as $b)
                                        <option value="{{ $b->id }}" @selected((string)old('batch_id') === (string)$b->id)>
                                            {{ $b->batch_no }} (Balance: {{ number_format((float)$b->available_balance,2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="pc-field">
                            <label>Meter No</label>
                            <input class="pc-input" name="meter_no" @if($isTokenAgreement) required @endif value="{{ old('meter_no', $hostel->meter_no) }}">
                        </div>

                        <div class="pc-field">
                            <label>Reference / Transaction Code</label>
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
                            <label>Receiver Name (optional)</label>
                            <input class="pc-input" name="receiver_name" value="{{ old('receiver_name') }}">
                        </div>

                        <div class="pc-field">
                            <label>Receiver Phone</label>
                            <input class="pc-input" name="receiver_phone" @if(!$isPackageAgreement) required @endif value="{{ old('receiver_phone') }}">
                        </div>

                        <div class="pc-field full">
                            <label>Notes (optional)</label>
                            <input class="pc-input" name="notes" value="{{ old('notes') }}">
                        </div>

                        <div class="pc-actions">
                            <button class="btn" type="submit">Save Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function(){
    const body = document.body;
    const modals = Array.from(document.querySelectorAll('[data-modal]'));
    const autoOpenModal = @json($openModal);
    const funding = document.getElementById('fundingModal');
    const batchWrap = document.getElementById('batchWrapModal');
    const batchSel = document.getElementById('batchIdModal');

    function getModal(id){
        return document.querySelector('[data-modal="' + id + '"]');
    }

    function openModal(id){
        const modal = getModal(id);
        if (!modal) return;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        body.classList.add('pc-modal-open');
    }

    function closeModal(modal){
        if (!modal) return;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        if (!modals.some((m) => m.classList.contains('show'))) {
            body.classList.remove('pc-modal-open');
        }
    }

    document.querySelectorAll('[data-modal-target]').forEach((trigger) => {
        trigger.addEventListener('click', () => openModal(trigger.getAttribute('data-modal-target')));
    });

    document.querySelectorAll('[data-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => closeModal(btn.closest('[data-modal]')));
    });

    modals.forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const active = modals.find((m) => m.classList.contains('show'));
        if (active) closeModal(active);
    });

    function syncFunding(){
        if (!funding || !batchWrap) return;
        const isSingle = funding.value === 'single';
        batchWrap.style.display = isSingle ? 'block' : 'none';
        if (!isSingle && batchSel) batchSel.value = '';
    }

    function setupOntPicker(prefix){
        const mountNode = document.querySelector('.ont-smart[data-target-prefix="' + prefix + '"]');
        const select = document.querySelector('.ont-select[data-target-prefix="' + prefix + '"]');
        if (!mountNode || !select) return;
        const searchUrl = String(mountNode.dataset.searchUrl || '');
        const defaultLabel = 'Search ONT site by name';
        let activeRequest = 0;

        const hiddenName = document.querySelector('.hostel-name-hidden[data-target-prefix="' + prefix + '"]');
        const hostelPreview = document.querySelector('.selected-hostel-preview[data-target-prefix="' + prefix + '"]');
        const sitePreview = document.querySelector('.selected-site-preview[data-target-prefix="' + prefix + '"]');
        const snPreview = document.querySelector('.selected-sn-preview[data-target-prefix="' + prefix + '"]');
        const statusPreview = document.querySelector('.selected-status-preview[data-target-prefix="' + prefix + '"]');
        if (!hostelPreview || !sitePreview || !snPreview || !statusPreview) return;

        function syncStatusChip(label, tone) {
            statusPreview.textContent = label || 'Not Added';
            statusPreview.classList.remove('success', 'muted');
            statusPreview.classList.add(tone === 'success' ? 'success' : 'muted');
        }

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ont-smart-trigger';
        trigger.disabled = select.disabled || !searchUrl;

        const triggerLabel = document.createElement('span');
        const triggerIcon = document.createElement('span');
        triggerIcon.textContent = '▾';
        trigger.appendChild(triggerLabel);
        trigger.appendChild(triggerIcon);

        const menu = document.createElement('div');
        menu.className = 'ont-smart-menu';
        menu.hidden = true;

        const search = document.createElement('input');
        search.type = 'text';
        search.className = 'ont-smart-search';
        search.placeholder = 'Search site name...';

        const list = document.createElement('div');
        list.className = 'ont-smart-list';

        menu.appendChild(search);
        menu.appendChild(list);
        mountNode.appendChild(trigger);
        mountNode.appendChild(menu);

        function upsertSelectOption(item) {
            const value = String(item.key || '');
            if (value === '') return null;

            const label = String(item.hostel_name || '') + (item.site_id ? (' • Site ' + item.site_id) : '');
            let option = Array.from(select.options).find((row) => row.value === value);
            if (!option) {
                option = new Option(label, value, false, false);
                select.add(option);
            } else {
                option.textContent = label;
            }

            option.dataset.name = String(item.hostel_name || '');
            option.dataset.siteId = String(item.site_id || '');
            option.dataset.siteSn = String(item.site_sn || '');
            option.dataset.mergeStatus = String(item.merge_status || 'unlinked');
            option.dataset.mergeStatusLabel = String(item.merge_status_label || 'Not Added');
            option.dataset.mergeStatusTone = String(item.merge_status_tone || 'muted');

            return option;
        }

        const syncFromSelected = () => {
            const selected = select.options[select.selectedIndex];
            if (!selected || !selected.value) {
                if (hiddenName) hiddenName.value = '';
                hostelPreview.textContent = '-';
                sitePreview.textContent = '';
                snPreview.textContent = '';
                syncStatusChip('Not Added', 'muted');
                return;
            }

            const hostelName = selected.dataset.name || '';
            const siteId = selected.dataset.siteId || '';
            const siteSn = selected.dataset.siteSn || '';
            const mergeLabel = selected.dataset.mergeStatusLabel || 'Not Added';
            const mergeTone = selected.dataset.mergeStatusTone || 'muted';

            if (hiddenName) hiddenName.value = hostelName;
            hostelPreview.textContent = hostelName || '-';
            sitePreview.textContent = siteId !== '' ? ('Site ' + siteId) : 'No site id';
            snPreview.textContent = siteSn !== '' ? ('S.N ' + siteSn) : '';
            syncStatusChip(mergeLabel, mergeTone);
        };

        function renderHint(text) {
            list.innerHTML = '';
            const row = document.createElement('div');
            row.className = 'ont-smart-empty';
            row.textContent = text;
            list.appendChild(row);
        }

        function renderItems(items) {
            list.innerHTML = '';
            if (!Array.isArray(items) || items.length === 0) {
                renderHint('No site found');
                return;
            }

            items.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ont-smart-item';
                if (select.value === String(item.key || '')) {
                    btn.classList.add('active');
                }

                const title = document.createElement('div');
                title.className = 'ont-smart-item-title';
                title.textContent = String(item.hostel_name || item.site_name || '-');

                const meta = document.createElement('div');
                meta.className = 'ont-smart-item-meta';

                const site = document.createElement('span');
                site.className = 'ont-smart-item-site';
                site.textContent = item.site_id ? ('Site ' + item.site_id) : 'No site id';

                const sn = document.createElement('span');
                sn.className = 'ont-smart-item-sn';
                sn.textContent = 'S.N ' + String(item.site_sn || '-');

                const status = document.createElement('span');
                status.className = 'ont-status-chip ' + (String(item.merge_status_tone || 'muted') === 'success' ? 'success' : 'muted');
                status.textContent = String(item.merge_status_label || 'Not Added');

                meta.appendChild(site);
                meta.appendChild(sn);
                meta.appendChild(status);
                btn.appendChild(title);
                btn.appendChild(meta);

                btn.addEventListener('click', function () {
                    const option = upsertSelectOption(item);
                    if (!option) return;
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    menu.hidden = true;
                    updateTriggerLabel();
                });

                list.appendChild(btn);
            });
        }

        async function fetchAndRender(query) {
            if (!searchUrl) {
                renderHint('ONT search endpoint unavailable');
                return;
            }

            const requestNo = ++activeRequest;
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('limit', '40');

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json().catch(() => ({}));
                if (requestNo !== activeRequest) return;

                if (!response.ok) {
                    renderHint(String(payload.message || 'Search failed'));
                    return;
                }

                renderItems(Array.isArray(payload.onts) ? payload.onts : []);
            } catch (error) {
                if (requestNo !== activeRequest) return;
                const detail = error && error.message ? (': ' + error.message) : '. Try again.';
                renderHint('Search failed' + detail);
            }
        }

        function updateTriggerLabel() {
            const selected = select.options[select.selectedIndex];
            triggerLabel.textContent = selected && selected.value ? selected.textContent.trim() : defaultLabel;
        }

        async function onSearchInput() {
            const q = search.value.trim();
            if (q.length < 2) {
                renderHint('Type 2+ characters');
                return;
            }
            await fetchAndRender(q);
        }

        trigger.addEventListener('click', function () {
            if (trigger.disabled) return;
            menu.hidden = !menu.hidden;
            if (!menu.hidden) {
                if (search.value.trim().length < 2) {
                    renderHint('Type 2+ characters');
                } else {
                    fetchAndRender(search.value.trim());
                }
                search.focus();
            }
        });

        search.addEventListener('input', onSearchInput);
        search.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const firstOption = list.querySelector('.ont-smart-item');
            if (firstOption) {
                firstOption.click();
            }
        });

        document.addEventListener('click', function (event) {
            if (!mountNode.contains(event.target)) {
                menu.hidden = true;
            }
        });

        select.addEventListener('change', syncFromSelected);
        select.addEventListener('change', updateTriggerLabel);
        updateTriggerLabel();
        syncFromSelected();
        renderHint('Type 2+ characters');
    }

    if (funding) funding.addEventListener('change', syncFunding);
    syncFunding();

    setupOntPicker('edit');
    setupOntPicker('merge');

    if (autoOpenModal) {
        openModal(autoOpenModal);
    }
})();
</script>
@endpush
