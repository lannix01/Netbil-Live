@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/socket.io-client@4.5.0/dist/socket.io.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    .card .icon i {
        transition: transform 0.3s ease;
        font-size: 32px;
    }
    .card:hover .icon i { transform: scale(1.4); }
    .card-cream { background: rgba(190, 190, 190, 0.85); border-radius: 12px; }
    .card-unique { background: rgba(223, 68, 68, 0.85); border-radius: 12px; }
    .card-active { background: rgba(59, 101, 240, 0.85); border-radius: 12px; }
    .card-rate { background: rgba(1, 97, 14, 0.84); border-radius: 12px; }
</style>

<div class="container-fluid mt-4">
    <h2>Dashboard</h2>

    <!-- Stat Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card card-active text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h3 id="active-users">0</h3><p>Active Users</p></div>
                    <div class="icon"><i class="bi bi-people-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-rate text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h3 id="subscription-rate">0%</h3><p>Subscription Rate</p></div>
                    <div class="icon"><i class="bi bi-graph-up"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card bg-warning text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h3 id="total-users">0</h3><p>Total Users</p></div>
                    <div class="icon"><i class="bi bi-person-plus-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-unique text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h3 id="unique-visitors">0</h3><p>Unique Visitors</p></div>
                    <div class="icon"><i class="bi bi-eye-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- SMS & Revenue -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card card-cream">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><h5>SMS Balance</h5><span id="smsBalanceText">Loading...</span></div>
                    <div class="icon"><i class="bi bi-envelope-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <a href="#" class="card card-cream text-dark text-decoration-none">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><h5>KES 0</h5><span>Amount this Month</span></div>
                    <div class="icon"><i class="bi bi-cash"></i></div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <div class="card card-cream">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><h5>FEATURE Z</h5><span>Coming soon</span></div>
                    <div class="icon"><i class="bi bi-box-seam"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- System + Interfaces -->
    <div class="row mt-4" id="system-status">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><h5>System Status</h5>
                <button class="btn btn-sm btn-outline-primary refresh-section" data-section="system"><i class="bi bi-arrow-clockwise"></i></button>
            </div>

                <div class="card-body">
                    <p id="ram-usage"></p>
                    <div class="progress mb-2"><div class="progress-bar ram bg-primary"></div></div>
                    <p id="cpu-usage"></p>
                    <div class="progress mb-2"><div class="progress-bar cpu bg-info"></div></div>
                    <p id="hdd-usage"></p>
                    <div class="progress mb-2"><div class="progress-bar hdd bg-warning"></div></div>
                    <p id="uptime"></p>
                </div>
            </div>
        </div>

        <div class="col-md-6" id="interface-traffic">
            <div class="card h-100">
                <div class="card-header"><h5>Interface Traffic</h5>
                <span><button class="btn btn-sm btn-outline-primary refresh-section" data-section="system"><i class="bi bi-arrow-clockwise"></i></button> </span>
            </div>
                <div class="card-body p-0">
                    @if(count($traffic))
                        <table class="table table-hover mb-0">
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
                                        <td>{{ $iface['interface_name'] }}</td>

                                        <td>{{ number_format($iface['rx_bps'] / 1_000_000, 2) }} Mbps</td>
                                        <td>{{ number_format($iface['tx_bps'] / 1_000_000, 2) }} Mbps</td>
                                        <td>
                                            <span class="badge {{ $iface['status'] === 'up' ? 'bg-success' : 'bg-danger' }}">
                                                {{ ucfirst($iface['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted p-3">No interfaces found or traffic data unavailable.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function animateNumber(el, target) {
    let current = 0;
    const step = Math.max(Math.ceil(target / 20), 1);
    const interval = setInterval(() => {
        current += step;
        if (current >= target) {
            el.textContent = target;
            clearInterval(interval);
        } else {
            el.textContent = current;
        }
    }, 20);
}

document.addEventListener('DOMContentLoaded', () => {
    animateNumber(document.getElementById('active-users'), {{ $activeUsers }});
    animateNumber(document.getElementById('total-users'), {{ $totalUsers }});
    animateNumber(document.getElementById('subscription-rate'), {{ $subscriptionRate }});
    animateNumber(document.getElementById('unique-visitors'), {{ $uniqueHosts ?? 0 }});

    @if($latestMetrics)
        document.getElementById('ram-usage').textContent = `RAM: {{ $latestMetrics->ram_used }}GB ({{ $latestMetrics->ram_percent }}%)`;
        document.querySelector('.progress-bar.ram').style.width = '{{ $latestMetrics->ram_percent }}%';

        document.getElementById('cpu-usage').textContent = `CPU Load: {{ $latestMetrics->cpu_percent }}%`;
        document.querySelector('.progress-bar.cpu').style.width = '{{ $latestMetrics->cpu_percent }}%';

        document.getElementById('hdd-usage').textContent = `HDD: {{ $latestMetrics->hdd_used }}GB ({{ $latestMetrics->hdd_percent }}%)`;
        document.querySelector('.progress-bar.hdd').style.width = '{{ $latestMetrics->hdd_percent }}%';

        document.getElementById('uptime').textContent = `Uptime: {{ $latestMetrics->uptime }}`;
    @else
        document.getElementById('ram-usage').textContent = 'RAM: 0GB (0%)';
        document.getElementById('cpu-usage').textContent = 'CPU Load: 0%';
        document.getElementById('hdd-usage').textContent = 'HDD: 0GB (0%)';
        document.getElementById('uptime').textContent = 'Uptime: 0s';
    @endif
});

// Socket for SMS Balance
const socket = io("https://net1.amazons.co.ke:9585", { transports: ['websocket'] });
setInterval(() => socket.emit("dashboard.get.sms.balance", JSON.stringify({})), 1000);
socket.on("dashboard.get.sms.balance", (data) => {
    let bal = parseFloat(JSON.parse(data).smsBalance || 0).toFixed(0);
    $('#smsBalanceText').text(bal);
});
</script>

<script>
// Global Refresh Button Handler
$('.refresh-section').on('click', function () {
    const section = $(this).data('section');
    refreshSection(section);
});

function refreshSection(section) {
    $.get(`/dashboard/refresh?section=${section}`, function (data) {
        if (section === 'system') {
            $('#system-status').html(data.html);
        } else if (section === 'interface') {
            $('#interface-traffic').html(data.html);
        } else if (section === 'stats') {
            $('#stat-cards').html(data.html);
        } else if (section === 'sms') {
            $('#sms-stats').html(data.html);
        }
    });
}

// Auto-refresh Interface Traffic every 2 seconds
setInterval(() => refreshSection('interface'), 2000);
</script>

@endsection
