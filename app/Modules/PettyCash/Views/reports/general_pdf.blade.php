<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:DejaVu Sans, sans-serif;font-size:11px;color:#101828;margin:0}
        .muted{color:#667085;font-size:10px}
        .tiny{color:#667085;font-size:9px}
        .badge{display:inline-block;padding:3px 9px;border-radius:999px;background:#f2f4f7;font-size:10px}
        .hr{height:1px;background:#e5e7eb;margin:12px 0}
        .right{text-align:right}
        .center{text-align:center}
        .strong{font-weight:800}
        .pagebreak{page-break-before:always}

        /* Header */
        .header{
            padding:16px 16px 10px;
            border-bottom:1px solid #e5e7eb;
        }
        .title{font-size:16px;font-weight:900;margin:0}
        .subline{margin-top:6px}

        /* Summary cards */
        .cards{display:table;width:100%;margin:12px 0 8px}
        .card{display:table-cell;width:25%;padding:10px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;vertical-align:top}
        .card + .card{border-left:0}
        .card .k{font-size:10px;color:#667085;margin-bottom:6px}
        .card .v{font-size:13px;font-weight:900}
        .card .s{font-size:9px;color:#667085;margin-top:4px}

        /* Tables */
        table{width:100%;border-collapse:collapse;margin:10px 0 14px}
        th,td{border:1px solid #e5e7eb;padding:6px;vertical-align:top}
        th{background:#f8fafc;font-size:10px;text-transform:uppercase;letter-spacing:.3px;color:#475467}
        .striped tbody tr:nth-child(even){background:#fcfcfd}
        .total-row td{background:#f8fafc;font-weight:900}
        .smallhead{font-size:12px;font-weight:900;margin:14px 0 6px}
        .note{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:8px;border-radius:10px;margin:10px 0}
    </style>
</head>
<body>
@php
    $m = fn($v) => number_format((float)$v, 2);

    $viewMode = $viewMode ?? request('view', 'combined');

    $batchLabel = (!empty($batchIds) && is_array($batchIds) && count($batchIds))
        ? implode(', ', $batchIds)
        : 'All';

    // Group spendings for split view
    $groups = [
        'bike:fuel' => [],
        'bike:maintenance' => [],
        'meal:lunch' => [],
        'token:hostel' => [],
        'other' => [],
    ];

    foreach ($spendings as $s) {
        if ($s->type === 'bike' && $s->sub_type === 'fuel') $groups['bike:fuel'][] = $s;
        elseif ($s->type === 'bike' && $s->sub_type === 'maintenance') $groups['bike:maintenance'][] = $s;
        elseif ($s->type === 'meal' && $s->sub_type === 'lunch') $groups['meal:lunch'][] = $s;
        elseif ($s->type === 'token') $groups['token:hostel'][] = $s;
        elseif ($s->type === 'other') $groups['other'][] = $s;
    }

    $groupTotalsNet = [];
    foreach ($groups as $k => $rows) {
        $groupTotalsNet[$k] = 0.0;
        foreach ($rows as $r) {
            $groupTotalsNet[$k] += ((float)$r->amount + (float)($r->transaction_cost ?? 0));
        }
    }

    $detailsFor = function($s){
        if($s->type==='token'){
            $hostelName = $s->hostel?->hostel_name ?? '-';
            $meter = $s->hostel?->meter_no ?? '-';
            return "Hostel: {$hostelName} | Meter: {$meter}";
        }
        if($s->type==='bike'){
            $plate = $s->bike?->plate_no ?? '-';
            $d = "Plate: {$plate}";
            //  force particulars to show for maintenance
            if($s->sub_type==='maintenance'){
                $work = trim((string)($s->particulars ?? ''));
                $d .= " | Work: " . ($work !== '' ? $work : '—');
            }
            return $d;
        }
        if($s->type==='meal'){
            return "Lunch | " . ($s->description ?? '-');
        }
        return $s->description ?? '-';
    };
@endphp

<div class="header">
    <div class="title">PettyCash Board Report</div>

    <div class="subline muted">
        Range:
        <span class="badge">{{ $from ?? 'All' }}</span>
        to
        <span class="badge">{{ $to ?? 'All' }}</span>

        &nbsp; | &nbsp; Batches:
        <span class="badge">{{ $batchLabel }}</span>

        @if(!empty($bike))
            &nbsp; | &nbsp; Bike: <span class="badge">{{ $bike->plate_no }}</span>
        @endif
        @if(!empty($respondent))
            &nbsp; | &nbsp; Respondent: <span class="badge">{{ $respondent->name }}</span>
        @endif

        &nbsp; | &nbsp; View: <span class="badge">{{ strtoupper($viewMode) }}</span>
        &nbsp; | &nbsp; Generated: {{ now()->format('Y-m-d H:i') }}
    </div>
</div>

<div style="padding: 0 16px 10px;">
    <div class="cards">
        <div class="card">
            <div class="k">Credits (Net In)</div>
            <div class="v">{{ $m($creditedNetTotal) }}</div>
            <div class="s">Gross: {{ $m($creditedAmountTotal) }} • Fees: {{ $m($creditedFeeTotal) }}</div>
        </div>
        <div class="card">
            <div class="k">Debits (Net Out)</div>
            <div class="v">{{ $m($debitNetTotal) }}</div>
            <div class="s">Gross: {{ $m($debitAmountTotal) }} • Fees: {{ $m($debitFeeTotal) }}</div>
        </div>
        <div class="card">
            <div class="k">Balance</div>
            <div class="v">{{ $m($balance) }}</div>
            <div class="s">Balance = Net Credits − Net Debits</div>
        </div>
        <div class="card">
            <div class="k">Total Fees</div>
            <div class="v">{{ $m($creditedFeeTotal + $debitFeeTotal) }}</div>
            <div class="s">Credit fees + debit fees</div>
        </div>
    </div>

    <div class="hr"></div>

    {{-- ===================== CREDITS ===================== --}}
    <div class="smallhead">Credits (Money In)</div>
    <table class="striped">
        <thead>
        <tr>
            <th>Date</th>
            <th>Batch</th>
            <th>MPESA Ref</th>
            <th>Description</th>
            <th class="right">Amount</th>
            <th class="right">Fee</th>
            <th class="right">Net In</th>
        </tr>
        </thead>
        <tbody>
        @forelse($credits as $c)
            @php
                $amt = (float)$c->amount;
                $fee = (float)($c->transaction_cost ?? 0);
                $net = $amt - $fee;
            @endphp
            <tr>
                <td>{{ $c->date?->format('Y-m-d') }}</td>
                <td>{{ $c->batch?->batch_no ?? ($c->batch_id ?? '-') }}</td>
                <td>{{ $c->reference ?? '-' }}</td>
                <td>{{ $c->description ?? '-' }}</td>
                <td class="right">{{ $m($amt) }}</td>
                <td class="right">{{ $m($fee) }}</td>
                <td class="right strong">{{ $m($net) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted center">No credits in this scope.</td></tr>
        @endforelse

        <tr class="total-row">
            <td colspan="4" class="right">TOTAL</td>
            <td class="right">{{ $m($creditedAmountTotal) }}</td>
            <td class="right">{{ $m($creditedFeeTotal) }}</td>
            <td class="right">{{ $m($creditedNetTotal) }}</td>
        </tr>
        </tbody>
    </table>

    {{-- ===================== DEBITS ===================== --}}
    @if($viewMode === 'split')
        <div class="smallhead">Debits (Spendings) — Split View</div>
        <div class="tiny">Each category is shown in its own table. Amount + fee = net out.</div>

        {{-- Bikes Fuel --}}
        @if(count($groups['bike:fuel']))
            <div class="smallhead">Bikes — Fuel</div>
            <table class="striped">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>MPESA Ref</th>
                    <th>Plate</th>
                    <th>Respondent</th>
                    <th class="right">Amount</th>
                    <th class="right">Fee</th>
                    <th class="right">Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($groups['bike:fuel'] as $s)
                    @php $fee = (float)($s->transaction_cost ?? 0); @endphp
                    <tr>
                        <td>{{ $s->date?->format('Y-m-d') }}</td>
                        <td>{{ $s->reference ?? '-' }}</td>
                        <td>{{ $s->bike?->plate_no ?? '-' }}</td>
                        <td>{{ $s->respondent?->name ?? '-' }}</td>
                        <td class="right">{{ $m($s->amount) }}</td>
                        <td class="right">{{ $m($fee) }}</td>
                        <td class="right strong">{{ $m((float)$s->amount + $fee) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="6" class="right">TOTAL FUEL</td>
                    <td class="right">{{ $m($groupTotalsNet['bike:fuel']) }}</td>
                </tr>
                </tbody>
            </table>
        @endif

        {{-- Bikes Maintenance --}}
        @if(count($groups['bike:maintenance']))
            <div class="smallhead">Bikes — Maintenance</div>
            <table class="striped">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>MPESA Ref</th>
                    <th>Plate</th>
                    <th>Respondent</th>
                    <th>Particulars</th>
                    <th class="right">Amount</th>
                    <th class="right">Fee</th>
                    <th class="right">Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($groups['bike:maintenance'] as $s)
                    @php
                        $fee = (float)($s->transaction_cost ?? 0);
                        $work = trim((string)($s->particulars ?? ''));
                    @endphp
                    <tr>
                        <td>{{ $s->date?->format('Y-m-d') }}</td>
                        <td>{{ $s->reference ?? '-' }}</td>
                        <td>{{ $s->bike?->plate_no ?? '-' }}</td>
                        <td>{{ $s->respondent?->name ?? '-' }}</td>
                        <td>{{ $work !== '' ? $work : '—' }}</td>
                        <td class="right">{{ $m($s->amount) }}</td>
                        <td class="right">{{ $m($fee) }}</td>
                        <td class="right strong">{{ $m((float)$s->amount + $fee) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="7" class="right">TOTAL MAINTENANCE</td>
                    <td class="right">{{ $m($groupTotalsNet['bike:maintenance']) }}</td>
                </tr>
                </tbody>
            </table>
        @endif

        {{-- Meals --}}
        @if(count($groups['meal:lunch']))
            <div class="smallhead">Meals — Lunch</div>
            <table class="striped">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>MPESA Ref</th>
                    <th>Description</th>
                    <th class="right">Amount</th>
                    <th class="right">Fee</th>
                    <th class="right">Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($groups['meal:lunch'] as $s)
                    @php $fee = (float)($s->transaction_cost ?? 0); @endphp
                    <tr>
                        <td>{{ $s->date?->format('Y-m-d') }}</td>
                        <td>{{ $s->reference ?? '-' }}</td>
                        <td>{{ $s->description ?? '-' }}</td>
                        <td class="right">{{ $m($s->amount) }}</td>
                        <td class="right">{{ $m($fee) }}</td>
                        <td class="right strong">{{ $m((float)$s->amount + $fee) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="5" class="right">TOTAL MEALS</td>
                    <td class="right">{{ $m($groupTotalsNet['meal:lunch']) }}</td>
                </tr>
                </tbody>
            </table>
        @endif

        {{-- Token --}}
        @if(count($groups['token:hostel']))
            <div class="smallhead">Token — Hostels</div>
            <table class="striped">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>MPESA Ref</th>
                    <th>Meter No</th>
                    <th>Hostel</th>
                    <th class="right">Amount</th>
                    <th class="right">Fee</th>
                    <th class="right">Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($groups['token:hostel'] as $s)
                    @php $fee = (float)($s->transaction_cost ?? 0); @endphp
                    <tr>
                        <td>{{ $s->date?->format('Y-m-d') }}</td>
                        <td>{{ $s->reference ?? '-' }}</td>
                        <td>{{ $s->hostel?->meter_no ?? '-' }}</td>
                        <td>{{ $s->hostel?->hostel_name ?? '-' }}</td>
                        <td class="right">{{ $m($s->amount) }}</td>
                        <td class="right">{{ $m($fee) }}</td>
                        <td class="right strong">{{ $m((float)$s->amount + $fee) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="6" class="right">TOTAL TOKEN</td>
                    <td class="right">{{ $m($groupTotalsNet['token:hostel']) }}</td>
                </tr>
                </tbody>
            </table>
        @endif

        {{-- Others --}}
        @if(count($groups['other']))
            <div class="smallhead">Others</div>
            <table class="striped">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>MPESA Ref</th>
                    <th>Description</th>
                    <th>Respondent</th>
                    <th class="right">Amount</th>
                    <th class="right">Fee</th>
                    <th class="right">Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($groups['other'] as $s)
                    @php $fee = (float)($s->transaction_cost ?? 0); @endphp
                    <tr>
                        <td>{{ $s->date?->format('Y-m-d') }}</td>
                        <td>{{ $s->reference ?? '-' }}</td>
                        <td>{{ $s->description ?? '-' }}</td>
                        <td>{{ $s->respondent?->name ?? '-' }}</td>
                        <td class="right">{{ $m($s->amount) }}</td>
                        <td class="right">{{ $m($fee) }}</td>
                        <td class="right strong">{{ $m((float)$s->amount + $fee) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="6" class="right">TOTAL OTHERS</td>
                    <td class="right">{{ $m($groupTotalsNet['other']) }}</td>
                </tr>
                </tbody>
            </table>
        @endif

        <table>
            <tbody>
            <tr class="total-row">
                <td class="right">TOTAL DEBITS (Net Out)</td>
                <td class="right" style="width:160px">{{ $m($debitNetTotal) }}</td>
            </tr>
            <tr class="total-row">
                <td class="right">BALANCE</td>
                <td class="right">{{ $m($balance) }}</td>
            </tr>
            </tbody>
        </table>

    @else
        <div class="smallhead">Debits (Spendings) — Combined View</div>
        <div class="tiny">Transactions from the credited amount</div>

        <table class="striped">
            <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Details</th>
<th>Particulars</th>

                <th>MPESA Ref</th>
                <th>Respondent</th>
                <th class="right">Amount</th>
                <th class="right">Fee</th>
                <th class="right">Total</th>
            </tr>
            </thead>
            <tbody>
            @forelse($spendings as $s)
                @php
                    $cat = strtoupper($s->type . ($s->sub_type ? (':'.$s->sub_type) : ''));
                    $fee = (float)($s->transaction_cost ?? 0);
                @endphp
                <tr>
                    <td>{{ $s->date?->format('Y-m-d') }}</td>
                    <td><span class="badge">{{ $cat }}</span></td>
                    <td>{{ $detailsFor($s) }}</td>
                    <td>
    {{ ($s->type === 'bike' && $s->sub_type === 'maintenance') ? ($s->particulars ?? '-') : '-' }}
</td>

                    <td>{{ $s->reference ?? '-' }}</td>
                    <td>{{ $s->respondent?->name ?? '-' }}</td>
                    <td class="right">{{ $m($s->amount) }}</td>
                    <td class="right">{{ $m($fee) }}</td>
                    <td class="right strong">{{ $m((float)$s->amount + $fee) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted center">No spendings in this scope.</td></tr>
            @endforelse

            <tr class="total-row">
                <td colspan="5" class="right">TOTAL</td>
                <td class="right">{{ $m($debitAmountTotal) }}</td>
                <td class="right">{{ $m($debitFeeTotal) }}</td>
                <td class="right">{{ $m($debitNetTotal) }}</td>
            </tr>

            <tr class="total-row">
                <td colspan="7" class="right">BALANCE</td>
                <td class="right">{{ $m($balance) }}</td>
            </tr>
            </tbody>
        </table>
    @endif
</div>

{{-- ===================== ANALYSIS PAGE ===================== --}}
<div class="pagebreak"></div>
<div style="padding: 0 16px 16px;">
    <div class="title" style="font-size:14px;">Analysis & Charts</div>
    <div class="muted" style="margin-top:6px;">
        
    </div>

    <table class="striped">
        <thead>
        <tr>
            <th>Bucket</th>
            <th class="right">Net Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($totalsNet as $k => $v)
            <tr>
                <td>{{ strtoupper($k) }}</td>
                <td class="right">{{ $m($v) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if(!empty($chartB64))
        <img style="width:100%;max-width:900px;border:1px solid #e5e7eb;border-radius:12px" src="data:image/png;base64,{{ $chartB64 }}">
    @else
        <div class="note">
            Chart not available. Totals table above is your fallback.
        </div>
    @endif
</div>

</body>
</html>
