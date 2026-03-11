<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\PettyApiToken;
use App\Modules\PettyCash\Models\PettyNotificationSetting;
use App\Modules\PettyCash\Models\PettyUser;
use App\Modules\PettyCash\Support\PettyAccess;
use App\Services\Sms\AdvantaSmsService;
use App\Services\Sms\AmazonsSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function index()
    {
        $currentUser = auth('petty')->user();
        abort_unless($currentUser, 403);

        $isAdmin = PettyAccess::isAdmin($currentUser);
        $supportsPhoneNo = Schema::hasColumn('petty_users', 'phone_no');
        $supportsLoginSmsTracking = Schema::hasColumn('petty_users', 'login_sms_sent_at');

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
            'supportsPhoneNo' => $supportsPhoneNo,
            'supportsLoginSmsTracking' => $supportsLoginSmsTracking,
            'stats' => [
                'total_users' => $isAdmin ? $users->count() : null,
                'active_users' => $isAdmin ? $users->where('is_active', true)->count() : null,
                'active_sessions' => $activeSessions->count(),
                'my_sessions' => $currentUserSessions->count(),
            ],
        ]);
    }

    public function sendLoginSms(PettyUser $user, Request $request)
    {
        $currentUser = auth('petty')->user();
        abort_unless($currentUser && PettyAccess::isAdmin($currentUser), 403);

        if (!Schema::hasColumn('petty_users', 'phone_no')) {
            return back()->with('error', 'User phone field is missing. Run migrations first.');
        }

        if (!$user->is_active) {
            return back()->with('error', 'Cannot send login SMS to a disabled user.');
        }

        $phone = $this->normalizePhone((string) ($user->phone_no ?? ''));
        if ($phone === '') {
            return back()->with('error', 'User phone number is required before sending login SMS.');
        }

        $settings = PettyNotificationSetting::current();
        if (!$settings->sms_enabled) {
            return back()->with('error', 'SMS is currently disabled in notification settings.');
        }

        $gateway = strtolower((string) ($settings->sms_gateway ?: 'advanta'));
        $service = $gateway === 'amazons'
            ? app(AmazonsSmsService::class)
            : app(AdvantaSmsService::class);

        $temporaryPassword = $this->generateTemporaryPassword(8);
        $loginUrl = route('petty.login');
        $displayName = trim((string) ($user->name ?: 'User'));
        $message = sprintf(
            'Hello %s, PettyCash login details: Email %s, Password %s, Login link %s',
            $displayName,
            (string) $user->email,
            $temporaryPassword,
            $loginUrl
        );

        $oldPasswordHash = (string) $user->password;
        $hasLoginSmsTracking = Schema::hasColumn('petty_users', 'login_sms_sent_at');
        $oldSmsSentAt = $hasLoginSmsTracking ? $user->login_sms_sent_at : null;

        $user->password = Hash::make($temporaryPassword);
        if ($hasLoginSmsTracking) {
            $user->login_sms_sent_at = now();
        }
        $user->save();

        try {
            $response = $service->send($phone, $message);
            if (!$this->isSmsSuccess((array) $response)) {
                throw new \RuntimeException('SMS gateway rejected the message.');
            }
        } catch (\Throwable $e) {
            $user->password = $oldPasswordHash;
            if ($hasLoginSmsTracking) {
                $user->login_sms_sent_at = $oldSmsSentAt;
            }
            $user->save();

            return back()->with('error', 'Failed to send login SMS: ' . $e->getMessage());
        }

        return back()->with('success', 'Login SMS sent to ' . $user->name . ' successfully.');
    }

    private function generateTemporaryPassword(int $length = 8): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $maxIndex = strlen($alphabet) - 1;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $maxIndex)];
        }

        return $password;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }
        $phone = preg_replace('/\D+/', '', $phone) ?: '';

        if (preg_match('/^07\d{8}$/', $phone)) {
            return '254' . substr($phone, 1);
        }
        if (preg_match('/^01\d{8}$/', $phone)) {
            return '254' . substr($phone, 1);
        }

        return $phone;
    }

    private function isSmsSuccess(array $response): bool
    {
        if (array_key_exists('success', $response)) {
            return (bool) $response['success'];
        }

        if (isset($response['responses'][0]['response-code'])) {
            return (string) $response['responses'][0]['response-code'] === '200';
        }

        if (isset($response['response-code'])) {
            return (string) $response['response-code'] === '200';
        }

        if (isset($response['status'])) {
            return in_array(strtolower((string) $response['status']), ['ok', 'success', 'sent'], true);
        }

        return false;
    }
}
