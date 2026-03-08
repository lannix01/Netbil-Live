<?php

namespace App\Modules\Inventory\Http\Controllers\Inventory;

use App\Modules\Inventory\Http\Controllers\Controller;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\ItemUnit;
use App\Modules\Inventory\Models\TechnicianItemAssignment;

class TechnicianInventoryController extends Controller
{
    public function index()
    {
        $user = auth('inventory')->user();

        if (!$user) {
            return redirect()->route('inventory.auth.login');
        }

        $assignments = TechnicianItemAssignment::query()
            ->with(['item.group'])
            ->where('technician_id', $user->id)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->paginate(30)
            ->withQueryString();

        return view('inventory::tech.items.index', compact('assignments'));
    }

    public function show(Item $item)
    {
        $user = auth('inventory')->user();

        if (!$user) {
            return redirect()->route('inventory.auth.login');
        }

        $assignment = TechnicianItemAssignment::query()
            ->with(['item.group'])
            ->where('technician_id', $user->id)
            ->where('item_id', $item->id)
            ->where('is_active', true)
            ->firstOrFail();

        $assignedUnits = collect();

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
