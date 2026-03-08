<?php

namespace App\Modules\PettyCash\Middleware;

use App\Modules\PettyCash\Models\PettyApiToken;
use App\Modules\PettyCash\Support\ApiEnvelope;
use Closure;
use Illuminate\Http\Request;

class PettyApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        $plainToken = (string) $request->bearerToken();
        if ($plainToken === '') {
            return $this->unauthenticatedResponse($request);
        }

        $hashedToken = hash('sha256', $plainToken);

        $apiToken = PettyApiToken::query()
            ->with('user')
            ->where('token', $hashedToken)
            ->active()
            ->first();

        if (!$apiToken || !$apiToken->user || !$apiToken->user->is_active) {
            return $this->unauthenticatedResponse($request);
        }

        $idleMinutes = (int) config('pettycash.api.token_idle_ttl_minutes', 0);
        if ($idleMinutes > 0 && $apiToken->last_used_at && $apiToken->last_used_at->lt(now()->subMinutes($idleMinutes))) {
            $this->revokeToken($apiToken);
            return $this->unauthenticatedResponse($request);
        }

        $updates = [
            'last_used_at' => now(),
        ];

        if (PettyApiToken::supportsColumn('last_ip')) {
            $updates['last_ip'] = $request->ip();
        }
        if (PettyApiToken::supportsColumn('last_user_agent')) {
            $updates['last_user_agent'] = substr((string) $request->userAgent(), 0, 255);
        }

        $refreshOnUse = (bool) config('pettycash.api.token_refresh_on_use', false);
        $ttlDays = (int) config('pettycash.api.token_ttl_days', 30);
        if ($refreshOnUse && $ttlDays > 0) {
            $updates['expires_at'] = now()->addDays($ttlDays);
        }

        $apiToken->forceFill($updates)->save();

        $request->attributes->set('pettyUser', $apiToken->user);
        $request->attributes->set('pettyApiToken', $apiToken);
        $request->setUserResolver(fn () => $apiToken->user);

        return $next($request);
    }

    private function unauthenticatedResponse(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
            'errors' => (object) [],
            'meta' => (object) ApiEnvelope::meta($request),
        ], 401);
    }

    private function revokeToken(PettyApiToken $token): void
    {
        if (PettyApiToken::supportsColumn('revoked_at')) {
            $token->forceFill(['revoked_at' => now()])->save();
            return;
        }

        $token->delete();
    }
}
