<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthVerificationController extends Controller
{
    public function index()
    {
        $unverifiedUsers = User::whereNull('email_verified_at')->get();
        $verifiedUsers = User::whereNotNull('email_verified_at')->get();

        return view('admin.authentication', compact(
            'unverifiedUsers',
            'verifiedUsers'
        ));
    }

    public function verify(User $user)
    {
        $user->email_verified_at = now();
        $user->save();

        return back()->with('success', 'User verified successfully.');
    }
}