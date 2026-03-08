<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family: DejaVu Sans, sans-serif; font-size:12px;}
        h2{margin:0 0 8px;}
        .muted{color:#666;font-size:11px;margin-bottom:10px;}
        table{width:100%;border-collapse:collapse;}
        th,td{border:1px solid #ddd;padding:8px;text-align:left;}
        th{background:#f3f3f3;}
        .right{text-align:right;}
        .total{font-weight:700;}
    </style>
</head>
<body>
    <h2>Hostel Payments: {{ $hostel->hostel_name }}</h2>
    <div class="muted">
        Meter: {{ $hostel->meter_no }} | Phone: {{ $hostel->phone_no }} |
        Generated: {{ $generatedAt }}
    </div>

    <table>
        <thead>
        <tr>
            <th>Date</th>
            <th>Reference</th>
            <th>Receiver</th>
            <th>Notes</th>
            <th class="right">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach($payments as $p)
            <tr>
                <td>{{ $p->date?->format('Y-m-d') }}</td>
                <td>{{ $p->reference }}</td>
                <td>{{ $p->receiver_name }} {{ $p->receiver_phone ? '('.$p->receiver_phone.')' : '' }}</td>
                <td>{{ $p->notes }}</td>
                <td class="right">{{ number_format((float)$p->amount, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="4" class="total right">TOTAL</td>
            <td class="total right">{{ number_format((float)$total, 2) }}</td>
        </tr>
        </tbody>
    </table>
</body>
</html>
