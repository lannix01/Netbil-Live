<?php

namespace Modules\Inventory\Http\Controllers\Inventory;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\InventoryLog;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemUnit;
use Modules\Inventory\Models\TechnicianItemAssignment;

class AssignmentController extends Controller
{
    public function index()
    {
        $technicians = User::query()
            ->where('role', 'technician')
            ->with('department')
            ->orderBy('name')
            ->get();

        $items = Item::query()->where('is_active', true)->orderBy('name')->get();

        // Serialized items list
        $serialItems = Item::query()
            ->where('is_active', true)
            ->where('has_serial', true)
            ->orderBy('name')
            ->get();

        // Available units in store (status = in_store)
        // NOTE: this is for UI dropdown only; backend re-checks with locks.
        $availableUnits = ItemUnit::query()
            ->where('status', 'in_store')
            ->with('item')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('item_id');

        $assignments = TechnicianItemAssignment::query()
            ->with(['technician.department', 'item.group', 'assigner'])
            ->latest()
            ->paginate(30);

        return view('inventory::assignments.index', compact('technicians', 'items', 'serialItems', 'availableUnits', 'assignments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'technician_id' => ['required','exists:users,id'],
            'item_id' => ['required','exists:inventory_items,id'],

            // Bulk assignment
            'qty_allocated' => ['nullable','integer','min:0'],

            // Serialized assignment: provide unit_ids[]
            'unit_ids' => ['nullable','array'],
            'unit_ids.*' => ['integer'],
            'reference' => ['nullable','string','max:255'],
            'notes' => ['nullable','string'],
        ]);

        DB::transaction(function () use ($data) {
            $item = Item::lockForUpdate()->findOrFail($data['item_id']);
            $adminId = auth()->id();

            $assignment = TechnicianItemAssignment::lockForUpdate()->firstOrCreate(
                [
                    'technician_id' => $data['technician_id'],
                    'item_id' => $item->id,
                ],
                [
                    'qty_allocated' => 0,
                    'qty_deployed' => 0,
                    'assigned_by' => $adminId,
                    'assigned_at' => now(),
                    'is_active' => true,
                ]
            );

            // SERIALIZED FLOW
            if ($item->has_serial) {
                $unitIds = $data['unit_ids'] ?? [];
                if (count($unitIds) < 1) {
                    abort(422, "unit_ids is required for serialized item: {$item->name}");
                }

                $units = ItemUnit::query()
                    ->where('item_id', $item->id)
                    ->whereIn('id', $unitIds)
                    ->where('status', 'in_store')
                    ->lockForUpdate()
                    ->get();

                if ($units->count() !== count($unitIds)) {
                    abort(422, "One or more units are not available in store for item: {$item->name}");
                }

                if ($item->qty_on_hand < $units->count()) {
                    abort(422, "Not enough qty_on_hand for item: {$item->name}");
                }

                $item->decrement('qty_on_hand', $units->count());

                $assignment->qty_allocated += $units->count();
                $assignment->assigned_by = $adminId;
                $assignment->assigned_at = now();
                $assignment->is_active = true;
                $assignment->save();

                foreach ($units as $unit) {
                    $unit->update([
                        'status' => 'assigned',
                        'assigned_to' => $data['technician_id'],
                        'assigned_at' => now(),
                    ]);

                    InventoryLog::create([
                        'action' => 'assigned',
                        'item_id' => $item->id,
                        'item_unit_id' => $unit->id,
                        'qty' => null,
                        'serial_no' => $unit->serial_no,
                        'from_user_id' => $adminId,
                        'to_user_id' => $data['technician_id'],
                        'reference' => $data['reference'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'created_by' => $adminId,
                    ]);
                }

                return;
            }

            // BULK FLOW
            $qty = (int)($data['qty_allocated'] ?? 0);
            if ($qty < 1) {
                abort(422, "qty_allocated is required for bulk item: {$item->name}");
            }

            if ($qty > (int)$item->qty_on_hand) {
                abort(422, "Allocated qty cannot exceed store qty_on_hand for item: {$item->name}");
            }

            $item->decrement('qty_on_hand', $qty);

            $assignment->qty_allocated += $qty;
            $assignment->assigned_by = $adminId;
            $assignment->assigned_at = now();
            $assignment->is_active = true;
            $assignment->save();

            InventoryLog::create([
                'action' => 'assigned',
                'item_id' => $item->id,
                'item_unit_id' => null,
                'qty' => $qty,
                'serial_no' => null,
                'from_user_id' => $adminId,
                'to_user_id' => $data['technician_id'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $adminId,
            ]);
        });

        return back()->with('success', 'Assignment saved (store updated + log created).');
    }

    public function update(Request $request, TechnicianItemAssignment $assignment)
    {
        $data = $request->validate([
            'is_active' => ['nullable','boolean'],
        ]);

        $assignment->update([
            'is_active' => (bool)($data['is_active'] ?? true),
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        return back()->with('success', 'Assignment updated.');
    }

    public function destroy(TechnicianItemAssignment $assignment)
    {
        $assignment->delete();
        return back()->with('success', 'Assignment removed.');
    }
}
