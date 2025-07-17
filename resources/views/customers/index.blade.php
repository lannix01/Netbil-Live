@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
    .mini-card {
        min-height: 80px;
        background-color: rgba(161, 189, 241, 0.85); /* RGBA background */
        color: rgb(12, 3, 3);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        white-space: nowrap;
    }
    .mini-card-title {
        font-size: 0.95rem;
        font-weight: 500;
        flex-grow: 1;
        margin: 0;
    }
    .mini-card-value {
        font-size: 1.8rem;
        font-weight: bold;
        margin-left: auto;
        text-align: center;
        min-width: 60px;
    }
</style>
<h2 class="mb-3">Hotspot Management Panel</h2>
<div class="row g-2 mb-2">
    <div class="col-md-2 col-6">
        <div class="card mini-card">
            <p class="mini-card-title">Total Users</p>
            <div class="mini-card-value">{{ count($users) }}</div>
        </div>
    </div>

    <div class="col-md-2 col-6">
        <div class="card mini-card">
            <p class="mini-card-title">Active Sessions</p>
            <div class="mini-card-value">{{ count($activeSessions) }}</div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card mini-card">
            <p class="mini-card-title">Common Package</p>
            <div class="mini-card-value">
                {{ collect($users)->pluck('profile')->countBy()->sortDesc()->keys()->first() ?? 'N/A' }}
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card mini-card">
            <p class="mini-card-title">Unique Hosts</p>
            <div class="mini-card-value">
                {{ collect($hosts)->pluck('mac-address')->unique()->count() }}
            </div>
        </div>
    </div>
</div>
<div class="container-fluid mt-4">
    <h4 class="mb-3">Hotspot Customers</h4>

    {{-- Navigation Tabs --}}
    <ul class="nav nav-pills mb-3" id="hotspot-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="users-tab" data-bs-toggle="pill" data-bs-target="#users-pane" type="button" role="tab" onclick="refreshSection('users')">All Users</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sessions-tab" data-bs-toggle="pill" data-bs-target="#sessions-pane" type="button" role="tab" onclick="refreshSection('sessions')">Active Sessions</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="hosts-tab" data-bs-toggle="pill" data-bs-target="#hosts-pane" type="button" role="tab" onclick="refreshSection('hosts')">Hosts</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="cookies-tab" data-bs-toggle="pill" data-bs-target="#cookies-pane" type="button" role="tab" onclick="refreshSection('cookies')">Cookies</button>
        </li>
    </ul>

    <div class="tab-content" id="hotspot-tabs-content">
        <div class="tab-pane fade show active" id="users-pane" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Hotspot Users</strong>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshSection('users')">
                        <i class="bi bi-arrow-repeat"></i> Refresh
                    </button>
                </div>
                <div class="card-body p-0" id="users-section">
                    @include('customers.partials.users', ['users' => $users])
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="sessions-pane" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Active Sessions</strong>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshSection('sessions')">
                        <i class="bi bi-arrow-repeat"></i> Refresh
                    </button>
                </div>
                <div class="card-body p-0" id="sessions-section">
                    @include('customers.partials.sessions', ['activeSessions' => $activeSessions])
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="hosts-pane" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Hosts Table</strong>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshSection('hosts')">
                        <i class="bi bi-arrow-repeat"></i> Refresh
                    </button>
                </div>
                <div class="card-body p-0" id="hosts-section">
                    @include('customers.partials.hosts', ['hosts' => $hosts])
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="cookies-pane" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Hotspot Cookies</strong>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshSection('cookies')">
                        <i class="bi bi-arrow-repeat"></i> Refresh
                    </button>
                </div>
                <div class="card-body p-0" id="cookies-section">
                    @include('customers.partials.cookies', ['cookies' => $cookies])
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Live Traffic Modal --}}
<div class="modal fade" id="trafficModal" tabindex="-1" aria-labelledby="trafficModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Live Traffic Monitor for <span id="monitored-username" class="badge bg-primary"></span></h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="stopChart()"></button>
            </div>
            <div class="modal-body">
                <canvas id="trafficChart" height="140"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    let chart, chartInterval;

    function disconnectUser(mac) {
        fetch("{{ url('/customers/disconnect') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ mac })
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            refreshSection('users');
            refreshSection('sessions');
        });
    }

function monitorTrafficByUser(username) {
    stopChart();
    $('#monitored-username').text(username);

    const modal = new bootstrap.Modal(document.getElementById('trafficModal'));
    modal.show();

    const ctx = document.getElementById('trafficChart').getContext('2d');
    if (chart) chart.destroy();

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'RX (bps)',
                    data: [],
                    borderColor: 'blue',
                    borderWidth: 2,
                    fill: false
                },
                {
                    label: 'TX (bps)',
                    data: [],
                    borderColor: 'green',
                    borderWidth: 2,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            animation: false,
            scales: {
                y: { beginAtZero: true },
                x: { display: false }
            }
        }
    });

    chartInterval = setInterval(() => {
        fetch("{{ url('/customers/monitor-traffic') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ username })
        })
        .then(res => res.json())
        .then(data => {
            const now = new Date().toLocaleTimeString();
            if (chart.data.labels.length > 30) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
                chart.data.datasets[1].data.shift();
            }
            chart.data.labels.push(now);
            chart.data.datasets[0].data.push(data.rx || 0);
            chart.data.datasets[1].data.push(data.tx || 0);
            chart.update();
        });
    }, 1000);
}

function stopChart() {
    if (chartInterval) {
        clearInterval(chartInterval);
        chartInterval = null;
    }
}

    function refreshSection(section) {
        const sectionMap = {
            'users': '/customers/section/users',
            'hosts': '/customers/section/hosts',
            'cookies': '/customers/section/cookies',
            'sessions': '/customers/section/sessions'
        };

        const url = sectionMap[section];
        if (!url) return;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById(`${section}-section`).innerHTML = html;
        });
    }
</script>
@endsection
