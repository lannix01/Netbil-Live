<?php

namespace App\Modules\Inventory\Http\Controllers\Inventory;

use Illuminate\Routing\Controller;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\ItemUnit;
use App\Modules\Inventory\Models\TechnicianItemAssignment;

class TechnicianInventoryController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $assignments = TechnicianItemAssignment::query()
            ->with(['item.group'])
            ->where('technician_id', $user->id)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->paginate(30);

        return view('inventory::tech.items.index', compact('assignments'));
    }

    public function show(Item $item)
    {
        $user = auth()->user();

        $assignment = TechnicianItemAssignment::query()
            ->with(['item.group'])
            ->where('technician_id', $user->id)
            ->where('item_id', $item->id)
            ->firstOrFail();

        $assignedUnits = [];
        if ($assignment->item?->has_serial) {
            $assignedUnits = ItemUnit::query()
                ->where('item_id', $item->id)
                ->where('status', 'assigned')
                ->where('assigned_to', $user->id)
                ->orderBy('serial_no')
                ->get();
        }

        return view('inventory::tech.items.show', compact('assignment', 'assignedUnits'));
    }
}
