<?php

namespace App\Modules\Inventory\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventoryForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('inventory')->user();

        // Allow reaching the change password routes
        if ($request->routeIs('inventory.auth.password.change') || $request->routeIs('inventory.auth.password.update') || $request->routeIs('inventory.auth.logout')) {
            return $next($request);
        }

        if ($user && $user->inventory_force_password_change) {
            return redirect()->route('inventory.auth.password.change');
        }

        return $next($request);
    }
}