<?php

namespace App\Modules\Inventory\Http\Controllers\Inventory;

use App\Modules\Inventory\Http\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryUser;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\ItemUnit;
use App\Modules\Inventory\Models\TechnicianItemAssignment;

class AdminDeploymentController extends Controller
{
    public function create()
    {
        // Admin-only route middleware should guard this already,
        // but keep the controller clean + consistent with inventory guard.
        $technicians = InventoryUser::query()
            ->where('inventory_role', 'technician')
            ->where('inventory_enabled', true)
            ->with('department')
            ->orderBy('name')
            ->get();

        $items = Item::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Assignments help us know what items a tech can deploy
        $assignments = TechnicianItemAssignment::query()
            ->with(['item'])
            ->where('is_active', true)
            ->get()
            ->groupBy('technician_id');

        // For serialized items: available assigned units per technician per item
        $assignedUnits = ItemUnit::query()
            ->where('status', 'assigned')
            ->whereNotNull('assigned_to')
            ->orderBy('serial_no')
            ->get()
            ->groupBy(fn ($u) => $u->assigned_to) // technician_id
            ->map(function ($units) {
                return $units->groupBy('item_id')->map(function ($u2) {
                    return $u2->map(fn ($u) => ['id' => $u->id, 'serial_no' => $u->serial_no])->values();
                });
            });

        return view('inventory::admin.deploy', compact('technicians', 'items', 'assignments', 'assignedUnits'));
    }
}
