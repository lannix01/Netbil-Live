@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Sora:wght@600;700&display=swap');
    body { background:#f5f7fa; font-family:"Manrope",sans-serif; }

    .stat-grid{ row-gap:20px; }
    .stat-card{
        background:#fff; border-radius:14px; height:120px; padding:18px;
        display:flex; align-items:center; justify-content:center;
        box-shadow:0 2px 6px rgba(0,0,0,0.06);
        border:1px solid #e8e8e8;
        transition:all .2s ease;
    }
    .stat-card:hover{ transform:translateY(-4px); box-shadow:0 6px 16px rgba(0,0,0,0.08); }
    .stat-inner{ width:100%; display:flex; justify-content:space-between; align-items:center; }
    .stat-number{ font-size:1.8rem; font-weight:700; margin:0; color:#111827; }
    .stat-label{ font-size:.85rem; color:#6b7280; margin:0; }
    .stat-icon{ font-size:2.2rem; color:#4b5563; opacity:.75; transition:.2s; }
    .stat-card:hover .stat-icon{ opacity:1; transform:scale(1.08); }

    .card-active{ border-left:5px solid #2563eb; }
    .card-rate{ border-left:5px solid #16a34a; }
    .card-warning{ border-left:5px solid #f59e0b; }
    .card-unique{ border-left:5px solid #7c3aed; }

    .module-box, .card{
        background:#fff; border-radius:14px;
        border:1px solid #e6e6e6;
        box-shadow:0 2px 6px rgba(0,0,0,0.04);
    }

    .dashboard-title{
        font-family:"Sora","Manrope",sans-serif;
        letter-spacing:.2px;
    }

    .finance-card{
        border-radius:16px;
        border:1px solid #e2e8f0;
        box-shadow:0 10px 24px rgba(15,23,42,.08);
        background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);
        transition:transform .2s ease, box-shadow .2s ease;
        height:158px;
        min-height:158px;
        overflow:hidden;
        display:flex;
        flex-direction:column;
        justify-content:flex-start;
        width:100%;
    }
    .finance-card:hover{
        transform:translateY(-3px);
        box-shadow:0 14px 26px rgba(15,23,42,.12);
    }
    .finance-card .kpi-title{
        font-family:"Sora","Manrope",sans-serif;
        margin:0;
        font-size:.94rem;
        color:#475569;
        font-weight:700;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .finance-card .kpi-value{
        margin:0;
        font-family:"Sora","Manrope",sans-serif;
        font-size:1.9rem;
        font-weight:700;
        color:#0f172a;
        line-height:1.2;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .finance-card .kpi-meta{
        margin-top:2px;
        font-size:.78rem;
        color:#64748b;
        font-weight:600;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .finance-card .kpi-stack{
        width:100%;
        min-width:0;
        display:flex;
        flex-direction:column;
        gap:6px;
    }
    .finance-card .kpi-head{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:10px;
        width:100%;
    }
    .finance-kpi-row > [class*="col-"]{
        display:flex;
    }
    .finance-card .kpi-icon{
        flex:0 0 auto;
    }
    .finance-card.today{
        border-left:5px solid #16a34a;
    }
    .finance-card.month{
        border-left:5px solid #2563eb;
    }
    .finance-card.sms{
        border-left:5px solid #f59e0b;
    }
    .sms-card-title{
        font-family:"Sora","Manrope",sans-serif;
        margin:0;
        font-size:1.06rem;
        color:#1e293b;
        font-weight:700;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .sms-gateway-grid{
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:10px;
        margin-top:2px;
    }
    .sms-gateway-card{
        border:1px solid #e2e8f0;
        border-radius:12px;
        background:#ffffff;
        padding:8px 10px;
        min-width:0;
        box-shadow:inset 0 1px 0 rgba(255,255,255,.65);
        display:flex;
        flex-direction:column;
        gap:4px;
    }
    .sms-gateway-name{
        font-size:.68rem;
        font-weight:800;
        letter-spacing:.06em;
        text-transform:uppercase;
        color:#64748b;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .sms-gateway-amount{
        font-family:"Sora","Manrope",sans-serif;
        font-size:1rem;
        font-weight:700;
        color:#111827;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .finance-card #smsBalancesMeta{
        margin-top:2px;
        font-size:.72rem;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    @media (max-width:575.98px){
        .sms-gateway-grid{
            grid-template-columns:1fr;
        }
    }

    .card-header{
        background:#f3f4f6; font-weight:600; font-size:1rem;
        border-bottom:1px solid #e5e7eb;
    }

    .table th{ background:#eef1f5; color:#374151; font-weight:600; }

    /* ====== FAST DASH UX ADDITIONS ====== */
    .soft-skeleton{
        position:relative;
        overflow:hidden;
        background:linear-gradient(90deg,#f2f4f8 0%,#e9edf5 40%,#f2f4f8 80%);
        background-size:200% 100%;
        animation: shimmer 1.2s linear infinite;
        border-radius:10px;
        min-height:16px;
    }
    @keyframes shimmer{ 0%{background-position:200% 0} 100%{background-position:-200% 0} }

    .live-pill{
        display:inline-flex; align-items:center; gap:8px;
        font-size:12px; font-weight:700;
        padding:6px 10px;
        border-radius:999px;
        border:1px solid #e5e7eb;
        background:#fff;
        color:#111827;
        user-select:none;
        white-space:nowrap;
    }
    .dot{
        width:9px; height:9px; border-radius:999px;
        background:#9ca3af;
        box-shadow:0 0 0 4px rgba(156,163,175,.12);
    }
    .dot.ok{
        background:#22c55e;
        box-shadow:0 0 0 4px rgba(34,197,94,.14);
        animation:pulse 1.2s ease-in-out infinite;
    }
    .dot.warn{
        background:#f59e0b;
        box-shadow:0 0 0 4px rgba(245,158,11,.16);
        animation:pulse 1.2s ease-in-out infinite;
    }
    .dot.bad{
        background:#ef4444;
        box-shadow:0 0 0 4px rgba(239,68,68,.14);
    }
    @keyframes pulse{ 0%,100%{transform:scale(1)} 50%{transform:scale(1.12)} }

    .live-row{
        display:flex; align-items:center; justify-content:space-between; gap:12px;
        flex-wrap:wrap;
        margin-bottom:14px;
    }
    .mini-muted{ color:#6b7280; font-size:12px; }

    .btn-ghost{
        border:1px solid #e5e7eb;
        background:#fff;
        color:#111827;
        border-radius:10px;
        padding:8px 10px;
        font-weight:700;
        font-size:13px;
    }
    .btn-ghost:hover{ background:#f9fafb; }

    /* overlay for “refreshing section” so page still feels instant */
    .section-wrap{ position:relative; }
    .section-busy{
        position:absolute; inset:0;
        background: rgba(255,255,255,.65);
        backdrop-filter: blur(2px);
        display:none;
        align-items:center; justify-content:center;
        border-radius:14px;
        z-index:5;
    }
    .section-busy.show{ display:flex; }
    .spinner{
        width:18px;height:18px;border-radius:999px;
        border:2px solid rgba(37,99,235,.20);
        border-top-color: rgba(37,99,235,.95);
        animation: spin .8s linear infinite;
        margin-right:10px;
    }
    @keyframes spin{ to{ transform:rotate(360deg) } }

    /* anchor ids used by refreshSection() */
    #system-status, #interface-traffic{ min-height: 120px; }
</style>

<div class="container-fluid px-4 py-4">

    <div class="live-row">
        <h2 class="mb-0 dashboard-title" style="font-weight:700; color:#111827;">Dashboard Overview</h2>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="live-pill" title="Shows whether live data streams are connected">
                <span id="liveDot" class="dot warn"></span>
                <span id="liveText">Connecting…</span>
            </span>

            <button class="btn-ghost" id="pauseLiveBtn" type="button" title="Pause live updates to keep the page snappy">
                ⏸ Pause live
            </button>

            <button class="btn-ghost" id="resumeLiveBtn" type="button" style="display:none" title="Resume live updates">
                ▶ Resume live
            </button>

            <span class="mini-muted" id="lastUpdated">Last update: —</span>
        </div>
    </div>

    <!-- ===================== STAT CARDS ===================== -->
    <div class="row stat-grid">

        <div class="col-6 col-md-3">
            <div class="stat-card card-active">
                <div class="stat-inner">
                    <div>
                        <h3 id="active-users" class="stat-number">{{ $activeUsers ?? 0 }}</h3>
                        <p class="stat-label">Active Users</p>
                    </div>
                    <i class="bi bi-people-fill stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card card-rate">
                <div class="stat-inner">
                    <div>
                        <h3 id="subscription-rate" class="stat-number">{{ $subscriptionRate ?? 0 }}%</h3>
                        <p class="stat-label">Subs Rate</p>
                    </div>
                    <i class="bi bi-graph-up stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card card-warning">
                <div class="stat-inner">
                    <div>
                        <h3 id="total-users" class="stat-number">{{ $totalUsers ?? 0 }}</h3>
                        <p class="stat-label">Total Users</p>
                    </div>
                    <i class="bi bi-person-plus-fill stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card card-unique">
                <div class="stat-inner">
                    <div>
                        <h3 id="unique-visitors" class="stat-number">{{ $uniqueHosts ?? 0 }}</h3>
                        <p class="stat-label">Unique Visitors</p>
                    </div>
                    <i class="bi bi-eye-fill stat-icon"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- ===================== SMS & REVENUE ===================== -->
    <div class="row mt-4 g-3 align-items-stretch finance-kpi-row">
        <div class="col-md-4">
            <a href="{{ route('revenue.index') }}" class="card finance-card today p-3 h-100 text-dark text-decoration-none">
                <div class="kpi-stack">
                    <div class="kpi-head">
                        <p class="kpi-title">Amount Today</p>
                        <i class="bi bi-cash-coin fs-3 text-success kpi-icon"></i>
                    </div>
                    <p class="kpi-value" id="amountTodayValue">KES 0.00</p>
                    <div class="kpi-meta" id="amountTodayMeta">Loading today totals…</div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ route('revenue.index') }}" class="card finance-card month p-3 h-100 text-dark text-decoration-none">
                <div class="kpi-stack">
                    <div class="kpi-head">
                        <p class="kpi-title">Amount this Month</p>
                        <i class="bi bi-wallet2 fs-3 text-primary kpi-icon"></i>
                    </div>
                    <p id="amountThisMonthValue" class="kpi-value">KES 0.00</p>
                    <div class="kpi-meta" id="amountThisMonthMeta">Loading MegaPay totals…</div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <div class="card finance-card sms p-3 h-100">
                <div class="kpi-stack">
                    <div class="kpi-head">
                        <p class="sms-card-title">SMS Balances</p>
                        <i class="bi bi-chat-dots-fill fs-3 text-warning kpi-icon"></i>
                    </div>
                    <div class="sms-gateway-grid">
                        <div class="sms-gateway-card">
                            <span class="sms-gateway-name">Amazons</span>
                            <span class="sms-gateway-amount">
                                <span id="amazonsSmsBalanceText" class="soft-skeleton" style="display:inline-block; width:70px;">&nbsp;</span>
                            </span>
                        </div>
                        <div class="sms-gateway-card">
                            <span class="sms-gateway-name">Advanta</span>
                            <span class="sms-gateway-amount" id="advantaSmsBalanceText">
                                {{ isset($advantaBalance) && $advantaBalance !== null ? number_format((float)$advantaBalance, 0) : 'Unavailable' }}
                            </span>
                        </div>
                    </div>
                    <div class="mini-muted" id="smsBalancesMeta">Waiting for live update…</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== SYSTEM INFO + INTERFACES ===================== -->
    <div class="row mt-4">

        <!-- SYSTEM INFO -->
        <div class="col-md-6">
            <div class="section-wrap">
                <div class="section-busy" id="busy-system">
                    <div class="d-flex align-items-center">
                        <div class="spinner"></div>
                        <div>
                            <div style="font-weight:800;">Refreshing system…</div>
                            <div class="mini-muted">Pulling latest Mikrotik metrics</div>
                        </div>
                    </div>
                </div>

                <div class="card h-100" id="system-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>System Status</span>
                        <button class="btn btn-sm btn-outline-primary refresh-section" data-section="system" type="button">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>

                    <div class="card-body" id="system-status">
                        <p id="ram-usage">
                            RAM: {{ $latestMetrics->ram_used ?? 0 }}GB ({{ $latestMetrics->ram_percent ?? 0 }}%)
                        </p>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-primary" style="width: {{ $latestMetrics->ram_percent ?? 0 }}%"></div>
                        </div>

                        <p id="cpu-usage">CPU Load: {{ $latestMetrics->cpu_percent ?? 0 }}%</p>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-info" style="width: {{ $latestMetrics->cpu_percent ?? 0 }}%"></div>
                        </div>

                        <p id="hdd-usage">
                            HDD: {{ $latestMetrics->hdd_used ?? 0 }}GB ({{ $latestMetrics->hdd_percent ?? 0 }}%)
                        </p>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-warning" style="width: {{ $latestMetrics->hdd_percent ?? 0 }}%"></div>
                        </div>

                        <p id="uptime">Uptime: {{ $latestMetrics->uptime ?? '0s' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- INTERFACE TRAFFIC -->
        <div class="col-md-6">
            <div class="section-wrap">
                <div class="section-busy" id="busy-interface">
                    <div class="d-flex align-items-center">
                        <div class="spinner"></div>
                        <div>
                            <div style="font-weight:800;">Refreshing interfaces…</div>
                            <div class="mini-muted">Updating traffic table</div>
                        </div>
                    </div>
                </div>

                <div class="card h-100" id="interface-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Interface Traffic</span>
                        <button class="btn btn-sm btn-outline-primary refresh-section" data-section="interface" type="button">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>

                    <div class="card-body p-0" id="interface-traffic">
                        @if(!empty($traffic))
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Interface</th>
                                            <th>RX</th>
                                            <th>TX</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($traffic as $iface)
                                            <tr>
                                                <td>{{ $iface['interface_name'] ?? '—' }}</td>
                                                <td>{{ number_format(($iface['rx_bps'] ?? 0) / 1_000_000, 2) }} Mbps</td>
                                                <td>{{ number_format(($iface['tx_bps'] ?? 0) / 1_000_000, 2) }} Mbps</td>
                                                <td>
                                                    <span class="badge {{ ($iface['status'] ?? 'down') === 'up' ? 'bg-success' : 'bg-danger' }}">
                                                        {{ ucfirst($iface['status'] ?? 'down') }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>

                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center p-3 mb-0">No interface traffic found.</p>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- ====================================================== -->
<!-- MAKE DASH FEEL FAST: NON-BLOCKING LIVE UPDATES -->
<!-- ====================================================== -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/socket.io-client@4.5.0/dist/socket.io.min.js"></script>

<script>


const LIVE = {
  paused: false,

  // SMS socket cadence
  sms: {
    intervalMs: 8000,     // was 1500ms (too aggressive)
    timer: null,
    lastAt: 0
  },

  // Interface refresh via HTTP
  iface: {
    baseMs: 6000,         // was 2000ms (too aggressive)
    maxMs: 20000,
    backoffMs: 6000,
    timer: null,
    inFlight: false,
    lastOkAt: 0
  },

  // System refresh on demand (or very slow)
  system: {
    inFlight: false
  }
};

const $liveDot = $('#liveDot');
const $liveText = $('#liveText');
const $lastUpdated = $('#lastUpdated');
const $amazonsSmsText = $('#amazonsSmsBalanceText');
const $smsMeta = $('#smsBalancesMeta');
const $amountTodayValue = $('#amountTodayValue');
const $amountTodayMeta = $('#amountTodayMeta');
const $amountThisMonthValue = $('#amountThisMonthValue');
const $amountThisMonthMeta = $('#amountThisMonthMeta');

function nowStamp(){
  const d = new Date();
  return d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'});
}

function setLiveState(kind){
  // kind: connecting, ok, warn, bad
  $liveDot.removeClass('ok warn bad');
  if (kind === 'ok') $liveDot.addClass('ok');
  if (kind === 'warn') $liveDot.addClass('warn');
  if (kind === 'bad') $liveDot.addClass('bad');

  const label = ({
    connecting: 'Connecting…',
    ok: 'Live',
    warn: 'Live (slow)',
    bad: 'Offline'
  })[kind] || '—';

  $liveText.text(label);
}

setLiveState('connecting');

function renderKesAmount($el, amount){
  const n = Number(amount || 0);
  const safe = Number.isFinite(n) ? n : 0;
  const formatted = safe.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
  $el.text(`KES ${formatted}`);
}

function loadAmountThisMonth(){
  $.ajax({
    url: '/dashboard/amount-this-month',
    method: 'GET',
    cache: false,
    timeout: 9000
  })
  .done((res) => {
    renderKesAmount($amountTodayValue, res?.today_amount ?? 0);
    renderKesAmount($amountThisMonthValue, res?.month_amount ?? res?.amount ?? 0);

    const todayTxCount = Number(res?.today_transactions ?? 0);
    const safeTodayTxCount = Number.isFinite(todayTxCount) ? todayTxCount : 0;
    const todayLabel = safeTodayTxCount === 1 ? 'payment' : 'payments';
    $amountTodayMeta.text(`Today: ${safeTodayTxCount} completed ${todayLabel}`);

    const txCount = Number(res?.month_transactions ?? res?.transactions ?? 0);
    const safeTxCount = Number.isFinite(txCount) ? txCount : 0;
    const month = (res?.month || 'This month').toString();
    const label = safeTxCount === 1 ? 'payment' : 'payments';
    $amountThisMonthMeta.text(`${month}: ${safeTxCount} completed ${label}`);
  })
  .fail(() => {
    $amountTodayMeta.text('Could not load today total');
    $amountThisMonthMeta.text('Could not load monthly total');
  });
}

// ===== socket setup (make it resilient) =====
const socket = io("https://net1.skybrix.co.ke:9585", {
  transports: ['websocket'],
  reconnection: true,
  reconnectionAttempts: Infinity,
  reconnectionDelay: 800,
  reconnectionDelayMax: 6000,
  timeout: 8000
});

socket.on('connect', () => {
  setLiveState('ok');
  $('#smsBalancesMeta').text('Connected. Waiting for update…');
  startSmsLoop();
});

socket.on('disconnect', () => {
  setLiveState('bad');
  $('#smsBalancesMeta').text('Disconnected. Will retry…');
  stopSmsLoop();
});

socket.on('connect_error', () => {
  setLiveState('warn');
  $('#smsBalancesMeta').text('Connection slow. Retrying…');
});

// ===== SMS: don’t spam =====
function startSmsLoop(){
  stopSmsLoop();
  LIVE.sms.timer = setInterval(() => {
    if (LIVE.paused) return;
    // emit request
    socket.emit("dashboard.get.sms.balance", JSON.stringify({}));
    LIVE.sms.lastAt = Date.now();
  }, LIVE.sms.intervalMs);

  // immediately ask once
  socket.emit("dashboard.get.sms.balance", JSON.stringify({}));
}

function stopSmsLoop(){
  if (LIVE.sms.timer) clearInterval(LIVE.sms.timer);
  LIVE.sms.timer = null;
}

socket.on("dashboard.get.sms.balance", (data) => {
  try{
    const parsed = JSON.parse(data);
    const balRaw = parsed?.smsBalance ?? 0;
    const bal = parseFloat(balRaw || 0);
    const nice = Number.isFinite(bal) ? bal.toFixed(0) : '0';

    // remove skeleton once we have first real number
    $amazonsSmsText.removeClass('soft-skeleton').css({width:'auto'}).text(nice);
    $smsMeta.text(`Updated ${nowStamp()}`);
    $lastUpdated.text(`Last update: ${nowStamp()}`);
    setLiveState('ok');
  }catch(e){
    setLiveState('warn');
  }
});

// ===== refresh overlays =====
function busy(section, on){
  const id = section === 'system' ? '#busy-system' : '#busy-interface';
  if (on) $(id).addClass('show');
  else $(id).removeClass('show');
}

// ===== safe refresh that never piles requests =====
function refreshSection(section, opts = {}){
  if (LIVE.paused) return;

  if (section === 'interface'){
    if (LIVE.iface.inFlight) return; // critical: prevent piling
    LIVE.iface.inFlight = true;
    busy('interface', true);
  }

  if (section === 'system'){
    if (LIVE.system.inFlight) return;
    LIVE.system.inFlight = true;
    busy('system', true);
  }

  const url = `/dashboard/refresh?section=${encodeURIComponent(section)}`;

  $.ajax({
    url,
    method: 'GET',
    cache: false,
    timeout: opts.timeoutMs ?? 9000
  })
  .done((data) => {
    // expected: { html: "..."}
    if (section === 'system') $('#system-status').html(data.html);
    if (section === 'interface') $('#interface-traffic').html(data.html);

    $lastUpdated.text(`Last update: ${nowStamp()}`);

    if (section === 'interface'){
      LIVE.iface.lastOkAt = Date.now();
      // reset backoff to base
      LIVE.iface.backoffMs = LIVE.iface.baseMs;
      setLiveState('ok');
    }
  })
  .fail(() => {
    // Backoff on failure or slowness
    if (section === 'interface'){
      LIVE.iface.backoffMs = Math.min(LIVE.iface.backoffMs * 2, LIVE.iface.maxMs);
      setLiveState('warn');
    }
  })
  .always(() => {
    if (section === 'interface'){
      LIVE.iface.inFlight = false;
      busy('interface', false);
    }
    if (section === 'system'){
      LIVE.system.inFlight = false;
      busy('system', false);
    }
  });
}

// ===== manual refresh buttons =====
$('.refresh-section').on('click', function () {
  const section = $(this).data('section');
  refreshSection(section, { timeoutMs: 12000 });
});

// ===== auto-refresh interface with adaptive backoff =====
function startInterfaceLoop(){
  stopInterfaceLoop();
  const tick = () => {
    if (LIVE.paused) return;

    refreshSection('interface');

    // schedule next tick using current backoff
    LIVE.iface.timer = setTimeout(tick, LIVE.iface.backoffMs);
  };

  // start after a short delay so initial render is instant
  LIVE.iface.timer = setTimeout(tick, 1200);
}

function stopInterfaceLoop(){
  if (LIVE.iface.timer) clearTimeout(LIVE.iface.timer);
  LIVE.iface.timer = null;
}

startInterfaceLoop();
loadAmountThisMonth();
setInterval(() => {
  if (!LIVE.paused) loadAmountThisMonth();
}, 15000);

// ===== pause/resume live updates =====
$('#pauseLiveBtn').on('click', () => {
  LIVE.paused = true;
  $('#pauseLiveBtn').hide();
  $('#resumeLiveBtn').show();
  setLiveState('warn');
  $('#smsBalancesMeta').text('Live updates paused.');
  stopInterfaceLoop();
});

$('#resumeLiveBtn').on('click', () => {
  LIVE.paused = false;
  $('#resumeLiveBtn').hide();
  $('#pauseLiveBtn').show();
  setLiveState(socket.connected ? 'ok' : 'warn');
  $('#smsBalancesMeta').text('Resuming live updates…');
  startInterfaceLoop();
  if (socket.connected) startSmsLoop();
});

// ===== visibility: don’t keep hammering server when tab is hidden =====
document.addEventListener('visibilitychange', () => {
  if (document.hidden){
    // pause loops quietly
    stopInterfaceLoop();
    stopSmsLoop();
  } else {
    if (!LIVE.paused){
      startInterfaceLoop();
      if (socket.connected) startSmsLoop();
    }
  }
});
</script>

@endsection
