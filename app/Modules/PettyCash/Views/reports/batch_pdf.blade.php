<!doctype html><html><head><meta charset="utf-8">
<style>
body{font-family:DejaVu Sans,sans-serif;font-size:11px}
h2{margin:0 0 6px}.muted{color:#666;font-size:10px;margin-bottom:10px}
table{width:100%;border-collapse:collapse;margin:8px 0 14px}
th,td{border:1px solid #ddd;padding:6px;vertical-align:top}
th{background:#f3f3f3}.right{text-align:right}.total{font-weight:700}
.pagebreak{page-break-before:always}
</style></head><body>

<h2>Batch Statement: {{ $batch->batch_no }}</h2>
<div class="muted">Range: {{ $from ?? 'All' }} to {{ $to ?? 'All' }} | Generated: {{ now()->format('Y-m-d H:i') }}</div>

<h3>Credits</h3>
<table>
<thead><tr><th>Date</th><th>MPESA Ref</th><th>Description</th><th class="right">Amount</th></tr></thead>
<tbody>
@foreach($credits as $c)
<tr>
<td>{{ $c->date?->format('Y-m-d') }}</td>
<td>{{ $c->reference }}</td>
<td>{{ $c->description }}</td>
<td class="right">{{ number_format((float)$c->amount,2) }}</td>
</tr>
@endforeach
<tr><td colspan="3" class="total right">TOTAL CREDITS</td><td class="total right">{{ number_format((float)$creditedTotal,2) }}</td></tr>
</tbody>
</table>

<h3>Debits</h3>
<table>
<thead><tr><th>Date</th><th>Category</th><th>Details</th><th>MPESA Ref</th><th>Respondent</th><th class="right">Amount</th></tr></thead>
<tbody>
@foreach($spendings as $s)
@php
  $cat = strtoupper($s->type . ($s->sub_type ? (':'.$s->sub_type) : ''));
  $details = '';
  if($s->type==='token'){
    $details = 'Hostel: '.($s->hostel?->hostel_name ?? '-').' | Meter: '.($s->hostel?->meter_no ?? '-');
  } elseif($s->type==='bike'){
    $details = 'Plate: '.($s->bike?->plate_no ?? '-');
    if($s->sub_type==='maintenance' && $s->particulars) $details .= ' | Work: '.$s->particulars;
  } elseif($s->type==='meal'){
    $details = 'Lunch | '.$s->description;
  } else {
    $details = $s->description ?? '-';
  }
@endphp
<tr>
<td>{{ $s->date?->format('Y-m-d') }}</td>
<td>{{ $cat }}</td>
<td>{{ $details }}</td>
<td>{{ $s->reference }}</td>
<td>{{ $s->respondent?->name ?? '-' }}</td>
<td class="right">{{ number_format((float)$s->amount,2) }}</td>
</tr>
@endforeach
<tr><td colspan="5" class="total right">TOTAL DEBITS</td><td class="total right">{{ number_format((float)$debitTotal,2) }}</td></tr>
<tr><td colspan="5" class="total right">BALANCE</td><td class="total right">{{ number_format((float)$balance,2) }}</td></tr>
</tbody>
</table>

<div class="pagebreak"></div>
<h2>Analysis & Chart</h2>
<table><thead><tr><th>Bucket</th><th class="right">Total</th></tr></thead><tbody>
@foreach($totals as $k=>$v)<tr><td>{{ strtoupper($k) }}</td><td class="right">{{ number_format((float)$v,2) }}</td></tr>@endforeach
</tbody></table>
@if($chartB64)<img style="width:100%;max-width:900px" src="data:image/png;base64,{{ $chartB64 }}">@endif

</body></html>
