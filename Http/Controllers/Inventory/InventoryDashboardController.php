<?php

namespace App\Modules\Inventory\Http\Controllers\Inventory;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class InventoryDashboardController extends Controller
{
    public function index()
    {
        $lowStock = DB::table('inventory_items')
            ->where('is_active', 1)
            ->whereColumn('qty_on_hand', '<=', 'reorder_level')
            ->count();

        $items = DB::table('inventory_items')->count();
        $teams = DB::table('inventory_teams')->count();
        $logs7d = DB::table('inventory_logs')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return view('inventory::dashboard.index', compact('lowStock', 'items', 'teams', 'logs7d'));
    }
}