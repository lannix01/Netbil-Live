<?php

namespace App\Modules\Inventory\Http\Controllers\Inventory;

use App\Modules\Inventory\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        // Inventory guard user
        $user = auth('inventory')->user();

        $query = ItemDeployment::query()
            ->with(['item.group', 'technician', 'creator'])
            ->latest();

        // Safety: if a technician ever reaches index, restrict to their own deployments
        if (($user->inventory_role ?? null) === 'technician') {
            $query->where('technician_id', $user->id);
        }

        $deployments = $query->paginate(30)->withQueryString();

        return view('inventory::deployments.index', compact('deployments'));
    }

    public function store(Request $request)
    {
        $user = auth('inventory')->user();
        $role = (string)($user->inventory_role ?? '');

        $data = $request->validate([
            // Admin can deploy on behalf of a technician (nullable)
            // IMPORTANT: now points to inventory_users table
            'technician_id' => ['nullable', 'integer', 'exists:inventory_users,id'],

            'item_id' => ['required', 'integer', 'exists:inventory_items,id'],

            // bulk deploy
            'qty' => ['nullable', 'integer', 'min:1'],

            // serialized deploy
            'unit_id' => ['nullable', 'integer'],

            'site_code' => ['nullable', 'string', 'max:100'],
            'site_name' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        /**
         * Role enforcement:
         * - Technician: ALWAYS deploy under themselves (ignore technician_id)
         * - Admin: can deploy under selected technician_id; fallback to self if not provided
         */
        $technicianId = ($role === 'technician')
            ? $user->id
            : ((int)($data['technician_id'] ?? $user->id));

        DB::transaction(function () use ($data, $technicianId, $user, $role) {
            // Lock item row
            $item = Item::lockForUpdate()->findOrFail($data['item_id']);

            // Lock assignment row
            $assignment = TechnicianItemAssignment::query()
                ->where('technician_id', $technicianId)
                ->where('item_id', $item->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$assignment) {
                abort(422, "No active assignment found for this technician and item.");
            }

            /**
             * SERIALIZED DEPLOY
             */
            if ((bool)$item->has_serial) {
                $unitId = $data['unit_id'] ?? null;
                if (!$unitId) {
                    abort(422, "unit_id is required for serialized deploy: {$item->name}");
                }

                // Make sure they have available allocation
                if ($assignment->availableToDeploy() < 1) {
                    abort(422, "No available allocated units to deploy for {$item->name}");
                }

                // Lock the unit and ensure it belongs to that technician and is in assigned state
                $unit = ItemUnit::query()
                    ->where('id', $unitId)
                    ->where('item_id', $item->id)
                    ->where('status', 'assigned')
                    ->where('assigned_to', $technicianId)
                    ->lockForUpdate()
                    ->first();

                if (!$unit) {
                    abort(422, "Selected serial is not available for this technician.");
                }

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

                // Increment deployed count
                $assignment->increment('qty_deployed', 1);

                // Update unit status to deployed
                $unit->update([
                    'status' => 'deployed',
                    'deployed_site_code' => $data['site_code'] ?? null,
                    'deployed_site_name' => $data['site_name'],
                    'deployed_at' => now(),
                ]);

                // Log
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

            /**
             * BULK DEPLOY
             */
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

            // deployed increments on assignment (store qty already reduced at assignment time)
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
