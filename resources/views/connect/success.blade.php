<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Confirmed</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at 15% 20%, rgba(22, 163, 74, 0.16), transparent 40%),
                        linear-gradient(145deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #0f172a;
        }

        .card {
            width: 100%;
            max-width: 520px;
            border-radius: 16px;
            border: 1px solid #dbe2ef;
            background: #fff;
            box-shadow: 0 22px 44px rgba(15, 23, 42, 0.16);
            overflow: hidden;
        }

        .head {
            padding: 20px;
            color: #fff;
            background: linear-gradient(135deg, #16a34a, #15803d);
        }

        .head h1 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
        }

        .head p {
            margin: 8px 0 0;
            opacity: 0.92;
            font-size: 0.92rem;
        }

        .body {
            padding: 18px 20px 20px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            font-size: 0.92rem;
        }

        .label {
            color: #64748b;
            font-weight: 700;
        }

        .value {
            text-align: right;
            font-weight: 700;
            color: #0f172a;
        }

        .hint {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            font-size: 0.88rem;
        }

        .actions {
            margin-top: 14px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: 0;
            border-radius: 10px;
            padding: 10px 13px;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
        }

        .btn-muted {
            background: #e2e8f0;
            color: #0f172a;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="head">
        <h1>Payment Successful</h1>
        <p>Your hotspot session is active.</p>
    </div>
    <div class="body">
        <div class="row">
            <span class="label">Package</span>
            <span class="value">{{ $package->name ?? 'N/A' }}</span>
        </div>
        <div class="row">
            <span class="label">Amount</span>
            <span class="value">KES {{ number_format((float)($package->price ?? 0), 2) }}</span>
        </div>
        <div class="row">
            <span class="label">Mode</span>
            <span class="value">{{ strtoupper((string)($mode ?? 'hotspot')) }}</span>
        </div>
        <div class="row">
            <span class="label">Started</span>
            <span class="value">{{ optional($connection->started_at)->toDateTimeString() ?: '-' }}</span>
        </div>
        <div class="row">
            <span class="label">Expires</span>
            <span class="value">
                @if(($mode ?? 'hotspot') === 'metered')
                    Billing-cycle controlled
                @else
                    {{ optional($connection->expires_at)->toDateTimeString() ?: '-' }}
                @endif
            </span>
        </div>

        <div class="hint" id="closeHint">This page will close in 8s.</div>

        <div class="actions">
            <a class="btn btn-primary" href="{{ $statusUrl }}">Back to my account</a>
            <button class="btn btn-muted" type="button" id="closeNowBtn">Close now</button>
        </div>
    </div>
</div>

<script>
(function () {
    const closeHint = document.getElementById('closeHint');
    const closeNowBtn = document.getElementById('closeNowBtn');
    let left = 8;

    function tryCloseWindow() {
        window.close();
        setTimeout(() => {
            if (closeHint) {
                closeHint.textContent = 'Auto-close blocked by browser. You can close this tab manually.';
            }
        }, 450);
    }

    if (closeNowBtn) {
        closeNowBtn.addEventListener('click', tryCloseWindow);
    }

    const timer = setInterval(() => {
        left -= 1;
        if (left <= 0) {
            clearInterval(timer);
            tryCloseWindow();
            return;
        }
        if (closeHint) {
            closeHint.textContent = `This page will close in ${left}s.`;
        }
    }, 1000);
})();
</script>
</body>
</html>
