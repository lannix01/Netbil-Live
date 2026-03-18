@extends('inventory::layout')

@section('page-title', 'Deploy Item')
@section('page-subtitle', 'Deploy only the inventory assigned to you. Site selection must come from a registered ONT/site.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.tech.dashboard') }}" data-inv-loading>Back</a>
@endsection

@section('page-toolbar')
    <div class="tech-toolbar">
        <span class="tech-toolbar-chip">{{ $assignment->item?->name ?? 'Item' }}</span>
        <span class="tech-toolbar-chip">{{ $assignment->item?->has_serial ? 'Serialized' : 'Bulk' }}</span>
        <span class="tech-toolbar-chip">{{ number_format((int) ($assignment->qty_allocated ?? 0)) }} Allocated</span>
        <span class="tech-toolbar-chip">{{ number_format((int) ($assignment->qty_deployed ?? 0)) }} Deployed</span>
        <span class="tech-toolbar-chip">{{ number_format((int) (method_exists($assignment, 'availableToDeploy') ? $assignment->availableToDeploy() : 0)) }} Ready</span>
    </div>
@endsection

@section('inventory-content')
@php
    $access = \App\Modules\Inventory\Support\InventoryAccess::class;
    $canDeploy = $access::allows(auth('inventory')->user(), 'deployments.manage');
    $hasSerial = (bool) ($assignment->item?->has_serial ?? false);
    $available = method_exists($assignment, 'availableToDeploy') ? (int) $assignment->availableToDeploy() : 0;
    $itemName = $assignment->item?->name ?? 'Item';
    $groupName = $assignment->item?->group?->name ?? 'Ungrouped';

    $selectedSite = [
        'site_id' => (string) old('site_id', request()->query('site_id', old('site_code', ''))),
        'site_name' => (string) old('site_name', request()->query('site_name', '')),
        'site_serial' => (string) old('site_serial', request()->query('site_serial', '')),
        'status' => (string) old('site_status_display', ''),
        'rx_power' => (string) old('site_rx_power_display', ''),
        'tx_power' => (string) old('site_tx_power_display', ''),
        'olt' => (string) old('site_olt_display', ''),
        'slot' => (string) old('site_slot_display', ''),
        'pon' => (string) old('site_pon_display', ''),
        'acs_last_inform' => (string) old('site_acs_last_inform_display', ''),
    ];

    $siteSearchValue = $selectedSite['site_name'] !== '' ? $selectedSite['site_name'] : (string) old('site_lookup', '');
@endphp

<style>
    .tech-toolbar{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
    }
    .tech-toolbar-chip{
        display:inline-flex;
        align-items:center;
        padding:8px 14px;
        border-radius:999px;
        border:1px solid rgba(38, 83, 64, .14);
        background:rgba(255,255,255,.78);
        color:#214636;
        font-size:12px;
        font-weight:800;
        letter-spacing:.04em;
    }
    .tech-deploy-grid{
        display:grid;
        gap:18px;
    }
    .tech-card{
        border:1px solid rgba(38, 83, 64, .12);
        border-radius:24px;
        background:linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(247,252,248,.98) 100%);
        box-shadow:0 18px 44px rgba(73, 98, 82, .08);
        padding:18px;
    }
    .tech-card-head{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:14px;
        flex-wrap:wrap;
        margin-bottom:16px;
    }
    .tech-card-title{
        margin:0;
        font-size:22px;
        line-height:1.1;
        font-weight:900;
        color:#153728;
    }
    .tech-card-sub{
        margin-top:6px;
        color:#5d7568;
        font-size:13px;
    }
    .tech-chip{
        display:inline-flex;
        align-items:center;
        padding:6px 10px;
        border-radius:999px;
        border:1px solid rgba(38, 83, 64, .12);
        background:rgba(255,255,255,.9);
        color:#214636;
        font-size:11px;
        font-weight:800;
        letter-spacing:.04em;
    }
    .tech-form{
        display:grid;
        gap:14px;
    }
    .tech-field{
        display:grid;
        gap:8px;
    }
    .tech-label{
        font-size:12px;
        font-weight:800;
        letter-spacing:.12em;
        text-transform:uppercase;
        color:#6f8879;
    }
    .tech-control{
        width:100%;
        min-height:48px;
        border-radius:16px;
        border:1px solid rgba(38, 83, 64, .16);
        background:#fff;
        padding:0 14px;
        color:#173829;
        font-size:15px;
        outline:none;
        box-shadow:none;
    }
    .tech-control:focus{
        border-color:#1f8d5f;
        box-shadow:0 0 0 4px rgba(31, 141, 95, .10);
    }
    .tech-control.invalid{
        border-color:#dc2626;
        box-shadow:0 0 0 4px rgba(220, 38, 38, .08);
    }
    .tech-control-area{
        min-height:112px;
        padding:14px;
        resize:vertical;
    }
    .tech-help{
        color:#5d7568;
        font-size:12px;
        line-height:1.45;
    }
    .tech-error{
        color:#b91c1c;
        font-size:12px;
        font-weight:700;
    }
    .tech-site-search{
        position:relative;
    }
    .tech-site-search-box{
        display:flex;
        gap:10px;
        align-items:center;
    }
    .tech-site-search-box .tech-control{
        flex:1 1 auto;
    }
    .tech-clear-btn{
        min-height:48px;
        padding:0 14px;
        border-radius:16px;
        border:1px solid rgba(38, 83, 64, .16);
        background:#fff;
        color:#214636;
        font-size:13px;
        font-weight:800;
    }
    .tech-site-results{
        display:none;
        margin-top:10px;
        border:1px solid rgba(38, 83, 64, .12);
        border-radius:18px;
        background:#fff;
        overflow:hidden;
    }
    .tech-site-results.show{
        display:block;
    }
    .tech-site-option{
        width:100%;
        border:0;
        border-bottom:1px solid rgba(38, 83, 64, .08);
        background:#fff;
        padding:14px;
        text-align:left;
        display:grid;
        gap:6px;
    }
    .tech-site-option:last-child{
        border-bottom:0;
    }
    .tech-site-option:hover{
        background:rgba(243, 249, 245, .96);
    }
    .tech-site-option strong{
        color:#173829;
        font-size:15px;
    }
    .tech-site-meta{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        color:#5d7568;
        font-size:12px;
        font-weight:700;
    }
    .tech-status-line{
        min-height:18px;
        color:#5d7568;
        font-size:12px;
    }
    .tech-selected{
        display:none;
        border:1px solid rgba(31, 141, 95, .16);
        border-radius:20px;
        background:rgba(243, 249, 245, .96);
        padding:16px;
        gap:12px;
    }
    .tech-selected.show{
        display:grid;
    }
    .tech-selected-grid{
        display:grid;
        gap:10px;
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }
    .tech-selected-box{
        border-radius:16px;
        background:rgba(255,255,255,.85);
        border:1px solid rgba(38, 83, 64, .08);
        padding:12px;
    }
    .tech-selected-label{
        font-size:11px;
        font-weight:800;
        letter-spacing:.12em;
        text-transform:uppercase;
        color:#78907f;
    }
    .tech-selected-value{
        margin-top:6px;
        color:#153728;
        font-size:14px;
        font-weight:800;
        line-height:1.35;
        word-break:break-word;
    }
    .tech-submit{
        display:inline-flex;
        justify-content:center;
        align-items:center;
        width:100%;
        min-height:50px;
        border:0;
        border-radius:18px;
        background:#1f8d5f;
        color:#fff;
        font-size:15px;
        font-weight:900;
        letter-spacing:.02em;
    }
    .tech-submit:disabled{
        background:#9db9ac;
    }
    .tech-side-stack{
        display:grid;
        gap:18px;
    }
    .tech-list{
        display:grid;
        gap:10px;
    }
    .tech-list-row{
        border:1px solid rgba(38, 83, 64, .10);
        border-radius:18px;
        background:rgba(255,255,255,.9);
        padding:13px 14px;
    }
    .tech-list-top{
        display:flex;
        justify-content:space-between;
        gap:10px;
        align-items:flex-start;
        flex-wrap:wrap;
    }
    .tech-list-title{
        color:#173829;
        font-size:15px;
        font-weight:800;
    }
    .tech-list-sub{
        color:#5d7568;
        font-size:12px;
        margin-top:4px;
        line-height:1.45;
    }
    .tech-empty{
        padding:22px 18px;
        border-radius:20px;
        border:1px dashed rgba(38, 83, 64, .18);
        background:rgba(255,255,255,.78);
        color:#5d7568;
        text-align:center;
    }
    .tech-warning{
        padding:14px 16px;
        border-radius:18px;
        border:1px solid rgba(217, 119, 6, .18);
        background:rgba(255, 247, 237, .94);
        color:#9a5b0b;
        font-size:13px;
        font-weight:800;
    }
    @media (min-width: 992px){
        .tech-deploy-grid{
            grid-template-columns:minmax(0, 1.18fr) minmax(320px, .82fr);
            align-items:start;
        }
    }
    @media (max-width: 767.98px){
        .tech-card-title{
            font-size:20px;
        }
        .tech-selected-grid{
            grid-template-columns:1fr;
        }
        .tech-site-search-box{
            flex-direction:column;
        }
        .tech-clear-btn{
            width:100%;
        }
    }
</style>

<div class="tech-deploy-grid">
    <div class="tech-card">
        <div class="tech-card-head">
            <div>
                <h2 class="tech-card-title">{{ $itemName }}</h2>
                <div class="tech-card-sub">Group: {{ $groupName }}</div>
            </div>
            <span class="tech-chip">{{ $hasSerial ? 'Serialized Deployment' : 'Bulk Deployment' }}</span>
        </div>

        @if(!$canDeploy)
            <div class="tech-warning" style="margin-bottom:16px;">
                Your account can view this assignment but cannot record deployments. Ask an admin to enable deployment access for you.
            </div>
        @elseif($available <= 0)
            <div class="tech-warning" style="margin-bottom:16px;">
                No deployable balance is left on this assignment. Ask an admin to top up or adjust the allocation first.
            </div>
        @endif

        <form method="POST" action="{{ route('inventory.deployments.store') }}" class="tech-form" data-inv-loading>
            @csrf
            <input type="hidden" name="item_id" value="{{ $assignment->item_id }}">
            <input type="hidden" name="site_id" id="selectedSiteId" value="{{ $selectedSite['site_id'] }}">
            <input type="hidden" name="site_code" id="selectedSiteCode" value="{{ $selectedSite['site_id'] }}">
            <input type="hidden" name="site_name" id="selectedSiteName" value="{{ $selectedSite['site_name'] }}">
            <input type="hidden" name="site_serial" id="selectedSiteSerial" value="{{ $selectedSite['site_serial'] }}">
            <input type="hidden" name="site_status_display" id="selectedSiteStatus" value="{{ $selectedSite['status'] }}">
            <input type="hidden" name="site_rx_power_display" id="selectedSiteRxPower" value="{{ $selectedSite['rx_power'] }}">
            <input type="hidden" name="site_tx_power_display" id="selectedSiteTxPower" value="{{ $selectedSite['tx_power'] }}">
            <input type="hidden" name="site_olt_display" id="selectedSiteOlt" value="{{ $selectedSite['olt'] }}">
            <input type="hidden" name="site_slot_display" id="selectedSiteSlot" value="{{ $selectedSite['slot'] }}">
            <input type="hidden" name="site_pon_display" id="selectedSitePon" value="{{ $selectedSite['pon'] }}">
            <input type="hidden" name="site_acs_last_inform_display" id="selectedSiteAcsLastInform" value="{{ $selectedSite['acs_last_inform'] }}">

            @if($hasSerial)
                <div class="tech-field">
                    <label class="tech-label" for="deployUnitId">Assigned Serial</label>
                    <select
                        class="tech-control {{ $errors->has('unit_id') ? 'invalid' : '' }}"
                        name="unit_id"
                        id="deployUnitId"
                        required
                    >
                        <option value="">Select a serial</option>
                        @foreach($assignedUnits as $unit)
                            <option value="{{ $unit->id }}" @selected((string) old('unit_id') === (string) $unit->id)>
                                {{ $unit->serial_no }}
                            </option>
                        @endforeach
                    </select>
                    <div class="tech-help">Only serials currently assigned to your account are available.</div>
                    @error('unit_id')
                        <div class="tech-error">{{ $message }}</div>
                    @enderror
                </div>
            @else
                <div class="tech-field">
                    <label class="tech-label" for="deployQty">Quantity</label>
                    <input
                        class="tech-control {{ $errors->has('qty') ? 'invalid' : '' }}"
                        type="number"
                        id="deployQty"
                        name="qty"
                        min="1"
                        max="{{ max(1, $available) }}"
                        value="{{ old('qty', 1) }}"
                        required
                    >
                    <div class="tech-help">Enter a quantity up to the available balance.</div>
                    @error('qty')
                        <div class="tech-error">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            <div class="tech-field">
                <label class="tech-label" for="siteLookupInput">Registered ONT / Site</label>
                <div class="tech-site-search" data-site-lookup data-site-url="{{ $siteLookupUrl }}">
                    <div class="tech-site-search-box">
                        <input
                            class="tech-control {{ $errors->has('site_name') || $errors->has('site_id') || $errors->has('site_serial') ? 'invalid' : '' }}"
                            type="text"
                            id="siteLookupInput"
                            name="site_lookup"
                            autocomplete="off"
                            placeholder="Search by site name or ONT serial"
                            value="{{ $siteSearchValue }}"
                        >
                        <button class="tech-clear-btn" type="button" id="clearSelectedSite">Clear</button>
                    </div>

                    <div class="tech-status-line" id="siteLookupStatus">
                        Select a live ONT/site before deploying. Free-text deployment is disabled for technicians.
                    </div>

                    <div class="tech-site-results" id="siteLookupResults"></div>
                </div>

                @if($errors->has('site_name') || $errors->has('site_id') || $errors->has('site_serial'))
                    <div class="tech-error">{{ $errors->first('site_name') ?: $errors->first('site_id') ?: $errors->first('site_serial') }}</div>
                @endif
            </div>

            <div class="tech-selected {{ $selectedSite['site_id'] !== '' ? 'show' : '' }}" id="selectedSiteCard">
                <div class="tech-selected-grid">
                    <div class="tech-selected-box">
                        <div class="tech-selected-label">Site</div>
                        <div class="tech-selected-value" id="selectedSiteNameDisplay">{{ $selectedSite['site_name'] !== '' ? $selectedSite['site_name'] : 'No site selected' }}</div>
                    </div>
                    <div class="tech-selected-box">
                        <div class="tech-selected-label">Site ID</div>
                        <div class="tech-selected-value" id="selectedSiteIdDisplay">{{ $selectedSite['site_id'] !== '' ? $selectedSite['site_id'] : 'Not selected' }}</div>
                    </div>
                    <div class="tech-selected-box">
                        <div class="tech-selected-label">ONT Serial</div>
                        <div class="tech-selected-value" id="selectedSiteSerialDisplay">{{ $selectedSite['site_serial'] !== '' ? $selectedSite['site_serial'] : 'Not selected' }}</div>
                    </div>
                    <div class="tech-selected-box">
                        <div class="tech-selected-label">Status / Signal</div>
                        <div class="tech-selected-value" id="selectedSiteSignalDisplay">
                            {{ trim($selectedSite['status'] . ($selectedSite['rx_power'] !== '' ? ' • RX ' . $selectedSite['rx_power'] : '')) !== '' ? trim($selectedSite['status'] . ($selectedSite['rx_power'] !== '' ? ' • RX ' . $selectedSite['rx_power'] : '')) : 'Waiting for lookup details' }}
                        </div>
                    </div>
                    <div class="tech-selected-box">
                        <div class="tech-selected-label">OLT / Slot / PON</div>
                        <div class="tech-selected-value" id="selectedSiteOltDisplay">
                            {{ trim($selectedSite['olt'] . ($selectedSite['slot'] !== '' ? ' • Slot ' . $selectedSite['slot'] : '') . ($selectedSite['pon'] !== '' ? ' • PON ' . $selectedSite['pon'] : '')) !== '' ? trim($selectedSite['olt'] . ($selectedSite['slot'] !== '' ? ' • Slot ' . $selectedSite['slot'] : '') . ($selectedSite['pon'] !== '' ? ' • PON ' . $selectedSite['pon'] : '')) : 'Waiting for lookup details' }}
                        </div>
                    </div>
                    <div class="tech-selected-box">
                        <div class="tech-selected-label">Last Inform</div>
                        <div class="tech-selected-value" id="selectedSiteInformDisplay">{{ $selectedSite['acs_last_inform'] !== '' ? $selectedSite['acs_last_inform'] : 'Waiting for lookup details' }}</div>
                    </div>
                </div>
            </div>

            <div class="tech-field">
                <label class="tech-label" for="deployReference">Reference</label>
                <input
                    class="tech-control {{ $errors->has('reference') ? 'invalid' : '' }}"
                    type="text"
                    id="deployReference"
                    name="reference"
                    value="{{ old('reference') }}"
                    placeholder="Ticket, job card, or request"
                >
                @error('reference')
                    <div class="tech-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="tech-field">
                <label class="tech-label" for="deployNotes">Notes</label>
                <textarea
                    class="tech-control tech-control-area {{ $errors->has('notes') ? 'invalid' : '' }}"
                    id="deployNotes"
                    name="notes"
                    placeholder="Any deployment note that should remain on the record"
                >{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="tech-error">{{ $message }}</div>
                @enderror
            </div>

            <button class="tech-submit" type="submit" id="deploySubmitButton" {{ !$canDeploy || $available <= 0 ? 'disabled' : '' }}>
                {{ $hasSerial ? 'Deploy Serial' : 'Deploy Quantity' }}
            </button>
        </form>
    </div>

    <div class="tech-side-stack">
        <div class="tech-card">
            <div class="tech-card-head">
                <div>
                    <h2 class="tech-card-title">Assigned Stock</h2>
                    <div class="tech-card-sub">Everything deployable under this item for your account.</div>
                </div>
            </div>

            @if($hasSerial)
                <div class="tech-list">
                    @forelse($assignedUnits as $unit)
                        <div class="tech-list-row">
                            <div class="tech-list-top">
                                <div class="tech-list-title">{{ $unit->serial_no }}</div>
                                <span class="tech-chip">Assigned</span>
                            </div>
                        </div>
                    @empty
                        <div class="tech-empty">No assigned serials are currently available under this item.</div>
                    @endforelse
                </div>
            @else
                <div class="tech-selected-grid">
                    <div class="tech-selected-box">
                        <div class="tech-selected-label">Allocated</div>
                        <div class="tech-selected-value">{{ number_format((int) ($assignment->qty_allocated ?? 0)) }}</div>
                    </div>
                    <div class="tech-selected-box">
                        <div class="tech-selected-label">Deployed</div>
                        <div class="tech-selected-value">{{ number_format((int) ($assignment->qty_deployed ?? 0)) }}</div>
                    </div>
                    <div class="tech-selected-box">
                        <div class="tech-selected-label">Ready To Deploy</div>
                        <div class="tech-selected-value">{{ number_format($available) }}</div>
                    </div>
                    <div class="tech-selected-box">
                        <div class="tech-selected-label">Assigned At</div>
                        <div class="tech-selected-value">{{ optional($assignment->assigned_at)->format('d M Y, H:i') ?? 'Not recorded' }}</div>
                    </div>
                </div>
            @endif
        </div>

        <div class="tech-card">
            <div class="tech-card-head">
                <div>
                    <h2 class="tech-card-title">Recent Records</h2>
                    <div class="tech-card-sub">Latest deployment rows for this same item under your account.</div>
                </div>
            </div>

            <div class="tech-list">
                @forelse($recentDeployments as $deployment)
                    <div class="tech-list-row">
                        <div class="tech-list-top">
                            <div>
                                <div class="tech-list-title">{{ $deployment->site_name ?: 'Site not recorded' }}</div>
                                <div class="tech-list-sub">Site ID: {{ $deployment->site_code ?: 'N/A' }}</div>
                            </div>
                            <span class="tech-chip">{{ number_format((int) ($deployment->qty ?? 0)) }}</span>
                        </div>
                        <div class="tech-list-sub">
                            {{ optional($deployment->created_at)->format('d M Y, H:i') ?? 'Just now' }}
                            @if($deployment->reference)
                                • Ref {{ $deployment->reference }}
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="tech-empty">No deployment records exist for this item under your account yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lookupRoot = document.querySelector('[data-site-lookup]');
        if (!lookupRoot) {
            return;
        }

        const lookupUrl = lookupRoot.getAttribute('data-site-url') || '';
        const searchInput = document.getElementById('siteLookupInput');
        const results = document.getElementById('siteLookupResults');
        const status = document.getElementById('siteLookupStatus');
        const clearButton = document.getElementById('clearSelectedSite');
        const submitButton = document.getElementById('deploySubmitButton');
        const selectedCard = document.getElementById('selectedSiteCard');

        const fields = {
            siteId: document.getElementById('selectedSiteId'),
            siteCode: document.getElementById('selectedSiteCode'),
            siteName: document.getElementById('selectedSiteName'),
            siteSerial: document.getElementById('selectedSiteSerial'),
            siteStatus: document.getElementById('selectedSiteStatus'),
            siteRxPower: document.getElementById('selectedSiteRxPower'),
            siteTxPower: document.getElementById('selectedSiteTxPower'),
            siteOlt: document.getElementById('selectedSiteOlt'),
            siteSlot: document.getElementById('selectedSiteSlot'),
            sitePon: document.getElementById('selectedSitePon'),
            siteAcsLastInform: document.getElementById('selectedSiteAcsLastInform'),
        };

        const displays = {
            siteName: document.getElementById('selectedSiteNameDisplay'),
            siteId: document.getElementById('selectedSiteIdDisplay'),
            siteSerial: document.getElementById('selectedSiteSerialDisplay'),
            signal: document.getElementById('selectedSiteSignalDisplay'),
            olt: document.getElementById('selectedSiteOltDisplay'),
            inform: document.getElementById('selectedSiteInformDisplay'),
        };

        let debounceId = null;
        let activeController = null;

        const hasSelectedSite = function () {
            return fields.siteId.value.trim() !== '' && fields.siteSerial.value.trim() !== '' && fields.siteName.value.trim() !== '';
        };

        const updateSubmitState = function () {
            if (!submitButton || submitButton.hasAttribute('data-fixed-disabled')) {
                return;
            }

            submitButton.disabled = !hasSelectedSite();
        };

        const setStatus = function (message) {
            status.textContent = message || '';
        };

        const hideResults = function () {
            results.classList.remove('show');
            results.innerHTML = '';
        };

        const updateSelectedCard = function () {
            if (!hasSelectedSite()) {
                selectedCard.classList.remove('show');
                displays.siteName.textContent = 'No site selected';
                displays.siteId.textContent = 'Not selected';
                displays.siteSerial.textContent = 'Not selected';
                displays.signal.textContent = 'Waiting for lookup details';
                displays.olt.textContent = 'Waiting for lookup details';
                displays.inform.textContent = 'Waiting for lookup details';
                return;
            }

            selectedCard.classList.add('show');
            displays.siteName.textContent = fields.siteName.value || 'No site selected';
            displays.siteId.textContent = fields.siteId.value || 'Not selected';
            displays.siteSerial.textContent = fields.siteSerial.value || 'Not selected';

            const signalBits = [];
            if (fields.siteStatus.value) {
                signalBits.push(fields.siteStatus.value);
            }
            if (fields.siteRxPower.value) {
                signalBits.push('RX ' + fields.siteRxPower.value);
            }
            if (fields.siteTxPower.value) {
                signalBits.push('TX ' + fields.siteTxPower.value);
            }
            displays.signal.textContent = signalBits.length ? signalBits.join(' • ') : 'Waiting for lookup details';

            const oltBits = [];
            if (fields.siteOlt.value) {
                oltBits.push(fields.siteOlt.value);
            }
            if (fields.siteSlot.value) {
                oltBits.push('Slot ' + fields.siteSlot.value);
            }
            if (fields.sitePon.value) {
                oltBits.push('PON ' + fields.sitePon.value);
            }
            displays.olt.textContent = oltBits.length ? oltBits.join(' • ') : 'Waiting for lookup details';
            displays.inform.textContent = fields.siteAcsLastInform.value || 'Waiting for lookup details';
        };

        const clearSelection = function (preserveInput) {
            fields.siteId.value = '';
            fields.siteCode.value = '';
            fields.siteName.value = '';
            fields.siteSerial.value = '';
            fields.siteStatus.value = '';
            fields.siteRxPower.value = '';
            fields.siteTxPower.value = '';
            fields.siteOlt.value = '';
            fields.siteSlot.value = '';
            fields.sitePon.value = '';
            fields.siteAcsLastInform.value = '';

            if (!preserveInput) {
                searchInput.value = '';
            }

            updateSelectedCard();
            updateSubmitState();
        };

        const applySelection = function (site) {
            fields.siteId.value = site.site_id || '';
            fields.siteCode.value = site.site_id || '';
            fields.siteName.value = site.site_name || '';
            fields.siteSerial.value = site.site_serial || '';
            fields.siteStatus.value = site.status || '';
            fields.siteRxPower.value = site.rx_power || '';
            fields.siteTxPower.value = site.tx_power || '';
            fields.siteOlt.value = site.olt || '';
            fields.siteSlot.value = site.slot || '';
            fields.sitePon.value = site.pon || '';
            fields.siteAcsLastInform.value = site.acs_last_inform || '';

            searchInput.value = site.site_name || '';
            setStatus('Selected ' + (site.site_name || 'site') + '.');
            hideResults();
            updateSelectedCard();
            updateSubmitState();
        };

        const renderResults = function (sites) {
            if (!sites.length) {
                hideResults();
                setStatus('No registered ONT/site matched that search.');
                return;
            }

            results.innerHTML = '';

            sites.forEach(function (site) {
                const button = document.createElement('button');
                const title = document.createElement('strong');
                const metaOne = document.createElement('div');
                const metaTwo = document.createElement('div');

                button.type = 'button';
                button.className = 'tech-site-option';
                title.textContent = site.site_name || 'Site';

                metaOne.className = 'tech-site-meta';
                ['Site ID ' + (site.site_id || 'N/A'), 'ONT ' + (site.site_serial || 'N/A'), site.status || 'Status N/A']
                    .forEach(function (text) {
                        const span = document.createElement('span');
                        span.textContent = text;
                        metaOne.appendChild(span);
                    });

                metaTwo.className = 'tech-site-meta';
                [site.olt || 'OLT N/A', 'Slot ' + (site.slot || '-'), 'PON ' + (site.pon || '-')]
                    .forEach(function (text) {
                        const span = document.createElement('span');
                        span.textContent = text;
                        metaTwo.appendChild(span);
                    });

                button.appendChild(title);
                button.appendChild(metaOne);
                button.appendChild(metaTwo);

                button.addEventListener('click', function () {
                    applySelection(site);
                });

                results.appendChild(button);
            });

            results.classList.add('show');
            setStatus('Select the correct registered ONT/site from the list.');
        };

        const fetchSites = function (query) {
            if (!lookupUrl) {
                setStatus('Site lookup is not configured.');
                return;
            }

            if (activeController) {
                activeController.abort();
            }

            activeController = new AbortController();
            setStatus('Searching registered ONT/sites...');

            fetch(lookupUrl + '?q=' + encodeURIComponent(query) + '&limit=8', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: activeController.signal
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Lookup failed.');
                    }

                    return response.json();
                })
                .then(function (payload) {
                    if (!payload.available) {
                        hideResults();
                        setStatus(payload.message || 'Site lookup is unavailable right now.');
                        return;
                    }

                    renderResults(Array.isArray(payload.data) ? payload.data : []);
                })
                .catch(function (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    hideResults();
                    setStatus('Site lookup failed. Check your connection and try again.');
                });
        };

        searchInput.addEventListener('input', function () {
            const query = searchInput.value.trim();

            if (fields.siteName.value && query !== fields.siteName.value) {
                clearSelection(true);
            }

            if (debounceId) {
                window.clearTimeout(debounceId);
            }

            if (query.length < 2) {
                hideResults();
                setStatus(query.length === 0
                    ? 'Select a live ONT/site before deploying. Free-text deployment is disabled for technicians.'
                    : 'Keep typing to search registered ONT/sites.');
                return;
            }

            debounceId = window.setTimeout(function () {
                fetchSites(query);
            }, 260);
        });

        clearButton.addEventListener('click', function () {
            clearSelection(false);
            hideResults();
            setStatus('Selection cleared. Search again by site name or ONT serial.');
            searchInput.focus();
        });

        document.addEventListener('click', function (event) {
            if (!lookupRoot.contains(event.target)) {
                hideResults();
            }
        });

        if (submitButton && submitButton.disabled) {
            submitButton.setAttribute('data-fixed-disabled', '1');
        }

        updateSelectedCard();
        updateSubmitState();
    });
</script>
@endsection
