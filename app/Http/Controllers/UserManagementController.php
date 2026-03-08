<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class UserManagementController extends Controller
{
    public function index()
    {
        return view('control.panel', [
            'users' => User::latest()->get()
        ]);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'required|string',
            'email'     => 'required|email',
            'phone'     => 'nullable|string',
            'role'      => 'required|string',
            'is_active' => 'required|boolean',
            'can_login' => 'required|boolean',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully'
        ]);
    }

    public function toggleLogin(User $user)
    {
        $user->can_login = ! $user->can_login;
        $user->save();

        // Force logout if disabled
        if (! $user->can_login) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return response()->json([
            'can_login' => $user->can_login
        ]);
    }

    public function resetPassword(User $user)
    {
        Password::sendResetLink(['email' => $user->email]);

        return response()->json([
            'message' => 'Password reset email sent'
        ]);
    }
}
