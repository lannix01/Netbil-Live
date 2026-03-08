<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class ControlPanelController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('control.panel', compact('users'));
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'is_active' => $request->has('is_active'),
        ]);
        return response()->json(['status' => 'success', 'message' => 'User updated successfully']);
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->password = bcrypt('defaultpassword'); // reset to default
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Password reset successfully']);
    }
}
