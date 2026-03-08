<?php

namespace App\Modules\Inventory\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->get('q', ''));

        $items = DB::table('inventory_items')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('sku', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('inventory::items.index', compact('items', 'q'));
    }

    public function create()
    {
        $groups = DB::table('inventory_item_groups')->where('is_active', 1)->orderBy('name')->get();
        return view('inventory::items.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_group_id' => ['required','integer'],
            'name' => ['required','string','max:255'],
            'sku' => ['nullable','string','max:100'],
            'unit' => ['nullable','string','max:50'],
            'description' => ['nullable','string'],
            'has_serial' => ['nullable','boolean'],
            'reorder_level' => ['nullable','integer','min:0'],
            'qty_on_hand' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ]);

        DB::table('inventory_items')->insert([
            'item_group_id' => (int)$data['item_group_id'],
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'unit' => $data['unit'] ?? 'pcs',
            'description' => $data['description'] ?? null,
            'has_serial' => (bool)($data['has_serial'] ?? false),
            'reorder_level' => (int)($data['reorder_level'] ?? 0),
            'qty_on_hand' => (int)($data['qty_on_hand'] ?? 0),
            'is_active' => (bool)($data['is_active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('inventory.items.index')->with('success', 'Item created.');
    }

    public function edit($id)
    {
        $item = DB::table('inventory_items')->where('id', $id)->first();
        abort_if(!$item, 404);

        $groups = DB::table('inventory_item_groups')->where('is_active', 1)->orderBy('name')->get();

        return view('inventory::items.edit', compact('item', 'groups'));
    }

    public function update(Request $request, $id)
    {
        $item = DB::table('inventory_items')->where('id', $id)->first();
        abort_if(!$item, 404);

        $data = $request->validate([
            'item_group_id' => ['required','integer'],
            'name' => ['required','string','max:255'],
            'sku' => ['nullable','string','max:100'],
            'unit' => ['nullable','string','max:50'],
            'description' => ['nullable','string'],
            'has_serial' => ['nullable','boolean'],
            'reorder_level' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ]);

        DB::table('inventory_items')->where('id', $id)->update([
            'item_group_id' => (int)$data['item_group_id'],
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'unit' => $data['unit'] ?? 'pcs',
            'description' => $data['description'] ?? null,
            'has_serial' => (bool)($data['has_serial'] ?? false),
            'reorder_level' => (int)($data['reorder_level'] ?? 0),
            'is_active' => (bool)($data['is_active'] ?? true),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Item updated.');
    }

    public function destroy($id)
    {
        DB::table('inventory_items')->where('id', $id)->delete();
        return back()->with('success', 'Item deleted.');
    }

    public function lowStock()
    {
        $items = DB::table('inventory_items')
            ->where('is_active', 1)
            ->whereColumn('qty_on_hand', '<=', 'reorder_level')
            ->orderBy('name')
            ->paginate(50);

        return view('inventory::alerts.low_stock', compact('items'));
    }
}
