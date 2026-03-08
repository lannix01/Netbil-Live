<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only allow a specific email to access
        if (!Auth::check() || Auth::user()->email !== 'marcep.agency@gmail.com') {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
