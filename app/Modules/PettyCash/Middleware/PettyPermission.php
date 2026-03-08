<?php

namespace App\Modules\PettyCash\Middleware;

use App\Modules\PettyCash\Support\PettyAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PettyPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('petty')->user();
        $routeName = (string) ($request->route()?->getName() ?? '');
        $requiredPermission = PettyAccess::permissionForRoute($routeName);

        if ($requiredPermission !== null && !PettyAccess::allows($user, $requiredPermission)) {
            $message = 'You do not have permission to access this page.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 403);
            }

            if ($routeName === 'petty.dashboard' && PettyAccess::allows($user, 'profile.view')) {
                return redirect()->route('petty.profile.index')->with('error', $message);
            }

            if ($routeName !== 'petty.dashboard' && PettyAccess::allows($user, 'dashboard.view')) {
                return redirect()->route('petty.dashboard')->with('error', $message);
            }

            if ($routeName !== 'petty.profile.index' && PettyAccess::allows($user, 'profile.view')) {
                return redirect()->route('petty.profile.index')->with('error', $message);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
