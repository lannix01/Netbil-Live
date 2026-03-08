<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('superadmin.login'); // create this blade
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('superadmin')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/authentication');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('superadmin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('superadmin.login');
    }
}
