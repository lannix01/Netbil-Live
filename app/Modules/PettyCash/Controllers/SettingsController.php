<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\PettyNotificationSetting;
use App\Modules\PettyCash\Models\PettyUser;
use App\Modules\PettyCash\Models\PettyUserPermission;
use App\Modules\PettyCash\Support\PettyAccess;
use App\Services\Sms\AdvantaSmsService;
use App\Services\Sms\AmazonsSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth('petty')->user();
        abort_unless(PettyAccess::isAdmin($currentUser), 403);

        $users = PettyUser::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $selectedUserId = (int) $request->integer('user', 0);
        $selectedUser = $users->firstWhere('id', $selectedUserId) ?: $users->first();

        $selectedExplicitPermissions = [];
        $selectedEffectivePermissions = [];

        if ($selectedUser) {
            $selectedExplicitPermissions = PettyAccess::explicitPermissionsForUser($selectedUser) ?? [];
            $selectedEffectivePermissions = PettyAccess::permissionsForUser($selectedUser);
        }

        return view('pettycash::settings.index', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'supportsPhoneNo' => Schema::hasColumn('petty_users', 'phone_no'),
            'roleOptions' => PettyAccess::roleOptions(),
            'permissionCatalog' => PettyAccess::permissionCatalog(),
            'selectedExplicitPermissions' => $selectedExplicitPermissions,
            'selectedEffectivePermissions' => $selectedEffectivePermissions,
        ]);
    }

    public function updateUser(Request $request, PettyUser $user)
    {
        $currentUser = auth('petty')->user();
        abort_unless(PettyAccess::isAdmin($currentUser), 403);

        $roleValues = array_keys(PettyAccess::roleOptions());
        $hasPhoneNo = Schema::hasColumn('petty_users', 'phone_no');

        $data = $request->validateWithBag('updateUser', [
            'role' => ['required', 'string', 'in:' . implode(',', $roleValues)],
            'is_active' => ['nullable', 'boolean'],
            'phone_no' => $hasPhoneNo
                ? ['nullable', 'string', 'max:32']
                : ['nullable'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $targetRole = PettyAccess::normalizeRole((string) $data['role']);
        $targetActive = (bool) ($data['is_active'] ?? false);

        if ((int) $user->id === (int) $currentUser->id && $targetRole !== 'admin') {
            return back()->with('error', 'You cannot remove your own admin role.');
        }

        if ((int) $user->id === (int) $currentUser->id && !$targetActive) {
            return back()->with('error', 'You cannot disable your own account.');
        }

        $user->fill([
            'role' => $targetRole,
            'is_active' => $targetActive,
        ]);
        if (Schema::hasColumn('petty_users', 'phone_no')) {
            $user->phone_no = trim((string) ($data['phone_no'] ?? '')) ?: null;
        }

        if (!empty($data['password'])) {
            $user->password = Hash::make((string) $data['password']);
        }

        $user->save();

        if (!Schema::hasTable('petty_user_permissions')) {
            return redirect()
                ->route('petty.settings.index', ['user' => $user->id])
                ->with('error', 'Role updated, but permissions table is missing. Run migrations and try again.');
        }

        if ($targetRole === 'admin') {
            PettyUserPermission::query()
                ->where('petty_user_id', $user->id)
                ->delete();
        } else {
            $permissions = PettyAccess::withImplicitViewPermissions((array) ($data['permissions'] ?? []));

            if (!in_array('profile.view', $permissions, true)) {
                $permissions[] = 'profile.view';
            }

            sort($permissions);

            $profile = PettyUserPermission::query()->firstOrNew([
                'petty_user_id' => $user->id,
            ]);

            if (!$profile->exists) {
                $profile->created_by = $currentUser->id;
            }

            $profile->permissions = $permissions;
            $profile->updated_by = $currentUser->id;
            $profile->save();
        }

        return redirect()
            ->route('petty.settings.index', ['user' => $user->id])
            ->with('success', 'User role and permissions saved.');
    }

    public function storeUser(Request $request)
    {
        $currentUser = auth('petty')->user();
        abort_unless(PettyAccess::isAdmin($currentUser), 403);

        $roleValues = array_keys(PettyAccess::roleOptions());
        $hasPhoneNo = Schema::hasColumn('petty_users', 'phone_no');

        $data = $request->validateWithBag('createUser', [
            'create.name' => ['required', 'string', 'max:120'],
            'create.email' => ['required', 'email', 'max:255', 'unique:petty_users,email'],
            'create.phone_no' => $hasPhoneNo
                ? ['required', 'regex:/^07\d{8}$/']
                : ['nullable'],
            'create.role' => ['required', 'string', 'in:' . implode(',', $roleValues)],
            'create.password' => ['required', 'string', 'min:6', 'confirmed'],
            'create.is_active' => ['nullable', 'boolean'],
            'create.send_login_sms' => ['nullable', 'boolean'],
        ], [
            'create.phone_no.required' => 'Phone number is required.',
            'create.phone_no.regex' => 'Phone number must be in 07XXXXXXXX format.',
        ]);

        $payload = (array) ($data['create'] ?? []);
        $role = PettyAccess::normalizeRole((string) ($payload['role'] ?? 'viewer'));
        $plainPassword = (string) ($payload['password'] ?? '');

        try {
            $createPayload = [
                'name' => (string) ($payload['name'] ?? ''),
                'email' => strtolower(trim((string) ($payload['email'] ?? ''))),
                'password' => Hash::make($plainPassword),
                'role' => $role,
                'is_active' => (bool) ($payload['is_active'] ?? true),
            ];
            if ($hasPhoneNo) {
                $createPayload['phone_no'] = trim((string) ($payload['phone_no'] ?? '')) ?: null;
            }

            $user = PettyUser::query()->create($createPayload);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Unable to create user. If role columns were not migrated yet, run migrations and try again.');
        }

        $sendLoginSms = true;
        if (!$sendLoginSms) {
            return redirect()
                ->route('petty.settings.index', ['user' => $user->id])
                ->with('success', 'User created. You can now fine-tune their permissions.');
        }

        $smsResult = $this->sendLoginSmsForCreatedUser($user, $plainPassword);
        $smsMessage = (string) ($smsResult['message'] ?? '');
        $smsSent = (bool) ($smsResult['sent'] ?? false);

        return redirect()
            ->route('petty.settings.index', ['user' => $user->id])
            ->with($smsSent ? 'success' : 'error', $smsSent
                ? ('User created and login SMS sent to ' . $user->name . '.')
                : ('User created, but login SMS failed: ' . ($smsMessage !== '' ? $smsMessage : 'unknown error.'))
            );
    }

    /**
     * @return array{sent:bool,message:string}
     */
    private function sendLoginSmsForCreatedUser(PettyUser $user, string $plainPassword): array
    {
        if (!Schema::hasColumn('petty_users', 'phone_no')) {
            return [
                'sent' => false,
                'message' => 'phone field is missing (run migrations).',
            ];
        }

        if (!$user->is_active) {
            return [
                'sent' => false,
                'message' => 'user is disabled.',
            ];
        }

        $phone = $this->normalizePhone((string) ($user->phone_no ?? ''));
        if ($phone === '') {
            return [
                'sent' => false,
                'message' => 'user phone number is missing or invalid.',
            ];
        }

        if (trim($plainPassword) === '') {
            return [
                'sent' => false,
                'message' => 'password was empty.',
            ];
        }

        $settings = PettyNotificationSetting::current();
        if (!$settings->sms_enabled) {
            return [
                'sent' => false,
                'message' => 'SMS is disabled in notification settings.',
            ];
        }

        $gateway = strtolower((string) ($settings->sms_gateway ?: 'advanta'));
        $service = $gateway === 'amazons'
            ? app(AmazonsSmsService::class)
            : app(AdvantaSmsService::class);

        $loginUrl = route('petty.login');
        $displayName = trim((string) ($user->name ?: 'User'));
        $message = sprintf(
            'Hello %s, PettyCash login details: Email %s, Password %s, Login link %s',
            $displayName,
            (string) $user->email,
            $plainPassword,
            $loginUrl
        );

        try {
            $lastFailure = 'SMS gateway rejected the message.';
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $response = $service->send($phone, $message);
                if ($this->isSmsSuccess((array) $response)) {
                    $lastFailure = '';
                    break;
                }

                $lastFailure = 'SMS gateway rejected the message.';
            }

            if ($lastFailure !== '') {
                return [
                    'sent' => false,
                    'message' => $lastFailure,
                ];
            }
        } catch (\Throwable $e) {
            return [
                'sent' => false,
                'message' => $e->getMessage(),
            ];
        }

        if (Schema::hasColumn('petty_users', 'login_sms_sent_at')) {
            $user->login_sms_sent_at = now();
            $user->save();
        }

        return [
            'sent' => true,
            'message' => '',
        ];
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
