@php
    use Carbon\Carbon;

    $calc = !empty($calc);

    $sumByTypeAmount = [];
    $sumByTypeFee = [];
    $sumByTypeTotal = [];

    $grandAmount = 0.0;
    $grandFee = 0.0;
    $grandTotal = 0.0;

    $normType = function($t) {
        $t = strtolower(trim((string)$t));
        return $t === '' ? 'other' : $t;
    };

    $fmtDate = function($v, $format = 'Y-m-d') {
        if (empty($v)) return '-';
        try {
            if ($v instanceof \DateTimeInterface) return $v->format($format);
            return Carbon::parse($v)->format($format);
        } catch (\Throwable $e) {
            return (string)$v;
        }
    };

    $plateFromSpending = function($s) {
        $plate = data_get($s, 'plate_no')
            ?? data_get($s, 'bike.plate_no')
            ?? data_get($s, 'bike.plate')
            ?? data_get($s, 'bike.plate_number')
            ?? data_get($s, 'vehicle.plate_no')
            ?? data_get($s, 'vehicle.plate')
            ?? data_get($s, 'vehicle.plate_number')
            ?? data_get($s, 'asset.plate_no')
            ?? data_get($s, 'asset.plate')
            ?? data_get($s, 'asset.plate_number');

        if (!empty($plate)) return $plate;

        $hay = implode(' ', array_filter([
            (string) data_get($s, 'description'),
            (string) data_get($s, 'reference'),
            (string) data_get($s, 'sub_type'),
            (string) data_get($s, 'type'),
        ]));

        if (preg_match('/\b([A-Z]{2,3}\s?\d{3,4}[A-Z]?)\b/i', $hay, $m)) {
            return strtoupper(str_replace(' ', '', $m[1]));
        }

        return null;
    };

    foreach ($spendings as $s) {
        $fee = (float) data_get($s, 'transaction_cost', 0);
        $amt = (float) data_get($s, 'amount', 0);
        $total = $amt + $fee;

        $t = $normType(data_get($s, 'type', 'other'));

        $sumByTypeAmount[$t] = ($sumByTypeAmount[$t] ?? 0) + $amt;
        $sumByTypeFee[$t]    = ($sumByTypeFee[$t] ?? 0) + $fee;
        $sumByTypeTotal[$t]  = ($sumByTypeTotal[$t] ?? 0) + $total;

        $grandAmount += $amt;
        $grandFee    += $fee;
        $grandTotal  += $total;
    }

    $preferredOrder = ['bike','fuel','meal','token','other'];
    $allTypes = array_unique(array_merge($preferredOrder, array_keys($sumByTypeTotal)));
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Spendings Ledger</title>
    <style>
        @page { margin: 8mm; }

        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color:#111;
            margin:0;
        }

        .header{
            border:1px solid #e5e7eb;
            border-radius:10px;
            padding:10px 12px;
            background:#fafafa;
        }
        .header .top{display:flex; justify-content:space-between; gap:12px; align-items:flex-start}
        .header h1{margin:0; font-size:14px; letter-spacing:.02em}
        .meta{font-size:9px; color:#666; text-align:right}
        .filters{margin-top:6px; font-size:9px; color:#333}
        .filters b{color:#111}

        table.data{
            width:100%;
            border-collapse:collapse;
            margin-top:10px;
            table-layout:fixed;
        }
        table.data th, table.data td{
            border:1px solid #e5e7eb;
            padding:3px 4px; /* tighter */
            vertical-align:top;
            word-wrap:break-word;
            overflow-wrap:break-word;
            word-break:break-word;
            line-height:1.15;
        }
        table.data th{
            background:#f3f4f6;
            font-size:8.8px;
            text-transform:uppercase;
            letter-spacing:.04em;
        }

        .num{text-align:right; white-space:nowrap}
        .badge{
            display:inline-block;
            padding:1px 5px;
            border-radius:5px;
            background:#eef2ff;
            font-size:8.5px;
            border:1px solid #dbe3ff;
        }
        .small{font-size:8.8px;color:#666;margin-top:1px}

        .totals{
            margin-top:10px;
            border:1px solid #e5e7eb;
            border-radius:10px;
            padding:8px 10px;
        }
        .totals h3{margin:0 0 6px 0; font-size:10.5px}
        table.sum{width:100%; border-collapse:collapse}
        table.sum th, table.sum td{padding:4px; border-top:1px solid #eee; font-size:9.5px}
        table.sum th{text-align:left; background:#fafafa}
        table.sum td.num{text-align:right}
        .grandRow td{font-weight:800; border-top:2px solid #ddd}
    </style>
</head>
<body>

    <div class="header">
        <div class="top">
            <div>
                <h1>PETTY CASH SPENDING</h1>
                <div class="filters">
                    <b>Dated:</b> From: {{ $from ?: '-' }} | To: {{ $to ?: '-' }}
                </div>
            </div>
            <div class="meta">
                <div><b>Generated:</b> {{ now()->format('Y-m-d H:i') }}</div>
                <div><b>Records:</b> {{ $spendings->count() }}</div>
            </div>
        </div>

        <div class="filters">
            Batch: <b>{{ $batchId ?: 'All' }}</b> |
            Category: <b>{{ $type ?: 'All' }}</b> |
            Search: <b>{{ $q ?: '-' }}</b>
        </div>
    </div>

    <table class="data">
        <thead>
        <tr>
            <th style="width:60px">Date</th>
            <th style="width:80px">Reference</th>
            <th style="width:50px">Category</th>
            <th>Description</th>
            <th style="width:35px">Batch</th>
            <th style="width:50px" class="num">Amount</th>
            <th style="width:40px" class="num">Fee</th>
            <th style="width:50px" class="num">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($spendings as $s)
            @php
                $fee = (float) data_get($s, 'transaction_cost', 0);
                $amt = (float) data_get($s, 'amount', 0);
                $total = $amt + $fee;

                $typeRaw = (string) data_get($s, 'type', '');
                $t = strtolower($typeRaw);
                $sub = strtolower((string) data_get($s, 'sub_type', ''));

                $dateVal = data_get($s, 'date') ?? data_get($s, 'service_date');
                $batchNo = data_get($s, 'batch_no') ?? data_get($s, 'batch.batch_no') ?? '-';


                $meterNo = trim((string) data_get($s, 'meter_no', ''));
                $descRaw = trim((string) data_get($s, 'description', ''));

                // Plate for bike/fuel/service
                $plate = $plateFromSpending($s);

                // ✅ Token formatting: "Token payment: HOSTEL NAME - METERNO"
                if ($t === 'token') {
                    // Prefer "Token payment: ..." and just append meter, without duplicating
                    $base = $descRaw !== '' ? $descRaw : 'Token payment';
                    $desc = $base;

                    if ($meterNo !== '') {
                        // prevent double meter if already present in text
                        if (stripos($desc, $meterNo) === false) {
                            $desc .= ' - ' . $meterNo;
                        }
                    }
                } else {
                    $desc = $descRaw;
                }

                // Fallbacks for empty descriptions on bike/fuel
                if ($desc === '' && in_array($t, ['bike','fuel'], true)) {
                    $desc = $plate ? ("Plate: " . $plate) : '-';
                }
                if ($desc === '') $desc = '-';

                // Always show plate inline (compact) for bike/fuel/service if exists and not already in desc
                $shouldShowPlateInline = !empty($plate) && ($t === 'bike' || $t === 'fuel' || $sub === 'service')
                    && stripos($desc, 'plate:') === false
                    && stripos($desc, $plate) === false;

                if ($shouldShowPlateInline) {
                    $desc .= " | Plate: {$plate}";
                }
            @endphp

            <tr>
                <td>{{ $fmtDate($dateVal, 'Y-m-d') }}</td>
                <td>{{ data_get($s, 'reference', '-') ?: '-' }}</td>
                <td><span class="badge">{{ strtoupper($typeRaw ?: 'N/A') }}</span></td>
                <td>{{ $desc }}</td>
                <td>{{ $batchNo }}</td>
                <td class="num">{{ number_format($amt, 2) }}</td>
                <td class="num">{{ number_format($fee, 2) }}</td>
                <td class="num"><b>{{ number_format($total, 2) }}</b></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <h3>Totals Summary</h3>
        <table class="sum">
            <thead>
            <tr>
                <th>Category</th>
                <th class="num">Amount</th>
                <th class="num">Fee</th>
                <th class="num">Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($allTypes as $t)
                @php
                    $exists = array_key_exists($t, $sumByTypeTotal);
                    $a = (float)($sumByTypeAmount[$t] ?? 0);
                    $f = (float)($sumByTypeFee[$t] ?? 0);
                    $tt = (float)($sumByTypeTotal[$t] ?? 0);
                @endphp
                @if($exists)
                    <tr>
                        <td>{{ strtoupper($t) }}</td>
                        <td class="num">{{ number_format($a, 2) }}</td>
                        <td class="num">{{ number_format($f, 2) }}</td>
                        <td class="num"><b>{{ number_format($tt, 2) }}</b></td>
                    </tr>
                @endif
            @endforeach

            <tr class="grandRow">
                <td>GRAND TOTAL</td>
                <td class="num">{{ number_format($grandAmount, 2) }}</td>
                <td class="num">{{ number_format($grandFee, 2) }}</td>
                <td class="num">{{ number_format($grandTotal, 2) }}</td>
            </tr>
            </tbody>
        </table>

        <div class="small" style="margin-top:6px">
            THIS IS A SYSTEM GENERATED RECEIPT AND DOES NOT REQUIRE A SIGNATURE.
        </div>
    </div>

</body>
</html>
