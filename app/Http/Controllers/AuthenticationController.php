<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AuthenticationController extends Controller
{
    public function index()
    {
        $unverifiedUsers = User::whereNull('email_verified_at')->get();
        $verifiedUsers = User::whereNotNull('email_verified_at')->get();

        return view('admin.authentication', compact('unverifiedUsers', 'verifiedUsers'));
    }

    public function verify(User $user)
    {
        $user->email_verified_at = now();
        $user->save();

        return redirect()->route('authentication')->with('success', 'User verified!');
    }

    public function destroy(User $user)
    {
        // Only delete unverified users
        if ($user->email_verified_at) {
            return redirect()->route('authentication')->with('success', 'Cannot delete verified user.');
        }

        $user->delete();

        return redirect()->route('authentication')->with('success', 'Unverified user deleted successfully.');
    }
}
