<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 24px; }
        .wrap { max-width: 860px; margin: 0 auto; }
        .head { display:flex; justify-content:space-between; margin-bottom: 18px; }
        .card { border:1px solid #dbe1ea; border-radius:10px; padding:14px; margin-bottom:14px; }
        .k { font-size: 12px; color: #475569; text-transform: uppercase; }
        .v { font-size: 14px; font-weight: 700; margin-top: 4px; }
        table { width:100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border:1px solid #dbe1ea; padding:8px; font-size: 13px; text-align:left; }
        th { background: #f8fafc; }
        .totals { width: 320px; margin-left:auto; }
        .status { font-weight: 700; text-transform: uppercase; }
        .muted { color:#64748b; font-size:12px; }
        @media print {
            .no-print { display:none; }
            body { margin:0; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="head">
            <div>
                <h2 style="margin:0;">Invoice</h2>
                <div class="muted">{{ $invoice->invoice_number }}</div>
            </div>
            <button class="no-print" onclick="window.print()">Print</button>
        </div>

        <div class="card">
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
                <div><div class="k">Customer</div><div class="v">{{ $customer?->name ?? 'N/A' }}</div></div>
                <div><div class="k">Phone</div><div class="v">{{ $customer?->phone ?? '-' }}</div></div>
                <div><div class="k">Email</div><div class="v">{{ $customer?->email ?? '-' }}</div></div>
                <div><div class="k">Issued</div><div class="v">{{ optional($invoice->issued_at)->format('Y-m-d') ?? optional($invoice->created_at)->format('Y-m-d') }}</div></div>
                <div><div class="k">Due Date</div><div class="v">{{ optional($invoice->due_date)->format('Y-m-d') ?? '-' }}</div></div>
                <div><div class="k">Status</div><div class="v status">{{ $invoice->invoice_status ?? $invoice->status }}</div></div>
            </div>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Data Usage Charges</td>
                        <td>{{ $invoice->currency ?? 'KES' }} {{ number_format((float)($invoice->subtotal_amount ?: $invoice->amount), 2) }}</td>
                    </tr>
                    <tr>
                        <td>Tax ({{ number_format((float)$invoice->tax_percent, 2) }}%)</td>
                        <td>{{ $invoice->currency ?? 'KES' }} {{ number_format((float)$invoice->tax_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Penalty ({{ number_format((float)$invoice->penalty_percent, 2) }}%)</td>
                        <td>{{ $invoice->currency ?? 'KES' }} {{ number_format((float)$invoice->penalty_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card totals">
            <table>
                <tr><th>Subtotal</th><td>{{ $invoice->currency ?? 'KES' }} {{ number_format((float)($invoice->subtotal_amount ?: $invoice->amount), 2) }}</td></tr>
                <tr><th>Total</th><td>{{ $invoice->currency ?? 'KES' }} {{ number_format((float)($invoice->total_amount ?: $invoice->amount), 2) }}</td></tr>
                <tr><th>Paid</th><td>{{ $invoice->currency ?? 'KES' }} {{ number_format((float)$invoice->paid_amount, 2) }}</td></tr>
                <tr><th>Balance</th><td>{{ $invoice->currency ?? 'KES' }} {{ number_format((float)$invoice->balance_amount, 2) }}</td></tr>
            </table>
        </div>

        @if(!empty($invoice->notes))
            <div class="card">
                <div class="k">Notes</div>
                <div class="v" style="font-weight:500;">{{ $invoice->notes }}</div>
            </div>
        @endif

        <div class="muted" style="margin-top: 14px;">
            Public payment link: {{ $publicUrl }}
        </div>
    </div>
</body>
</html>

