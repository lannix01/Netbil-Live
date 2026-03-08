<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Payments • Netbil</title>
    <style>
        body{background:#f5f7fa;font-family:Inter,system-ui,Arial,sans-serif;margin:0;color:#101828}
        .wrap{max-width:1100px;margin:30px auto;padding:0 16px}
        .top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
        .title{font-size:20px;font-weight:900;margin:0}
        .sub{color:#667085;font-size:13px;margin:4px 0 0}
        .card{background:#fff;border:1px solid #e7e9f2;border-radius:16px;padding:16px;box-shadow:0 10px 30px rgba(16,24,40,.06);margin-top:14px}
        .grid{display:grid;grid-template-columns:1.1fr .9fr;gap:14px}
        @media (max-width:900px){.grid{grid-template-columns:1fr}}
        label{display:block;font-size:12px;font-weight:800;color:#344054;margin-bottom:6px}
        input{width:100%;padding:11px 12px;border-radius:12px;border:1px solid #d0d5dd;outline:none}
        input:focus{border-color:#111827;box-shadow:0 0 0 4px rgba(17,24,39,.08)}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        @media (max-width:650px){.row{grid-template-columns:1fr}}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:#111827;color:#fff;text-decoration:none;border:none;border-radius:12px;padding:11px 14px;font-weight:900;cursor:pointer}
        .btn2{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:#fff;color:#111827;text-decoration:none;border:1px solid #d0d5dd;border-radius:12px;padding:11px 14px;font-weight:900;cursor:pointer}
        .muted{color:#667085;font-size:12px}
        .pill{display:inline-block;padding:5px 10px;border-radius:999px;font-weight:900;font-size:12px;border:1px solid #e7e9f2;background:#f9fafb}
        .hint{background:#fefbe8;border:1px solid #f8e6a0;color:#7a5d00;padding:10px 12px;border-radius:12px;font-size:12px}
        .stack{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
        .sep{height:1px;background:#eef2f6;margin:12px 0}
        code{background:#111827;color:#e5e7eb;padding:2px 6px;border-radius:8px}
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div>
            <h1 class="title">Payments</h1>
            <div class="sub">This page will become the “Enter details → STK Push / Paystack” hub.</div>
        </div>
        <span class="pill">/payments</span>
    </div>

    <div class="card">
        <div class="grid">
            <div>
                <div class="hint">
                    ⚙️ Placeholder UI for now. Next we’ll replace this with the STK Push details form + validation + live status.
                </div>

                <div class="sep"></div>

                <form method="GET" action="{{ route('paystack.start') }}">
                    <div class="row">
                        <div>
                            <label>Email</label>
                            <input name="email" type="email" placeholder="customer@example.com" required>
                            <div class="muted" style="margin-top:6px">Paystack requires an email for initialize.</div>
                        </div>
                        <div>
                            <label>Amount</label>
                            <input name="amount" type="number" step="0.01" min="1" placeholder="e.g. 100" required>
                            <div class="muted" style="margin-top:6px">Entered in major units; we convert to kobo internally.</div>
                        </div>
                    </div>

                    <div class="row" style="margin-top:10px">
                        <div>
                            <label>Purpose</label>
                            <input name="purpose" type="text" value="topup">
                        </div>
                        <div>
                            <label>Item Code</label>
                            <input name="item_code" type="text" placeholder="e.g. NETBIL_TOPUP">
                        </div>
                    </div>

                    <div class="stack">
                        <button class="btn" type="submit">Pay with Paystack →</button>
                        <a class="btn2" href="/">Back to home</a>
                    </div>

                    <div class="muted" style="margin-top:10px">
                        Route: <code>{{ route('paystack.start') }}</code>
                    </div>
                </form>
            </div>

            <div>
                <div class="card" style="margin-top:0">
                    <h3 style="margin:0 0 8px;font-size:14px;font-weight:900">Coming next</h3>
                    <div class="muted" style="line-height:1.7">
                        • Enter phone number<br>
                        • Choose package / amount<br>
                        • “Send STK Push” (M-Pesa) OR Paystack card/bank<br>
                        • Live status updates + receipt<br>
                        • Then Netbil provisions Mikrotik automatically ⚡
                    </div>

                    <div class="sep"></div>

                    <div class="muted">
                        Debug note: Paystack callback is <code>/payment/success</code> (singular).  
                        This UI hub is <code>/payments</code> (plural).
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</body>
</html>
