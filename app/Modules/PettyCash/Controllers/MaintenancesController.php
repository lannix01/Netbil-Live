<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Models\BikeService;
use Illuminate\Support\Facades\DB;

class MaintenancesController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'schedule');

        if ($tab === 'history') {
            $services = BikeService::query()
                ->with('bike:id,plate_no')
                ->orderByDesc('service_date')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString();

            return view('pettycash::maintenances.index', compact('tab', 'services'));
        }

        if ($tab === 'unroadworthy') {
            $unroadworthy = Bike::query()
                ->where('is_unroadworthy', 1)
                ->orderByDesc('unroadworthy_at')
                ->orderBy('plate_no')
                ->get();

            return view('pettycash::maintenances.index', compact('tab', 'unroadworthy'));
        }

        // schedule tab
        $today = Carbon::today();
        $soonCutoff = $today->copy()->addDays(7);

        $bikes = Bike::query()
            ->orderBy('plate_no')
            ->get()
            ->map(function ($b) use ($today, $soonCutoff) {
                $b->computed_last_service = $b->last_service_date ? Carbon::parse($b->last_service_date)->format('Y-m-d') : null;
                $b->computed_next_due = $b->next_service_due_date ? Carbon::parse($b->next_service_due_date)->format('Y-m-d') : null;

                if ((bool)$b->is_unroadworthy) {
                    $b->computed_status = 'unroadworthy';
                    return $b;
                }

                if (!$b->next_service_due_date) {
                    $b->computed_status = 'never';
                    return $b;
                }

                $due = Carbon::parse($b->next_service_due_date);

                if ($due->lt($today)) $b->computed_status = 'overdue';
                elseif ($due->lte($soonCutoff)) $b->computed_status = 'due_soon';
                else $b->computed_status = 'ok';

                return $b;
            });

        return view('pettycash::maintenances.index', compact('tab', 'bikes'));
    }

    public function show($bikeId, Request $request)
    {
        $tab = $request->query('tab', 'overview');

        $bike = Bike::findOrFail($bikeId);

        $lastService = $bike->last_service_date ? Carbon::parse($bike->last_service_date) : null;
        $nextDue = $bike->next_service_due_date ? Carbon::parse($bike->next_service_due_date) : null;

        // SERVICES from petty_bike_services
        $services = BikeService::query()
            ->where('bike_id', $bike->id)
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->get();

        // MAINTENANCES (repairs) from spendings where type=bike and sub_type=maintenance and related_id=bike_id
        $maintenances = Spending::query()
            ->with('batch:id,batch_no')
            ->where('type', 'bike')
            ->where('sub_type', 'maintenance')
            ->where('related_id', $bike->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $serviceTotalNet = (float) BikeService::where('bike_id', $bike->id)
            ->selectRaw('COALESCE(SUM(amount + transaction_cost),0) as t')
            ->value('t');

        $maintenanceTotalNet = (float) Spending::where('type', 'bike')
            ->where('sub_type', 'maintenance')
            ->where('related_id', $bike->id)
            ->selectRaw('COALESCE(SUM(amount + transaction_cost),0) as t')
            ->value('t');

        return view('pettycash::maintenances.show', compact(
            'bike',
            'tab',
            'lastService',
            'nextDue',
            'services',
            'maintenances',
            'serviceTotalNet',
            'maintenanceTotalNet'
        ));
    }

    /**
     * Matches your URL pattern:
     * GET /pettycash/maintenances/{bike}/service/create
     */
    public function create($bikeId, Request $request)
    {
        $bike = Bike::findOrFail($bikeId);

        $defaultServiceDate = Carbon::today()->format('Y-m-d');

        return view('pettycash::maintenances.create', compact('bike', 'defaultServiceDate'));
    }

    /**
     * Matches:
     * POST /pettycash/maintenances/{bike}/service
     */
    public function store($bikeId, Request $request)
    {
        $data = $request->validate([
            'service_date' => ['required', 'date'],
            'next_due_date' => ['nullable', 'date', 'after_or_equal:service_date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'work_done' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($bikeId, $data) {
            $bike = Bike::lockForUpdate()->findOrFail($bikeId);

            $service = new BikeService();
            $service->bike_id = $bike->id;

            $service->service_date = Carbon::parse($data['service_date'])->format('Y-m-d');
            $service->next_due_date = !empty($data['next_due_date'])
                ? Carbon::parse($data['next_due_date'])->format('Y-m-d')
                : null;

            $service->reference = $data['reference'] ?? null;
            $service->work_done = $data['work_done'] ?? null;

            $service->amount = (float)($data['amount'] ?? 0);
            $service->transaction_cost = (float)($data['transaction_cost'] ?? 0);

            // recorded_by column exists in your table
            $service->recorded_by = auth('petty')->id() ?? null;

            $service->save();

            // Keep bikes table in sync for schedule tab
            $bike->last_service_date = $service->service_date;

            if ($service->next_due_date) {
                // bikes table column in your code is next_service_due_date
                $bike->next_service_due_date = $service->next_due_date;
            }

            // Optional: auto-clear unroadworthy if you just serviced it
            if ((bool)$bike->is_unroadworthy) {
                $bike->is_unroadworthy = 0;
                $bike->unroadworthy_notes = null;
                $bike->unroadworthy_at = null;
                $bike->flagged_at = null;
            }

            $bike->save();

            return redirect()
                ->route('petty.maintenances.show', [$bike->id, 'tab' => 'overview'])
                ->with('success', 'Service saved.');
        });
    }

    public function saveUnroadworthy($bikeId, Request $request)
    {
        $bike = Bike::findOrFail($bikeId);

        $data = $request->validate([
            'is_unroadworthy' => ['required', 'in:0,1'],
            'unroadworthy_notes' => ['nullable', 'string'],
        ]);

        $is = (int)$data['is_unroadworthy'] === 1;

        $bike->is_unroadworthy = $is;
        $bike->unroadworthy_notes = $data['unroadworthy_notes'] ?? null;
        $bike->unroadworthy_at = $is ? now() : null;

        $bike->flagged_at = $is ? now() : null;

        $bike->save();

        return redirect()
            ->route('petty.maintenances.show', [$bike->id, 'tab' => 'overview'])
            ->with('success', 'Unroadworthy status updated.');
    }
}
