<?php

namespace App\Modules\Inventory\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class InventoryAuthController extends Controller
{
    public function showLogin()
    {
        return view('inventory::auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);

        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
            'inventory_enabled' => 1,
        ];

        if (!Auth::guard('inventory')->attempt($credentials, true)) {
            return back()
                ->withErrors(['email' => 'Invalid credentials or no Inventory access.'])
                ->withInput();
        }

        $request->session()->regenerate();

        $user = Auth::guard('inventory')->user();

        // Force password change if flagged
        if ($user->inventory_force_password_change) {
            return redirect()->route('inventory.auth.password.change');
        }

        // Role-based landing
        $role = strtolower((string)($user->inventory_role ?? ''));

        if ($role === 'technician') {
            return redirect()->route('inventory.tech.items.index');
        }

        return redirect()->route('inventory.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('inventory')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inventory.auth.login');
    }
}
