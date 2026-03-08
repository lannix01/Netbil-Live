<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family: DejaVu Sans, sans-serif; font-size:11px;}
        h2{margin:0 0 8px;}
        .muted{color:#666;font-size:10px;margin-bottom:10px;}
        table{width:100%;border-collapse:collapse;}
        th,td{border:1px solid #ddd;padding:6px;text-align:left;}
        th{background:#f3f3f3;}
        .right{text-align:right;}
        .total{font-weight:700;}
    </style>
</head>
<body>
    <h2>Bike Spendings Report</h2>
    <div class="muted">
        Range: {{ $from ?? 'All' }} to {{ $to ?? 'All' }}
        @if($sub) | Subtype: {{ strtoupper($sub) }} @endif
        | Generated: {{ now()->format('Y-m-d H:i') }}
    </div>

    <table>
        <thead>
        <tr>
            <th>Date</th>
            <th>Subtype</th>
            <th>Reference</th>
            <th>Description</th>
            <th>Particulars</th>
            <th class="right">Amount</th>
            <th>Batch</th>
        </tr>
        </thead>
        <tbody>
        @foreach($spendings as $s)
            <tr>
                <td>{{ $s->date?->format('Y-m-d') }}</td>
                <td>{{ strtoupper($s->sub_type ?? '-') }}</td>
                <td>{{ $s->reference }}</td>
                <td>{{ $s->description }}</td>
                <td>{{ $s->particulars }}</td>
                <td class="right">{{ number_format((float)$s->amount, 2) }}</td>
                <td>{{ $s->batch_id }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="5" class="total right">TOTAL</td>
            <td class="total right">{{ number_format((float)$total, 2) }}</td>
            <td></td>
        </tr>
        </tbody>
    </table>
</body>
</html>
