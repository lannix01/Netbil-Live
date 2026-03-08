<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NetBil Demo Connect</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #c2410c;
            --primary-dark: #9a3412;
            --primary-soft: #ffedd5;
            --surface: #ffffff;
            --surface-soft: #fff7ed;
            --text: #111827;
            --muted: #6b7280;
            --border: #fed7aa;
            --success: #0f766e;
            --danger: #b91c1c;
            --warning: #b45309;
        }

        body {
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at 12% 20%, rgba(194, 65, 12, 0.10), transparent 42%),
                radial-gradient(circle at 85% 85%, rgba(15, 118, 110, 0.14), transparent 44%),
                linear-gradient(145deg, #fff7ed 0%, #fffbeb 46%, #f0fdfa 100%);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .portal {
            width: 100%;
            max-width: 980px;
            background: var(--surface);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 20px 50px rgba(17, 24, 39, 0.12);
            overflow: hidden;
        }

        .portal-head {
            padding: 28px 30px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }

        .portal-head h1 {
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .portal-head p {
            margin-top: 8px;
            opacity: 0.9;
        }

        .portal-body {
            padding: 26px;
            background: var(--surface-soft);
        }

        .demo-ribbon {
            border: 1px solid #fed7aa;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff7ed, #fffbeb);
            padding: 10px;
            margin-bottom: 10px;
        }

        .demo-ribbon-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) repeat(2, minmax(0, 0.92fr));
            gap: 8px;
        }

        .demo-mini-card {
            border: 1px solid #fdba74;
            border-radius: 10px;
            background: #fff;
            padding: 8px 10px;
            min-width: 0;
        }

        .demo-mini-card label {
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            color: #7c2d12;
            margin-bottom: 4px;
            line-height: 1.1;
        }

        .pay-group label,
        .metered-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: #7c2d12;
            margin-bottom: 4px;
        }

        .demo-value {
            font-size: 0.82rem;
            font-weight: 700;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .demo-pin-input {
            padding: 8px 10px;
            border-radius: 9px;
            font-size: 0.88rem;
        }

        .demo-status-line {
            margin-bottom: 14px;
            font-size: 0.8rem;
            color: #7c2d12;
        }

        .tabs {
            display: flex;
            gap: 8px;
            background: #fed7aa;
            border-radius: 12px;
            padding: 6px;
            margin-bottom: 16px;
        }

        .tab-btn {
            flex: 1;
            border: 0;
            background: transparent;
            color: #9a3412;
            border-radius: 9px;
            padding: 11px 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .tab-btn.active {
            background: #fff;
            color: #7c2d12;
            box-shadow: 0 4px 14px rgba(194, 65, 12, 0.16);
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        .hotspot-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .plan-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 12px;
            box-shadow: 0 6px 12px rgba(17, 24, 39, 0.06);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .plan-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(17, 24, 39, 0.1);
            border-color: #fb923c;
        }

        .plan-summary {
            font-weight: 700;
            font-size: 0.95rem;
            line-height: 1.35;
            color: #111827;
            flex: 1;
        }

        .btn-buy {
            width: auto;
            min-width: 108px;
            padding: 9px 14px;
            border-radius: 9px;
        }

        .input {
            width: 100%;
            border: 1px solid #fdba74;
            background: #fff;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 0.95rem;
            color: #111827;
        }

        .input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(194, 65, 12, 0.12);
        }

        .btn {
            border: 0;
            border-radius: 10px;
            padding: 11px 14px;
            font-weight: 800;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            min-width: 180px;
        }

        .btn-green {
            background: linear-gradient(135deg, #0f766e, #115e59);
        }

        .btn-green:hover,
        .btn:hover {
            filter: brightness(1.03);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .pay-state {
            margin-top: 12px;
            font-size: 0.85rem;
            color: #374151;
            line-height: 1.45;
        }

        .pay-group {
            margin-top: 10px;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.48);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 1200;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-card {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #fdba74;
            box-shadow: 0 26px 56px rgba(17, 24, 39, 0.28);
            padding: 18px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            right: 10px;
            top: 10px;
            border: 0;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            background: #ffedd5;
            color: #7c2d12;
            font-size: 0.95rem;
        }

        .modal-title {
            font-size: 1.03rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 8px;
        }

        .modal-plan {
            background: #f0fdf4;
            border: 1px solid #99f6e4;
            border-radius: 11px;
            padding: 10px;
            font-size: 0.86rem;
            color: #115e59;
            margin-bottom: 10px;
        }

        .modal-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 12px;
        }

        .btn-muted {
            background: #f3f4f6;
            color: #111827;
        }

        .metered-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--border);
            padding: 18px;
        }

        .metered-note {
            font-size: 0.86rem;
            color: #4b5563;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .metered-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .metered-actions {
            margin-top: 14px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .metered-status {
            font-size: 0.84rem;
            color: #374151;
            line-height: 1.45;
        }

        .toast-stack {
            position: fixed;
            top: 14px;
            right: 14px;
            z-index: 1000;
            display: grid;
            gap: 8px;
            width: min(360px, calc(100vw - 20px));
        }

        .toast {
            border-radius: 10px;
            padding: 10px 12px;
            color: #fff;
            background: #111827;
            box-shadow: 0 10px 22px rgba(17, 24, 39, 0.26);
            animation: toastIn 0.18s ease-out;
        }

        .toast.success { background: #0f766e; }
        .toast.error { background: #b91c1c; }
        .toast.warning { background: #b45309; }

        .toast-title {
            font-size: 0.83rem;
            font-weight: 800;
            margin-bottom: 3px;
        }

        .toast-msg {
            font-size: 0.82rem;
            opacity: 0.96;
            white-space: pre-line;
        }

        @keyframes toastIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 820px) {
            .demo-ribbon-grid,
            .metered-grid {
                grid-template-columns: 1fr;
            }

            .plan-card {
                flex-direction: row;
                align-items: center;
            }

            .btn-buy {
                width: auto;
                min-width: 92px;
                flex: 0 0 auto;
            }

            .modal-actions {
                grid-template-columns: 1fr;
            }

            .modal-actions .btn {
                width: 100%;
            }
        }

        @media (max-width: 620px) {
            body {
                padding: 14px;
            }

            .portal-head,
            .portal-body {
                padding: 18px;
            }

            .plan-card,
            .metered-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn,
            .btn-buy {
                width: 100%;
                min-width: 0;
            }
        }
    </style>
</head>
<body>
@php
    $defaultMeteredPackage = $meteredPackages->first();
@endphp
<div class="portal">
    <div class="portal-head">
        <h1><i class="fas fa-wifi"></i> NetBil Demo Captive Portal</h1>
        <p>Choose a hotspot package or login with the demo metered account.</p>
    </div>

    <div class="portal-body">
        <section class="demo-ribbon">
            <div class="demo-ribbon-grid">
                <div class="demo-mini-card">
                    <label for="demoPin">Demo PIN</label>
                    <input
                        id="demoPin"
                        class="input demo-pin-input"
                        type="password"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        placeholder="PIN"
                        @disabled(!$demoPinConfigured)
                    >
                </div>

                <div class="demo-mini-card">
                    <label>MAC</label>
                    <div class="demo-value" title="{{ $portal['mac'] ?: 'Unavailable' }}">{{ $portal['mac'] ?: 'Unavailable' }}</div>
                </div>

                <div class="demo-mini-card">
                    <label>IP</label>
                    <div class="demo-value" title="{{ $portal['ip'] ?: request()->ip() }}">{{ $portal['ip'] ?: request()->ip() }}</div>
                </div>
            </div>
        </section>

        <div class="demo-status-line" id="demoStatusBanner">
            <span id="demoStatusText">{{ $demoPinConfigured ? 'Select a package or login below.' : 'Demo locked.' }}</span>
        </div>

        <div class="tabs">
            <button class="tab-btn active" type="button" id="tabHotspot">Hotspot</button>
            <button class="tab-btn" type="button" id="tabMetered">Metered</button>
        </div>

        <section class="section active" id="hotspotSection">
            <div class="hotspot-cards">
                @forelse($hotspotPackages as $package)
                    @php
                        $minutes = (int)($package->duration_minutes ?? 0);
                        if ($minutes <= 0) {
                            $durationHours = (int)($package->duration ?? 0);
                            $minutes = $durationHours > 0 ? $durationHours * 60 : 0;
                        }

                        if ($minutes > 0) {
                            $hours = intdiv($minutes, 60);
                            $mins = $minutes % 60;
                            if ($hours > 0 && $mins > 0) {
                                $timeLabel = "{$hours}h {$mins}m";
                            } elseif ($hours > 0) {
                                $timeLabel = "{$hours}h";
                            } else {
                                $timeLabel = "{$mins}m";
                            }
                        } else {
                            $bytes = (float)($package->data_limit ?? 0);
                            if ($bytes > 0) {
                                $timeLabel = number_format($bytes / (1024 * 1024), 0) . ' MB cap';
                            } else {
                                $timeLabel = 'On demand';
                            }
                        }
                    @endphp
                    <article class="plan-card">
                        <div class="plan-summary">
                            {{ $package->name }} for {{ $timeLabel }} @ KSH {{ number_format((float)($package->price ?? 0), 2) }}
                        </div>
                        <button
                            type="button"
                            class="btn btn-green btn-buy buy-plan-btn"
                            data-package-id="{{ $package->id }}"
                            data-package-name="{{ $package->name }}"
                            data-package-time="{{ $timeLabel }}"
                            data-package-price="{{ number_format((float)($package->price ?? 0), 2, '.', '') }}"
                            @disabled(!$demoPinConfigured)
                        >
                            Buy
                        </button>
                    </article>
                @empty
                    <div class="plan-card">
                        <div style="color:#7c2d12;">No hotspot demo packages are available.</div>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="section" id="meteredSection">
            <div class="metered-card">
                <p class="metered-note">
                    Use <strong>{{ $demoMeteredUsername }}</strong> / <strong>{{ $demoMeteredPassword }}</strong>. Package: <strong>{{ $defaultMeteredPackage?->name ?? 'Unavailable' }}</strong>.
                </p>
                <form id="meteredForm">
                    <div class="metered-grid">
                        <div class="metered-group">
                            <label for="meteredUsername">Username</label>
                            <input id="meteredUsername" class="input" name="username" value="{{ $demoMeteredUsername }}" required @disabled(!$demoPinConfigured)>
                        </div>
                        <div class="metered-group">
                            <label for="meteredPassword">Password</label>
                            <input id="meteredPassword" class="input" type="password" name="password" value="{{ $demoMeteredPassword }}" required @disabled(!$demoPinConfigured)>
                        </div>
                    </div>
                    <div class="metered-actions">
                        <button type="submit" class="btn" id="meteredConnectBtn" @disabled(!$demoPinConfigured)>Connect Metered User</button>
                        <div class="metered-status" id="meteredStatus">
                            {{ $demoPinConfigured ? 'Enter PIN and connect.' : 'Demo locked.' }}
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<div class="toast-stack" id="toastStack"></div>

<div class="modal-overlay" id="hotspotDemoModal" aria-hidden="true">
    <div class="modal-card">
        <button type="button" class="modal-close" id="closeHotspotModal"><i class="fas fa-times"></i></button>
        <div class="modal-title">Pay and Connect</div>
        <div class="modal-plan" id="demoPlanSummary">Choose a package to continue.</div>
        <div class="pay-group">
            <label for="hotspotMsisdn">M-Pesa Number</label>
            <input id="hotspotMsisdn" class="input" placeholder="07XXXXXXXX or 2547XXXXXXXX">
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-muted" id="cancelHotspotModalBtn">Cancel</button>
            <button type="button" class="btn btn-green" id="hotspotPayBtn">Pay and Connect</button>
        </div>
        <div class="pay-state" id="hotspotDemoState">Enter PIN, number, and pay.</div>
    </div>
</div>

<script>
const demoMeta = {
    startUrl: @json(route('connect.demo.start', [], false)),
    requestPaymentUrl: @json(route('connect.hotspot.request-payment', [], false)),
    paymentStatusUrl: @json(route('connect.hotspot.payment-status', [], false)),
    mac: @json($portal['mac'] ?? ''),
    ip: @json($portal['ip'] ?? request()->ip()),
    pinConfigured: @json($demoPinConfigured),
    meteredUsername: @json($demoMeteredUsername ?? 'demo'),
    meteredPassword: @json($demoMeteredPassword ?? 'password'),
};

const state = {
    selectedPlan: null,
    payReference: '',
    payPollTimer: null,
    payAttempts: 0,
    payBusy: false,
    redirecting: false,
};

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function escapeHtml(value) {
    return (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function showToast(title, message, type = 'info') {
    const stack = document.getElementById('toastStack');
    if (!stack) return;

    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<div class="toast-title">${escapeHtml(title)}</div><div class="toast-msg">${escapeHtml(message)}</div>`;
    stack.appendChild(el);
    setTimeout(() => el.remove(), 2400);
}

async function readJsonResponse(res) {
    const text = await res.text();
    try {
        return JSON.parse(text || '{}');
    } catch {
        return { ok: false, message: text || 'Unexpected server response.' };
    }
}

function localizeStatusUrl(rawUrl) {
    const raw = (rawUrl || '').toString().trim();
    if (!raw) return '';
    try {
        const parsed = new URL(raw, window.location.origin);
        return `${parsed.pathname}${parsed.search}${parsed.hash}`;
    } catch {
        return raw;
    }
}

function prettyDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return dateStr;
    return date.toLocaleString();
}

function setActiveTab(tab) {
    const hotspotBtn = document.getElementById('tabHotspot');
    const meteredBtn = document.getElementById('tabMetered');
    const hotspotSection = document.getElementById('hotspotSection');
    const meteredSection = document.getElementById('meteredSection');

    if (tab === 'hotspot') {
        hotspotBtn?.classList.add('active');
        meteredBtn?.classList.remove('active');
        hotspotSection?.classList.add('active');
        meteredSection?.classList.remove('active');
        return;
    }

    meteredBtn?.classList.add('active');
    hotspotBtn?.classList.remove('active');
    meteredSection?.classList.add('active');
    hotspotSection?.classList.remove('active');
}

function setDemoStatus(message) {
    const el = document.getElementById('demoStatusText');
    if (el) {
        el.textContent = message;
    }
}

function setMeteredStatus(message) {
    const el = document.getElementById('meteredStatus');
    if (el) {
        el.textContent = message;
    }
}

function getDemoPin() {
    return (document.getElementById('demoPin')?.value || '').trim();
}

function validateDemoGate() {
    if (!demoMeta.pinConfigured) {
        return 'Demo locked.';
    }

    const pin = getDemoPin();
    if (!pin) {
        return 'Enter demo PIN.';
    }

    return null;
}

function getHotspotMsisdn() {
    return (document.getElementById('hotspotMsisdn')?.value || '').trim();
}

function clearHotspotPayPoll() {
    if (state.payPollTimer) {
        window.clearTimeout(state.payPollTimer);
        state.payPollTimer = null;
    }
}

function setHotspotModalBusy(busy) {
    state.payBusy = busy;
    ['closeHotspotModal', 'cancelHotspotModalBtn', 'hotspotPayBtn'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) {
            el.disabled = busy;
        }
    });
}

function updateHotspotSummary() {
    const summary = document.getElementById('demoPlanSummary');
    if (!summary || !state.selectedPlan) return;
    summary.textContent = `${state.selectedPlan.name} for ${state.selectedPlan.time} @ KSH ${state.selectedPlan.price.toFixed(2)}`;
}

function openHotspotModal(plan) {
    const planChanged = !state.selectedPlan || state.selectedPlan.id !== plan.id;
    state.selectedPlan = plan;
    if (planChanged) {
        clearHotspotPayPoll();
        state.payReference = '';
        state.payAttempts = 0;
        setHotspotModalBusy(false);
        const msisdnInput = document.getElementById('hotspotMsisdn');
        if (msisdnInput) {
            msisdnInput.value = '';
        }
    }

    updateHotspotSummary();
    const stateEl = document.getElementById('hotspotDemoState');
    if (stateEl) {
        stateEl.textContent = state.payReference
            ? 'Payment pending. Tap Pay and Connect to check.'
            : 'Enter PIN, number, and pay.';
    }

    const modal = document.getElementById('hotspotDemoModal');
    if (modal) {
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    }
}

function closeHotspotModal() {
    if (state.payBusy) {
        return;
    }

    const modal = document.getElementById('hotspotDemoModal');
    if (modal) {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    }
}

async function handleConnectedState(payload, fallbackMode) {
    if (state.redirecting) {
        return;
    }

    if (fallbackMode === 'hotspot') {
        clearHotspotPayPoll();
        state.payReference = '';
        state.payAttempts = 0;
        setHotspotModalBusy(false);
        const modal = document.getElementById('hotspotDemoModal');
        if (modal) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    const serverMessage = (payload?.message || payload?.data?.message || 'Connected successfully.').toString();
    const statusUrl = localizeStatusUrl(payload?.status_url ?? payload?.data?.status_url ?? '');
    const statusPollUrl = localizeStatusUrl(payload?.status_poll_url ?? payload?.data?.status_poll_url ?? '');
    const successUrl = localizeStatusUrl(payload?.success_url ?? payload?.data?.success_url ?? '');
    let statusLine = serverMessage;

    if (statusPollUrl) {
        try {
            const res = await fetch(statusPollUrl, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            const pollData = await readJsonResponse(res);
            const connection = pollData?.connection || {};
            const mode = (connection.mode || fallbackMode || 'hotspot').toString();
            const status = (connection.status || 'active').toString();
            const expires = prettyDate(connection.expires_at || '');
            statusLine = `${serverMessage} (${mode}) • Status: ${status}${expires ? ` • Expires: ${expires}` : ''}`;
        } catch (_error) {
            statusLine = serverMessage;
        }
    }

    setDemoStatus(statusLine);
    setMeteredStatus('Connected. Redirecting...');
    showToast('Connected', `${serverMessage} Redirecting to session page...`, 'success');

    const redirectUrl = fallbackMode === 'hotspot'
        ? (successUrl || statusUrl)
        : (statusUrl || successUrl);

    if (!redirectUrl) {
        return;
    }

    state.redirecting = true;
    window.setTimeout(() => {
        window.location.href = redirectUrl;
    }, 1000);
}

async function pollHotspotPayment(reference) {
    if (!reference) {
        return;
    }

    clearHotspotPayPoll();

    const stateEl = document.getElementById('hotspotDemoState');
    state.payAttempts += 1;
    if (stateEl) {
        stateEl.textContent = 'Waiting for payment confirmation...';
    }

    try {
        const url = `${demoMeta.paymentStatusUrl}?${new URLSearchParams({ reference }).toString()}`;
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });
        const data = await readJsonResponse(res);

        if (data?.connected) {
            await handleConnectedState(data, 'hotspot');
            return;
        }

        if (!res.ok || data?.ok === false) {
            const message = (data?.message || 'Payment did not complete.').toString();
            state.payReference = '';
            state.payAttempts = 0;
            setHotspotModalBusy(false);
            setDemoStatus(message);
            if (stateEl) {
                stateEl.textContent = message;
            }
            showToast('Payment failed', message, 'error');
            return;
        }

        const message = (data?.message || 'Waiting for payment confirmation.').toString();
        if (stateEl) {
            stateEl.textContent = message;
        }

        if (state.payAttempts >= 30) {
            setHotspotModalBusy(false);
            const timeoutMessage = 'Still waiting. Tap Pay and Connect to check again.';
            setDemoStatus(timeoutMessage);
            if (stateEl) {
                stateEl.textContent = timeoutMessage;
            }
            showToast('Payment pending', timeoutMessage, 'warning');
            return;
        }

        state.payPollTimer = window.setTimeout(() => {
            pollHotspotPayment(reference);
        }, 3000);
    } catch (_error) {
        setHotspotModalBusy(false);
        const message = 'Could not confirm payment. Tap Pay and Connect to check again.';
        setDemoStatus(message);
        if (stateEl) {
            stateEl.textContent = message;
        }
        showToast('Network error', message, 'error');
    }
}

async function requestHotspotDemo() {
    if (!state.selectedPlan || !state.selectedPlan.id) {
        showToast('Plan required', 'Select a hotspot package first.', 'warning');
        return;
    }

    const stateEl = document.getElementById('hotspotDemoState');
    const gateError = validateDemoGate();
    if (gateError) {
        setDemoStatus(gateError);
        if (stateEl) stateEl.textContent = gateError;
        showToast('Demo gate', gateError, 'warning');
        document.getElementById('demoPin')?.focus();
        return;
    }

    if (state.payReference) {
        state.payAttempts = 0;
        setHotspotModalBusy(true);
        if (stateEl) {
            stateEl.textContent = 'Checking payment...';
        }
        await pollHotspotPayment(state.payReference);
        return;
    }

    const msisdn = getHotspotMsisdn();
    if (!msisdn) {
        const message = 'Enter M-Pesa number.';
        setDemoStatus(message);
        if (stateEl) {
            stateEl.textContent = message;
        }
        showToast('Number required', message, 'warning');
        document.getElementById('hotspotMsisdn')?.focus();
        return;
    }

    setHotspotModalBusy(true);
    if (stateEl) stateEl.textContent = 'Sending STK...';

    try {
        const res = await fetch(demoMeta.requestPaymentUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                package_id: state.selectedPlan.id,
                msisdn,
                demo: true,
                pin: getDemoPin(),
                mac: demoMeta.mac,
                ip: demoMeta.ip,
            }),
        });

        const data = await readJsonResponse(res);
        if (!res.ok || data?.ok === false) {
            const message = (data?.message || 'Could not start hotspot demo.').toString();
            setDemoStatus(message);
            if (stateEl) stateEl.textContent = message;
            showToast('Hotspot demo failed', message, 'error');
            setHotspotModalBusy(false);
            return;
        }

        if (data?.connected) {
            await handleConnectedState(data, 'hotspot');
            return;
        }

        state.payReference = (data?.reference || '').toString();
        state.payAttempts = 0;
        const message = (data?.message || 'STK sent. Complete payment on your phone.').toString();
        setDemoStatus(message);
        if (stateEl) stateEl.textContent = message;
        showToast('STK sent', message, 'success');
        await pollHotspotPayment(state.payReference);
    } catch (error) {
        const message = (error?.message || 'Network error while sending STK.').toString();
        setHotspotModalBusy(false);
        setDemoStatus(message);
        if (stateEl) stateEl.textContent = message;
        showToast('Network error', message, 'error');
    } finally {
        if (!state.payBusy) {
            setHotspotModalBusy(false);
        }
    }
}

async function submitMetered(event) {
    event.preventDefault();

    const gateError = validateDemoGate();
    if (gateError) {
        setDemoStatus(gateError);
        setMeteredStatus(gateError);
        showToast('Demo gate', gateError, 'warning');
        document.getElementById('demoPin')?.focus();
        return;
    }

    const username = (document.getElementById('meteredUsername')?.value || '').trim();
    const password = (document.getElementById('meteredPassword')?.value || '').trim();
    if (!username || !password) {
        const message = 'Enter username and password.';
        setMeteredStatus(message);
        showToast('Credentials required', message, 'warning');
        return;
    }

    const button = document.getElementById('meteredConnectBtn');
    if (button) button.disabled = true;
    setMeteredStatus('Connecting...');

    try {
        const res = await fetch(demoMeta.startUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                mode: 'metered',
                pin: getDemoPin(),
                username,
                password,
                mac: demoMeta.mac,
                ip: demoMeta.ip,
            }),
        });

        const data = await readJsonResponse(res);
        if (!res.ok || data?.ok === false) {
            const message = (data?.message || 'Could not connect metered demo.').toString();
            setDemoStatus(message);
            setMeteredStatus(message);
            showToast('Metered demo failed', message, 'error');
            return;
        }

        await handleConnectedState(data, 'metered');
    } catch (error) {
        const message = (error?.message || 'Network error while starting metered demo.').toString();
        setDemoStatus(message);
        setMeteredStatus(message);
        showToast('Network error', message, 'error');
    } finally {
        if (button) button.disabled = false;
    }
}

document.getElementById('tabHotspot')?.addEventListener('click', () => setActiveTab('hotspot'));
document.getElementById('tabMetered')?.addEventListener('click', () => setActiveTab('metered'));

document.querySelectorAll('.buy-plan-btn').forEach((button) => {
    button.addEventListener('click', () => {
        const plan = {
            id: Number(button.dataset.packageId || 0),
            name: button.dataset.packageName || 'Plan',
            time: button.dataset.packageTime || 'N/A',
            price: Number(button.dataset.packagePrice || 0),
        };
        openHotspotModal(plan);
    });
});

document.getElementById('hotspotPayBtn')?.addEventListener('click', requestHotspotDemo);
document.getElementById('closeHotspotModal')?.addEventListener('click', closeHotspotModal);
document.getElementById('cancelHotspotModalBtn')?.addEventListener('click', closeHotspotModal);
document.getElementById('hotspotDemoModal')?.addEventListener('click', (event) => {
    if (event.target?.id === 'hotspotDemoModal') {
        closeHotspotModal();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeHotspotModal();
    }
});

document.getElementById('meteredForm')?.addEventListener('submit', submitMetered);
</script>
</body>
</html>
