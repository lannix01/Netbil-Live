<?php

namespace App\Modules\PettyCash\Services;

use App\Modules\PettyCash\Models\Credit;
use App\Modules\PettyCash\Models\Spending;
use Illuminate\Support\Carbon;

class BalanceService
{
    public function dashboardSummary(?string $from, ?string $to): array
    {
        [$fromDate, $toDate] = $this->normalizeRange($from, $to);

        $creditsQuery = Credit::query();
        $spendingsQuery = Spending::query();

        if ($fromDate) {
            $creditsQuery->whereDate('date', '>=', $fromDate);
            $spendingsQuery->whereDate('date', '>=', $fromDate);
        }
        if ($toDate) {
            $creditsQuery->whereDate('date', '<=', $toDate);
            $spendingsQuery->whereDate('date', '<=', $toDate);
        }

       $totalCredited = (float) $creditsQuery
    ->selectRaw('COALESCE(SUM(amount - transaction_cost), 0) as t')
    ->value('t');

$totalSpent = (float) $spendingsQuery
    ->selectRaw('COALESCE(SUM(amount + transaction_cost), 0) as t')
    ->value('t');

        $balance = $totalCredited - $totalSpent;

        // Spend by category + subtype
        $byType = Spending::query()
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate))
            ->selectRaw('type, COALESCE(sub_type, "") as sub_type, SUM(amount + transaction_cost) as total')

            ->groupBy('type', 'sub_type')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        // Category totals (top-level)
        $typeTotals = Spending::query()
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate))
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get()
            ->keyBy('type')
            ->map(fn($r) => (float) $r->total)
            ->toArray();

        // Most spending bucket (type + subtype if exists)
        $topBucket = Spending::query()
            ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate))
            ->selectRaw('type, sub_type, SUM(amount) as total')
            ->groupBy('type', 'sub_type')
            ->orderByDesc('total')
            ->first();

        return [
            'from' => $fromDate?->toDateString(),
            'to' => $toDate?->toDateString(),
            'totalCredited' => $totalCredited,
            'totalSpent' => $totalSpent,
            'balance' => $balance,
            'byType' => $byType,
            'typeTotals' => $typeTotals,
            'topBucket' => $topBucket ? [
                'type' => $topBucket->type,
                'sub_type' => $topBucket->sub_type,
                'total' => (float) $topBucket->total,
            ] : null,
        ];
    }

    private function normalizeRange(?string $from, ?string $to): array
    {
        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        if ($fromDate && $toDate && $fromDate->gt($toDate)) {
            // swap if user entered wrong order
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) return null;

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
