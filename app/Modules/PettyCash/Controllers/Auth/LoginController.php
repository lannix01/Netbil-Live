<?php

namespace App\Modules\PettyCash\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Modules\PettyCash\Models\PettyUser;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::guard('petty')->check()) {
            return redirect()->route('petty.dashboard');
        }

        return view('pettycash::auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Only allow active users
        $user = PettyUser::where('email', $credentials['email'])->first();
        if (!$user || !$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Account not found or disabled.',
            ]);
        }

        if (!Auth::guard('petty')->attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password'], 'is_active' => 1],
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        $request->session()->regenerate();

        // Save last login
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('petty.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('petty')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('petty.login');
    }
}
