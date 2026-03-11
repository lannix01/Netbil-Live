@extends('pettycash::layouts.app')

@section('title','Add Hostel')

@push('styles')
<style>
    .status-banner{margin-top:12px;padding:12px;border-radius:12px;border:1px solid #d0d5dd;background:#f8fafc;color:#344054;font-size:13px}
    .status-banner.error{border-color:#fecdca;background:#fef3f2;color:#b42318}
    .status-banner.ok{border-color:#abefc6;background:#ecfdf3;color:#027a48}
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
        .ont-preview{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
@php
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
@endphp
<div class="form-wrap">
    <div class="form-header">
        <div>
            <h2>Add Hostel</h2>
            <div class="form-subtitle">Step 1 of 2: Create hostel profile from ONT, then set agreement details.</div>
        </div>
        <a class="btn2" href="{{ route('petty.tokens.index') }}">Back</a>
    </div>

    @if(!$ontAvailable)
        <div class="status-banner error">
            {{ $ontMessage !== '' ? $ontMessage : 'ONT directory is unavailable.' }}
        </div>
    @endif

    <div class="form-card">
        @if($errors->any())
            <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form class="pc-form" method="POST" action="{{ route('petty.tokens.hostels.store') }}">
            @csrf

            <div class="pc-field full">
                <label>ONT / Site</label>
                <select class="pc-select ont-select-native" name="ont_key" id="ontKey" @if(!$ontAvailable) disabled @endif required>
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
                <div class="ont-smart" id="ontSmartCreate" data-search-url="{{ route('petty.tokens.onts.search', [], false) }}"></div>
                <input type="hidden" name="hostel_name" id="hostelNameHidden" value="{{ old('hostel_name') }}">
            </div>

            <div class="pc-field full">
                <div class="ont-preview">
                    <div class="ont-meta">
                        <div class="label">Selected Hostel Name</div>
                        <div class="value" id="selectedHostelPreview">-</div>
                    </div>
                    <div class="ont-meta">
                        <div class="label">Selected Site</div>
                        <div class="value" id="selectedSitePreview">-</div>
                    </div>
                    <div class="ont-meta">
                        <div class="label">Site S.N</div>
                        <div class="value" id="selectedSnPreview">-</div>
                    </div>
                    <div class="ont-meta">
                        <div class="label">Merge Status</div>
                        <div class="value">
                            <span class="ont-status-chip muted" id="selectedMergeStatus">Not Added</span>
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
                <input class="pc-input" id="noOfRoutersInput" type="number" min="0" name="no_of_routers" value="{{ old('no_of_routers', 0) }}">
            </div>

            <div class="pc-field full">
                <div class="status-banner ok" style="margin-top:0">
                    After saving this step, you will set agreement type:
                    <strong>Token</strong>, <strong>Send Money</strong>, <strong>Package</strong>, or <strong>No Agreement</strong>.
                </div>
            </div>

            <div class="pc-actions">
                <button class="btn" type="submit" @disabled(!$ontAvailable)>Save and Continue to Agreement</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const ontSelect = document.getElementById('ontKey');
    const ontSmart = document.getElementById('ontSmartCreate');
    const hostelNameHidden = document.getElementById('hostelNameHidden');
    const hostelPreview = document.getElementById('selectedHostelPreview');
    const sitePreview = document.getElementById('selectedSitePreview');
    const snPreview = document.getElementById('selectedSnPreview');
    const mergeStatusPreview = document.getElementById('selectedMergeStatus');
    const searchUrl = ontSmart ? String(ontSmart.dataset.searchUrl || '') : '';

    if (!ontSelect || !ontSmart || !hostelNameHidden || !hostelPreview || !sitePreview || !snPreview || !mergeStatusPreview || !searchUrl) {
        return;
    }

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

    buildSmartSelect(ontSelect, ontSmart);
    ontSelect.addEventListener('change', syncFromSelected);
    syncFromSelected();
})();
</script>
@endpush
