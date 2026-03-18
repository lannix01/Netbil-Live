<?php

namespace App\Modules\Inventory\Http\Middleware;

use App\Modules\Inventory\Support\InventoryAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventoryPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('inventory')->user();
        $routeName = (string) ($request->route()?->getName() ?? '');
        $requiredPermission = InventoryAccess::permissionForRoute($routeName);

        if ($requiredPermission !== null && !InventoryAccess::allows($user, $requiredPermission)) {
            $message = 'You do not have permission to access this page.';

            if ($request->expectsJson() || !$request->isMethod('GET')) {
                abort(403, $message);
            }

            $fallbackRoute = InventoryAccess::landingRouteName($user);

            if ($fallbackRoute !== null && !$request->routeIs($fallbackRoute)) {
                return redirect()->route($fallbackRoute)->with('warning', $message);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
