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
    <h2>Meals (Lunch) Report</h2>
    <div class="muted">
        Range: {{ $from ?? 'All' }} to {{ $to ?? 'All' }}
        @if($batchId) | Batch ID: {{ $batchId }} @endif
        | Generated: {{ now()->format('Y-m-d H:i') }}
    </div>

    <table>
        <thead>
        <tr>
            <th>Date</th>
            <th>Reference</th>
            <th>Description</th>
            <th class="right">Amount</th>
            <th>Batch</th>
        </tr>
        </thead>
        <tbody>
        @foreach($meals as $m)
            <tr>
                <td>{{ $m->date?->format('Y-m-d') }}</td>
                <td>{{ $m->reference }}</td>
                <td>{{ $m->description }}</td>
                <td class="right">{{ number_format((float)$m->amount, 2) }}</td>
                <td>{{ $m->batch_id }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="3" class="total right">TOTAL</td>
            <td class="total right">{{ number_format((float)$total, 2) }}</td>
            <td></td>
        </tr>
        </tbody>
    </table>
</body>
</html>
