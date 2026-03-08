@php
    use Carbon\Carbon;

    $fmtDate = function ($v, $format = 'Y-m-d') {
        if (empty($v)) return '-';
        try {
            if ($v instanceof \DateTimeInterface) return $v->format($format);
            return Carbon::parse($v)->format($format);
        } catch (\Throwable $e) {
            return (string) $v;
        }
    };

    /**
     * Plate fallback strategy (kept from your existing code),
     * but now we first prefer $s->plate_no coming from the unified query.
     */
    $plateFromRow = function($s) {
        $plate = data_get($s, 'plate_no')
            ?? data_get($s, 'bike.plate_no')
            ?? data_get($s, 'bike.plate')
            ?? data_get($s, 'vehicle.plate_no')
            ?? data_get($s, 'vehicle.plate')
            ?? data_get($s, 'asset.plate_no')
            ?? data_get($s, 'asset.plate');

        if (!empty($plate)) return $plate;

        $plate = data_get($s, 'meta.plate_no')
            ?? data_get($s, 'meta.plate')
            ?? data_get($s, 'details.plate_no')
            ?? data_get($s, 'details.plate');

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

    $badgeClass = function ($type) {
        $t = strtolower((string)$type);
        return match ($t) {
            'bike' => 'background:#ecfdf3;border-color:#abefc6;color:#027a48;',
            'fuel' => 'background:#fff6ed;border-color:#ffd7ae;color:#b93815;',
            'token' => 'background:#eef4ff;border-color:#c7d7fe;color:#3538cd;',
            'meal' => 'background:#fdf2fa;border-color:#fbcfe8;color:#c11574;',
            default => 'background:#f2f4f7;border-color:#e4e7ec;color:#344054;',
        };
    };
@endphp

<div class="table-wrap">
<table>
    <thead>
    <tr>
        <th style="width:140px">Date</th>
        <th style="width:160px">Reference</th>
        <th style="width:110px">Category</th>
        <th>Description</th>
        <th style="width:140px">Batch</th>
        <th class="num" style="width:110px">Amount</th>
        <th class="num" style="width:110px">Fee</th>
        <th class="num" style="width:130px">Total</th>
    </tr>
    </thead>

    <tbody>
    @forelse($spendings as $s)
        @php
            $fee   = (float) data_get($s, 'transaction_cost', 0);
            $amt   = (float) data_get($s, 'amount', 0);
            $total = $amt + $fee;

            $type    = strtolower((string) data_get($s, 'type', ''));
            $subType = (string) data_get($s, 'sub_type', '');

            $dateVal = data_get($s, 'date') ?? data_get($s, 'service_date');
            $createdAtVal = data_get($s, 'created_at');

            $desc = trim((string) data_get($s, 'description', ''));
            if ($desc === '') $desc = '-';

            // ✅ ALWAYS show plate for bike/service/fuel (and also whenever plate exists)
            $plate = $plateFromRow($s);
            $shouldShowPlate = !empty($plate) && (
                $type === 'bike' || $type === 'fuel' || strtolower($subType) === 'service'
            );

            $batchNo = data_get($s, 'batch_no') ?? data_get($s, 'batch.batch_no') ?? '-';

            $respondentName = data_get($s, 'respondent.name');

            $src = strtolower((string) data_get($s, 'source', 'spending'));
            $srcLabel = $src === 'bike_service' ? 'SERVICE' : 'SPENDING';
        @endphp

        <tr>
            <td>
                <div>{{ $fmtDate($dateVal, 'Y-m-d') }}</div>
                <div class="small">{{ $createdAtVal ? $fmtDate($createdAtVal, 'H:i') : '-' }}</div>
            </td>

            <td>{{ data_get($s, 'reference', '-') ?: '-' }}</td>

            <td>
                <span class="badge" style="border:1px solid #e4e7ec;{{ $badgeClass($type) }}">
                    {{ strtoupper($type ?: 'N/A') }}
                </span>

                @if(!empty($subType))
                    <div class="small">{{ strtoupper($subType) }}</div>
                @endif

                <div class="small" style="letter-spacing:.06em">{{ $srcLabel }}</div>
            </td>

            <td>
                <div>{{ $desc }}</div>

                @php
    $meterNo = trim((string) data_get($s, 'meter_no', ''));
    $isToken = strtolower((string) data_get($s, 'type', '')) === 'token';
@endphp

@if($shouldShowPlate)
    <div class="small" style="margin-top:3px"><strong>Plate:</strong> {{ $plate }}</div>
@endif

@if($isToken && $meterNo !== '')
    <div class="small" style="margin-top:3px"><strong>Meter:</strong> {{ $meterNo }}</div>
@endif


                @if(!empty($respondentName))
                    <div class="small" style="margin-top:3px">Respondent: {{ $respondentName }}</div>
                @endif
            </td>

            <td>{{ $batchNo }}</td>

            <td class="num">{{ number_format($amt, 2) }}</td>
            <td class="num">{{ number_format($fee, 2) }}</td>
            <td class="num"><strong>{{ number_format($total, 2) }}</strong></td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="muted">No spendings found for the selected filters.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</div>
