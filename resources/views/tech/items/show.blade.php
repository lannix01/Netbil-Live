@extends('inventory::layout')

@section('page-title', 'Deploy Item')
@section('page-subtitle', 'Deploy assigned stock to a site. This updates your available allocation and writes audit logs.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.tech.items.index') }}" data-inv-loading>Back</a>
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.logs.index') }}" data-inv-loading>Logs</a>
@endsection

@section('inventory-content')
@php
    $hasSerial = (bool)($assignment->item?->has_serial ?? false);
    $avail = method_exists($assignment, 'availableToDeploy') ? (int)$assignment->availableToDeploy() : 0;

    $hasErr = fn($key) => $errors->has($key);
    $errMsg = fn($key) => $errors->first($key);

    // Old input preservation (after validation fail)
    $oldSite = old('site_name');
    $oldSiteCode = old('site_code');
    $oldQty = old('qty', 1);
    $oldRef = old('reference');
    $oldNotes = old('notes');
    $oldUnit = old('unit_id');

    $itemName = $assignment->item?->name ?? 'Item';
    $groupName = $assignment->item?->group?->name ?? '—';

    // Optional skeleton preview: ?loading=1
    $loading = request()->boolean('loading');
@endphp

<style>
    /* Tech deploy show (scoped) */
    .inv-panel{ padding:0; overflow:hidden; }
    .inv-panel-head{
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        background: #fbfcff;
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
    }
    .inv-panel-title{ font-weight: 900; margin:0; font-size: 14px; }
    .inv-panel-sub{ color: var(--muted); font-size: 12px; margin-top: 3px; }

    .inv-panel-body{ padding: 16px; }

    .inv-kpi{
        display:grid;
        gap:10px;
        grid-template-columns: 1fr 1fr;
        margin-top: 10px;
    }
    .inv-kpi .box{
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 10px;
        background: #fff;
    }
    .inv-kpi .label{
        font-size: 11px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
        font-weight: 900;
    }
    .inv-kpi .val{
        font-weight: 900;
        font-size: 18px;
        margin-top: 3px;
    }

    .inv-field{
        background:#fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 12px;
        margin-bottom: 12px;
    }
    .inv-field .form-label{
        font-weight: 900;
        font-size: 12px;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 6px;
    }
    .inv-hint{
        font-size: 12px;
        color: var(--muted);
        margin-top: 6px;
        line-height: 1.35;
    }
    .inv-error{
        margin-top: 6px;
        font-size: 12px;
        color: #b91c1c;
        font-weight: 700;
    }
    .inv-input-invalid{
        border-color: rgba(220,38,38,.55) !important;
        box-shadow: 0 0 0 .15rem rgba(220,38,38,.10) !important;
    }

    .inv-note{
        padding: 14px 16px;
        border-radius: 14px;
        border: 1px solid var(--infoBd);
        background: var(--infoBg);
        color: var(--infoTx);
        font-size: 13px;
        line-height: 1.45;
        font-weight: 700;
    }

    .inv-warn{
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid var(--warnBd);
        background: var(--warnBg);
        color: var(--warnTx);
        font-size: 12px;
        line-height: 1.45;
        font-weight: 900;
    }

    #serialSelect{
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
    }

    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }
</style>

<div class="row g-3">
    {{-- Left: Summary + Deploy form --}}
    <div class="col-lg-5">
        <div class="inv-card inv-panel">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">{{ $itemName }}</p>
                    <div class="inv-panel-sub">
                        Group: <span class="inv-chip">{{ $groupName }}</span>
                        @if($hasSerial)
                            <span class="inv-chip">Serialized</span>
                        @else
                            <span class="inv-chip">Bulk</span>
                        @endif
                        <span class="inv-chip">Item ID: #{{ $assignment->item_id }}</span>
                    </div>
                </div>

                <div>
                    <span class="badge bg-dark">Available: {{ $avail }}</span>
                </div>
            </div>

            {{-- Optional skeleton preview --}}
            <div class="inv-skel {{ $loading ? 'show' : '' }}">
                <div class="inv-skeleton inv-skel-row lg" style="width:220px;"></div>
                <div class="inv-skeleton inv-skel-row" style="width:320px;"></div>
                <div class="inv-divider"></div>
                @for($i=0;$i<5;$i++)
                    <div class="inv-skeleton inv-skel-row"></div>
                @endfor
            </div>

            @if(!$loading)
                <div class="inv-panel-body">
                    <div class="inv-kpi">
                        <div class="box">
                            <div class="label">Allocated</div>
                            <div class="val">{{ (int)$assignment->qty_allocated }}</div>
                        </div>
                        <div class="box">
                            <div class="label">Deployed</div>
                            <div class="val">{{ (int)$assignment->qty_deployed }}</div>
                        </div>
                    </div>

                    <div class="inv-divider"></div>

                    @if($avail <= 0)
                        <div class="inv-warn mb-3">
                            No available allocation for deployment. (Allocated − Deployed = 0)
                        </div>
                    @endif

                    @if($hasSerial)
                        <div style="font-weight:900; margin-bottom:8px;">Deploy Serialized Unit</div>

                        <form method="POST" action="{{ route('inventory.deployments.store') }}" data-inv-loading>
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $assignment->item_id }}">

                            <div class="inv-field">
                                <label class="form-label">Select Serial <span class="text-danger">*</span></label>
                                <select
                                    class="form-select {{ $hasErr('unit_id') ? 'inv-input-invalid' : '' }}"
                                    name="unit_id"
                                    id="serialSelect"
                                    required
                                >
                                    <option value="">-- Select Serial --</option>
                                    @foreach($assignedUnits as $u)
                                        <option value="{{ $u->id }}" @selected((string)$oldUnit === (string)$u->id)>{{ $u->serial_no }}</option>
                                    @endforeach
                                </select>
                                <div class="inv-hint">Only serials assigned to you appear here.</div>
                                @if($hasErr('unit_id'))
                                    <div class="inv-error">{{ $errMsg('unit_id') }}</div>
                                @endif
                            </div>

                            <div class="inv-field">
                                <label class="form-label">Site Name <span class="text-danger">*</span></label>
                                <input
                                    class="form-control {{ $hasErr('site_name') ? 'inv-input-invalid' : '' }}"
                                    name="site_name"
                                    value="{{ $oldSite }}"
                                    placeholder="e.g. Westlands POP"
                                    required
                                >
                                @if($hasErr('site_name'))
                                    <div class="inv-error">{{ $errMsg('site_name') }}</div>
                                @endif
                            </div>

                            <div class="inv-field">
                                <label class="form-label">Site Code (optional)</label>
                                <input
                                    class="form-control {{ $hasErr('site_code') ? 'inv-input-invalid' : '' }}"
                                    name="site_code"
                                    value="{{ $oldSiteCode }}"
                                    placeholder="e.g. NBI-WLD-003"
                                >
                                @if($hasErr('site_code'))
                                    <div class="inv-error">{{ $errMsg('site_code') }}</div>
                                @endif
                            </div>

                            <div class="inv-field">
                                <label class="form-label">Reference (optional)</label>
                                <input
                                    class="form-control {{ $hasErr('reference') ? 'inv-input-invalid' : '' }}"
                                    name="reference"
                                    value="{{ $oldRef }}"
                                    placeholder="Ticket / job card / request…"
                                >
                                @if($hasErr('reference'))
                                    <div class="inv-error">{{ $errMsg('reference') }}</div>
                                @endif
                            </div>

                            <div class="inv-field">
                                <label class="form-label">Notes (optional)</label>
                                <textarea class="form-control {{ $hasErr('notes') ? 'inv-input-invalid' : '' }}" name="notes" rows="2">{{ $oldNotes }}</textarea>
                                @if($hasErr('notes'))
                                    <div class="inv-error">{{ $errMsg('notes') }}</div>
                                @endif
                            </div>

                            <button class="btn btn-dark w-100" {{ $avail <= 0 ? 'disabled' : '' }}>
                                Deploy Serial
                            </button>
                        </form>
                    @else
                        <div style="font-weight:900; margin-bottom:8px;">Deploy Bulk Qty</div>

                        <form method="POST" action="{{ route('inventory.deployments.store') }}" data-inv-loading>
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $assignment->item_id }}">

                            <div class="inv-field">
                                <label class="form-label">Site Name <span class="text-danger">*</span></label>
                                <input
                                    class="form-control {{ $hasErr('site_name') ? 'inv-input-invalid' : '' }}"
                                    name="site_name"
                                    value="{{ $oldSite }}"
                                    placeholder="e.g. Westlands POP"
                                    required
                                >
                                @if($hasErr('site_name'))
                                    <div class="inv-error">{{ $errMsg('site_name') }}</div>
                                @endif
                            </div>

                            <div class="inv-field">
                                <label class="form-label">Site Code (optional)</label>
                                <input
                                    class="form-control {{ $hasErr('site_code') ? 'inv-input-invalid' : '' }}"
                                    name="site_code"
                                    value="{{ $oldSiteCode }}"
                                    placeholder="e.g. NBI-WLD-003"
                                >
                                @if($hasErr('site_code'))
                                    <div class="inv-error">{{ $errMsg('site_code') }}</div>
                                @endif
                            </div>

                            <div class="inv-field">
                                <label class="form-label">Qty <span class="text-danger">*</span></label>
                                <input
                                    class="form-control {{ $hasErr('qty') ? 'inv-input-invalid' : '' }}"
                                    type="number"
                                    min="1"
                                    name="qty"
                                    value="{{ $oldQty }}"
                                    required
                                >
                                <div class="inv-hint">Must be ≤ available allocation.</div>
                                @if($hasErr('qty'))
                                    <div class="inv-error">{{ $errMsg('qty') }}</div>
                                @endif
                            </div>

                            <div class="inv-field">
                                <label class="form-label">Reference (optional)</label>
                                <input
                                    class="form-control {{ $hasErr('reference') ? 'inv-input-invalid' : '' }}"
                                    name="reference"
                                    value="{{ $oldRef }}"
                                    placeholder="Ticket / job card / request…"
                                >
                                @if($hasErr('reference'))
                                    <div class="inv-error">{{ $errMsg('reference') }}</div>
                                @endif
                            </div>

                            <div class="inv-field">
                                <label class="form-label">Notes (optional)</label>
                                <textarea class="form-control {{ $hasErr('notes') ? 'inv-input-invalid' : '' }}" name="notes" rows="2">{{ $oldNotes }}</textarea>
                                @if($hasErr('notes'))
                                    <div class="inv-error">{{ $errMsg('notes') }}</div>
                                @endif
                            </div>

                            <button class="btn btn-dark w-100" {{ $avail <= 0 ? 'disabled' : '' }}>
                                Deploy Qty
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Right: Notes --}}
    <div class="col-lg-7">
        <div class="inv-card inv-panel">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Notes</p>
                    <div class="inv-panel-sub">What deployment changes in the system.</div>
                </div>
                <span class="inv-chip">Info</span>
            </div>

            <div class="inv-panel-body">
                <div class="inv-note">
                    <div style="font-weight:900; margin-bottom:6px;">Deployment updates</div>
                    <ul class="mb-0" style="margin-left: 18px;">
                        <li><strong>Serialized:</strong> unit status becomes <code>deployed</code> and stores site details.</li>
                        <li><strong>Bulk:</strong> assignment’s deployed qty increments (store qty already reduced at assignment).</li>
                        <li>Every action is written to <strong>Inventory Logs</strong> for audits.</li>
                    </ul>
                </div>

                <div class="inv-divider"></div>

                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.tech.items.index') }}" data-inv-loading>Back to list</a>
                    <a class="btn btn-outline-dark" href="{{ route('inventory.logs.index') }}" data-inv-loading>Open logs</a>
                    <a class="btn btn-outline-dark" href="{{ route('inventory.deployments.index') }}" data-inv-loading>Deployments</a>
                </div>
            </div>
        </div>

        <div class="inv-card inv-panel mt-3">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Safety</p>
                    <div class="inv-panel-sub">Avoid “oops” deployments.</div>
                </div>
                <span class="inv-chip">⚠️</span>
            </div>
            <div class="inv-panel-body">
                <div class="inv-muted" style="font-size:13px;">
                    • Confirm the site name before submitting<br>
                    • Use reference for tickets/jobs when possible<br>
                    • Don’t deploy above available allocation
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
