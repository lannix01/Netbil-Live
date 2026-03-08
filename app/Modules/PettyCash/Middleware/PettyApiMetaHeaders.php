<?php

namespace App\Modules\PettyCash\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PettyApiMetaHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = trim((string) $request->headers->get('X-Request-Id', ''));
        if ($requestId === '') {
            $requestId = (string) Str::uuid();
        }
        $apiVersion = (string) config('pettycash.api.version', 'v1');

        $request->attributes->set('petty_request_id', $requestId);
        $request->attributes->set('petty_api_version', $apiVersion);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-Petty-Api-Version', $apiVersion);

        $deprecated = (bool) config('pettycash.api.deprecated', false);
        if ($deprecated) {
            $response->headers->set('Deprecation', 'true');
        }

        $sunsetAt = trim((string) config('pettycash.api.sunset_at', ''));
        if ($sunsetAt !== '') {
            $response->headers->set('Sunset', $sunsetAt);
        }

        return $response;
    }
}
