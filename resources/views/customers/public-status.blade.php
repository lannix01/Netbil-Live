<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Status - {{ $customer->username ?? $customer->name }}</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f6f8fb; color: #111827; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 20px 14px 40px; }
        .head { margin-bottom: 12px; }
        .head h1 { margin: 0 0 4px; font-size: 1.35rem; }
        .muted { color: #6b7280; font-size: .9rem; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; }
        .k { color: #6b7280; font-size: .76rem; text-transform: uppercase; margin-bottom: 5px; }
        .v { font-weight: 700; font-size: 1rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #f0f2f5; text-align: left; font-size: .9rem; }
        th { font-size: .76rem; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; background: #fafbfc; }
        .btn { display: inline-block; padding: 7px 11px; font-size: .82rem; border-radius: 7px; text-decoration: none; border: 1px solid #d1d5db; color: #111827; background: #fff; }
        .btn.primary { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .section { margin-top: 14px; }
        @media (max-width: 900px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 520px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <h1>Account Status</h1>
        <div class="muted">
            {{ $customer->name ?: $customer->username }} ({{ $customer->username }}) |
            {{ $customer->phone ?: 'No phone' }} |
            Status: {{ ucfirst($customer->status ?? 'active') }}
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <div class="k">Total Billed</div>
            <div class="v">KES {{ number_format((float)($summary['total_billed'] ?? 0), 2) }}</div>
        </div>
        <div class="card">
            <div class="k">Total Paid</div>
            <div class="v">KES {{ number_format((float)($summary['total_paid'] ?? 0), 2) }}</div>
        </div>
        <div class="card">
            <div class="k">Current Due</div>
            <div class="v">KES {{ number_format((float)($summary['total_due'] ?? 0), 2) }}</div>
        </div>
        <div class="card">
            <div class="k">Open Invoices</div>
            <div class="v">{{ number_format((int)($summary['open_invoices'] ?? 0)) }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <div class="k">Assigned Plan</div>
            <div class="v">{{ $package?->name ?? 'Not assigned' }}</div>
            <div class="muted">{{ ucfirst($package?->category ?? 'hotspot') }}</div>
        </div>
        <div class="card">
            <div class="k">Profile</div>
            <div class="v">{{ $package?->mk_profile ?? $package?->mikrotik_profile ?? 'default' }}</div>
        </div>
        <div class="card">
            <div class="k">Rate</div>
            @php $isHotspotRate = strtolower((string)($rateMode ?? 'metered')) === 'hotspot'; @endphp
            <div class="v">KES {{ number_format((float)$ratePerMb, 2) }} {{ $isHotspotRate ? '/ package' : '/ MB' }}</div>
            <div class="muted">
                {{ $isHotspotRate
                    ? 'Hotspot bills a flat package amount for configured duration/data limits.'
                    : 'Metered billing uses per-MB pricing against measured usage.' }}
            </div>
        </div>
        <div class="card">
            <div class="k">Billing Dates</div>
            <div class="v">Last: {{ $summary['latest_billing_date'] ?: 'N/A' }}</div>
            <div class="muted">Next due: {{ $summary['next_due_date'] ?: 'N/A' }}</div>
        </div>
    </div>

    <div class="section">
        <h3>Open Invoices</h3>
        <table>
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Balance</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($dueInvoices as $inv)
                @php
                    $status = $inv->invoice_status ?? $inv->status ?? 'unpaid';
                    $balance = (float)($inv->balance_amount ?? ($inv->total_amount ?? $inv->amount ?? 0));
                    $payToken = $inv->public_token ?? $inv->invoice_number;
                    $payUrl = '/pay/invoices/' . rawurlencode((string)$payToken);
                @endphp
                <tr>
                    <td>{{ $inv->invoice_number }}</td>
                    <td>{{ ucfirst($status) }}</td>
                    <td>{{ optional($inv->due_date)->format('Y-m-d') ?: 'N/A' }}</td>
                    <td>KES {{ number_format($balance, 2) }}</td>
                    <td><a href="{{ $payUrl }}" class="btn primary">Pay Now</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No open invoices.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Recent Billing History</h3>
        <table>
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Created</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @forelse($invoices as $inv)
                @php
                    $total = (float)($inv->total_amount ?? $inv->amount ?? 0);
                    $paid = (float)($inv->paid_amount ?? 0);
                    $balance = (float)($inv->balance_amount ?? max(0, $total - $paid));
                    $status = $inv->invoice_status ?? $inv->status ?? 'unpaid';
                @endphp
                <tr>
                    <td>{{ $inv->invoice_number }}</td>
                    <td>{{ optional($inv->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
                    <td>KES {{ number_format($total, 2) }}</td>
                    <td>KES {{ number_format($paid, 2) }}</td>
                    <td>KES {{ number_format($balance, 2) }}</td>
                    <td>{{ ucfirst($status) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No invoices found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
