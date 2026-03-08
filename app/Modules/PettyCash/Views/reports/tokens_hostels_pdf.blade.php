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
    </style>
</head>
<body>
    <h2>Token Hostels Report</h2>
    <div class="muted">Generated: {{ $generatedAt }}</div>

    <table>
        <thead>
        <tr>
            <th>Hostel</th>
            <th>Meter</th>
            <th>Phone</th>
            <th>Routers</th>
            <th>Stake</th>
            <th>Amount Due</th>
            <th>Last Payment</th>
        </tr>
        </thead>
        <tbody>
        @foreach($hostels as $h)
            <tr>
                <td>{{ $h->hostel_name }}</td>
                <td>{{ $h->meter_no }}</td>
                <td>{{ $h->phone_no }}</td>
                <td>{{ $h->no_of_routers }}</td>
                <td>{{ strtoupper($h->stake) }}</td>
                <td>{{ number_format((float)$h->amount_due, 2) }}</td>
                <td>
                    @if($h->last_payment_amount !== null)
                        {{ number_format((float)$h->last_payment_amount, 2) }} on {{ $h->last_payment_date }}
                    @else
                        -
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
