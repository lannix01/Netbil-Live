@extends('pettycash::layouts.app')

@section('title','Batches')

@push('styles')
<style>
    .wrap{max-width:1100px;margin:0 auto}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px;vertical-align:top}
    th{font-size:12px;color:#475467;text-align:left}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#f2f4f7;font-size:12px}
    .muted{color:#667085;font-size:12px}
    .tiny{color:#667085;font-size:11px;margin-top:3px}
    .neg{color:#b42318;font-weight:800}
</style>
@endpush

@section('content')
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:10px;flex-wrap:wrap">
        <div>
            <h2 style="margin:0">Batches</h2>
            <div class="muted">
               credited batches, total spent, and balance per batch.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Batch No</th>
                <th>Credited (Net)</th>
                <th>Spent (Net)</th>
                <th>Balance (Net)</th>
                <th>Created</th>
            </tr>
            </thead>
            <tbody>
            @forelse($batches as $b)
                @php
                    $credited = (float)($b->credited_net ?? 0);
                    $spent = (float)($b->effective_spent ?? 0);
                    $bal = (float)($b->effective_balance ?? 0);

                    // carry_out is negative if deficit continues
                    $carryOut = (float)($b->carry_out ?? 0);
                    $rolled = $carryOut < 0 ? abs($carryOut) : 0;
                @endphp
                <tr>
                    <td>
                        {{-- If you have the show route wired, keep this --}}
                        <a href="{{ route('petty.batches.show', $b->id) }}">{{ $b->batch_no }}</a>

                        @if($rolled > 0)
                            <div class="tiny">
                                <span class="neg">Overdraw rolled forward:</span>
                                {{ number_format($rolled, 2) }}
                            </div>
                        @endif
                    </td>

                    <td>{{ number_format($credited, 2) }}</td>
                    <td>{{ number_format($spent, 2) }}</td>
                    <td><span class="pill">{{ number_format($bal, 2) }}</span></td>
                    <td>{{ $b->created_at?->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No batches yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
