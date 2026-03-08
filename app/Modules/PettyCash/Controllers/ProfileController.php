<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\PettyApiToken;
use App\Modules\PettyCash\Models\PettyUser;
use App\Modules\PettyCash\Support\PettyAccess;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function index()
    {
        $currentUser = auth('petty')->user();
        abort_unless($currentUser, 403);

        $isAdmin = PettyAccess::isAdmin($currentUser);

        $users = collect();
        $otherUsers = collect();
        if ($isAdmin) {
            $users = PettyUser::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get();

            $otherUsers = $users->where('id', '!=', $currentUser->id)->values();
        }

        $tokenQuery = PettyApiToken::query()
            ->active()
            ->with('user:id,name,email,role')
            ->orderByDesc('last_used_at')
            ->orderByDesc('id');

        if (!$isAdmin) {
            $tokenQuery->where('petty_user_id', $currentUser->id);
        }

        $activeSessions = $tokenQuery
            ->limit(500)
            ->get()
            ->map(function (PettyApiToken $token) use ($users) {
                /** @var PettyUser|null $user */
                $user = $token->user ?: $users->firstWhere('id', $token->petty_user_id);

                $deviceParts = array_filter([
                    PettyApiToken::supportsColumn('device_platform') ? (string) ($token->device_platform ?? '') : '',
                    (string) ($token->name ?? ''),
                ]);
                $deviceName = $deviceParts ? implode(' | ', $deviceParts) : '-';

                $agent = PettyApiToken::supportsColumn('last_user_agent')
                    ? (string) ($token->last_user_agent ?? '')
                    : '';

                $lastSeen = $token->last_used_at ?: $token->created_at;

                return [
                    'session_id' => (string) $token->id,
                    'petty_user_id' => (int) $token->petty_user_id,
                    'name' => $user?->name ?? ('User #' . $token->petty_user_id),
                    'email' => $user?->email ?? '-',
                    'role' => $user?->role ?? '-',
                    'ip_address' => PettyApiToken::supportsColumn('last_ip') ? (string) ($token->last_ip ?: '-') : '-',
                    'device_name' => Str::limit($deviceName, 60),
                    'user_agent' => $agent !== '' ? Str::limit($agent, 100) : '-',
                    'last_activity_at' => $lastSeen,
                    'expires_at' => $token->expires_at,
                    'is_current' => false,
                ];
            })
            ->values();

        $currentUserSessions = $activeSessions
            ->where('petty_user_id', (int) $currentUser->id)
            ->values();

        return view('pettycash::profile.index', [
            'currentUser' => $currentUser,
            'isAdmin' => $isAdmin,
            'otherUsers' => $otherUsers,
            'activeSessions' => $activeSessions,
            'currentUserSessions' => $currentUserSessions,
            'stats' => [
                'total_users' => $isAdmin ? $users->count() : null,
                'active_users' => $isAdmin ? $users->where('is_active', true)->count() : null,
                'active_sessions' => $activeSessions->count(),
                'my_sessions' => $currentUserSessions->count(),
            ],
        ]);
    }
}
