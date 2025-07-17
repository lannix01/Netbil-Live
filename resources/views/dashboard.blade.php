@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://net1.amazons.co.ke/libs/jquery-3.7.1/dist/jquery.min.js"></script>
<script src="https://net1.amazons.co.ke:9585/socket.io/socket.io.js"></script>

<style>
    .card .icon i {
        transition: transform 0.3s ease;
        font-size: 32px;
    }
    .card:hover .icon i {
        transform: scale(1.4);
    }
    .card-cream { background: rgba(190, 190, 190, 0.85); border-radius: 12px; }
    .card-unique { background: rgba(223, 68, 68, 0.85); border-radius: 12px; }
    .card-active { background: rgba(59, 101, 240, 0.85); border-radius: 12px; }
    .card-total { background: rgba(243, 221, 20, 0.85); border-radius: 12px; }
    .card-rate  { background: rgba(1, 97, 14, 0.84); border-radius: 12px; }
</style>

<div class="container-fluid mt-4">
    <h2>Dashboard</h2>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card card-active text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 id="active-users">0</h3>
                        <p>Active Users</p>
                    </div>
                    <div class="icon"><i class="bi bi-people-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-rate text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 id="subscription-rate">0%</h3>
                        <p>Subscription Rate</p>
                    </div>
                    <div class="icon"><i class="bi bi-graph-up"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card bg-warning text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 id="total-users">0</h3>
                        <p>Total Users</p>
                    </div>
                    <div class="icon"><i class="bi bi-person-plus-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card card-unique text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 id="unique-visitors">0</h3>
                        <p>Unique Visitors</p>
                    </div>
                    <div class="icon"><i class="bi bi-eye-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card card-cream">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5>SMS Balance</h5>
                        <span id="smsBalanceText">Loading...</span>
                    </div>
                    <div class="icon"><i class="bi bi-envelope-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <a href="#" class="card card-cream text-dark text-decoration-none">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5>$AMOUNT</h5>
                        <span>Amount this Month</span>
                    </div>
                    <div class="icon"><i class="bi bi-cash"></i></div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <div class="card card-cream">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5>FEATURE Z</h5>
                        <span>Coming soon</span>
                    </div>
                    <div class="icon"><i class="bi bi-box-seam"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status + Traffic -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><h5>System Status</h5></div>
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
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><h5>Interface Traffic</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover" id="traffic-table">
                        <thead><tr><th>Interface</th><th>RX</th><th>TX</th><th>Status</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Uplink Graph -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="m-0">Uplink Transmission Graph</h5>
            <span id="uplink-name" class="badge bg-primary"></span>
        </div>
        <div class="card-body" id="uplink-graph-container" style="display: none;">
            <canvas id="uplinkChart" height="180"></canvas>
        </div>
        <div class="card-body text-muted" id="uplink-instructions" style="display: none;">
            <p>No uplink interface configured.</p>
        </div>
    </div>

    <!-- Graph Modal -->
    <div class="modal fade" id="trafficModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="trafficModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><canvas id="trafficChart" height="200"></canvas></div>
            </div>
        </div>
    </div>
</div>

<script>
const interfaceHistory = {};
let uplinkInterface = null;

function formatBits(bits) {
    bits = parseInt(bits);
    if (bits >= 1e9) return (bits / 1e9).toFixed(2) + ' Gbps';
    if (bits >= 1e6) return (bits / 1e6).toFixed(2) + ' Mbps';
    if (bits >= 1e3) return (bits / 1e3).toFixed(2) + ' Kbps';
    return bits + ' bps';
}

function updateDashboard() {
    $.getJSON('/api/dashboard-metrics', function (res) {
        if (res.error) return console.error(res.error);

        $('#ram-usage').text(`RAM Usage: ${res.system.ram_usage} (${res.system.ram_percentage}%)`);
        $('.progress-bar.ram').css('width', res.system.ram_percentage + '%');

        $('#cpu-usage').text(`CPU Usage: ${res.system.cpu_usage}%`);
        $('.progress-bar.cpu').css('width', res.system.cpu_usage + '%');

        $('#hdd-usage').text(`Storage Usage: ${res.system.storage_usage} (${res.system.hdd_percentage}%)`);
        $('.progress-bar.hdd').css('width', res.system.hdd_percentage + '%');

        $('#uptime').text(`Uptime: ${res.system.uptime}`);

        $('#active-users').text(res.active_users_count);
        $('#subscription-rate').text(Math.round((res.active_users_count / Math.max(res.total_users_count, 1)) * 100) + '%');
        $('#total-users').text(res.total_users_count);
        $('#unique-visitors').text(res.unique_visitors);

        const $table = $('#traffic-table tbody').empty();
        res.interface_traffic.forEach(iface => {
            const status = iface.status === 'up'
                ? '<span class="badge bg-success">Tx/Rx Up</span>'
                : '<span class="badge bg-danger">Tx/Rx Down</span>';
            const row = `<tr class="interface-row" data-name="${iface.name}" data-rx="${iface.rx}" data-tx="${iface.tx}">
                <td>${iface.name}</td><td>${formatBits(iface.rx)}</td><td>${formatBits(iface.tx)}</td><td>${status}</td>
            </tr>`;
            $table.append(row);
        });

        $('.interface-row').off('click').on('click', function () {
            const name = $(this).data('name');
            const rx = $(this).data('rx');
            const tx = $(this).data('tx');
            if (!interfaceHistory[name]) {
                interfaceHistory[name] = { timestamps: [], rx: [], tx: [] };
            }
            const now = new Date().toLocaleTimeString();
            interfaceHistory[name].timestamps.push(now);
            interfaceHistory[name].rx.push(rx);
            interfaceHistory[name].tx.push(tx);
            if (interfaceHistory[name].rx.length > 10) {
                interfaceHistory[name].timestamps.shift();
                interfaceHistory[name].rx.shift();
                interfaceHistory[name].tx.shift();
            }
            showTrafficModal(name, interfaceHistory[name]);
        });

        if (res.uplink && res.uplink.interface) {
            uplinkInterface = res.uplink.interface;
            $('#uplink-name').text(uplinkInterface);
            $('#uplink-graph-container').show();
            $('#uplink-instructions').hide();
            if (!interfaceHistory[uplinkInterface]) {
                interfaceHistory[uplinkInterface] = { timestamps: [], rx: [], tx: [] };
            }

            const now = new Date().toLocaleTimeString();
            interfaceHistory[uplinkInterface].timestamps.push(now);
            interfaceHistory[uplinkInterface].rx.push(res.uplink.rx);
            interfaceHistory[uplinkInterface].tx.push(res.uplink.tx);
            if (interfaceHistory[uplinkInterface].rx.length > 10) {
                interfaceHistory[uplinkInterface].timestamps.shift();
                interfaceHistory[uplinkInterface].rx.shift();
                interfaceHistory[uplinkInterface].tx.shift();
            }
            drawUplinkChart(interfaceHistory[uplinkInterface]);
        } else {
            $('#uplink-graph-container').hide();
            $('#uplink-instructions').show();
        }
    });
}

function drawUplinkChart(history) {
    const ctx = document.getElementById('uplinkChart').getContext('2d');
    if (window.uplinkChart) window.uplinkChart.destroy();
    window.uplinkChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: history.timestamps,
            datasets: [
                { label: 'RX', data: history.rx, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.2)' },
                { label: 'TX', data: history.tx, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,0.2)' }
            ]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
}

function showTrafficModal(name, history) {
    $('#trafficModalLabel').text(`${name} Traffic`);
    const ctx = document.getElementById('trafficChart').getContext('2d');
    if (window.trafficChart) window.trafficChart.destroy();
    window.trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: history.timestamps,
            datasets: [
                { label: 'Download (RX)', data: history.rx, borderColor: 'green', backgroundColor: 'rgba(0,255,0,0.1)' },
                { label: 'Upload (TX)', data: history.tx, borderColor: 'red', backgroundColor: 'rgba(255,0,0,0.1)' }
            ]
        }
    });
    $('#trafficModal').modal('show');
}

updateDashboard();
setInterval(updateDashboard, 4000);

// Socket.IO SMS balance
const socket = io("https://net1.amazons.co.ke:9585", { transports: ['websocket'] });
setInterval(() => socket.emit("dashboard.get.sms.balance", JSON.stringify({})), 1000);
socket.on("dashboard.get.sms.balance", (data) => {
    let bal = parseFloat(JSON.parse(data).smsBalance || 0).toFixed(0);
    $('#smsBalanceText').text(bal);
});
</script>
@endsection
