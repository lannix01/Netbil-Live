<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NgrokSkipWarning
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only add header when running locally
        if (app()->environment('local')) {
            $response->headers->set('ngrok-skip-browser-warning', 'true');
        }

        return $response;
    }
}
