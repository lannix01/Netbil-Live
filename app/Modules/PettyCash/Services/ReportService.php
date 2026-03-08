<?php

namespace App\Modules\PettyCash\Services;

use App\Modules\PettyCash\Models\Credit;
use App\Modules\PettyCash\Models\Spending;
use Illuminate\Support\Collection;

class ReportService
{
    public function credits(?array $batchIds, ?string $from, ?string $to): Collection
    {
        return Credit::query()
            ->with('batch:id,batch_no')
            ->when($batchIds && count($batchIds), fn($q) => $q->whereIn('batch_id', $batchIds))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderBy('date')
            ->orderBy('id')
            ->get();
    }

    public function spendings(?array $batchIds, ?string $from, ?string $to, ?array $includeBuckets): Collection
    {
        $q = Spending::query()
            ->with([
                'batch:id,batch_no',
                'respondent:id,name',
                'bike:id,plate_no',
                'hostel:id,hostel_name,meter_no',
            ])
            ->when($batchIds && count($batchIds), fn($x) =>
    $x->whereHas('allocations', fn($a) => $a->whereIn('batch_id', $batchIds))
)

            ->when($from, fn($x) => $x->whereDate('date', '>=', $from))
            ->when($to, fn($x) => $x->whereDate('date', '<=', $to));

        if ($includeBuckets && count($includeBuckets)) {
            $q->where(function ($w) use ($includeBuckets) {
                foreach ($includeBuckets as $bucket) {
                    if ($bucket === 'other') {
                        $w->orWhere(fn($x) => $x->where('type', 'other'));
                        continue;
                    }

                    [$type, $sub] = array_pad(explode(':', $bucket, 2), 2, null);

                    $w->orWhere(function ($x) use ($type, $sub) {
                        $x->where('type', $type);
                        if ($sub !== null) {
                            $x->where('sub_type', $sub);
                        }
                    });
                }
            });
        }

        return $q->orderBy('date')->orderBy('id')->get();
    }

    /**
     * NET totals for spendings: amount + transaction_cost
     * Used for charts / analysis pages.
     */
    public function totalsByBucketNet(Collection $spendings): array
    {
        $totals = [];

        foreach ($spendings as $s) {
            $key = $s->type . ($s->sub_type ? (':' . $s->sub_type) : '');
            $net = (float)$s->amount + (float)($s->transaction_cost ?? 0);
            $totals[$key] = ($totals[$key] ?? 0) + $net;
        }

        arsort($totals);
        return $totals;
    }

    /**
     * If you still need the old behavior anywhere:
     */
    public function totalsByBucketGross(Collection $spendings): array
    {
        $totals = [];

        foreach ($spendings as $s) {
            $key = $s->type . ($s->sub_type ? (':' . $s->sub_type) : '');
            $totals[$key] = ($totals[$key] ?? 0) + (float)$s->amount;
        }

        arsort($totals);
        return $totals;
    }
}
