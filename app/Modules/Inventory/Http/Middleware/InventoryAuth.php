<?php

namespace App\Modules\Inventory\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventoryAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('inventory')->check()) {
            return redirect()->route('inventory.auth.login');
        }

        $user = auth('inventory')->user();

        if (!$user->inventory_enabled) {
            auth('inventory')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('inventory.auth.login')
                ->withErrors(['email' => 'Inventory access disabled.']);
        }

        return $next($request);
    }
}