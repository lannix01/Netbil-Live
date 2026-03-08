@extends('layouts.app')

@section('content')
<style>
:root{
    --bg:#f6f7fb;
    --card:#ffffff;
    --border:#e6e9f2;
    --text:#0f172a;
    --muted:#667085;
    --primary:#4e73df;
    --primary2:#2f5cf0;
    --danger:#e11d48;
    --success:#16a34a;
    --shadow:0 10px 30px rgba(16,24,40,.08);
    --shadow2:0 6px 14px rgba(16,24,40,.10);
    --r:14px;
    --r2:18px;
}

body{ background:var(--bg); }

.chat-wrap{ max-width:1320px; margin:0 auto; padding:18px 10px 40px; }
.page-title{
    display:flex; justify-content:space-between; align-items:flex-end; gap:14px; flex-wrap:wrap;
    margin-bottom:12px;
}
.page-title h1{ margin:0; font-weight:900; color:#1d4ed8; letter-spacing:.2px; }
.page-title p{ margin:0; color:var(--muted); font-size:.95rem; }

/* ===== mini stats ===== */
.stats-row{ margin-top:10px; }
.mini-stat{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:var(--r);
    padding:12px 12px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    min-height:76px;
    box-shadow:0 2px 10px rgba(16,24,40,.05);
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    position:relative;
    overflow:hidden;
}
.mini-stat:hover{ transform:translateY(-2px); box-shadow:var(--shadow2); }
.mini-stat.clickable{ cursor:pointer; }
.mini-stat.clickable:hover{ border-color:#b9c8ff; }
.mini-stat .k{ font-size:.72rem; color:var(--muted); font-weight:800; letter-spacing:.6px; text-transform:uppercase; }
.mini-stat .v{ font-size:1.35rem; font-weight:900; color:var(--text); line-height:1.05; margin-top:2px; }
.mini-stat .v small{ font-weight:800; color:var(--muted); font-size:.95rem; }
.mini-stat .ico{
    width:38px; height:38px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    border:1px solid var(--border);
    background:linear-gradient(180deg, #ffffff, #f4f6ff);
    color:#3b5be6;
}
.mini-stat .hint{
    position:absolute; right:10px; bottom:7px;
    font-size:.72rem; color:#94a3b8; font-style:italic;
    opacity:0; transform:translateY(3px);
    transition:opacity .18s ease, transform .18s ease;
}
.mini-stat.clickable:hover .hint{ opacity:1; transform:translateY(0); }

/* ===== cards ===== */
.panel-card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:var(--r);
    box-shadow:0 2px 10px rgba(16,24,40,.05);
    overflow:hidden;
}
.panel-head{
    padding:12px 14px;
    border-bottom:1px solid var(--border);
    background:linear-gradient(135deg, #c7d6ff 0%, #b6c8ff 55%, #aebfff 100%);
    color:#0b1220;
}
.panel-head h4, .panel-head h5{
    margin:0; font-weight:900;
    display:flex; align-items:center; gap:8px;
}
.panel-body{ padding:14px; }

/* ===== inputs/buttons ===== */
.form-control:focus, .form-select:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 .2rem rgba(78,115,223,.20);
}
.btn-primary{
    background:linear-gradient(135deg, #86a4ff 0%, #6f95ff 100%);
    border:none;
    font-weight:900;
    border-radius:12px;
}
.btn-primary:hover{
    background:linear-gradient(135deg, #2f5cf0 0%, #2448c9 100%);
    transform:translateY(-1px);
    box-shadow:0 8px 18px rgba(47,92,240,.25);
}
.btn-outline-primary{ border-radius:12px; font-weight:900; }
.btn-outline-primary.active{
    background:var(--primary);
    border-color:var(--primary);
    color:#fff;
}

/* ===== top layout: left send, right split (contacts + thread) ===== */
.top-grid{
    display:grid;
    grid-template-columns: 1fr 520px;
    gap:14px;
    align-items:stretch;
    margin-bottom:14px;
}
@media (max-width: 1200px){
    .top-grid{ grid-template-columns: 1fr 460px; }
}
@media (max-width: 992px){
    .top-grid{ grid-template-columns: 1fr; }
}

/* Right card split: contacts (left) + thread (right) */
.split-card{
    display:grid;
    grid-template-columns: 1fr 1.1fr;
    gap:0;
    min-height: 420px;
}
.split-left, .split-right{ min-width:0; }
.split-left{
    border-right:1px solid var(--border);
}
@media (max-width: 992px){
    .split-card{ grid-template-columns: 1fr; }
    .split-left{ border-right:none; border-bottom:1px solid var(--border); }
}

/* Contacts list container */
.recent-contacts{
    max-height: 360px;
    overflow:auto;
    padding:12px;
}
.recent-contacts::-webkit-scrollbar{ width:6px; }
.recent-contacts::-webkit-scrollbar-track{ background:#eef2ff; border-radius:10px; }
.recent-contacts::-webkit-scrollbar-thumb{ background:#90aafc; border-radius:10px; }
.recent-contacts::-webkit-scrollbar-thumb:hover{ background:#7f9cff; }

/* Active highlight support:
   - apply .rc-item on each contact row/link/button in partial
   - set data-phone="07xx" on each rc-item
*/
.rc-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:10px 10px;
    border:1px solid var(--border);
    border-radius:12px;
    background:#fff;
    margin-bottom:8px;
    transition:transform .12s ease, border-color .12s ease, box-shadow .12s ease;
}
.rc-item:hover{ transform:translateY(-1px); box-shadow:0 6px 14px rgba(16,24,40,.06); }
.rc-item.active{
    border-color:#93b0ff;
    box-shadow:0 10px 20px rgba(47,92,240,.12);
    background:linear-gradient(180deg, #ffffff, #f5f8ff);
}
.rc-meta{ min-width:0; }
.rc-title{ font-weight:900; color:var(--text); font-size:.95rem; margin:0; }
.rc-sub{ color:var(--muted); font-size:.82rem; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.rc-actions{ flex:0 0 auto; display:flex; gap:6px; align-items:center; }

/* Thread box */
.thread-shell{
    border:1px solid var(--border);
    border-radius:18px;
    background:linear-gradient(to bottom, #eef2ff, #ffffff);
    box-shadow: inset 0 0 16px rgba(2,6,23,.06);
    position:relative;
    padding:10px 10px;
    height: 360px;
    overflow:auto;
}
.thread-shell::before{
    content:'';
    position:sticky;
    top:0;
    display:block;
    width:64px;
    height:7px;
    margin:0 auto 10px auto;
    border-radius:99px;
    background:rgba(15,23,42,.35);
}
.thread-shell::-webkit-scrollbar{ width:4px; }
.thread-shell::-webkit-scrollbar-thumb{ background:var(--primary); border-radius:999px; }
.thread-empty{
    text-align:center;
    padding:28px 14px;
    color:#6b7280;
}
.thread-empty i{ font-size:3.1rem; opacity:.18; display:block; margin-bottom:10px; }

/* skeleton */
.skeleton{
    border-radius:12px;
    background:linear-gradient(90deg, #f1f5ff 0%, #e9eeff 40%, #f1f5ff 80%);
    background-size:200% 100%;
    animation: shimmer 1.1s infinite linear;
}
@keyframes shimmer{ from { background-position: 200% 0; } to { background-position: -200% 0; } }
.skel-row{ height:14px; margin:10px 0; }
.skel-row.w70{ width:70%; }
.skel-row.w85{ width:85%; }
.skel-row.w55{ width:55%; }
.skel-block{ padding:14px; border:1px solid var(--border); border-radius:14px; }

/* ===== table should be full width below ===== */
.table-card{ width:100%; }
.table-tools{
    display:flex; align-items:center; justify-content:space-between;
    gap:10px; flex-wrap:wrap;
    margin-bottom:10px;
}
.table-tools .search{
    min-width:220px;
    max-width:380px;
    width:100%;
}

/* modal */
.modal-content{ border-radius:14px; }
.modal-header{ border-bottom:1px solid var(--border); }
.modal-footer{ border-top:1px solid var(--border); }

.muted{ color:var(--muted); }
</style>

<div class="container-fluid">
    <div class="chat-wrap">

        <div class="page-title">
            <div>
                <h1 class="h3">Messages Panel</h1>
                <p>Send SMS, view chats, and manage comms.</p>
            </div>
        </div>

        <!-- Mini Stats -->
        <div class="row g-3 stats-row mb-3">
            <div class="col-md-3">
                <div class="mini-stat">
                    <div>
                        <div class="k">Advanta Balance</div>
                        <div class="v text-success">
                            @if(isset($advantaBalance) && $advantaBalance !== null)
                                {{ number_format($advantaBalance) }}
                            @else
                                <small>Unavailable</small>
                            @endif
                        </div>
                    </div>
                    <div class="ico"><i class="bi bi-chat-dots fs-5"></i></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="mini-stat clickable" onclick="scrollToMessages()">
                    <div>
                        <div class="k">Sent Today</div>
                        <div class="v">{{ $sentToday ?? 0 }}</div>
                    </div>
                    <div class="ico"><i class="bi bi-send fs-5"></i></div>
                    <div class="hint">Click to jump</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="mini-stat">
                    <div>
                        <div class="k">Delivery Rate</div>
                        <div class="v text-primary">{{ $deliveryRate ?? 0 }}%</div>
                    </div>
                    <div class="ico"><i class="bi bi-check2-circle fs-5"></i></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="mini-stat clickable" onclick="scrollToMessages('failed')">
                    <div>
                        <div class="k">Failed / Pending</div>
                        <div class="v text-danger">{{ $failedCount ?? 0 }}</div>
                    </div>
                    <div class="ico"><i class="bi bi-exclamation-triangle fs-5"></i></div>
                    <div class="hint">Click to filter</div>
                </div>
            </div>
        </div>

        <!-- TOP: Left send card, Right: One card split into Contacts + Thread -->
        <div class="top-grid">
            <!-- Left: New Message (exactly where it is, just kept on left) -->
            <div class="panel-card">
                <div class="panel-head">
                    <h4><i class="bi bi-envelope-plus"></i> New Message</h4>
                </div>
                <div class="panel-body">
                    <form id="smsForm" action="{{ route('chats.send') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="to" class="form-label fw-bold">Phone Number</label>
                            <input type="text" name="to" id="to" placeholder="e.g. 07xx..." class="form-control" required>
                            <div class="form-text muted">Tip: click a contact to fill & load its thread.</div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label fw-bold">Message Body</label>
                            <textarea name="message" id="message" rows="4" class="form-control" placeholder="Type your message..." required></textarea>
                            <div class="form-text" id="charCount">0 characters</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">SMS Gateway</label>
                            <select name="gateway" class="form-select">
                                <option value="advanta" selected>Advanta</option>
                                <option value="amazons">Amazons</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4" id="sendBtn">
                                <span id="sendBtnText"><i class="bi bi-send me-2"></i>Send Message</span>
                                <span id="sendBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right: Split card -->
            <div class="panel-card">
                <div class="panel-head">
                    <h5 class="w-100 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-chat-dots"></i> Contacts & Thread</span>
                        <span class="opacity-75 d-flex align-items-center gap-2">
                            <i class="bi bi-people" title="Recent contacts"></i>
                            <i class="bi bi-chat-square-text" title="Thread"></i>
                        </span>
                    </h5>
                </div>

                <div class="split-card">
                    <!-- Recent Contacts -->
                    <div class="split-left">
                        <div class="panel-body p-0">
                            <div class="d-flex align-items-center justify-content-between px-3 pt-3">
                                <div class="fw-bold">Recent</div>
                                <div class="small muted" id="activeThreadLabel">No thread</div>
                            </div>

                            <div class="recent-contacts" id="recentContactsWrapper">
                                <div id="recentContactsContent">
                                    {{-- IMPORTANT:
                                         Update your partial to render each contact row with:
                                         class="rc-item" data-phone="07xx..."
                                         and make the "Show thread" button call: viewSmsThread('07xx...')
                                         You can also call fillPhone('07xx...') if you want.
                                    --}}
                                    @include('chats.partials.recent-contacts')
                                </div>

                                <div id="recentContactsLoader" class="text-center d-none mt-2 pb-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="small muted mt-1">Loading contacts…</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thread -->
                    <div class="split-right">
                        <div class="panel-body">
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" id="threadPhoneInput" class="form-control form-control-sm" placeholder="Type number…">
                                <button class="btn btn-primary btn-sm" onclick="loadThreadFromInput()">
                                    <i class="bi bi-arrow-right-circle"></i>
                                </button>
                            </div>

                            <div id="threadBox" class="thread-shell">
                                <div class="thread-empty">
                                    <i class="bi bi-chat-square-text"></i>
                                    <div class="fw-bold">No thread loaded</div>
                                    <div class="small">Click “Show thread” on a contact.</div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm w-50" onclick="syncPhoneFromThread()">
                                    <i class="bi bi-arrow-left-right me-1"></i>Use this number
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm w-50" onclick="clearThread()">
                                    <i class="bi bi-x-circle me-1"></i>Clear
                                </button>
                            </div>

                            <div class="small muted mt-2">
                                Thread loads into this panel; the active contact highlights on the left.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BELOW: Table takes 100% width -->
        <div class="panel-card table-card" id="messagesSection">
            <div class="panel-head">
                <h5><i class="bi bi-table"></i> All Sent Messages</h5>
            </div>

            <div class="panel-body">
                <div class="table-tools">
                    <div class="btn-group filter-btn-group" role="group" aria-label="Filter messages">
                        <button type="button" class="btn btn-outline-primary active" data-filter="all">
                            <i class="bi bi-list-ul me-1"></i>All
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-filter="sent">
                            <i class="bi bi-check-circle me-1"></i>Sent
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-filter="failed">
                            <i class="bi bi-x-circle me-1"></i>Failed
                        </button>
                    </div>

                    <input type="text" id="searchInput" class="form-control search" placeholder="🔍 Search messages…">
                </div>

                <div id="messagesLoader" class="d-none">
                    <div class="skel-block">
                        <div class="skeleton skel-row w85"></div>
                        <div class="skeleton skel-row w70"></div>
                        <div class="skeleton skel-row w55"></div>
                        <div class="skeleton skel-row w85"></div>
                        <div class="skeleton skel-row w70"></div>
                    </div>
                </div>

                <div id="messagesTableWrapper">
                    @include('chats.partials.messages-table')
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal for Full Message -->
<div class="modal fade" id="fullMessageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header panel-head">
                <h5 class="modal-title" id="messageModalLabel">Full Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessageText" class="text-dark mb-0"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="resendToSame">
                    <i class="bi bi-arrow-repeat me-1"></i>Resend to Same
                </button>
                <button class="btn btn-outline-primary" id="resendNew">
                    <i class="bi bi-pencil me-1"></i>Send to Different
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/* =========================
   Counters + basic helpers
   ========================= */
const msgInput = document.getElementById('message');
const phoneInput = document.getElementById('to');
const charCounter = document.getElementById('charCount');

msgInput?.addEventListener('input', function () {
    if (charCounter) charCounter.textContent = `${this.value.length} characters`;
});

function normalizePhone(p){ return (p || '').toString().trim(); }

/* =========================
   Scroll to table
   ========================= */
function scrollToMessages(filter = 'all') {
    document.getElementById('messagesSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    setTimeout(() => {
        if (filter !== 'all') setActiveFilter(filter);
        applyFilters();
    }, 450);
}

/* =========================
   Thread + Active highlight
   - Highlights .rc-item[data-phone="..."]
   - Also updates label on right header
   ========================= */
let activeThreadPhone = '';
let threadAbort = null;

function setActiveContact(phone){
    const p = normalizePhone(phone);
    activeThreadPhone = p;

    // label
    const lab = document.getElementById('activeThreadLabel');
    if (lab) lab.textContent = p ? `Thread: ${p}` : 'No thread';

    // highlight
    document.querySelectorAll('#recentContactsContent .rc-item').forEach(el => {
        const ph = normalizePhone(el.getAttribute('data-phone'));
        el.classList.toggle('active', p && ph && ph === p);
    });
}

function threadLoadingUI(){
    return `
        <div class="py-2">
            <div class="skel-block">
                <div class="skeleton skel-row w55"></div>
                <div class="skeleton skel-row w85"></div>
                <div class="skeleton skel-row w70"></div>
                <div class="skeleton skel-row w85"></div>
                <div class="skeleton skel-row w55"></div>
            </div>
            <div class="text-center small muted mt-2">Loading thread…</div>
        </div>
    `;
}

function threadErrorUI(){
    return `
        <div class="text-center py-4">
            <i class="bi bi-exclamation-triangle text-danger fs-2"></i>
            <div class="fw-bold mt-2">Failed to load thread</div>
            <div class="small muted">Try again.</div>
        </div>
    `;
}

function viewSmsThread(phone) {
    const threadBox = document.getElementById('threadBox');
    const threadPhoneInput = document.getElementById('threadPhoneInput');
    const p = normalizePhone(phone);

    if (!p) return;

    if (threadPhoneInput) threadPhoneInput.value = p;
    setActiveContact(p);

    // Cancel previous request if any
    if (threadAbort) threadAbort.abort();
    threadAbort = new AbortController();

    if (threadBox) threadBox.innerHTML = threadLoadingUI();

    fetch(`/chats/thread/${encodeURIComponent(p)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        signal: threadAbort.signal
    })
    .then(res => {
        if (!res.ok) throw new Error('Bad response');
        return res.text();
    })
    .then(html => { if (threadBox) threadBox.innerHTML = html; })
    .catch(err => {
        if (err?.name === 'AbortError') return;
        if (threadBox) threadBox.innerHTML = threadErrorUI();
    });
}

function loadThreadFromInput() {
    const phone = document.getElementById('threadPhoneInput')?.value || '';
    const p = normalizePhone(phone);
    if (!p) return alert('Please enter a phone number');
    viewSmsThread(p);
}

function fillPhone(phone) {
    const p = normalizePhone(phone);
    if (!p || !phoneInput) return;
    phoneInput.value = p;
    phoneInput.focus();
}

/* quick actions under thread */
function syncPhoneFromThread(){
    if (!activeThreadPhone) return;
    fillPhone(activeThreadPhone);
}
function clearThread(){
    activeThreadPhone = '';
    setActiveContact('');
    const threadBox = document.getElementById('threadBox');
    if (threadBox) threadBox.innerHTML = `
        <div class="thread-empty">
            <i class="bi bi-chat-square-text"></i>
            <div class="fw-bold">No thread loaded</div>
            <div class="small">Click “Show thread” on a contact.</div>
        </div>`;
    const threadPhoneInput = document.getElementById('threadPhoneInput');
    if (threadPhoneInput) threadPhoneInput.value = '';
}

/* =========================
   Delegated click: if your partial has "Show thread" buttons/links
   you can make them:
     <button class="btn btn-sm btn-primary js-show-thread" data-phone="07xx">Show thread</button>
   and we’ll catch it here even after pagination.
   ========================= */
document.addEventListener('click', function(e){
    const btn = e.target.closest('.js-show-thread');
    if (!btn) return;
    const phone = btn.getAttribute('data-phone');
    if (phone) viewSmsThread(phone);
});

/* =========================
   Full message modal + resend
   ========================= */
function resendMessage(message, phone) {
    if (phoneInput) phoneInput.value = phone ? normalizePhone(phone) : '';
    if (msgInput) msgInput.value = message || '';
    if (charCounter) charCounter.textContent = `${(message || '').length} characters`;
    phoneInput?.focus();
}

let modalMessage = '';
let modalPhone = '';

function showFullMessage(message, phone) {
    modalMessage = message || '';
    modalPhone = normalizePhone(phone || '');
    const el = document.getElementById('modalMessageText');
    if (el) el.textContent = modalMessage;
    const m = document.getElementById('fullMessageModal');
    if (m) new bootstrap.Modal(m).show();
}

document.getElementById('resendToSame')?.addEventListener('click', () => {
    resendMessage(modalMessage, modalPhone);
    bootstrap.Modal.getInstance(document.getElementById('fullMessageModal'))?.hide();
});

document.getElementById('resendNew')?.addEventListener('click', () => {
    resendMessage(modalMessage, '');
    bootstrap.Modal.getInstance(document.getElementById('fullMessageModal'))?.hide();
});

/* =========================
   Filters + search (table)
   ========================= */
let currentFilter = 'all';
let currentSearch = '';

function setActiveFilter(type){
    currentFilter = type || 'all';
    document.querySelectorAll('.filter-btn-group .btn').forEach(b => {
        b.classList.toggle('active', (b.getAttribute('data-filter') === currentFilter));
    });
}
function rowStatusText(row){
    const badge = row.querySelector('.badge');
    return (badge?.textContent || '').toLowerCase();
}
function matchesFilter(row){
    if (currentFilter === 'all') return true;
    const st = rowStatusText(row);
    if (currentFilter === 'sent') return st.includes('sent') || st.includes('delivered') || st.includes('success');
    if (currentFilter === 'failed') return st.includes('failed') || st.includes('pending') || st.includes('error');
    return true;
}
function matchesSearch(row){
    if (!currentSearch) return true;
    return (row.textContent || '').toLowerCase().includes(currentSearch);
}
function applyFilters(){
    const rows = document.querySelectorAll("#messagesTable tbody tr");
    rows.forEach(row => row.style.display = (matchesFilter(row) && matchesSearch(row)) ? '' : 'none');
}

document.addEventListener('click', function(e){
    const b = e.target.closest('.filter-btn-group .btn');
    if (!b) return;
    setActiveFilter(b.getAttribute('data-filter'));
    applyFilters();
});

document.getElementById('searchInput')?.addEventListener('input', function () {
    currentSearch = (this.value || '').toLowerCase().trim();
    applyFilters();
});

/* =========================
   Send form loader
   ========================= */
document.getElementById('smsForm')?.addEventListener('submit', function () {
    document.getElementById('sendBtnText')?.classList.add('d-none');
    document.getElementById('sendBtnSpinner')?.classList.remove('d-none');
    const btn = document.getElementById('sendBtn');
    if (btn) btn.disabled = true;
});

/* Delete button loader support */
document.addEventListener('submit', function(e){
    const form = e.target.closest('.delete-form');
    if (!form) return;
    const btn = form.querySelector('.delete-btn');
    btn?.querySelector('.delete-text')?.classList.add('d-none');
    btn?.querySelector('.spinner-border')?.classList.remove('d-none');
}, true);

/* =========================
   AJAX pagination:
   - Contacts expects JSON { html: "..." }
   - Messages expects HTML
   After contacts reload: re-apply active highlight ✅
   ========================= */
function loadRecentContacts(url) {
    const wrapper = document.getElementById('recentContactsContent');
    const loader = document.getElementById('recentContactsLoader');

    wrapper?.classList.add('opacity-50');
    loader?.classList.remove('d-none');

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r => r.json())
        .then(data => {
            if (wrapper) wrapper.innerHTML = data.html;
            // re-highlight active thread contact after DOM swap
            if (activeThreadPhone) setActiveContact(activeThreadPhone);
        })
        .catch(() => { if (wrapper) wrapper.innerHTML = '<p class="text-danger small m-0 p-2">Failed to load contacts.</p>'; })
        .finally(() => {
            wrapper?.classList.remove('opacity-50');
            loader?.classList.add('d-none');
        });
}

function loadMessagesPage(url){
    const loader = document.getElementById('messagesLoader');
    const wrapper = document.getElementById('messagesTableWrapper');

    loader?.classList.remove('d-none');
    if (wrapper) wrapper.style.opacity = 0.35;

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r => r.text())
        .then(html => {
            if (wrapper) wrapper.innerHTML = html;
            if (wrapper) wrapper.style.opacity = 1;
            loader?.classList.add('d-none');

            // re-apply filter/search after reload
            setActiveFilter(currentFilter);
            applyFilters();
        })
        .catch(() => {
            loader?.classList.add('d-none');
            if (wrapper) wrapper.style.opacity = 1;
        });
}

document.addEventListener("click", function (e) {
    const cLink = e.target.closest('#recentContactsWrapper .pagination a');
    if (cLink) {
        e.preventDefault();
        const url = cLink.getAttribute('href');
        if (url) loadRecentContacts(url);
        return;
    }

    const mLink = e.target.closest('#messagesTableWrapper .pagination a');
    if (mLink) {
        e.preventDefault();
        const url = mLink.getAttribute('href');
        if (url) loadMessagesPage(url);
        return;
    }
});

/* Init */
document.addEventListener("DOMContentLoaded", function(){
    setActiveFilter('all');
    applyFilters();
});
</script>

@if (session('status'))
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
    <div id="liveToast" class="toast align-items-center text-white bg-success border-0 show fade" role="alert">
        <div class="d-flex">
            <div class="toast-body">{{ session('status') }}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function(){
    const toastLive = document.getElementById('liveToast');
    if (toastLive) (new bootstrap.Toast(toastLive)).show();
});
</script>
@endif

@endsection
