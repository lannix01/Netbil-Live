<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NetBil Connect</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-soft: #dbeafe;
            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --border: #dbe2ef;
            --success: #0f766e;
            --danger: #b91c1c;
            --warning: #9a3412;
        }

        body {
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at 12% 20%, rgba(37, 99, 235, 0.11), transparent 42%),
                radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.14), transparent 44%),
                linear-gradient(145deg, #f1f5f9 0%, #e2e8f0 100%);
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
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
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

        .ads-ribbon {
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: linear-gradient(135deg, #eff6ff, #f8fafc);
            padding: 12px;
            margin-bottom: 14px;
        }

        .ads-ribbon-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .ads-ribbon-title {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #1d4ed8;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .ads-dots {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .ads-dot {
            width: 8px;
            height: 8px;
            border: 0;
            border-radius: 999px;
            background: #bfdbfe;
            cursor: pointer;
            padding: 0;
        }

        .ads-dot.active {
            width: 20px;
            background: #2563eb;
        }

        .ads-track {
            position: relative;
            min-height: 124px;
        }

        .ad-slide {
            display: none;
            align-items: center;
            gap: 12px;
            border: 1px solid #dbe2ef;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .ad-slide.active {
            display: flex;
        }

        .ad-copy {
            flex: 1;
            min-width: 0;
        }

        .ad-copy h3 {
            font-size: 0.96rem;
            line-height: 1.3;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .ad-copy p {
            font-size: 0.82rem;
            color: #334155;
            line-height: 1.42;
            margin-bottom: 8px;
        }

        .ad-cta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.76rem;
            font-weight: 700;
            color: #1d4ed8;
            text-decoration: none;
        }

        .ad-media {
            width: 156px;
            flex: 0 0 156px;
            border-radius: 10px;
            border: 1px solid #dbe2ef;
            overflow: hidden;
            background: #f8fafc;
        }

        .ad-media img,
        .ad-media video {
            width: 100%;
            height: 92px;
            object-fit: cover;
            display: block;
        }

        .tabs {
            display: flex;
            gap: 8px;
            background: #e2e8f0;
            border-radius: 12px;
            padding: 6px;
            margin-bottom: 16px;
        }

        .tab-btn {
            flex: 1;
            border: 0;
            background: transparent;
            color: #475569;
            border-radius: 9px;
            padding: 11px 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .tab-btn.active {
            background: #fff;
            color: var(--primary-dark);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.18);
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
            box-shadow: 0 6px 12px rgba(15, 23, 42, 0.06);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .plan-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.1);
            border-color: #93c5fd;
        }

        .plan-summary {
            font-weight: 700;
            font-size: 0.95rem;
            line-height: 1.35;
            color: #0f172a;
            flex: 1;
        }

        .btn-buy {
            width: auto;
            min-width: 108px;
            padding: 9px 14px;
            border-radius: 9px;
        }

        .pay-group label,
        .metered-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 4px;
        }

        .input {
            width: 100%;
            border: 1px solid #cbd5e1;
            background: #fff;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 0.95rem;
            color: #0f172a;
        }

        .input:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
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
            background: linear-gradient(135deg, #16a34a, #15803d);
        }

        .btn-green:hover {
            filter: brightness(1.04);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .pay-state {
            margin-top: 12px;
            font-size: 0.85rem;
            color: #334155;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.48);
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
            border: 1px solid #cbd5e1;
            box-shadow: 0 26px 56px rgba(15, 23, 42, 0.28);
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
            background: #e2e8f0;
            color: #0f172a;
            font-size: 0.95rem;
        }

        .modal-title {
            font-size: 1.03rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .modal-plan {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 11px;
            padding: 10px;
            font-size: 0.86rem;
            color: #14532d;
            margin-bottom: 10px;
        }

        .pay-popup-layer {
            position: absolute;
            inset: 0;
            border-radius: 16px;
            background: rgba(15, 23, 42, 0.36);
            backdrop-filter: blur(2px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 14px;
            z-index: 20;
        }

        .pay-popup-layer.show {
            display: flex;
        }

        .pay-flow {
            width: min(380px, 100%);
            border: 1px solid #dbeafe;
            border-radius: 11px;
            background: linear-gradient(180deg, #f8fbff 0%, #f2f7ff 100%);
            padding: 10px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.25);
        }

        .pay-flow-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .flow-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.76rem;
            font-weight: 800;
            border-radius: 999px;
            padding: 5px 10px;
            color: #1e3a8a;
            background: #dbeafe;
            white-space: nowrap;
        }

        .flow-spinner {
            width: 13px;
            height: 13px;
            border-radius: 999px;
            border: 2px solid rgba(30, 64, 175, 0.24);
            border-top-color: rgba(30, 64, 175, 0.95);
            animation: flowSpin .8s linear infinite;
            display: inline-block;
        }

        .flow-spinner.static {
            animation: none;
            border-color: rgba(30, 64, 175, 0.5);
            border-top-color: rgba(30, 64, 175, 0.5);
        }

        .flow-hint {
            font-size: 0.74rem;
            color: #334155;
            text-align: right;
        }

        .flow-track {
            height: 7px;
            background: #dbeafe;
            border-radius: 999px;
            overflow: hidden;
        }

        .flow-track-fill {
            height: 100%;
            width: 0%;
            border-radius: 999px;
            background: linear-gradient(90deg, #2563eb, #16a34a);
            transition: width 0.3s ease;
        }

        .flow-steps {
            margin-top: 8px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 6px;
        }

        .flow-step {
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748b;
            text-align: center;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 6px 4px;
            background: #fff;
            transition: all 0.2s ease;
        }

        .flow-step.done {
            color: #166534;
            background: #f0fdf4;
            border-color: #86efac;
        }

        .flow-step.active {
            color: #1e3a8a;
            background: #eff6ff;
            border-color: #93c5fd;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.16);
        }

        .flow-step.fail {
            color: #991b1b;
            background: #fef2f2;
            border-color: #fecaca;
        }

        .pay-flow[data-tone="success"] .flow-pill {
            color: #166534;
            background: #dcfce7;
        }

        .pay-flow[data-tone="success"] .flow-track-fill {
            background: linear-gradient(90deg, #16a34a, #15803d);
        }

        .pay-flow[data-tone="warning"] .flow-pill {
            color: #9a3412;
            background: #ffedd5;
        }

        .pay-flow[data-tone="warning"] .flow-track-fill {
            background: linear-gradient(90deg, #f59e0b, #ea580c);
        }

        .pay-flow[data-tone="danger"] .flow-pill {
            color: #991b1b;
            background: #fee2e2;
        }

        .pay-flow[data-tone="danger"] .flow-track-fill {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .pay-popup-close {
            margin-top: 10px;
            width: 100%;
        }

        .pay-popup-close[hidden] {
            display: none !important;
        }

        @keyframes flowSpin {
            to { transform: rotate(360deg); }
        }

        .modal-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 12px;
        }

        .btn-muted {
            background: #e2e8f0;
            color: #0f172a;
        }

        .metered-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--border);
            padding: 18px;
        }

        .metered-note {
            font-size: 0.86rem;
            color: #475569;
            margin-bottom: 12px;
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
            color: #334155;
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
            background: #0f172a;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.26);
            animation: toastIn 0.18s ease-out;
        }

        .toast.success { background: #0f766e; }
        .toast.error { background: #b91c1c; }
        .toast.warning { background: #9a3412; }

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
            .ad-slide {
                flex-direction: column;
                align-items: stretch;
            }

            .ad-media {
                width: 100%;
                flex: 0 0 auto;
            }

            .ad-media img,
            .ad-media video {
                height: 140px;
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

            .metered-grid {
                grid-template-columns: 1fr;
            }

            .modal-actions {
                grid-template-columns: 1fr;
            }

            .modal-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="portal">
    <div class="portal-head">
        <h1><i class="fas fa-wifi"></i> NetBil Captive Portal</h1>
        <p>Choose a hotspot package and pay via STK, or login as an existing metered account.</p>
    </div>

    <div class="portal-body">
        @if(($ads ?? collect())->isNotEmpty())
            <section class="ads-ribbon" id="portalAds">
                <div class="ads-ribbon-head">
                    <div class="ads-ribbon-title"><i class="fas fa-bullhorn"></i> Sponsored</div>
                    <div class="ads-dots" id="portalAdsDots"></div>
                </div>
                <div class="ads-track">
                    @foreach($ads as $ad)
                        @php
                            $mediaType = $ad->resolved_media_type ?? 'text';
                            $mediaUrl = $ad->resolved_media_url;
                            $ctaUrl = $ad->resolved_cta_url;
                            $ctaText = $ad->resolved_cta_text;
                        @endphp
                        <article class="ad-slide {{ $loop->first ? 'active' : '' }}" data-ad-index="{{ $loop->index }}">
                            <div class="ad-copy">
                                <h3>{{ $ad->title }}</h3>
                                @if((string)($ad->content ?? '') !== '')
                                    <p>{{ $ad->content }}</p>
                                @endif
                                @if($ctaUrl)
                                    <a class="ad-cta" href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer">
                                        {{ $ctaText ?: 'Learn more' }} <i class="fas fa-arrow-right"></i>
                                    </a>
                                @endif
                            </div>
                            @if(in_array($mediaType, ['image', 'video'], true) && $mediaUrl)
                                <div class="ad-media">
                                    @if($mediaType === 'video')
                                        <video src="{{ $mediaUrl }}" muted loop playsinline preload="metadata"></video>
                                    @else
                                        <img src="{{ $mediaUrl }}" alt="{{ $ad->title }}">
                                    @endif
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="tabs">
            <button class="tab-btn active" type="button" id="tabHotspot">Hotspot</button>
            <button class="tab-btn" type="button" id="tabMetered">Metered</button>
        </div>

        <section class="section active" id="hotspotSection">
            <div class="hotspot-cards">
                @forelse($packages as $package)
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
                        >
                            Buy
                        </button>
                    </article>
                @empty
                    <div class="plan-card">
                        <div style="color:#3b1818;">No hotspot plans available.</div>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="section" id="meteredSection">
            <div class="metered-card">
                <p class="metered-note">
                    Metered login is for existing users only. If your account invoices are overdue, connection is blocked until payment is made.
                </p>
                <form id="meteredForm">
                    <div class="metered-grid">
                        <div class="metered-group">
                            <label for="meteredUsername">Username</label>
                            <input id="meteredUsername" class="input" name="username" required>
                        </div>
                        <div class="metered-group">
                            <label for="meteredPassword">Password</label>
                            <input id="meteredPassword" class="input" type="password" name="password" required>
                        </div>
                    </div>
                    <div class="metered-actions">
                        <button type="submit" class="btn" id="meteredConnectBtn">Connect Metered User</button>
                        <div class="metered-status" id="meteredStatus">Enter your account credentials to continue.</div>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<div class="toast-stack" id="toastStack"></div>

<div class="modal-overlay" id="hotspotPayModal" aria-hidden="true">
    <div class="modal-card">
        <button type="button" class="modal-close" id="closeHotspotModal"><i class="fas fa-times"></i></button>
        <div class="modal-title">Pay and Connect</div>
        <div class="modal-plan" id="paySummary">Choose a package to continue.</div>
        <div class="pay-group">
            <label for="hotspotMsisdn">Enter M-Pesa Number</label>
            <input id="hotspotMsisdn" class="input" placeholder="07XXXXXXXX or 2547XXXXXXXX">
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-muted" id="cancelHotspotPayBtn">Cancel</button>
            <button type="button" class="btn btn-green" id="hotspotPayBtn">Pay and Connect</button>
        </div>
        <div class="pay-state" id="hotspotPayState">Select a package and enter your number to start payment.</div>

        <div class="pay-popup-layer" id="hotspotPayPopup" aria-hidden="true">
            <div class="pay-flow" id="hotspotPayFlow" data-tone="idle">
                <div class="pay-flow-head">
                    <span class="flow-pill">
                        <span class="flow-spinner static" id="hotspotFlowSpinner"></span>
                        <span id="hotspotFlowText">Ready</span>
                    </span>
                    <span class="flow-hint" id="hotspotFlowHint">No payment started</span>
                </div>
                <div class="flow-track">
                    <div class="flow-track-fill" id="hotspotFlowTrack"></div>
                </div>
                <div class="flow-steps">
                    <div class="flow-step" id="flowStepRequest">Request</div>
                    <div class="flow-step" id="flowStepSent">STK Sent</div>
                    <div class="flow-step" id="flowStepPin">PIN</div>
                    <div class="flow-step" id="flowStepResult">Result</div>
                </div>
                <button type="button" class="btn btn-muted pay-popup-close" id="hotspotFlowCloseBtn" hidden>Dismiss</button>
            </div>
        </div>
    </div>
</div>

<script>
const portalMeta = {
    mac: @json($portal['mac'] ?? ''),
    ip: @json($portal['ip'] ?? request()->ip()),
    requestPaymentUrl: @json(route('connect.hotspot.request-payment', [], false)),
    paymentStatusUrl: @json(route('connect.hotspot.payment-status', [], false)),
    meteredUrl: @json(route('connect.metered', [], false)),
};

const state = {
    selectedPlan: null,
    payPollTimer: null,
    payAttempts: 0,
    payFlowBlocking: false,
    currentFlowStage: 'idle',
    connectionStatusUrl: '',
    connectionStatusPollUrl: '',
    connectionSuccessUrl: '',
    redirecting: false,
};

const flowStageConfig = {
    idle: { text: 'Ready', hint: 'No payment started', tone: 'idle', progress: 0, doneSteps: 0, activeStep: 0, failStep: 0, spin: false },
    requesting: { text: 'Requesting Payment', hint: 'Sending STK request to server...', tone: 'idle', progress: 18, doneSteps: 0, activeStep: 1, failStep: 0, spin: true },
    stk_sent: { text: 'STK Sent', hint: 'Prompt sent to your phone.', tone: 'idle', progress: 42, doneSteps: 1, activeStep: 2, failStep: 0, spin: false },
    awaiting_pin: { text: 'Waiting for PIN', hint: 'Enter your M-Pesa PIN on phone.', tone: 'warning', progress: 62, doneSteps: 2, activeStep: 3, failStep: 0, spin: true },
    awaiting_confirmation: { text: 'Confirming Payment', hint: 'Checking payment status with provider...', tone: 'warning', progress: 78, doneSteps: 2, activeStep: 3, failStep: 0, spin: true },
    confirmed: { text: 'Confirmed', hint: 'Payment complete. Connecting now.', tone: 'success', progress: 100, doneSteps: 4, activeStep: 4, failStep: 0, spin: false },
    cancelled: { text: 'Cancelled', hint: 'Payment was cancelled by user.', tone: 'danger', progress: 100, doneSteps: 3, activeStep: 0, failStep: 4, spin: false },
    failed: { text: 'Failed', hint: 'Payment did not complete.', tone: 'danger', progress: 100, doneSteps: 3, activeStep: 0, failStep: 4, spin: false },
    timeout: { text: 'Timed Out', hint: 'Transaction timed out.', tone: 'danger', progress: 100, doneSteps: 3, activeStep: 0, failStep: 4, spin: false },
    expired: { text: 'Expired', hint: 'Payment session expired.', tone: 'danger', progress: 100, doneSteps: 3, activeStep: 0, failStep: 4, spin: false },
    error: { text: 'Network Error', hint: 'Could not reach payment service.', tone: 'danger', progress: 100, doneSteps: 1, activeStep: 0, failStep: 2, spin: false },
};

const flowStepIds = ['flowStepRequest', 'flowStepSent', 'flowStepPin', 'flowStepResult'];
const flowDismissibleStages = new Set(['confirmed', 'failed', 'cancelled', 'timeout', 'expired', 'error']);

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function showToast(title, message, type = 'info') {
    const stack = document.getElementById('toastStack');
    if (!stack) return;

    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<div class="toast-title">${escapeHtml(title)}</div><div class="toast-msg">${escapeHtml(message)}</div>`;
    stack.appendChild(el);
    setTimeout(() => el.remove(), 2200);
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

async function readJsonResponse(res) {
    const text = await res.text();
    try {
        return JSON.parse(text || '{}');
    } catch {
        return { ok: false, message: text || 'Unexpected server response.' };
    }
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

function updatePaySummary() {
    const summary = document.getElementById('paySummary');
    if (!summary || !state.selectedPlan) return;
    summary.textContent = `You selected ${state.selectedPlan.name} for ${state.selectedPlan.time}. Amount: KSH ${state.selectedPlan.price.toFixed(2)}`;
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
    const d = new Date(dateStr);
    if (Number.isNaN(d.getTime())) return dateStr;
    return d.toLocaleString();
}

function setHotspotActionsBlocked(blocked) {
    ['closeHotspotModal', 'cancelHotspotPayBtn', 'hotspotPayBtn'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) {
            el.disabled = blocked;
        }
    });
}

function setHotspotFlow(stage, hintOverride = null) {
    const cfg = flowStageConfig[stage] || flowStageConfig.idle;
    const flowEl = document.getElementById('hotspotPayFlow');
    const popupEl = document.getElementById('hotspotPayPopup');
    const flowText = document.getElementById('hotspotFlowText');
    const flowHint = document.getElementById('hotspotFlowHint');
    const flowTrack = document.getElementById('hotspotFlowTrack');
    const spinner = document.getElementById('hotspotFlowSpinner');
    const closeBtn = document.getElementById('hotspotFlowCloseBtn');

    if (flowEl) flowEl.setAttribute('data-tone', cfg.tone);
    if (flowText) flowText.textContent = cfg.text;
    if (flowHint) flowHint.textContent = hintOverride || cfg.hint;
    if (flowTrack) flowTrack.style.width = `${cfg.progress}%`;
    if (spinner) spinner.classList.toggle('static', !cfg.spin);

    flowStepIds.forEach((id, i) => {
        const stepEl = document.getElementById(id);
        if (!stepEl) return;
        const stepNo = i + 1;
        stepEl.classList.remove('done', 'active', 'fail');
        if (cfg.failStep === stepNo) {
            stepEl.classList.add('fail');
            return;
        }
        if (stepNo <= cfg.doneSteps) {
            stepEl.classList.add('done');
            return;
        }
        if (cfg.activeStep === stepNo) {
            stepEl.classList.add('active');
        }
    });

    const showPopup = stage !== 'idle';
    const dismissible = flowDismissibleStages.has(stage);

    if (popupEl) {
        popupEl.classList.toggle('show', showPopup);
        popupEl.setAttribute('aria-hidden', showPopup ? 'false' : 'true');
    }

    if (closeBtn) {
        closeBtn.hidden = !dismissible;
        if (!closeBtn.hidden) {
            closeBtn.textContent = stage === 'confirmed' ? 'Continue' : 'Dismiss';
        }
    }

    state.currentFlowStage = stage;
    state.payFlowBlocking = showPopup;
    setHotspotActionsBlocked(showPopup);
}

function openHotspotPayModal(plan) {
    if (state.payPollTimer) {
        clearTimeout(state.payPollTimer);
        state.payPollTimer = null;
    }

    state.connectionStatusUrl = '';
    state.connectionStatusPollUrl = '';
    state.connectionSuccessUrl = '';
    state.redirecting = false;
    state.selectedPlan = plan;
    updatePaySummary();
    setHotspotFlow('idle', 'Enter number then tap Pay and Connect.');
    setHotspotState('Enter M-Pesa number then click Pay and Connect.');

    const modal = document.getElementById('hotspotPayModal');
    if (modal) {
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    }

    const msisdnInput = document.getElementById('hotspotMsisdn');
    if (msisdnInput) {
        msisdnInput.focus();
    }
}

function closeHotspotPayModal() {
    if (state.payFlowBlocking) return;

    const modal = document.getElementById('hotspotPayModal');
    if (modal) {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    }
}

function dismissHotspotFlowPopup() {
    if (state.payPollTimer) {
        clearTimeout(state.payPollTimer);
        state.payPollTimer = null;
    }

    const stage = state.currentFlowStage;
    state.payAttempts = 0;
    setHotspotFlow('idle');

    if (stage === 'confirmed') {
        const modal = document.getElementById('hotspotPayModal');
        if (modal) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }
        return;
    }

    setHotspotState('You can edit your number and retry payment.');
}

function setHotspotState(text) {
    const el = document.getElementById('hotspotPayState');
    if (el) {
        el.textContent = text;
    }
}

async function handleConnectedState(payload) {
    if (state.redirecting) {
        return;
    }

    const serverMessage = (payload?.message || payload?.data?.message || 'Connected successfully.').toString();
    const restoreHint = payload?.reconnected
        ? 'Existing package restored. Redirecting...'
        : 'Connection is active. Redirecting...';
    const statusUrl = localizeStatusUrl(payload?.status_url ?? payload?.data?.status_url ?? '');
    const statusPollUrl = localizeStatusUrl(payload?.status_poll_url ?? payload?.data?.status_poll_url ?? '');
    const successUrl = localizeStatusUrl(payload?.success_url ?? payload?.data?.success_url ?? '');

    state.connectionStatusUrl = statusUrl;
    state.connectionStatusPollUrl = statusPollUrl;
    state.connectionSuccessUrl = successUrl;

    setHotspotFlow('confirmed', restoreHint);

    let statusLine = serverMessage;
    if (statusPollUrl) {
        try {
            const res = await fetch(statusPollUrl, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            const pollData = await readJsonResponse(res);
            const c = pollData?.connection || {};
            const mode = (c.mode || 'hotspot').toString();
            const stat = (c.status || 'active').toString();
            const expires = prettyDate(c.expires_at || '');
            statusLine = `${serverMessage} (${mode}) • Status: ${stat}${expires ? ` • Expires: ${expires}` : ''}`;
        } catch (_e) {
            statusLine = serverMessage;
        }
    }

    setHotspotState(statusLine);
    showToast('Connected', `${serverMessage} Redirecting to session page...`, 'success');

    const redirectUrl = successUrl || statusUrl;
    if (!redirectUrl) {
        return;
    }

    state.redirecting = true;
    setTimeout(() => {
        window.location.href = redirectUrl;
    }, 1200);
}

async function requestHotspotPayment() {
    if (!state.selectedPlan || !state.selectedPlan.id) {
        showToast('Plan required', 'Select a hotspot package first.', 'warning');
        return;
    }

    if (!portalMeta.mac) {
        showToast('Device MAC missing', 'Open this page from the captive portal login to continue.', 'error');
        return;
    }

    const msisdn = (document.getElementById('hotspotMsisdn')?.value || '').trim();
    if (!msisdn) {
        showToast('Phone required', 'Enter your M-Pesa number to proceed.', 'warning');
        return;
    }

    const btn = document.getElementById('hotspotPayBtn');
    if (btn) {
        btn.disabled = true;
    }
    setHotspotFlow('requesting');
    setHotspotState('Sending STK push request...');

    try {
        const res = await fetch(portalMeta.requestPaymentUrl, {
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
                mac: portalMeta.mac,
                ip: portalMeta.ip,
            }),
        });

        const data = await readJsonResponse(res);
        if (!res.ok || data?.ok === false) {
            showToast('Payment failed', data?.message || 'Could not request STK payment.', 'error');
            setHotspotFlow('failed', data?.message || 'Could not request STK payment.');
            setHotspotState('STK request failed. Retry after confirming number and plan.');
            return;
        }

        if (data?.connected) {
            await handleConnectedState(data);
            return;
        }

        const reference = (data?.reference || '').toString();
        if (!reference) {
            showToast('Missing reference', 'Could not track payment request.', 'error');
            setHotspotFlow('error', 'Payment reference missing.');
            setHotspotState('Payment reference missing. Please retry.');
            return;
        }

        showToast('STK sent', `Reference: ${reference}\nComplete payment on your phone.`, 'success');
        setHotspotFlow('stk_sent', `Reference: ${reference}`);
        setTimeout(() => {
            if (state.payAttempts === 0) {
                setHotspotFlow('awaiting_pin');
            }
        }, 500);
        setHotspotState(`Waiting for payment confirmation... Ref: ${reference}`);
        startHotspotPaymentPolling(reference);
    } catch (error) {
        const errMsg = (error?.message || 'Failed to reach payment service.').toString();
        showToast('Network error', errMsg, 'error');
        setHotspotFlow('error', errMsg);
        setHotspotState(`Network error while requesting payment (${errMsg}).`);
    }
}

function startHotspotPaymentPolling(reference) {
    if (state.payPollTimer) {
        clearTimeout(state.payPollTimer);
        state.payPollTimer = null;
    }

    state.payAttempts = 0;

    const poll = async () => {
        state.payAttempts += 1;

        try {
            if (state.payAttempts <= 2) {
                setHotspotFlow('awaiting_pin', 'Waiting for PIN approval on your phone.');
            } else {
                setHotspotFlow('awaiting_confirmation', `Checking confirmation (attempt ${state.payAttempts})...`);
            }

            const url = `${portalMeta.paymentStatusUrl}?reference=${encodeURIComponent(reference)}`;
            const res = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            const data = await readJsonResponse(res);

            const status = (data?.status || '').toString().toLowerCase();
            if (data?.ok && data?.connected) {
                await handleConnectedState(data);
                return;
            }

            if (!res.ok || data?.ok === false) {
                if (['failed', 'cancelled', 'timeout', 'expired'].includes(status)) {
                    const stage = ['failed', 'cancelled', 'timeout', 'expired'].includes(status) ? status : 'failed';
                    setHotspotFlow(stage, data?.message || `Payment ${status}.`);
                    showToast('Payment not completed', data?.message || `Status: ${status}`, 'error');
                    setHotspotState(`Payment failed (${status}).`);
                    return;
                }
            }

            setHotspotState(`Waiting for payment confirmation... Ref: ${reference}`);
        } catch (_error) {
            setHotspotFlow('error', 'Network issue while checking payment status.');
            setHotspotState(`Retrying payment check... Ref: ${reference}`);
        }

        if (state.payAttempts >= 48) {
            showToast('Still pending', 'Payment is still pending. You can retry or wait and refresh.', 'warning');
            setHotspotFlow('awaiting_confirmation', 'Still waiting for confirmation. Keep this screen open.');
            setHotspotState('Payment still pending after repeated checks.');
            return;
        }

        state.payPollTimer = setTimeout(poll, 5000);
    };

    poll();
}

async function submitMetered(event) {
    event.preventDefault();

    if (!portalMeta.mac) {
        showToast('Device MAC missing', 'Open this page from the captive portal login to continue.', 'error');
        return;
    }

    const username = (document.getElementById('meteredUsername')?.value || '').trim();
    const password = (document.getElementById('meteredPassword')?.value || '').trim();
    if (!username || !password) {
        showToast('Credentials required', 'Enter username and password.', 'warning');
        return;
    }

    const button = document.getElementById('meteredConnectBtn');
    const status = document.getElementById('meteredStatus');
    if (button) button.disabled = true;
    if (status) status.textContent = 'Checking account details and connecting...';

    try {
        const res = await fetch(portalMeta.meteredUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                username,
                password,
                mac: portalMeta.mac,
                ip: portalMeta.ip,
            }),
        });

        const data = await readJsonResponse(res);
        if (!res.ok || data?.ok === false) {
            showToast('Metered login failed', data?.message || 'Could not connect.', 'error');
            if (status) status.textContent = data?.message || 'Connection failed.';
            return;
        }

        showToast('Metered connected', data?.message || 'Connection successful.', 'success');
        if (status) status.textContent = 'Connected. Redirecting...';

        const statusUrl = data?.data?.status_url;
        if (statusUrl) {
            window.location.href = statusUrl;
        }
    } catch (error) {
        showToast('Network error', error?.message || 'Connection request failed.', 'error');
        if (status) status.textContent = 'Network error.';
    } finally {
        if (button) button.disabled = false;
    }
}

function initPortalAds() {
    const root = document.getElementById('portalAds');
    if (!root) {
        return;
    }

    const slides = Array.from(root.querySelectorAll('.ad-slide'));
    const dotsWrap = document.getElementById('portalAdsDots');
    if (!slides.length || !dotsWrap) {
        return;
    }

    let activeIndex = Math.max(0, slides.findIndex((slide) => slide.classList.contains('active')));
    if (activeIndex < 0) {
        activeIndex = 0;
    }

    let timerId = null;

    const syncVideoPlayback = () => {
        slides.forEach((slide, idx) => {
            const video = slide.querySelector('video');
            if (!video) {
                return;
            }
            if (idx === activeIndex) {
                video.currentTime = 0;
                video.play().catch(() => {});
            } else {
                video.pause();
            }
        });
    };

    const setActive = (index) => {
        activeIndex = ((index % slides.length) + slides.length) % slides.length;
        slides.forEach((slide, idx) => {
            slide.classList.toggle('active', idx === activeIndex);
        });

        Array.from(dotsWrap.querySelectorAll('.ads-dot')).forEach((dot, idx) => {
            dot.classList.toggle('active', idx === activeIndex);
        });

        syncVideoPlayback();
    };

    slides.forEach((_, idx) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = `ads-dot${idx === activeIndex ? ' active' : ''}`;
        dot.setAttribute('aria-label', `Show ad ${idx + 1}`);
        dot.addEventListener('click', () => {
            setActive(idx);
            restartRotation();
        });
        dotsWrap.appendChild(dot);
    });

    const startRotation = () => {
        if (slides.length <= 1) {
            return;
        }
        timerId = window.setInterval(() => setActive(activeIndex + 1), 5500);
    };

    const stopRotation = () => {
        if (timerId) {
            window.clearInterval(timerId);
            timerId = null;
        }
    };

    const restartRotation = () => {
        stopRotation();
        startRotation();
    };

    root.addEventListener('mouseenter', stopRotation);
    root.addEventListener('mouseleave', startRotation);

    setActive(activeIndex);
    startRotation();
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
        openHotspotPayModal(plan);
    });
});

document.getElementById('hotspotPayBtn')?.addEventListener('click', requestHotspotPayment);
document.getElementById('hotspotFlowCloseBtn')?.addEventListener('click', dismissHotspotFlowPopup);
document.getElementById('closeHotspotModal')?.addEventListener('click', closeHotspotPayModal);
document.getElementById('cancelHotspotPayBtn')?.addEventListener('click', closeHotspotPayModal);
document.getElementById('hotspotPayModal')?.addEventListener('click', (event) => {
    if (state.payFlowBlocking) return;
    if (event.target?.id === 'hotspotPayModal') {
        closeHotspotPayModal();
    }
});
document.addEventListener('keydown', (event) => {
    if (state.payFlowBlocking) return;
    if (event.key === 'Escape') {
        closeHotspotPayModal();
    }
});
document.getElementById('meteredForm')?.addEventListener('submit', submitMetered);
initPortalAds();
</script>
</body>
</html>
