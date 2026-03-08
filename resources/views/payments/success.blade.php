<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Payment Status</title>
    <style>
        body{background:#f5f7fa;font-family:Inter,system-ui,Arial,sans-serif;margin:0}
        .wrap{max-width:900px;margin:40px auto;padding:0 16px}
        .card{background:#fff;border:1px solid #e7e9f2;border-radius:16px;padding:18px;box-shadow:0 10px 30px rgba(16,24,40,.06)}
        .title{font-size:18px;font-weight:800;margin:0 0 6px}
        .muted{color:#667085;font-size:13px}
        .pill{display:inline-block;padding:6px 10px;border-radius:999px;font-weight:800;font-size:12px}
        .ok{background:#ecfdf3;color:#027a48;border:1px solid #abefc6}
        .bad{background:#fef3f2;color:#b42318;border:1px solid #fecdca}
        .row{display:flex;gap:12px;flex-wrap:wrap;margin-top:12px}
        .box{flex:1;min-width:240px;background:#f9fafb;border:1px solid #eef2f6;border-radius:12px;padding:12px}
        pre{white-space:pre-wrap;background:#111827;color:#e5e7eb;padding:12px;border-radius:12px;overflow:auto}
        a.btn{display:inline-block;margin-top:12px;background:#111827;color:#fff;text-decoration:none;padding:10px 12px;border-radius:12px;font-weight:800}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
            <div>
                <h1 class="title">Netbil Payment</h1>
                <div class="muted">Reference: <strong>{{ $reference ?? '-' }}</strong></div>
            </div>
            @if(($ok ?? false) === true)
                <span class="pill ok">SUCCESS</span>
            @else
                <span class="pill bad">NOT CONFIRMED</span>
            @endif
        </div>

        <p class="muted" style="margin-top:10px">{{ $message ?? '' }}</p>

        @if(isset($payment))
            <div class="row">
                <div class="box">
                    <div class="muted">Amount (kobo)</div>
                    <div style="font-size:18px;font-weight:900">{{ $payment->amount }}</div>
                </div>
                <div class="box">
                    <div class="muted">Status</div>
                    <div style="font-size:18px;font-weight:900">{{ strtoupper($payment->status) }}</div>
                </div>
                <div class="box">
                    <div class="muted">Email</div>
                    <div style="font-size:14px;font-weight:800">{{ $payment->customer_email ?? '-' }}</div>
                </div>
            </div>
        @endif

        @if(isset($raw))
            <h3 style="margin-top:14px;font-size:14px">Debug</h3>
            <pre>{{ json_encode($raw, JSON_PRETTY_PRINT) }}</pre>
        @endif

        <a class="btn" href="/">Back to dashboard</a>
    </div>
</div>
</body>
</html>
