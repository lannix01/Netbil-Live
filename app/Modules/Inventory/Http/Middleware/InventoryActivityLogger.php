<?php

namespace App\Modules\Inventory\Http\Middleware;

use App\Modules\Inventory\Support\InventoryActivity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventoryActivityLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = auth('inventory')->user();
        if (!$user) {
            return $response;
        }

        if (!$request->isMethod('GET')) {
            return $response;
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return $response;
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        if ($routeName !== '' && str_starts_with($routeName, 'inventory.auth.')) {
            return $response;
        }

        InventoryActivity::log($user, 'viewed', $request);

        return $response;
    }
}
