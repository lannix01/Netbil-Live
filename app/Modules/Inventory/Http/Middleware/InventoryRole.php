<?php

namespace App\Modules\Inventory\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventoryRole
{
    /**
     * Usage:
     *   ->middleware('inventory.role:admin')
     *   ->middleware('inventory.role:technician')
     *   ->middleware('inventory.role:admin,technician')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth('inventory')->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        $role = strtolower(trim((string)($user->inventory_role ?? '')));
        $roles = array_map(fn($r) => strtolower(trim((string)$r)), $roles);

        if ($role === '' || empty($roles) || !in_array($role, $roles, true)) {
            abort(403, 'Forbidden.');
        }

        return $next($request);
    }
}
