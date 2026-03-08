<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\BikeService;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaintenanceController extends Controller
{
    use ApiResponder;

    public function schedule(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $validStatuses = ['overdue', 'due_soon', 'ok', 'never', 'unroadworthy'];
        if ($status !== '' && !in_array($status, $validStatuses, true)) {
            $status = '';
        }

        $soonDays = (int) $request->integer('soon_days', 7);
        if ($soonDays < 1) {
            $soonDays = 7;
        }

        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $today = Carbon::today()->toDateString();
        $soonCutoff = Carbon::today()->addDays($soonDays)->toDateString();

        $statusExpr = "CASE
            WHEN petty_bikes.is_unroadworthy = 1 THEN 'unroadworthy'
            WHEN petty_bikes.next_service_due_date IS NULL THEN 'never'
            WHEN petty_bikes.next_service_due_date < '{$today}' THEN 'overdue'
            WHEN petty_bikes.next_service_due_date <= '{$soonCutoff}' THEN 'due_soon'
            ELSE 'ok'
        END";

        $bikes = Bike::query()
            ->select('petty_bikes.*')
            ->selectRaw("{$statusExpr} as schedule_status")
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('plate_no', 'like', '%' . $q . '%')
                        ->orWhere('model', 'like', '%' . $q . '%')
                        ->orWhere('status', 'like', '%' . $q . '%')
                        ->orWhere('unroadworthy_notes', 'like', '%' . $q . '%');
                });
            })
            ->when($status !== '', fn ($query) => $query->having('schedule_status', '=', $status))
            ->orderBy('plate_no')
            ->paginate($perPage)
            ->withQueryString();

        $statusCounts = Bike::query()
            ->selectRaw("{$statusExpr} as schedule_status, COUNT(*) as c")
            ->groupBy('schedule_status')
            ->pluck('c', 'schedule_status');

        return $this->successResponse([
            'bikes' => collect($bikes->items())
                ->map(fn ($bike) => $this->mapBikeForSchedule($bike))
                ->values(),
            'summary' => [
                'overdue' => (int) ($statusCounts['overdue'] ?? 0),
                'due_soon' => (int) ($statusCounts['due_soon'] ?? 0),
                'ok' => (int) ($statusCounts['ok'] ?? 0),
                'never' => (int) ($statusCounts['never'] ?? 0),
                'unroadworthy' => (int) ($statusCounts['unroadworthy'] ?? 0),
            ],
            'filters' => [
                'q' => $q,
                'status' => $status,
                'soon_days' => $soonDays,
                'per_page' => $perPage,
            ],
        ], 'Maintenance schedule fetched.', 200, [
            'pagination' => $this->paginationMeta($bikes),
        ]);
    }

    public function history(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $bikeId = (int) $request->integer('bike_id', 0);
        $from = $request->query('from');
        $to = $request->query('to');
        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $query = BikeService::query()
            ->with('bike:id,plate_no')
            ->when($bikeId > 0, fn ($q2) => $q2->where('bike_id', $bikeId))
            ->when($from, fn ($q2) => $q2->whereDate('service_date', '>=', $from))
            ->when($to, fn ($q2) => $q2->whereDate('service_date', '<=', $to))
            ->when($q !== '', function ($q2) use ($q) {
                $q2->where(function ($nested) use ($q) {
                    $nested->where('reference', 'like', '%' . $q . '%')
                        ->orWhere('work_done', 'like', '%' . $q . '%')
                        ->orWhereHas('bike', fn ($bikeQ) => $bikeQ->where('plate_no', 'like', '%' . $q . '%'));
                });
            });

        $services = (clone $query)
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $totals = (clone $query)->selectRaw('
            COALESCE(SUM(amount),0) as amount_total,
            COALESCE(SUM(transaction_cost),0) as transaction_cost_total,
            COALESCE(SUM(amount + transaction_cost),0) as net_total
        ')->first();

        return $this->successResponse([
            'services' => collect($services->items())
                ->map(fn (BikeService $service) => $this->mapService($service))
                ->values(),
            'summary' => [
                'amount_total' => round((float) ($totals->amount_total ?? 0), 2),
                'transaction_cost_total' => round((float) ($totals->transaction_cost_total ?? 0), 2),
                'net_total' => round((float) ($totals->net_total ?? 0), 2),
            ],
            'filters' => [
                'q' => $q,
                'bike_id' => $bikeId > 0 ? $bikeId : null,
                'from' => $from,
                'to' => $to,
                'per_page' => $perPage,
            ],
        ], 'Maintenance history fetched.', 200, [
            'pagination' => $this->paginationMeta($services),
        ]);
    }

    public function unroadworthy(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $bikes = Bike::query()
            ->where('is_unroadworthy', 1)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('plate_no', 'like', '%' . $q . '%')
                        ->orWhere('model', 'like', '%' . $q . '%')
                        ->orWhere('status', 'like', '%' . $q . '%')
                        ->orWhere('unroadworthy_notes', 'like', '%' . $q . '%');
                });
            })
            ->orderByDesc('unroadworthy_at')
            ->orderBy('plate_no')
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse([
            'bikes' => collect($bikes->items())
                ->map(fn (Bike $bike) => $this->mapBike($bike))
                ->values(),
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ], 'Unroadworthy bikes fetched.', 200, [
            'pagination' => $this->paginationMeta($bikes),
        ]);
    }

    public function showBike(Bike $bike, Request $request)
    {
        $servicesPerPageOptions = [10, 20, 50, 100];
        $servicesPerPage = (int) $request->integer('services_per_page', 20);
        if (!in_array($servicesPerPage, $servicesPerPageOptions, true)) {
            $servicesPerPage = 20;
        }

        $maintPerPageOptions = [10, 20, 50, 100];
        $maintPerPage = (int) $request->integer('maintenances_per_page', 20);
        if (!in_array($maintPerPage, $maintPerPageOptions, true)) {
            $maintPerPage = 20;
        }

        $services = BikeService::query()
            ->where('bike_id', $bike->id)
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->paginate($servicesPerPage, ['*'], 'services_page')
            ->withQueryString();

        $maintenances = Spending::query()
            ->with(['batch', 'allocations.batch'])
            ->where('type', 'bike')
            ->where('sub_type', 'maintenance')
            ->where('related_id', $bike->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($maintPerPage, ['*'], 'maintenances_page')
            ->withQueryString();

        $serviceTotals = BikeService::query()
            ->where('bike_id', $bike->id)
            ->selectRaw('COALESCE(SUM(amount),0) as amount_total, COALESCE(SUM(transaction_cost),0) as transaction_cost_total, COALESCE(SUM(amount + transaction_cost),0) as net_total')
            ->first();

        $maintenanceTotals = Spending::query()
            ->where('type', 'bike')
            ->where('sub_type', 'maintenance')
            ->where('related_id', $bike->id)
            ->selectRaw('COALESCE(SUM(amount),0) as amount_total, COALESCE(SUM(transaction_cost),0) as transaction_cost_total, COALESCE(SUM(amount + transaction_cost),0) as net_total')
            ->first();

        return $this->successResponse([
            'bike' => $this->mapBikeForSchedule($bike),
            'services' => collect($services->items())
                ->map(fn (BikeService $service) => $this->mapService($service))
                ->values(),
            'maintenances' => collect($maintenances->items())
                ->map(fn (Spending $spending) => $this->mapMaintenanceSpending($spending))
                ->values(),
            'summary' => [
                'service' => [
                    'amount_total' => round((float) ($serviceTotals->amount_total ?? 0), 2),
                    'transaction_cost_total' => round((float) ($serviceTotals->transaction_cost_total ?? 0), 2),
                    'net_total' => round((float) ($serviceTotals->net_total ?? 0), 2),
                ],
                'maintenance' => [
                    'amount_total' => round((float) ($maintenanceTotals->amount_total ?? 0), 2),
                    'transaction_cost_total' => round((float) ($maintenanceTotals->transaction_cost_total ?? 0), 2),
                    'net_total' => round((float) ($maintenanceTotals->net_total ?? 0), 2),
                ],
            ],
        ], 'Bike maintenance profile fetched.', 200, [
            'pagination' => [
                'services' => $this->paginationMeta($services),
                'maintenances' => $this->paginationMeta($maintenances),
            ],
        ]);
    }

    public function storeService(Request $request, Bike $bike)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        $data = $request->validate([
            'service_date' => ['required', 'date'],
            'next_due_date' => ['nullable', 'date', 'after_or_equal:service_date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'work_done' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->attributes->get('pettyUser');
        $service = null;

        DB::transaction(function () use ($bike, $data, $user, &$service) {
            $lockedBike = Bike::query()->lockForUpdate()->findOrFail($bike->id);

            $service = BikeService::query()->create([
                'bike_id' => $lockedBike->id,
                'service_date' => Carbon::parse($data['service_date'])->format('Y-m-d'),
                'next_due_date' => !empty($data['next_due_date'])
                    ? Carbon::parse($data['next_due_date'])->format('Y-m-d')
                    : null,
                'reference' => $data['reference'] ?? null,
                'work_done' => $data['work_done'] ?? null,
                'amount' => (float) ($data['amount'] ?? 0),
                'transaction_cost' => (float) ($data['transaction_cost'] ?? 0),
                'recorded_by' => $user?->id,
            ]);

            $this->syncBikeServiceDates($lockedBike);

            if ((bool) $lockedBike->is_unroadworthy) {
                $lockedBike->is_unroadworthy = 0;
                $lockedBike->unroadworthy_notes = null;
                $lockedBike->unroadworthy_at = null;
                $lockedBike->flagged_at = null;
            }

            $lockedBike->save();
        });

        $service->load('bike');

        return $this->successResponse([
            'bike' => $this->mapBike($service->bike),
            'service' => $this->mapService($service),
        ], 'Service recorded.', 201);
    }

    public function updateService(Request $request, BikeService $service)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        $data = $request->validate([
            'service_date' => ['sometimes', 'required', 'date'],
            'next_due_date' => ['sometimes', 'nullable', 'date'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'work_done' => ['sometimes', 'nullable', 'string'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'transaction_cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ]);

        if (empty($data)) {
            return $this->errorResponse('No update fields supplied.', 422, [
                'payload' => ['Provide at least one field to update.'],
            ]);
        }

        $serviceDate = array_key_exists('service_date', $data)
            ? Carbon::parse($data['service_date'])->format('Y-m-d')
            : optional($service->service_date)->format('Y-m-d');
        $nextDueDate = array_key_exists('next_due_date', $data)
            ? ($data['next_due_date'] ? Carbon::parse($data['next_due_date'])->format('Y-m-d') : null)
            : optional($service->next_due_date)->format('Y-m-d');

        if ($nextDueDate && $serviceDate && Carbon::parse($nextDueDate)->lt(Carbon::parse($serviceDate))) {
            return $this->errorResponse('Validation failed.', 422, [
                'next_due_date' => ['The next due date must be after or equal to service date.'],
            ]);
        }

        DB::transaction(function () use ($service, $data, $serviceDate, $nextDueDate) {
            $service->fill([
                'service_date' => $serviceDate,
                'next_due_date' => $nextDueDate,
                'reference' => array_key_exists('reference', $data) ? $data['reference'] : $service->reference,
                'work_done' => array_key_exists('work_done', $data) ? $data['work_done'] : $service->work_done,
                'amount' => array_key_exists('amount', $data) ? (float) ($data['amount'] ?? 0) : $service->amount,
                'transaction_cost' => array_key_exists('transaction_cost', $data)
                    ? (float) ($data['transaction_cost'] ?? 0)
                    : $service->transaction_cost,
            ]);
            $service->save();

            $bike = Bike::query()->lockForUpdate()->findOrFail($service->bike_id);
            $this->syncBikeServiceDates($bike);
            $bike->save();
        });

        $service->refresh()->load('bike');

        return $this->successResponse([
            'bike' => $this->mapBike($service->bike),
            'service' => $this->mapService($service),
        ], 'Service updated.');
    }

    public function destroyService(Request $request, BikeService $service)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin'])) {
            return $deny;
        }

        $user = $request->attributes->get('pettyUser');
        $serviceSnapshot = $service->toArray();
        $bikeId = $service->bike_id;
        $serviceId = $service->id;
        $ip = (string) $request->ip();
        $userAgent = (string) $request->userAgent();

        DB::transaction(function () use ($service, $bikeId, $serviceId, $serviceSnapshot, $user, $ip, $userAgent) {
            $service->delete();

            $bike = Bike::query()->lockForUpdate()->findOrFail($bikeId);
            $this->syncBikeServiceDates($bike);
            $bike->save();

            if (Schema::hasTable('petty_bike_service_logs')) {
                DB::table('petty_bike_service_logs')->insert([
                    'bike_service_id' => $serviceId,
                    'bike_id' => $bikeId,
                    'action' => 'deleted',
                    'performed_by' => $user?->id,
                    'payload' => json_encode($serviceSnapshot),
                    'ip' => $ip !== '' ? $ip : null,
                    'user_agent' => $userAgent !== '' ? $userAgent : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return $this->successResponse([], 'Service deleted.');
    }

    public function setUnroadworthy(Request $request, Bike $bike)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        $data = $request->validate([
            'is_unroadworthy' => ['required', 'boolean'],
            'unroadworthy_notes' => ['nullable', 'string'],
        ]);

        $isUnroadworthy = (bool) $data['is_unroadworthy'];

        $bike->is_unroadworthy = $isUnroadworthy;
        $bike->unroadworthy_notes = $data['unroadworthy_notes'] ?? null;
        $bike->unroadworthy_at = $isUnroadworthy ? now() : null;
        $bike->flagged_at = $isUnroadworthy ? now() : null;
        $bike->save();

        return $this->successResponse([
            'bike' => $this->mapBike($bike->fresh()),
        ], 'Unroadworthy status updated.');
    }

    private function syncBikeServiceDates(Bike $bike): void
    {
        $latest = BikeService::query()
            ->where('bike_id', $bike->id)
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->first();

        if (!$latest) {
            $bike->last_service_date = null;
            $bike->next_service_due_date = null;
            return;
        }

        $bike->last_service_date = $latest->service_date;
        $bike->next_service_due_date = $latest->next_due_date;
    }

    private function mapBikeForSchedule($bike): array
    {
        $today = Carbon::today();
        $nextDue = $bike->next_service_due_date ? Carbon::parse($bike->next_service_due_date)->startOfDay() : null;
        $daysToDue = $nextDue ? $today->diffInDays($nextDue, false) : null;
        $scheduleStatus = $bike->schedule_status ?? null;

        if (!$scheduleStatus) {
            if ((bool) $bike->is_unroadworthy) {
                $scheduleStatus = 'unroadworthy';
            } elseif (!$bike->next_service_due_date) {
                $scheduleStatus = 'never';
            } elseif ($nextDue && $nextDue->lt($today)) {
                $scheduleStatus = 'overdue';
            } elseif ($nextDue && $nextDue->lte($today->copy()->addDays(7))) {
                $scheduleStatus = 'due_soon';
            } else {
                $scheduleStatus = 'ok';
            }
        }

        return [
            'id' => $bike->id,
            'plate_no' => $bike->plate_no,
            'model' => $bike->model,
            'status' => $bike->status,
            'is_unroadworthy' => (bool) $bike->is_unroadworthy,
            'unroadworthy_notes' => $bike->unroadworthy_notes,
            'unroadworthy_at' => optional($bike->unroadworthy_at)->format('Y-m-d H:i:s'),
            'last_service_date' => optional($bike->last_service_date)->format('Y-m-d'),
            'next_service_due_date' => optional($bike->next_service_due_date)->format('Y-m-d'),
            'days_to_due' => $daysToDue,
            'schedule_status' => $scheduleStatus,
        ];
    }

    private function mapBike(Bike $bike): array
    {
        return [
            'id' => $bike->id,
            'plate_no' => $bike->plate_no,
            'model' => $bike->model,
            'status' => $bike->status,
            'is_unroadworthy' => (bool) $bike->is_unroadworthy,
            'unroadworthy_notes' => $bike->unroadworthy_notes,
            'unroadworthy_at' => optional($bike->unroadworthy_at)->format('Y-m-d H:i:s'),
            'last_service_date' => optional($bike->last_service_date)->format('Y-m-d'),
            'next_service_due_date' => optional($bike->next_service_due_date)->format('Y-m-d'),
        ];
    }

    private function mapService(BikeService $service): array
    {
        $amount = (float) ($service->amount ?? 0);
        $transactionCost = (float) ($service->transaction_cost ?? 0);

        return [
            'id' => $service->id,
            'bike_id' => $service->bike_id,
            'bike_plate_no' => $service->bike?->plate_no,
            'service_date' => optional($service->service_date)->format('Y-m-d'),
            'next_due_date' => optional($service->next_due_date)->format('Y-m-d'),
            'reference' => $service->reference,
            'work_done' => $service->work_done,
            'amount' => $amount,
            'transaction_cost' => $transactionCost,
            'total' => $amount + $transactionCost,
            'recorded_by' => $service->recorded_by,
            'created_at' => optional($service->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function mapMaintenanceSpending(Spending $spending): array
    {
        $amount = (float) ($spending->amount ?? 0);
        $transactionCost = (float) ($spending->transaction_cost ?? 0);

        return [
            'id' => $spending->id,
            'reference' => $spending->reference,
            'amount' => $amount,
            'transaction_cost' => $transactionCost,
            'total' => $amount + $transactionCost,
            'date' => optional($spending->date)->format('Y-m-d'),
            'description' => $spending->description,
            'particulars' => $spending->particulars,
            'batch_id' => $spending->batch_id,
            'batch_no' => $spending->batch?->batch_no,
            'allocations' => $spending->allocations
                ->map(fn ($allocation) => [
                    'id' => $allocation->id,
                    'batch_id' => $allocation->batch_id,
                    'batch_no' => $allocation->batch?->batch_no,
                    'amount' => (float) ($allocation->amount ?? 0),
                    'transaction_cost' => (float) ($allocation->transaction_cost ?? 0),
                    'total' => (float) ($allocation->amount ?? 0) + (float) ($allocation->transaction_cost ?? 0),
                ])
                ->values(),
        ];
    }

    private function denyIfRoleNotIn(Request $request, array $roles)
    {
        $user = $request->attributes->get('pettyUser');
        $role = (string) ($user?->role ?? '');

        if (!in_array($role, $roles, true)) {
            return $this->errorResponse('Forbidden.', 403);
        }

        return null;
    }
}
