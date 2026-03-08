<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\BikeService;
use App\Modules\PettyCash\Services\BalanceService;
use App\Modules\PettyCash\Support\ApiResponder;
use App\Modules\PettyCash\Support\UnifiedLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InsightsController extends Controller
{
    use ApiResponder;

    public function dashboard(Request $request, BalanceService $balanceService)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $summary = $balanceService->dashboardSummary($from, $to);

        $serviceQuery = BikeService::query();
        if (!empty($from)) {
            $serviceQuery->whereDate('service_date', '>=', $from);
        }
        if (!empty($to)) {
            $serviceQuery->whereDate('service_date', '<=', $to);
        }

        $serviceSpentNet = (float) $serviceQuery
            ->selectRaw('COALESCE(SUM(amount + transaction_cost),0) as t')
            ->value('t');

        $totalCredited = (float) ($summary['totalCredited'] ?? 0);
        $totalSpentWithoutServices = (float) ($summary['totalSpent'] ?? 0);
        $totalSpent = $totalSpentWithoutServices + $serviceSpentNet;
        $balance = $totalCredited - $totalSpent;

        $today = Carbon::today();
        $serviceOverdue = Bike::query()
            ->whereNotNull('next_service_due_date')
            ->where('next_service_due_date', '<', $today)
            ->where('is_unroadworthy', false)
            ->orderBy('next_service_due_date')
            ->limit(10)
            ->get();

        $serviceDueSoon = Bike::query()
            ->whereNotNull('next_service_due_date')
            ->whereBetween('next_service_due_date', [$today, $today->copy()->addDays(3)])
            ->where('is_unroadworthy', false)
            ->orderBy('next_service_due_date')
            ->limit(10)
            ->get();

        $neverServiced = Bike::query()
            ->whereNull('last_service_date')
            ->where('is_unroadworthy', false)
            ->orderBy('plate_no')
            ->limit(10)
            ->get();

        $unroadworthyCount = (int) Bike::query()->where('is_unroadworthy', true)->count();

        $typeTotals = $summary['typeTotals'] ?? [];
        $typeTotals['bike:service'] = round($serviceSpentNet, 2);

        return $this->successResponse([
            'summary' => [
                'from' => $summary['from'] ?? null,
                'to' => $summary['to'] ?? null,
                'total_credited' => round($totalCredited, 2),
                'total_spent' => round($totalSpent, 2),
                'total_spent_without_services' => round($totalSpentWithoutServices, 2),
                'service_spent_net' => round($serviceSpentNet, 2),
                'balance' => round($balance, 2),
            ],
            'type_totals' => $typeTotals,
            'by_type' => $summary['byType'] ?? [],
            'top_bucket' => $summary['topBucket'] ?? null,
            'service_widgets' => [
                'overdue' => $serviceOverdue->map(fn (Bike $bike) => $this->mapBikeWidget($bike))->values(),
                'due_soon' => $serviceDueSoon->map(fn (Bike $bike) => $this->mapBikeWidget($bike))->values(),
                'never_serviced' => $neverServiced->map(fn (Bike $bike) => $this->mapBikeWidget($bike))->values(),
                'unroadworthy_count' => $unroadworthyCount,
            ],
        ], 'Dashboard insights fetched.');
    }

    public function ledger(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $batchId = (int) $request->integer('batch_id', 0);
        $type = trim((string) $request->query('type', ''));
        $source = trim((string) $request->query('source', ''));
        $q = trim((string) $request->query('q', ''));
        $sort = strtolower(trim((string) $request->query('sort', 'desc')));
        if (!in_array($sort, ['asc', 'desc'], true)) {
            $sort = 'desc';
        }

        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $query = UnifiedLedger::query();

        if (!empty($from)) {
            $query->whereDate('date', '>=', $from);
        }
        if (!empty($to)) {
            $query->whereDate('date', '<=', $to);
        }
        if ($batchId > 0) {
            $query->where(function ($nested) use ($batchId) {
                $nested->where('source', 'spending')
                    ->where('batch_id', $batchId);
            });
        }
        if ($type !== '') {
            $query->where('type', $type);
        }
        if ($source !== '') {
            $query->where('source', $source);
        }
        if ($q !== '') {
            $query->where(function ($nested) use ($q) {
                $nested->where('reference', 'like', '%' . $q . '%')
                    ->orWhere('description', 'like', '%' . $q . '%')
                    ->orWhere('plate_no', 'like', '%' . $q . '%')
                    ->orWhere('meter_no', 'like', '%' . $q . '%')
                    ->orWhere('batch_no', 'like', '%' . $q . '%');
            });
        }

        $totals = (clone $query)
            ->selectRaw('
                COALESCE(SUM(amount),0) as amount_total,
                COALESCE(SUM(transaction_cost),0) as transaction_cost_total,
                COALESCE(SUM(net_total),0) as net_total
            ')
            ->first();

        $byType = (clone $query)
            ->selectRaw('type, sub_type, source, COALESCE(SUM(net_total),0) as net_total')
            ->groupBy('type', 'sub_type', 'source')
            ->orderByDesc('net_total')
            ->get()
            ->map(function ($row) {
                return [
                    'type' => $row->type,
                    'sub_type' => $row->sub_type,
                    'source' => $row->source,
                    'net_total' => round((float) $row->net_total, 2),
                ];
            })
            ->values();

        $items = (clone $query)
            ->orderBy('date', $sort)
            ->orderBy('id', $sort)
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse([
            'entries' => collect($items->items())
                ->map(fn ($row) => $this->mapLedgerRow($row))
                ->values(),
            'summary' => [
                'amount_total' => round((float) ($totals->amount_total ?? 0), 2),
                'transaction_cost_total' => round((float) ($totals->transaction_cost_total ?? 0), 2),
                'net_total' => round((float) ($totals->net_total ?? 0), 2),
                'by_type' => $byType,
            ],
            'filters' => [
                'from' => $from,
                'to' => $to,
                'batch_id' => $batchId > 0 ? $batchId : null,
                'type' => $type,
                'source' => $source,
                'q' => $q,
                'sort' => $sort,
                'per_page' => $perPage,
            ],
        ], 'Ledger entries fetched.', 200, [
            'pagination' => $this->paginationMeta($items),
        ]);
    }

    private function mapBikeWidget(Bike $bike): array
    {
        return [
            'id' => $bike->id,
            'plate_no' => $bike->plate_no,
            'model' => $bike->model,
            'status' => $bike->status,
            'last_service_date' => optional($bike->last_service_date)->format('Y-m-d'),
            'next_service_due_date' => optional($bike->next_service_due_date)->format('Y-m-d'),
        ];
    }

    private function mapLedgerRow($row): array
    {
        return [
            'id' => (int) $row->id,
            'date' => $row->date,
            'reference' => $row->reference,
            'type' => $row->type,
            'sub_type' => $row->sub_type,
            'description' => $row->description,
            'plate_no' => $row->plate_no,
            'meter_no' => $row->meter_no,
            'batch_id' => $row->batch_id,
            'batch_no' => $row->batch_no,
            'source' => $row->source,
            'amount' => round((float) ($row->amount ?? 0), 2),
            'transaction_cost' => round((float) ($row->transaction_cost ?? 0), 2),
            'net_total' => round((float) ($row->net_total ?? 0), 2),
        ];
    }
}
