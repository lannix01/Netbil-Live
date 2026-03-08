<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\PettyCash\Services\BalanceService;
use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\BikeService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request, BalanceService $balanceService)
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        // Your existing summary (inflow/outflow/balance etc)
        $data = $balanceService->dashboardSummary($from, $to);

        // ✅ Add SERVICE spendings (already recorded in petty_bike_services)
        $serviceQuery = BikeService::query();

        if (!empty($from)) $serviceQuery->whereDate('service_date', '>=', $from);
        if (!empty($to))   $serviceQuery->whereDate('service_date', '<=', $to);

        $serviceSpentNet = (float) $serviceQuery
            ->selectRaw('COALESCE(SUM(amount + transaction_cost),0) as t')
            ->value('t');

        // Inject into data for the view
        $data['serviceSpentNet'] = $serviceSpentNet;

        // 🔧 Patch your existing totals defensively (works even if key names differ)
        // Common keys you might have: totalSpent / spent / totalOut / balance / availableBalance etc.
        foreach (['totalSpent', 'spent', 'totalOut', 'total_out', 'outflow', 'expenses'] as $k) {
            if (array_key_exists($k, $data)) {
                $data[$k] = (float)$data[$k] + $serviceSpentNet;
            }
        }

        foreach (['balance', 'available', 'availableBalance', 'balanceAvailable', 'netBalance'] as $k) {
            if (array_key_exists($k, $data)) {
                $data[$k] = (float)$data[$k] - $serviceSpentNet;
            }
        }

        // ✅ Bike service widgets (these were previously after return => never executed)
        $today = Carbon::today();

        $data['serviceOverdue'] = Bike::whereNotNull('next_service_due_date')
            ->where('next_service_due_date', '<', $today)
            ->where('is_unroadworthy', false)
            ->orderBy('next_service_due_date')
            ->limit(6)
            ->get();

        $data['serviceDueSoon'] = Bike::whereNotNull('next_service_due_date')
            ->whereBetween('next_service_due_date', [$today, $today->copy()->addDays(3)])
            ->where('is_unroadworthy', false)
            ->orderBy('next_service_due_date')
            ->limit(6)
            ->get();

        $data['neverServiced'] = Bike::whereNull('last_service_date')
            ->where('is_unroadworthy', false)
            ->orderBy('plate_no')
            ->limit(6)
            ->get();

        return view('pettycash::dashboard.index', $data);
    }
}
