<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\PettyUser;
use App\Modules\PettyCash\Models\PettyUserPermission;
use App\Modules\PettyCash\Support\PettyAccess;
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

        $data = $request->validateWithBag('updateUser', [
            'role' => ['required', 'string', 'in:' . implode(',', $roleValues)],
            'is_active' => ['nullable', 'boolean'],
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

        $data = $request->validateWithBag('createUser', [
            'create.name' => ['required', 'string', 'max:120'],
            'create.email' => ['required', 'email', 'max:255', 'unique:petty_users,email'],
            'create.role' => ['required', 'string', 'in:' . implode(',', $roleValues)],
            'create.password' => ['required', 'string', 'min:6', 'confirmed'],
            'create.is_active' => ['nullable', 'boolean'],
        ]);

        $payload = (array) ($data['create'] ?? []);
        $role = PettyAccess::normalizeRole((string) ($payload['role'] ?? 'viewer'));

        try {
            $user = PettyUser::query()->create([
                'name' => (string) ($payload['name'] ?? ''),
                'email' => strtolower(trim((string) ($payload['email'] ?? ''))),
                'password' => Hash::make((string) ($payload['password'] ?? '')),
                'role' => $role,
                'is_active' => (bool) ($payload['is_active'] ?? true),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Unable to create user. If role columns were not migrated yet, run migrations and try again.');
        }

        return redirect()
            ->route('petty.settings.index', ['user' => $user->id])
            ->with('success', 'User created. You can now fine-tune their permissions.');
    }
}
