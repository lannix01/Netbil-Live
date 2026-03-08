<?php

namespace App\Modules\PettyCash\Middleware;

use Closure;
use Illuminate\Http\Request;

class PettyAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth('petty')->check()) {
            return redirect()->route('petty.login');
        }

        return $next($request);
    }
}
