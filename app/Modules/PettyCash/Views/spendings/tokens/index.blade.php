@extends('pettycash::layouts.app')

@section('title','Token - Hostels')

@push('styles')
<style>
    .wrap{max-width:none;margin:0}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px;overflow:visible}
    .top{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
    .muted{color:#667085;font-size:12px}
    .success{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px;border-radius:10px;margin-top:12px}

    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px;vertical-align:top;overflow-wrap:anywhere}
    th{font-size:12px;color:#475467;text-align:left;white-space:normal}

    .btn{display:inline-block;padding:8px 11px;border-radius:9px;background:#7f56d9;color:#fff;text-decoration:none;font-weight:700;border:none;cursor:pointer;font-size:13px;line-height:1.15}
    .btn2{display:inline-block;padding:8px 11px;border-radius:9px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:700;font-size:13px;line-height:1.15}
    .btn2:hover{background:#f9fafb}
    .btn2-slim{
        padding:5px 8px;
        border-radius:8px;
        font-size:11px;
        line-height:1.1;
    }
    .actions-trigger{
        min-height:28px;
        padding:5px 8px;
        border-radius:8px;
        font-size:11px;
    }
    .action-menu{position:relative;display:inline-block}
    .action-menu[open]{z-index:1200}
    .action-menu > summary{list-style:none;cursor:pointer;user-select:none}
    .action-menu > summary::-webkit-details-marker{display:none}
    .action-menu-list{
        position:absolute;
        right:0;
        top:calc(100% + 6px);
        z-index:2000;
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
    .action-menu-item.is-highlight{color:#175cd3}
    .action-menu-item.is-disabled{
        color:#98a2b3;
        background:#f9fafb;
        cursor:not-allowed;
    }

    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px;white-space:nowrap}
    .pill-link{display:inline;text-decoration:none;overflow-wrap:anywhere;word-break:break-word}
    .pill-link:hover{opacity:.9}

    /* Status badges */
    .badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap}
    .b-upcoming{background:#eff8ff;border:1px solid #b2ddff;color:#175cd3}
    .b-due{background:#fffaeb;border:1px solid #fedf89;color:#b54708}
    .b-overdue{background:#fef3f2;border:1px solid #fecdca;color:#b42318}
    .b-unknown{background:#f2f4f7;border:1px solid #eaecf0;color:#344054}

    /* Reminder cards */
    .rem-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
        gap:12px;
        align-items:stretch;
    }
    .rem-grid > *{min-width:0}
    .rem-card{
        border:1px solid #e7e9f2;
        background:#fff;
        border-radius:14px;
        padding:12px;
        box-shadow:0 6px 18px rgba(16,24,40,.06);
        min-height:96px;
    }
    .rem-title{font-size:12px;color:#475467;margin:0}
    .rem-count{font-size:20px;font-weight:900;margin:3px 0 0}
    .rem-hint{margin:6px 0 0;font-size:12px;color:#667085}
    .rem-card.due{border-color:#fedf89}
    .rem-card.overdue{border-color:#fecdca}
    .rem-card.soon{border-color:#b2ddff}

    /* Search bar */
    .filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:12px}
    .input{width:min(420px,100%);padding:10px 12px;border:1px solid #d0d5dd;border-radius:12px;font-size:13px;outline:none}
    .input:focus{border-color:#7f56d9;box-shadow:0 0 0 4px rgba(127,86,217,.12)}
    .mini{font-size:12px;color:#667085}
    .right{margin-left:auto;display:flex;gap:10px;flex-wrap:wrap}

    /* Responsive table */
    .table-wrap{
        overflow-x:hidden;
        overflow-y:visible;
        border-radius:14px;
        border:1px solid #eef2f6;
        margin-top:12px;
        background:#fff;
    }
    .table-wrap.table-wrap-hostels{
        overflow:visible !important;
        position:relative;
        z-index:5;
    }
    .table-wrap table{
        margin-top:0;
        width:100%;
        min-width:100% !important;
        table-layout:auto;
    }
    .table-wrap th,.table-wrap td{padding:12px 10px}
    .table-wrap td{white-space:normal}
    .table-wrap tbody tr:hover{background:#f9fbff}
    .table-wrap .nowrap{white-space:normal}
    .col-site-sn{
        white-space:nowrap !important;
        word-break:normal !important;
        overflow-wrap:normal !important;
    }
    .col-site-sn .site-sn-text{
        white-space:nowrap;
        font-weight:700;
    }
    .col-routers{
        width:64px;
        min-width:64px;
        text-align:center;
    }
    .col-actions{
        width:96px;
        min-width:96px;
    }

    .section-title{
        font-weight:900;
        font-size:16px;
        display:flex;
        align-items:center;
        gap:8px;
    }
    .section-title::before{
        content:"";
        width:6px;
        height:18px;
        border-radius:99px;
        background:#7f56d9;
    }
    .footer-note{
        margin-top:10px;
        border-top:1px dashed #d0d5dd;
        padding-top:10px;
    }
    .pager{
        margin-top:12px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }
    .pager-meta{
        font-size:12px;
        color:#667085;
    }
    .pager-nav{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:wrap;
    }
    .pg-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:36px;
        height:36px;
        padding:0 12px;
        border-radius:10px;
        border:1px solid #d0d5dd;
        background:#fff;
        color:#344054;
        text-decoration:none;
        font-size:13px;
        font-weight:700;
        line-height:1;
        transition:.16s ease;
    }
    .pg-btn:hover{
        border-color:#98a2b3;
        background:#f9fafb;
    }
    .pg-btn.active{
        border-color:#111827;
        background:#111827;
        color:#fff;
    }
    .pg-btn.disabled{
        color:#98a2b3;
        background:#f9fafb;
        border-color:#eaecf0;
        pointer-events:none;
    }

    /* Stats row */
    .stat-grid{display:grid;grid-template-columns:repeat(3, minmax(0, 1fr));gap:10px;margin-top:12px}
    .stat-card{border:1px solid #e7e9f2;background:#fff;border-radius:14px;padding:12px;box-shadow:0 6px 18px rgba(16,24,40,.06)}
    .stat-label{font-size:12px;color:#475467;margin:0}
    .stat-value{font-size:22px;font-weight:900;margin:4px 0 0}
    .stat-sub{font-size:12px;color:#667085;margin-top:6px}
    .mobile-hostels{display:none;margin-top:12px}
    .mobile-hostels-shell{
        border:1px solid #e7e9f2;
        border-radius:16px;
        background:#fff;
        box-shadow:0 8px 22px rgba(16,24,40,.08);
        overflow:hidden;
    }
    .mobile-hostel-card{
        border-bottom:1px solid #eef2f6;
        background:#fff;
    }
    .mobile-hostel-card:last-child{border-bottom:none}
    .mobile-hostel-summary{
        list-style:none;
        display:grid;
        grid-template-columns:auto 1fr auto;
        align-items:center;
        gap:10px;
        padding:11px 12px;
        cursor:pointer;
    }
    .mobile-hostel-summary::-webkit-details-marker{display:none}
    .mobile-hostel-card[open] .mobile-hostel-summary{background:#f9fbff}
    .mobile-chevron{
        width:24px;
        height:24px;
        border-radius:8px;
        border:1px solid #d0d5dd;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        font-size:12px;
        color:#475467;
        transition:transform .16s ease;
        flex:0 0 auto;
    }
    .mobile-hostel-card[open] .mobile-chevron{transform:rotate(90deg)}
    .mobile-hostel-title{
        font-size:14px;
        font-weight:900;
        color:#101828;
        line-height:1.2;
        overflow-wrap:anywhere;
    }
    .mobile-hostel-sub{
        margin-top:3px;
        font-size:12px;
        color:#667085;
    }
    .mobile-hostel-right{
        text-align:right;
        display:grid;
        gap:5px;
        justify-items:end;
        flex:0 0 auto;
    }
    .mobile-hostel-amount{
        font-size:12px;
        font-weight:800;
        color:#101828;
    }
    .mobile-hostel-extra{
        border-top:1px solid #eef2f6;
        padding:10px 12px 12px;
        display:grid;
        gap:9px;
    }
    .mobile-kv{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:7px;
    }
    .mobile-kv-item{
        border:1px solid #eef2f6;
        border-radius:9px;
        padding:7px 8px;
        background:#fcfcfd;
    }
    .mobile-kv-label{
        font-size:10px;
        font-weight:800;
        letter-spacing:.04em;
        text-transform:uppercase;
        color:#667085;
    }
    .mobile-kv-value{
        margin-top:3px;
        font-size:13px;
        font-weight:800;
        color:#101828;
        overflow-wrap:anywhere;
    }
    .mobile-actions{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:7px;
    }
    .mobile-action-btn{
        display:flex;
        align-items:center;
        justify-content:center;
        width:100%;
        min-height:35px;
        padding:8px 9px;
        border-radius:9px;
        border:1px solid #d0d5dd;
        background:#fff;
        color:#344054;
        text-decoration:none;
        font-size:11px;
        font-weight:800;
        line-height:1.1;
    }
    .mobile-action-btn.primary{
        background:#7f56d9;
        border-color:#7f56d9;
        color:#fff;
    }
    .mobile-action-btn.disabled{
        background:#f9fafb;
        color:#98a2b3;
        border-color:#eaecf0;
        cursor:not-allowed;
    }
    .mobile-empty{
        padding:14px 12px;
        font-size:13px;
        color:#667085;
    }
    .status-banner{margin-top:12px;padding:12px;border-radius:12px;border:1px solid #d0d5dd;background:#f8fafc;color:#344054;font-size:13px}
    .status-banner.error{border-color:#fecdca;background:#fef3f2;color:#b42318}
    .status-banner.ok{border-color:#abefc6;background:#ecfdf3;color:#027a48}
    .pc-modal{position:fixed;inset:0;z-index:2000;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:18px}
    .pc-modal.show{display:flex}
    .pc-modal-panel{width:min(900px,100%);max-height:92vh;overflow:auto;background:#fff;border-radius:14px;border:1px solid #e7e9f2;box-shadow:0 22px 50px rgba(16,24,40,.25)}
    .pc-modal-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid #eaecf0}
    .pc-modal-body{padding:14px 16px}
    .pc-close{border:1px solid #d0d5dd;background:#fff;border-radius:10px;padding:6px 10px;font-weight:700;cursor:pointer}
    body.pc-modal-open{overflow:hidden}
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
    .ont-preview{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .ont-meta{border:1px solid #eaecf0;background:#fcfcfd;border-radius:12px;padding:10px}
    .ont-meta .label{font-size:11px;letter-spacing:.04em;font-weight:800;color:#667085;text-transform:uppercase}
    .ont-meta .value{margin-top:4px;font-size:14px;font-weight:800;color:#101828}

    @media(max-width:900px){
        .btn,.btn2{padding:7px 10px;font-size:12px;border-radius:8px}
        .btn2-slim,.actions-trigger{padding:4px 7px;font-size:10px;min-height:26px}
        .ont-preview{grid-template-columns:1fr}
        .stat-grid{
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:8px;
        }
        .stat-card{padding:9px;border-radius:11px}
        .stat-label{font-size:10px}
        .stat-value{font-size:16px;margin-top:2px}
        .stat-sub{font-size:10px;margin-top:4px}
    }

    @media(max-width:520px){
        .rem-grid{grid-template-columns:repeat(auto-fit, minmax(150px, 1fr))}
    }

    @media(max-width:760px){
        .table-wrap{display:none}
        .mobile-hostels{display:block}
        .rem-grid{
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:8px;
        }
        .rem-card{
            min-height:86px;
            padding:10px;
        }
        .rem-count{font-size:18px}
        .rem-hint{font-size:11px}
    }
    @media(max-width:420px){
        .btn,.btn2{padding:6px 8px;font-size:11px}
        .btn2-slim,.actions-trigger{padding:4px 6px;font-size:10px;min-height:24px}
        .stat-grid{gap:6px}
        .stat-card{padding:7px}
        .stat-value{font-size:14px}
        .stat-sub{display:none}
        .rem-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
        .mobile-kv{grid-template-columns:1fr}
        .mobile-actions{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
@php
    // Total hostels (unfiltered) - safe if controller didn't provide it:
    // If you want true total even when filtered, pass $totalHostels from controller.
    $canAddHostel = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.create_hostel');
    $canEditHostel = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.edit_hostel');
    $canRecordPayment = \App\Modules\PettyCash\Support\PettyAccess::allows(auth('petty')->user(), 'tokens.record_payment');
    $filteredCount = isset($hostels)
        ? (method_exists($hostels, 'total') ? $hostels->total() : $hostels->count())
        : 0;
    $shownCount = isset($hostels) ? $hostels->count() : 0;
    $totalCount = isset($totalHostels) ? (int)$totalHostels : $filteredCount;
    $currentPerPage = isset($perPage) ? (int)$perPage : 25;
    $currentSortDue = $sortDue ?? 'asc';
    $pageSizes = $perPageOptions ?? [15, 25, 30, 50, 100];

    $hasSearch = !empty($q);
    $exportBase = [
        'q' => $q ?? '',
    ];
    $ontHostels = (array) ($ontCatalog['hostels'] ?? []);
    $ontAvailable = (bool) ($ontCatalog['available'] ?? false);
    $ontMessage = (string) ($ontCatalog['message'] ?? '');
    $selectedOntKey = (string) old('ont_key', '');
    if ($selectedOntKey === '' && old('hostel_name')) {
        $normalizedOldHostel = strtoupper(trim((string) old('hostel_name')));
        foreach ($ontHostels as $candidate) {
            if (strtoupper(trim((string) ($candidate['hostel_name'] ?? ''))) === $normalizedOldHostel) {
                $selectedOntKey = (string) ($candidate['key'] ?? '');
                break;
            }
        }
    }
    $modalRequest = strtolower((string) request('modal', ''));
    $oldContext = (string) old('form_context', '');
    $openModal = match ($oldContext) {
        'add_hostel_modal' => 'hostel-add',
        default => in_array($modalRequest, ['hostel-add'], true) ? $modalRequest : '',
    };
@endphp

<div class="wrap">
    <div class="top">
        <div>
            <h2 style="margin:0">Token (Hostels)</h2>
            {{-- <div class="muted">Click meter/phone → compact view to record payments. Reminders are based on last payment date.</div> --}}
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            @if($canAddHostel)
                <button class="btn" type="button" data-modal-target="hostel-add">+ Add Hostel</button>
            @endif
            @include('pettycash::partials.export_select', [
                'options' => [
                    'PDF' => route('petty.tokens.pdf', array_merge($exportBase, ['format' => 'pdf'])),
                    'CSV' => route('petty.tokens.pdf', array_merge($exportBase, ['format' => 'csv'])),
                    'Excel' => route('petty.tokens.pdf', array_merge($exportBase, ['format' => 'excel'])),
                ],
            ])
        </div>
    </div>

    @if($canAddHostel)
        <div class="pc-modal" data-modal="hostel-add" aria-hidden="true">
            <div class="pc-modal-panel" role="dialog" aria-modal="true" aria-label="Add hostel">
                <div class="pc-modal-head">
                    <h3 style="margin:0">Add Hostel</h3>
                    <button type="button" class="pc-close" data-modal-close>Close</button>
                </div>
                <div class="pc-modal-body">
                    @if(!$ontAvailable)
                        <div class="status-banner error" style="margin-top:0">
                            {{ $ontMessage !== '' ? $ontMessage : 'ONT directory is unavailable.' }}
                        </div>
                    @endif

                    @if($errors->any() && $oldContext === 'add_hostel_modal')
                        <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                    @endif

                    <form class="pc-form" method="POST" action="{{ route('petty.tokens.hostels.store') }}">
                        @csrf
                        <input type="hidden" name="form_context" value="add_hostel_modal">

                        <div class="pc-field full">
                            <label>ONT / Site</label>
                            <select class="pc-select ont-select-native" name="ont_key" id="ontKeyModal" @if(!$ontAvailable) disabled @endif required>
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
                                        @selected($selectedOntKey === $optKey)
                                    >
                                        {{ $optName }} @if($optSiteId !== '') • Site {{ $optSiteId }} @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="ont-smart" id="ontSmartIndex" data-search-url="{{ route('petty.tokens.onts.search', [], false) }}"></div>
                            <input type="hidden" name="hostel_name" id="hostelNameHiddenModal" value="{{ old('hostel_name') }}">
                        </div>

                        <div class="pc-field full">
                            <div class="ont-preview">
                                <div class="ont-meta">
                                    <div class="label">Selected Hostel Name</div>
                                    <div class="value" id="selectedHostelPreviewModal">-</div>
                                </div>
                                <div class="ont-meta">
                                    <div class="label">Selected Site</div>
                                    <div class="value" id="selectedSitePreviewModal">-</div>
                                </div>
                                <div class="ont-meta">
                                    <div class="label">Site S.N</div>
                                    <div class="value" id="selectedSnPreviewModal">-</div>
                                </div>
                                <div class="ont-meta">
                                    <div class="label">Merge Status</div>
                                    <div class="value">
                                        <span class="ont-status-chip muted" id="selectedMergeStatusModal">Not Added</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pc-field">
                            <label>Contact Person</label>
                            <input class="pc-input" name="contact_person" value="{{ old('contact_person') }}">
                        </div>

                        <div class="pc-field">
                            <label>No of Routers</label>
                            <input class="pc-input" id="noOfRoutersInputModal" type="number" min="0" name="no_of_routers" value="{{ old('no_of_routers', 0) }}">
                        </div>

                        <div class="pc-actions">
                            <button class="btn" type="submit" @disabled(!$ontAvailable)>Save and Continue to Agreement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Counter cards --}}
    <div class="stat-grid">
        <div class="stat-card">
            <p class="stat-label">Total Hostels</p>
            <div class="stat-value">{{ number_format($totalCount) }}</div>
            <div class="stat-sub">All hostels</div>
        </div>

        <div class="stat-card">
            <p class="stat-label">Listed</p>
            <div class="stat-value">{{ number_format($filteredCount) }}</div>
            <div class="stat-sub">
                @if($hasSearch)
                    Filtered results (search active)
                @else
                    All listed records
                @endif
            </div>
        </div>

        <div class="stat-card">
            <p class="stat-label">Today's Date</p>
            <div class="stat-value">{{ isset($today) ? $today->format('Y-m-d') : now()->format('Y-m-d') }}</div>
            {{-- <div class="stat-sub">Snapshot date</div> --}}
        </div>
    </div>

    {{-- Reminders summary --}}
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:end">
            <div>
                <div class="section-title">Payment Reminders</div>
                {{-- <div class="muted">Today: <strong>{{ isset($today) ? $today->format('Y-m-d') : now()->format('Y-m-d') }}</strong></div> --}}
            </div>
            {{-- <div class="mini">Due buckets: 3 days, 2 days, 1 day, due today, overdue.</div> --}}
        </div>

        @php
            $cDueToday = isset($reminders['due_today']) ? $reminders['due_today']->count() : 0;
            $c1 = isset($reminders['due_1']) ? $reminders['due_1']->count() : 0;
            $c2 = isset($reminders['due_2']) ? $reminders['due_2']->count() : 0;
            $c3 = isset($reminders['due_3']) ? $reminders['due_3']->count() : 0;
            $cOver = isset($reminders['overdue']) ? $reminders['overdue']->count() : 0;

            $sumDueToday = isset($reminders['due_today']) ? (float) $reminders['due_today']->sum(fn($h) => (float) ($h->amount_due ?? 0)) : 0;
            $sum1 = isset($reminders['due_1']) ? (float) $reminders['due_1']->sum(fn($h) => (float) ($h->amount_due ?? 0)) : 0;
            $sum2 = isset($reminders['due_2']) ? (float) $reminders['due_2']->sum(fn($h) => (float) ($h->amount_due ?? 0)) : 0;
            $sum3 = isset($reminders['due_3']) ? (float) $reminders['due_3']->sum(fn($h) => (float) ($h->amount_due ?? 0)) : 0;
            $sumOver = isset($reminders['overdue']) ? (float) $reminders['overdue']->sum(fn($h) => (float) ($h->amount_due ?? 0)) : 0;
        @endphp

        <div class="rem-grid" style="margin-top:12px">
            <div class="rem-card due">
                <p class="rem-title">Due Today</p>
                <div class="rem-count">{{ $cDueToday }}</div>
                <p class="rem-hint">Expected: KES {{ number_format($sumDueToday, 2) }}</p>
            </div>
            <div class="rem-card soon">
                <p class="rem-title">Due Tomorrow</p>
                <div class="rem-count">{{ $c1 }}</div>
                <p class="rem-hint">Expected: KES {{ number_format($sum1, 2) }}</p>
            </div>
            <div class="rem-card soon">
                <p class="rem-title">Due in 2 Days</p>
                <div class="rem-count">{{ $c2 }}</div>
                <p class="rem-hint">Expected: KES {{ number_format($sum2, 2) }}</p>
            </div>
            <div class="rem-card soon">
                <p class="rem-title">Due in 3 Days</p>
                <div class="rem-count">{{ $c3 }}</div>
                <p class="rem-hint">Expected: KES {{ number_format($sum3, 2) }}</p>
            </div>
            <div class="rem-card overdue">
                <p class="rem-title">Overdue</p>
                <div class="rem-count">{{ $cOver }}</div>
                <p class="rem-hint">Expected: KES {{ number_format($sumOver, 2) }}</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    @php $hasTokenFilter = !empty($q) || (int)$currentPerPage !== 25 || $currentSortDue !== 'asc'; @endphp
    <div class="pc-filter-dock">
        <details class="pc-filter-panel" @if($hasTokenFilter) open @endif>
            <summary>
                <span class="pc-filter-title">Filters</span>
                <span class="pc-filter-state">{{ $hasTokenFilter ? 'active' : 'optional' }}</span>
            </summary>
            <div class="pc-filter-body">
                <form method="GET" action="{{ route('petty.tokens.index') }}" class="pc-filter-row">
                    <input class="input pc-filter-grow" type="text" name="q" value="{{ $q ?? '' }}"
                           placeholder="Search hostel, site S.N, contact person, meter, phone…">
                    <select class="input" name="sort_due" style="width:190px" onchange="this.form.submit()">
                        <option value="asc" @selected($currentSortDue === 'asc')>Due Date: Earliest First</option>
                        <option value="desc" @selected($currentSortDue === 'desc')>Due Date: Latest First</option>
                    </select>
                    <select class="input" name="per_page" style="width:140px" onchange="this.form.submit()">
                        @foreach($pageSizes as $size)
                            <option value="{{ $size }}" @selected((int)$currentPerPage === (int)$size)>{{ $size }} / page</option>
                        @endforeach
                    </select>
                    <div class="right">
                        <button class="btn2" type="submit">Search</button>
                        @if($hasTokenFilter)
                            <a class="btn2" href="{{ route('petty.tokens.index') }}">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </details>
    </div>

    {{-- Table --}}
    <div class="table-wrap table-wrap-hostels">
        <table>
            <thead>
            <tr>
                <th>Hostel</th>
                <th class="col-site-sn">Site S.N</th>
                <th>Meter No</th>
                <th>Contact Person</th>
                <th>Phone</th>
                <th class="col-routers">Routers</th>
                <th>Stake</th>
                <th>Amount Due</th>
                <th>Last Payment</th>
                <th>Next Due</th>
                <th>Status</th>
                @if($canEditHostel)
                    <th class="col-actions">Management</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse($hostels as $h)
                @php
                    $status = $h->due_status ?? 'unknown';
                    $badge = $h->due_badge ?? '—';
                    $nextDue = $h->next_due_date ?? null;
                    $agreementType = strtolower(trim((string) ($h->agreement_type ?? 'none')));
                    if (!in_array($agreementType, ['token', 'send_money', 'package', 'none'], true)) {
                        $agreementType = 'none';
                    }
                    $agreementConfigured = $agreementType !== 'none'
                        || trim((string) ($h->agreement_label ?? '')) !== '';
                    $agreementActionLabel = $agreementConfigured ? 'Update Agreement' : 'Set Agreement';
                    $canMarkOverpay = $canRecordPayment && $agreementType !== 'package' && !empty($h->last_payment_date);
                    $agreementTerminated = !empty($h->agreement_terminated_at);

                    $badgeClass = 'b-unknown';
                    if($status === 'overdue') $badgeClass = 'b-overdue';
                    elseif($status === 'due_today') $badgeClass = 'b-due';
                    elseif($status === 'upcoming') $badgeClass = 'b-upcoming';
                @endphp

                <tr>
                    <td data-label="Hostel">
                        <div style="font-weight:900">{{ $h->hostel_name }}</div>
                        <div class="muted">ID: {{ $h->id }}</div>
                    </td>
                    <td data-label="Site S.N" class="col-site-sn">
                        <span class="site-sn-text">{{ $h->ont_site_sn ?: '-' }}</span>
                    </td>
                    <td data-label="Meter No">
                        <a class="pill-link" href="{{ route('petty.tokens.hostels.show', $h->id) }}">
                            {{ $h->meter_no ?? '-' }}
                        </a>
                    </td>
                    <td data-label="Contact Person">
                        {{ $h->contact_person ?? '-' }}
                    </td>
                    <td data-label="Phone">
                        <a class="pill-link" href="{{ route('petty.tokens.hostels.show', $h->id) }}">
                            {{ $h->phone_no ?? '-' }}
                        </a>
                    </td>
                    <td data-label="Routers" class="col-routers">{{ $h->no_of_routers }}</td>
                    <td data-label="Stake"><span class="pill">{{ strtoupper($h->stake) }}</span></td>
                    <td data-label="Amount Due">{{ number_format((float)$h->amount_due, 2) }}</td>
                    <td data-label="Last Payment">
                        @if($h->last_payment_amount !== null)
                            <span class="pill">{{ number_format((float)$h->last_payment_amount, 2) }}</span>
                            <div class="muted">{{ $h->last_payment_date }}</div>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td data-label="Next Due">
                        @if($nextDue)
                            <span class="pill">{{ $nextDue }}</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td data-label="Status">
                        <span class="badge {{ $badgeClass }}">{{ $badge }}</span>
                    </td>
                    @if($canEditHostel)
                        <td data-label="Management" class="col-actions">
                            <details class="action-menu">
                                <summary class="btn2 btn2-slim actions-trigger">Actions ▾</summary>
                                <div class="action-menu-list">
                                    <a class="action-menu-item" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id]) }}">Open Hostel</a>
                                    @if($canMarkOverpay)
                                        <a class="action-menu-item" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id, 'modal' => 'overpay-mark']) }}">Mark Overpay</a>
                                    @endif
                                    <a class="action-menu-item is-highlight" href="{{ route('petty.tokens.hostels.agreement', ['hostel' => $h->id]) }}">{{ $agreementActionLabel }}</a>
                                    @if($terminationSupported ?? false)
                                        @if($agreementTerminated)
                                            <span class="action-menu-item is-disabled" aria-disabled="true">Agreement Terminated</span>
                                        @else
                                            <a class="action-menu-item" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id, 'modal' => 'agreement-terminate']) }}">Terminate Agreement</a>
                                        @endif
                                    @endif
                                    <a class="action-menu-item" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id, 'modal' => 'hostel-edit']) }}">Edit Details</a>
                                    @if((bool) ($h->ont_merged ?? false))
                                        @if(trim((string) ($h->ont_site_sn ?? '')) === '')
                                            <form method="POST" action="{{ route('petty.tokens.hostels.refresh_ont_sn', ['hostel' => $h->id]) }}" style="margin:0">
                                                @csrf
                                                <button type="submit" class="action-menu-item" title="Refresh Site S.N from ONT">&#8635; Refresh S.N</button>
                                            </form>
                                        @endif
                                        <span class="action-menu-item is-disabled" aria-disabled="true">Merged</span>
                                    @else
                                        <a class="action-menu-item" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id, 'modal' => 'hostel-merge']) }}">Merge ONT</a>
                                    @endif
                                </div>
                            </details>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $canEditHostel ? 12 : 11 }}" class="muted" style="padding:16px">No hostels yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mobile-hostels">
        <div class="mobile-hostels-shell">
            @forelse($hostels as $h)
                @php
                    $status = $h->due_status ?? 'unknown';
                    $badge = $h->due_badge ?? '—';
                    $nextDue = $h->next_due_date ?? null;
                    $agreementType = strtolower(trim((string) ($h->agreement_type ?? 'none')));
                    if (!in_array($agreementType, ['token', 'send_money', 'package', 'none'], true)) {
                        $agreementType = 'none';
                    }
                    $agreementConfigured = $agreementType !== 'none'
                        || trim((string) ($h->agreement_label ?? '')) !== '';
                    $agreementActionLabel = $agreementConfigured ? 'Update Agreement' : 'Set Agreement';
                    $canMarkOverpay = $canRecordPayment && $agreementType !== 'package' && !empty($h->last_payment_date);
                    $agreementTerminated = !empty($h->agreement_terminated_at);

                    $badgeClass = 'b-unknown';
                    if($status === 'overdue') $badgeClass = 'b-overdue';
                    elseif($status === 'due_today') $badgeClass = 'b-due';
                    elseif($status === 'upcoming') $badgeClass = 'b-upcoming';
                @endphp

                <details class="mobile-hostel-card">
                    <summary class="mobile-hostel-summary">
                        <span class="mobile-chevron">▸</span>
                        <div>
                            <div class="mobile-hostel-title">{{ $h->hostel_name }}</div>
                            <div class="mobile-hostel-sub">
                                ID: {{ $h->id }}
                                @if(trim((string) ($h->ont_site_sn ?? '')) !== '')
                                    • S.N {{ $h->ont_site_sn }}
                                @endif
                            </div>
                        </div>
                        <div class="mobile-hostel-right">
                            <span class="badge {{ $badgeClass }}">{{ $badge }}</span>
                            <div class="mobile-hostel-amount">KES {{ number_format((float)$h->amount_due, 2) }}</div>
                        </div>
                    </summary>
                    <div class="mobile-hostel-extra">
                        <div class="mobile-kv">
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Meter No</div>
                                <div class="mobile-kv-value">{{ $h->meter_no ?: '-' }}</div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Contact Person</div>
                                <div class="mobile-kv-value">{{ $h->contact_person ?: '-' }}</div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Phone</div>
                                <div class="mobile-kv-value">{{ $h->phone_no ?: '-' }}</div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Routers</div>
                                <div class="mobile-kv-value">{{ (int) $h->no_of_routers }}</div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Stake</div>
                                <div class="mobile-kv-value">{{ strtoupper((string) $h->stake) }}</div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Next Due</div>
                                <div class="mobile-kv-value">{{ $nextDue ?: '-' }}</div>
                            </div>
                            <div class="mobile-kv-item">
                                <div class="mobile-kv-label">Last Payment</div>
                                <div class="mobile-kv-value">
                                    @if($h->last_payment_amount !== null)
                                        {{ number_format((float)$h->last_payment_amount, 2) }} ({{ $h->last_payment_date }})
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mobile-actions">
                            <a class="mobile-action-btn primary" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id]) }}">Open Hostel</a>
                            @if($canMarkOverpay)
                                <a class="mobile-action-btn" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id, 'modal' => 'overpay-mark']) }}">Mark Overpay</a>
                            @endif
                            @if($canEditHostel)
                                <a class="mobile-action-btn" href="{{ route('petty.tokens.hostels.agreement', ['hostel' => $h->id]) }}">{{ $agreementActionLabel }}</a>
                                @if($terminationSupported ?? false)
                                    @if($agreementTerminated)
                                        <span class="mobile-action-btn disabled" aria-disabled="true">Agreement Terminated</span>
                                    @else
                                        <a class="mobile-action-btn" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id, 'modal' => 'agreement-terminate']) }}">Terminate Agreement</a>
                                    @endif
                                @endif
                                <a class="mobile-action-btn" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id, 'modal' => 'hostel-edit']) }}">Edit Details</a>
                                @if((bool) ($h->ont_merged ?? false))
                                    @if(trim((string) ($h->ont_site_sn ?? '')) === '')
                                        <form method="POST" action="{{ route('petty.tokens.hostels.refresh_ont_sn', ['hostel' => $h->id]) }}" style="margin:0">
                                            @csrf
                                            <button class="mobile-action-btn" type="submit">&#8635; Refresh S.N</button>
                                        </form>
                                    @endif
                                    <span class="mobile-action-btn disabled" aria-disabled="true">Merged</span>
                                @else
                                    <a class="mobile-action-btn" href="{{ route('petty.tokens.hostels.show', ['hostel' => $h->id, 'modal' => 'hostel-merge']) }}">Merge ONT</a>
                                @endif
                            @endif
                        </div>
                    </div>
                </details>
            @empty
                <div class="mobile-empty">No hostels yet.</div>
            @endforelse
        </div>
    </div>

    <div class="muted footer-note">
        Showing {{ number_format($shownCount) }} of {{ number_format($filteredCount) }} hostel{{ $filteredCount === 1 ? '' : 's' }}.
        Page size: <strong>{{ $currentPerPage }}</strong>.
    </div>

    @if(method_exists($hostels, 'links'))
        @php
            $current = $hostels->currentPage();
            $last = $hostels->lastPage();
            $start = max(1, $current - 2);
            $end = min($last, $current + 2);
        @endphp
        <div class="pager">
            <div class="pager-meta">
                Page <strong>{{ $current }}</strong> of <strong>{{ $last }}</strong>
            </div>

            <div class="pager-nav">
                @if($hostels->onFirstPage())
                    <span class="pg-btn disabled">Previous</span>
                @else
                    <a class="pg-btn" href="{{ $hostels->previousPageUrl() }}" rel="prev">Previous</a>
                @endif

                @if($start > 1)
                    <a class="pg-btn" href="{{ $hostels->url(1) }}">1</a>
                    @if($start > 2)
                        <span class="pg-btn disabled">...</span>
                    @endif
                @endif

                @for($page = $start; $page <= $end; $page++)
                    @if($page === $current)
                        <span class="pg-btn active">{{ $page }}</span>
                    @else
                        <a class="pg-btn" href="{{ $hostels->url($page) }}">{{ $page }}</a>
                    @endif
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)
                        <span class="pg-btn disabled">...</span>
                    @endif
                    <a class="pg-btn" href="{{ $hostels->url($last) }}">{{ $last }}</a>
                @endif

                @if($hostels->hasMorePages())
                    <a class="pg-btn" href="{{ $hostels->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="pg-btn disabled">Next</span>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const body = document.body;
    const modals = Array.from(document.querySelectorAll('[data-modal]'));
    const autoOpenModal = @json($openModal);

    function getModal(id) {
        return document.querySelector('[data-modal="' + id + '"]');
    }

    function openModal(id) {
        const modal = getModal(id);
        if (!modal) return;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        body.classList.add('pc-modal-open');
    }

    function closeModal(modal) {
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

    const mobileRows = Array.from(document.querySelectorAll('.mobile-hostels .mobile-hostel-card'));
    mobileRows.forEach(function (row) {
        row.addEventListener('toggle', function () {
            if (!row.open) return;
            mobileRows.forEach(function (other) {
                if (other !== row) other.removeAttribute('open');
            });
        });
    });

    const ontSelect = document.getElementById('ontKeyModal');
    const ontSmart = document.getElementById('ontSmartIndex');
    const hostelNameHidden = document.getElementById('hostelNameHiddenModal');
    const hostelPreview = document.getElementById('selectedHostelPreviewModal');
    const sitePreview = document.getElementById('selectedSitePreviewModal');
    const snPreview = document.getElementById('selectedSnPreviewModal');
    const mergeStatusPreview = document.getElementById('selectedMergeStatusModal');
    const searchUrl = ontSmart ? String(ontSmart.dataset.searchUrl || '') : '';

    function syncStatusChip(label, tone) {
        mergeStatusPreview.textContent = label || 'Not Added';
        mergeStatusPreview.classList.remove('success', 'muted');
        mergeStatusPreview.classList.add(tone === 'success' ? 'success' : 'muted');
    }

    function syncFromSelected() {
        const selected = ontSelect.options[ontSelect.selectedIndex];
        if (!selected || !selected.value) {
            hostelNameHidden.value = '';
            hostelPreview.textContent = '-';
            sitePreview.textContent = '-';
            snPreview.textContent = '-';
            syncStatusChip('Not Added', 'muted');
            return;
        }

        const hostelName = selected.dataset.name || '';
        const siteId = selected.dataset.siteId || '';
        const siteSn = selected.dataset.siteSn || '';
        const mergeLabel = selected.dataset.mergeStatusLabel || 'Not Added';
        const mergeTone = selected.dataset.mergeStatusTone || 'muted';

        hostelNameHidden.value = hostelName;
        hostelPreview.textContent = hostelName || '-';
        sitePreview.textContent = siteId !== '' ? ('Site ' + siteId) : 'No site id';
        snPreview.textContent = siteSn !== '' ? siteSn : '-';
        syncStatusChip(mergeLabel, mergeTone);
    }

    function buildSmartSelect(select, mountNode) {
        const defaultLabel = 'Search ONT site by name';
        let activeRequest = 0;

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ont-smart-trigger';
        trigger.disabled = select.disabled;

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

        select.addEventListener('change', updateTriggerLabel);
        updateTriggerLabel();
        renderHint('Type 2+ characters');
    }

    if (ontSelect && ontSmart && hostelNameHidden && hostelPreview && sitePreview && snPreview && mergeStatusPreview && searchUrl) {
        buildSmartSelect(ontSelect, ontSmart);
        ontSelect.addEventListener('change', syncFromSelected);
        syncFromSelected();
    }

    if (autoOpenModal) {
        openModal(autoOpenModal);
    }
})();
</script>
@endpush
