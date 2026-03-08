<?php

namespace App\Modules\Inventory\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Modules\Inventory\Models\InventoryLog;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\ItemDeployment;
use App\Modules\Inventory\Models\ItemUnit;
use App\Modules\Inventory\Models\TechnicianItemAssignment;

class DeploymentController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = ItemDeployment::query()
            ->with(['item.group', 'technician', 'creator'])
            ->latest();

        if (($user->role ?? null) === 'technician') {
            $query->where('technician_id', $user->id);
        }

        $deployments = $query->paginate(30);

        return view('inventory::deployments.index', compact('deployments'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            // Admin can deploy on behalf of a technician (optional)
            'technician_id' => ['nullable','exists:users,id'],

            'item_id' => ['required','exists:inventory_items,id'],

            // bulk deploy
            'qty' => ['nullable','integer','min:1'],

            // serialized deploy
            'unit_id' => ['nullable','integer'],

            'site_code' => ['nullable','string','max:100'],
            'site_name' => ['required','string','max:255'],
            'reference' => ['nullable','string','max:255'],
            'notes' => ['nullable','string'],
        ]);

        $technicianId = ($user->role ?? null) === 'technician'
            ? $user->id
            : ($data['technician_id'] ?? $user->id);

        DB::transaction(function () use ($data, $technicianId, $user) {
            $item = Item::lockForUpdate()->findOrFail($data['item_id']);

            // Ensure assignment exists + lock it
            $assignment = TechnicianItemAssignment::query()
                ->where('technician_id', $technicianId)
                ->where('item_id', $item->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            // SERIALIZED DEPLOY
            if ($item->has_serial) {
                $unitId = $data['unit_id'] ?? null;
                if (!$unitId) {
                    abort(422, "unit_id is required for serialized deploy: {$item->name}");
                }

                $unit = ItemUnit::query()
                    ->where('id', $unitId)
                    ->where('item_id', $item->id)
                    ->where('status', 'assigned')
                    ->where('assigned_to', $technicianId)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Create deployment row
                ItemDeployment::create([
                    'technician_id' => $technicianId,
                    'item_id' => $item->id,
                    'qty' => 1,
                    'site_code' => $data['site_code'] ?? null,
                    'site_name' => $data['site_name'],
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $user->id,
                ]);

                // Update assignment deployed count
                if ($assignment->availableToDeploy() < 1) {
                    abort(422, "No available allocated units to deploy for {$item->name}");
                }
                $assignment->increment('qty_deployed', 1);

                // Update unit "store record" (status change)
                $unit->update([
                    'status' => 'deployed',
                    'deployed_site_code' => $data['site_code'] ?? null,
                    'deployed_site_name' => $data['site_name'],
                    'deployed_at' => now(),
                ]);

                InventoryLog::create([
                    'action' => 'deployed',
                    'item_id' => $item->id,
                    'item_unit_id' => $unit->id,
                    'qty' => null,
                    'serial_no' => $unit->serial_no,
                    'from_user_id' => $technicianId,
                    'to_user_id' => null,
                    'site_code' => $data['site_code'] ?? null,
                    'site_name' => $data['site_name'],
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $user->id,
                ]);

                return;
            }

            // BULK DEPLOY
            $qty = (int)($data['qty'] ?? 0);
            if ($qty < 1) {
                abort(422, "qty is required for bulk deploy: {$item->name}");
            }

            if ($qty > $assignment->availableToDeploy()) {
                abort(422, "Not enough allocated quantity to deploy for {$item->name}");
            }

            ItemDeployment::create([
                'technician_id' => $technicianId,
                'item_id' => $item->id,
                'qty' => $qty,
                'site_code' => $data['site_code'] ?? null,
                'site_name' => $data['site_name'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // Deployed increments on assignment (store qty already reduced at assignment time)
            $assignment->increment('qty_deployed', $qty);

            InventoryLog::create([
                'action' => 'deployed',
                'item_id' => $item->id,
                'item_unit_id' => null,
                'qty' => $qty,
                'serial_no' => null,
                'from_user_id' => $technicianId,
                'to_user_id' => null,
                'site_code' => $data['site_code'] ?? null,
                'site_name' => $data['site_name'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);
        });

        return back()->with('success', 'Deployment recorded (log created, unit/store records updated).');
    }
}
