@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bold">System Logs</h4>
            <div class="text-muted small">
                Audit trail and MikroTik logs (timezone: {{ $timezone ?? config('app.timezone') }}).
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input id="auditSearch" class="form-control form-control-sm" style="min-width: 250px;" placeholder="Search audit logs...">
            <button id="refreshLogsBtn" class="btn btn-sm btn-primary">Refresh</button>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="autoRefreshToggle" checked>
                <label class="form-check-label small" for="autoRefreshToggle">Auto</label>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Server Time</div>
                    <div class="fs-5 fw-bold" id="serverTime">-</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">MikroTik Connection</div>
                    <div class="fs-6 fw-bold" id="routerState">Checking...</div>
                    <div class="small text-muted" id="routerHost">Host: {{ $routerHost ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Audit Entries Loaded</div>
                    <div class="fs-5 fw-bold" id="auditCount">0</div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" id="logsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="audit-tab" data-bs-toggle="tab" data-bs-target="#auditPanel" type="button" role="tab">System Audit</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="router-tab" data-bs-toggle="tab" data-bs-target="#routerPanel" type="button" role="tab">MikroTik Logs</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="auditPanel" role="tabpanel" aria-labelledby="audit-tab">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 72vh; overflow:auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light position-sticky top-0" style="z-index: 10;">
                                <tr>
                                    <th style="min-width: 150px;">Time</th>
                                    <th style="min-width: 190px;">Actor</th>
                                    <th style="min-width: 120px;">Event</th>
                                    <th style="min-width: 140px;">Action</th>
                                    <th style="min-width: 210px;">Description</th>
                                    <th style="min-width: 110px;">Route</th>
                                    <th style="min-width: 90px;">Method</th>
                                    <th style="min-width: 70px;">Status</th>
                                    <th style="min-width: 100px;">IP</th>
                                    <th style="min-width: 120px;">Context</th>
                                </tr>
                            </thead>
                            <tbody id="auditBody">
                                <tr><td colspan="10" class="text-center text-muted py-4">Loading audit logs...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="routerPanel" role="tabpanel" aria-labelledby="router-tab">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 72vh; overflow:auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light position-sticky top-0" style="z-index: 10;">
                                <tr>
                                    <th style="min-width: 140px;">Time</th>
                                    <th style="min-width: 180px;">Topics</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody id="routerBody">
                                <tr><td colspan="3" class="text-center text-muted py-4">Loading router logs...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const logsFetchBase = @json(route('logs.fetch', [], false));
const state = {
    timer: null,
    autoRefresh: true,
};

function escapeHtml(value) {
    return (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function readJson(response) {
    const text = await response.text();
    try {
        return JSON.parse(text || '{}');
    } catch {
        return {};
    }
}

function renderRouter(router) {
    const routerState = document.getElementById('routerState');
    const routerHost = document.getElementById('routerHost');
    const routerBody = document.getElementById('routerBody');

    routerState.textContent = router?.connected ? 'Connected' : 'Disconnected';
    routerState.className = router?.connected ? 'fs-6 fw-bold text-success' : 'fs-6 fw-bold text-danger';
    routerHost.textContent = `Host: ${router?.host || '-'}`;

    const logs = Array.isArray(router?.logs) ? router.logs : [];
    if (!logs.length) {
        const msg = router?.error ? escapeHtml(router.error) : 'No router logs available.';
        routerBody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-4">${msg}</td></tr>`;
        return;
    }

    routerBody.innerHTML = logs.map((log) => `
        <tr>
            <td>${escapeHtml(log.time || '-')}</td>
            <td>${escapeHtml(log.topics || '-')}</td>
            <td>${escapeHtml(log.message || '-')}</td>
        </tr>
    `).join('');
}

function renderAudit(audit) {
    const auditBody = document.getElementById('auditBody');
    const auditCount = document.getElementById('auditCount');
    const logs = Array.isArray(audit?.logs) ? audit.logs : [];

    auditCount.textContent = logs.length.toString();

    if (!logs.length) {
        const msg = audit?.error ? escapeHtml(audit.error) : 'No audit entries found.';
        auditBody.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-4">${msg}</td></tr>`;
        return;
    }

    auditBody.innerHTML = logs.map((log) => {
        const actor = `${log?.user?.name || '-'}${log?.user?.email ? ` (${log.user.email})` : ''}`;
        const context = JSON.stringify(log.context || {});
        return `
            <tr>
                <td>${escapeHtml(log.time || '-')}</td>
                <td>
                    <div>${escapeHtml(actor)}</div>
                    <div class="text-muted small">${escapeHtml(log?.user?.role || '')}</div>
                </td>
                <td><span class="badge bg-secondary">${escapeHtml(log.event || '-')}</span></td>
                <td><span class="badge bg-info text-dark">${escapeHtml(log.action || '-')}</span></td>
                <td>${escapeHtml(log.description || '-')}</td>
                <td>
                    <div>${escapeHtml(log.route || '-')}</div>
                    <div class="text-muted small">${escapeHtml(log.path || '-')}</div>
                </td>
                <td>${escapeHtml(log.method || '-')}</td>
                <td>${escapeHtml(log.status || '-')}</td>
                <td>${escapeHtml(log.ip || '-')}</td>
                <td>
                    <details>
                        <summary class="small">View</summary>
                        <pre class="small mb-0 mt-1" style="white-space: pre-wrap; max-width: 420px;">${escapeHtml(context)}</pre>
                    </details>
                </td>
            </tr>
        `;
    }).join('');
}

async function loadLogs() {
    await Promise.allSettled([loadAuditLogs(), loadRouterLogs()]);
}

async function loadAuditLogs() {
    const q = (document.getElementById('auditSearch')?.value || '').trim();
    const url = `${logsFetchBase}?scope=audit&q=${encodeURIComponent(q)}`;
    try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await readJson(res);

        document.getElementById('serverTime').textContent = `${data.server_time || '-'} (${data.timezone || '-'})`;
        renderAudit(data.audit || {});
    } catch (_e) {
        document.getElementById('auditBody').innerHTML = '<tr><td colspan="10" class="text-center text-danger py-4">Network error while loading logs.</td></tr>';
    }
}

async function loadRouterLogs() {
    const url = `${logsFetchBase}?scope=router`;
    try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await readJson(res);
        if (data.server_time) {
            document.getElementById('serverTime').textContent = `${data.server_time || '-'} (${data.timezone || '-'})`;
        }
        renderRouter(data.router || {});
    } catch (_e) {
        document.getElementById('routerBody').innerHTML = '<tr><td colspan="3" class="text-center text-danger py-4">Network error while loading router logs.</td></tr>';
    }
}

function bindActions() {
    document.getElementById('refreshLogsBtn')?.addEventListener('click', loadLogs);
    document.getElementById('auditSearch')?.addEventListener('input', () => {
        if (state.timer) {
            clearTimeout(state.timer);
        }
        state.timer = setTimeout(loadLogs, 280);
    });

    document.getElementById('autoRefreshToggle')?.addEventListener('change', (event) => {
        state.autoRefresh = Boolean(event.target?.checked);
    });
}

bindActions();
loadLogs();
setInterval(() => {
    if (state.autoRefresh) {
        loadLogs();
    }
}, 8000);
</script>
@endsection
