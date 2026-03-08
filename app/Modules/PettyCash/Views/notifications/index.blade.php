@extends('pettycash::layouts.app')

@section('title','Payment Reminder Notifications')

@push('styles')
<style>
    .wrap{max-width:1220px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    .top{display:flex;justify-content:space-between;align-items:end;gap:10px;flex-wrap:wrap}
    .muted{color:#667085;font-size:12px}
    .btn2{display:inline-block;padding:9px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-weight:800;cursor:pointer;transition:.15s}
    .btn2:hover{border-color:#98a2b3;background:#f9fafb}
    .btn{display:inline-block;padding:9px 12px;border-radius:10px;background:#175cd3;border:1px solid #175cd3;color:#fff;text-decoration:none;font-weight:800;cursor:pointer;transition:.15s}
    .btn:hover{background:#1849a9;border-color:#1849a9}
    .btn-danger{display:inline-block;padding:7px 10px;border-radius:10px;border:1px solid #fda29b;background:#fff;color:#b42318;text-decoration:none;font-weight:700;cursor:pointer}
    .alert{background:#ecfdf3;border:1px solid #abefc6;color:#027a48;padding:10px 12px;border-radius:10px;margin-top:12px}
    .alert-error{background:#fef3f2;border-color:#fecdca;color:#b42318}

    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px;vertical-align:top}
    th{font-size:12px;color:#475467;text-align:left;white-space:nowrap}
    .pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px;font-weight:900}
    .p-unread{background:#eff8ff;border:1px solid #b2ddff;color:#175cd3}
    .p-read{background:#f2f4f7;border:1px solid #eaecf0;color:#667085}
    .p-overdue{background:#fef3f2;border:1px solid #fecdca;color:#b42318}
    .p-due{background:#fffaeb;border:1px solid #fedf89;color:#b54708}
    .p-soon{background:#ecfdf3;border:1px solid #abefc6;color:#027a48}

    .filters{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;align-items:center}
    .input{width:min(420px,100%);padding:10px 12px;border:1px solid #d0d5dd;border-radius:12px;font-size:13px;outline:none;background:#fff}
    .input:focus{border-color:#2e90fa;box-shadow:0 0 0 3px rgba(46,144,250,.12)}
    textarea.input{min-height:120px;resize:vertical}
    .stack{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
    .sub-title{margin:0;font-size:16px;font-weight:900}

    .grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:980px){.grid2{grid-template-columns:1fr}}

    .placeholder-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:8px;margin-top:10px}
    .placeholder-card{border:1px solid #e7e9f2;border-radius:10px;padding:10px;background:#fcfcfd}
    .placeholder-card .key{font-family:monospace;font-weight:800;color:#101828;font-size:12px}
    .placeholder-card .desc{font-size:12px;color:#667085;margin-top:6px}

    .table-wrap{overflow:auto;border:1px solid #eef2f6;border-radius:12px;margin-top:10px}
    .pager{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:12px}
    .section-label{margin:0 0 8px;font-size:15px;font-weight:900}
    .sms-help{margin:0;padding:8px 10px;border-radius:10px;border:1px solid #e0eaff;background:#eff8ff;color:#1849a9;font-size:12px}
    .checkbox-inline{display:flex;gap:8px;align-items:center;padding:10px 0}

    .sms-modal{
        position:fixed;
        inset:0;
        z-index:80;
        display:none;
    }
    .sms-modal.show{display:block}
    .sms-backdrop{
        position:absolute;
        inset:0;
        background:rgba(16,24,40,.45);
    }
    .sms-panel{
        position:relative;
        z-index:1;
        width:min(1200px, calc(100vw - 24px));
        max-height:calc(100vh - 24px);
        margin:12px auto;
        overflow:auto;
        background:#f8f9fc;
        border:1px solid #d0d5dd;
        border-radius:16px;
        padding:16px;
    }
    .sms-modal-top{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }
    .sms-section{
        border:1px solid #e4e7ec;
        border-radius:12px;
        background:#fff;
        padding:12px;
    }
    .sms-section + .sms-section{margin-top:12px}
    body.sms-modal-open{overflow:hidden}
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="top">
        <div>
            <div class="muted">Unread: <strong>{{ $unreadCount ?? 0 }}</strong></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button class="btn" type="button" onclick="openSmsSettingsModal()">Configure SMS Settings</button>
            <form method="POST" action="{{ route('petty.notifications.read_all') }}" style="margin:0">
                @csrf
                <button class="btn2" type="submit">Mark all read</button>
            </form>
            <a class="btn2" href="{{ route('petty.dashboard') }}">Dashboard</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <h3 class="sub-title">In-App Notifications</h3>
        <form class="filters" method="GET" action="{{ route('petty.notifications.index') }}">
            <input class="input" name="q" value="{{ $q ?? '' }}" placeholder="Search hostel, meter, phone, title...">
            <select class="input" style="width:220px" name="type">
                <option value="">All types</option>
                @foreach(['token_due_3','token_due_2','token_due_1','token_due_today','token_overdue','due_tomorrow_shortfall','low_balance','low_credit'] as $t)
                    <option value="{{ $t }}" @if(($type ?? '') === $t) selected @endif>{{ $t }}</option>
                @endforeach
            </select>
            <select class="input" style="width:160px" name="unread">
                <option value="">All</option>
                <option value="1" @if((string)($unread ?? '') === '1') selected @endif>Unread</option>
                <option value="0" @if((string)($unread ?? '') === '0') selected @endif>Read</option>
            </select>
            <button class="btn2" type="submit">Filter</button>
            @if(!empty($q) || !empty($type) || (($unread ?? '') !== ''))
                <a class="btn2" href="{{ route('petty.notifications.index') }}">Clear</a>
            @endif
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Status</th>
                    <th>Hostel</th>
                    <th>Meter</th>
                    <th>Phone</th>
                    <th>Due</th>
                    <th>Message</th>
                    <th>Created</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $n)
                    @php
                        $statusPill = $n->is_read ? 'p-read' : 'p-unread';
                        $typePill = 'p-soon';
                        if($n->type === 'token_overdue' || $n->type === 'due_tomorrow_shortfall' || $n->type === 'low_balance' || $n->type === 'low_credit') $typePill = 'p-overdue';
                        elseif($n->type === 'token_due_today') $typePill = 'p-due';
                    @endphp
                    <tr>
                        <td>
                            <div class="pill {{ $statusPill }}">{{ $n->is_read ? 'READ' : 'UNREAD' }}</div>
                            <div style="margin-top:6px" class="pill {{ $typePill }}">{{ $n->type }}</div>
                        </td>
                        <td style="font-weight:900">{{ $n->hostel_name ?? '-' }}</td>
                        <td>{{ $n->meter_no ?? '-' }}</td>
                        <td>{{ $n->phone_no ?? '-' }}</td>
                        <td>{{ $n->due_date?->format('Y-m-d') ?? '-' }}</td>
                        <td style="white-space:pre-line">{{ $n->message ?? $n->title }}</td>
                        <td class="muted">{{ $n->created_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            @if(!$n->is_read)
                                <form method="POST" action="{{ route('petty.notifications.read_one', $n->id) }}" style="margin:0">
                                    @csrf
                                    <button class="btn2" type="submit">Mark read</button>
                                </form>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted" style="padding:16px">No notifications yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">
            <div class="muted">Page {{ $items->currentPage() }} of {{ max(1, $items->lastPage()) }}</div>
            <div>{{ $items->onEachSide(1)->links('pettycash::partials.pagination') }}</div>
        </div>
    </div>

    <div id="smsSettingsModal" class="sms-modal" aria-hidden="true">
        <div class="sms-backdrop" onclick="closeSmsSettingsModal()"></div>
        <div class="sms-panel" role="dialog" aria-modal="true" aria-labelledby="smsSettingsTitle">
            <div class="sms-modal-top">
                <h3 id="smsSettingsTitle" class="sub-title">SMS Notification Settings</h3>
                <button class="btn2" type="button" onclick="closeSmsSettingsModal()">Close</button>
            </div>

            <div class="sms-section" style="margin-top:12px">
                <p class="sms-help">
                    Configure admin numbers, reusable SMS templates, and assign each event to a specific template.
                </p>
            </div>

            <div class="grid2" style="margin-top:12px">
                <section class="sms-section">
                    <h4 class="section-label">Admin Contacts</h4>
                    <form class="pc-form" method="POST" action="{{ route('petty.notifications.admin_contacts.store') }}">
                        @csrf
                        <input type="hidden" name="sms_form" value="admin_contact">
                        <div class="pc-field">
                            <label>Name</label>
                            <input class="pc-input" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="pc-field">
                            <label>Role</label>
                            <input class="pc-input" name="role" value="{{ old('role') }}" placeholder="Admin / Accountant / Ops">
                        </div>
                        <div class="pc-field">
                            <label>Phone Number</label>
                            <input class="pc-input" name="phone_no" value="{{ old('phone_no') }}" placeholder="2547XXXXXXXX" required>
                        </div>
                        <div class="pc-field">
                            <label class="pc-check">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')> Active
                            </label>
                        </div>
                        <div class="pc-actions">
                            <button class="btn" type="submit">Save Contact</button>
                        </div>
                    </form>

                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Number</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($adminContacts as $c)
                                <tr>
                                    <td><strong>{{ $c->name }}</strong></td>
                                    <td>{{ $c->role ?: '-' }}</td>
                                    <td>{{ $c->phone_no }}</td>
                                    <td>{{ $c->is_active ? 'Active' : 'Inactive' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('petty.notifications.admin_contacts.destroy', $c->id) }}" onsubmit="return confirm('Remove this admin contact?')" style="margin:0">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-danger" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="muted">No admin contacts yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="sms-section">
                    <h4 class="section-label">Template Editor</h4>
                    <form class="pc-form" method="POST" action="{{ route('petty.notifications.sms_templates.store') }}">
                        @csrf
                        <input type="hidden" name="sms_form" value="sms_template">
                        <div class="pc-field">
                            <label>Template Name</label>
                            <input class="pc-input" name="name" value="{{ old('name') }}" placeholder="Low Balance Alert" required>
                        </div>
                        <div class="pc-field">
                            <label class="pc-check">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')> Active
                            </label>
                        </div>
                        <div class="pc-field full">
                            <label>Template Body</label>
                            <textarea class="pc-textarea" name="body" placeholder="Dear &#123;&#123;name&#125;&#125;, petty cash balance is &#123;&#123;balance&#125;&#125;. Please top up.">{{ old('body') }}</textarea>
                        </div>
                        <div class="pc-actions">
                            <button class="btn" type="submit">Save Template</button>
                        </div>
                    </form>

                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Template</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($templates as $t)
                                <tr>
                                    <td><strong>{{ $t->name }}</strong></td>
                                    <td>{{ $t->is_active ? 'Active' : 'Inactive' }}</td>
                                    <td style="max-width:320px;white-space:pre-line">{{ $t->body }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('petty.notifications.sms_templates.destroy', $t->id) }}" onsubmit="return confirm('Remove this template?')" style="margin:0">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-danger" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted">No templates yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <section class="sms-section" style="margin-top:12px">
                <h4 class="section-label">Template Usage and Alert Thresholds</h4>
                <form method="POST" action="{{ route('petty.notifications.sms_settings.save') }}">
                    @csrf
                    <input type="hidden" name="sms_form" value="sms_settings">

                    <div class="grid2">
                        <div>
                            <div class="muted">SMS Gateway</div>
                            <select class="input" name="sms_gateway">
                                <option value="advanta" @selected(old('sms_gateway', $settings->sms_gateway ?? 'advanta') === 'advanta')>Advanta (default)</option>
                                <option value="amazons" @selected(old('sms_gateway', $settings->sms_gateway ?? '') === 'amazons')>Amazons</option>
                            </select>
                        </div>

                        <div>
                            <div class="muted">SMS Enabled</div>
                            <label class="muted checkbox-inline">
                                <input type="hidden" name="sms_enabled" value="0">
                                <input type="checkbox" name="sms_enabled" value="1" @checked((string) old('sms_enabled', ($settings->sms_enabled ?? true) ? '1' : '0') === '1')> Enable SMS sending
                            </label>
                        </div>

                        <div>
                            <div class="muted">Low Balance Threshold</div>
                            <input class="input" type="number" step="0.01" min="0" name="low_balance_threshold" value="{{ old('low_balance_threshold', number_format((float)($settings->low_balance_threshold ?? 0), 2, '.', '')) }}">
                        </div>

                        <div>
                            <div class="muted">Low Credit Threshold</div>
                            <input class="input" type="number" step="0.01" min="0" name="low_credit_threshold" value="{{ old('low_credit_threshold', number_format((float)($settings->low_credit_threshold ?? 0), 2, '.', '')) }}">
                        </div>
                    </div>

                    <div style="margin-top:12px">
                        <div class="muted">Choose template per event</div>
                        <div class="grid2" style="margin-top:8px">
                            @foreach($eventOptions as $eventKey => $eventLabel)
                                <div>
                                    <div class="muted">{{ $eventLabel }}</div>
                                    <select class="input" name="template_usage[{{ $eventKey }}]">
                                        <option value="">Default message (no template)</option>
                                        @foreach($templates as $t)
                                            <option value="{{ $t->id }}" @selected((string) old('template_usage.' . $eventKey, (string) ($templateUsage[$eventKey] ?? '')) === (string)$t->id)>
                                                {{ $t->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div style="margin-top:12px">
                        <button class="btn" type="submit">Save SMS Settings</button>
                    </div>
                </form>

                <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
                    <form method="POST" action="{{ route('petty.notifications.auto_check') }}" style="margin:0">
                        @csrf
                        <input type="hidden" name="notify_admins" value="0">
                        <button class="btn2" type="submit">Run Due-Tomorrow Auto-Check</button>
                    </form>
                </div>

                @if(!empty($autoCheckResult))
                    @php
                        $hasShortfall = (bool)($autoCheckResult['has_shortfall'] ?? false);
                        $sentSms = (int)($autoCheckResult['sent_sms'] ?? 0);
                        $notifyRequested = (bool)($autoCheckResult['notify_requested'] ?? false);
                    @endphp
                    <div class="alert {{ $hasShortfall ? 'alert-error' : '' }}" style="margin-top:12px">
                        <div><strong>Auto-Check Result</strong></div>
                        <div>Available balance: <strong>{{ number_format((float)($autoCheckResult['available_balance'] ?? 0), 2) }}</strong></div>
                        <div>Expected tomorrow amount: <strong>{{ number_format((float)($autoCheckResult['due_tomorrow_total'] ?? 0), 2) }}</strong></div>
                        <div>Shortfall: <strong>{{ number_format((float)($autoCheckResult['shortfall'] ?? 0), 2) }}</strong></div>
                        @if($hasShortfall)
                            <div>SMS sent: <strong>{{ $sentSms }}</strong></div>
                            @if(!$notifyRequested)
                                <form method="POST" action="{{ route('petty.notifications.auto_check') }}" style="margin-top:10px">
                                    @csrf
                                    <input type="hidden" name="notify_admins" value="1">
                                    <button class="btn" type="submit">Notify Admins Now</button>
                                </form>
                            @endif
                        @endif
                    </div>
                @endif
            </section>

            <section class="sms-section" style="margin-top:12px">
                <h4 class="section-label">Placeholder Cards</h4>
                <div class="muted">Use these placeholders in template body, e.g. <code>Dear @{{name}}, balance is @{{balance}}</code>.</div>
                <div class="placeholder-grid">
                    @foreach($placeholderCards as $p)
                        <div class="placeholder-card">
                            <div class="key">{{ $p['key'] }}</div>
                            <div class="desc">{{ $p['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openSmsSettingsModal() {
    const modal = document.getElementById('smsSettingsModal');
    if (!modal) return;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('sms-modal-open');
}

function closeSmsSettingsModal() {
    const modal = document.getElementById('smsSettingsModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('sms-modal-open');
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeSmsSettingsModal();
    }
});

const shouldOpenSmsSettings = @json((bool) session('open_sms_settings') || (bool) old('sms_form'));
if (shouldOpenSmsSettings) {
    openSmsSettingsModal();
}
</script>
@endpush
@endsection
