<?php

namespace App\Modules\Inventory\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ItemGroupController extends Controller
{
    public function index()
    {
        $groups = DB::table('inventory_item_groups')->orderByDesc('id')->paginate(20);
        return view('inventory::item_groups.index', compact('groups'));
    }

    public function create()
    {
        return view('inventory::item_groups.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'is_active' => ['nullable','boolean'],
        ]);

        DB::table('inventory_item_groups')->insert([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool)($data['is_active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('inventory.item-groups.index')->with('success', 'Group created.');
    }

    public function edit($id)
    {
        $group = DB::table('inventory_item_groups')->where('id', $id)->first();
        abort_if(!$group, 404);
        return view('inventory::item_groups.edit', compact('group'));
    }

    public function update(Request $request, $id)
    {
        $group = DB::table('inventory_item_groups')->where('id', $id)->first();
        abort_if(!$group, 404);

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'is_active' => ['nullable','boolean'],
        ]);

        DB::table('inventory_item_groups')->where('id', $id)->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool)($data['is_active'] ?? true),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Group updated.');
    }

    public function destroy($id)
    {
        DB::table('inventory_item_groups')->where('id', $id)->delete();
        return back()->with('success', 'Group deleted.');
    }
}
