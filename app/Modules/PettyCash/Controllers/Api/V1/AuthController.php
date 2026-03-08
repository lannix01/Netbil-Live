<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\PettyApiToken;
use App\Modules\PettyCash\Models\PettyUser;
use App\Modules\PettyCash\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponder;

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'device_platform' => ['nullable', 'string', 'max:40'],
            'revoke_other_sessions' => ['nullable', 'boolean'],
        ]);

        $user = PettyUser::query()->where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->errorResponse('Invalid credentials.', 401);
        }

        if (!$user->is_active) {
            return $this->errorResponse('User account is inactive.', 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $plainToken = Str::random(80);
        $ttlDays = (int) config('pettycash.api.token_ttl_days', 30);
        $deviceName = trim((string) ($data['device_name'] ?? 'mobile'));
        $deviceId = trim((string) ($data['device_id'] ?? ''));
        $devicePlatform = trim((string) ($data['device_platform'] ?? ''));
        $revokeOtherSessions = (bool) ($data['revoke_other_sessions'] ?? false);

        if ($deviceName === '') {
            $deviceName = 'mobile';
        }
        if ($devicePlatform === '') {
            $devicePlatform = null;
        }
        if ($deviceId === '') {
            $deviceId = null;
        }

        $activeByUser = PettyApiToken::query()
            ->where('petty_user_id', $user->id)
            ->active();

        if ($revokeOtherSessions) {
            $this->revokeQuery($activeByUser);
        } else {
            $deviceScoped = (clone $activeByUser)->where('name', $deviceName);
            if ($deviceId && PettyApiToken::supportsColumn('device_id')) {
                $deviceScoped = (clone $activeByUser)->where('device_id', $deviceId);
            }
            $this->revokeQuery($deviceScoped);
        }

        $tokenPayload = [
            'petty_user_id' => $user->id,
            'name' => $deviceName,
            'token' => hash('sha256', $plainToken),
            'last_used_at' => now(),
            'expires_at' => $ttlDays > 0 ? now()->addDays($ttlDays) : null,
        ];
        if (PettyApiToken::supportsColumn('device_id')) {
            $tokenPayload['device_id'] = $deviceId;
        }
        if (PettyApiToken::supportsColumn('device_platform')) {
            $tokenPayload['device_platform'] = $devicePlatform;
        }
        if (PettyApiToken::supportsColumn('last_ip')) {
            $tokenPayload['last_ip'] = $request->ip();
        }
        if (PettyApiToken::supportsColumn('last_user_agent')) {
            $tokenPayload['last_user_agent'] = substr((string) $request->userAgent(), 0, 255);
        }

        $token = PettyApiToken::query()->create($tokenPayload);

        $this->enforceMaxActiveTokens($user->id);

        return $this->successResponse([
            'token_type' => 'Bearer',
            'access_token' => $plainToken,
            'expires_at' => optional($token->expires_at)->toIso8601String(),
            'user' => $this->mapUser($user),
            'session' => $this->mapSessionToken($token, $token->id),
        ], 'Login successful.');
    }

    public function logout(Request $request)
    {
        $token = $request->attributes->get('pettyApiToken');
        if ($token) {
            $this->revokeToken($token);
        }

        return $this->successResponse([], 'Logged out.');
    }

    public function logoutCurrent(Request $request)
    {
        $token = $request->attributes->get('pettyApiToken');
        if ($token) {
            $this->revokeToken($token);
        }

        return $this->successResponse([], 'Current session revoked.');
    }

    public function logoutAll(Request $request)
    {
        $user = $request->attributes->get('pettyUser');
        $currentToken = $request->attributes->get('pettyApiToken');
        $includeCurrent = (bool) $request->boolean('include_current', true);

        $query = PettyApiToken::query()
            ->where('petty_user_id', $user->id)
            ->active();

        if (!$includeCurrent && $currentToken) {
            $query->where('id', '!=', $currentToken->id);
        }

        $revokedCount = $this->revokeQuery($query);

        return $this->successResponse([
            'revoked_tokens' => $revokedCount,
            'include_current' => $includeCurrent,
        ], $includeCurrent ? 'All sessions revoked.' : 'Other sessions revoked.');
    }

    public function refresh(Request $request)
    {
        $data = $request->validate([
            'device_name' => ['nullable', 'string', 'max:100'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'device_platform' => ['nullable', 'string', 'max:40'],
            'revoke_other_sessions' => ['nullable', 'boolean'],
        ]);

        /** @var PettyUser|null $user */
        $user = $request->attributes->get('pettyUser');
        /** @var PettyApiToken|null $currentToken */
        $currentToken = $request->attributes->get('pettyApiToken');

        if (!$user || !$currentToken) {
            return $this->errorResponse('Unauthenticated.', 401);
        }

        $plainToken = Str::random(80);
        $ttlDays = (int) config('pettycash.api.token_ttl_days', 30);

        $deviceName = trim((string) ($data['device_name'] ?? $currentToken->name ?? 'mobile'));
        $deviceId = trim((string) ($data['device_id'] ?? (PettyApiToken::supportsColumn('device_id') ? ($currentToken->device_id ?? '') : '')));
        $devicePlatform = trim((string) ($data['device_platform'] ?? (PettyApiToken::supportsColumn('device_platform') ? ($currentToken->device_platform ?? '') : '')));
        $revokeOtherSessions = (bool) ($data['revoke_other_sessions'] ?? false);

        if ($deviceName === '') {
            $deviceName = 'mobile';
        }
        if ($deviceId === '') {
            $deviceId = null;
        }
        if ($devicePlatform === '') {
            $devicePlatform = null;
        }

        $activeByUser = PettyApiToken::query()
            ->where('petty_user_id', $user->id)
            ->where('id', '!=', $currentToken->id)
            ->active();

        if ($revokeOtherSessions) {
            $this->revokeQuery($activeByUser);
        } else {
            $deviceScoped = (clone $activeByUser)->where('name', $deviceName);
            if ($deviceId && PettyApiToken::supportsColumn('device_id')) {
                $deviceScoped = (clone $activeByUser)->where('device_id', $deviceId);
            }
            $this->revokeQuery($deviceScoped);
        }

        $tokenPayload = [
            'petty_user_id' => $user->id,
            'name' => $deviceName,
            'token' => hash('sha256', $plainToken),
            'last_used_at' => now(),
            'expires_at' => $ttlDays > 0 ? now()->addDays($ttlDays) : null,
        ];
        if (PettyApiToken::supportsColumn('device_id')) {
            $tokenPayload['device_id'] = $deviceId;
        }
        if (PettyApiToken::supportsColumn('device_platform')) {
            $tokenPayload['device_platform'] = $devicePlatform;
        }
        if (PettyApiToken::supportsColumn('last_ip')) {
            $tokenPayload['last_ip'] = $request->ip();
        }
        if (PettyApiToken::supportsColumn('last_user_agent')) {
            $tokenPayload['last_user_agent'] = substr((string) $request->userAgent(), 0, 255);
        }

        $token = PettyApiToken::query()->create($tokenPayload);
        $this->revokeToken($currentToken);
        $this->enforceMaxActiveTokens($user->id);

        return $this->successResponse([
            'token_type' => 'Bearer',
            'access_token' => $plainToken,
            'expires_at' => optional($token->expires_at)->toIso8601String(),
            'user' => $this->mapUser($user),
            'session' => $this->mapSessionToken($token, $token->id),
        ], 'Token refreshed.');
    }

    public function sessions(Request $request)
    {
        /** @var PettyUser $user */
        $user = $request->attributes->get('pettyUser');
        $currentToken = $request->attributes->get('pettyApiToken');
        $currentTokenId = (int) ($currentToken?->id ?? 0);

        $requestAllUsers = (bool) $request->boolean('all_users', false);
        $isAdmin = strtolower((string) ($user->role ?? '')) === 'admin';

        if ($requestAllUsers && !$isAdmin) {
            return $this->errorResponse('Forbidden: only admin can list all sessions.', 403);
        }

        $query = PettyApiToken::query()
            ->active()
            ->orderByDesc('last_used_at')
            ->orderByDesc('id');

        if ($requestAllUsers && $isAdmin) {
            $query->with('user:id,name,email,role');
            $targetUserId = (int) $request->integer('user_id', 0);
            if ($targetUserId > 0) {
                $query->where('petty_user_id', $targetUserId);
            }
        } else {
            $query->where('petty_user_id', $user->id);
        }

        $tokens = $query->get();

        return $this->successResponse([
            'scope' => $requestAllUsers && $isAdmin ? 'all_users' : 'current_user',
            'tokens' => $tokens->map(function (PettyApiToken $token) use ($currentTokenId, $requestAllUsers, $isAdmin) {
                $payload = $this->mapSessionToken($token, $currentTokenId);

                if ($requestAllUsers && $isAdmin) {
                    $payload['user'] = $token->relationLoaded('user') && $token->user
                        ? $this->mapUser($token->user)
                        : null;
                }

                return $payload;
            })->values(),
        ], 'Sessions fetched.');
    }

    public function revokeSession(Request $request, int $tokenId)
    {
        $user = $request->attributes->get('pettyUser');
        $currentToken = $request->attributes->get('pettyApiToken');
        $token = PettyApiToken::query()
            ->where('petty_user_id', $user->id)
            ->where('id', $tokenId)
            ->first();

        if (!$token) {
            return $this->errorResponse('Session not found.', 404);
        }

        $isCurrent = $currentToken && (int) $currentToken->id === (int) $token->id;
        $this->revokeToken($token);

        return $this->successResponse([
            'revoked_token_id' => (int) $tokenId,
            'revoked_current' => $isCurrent,
        ], 'Session revoked.');
    }

    public function me(Request $request)
    {
        $user = $request->attributes->get('pettyUser');

        return $this->successResponse([
            'user' => $this->mapUser($user),
        ]);
    }

    private function mapSessionToken(PettyApiToken $token, int $currentTokenId = 0): array
    {
        $payload = [
            'id' => $token->id,
            'name' => $token->name,
            'is_current' => $currentTokenId > 0 && $currentTokenId === (int) $token->id,
            'last_used_at' => optional($token->last_used_at)->toIso8601String(),
            'expires_at' => optional($token->expires_at)->toIso8601String(),
            'created_at' => optional($token->created_at)->toIso8601String(),
        ];

        if (PettyApiToken::supportsColumn('device_id')) {
            $payload['device_id'] = $token->device_id;
        }
        if (PettyApiToken::supportsColumn('device_platform')) {
            $payload['device_platform'] = $token->device_platform;
        }
        if (PettyApiToken::supportsColumn('last_ip')) {
            $payload['last_ip'] = $token->last_ip;
        }
        if (PettyApiToken::supportsColumn('last_user_agent')) {
            $payload['last_user_agent'] = $token->last_user_agent;
        }

        return $payload;
    }

    private function revokeToken(PettyApiToken $token): void
    {
        if (PettyApiToken::supportsColumn('revoked_at')) {
            $token->forceFill(['revoked_at' => now()])->save();
            return;
        }

        $token->delete();
    }

    private function revokeQuery($query): int
    {
        if (PettyApiToken::supportsColumn('revoked_at')) {
            return (int) $query->update(['revoked_at' => now()]);
        }

        return (int) $query->delete();
    }

    private function enforceMaxActiveTokens(int $userId): void
    {
        $max = (int) config('pettycash.api.max_active_tokens_per_user', 10);
        if ($max < 1) {
            return;
        }

        $overflowIds = PettyApiToken::query()
            ->where('petty_user_id', $userId)
            ->active()
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get(['id'])
            ->slice($max)
            ->pluck('id')
            ->values();

        if ($overflowIds->isEmpty()) {
            return;
        }

        $overflowQuery = PettyApiToken::query()->whereIn('id', $overflowIds->all());
        $this->revokeQuery($overflowQuery);
    }

    private function mapUser(PettyUser $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'last_login_at' => optional($user->last_login_at)->toIso8601String(),
        ];
    }
}
