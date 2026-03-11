@extends('pettycash::layouts.app')

@section('title', 'Settings')

@push('styles')
<style>
    .wrap{max-width:none;margin:0}
    .grid{display:grid;grid-template-columns:minmax(280px,1fr) minmax(560px,2fr);gap:12px}
    @media(max-width:1080px){.grid{grid-template-columns:1fr}}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06)}
    .title{margin:0 0 4px;font-size:18px;font-weight:900}
    .muted{color:#667085;font-size:12px}
    .table-wrap{overflow:auto;border:1px solid #eef2f6;border-radius:12px;margin-top:10px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px;text-align:left;vertical-align:top}
    th{font-size:12px;color:#475467;white-space:nowrap}
    .badge{display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;font-size:11px;font-weight:800}
    .badge-ok{background:#ecfdf3;color:#027a48;border:1px solid #abefc6}
    .badge-off{background:#fef3f2;color:#b42318;border:1px solid #fecdca}
    .badge-role{background:#f2f4f7;color:#344054;border:1px solid #eaecf0}
    .btn-link{display:inline-block;padding:8px 10px;border:1px solid #d0d5dd;border-radius:10px;background:#fff;color:#344054;text-decoration:none;font-size:12px;font-weight:700}
    .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    @media(max-width:760px){.form-grid{grid-template-columns:1fr}}
    .field{display:flex;flex-direction:column;gap:6px}
    .field label{font-size:12px;color:#475467;font-weight:700}
    .input,.select{width:100%;padding:10px 12px;border:1px solid #d0d5dd;border-radius:10px;font-size:13px;background:#fff}
    .input:focus,.select:focus{outline:none;border-color:#7f56d9;box-shadow:0 0 0 4px rgba(127,86,217,.12)}
    .perm-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:12px}
    @media(max-width:900px){.perm-grid{grid-template-columns:1fr}}
    .perm-card{border:1px solid #eaecf0;border-radius:12px;padding:10px;background:#fcfcfd}
    .perm-head{font-size:13px;font-weight:900;margin:0 0 8px;color:#101828}
    .perm-list{display:grid;gap:6px}
    .perm-item{display:flex;gap:8px;align-items:flex-start;font-size:12px;color:#344054}
    .perm-item input{margin-top:2px}
    .actions{margin-top:14px;display:flex;gap:10px;flex-wrap:wrap}
    .btn{display:inline-block;padding:10px 14px;border-radius:10px;background:#7f56d9;color:#fff;font-weight:700;border:none;cursor:pointer}
    .panel-note{margin-top:10px;padding:10px;border:1px solid #eaecf0;border-radius:10px;background:#f9fafb;font-size:12px;color:#475467}
    .section-gap{margin-top:12px;padding-top:12px;border-top:1px dashed #eaecf0}
</style>
@endpush

@section('content')
@php
    $editingRole = \App\Modules\PettyCash\Support\PettyAccess::normalizeRole((string) old('role', $selectedUser?->role));
    $hasCustomPermissions = !empty($selectedExplicitPermissions ?? []);
    $checkedPermissions = old('permissions', $selectedEffectivePermissions ?? []);
    $createRole = \App\Modules\PettyCash\Support\PettyAccess::normalizeRole((string) old('create.role', 'customer_care'));
    if (!is_array($checkedPermissions)) {
        $checkedPermissions = [];
    }
    $checkedPermissionMap = [];
    foreach ($checkedPermissions as $permissionKey) {
        $checkedPermissionMap[(string) $permissionKey] = true;
    }
@endphp

<div class="wrap">
    <div class="grid">
        <div class="card">
            <h2 class="title">Users</h2>
            <div class="muted">Admin can manage every petty user role and permissions.</div>

            <div class="section-gap">
                <h3 style="margin:0 0 6px;font-size:14px;font-weight:900">Create New User</h3>
                <div class="muted">Create users like Finance or Customer Care here.</div>

                @if($errors->createUser->any())
                    <div class="panel-note" style="margin-top:10px;border-color:#fecdca;background:#fef3f2;color:#b42318">
                        @foreach($errors->createUser->all() as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('petty.settings.users.store') }}" style="margin-top:10px">
                    @csrf

                    <div class="form-grid">
                        <div class="field">
                            <label for="create_name">Full Name</label>
                            <input id="create_name" class="input" type="text" name="create[name]" value="{{ old('create.name') }}" required>
                        </div>
                        <div class="field">
                            <label for="create_email">Email</label>
                            <input id="create_email" class="input" type="email" name="create[email]" value="{{ old('create.email') }}" required>
                        </div>
                        @if($supportsPhoneNo)
                            <div class="field">
                                <label for="create_phone_no">Phone No (SMS)</label>
                                <input id="create_phone_no" class="input" type="text" name="create[phone_no]" value="{{ old('create.phone_no') }}" placeholder="07XXXXXXXX" inputmode="numeric" maxlength="10" pattern="07[0-9]{8}" required>
                                <div class="muted">Use 07XXXXXXXX format.</div>
                            </div>
                        @endif
                        <div class="field">
                            <label for="create_role">Role</label>
                            <select id="create_role" class="select" name="create[role]" required>
                                @foreach($roleOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($createRole === \App\Modules\PettyCash\Support\PettyAccess::normalizeRole((string)$value))>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Status</label>
                            <input type="hidden" name="create[is_active]" value="0">
                            <label class="perm-item" style="font-size:13px">
                                <input type="checkbox" name="create[is_active]" value="1" @checked((string) old('create.is_active', '1') === '1')>
                                <span>Allow login immediately</span>
                            </label>
                        </div>
                        <div class="field">
                            <label>Login SMS</label>
                            <input type="hidden" name="create[send_login_sms]" value="1">
                            <div class="muted">Login details are sent by SMS automatically after user creation.</div>
                        </div>
                        <div class="field">
                            <label for="create_password">Password</label>
                            <input id="create_password" class="input" type="password" name="create[password]" required>
                        </div>
                        <div class="field">
                            <label for="create_password_confirmation">Confirm Password</label>
                            <input id="create_password_confirmation" class="input" type="password" name="create[password_confirmation]" required>
                        </div>
                    </div>

                    <div class="actions">
                        <button class="btn" type="submit">Create User</button>
                    </div>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        @if($supportsPhoneNo)
                            <th>Phone</th>
                        @endif
                        <th>Status</th>
                        <th>Last Login</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($users as $u)
                        @php
                            $isSelected = (int) ($selectedUser?->id ?? 0) === (int) $u->id;
                            $displayRole = strtoupper(str_replace('_', ' ', \App\Modules\PettyCash\Support\PettyAccess::normalizeRole((string) $u->role)));
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $u->name }}</strong>
                                <div class="muted">{{ $u->email }}</div>
                            </td>
                            <td><span class="badge badge-role">{{ $displayRole }}</span></td>
                            @if($supportsPhoneNo)
                                <td>{{ $u->phone_no ?: '-' }}</td>
                            @endif
                            <td>
                                <span class="badge {{ $u->is_active ? 'badge-ok' : 'badge-off' }}">
                                    {{ $u->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td>{{ $u->last_login_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>
                                <a class="btn-link" href="{{ route('petty.settings.index', ['user' => $u->id]) }}">
                                    {{ $isSelected ? 'Selected' : 'Manage' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $supportsPhoneNo ? 6 : 5 }}" class="muted">No petty users found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            @if($selectedUser)
                <h2 class="title">Permissions</h2>
                <div class="muted">
                    User: <strong>{{ $selectedUser->name }}</strong> ({{ $selectedUser->email }})
                </div>
                @if($editingRole !== 'admin')
                    <div class="muted" style="margin-top:4px">
                        Permission mode:
                        <span class="badge badge-role">{{ $hasCustomPermissions ? 'Custom' : 'Role Defaults' }}</span>
                    </div>
                @endif

                @if($errors->updateUser->any())
                    <div class="panel-note" style="margin-top:12px;border-color:#fecdca;background:#fef3f2;color:#b42318">
                        @foreach($errors->updateUser->all() as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('petty.settings.users.update', $selectedUser->id) }}" style="margin-top:12px">
                    @csrf
                    @method('PUT')

                    <div class="form-grid">
                        <div class="field">
                            <label for="role">Role</label>
                            <select id="role" class="select" name="role" required>
                                @foreach($roleOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($editingRole === \App\Modules\PettyCash\Support\PettyAccess::normalizeRole((string)$value))>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label>Status</label>
                            <input type="hidden" name="is_active" value="0">
                            <label class="perm-item" style="font-size:13px">
                                <input type="checkbox" name="is_active" value="1" @checked((string) old('is_active', $selectedUser->is_active ? '1' : '0') === '1')>
                                <span>Allow this user to login</span>
                            </label>
                        </div>
                        @if($supportsPhoneNo)
                            <div class="field">
                                <label for="phone_no">Phone No (SMS)</label>
                                <input id="phone_no" class="input" type="text" name="phone_no" value="{{ old('phone_no', $selectedUser->phone_no) }}" placeholder="07XXXXXXXX">
                            </div>
                        @endif
                        <div class="field">
                            <label for="password">New Password (optional)</label>
                            <input id="password" class="input" type="password" name="password" placeholder="Leave blank to keep current password">
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Confirm New Password</label>
                            <input id="password_confirmation" class="input" type="password" name="password_confirmation">
                        </div>
                    </div>

                    <div id="permissionsPanel" style="margin-top:10px" @if($editingRole === 'admin') hidden @endif>
                        <div class="panel-note">
                            Select page-level actions this user can perform. If an action is unchecked, the route is blocked and hidden in key views.
                            View access is automatically enabled when you grant any non-view action on that same page.
                        </div>

                        <div class="perm-grid">
                            @foreach($permissionCatalog as $pageKey => $pageMeta)
                                @if($pageKey === 'settings')
                                    @continue
                                @endif

                                <div class="perm-card">
                                    <div class="perm-head">{{ $pageMeta['label'] }}</div>
                                    <div class="perm-list">
                                        @foreach((array) ($pageMeta['actions'] ?? []) as $actionKey => $actionLabel)
                                            @php $permissionKey = $pageKey . '.' . $actionKey; @endphp
                                            <label class="perm-item">
                                                <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}" @checked(isset($checkedPermissionMap[$permissionKey]))>
                                                <span>{{ $actionLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div id="adminNotice" class="panel-note" @if($editingRole !== 'admin') hidden @endif>
                        Admin role has full access to all pages/actions. Custom permissions are ignored for admins.
                    </div>

                    <div class="actions">
                        <button class="btn" type="submit">Save Role and Permissions</button>
                    </div>
                </form>
            @else
                <h2 class="title">Permissions</h2>
                <div class="muted">Select a user from the list to manage permissions.</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const roleSelect = document.getElementById('role');
    const permissionsPanel = document.getElementById('permissionsPanel');
    const adminNotice = document.getElementById('adminNotice');

    function syncRolePanels() {
        if (!roleSelect || !permissionsPanel || !adminNotice) {
            return;
        }

        const isAdmin = String(roleSelect.value || '').toLowerCase().replace(/[-\s]+/g, '_') === 'admin';
        permissionsPanel.hidden = isAdmin;
        adminNotice.hidden = !isAdmin;
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', syncRolePanels);
    }

    syncRolePanels();
})();
</script>
@endpush
