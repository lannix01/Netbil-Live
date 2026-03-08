<?php

namespace App\Modules\Inventory\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class InventoryPasswordController extends Controller
{
    public function showChange()
    {
        return view('inventory::auth.change_password');
    }

    public function update(Request $request)
    {
        $user = auth('inventory')->user();

        $data = $request->validate([
            'current_password' => ['required','string'],
            'password' => ['required','string','min:8','confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = Hash::make($data['password']);
        $user->inventory_force_password_change = false;
        $user->inventory_password_changed_at = now();
        $user->save();

        // Role-based landing after password change
        $role = strtolower((string)($user->inventory_role ?? ''));

        if ($role === 'technician') {
            return redirect()
                ->route('inventory.tech.items.index')
                ->with('success', 'Password updated.');
        }

        return redirect()
            ->route('inventory.dashboard')
            ->with('success', 'Password updated.');
    }
}
