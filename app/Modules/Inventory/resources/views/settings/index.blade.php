@extends('inventory::layout')

@php
    $users = $users ?? collect();
    $departments = $departments ?? collect();
    $selectedUser = $selectedUser ?? null;
    $roleOptions = $roleOptions ?? [];
    $permissionCatalog = $permissionCatalog ?? [];
    $selectedExplicitPermissions = $selectedExplicitPermissions ?? [];
    $selectedEffectivePermissions = $selectedEffectivePermissions ?? [];
    $permissionsSupported = $permissionsSupported ?? false;
    $supportsPhoneNo = $supportsPhoneNo ?? false;
    $supportsLoginTracking = $supportsLoginTracking ?? false;
    $supportsLoginSmsTracking = $supportsLoginSmsTracking ?? false;
    $activityLogs = $activityLogs ?? collect();
    $defaultPassword = $defaultPassword ?? '123456789';
    $tempPassword = session('inventory_temp_password');
    $tempPasswordUser = session('inventory_temp_user');
    $showTempPassword = $selectedUser && $tempPassword && (int) $selectedUser->id === (int) $tempPasswordUser;
    $checkedPermissions = old('permissions', $selectedEffectivePermissions);
    $checkedPermissionMap = collect($checkedPermissions)->filter()->flip();
    $editingRole = old('role', $selectedUser?->inventory_role ?? 'technician');
    $editingRole = \App\Modules\Inventory\Support\InventoryAccess::normalizeRole($editingRole);
    $createRole = \App\Modules\Inventory\Support\InventoryAccess::normalizeRole(old('create.role', 'technician'));
@endphp

@section('page-title', 'Access Settings')
@section('page-subtitle', 'Manage inventory users, roles, and what each account can access.')

@section('inventory-content')
<style>
    .access-shell{
        display:grid;
        grid-template-columns:minmax(280px, 340px) minmax(0, 1fr);
        gap:18px;
    }
    .access-list,
    .access-editor{
        padding:20px;
    }
    .access-head{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        margin-bottom:16px;
    }
    .access-title{
        margin:0;
        font-family:"Space Grotesk", sans-serif;
        font-size:24px;
        letter-spacing:-.04em;
    }
    .access-sub{
        color:var(--muted);
        font-size:13px;
    }
    .access-users{
        display:grid;
        gap:10px;
    }
    .access-user{
        display:grid;
        gap:8px;
        padding:14px 16px;
        border-radius:22px;
        border:1px solid var(--line);
        background:linear-gradient(180deg, #ffffff 0%, #f6fbf5 100%);
        transition:border-color .16s ease, transform .16s ease, box-shadow .16s ease;
    }
    .access-user:hover{
        transform:translateY(-1px);
        border-color:rgba(29, 155, 108, .24);
        box-shadow:0 14px 24px rgba(22, 52, 37, .08);
    }
    .access-user.active{
        border-color:rgba(18, 116, 81, .32);
        box-shadow:0 16px 28px rgba(18, 116, 81, .12);
        background:linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(227,248,238,.96) 100%);
    }
    .access-user-top{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
    }
    .access-user-name{
        font-weight:700;
        font-size:14px;
        line-height:1.25;
    }
    .access-user-meta{
        display:grid;
        gap:4px;
        color:var(--muted);
        font-size:12px;
        line-height:1.45;
        word-break:break-word;
    }
    .access-chiprow{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
    }
    .access-chip{
        display:inline-flex;
        align-items:center;
        gap:6px;
        min-height:28px;
        padding:0 10px;
        border-radius:999px;
        border:1px solid var(--line);
        background:#ffffff;
        font-size:11px;
        font-weight:700;
        letter-spacing:.08em;
        text-transform:uppercase;
        color:var(--text-soft);
    }
    .access-chip.ok{
        border-color:rgba(18, 116, 81, .18);
        background:rgba(227, 248, 238, .96);
        color:var(--brand-strong);
    }
    .access-chip.muted{
        color:var(--muted);
        background:rgba(242, 237, 224, .72);
    }
    .access-grid{
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:14px;
    }
    .access-field{
        display:grid;
        gap:7px;
    }
    .access-field.full{
        grid-column:1 / -1;
    }
    .access-label{
        font-size:11px;
        font-weight:700;
        letter-spacing:.16em;
        text-transform:uppercase;
        color:var(--muted);
    }
    .access-input,
    .access-select{
        width:100%;
        min-height:46px;
        padding:11px 14px;
        border-radius:16px;
        border:1px solid var(--line);
        background:#ffffff;
        color:var(--text);
    }
    .access-checks{
        display:flex;
        flex-wrap:wrap;
        gap:10px 16px;
        margin-top:4px;
    }
    .access-check{
        display:inline-flex;
        align-items:center;
        gap:8px;
        font-size:13px;
        color:var(--text-soft);
    }
    .access-divider{
        height:1px;
        margin:18px 0;
        background:var(--line);
    }
    .access-permission-grid{
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:14px;
        margin-top:14px;
    }
    .access-permission-card{
        padding:16px;
        border-radius:22px;
        border:1px solid var(--line);
        background:linear-gradient(180deg, #ffffff 0%, #f8fbf6 100%);
    }
    .access-permission-title{
        margin:0;
        font-family:"Space Grotesk", sans-serif;
        font-size:18px;
        letter-spacing:-.03em;
    }
    .access-permission-actions{
        display:grid;
        gap:10px;
        margin-top:14px;
    }
    .access-permission-check{
        display:grid;
        grid-template-columns:auto 1fr;
        gap:10px;
        align-items:flex-start;
        font-size:13px;
        color:var(--text-soft);
    }
    .access-permission-check strong{
        display:block;
        color:var(--text);
        margin-bottom:2px;
    }
    .access-note{
        padding:14px 16px;
        border-radius:18px;
        border:1px solid var(--line);
        background:rgba(255,255,255,.78);
        color:var(--muted);
        font-size:13px;
        line-height:1.55;
    }
    .access-actions{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        justify-content:flex-end;
        margin-top:18px;
    }
    .access-head-actions{
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
        justify-content:flex-end;
    }
    .access-session{
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
        gap:12px;
        margin:12px 0 4px;
    }
    .access-session-card{
        padding:12px 14px;
        border-radius:18px;
        border:1px solid var(--line);
        background:#ffffff;
    }
    .access-session-label{
        font-size:11px;
        font-weight:700;
        letter-spacing:.16em;
        text-transform:uppercase;
        color:var(--muted);
    }
    .access-session-value{
        font-size:13px;
        font-weight:600;
        color:var(--text);
        margin-top:6px;
        word-break:break-word;
    }
    .access-quick-actions{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        margin-top:12px;
    }
    .access-password-row{
        display:flex;
        align-items:center;
        gap:8px;
    }
    .access-password-row .access-input{
        flex:1;
    }
    .access-activity-table{
        margin-top:12px;
        border:1px solid var(--line);
        border-radius:18px;
        overflow:hidden;
        background:#ffffff;
    }
    .access-activity-table table{
        width:100%;
        border-collapse:collapse;
        font-size:12px;
    }
    .access-activity-table th,
    .access-activity-table td{
        padding:10px 12px;
        border-bottom:1px solid var(--line);
        text-align:left;
        vertical-align:top;
    }
    .access-activity-table th{
        font-size:11px;
        text-transform:uppercase;
        letter-spacing:.12em;
        color:var(--muted);
        background:rgba(248, 249, 250, .8);
    }
    .access-activity-table tr:last-child td{
        border-bottom:none;
    }
    .access-modal{
        position:fixed;
        inset:0;
        z-index:1040;
        display:none;
        align-items:center;
        justify-content:center;
        padding:16px;
    }
    body.access-modal-open{
        overflow:hidden;
    }
    .access-modal.show{
        display:flex;
    }
    .access-modal-backdrop{
        position:absolute;
        inset:0;
        background:rgba(10, 20, 16, .48);
    }
    .access-modal-panel{
        position:relative;
        width:min(720px, 92vw);
        max-height:90vh;
        overflow:auto;
        background:#ffffff;
        border-radius:22px;
        border:1px solid var(--line);
        padding:22px;
        box-shadow:0 24px 48px rgba(15, 23, 20, .2);
    }
    .access-modal-head{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        margin-bottom:16px;
    }
    .access-modal-title{
        margin:0;
        font-family:\"Space Grotesk\", sans-serif;
        font-size:22px;
        letter-spacing:-.03em;
    }
    .access-modal-close{
        border:none;
        background:transparent;
        font-size:20px;
        line-height:1;
        color:var(--muted);
    }
    @media (max-width: 1199.98px){
        .access-grid,
        .access-permission-grid,
        .access-session{
            grid-template-columns:1fr;
        }
    }
    @media (max-width: 991.98px){
        .access-shell{
            grid-template-columns:1fr;
        }
    }
</style>

<div class="access-shell">
    <section class="inv-card access-list">
        <div class="access-head">
            <div>
                <h2 class="access-title">Inventory Users</h2>
                <div class="access-sub">Select a user to edit role and access.</div>
            </div>
            <div class="access-head-actions">
                <span class="access-chip">{{ $users->count() }} users</span>
                <button class="btn btn-dark btn-sm" type="button" onclick="openCreateUserModal()">Add User</button>
            </div>
        </div>

        <div class="access-users">
            @forelse($users as $user)
                @php
                    $isSelected = $selectedUser && (int) $selectedUser->id === (int) $user->id;
                    $userRole = \App\Modules\Inventory\Support\InventoryAccess::roleLabel($user->inventory_role);
                @endphp
                <a class="access-user {{ $isSelected ? 'active' : '' }}" href="{{ route('inventory.settings.index', ['user' => $user->id]) }}" data-inv-loading>
                    <div class="access-user-top">
                        <div class="access-user-name">{{ $user->name }}</div>
                        <span class="access-chip {{ $user->inventory_enabled ? 'ok' : 'muted' }}">{{ $user->inventory_enabled ? 'Enabled' : 'Disabled' }}</span>
                    </div>
                    <div class="access-user-meta">
                        <div>{{ $user->email }}</div>
                        @if($supportsPhoneNo)
                            <div>Phone: {{ $user->phone_no ?: 'No phone' }}</div>
                        @endif
                        @if($supportsLoginTracking)
                            <div>Last login: {{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</div>
                        @endif
                    </div>
                    <div class="access-chiprow">
                        <span class="access-chip">{{ $userRole }}</span>
                        @if($user->department?->name)
                            <span class="access-chip muted">{{ $user->department->name }}</span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="access-note">No inventory users found.</div>
            @endforelse
        </div>
    </section>

    <section class="inv-card access-editor">
        @if($selectedUser)
            <div class="access-head">
                <div>
                    <h2 class="access-title">{{ $selectedUser->name }}</h2>
                    <div class="access-sub">Update this user’s role, login state, password, and access map.</div>
                </div>
                <div class="access-chiprow">
                    <span class="access-chip">{{ \App\Modules\Inventory\Support\InventoryAccess::roleLabel($selectedUser->inventory_role) }}</span>
                    <span class="access-chip {{ $selectedUser->inventory_enabled ? 'ok' : 'muted' }}">{{ $selectedUser->inventory_enabled ? 'Enabled' : 'Disabled' }}</span>
                </div>
            </div>

            <div class="access-session">
                <div class="access-session-card">
                    <div class="access-session-label">Last Login</div>
                    <div class="access-session-value">
                        {{ $supportsLoginTracking ? ($selectedUser->last_login_at?->format('Y-m-d H:i') ?? 'Never') : 'Unavailable' }}
                    </div>
                </div>
                <div class="access-session-card">
                    <div class="access-session-label">Last IP</div>
                    <div class="access-session-value">
                        {{ $supportsLoginTracking ? ($selectedUser->last_login_ip ?: '-') : '-' }}
                    </div>
                </div>
                <div class="access-session-card">
                    <div class="access-session-label">Device</div>
                    <div class="access-session-value">
                        {{ $supportsLoginTracking && $selectedUser->last_login_user_agent ? \Illuminate\Support\Str::limit($selectedUser->last_login_user_agent, 60) : '-' }}
                    </div>
                </div>
                <div class="access-session-card">
                    <div class="access-session-label">Last SMS</div>
                    <div class="access-session-value">
                        {{ $supportsLoginSmsTracking ? ($selectedUser->login_sms_sent_at?->format('Y-m-d H:i') ?? 'Never') : '-' }}
                    </div>
                </div>
            </div>

            <div class="access-quick-actions">
                <form method="POST" action="{{ route('inventory.settings.users.send_login_sms', $selectedUser) }}" style="margin:0" onsubmit="return confirm('Send login details by SMS? This will reset the password to the default.');">
                    @csrf
                    <button class="btn btn-outline-dark" type="submit" {{ ($supportsPhoneNo && empty($selectedUser->phone_no)) ? 'disabled' : '' }}>Send Login SMS</button>
                </form>
                <form method="POST" action="{{ route('inventory.settings.users.reset_login', $selectedUser) }}" style="margin:0" onsubmit="return confirm('Reset login to the default password?');">
                    @csrf
                    <button class="btn btn-outline-secondary" type="submit">Reset Login</button>
                </form>
            </div>

            @if($supportsPhoneNo && empty($selectedUser->phone_no))
                <div class="access-note" style="margin-top:10px;">Add a phone number to enable SMS login details.</div>
            @endif

            @if($showTempPassword)
                <div class="access-note" style="margin-top:12px;">
                    Temporary password for {{ $selectedUser->name }}: <strong>{{ $tempPassword }}</strong>
                </div>
            @else
                <div class="access-note" style="margin-top:12px;">
                    Default reset password: <strong>{{ $defaultPassword }}</strong>. Use “Reset Login” or “Send Login SMS” to set it.
                </div>
            @endif

            <form method="POST" action="{{ route('inventory.settings.users.update', $selectedUser) }}">
                @csrf
                @method('PUT')

                <div class="access-grid">
                    <div class="access-field">
                        <label class="access-label">Name</label>
                        <input class="access-input" type="text" name="name" value="{{ old('name', $selectedUser->name) }}" required>
                    </div>

                    <div class="access-field">
                        <label class="access-label">Email</label>
                        <input class="access-input" type="email" name="email" value="{{ old('email', $selectedUser->email) }}" required>
                    </div>

                    @if($supportsPhoneNo)
                        <div class="access-field">
                            <label class="access-label">Phone (SMS)</label>
                            <input class="access-input" type="text" name="phone_no" value="{{ old('phone_no', $selectedUser->phone_no) }}" placeholder="07XXXXXXXX">
                        </div>
                    @endif

                    <div class="access-field">
                        <label class="access-label">Role</label>
                        <select class="access-select" name="role" id="inventoryRoleSelect">
                            @foreach($roleOptions as $roleKey => $roleLabel)
                                <option value="{{ $roleKey }}" @selected($editingRole === $roleKey)>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="access-field">
                        <label class="access-label">Department</label>
                        <select class="access-select" name="department_id">
                            <option value="">No department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('department_id', $selectedUser->department_id) === (string) $department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="access-field">
                        <label class="access-label">New Password</label>
                        <input class="access-input" type="password" name="password" placeholder="Leave blank to keep current password">
                    </div>

                    <div class="access-field">
                        <label class="access-label">Confirm Password</label>
                        <input class="access-input" type="password" name="password_confirmation" placeholder="Repeat new password">
                    </div>

                    <div class="access-field full">
                        <label class="access-label">Account State</label>
                        <div class="access-checks">
                            <label class="access-check">
                                <input type="checkbox" name="inventory_enabled" value="1" @checked(old('inventory_enabled', $selectedUser->inventory_enabled))>
                                <span>Inventory login enabled</span>
                            </label>
                            <label class="access-check">
                                <input type="checkbox" name="inventory_force_password_change" value="1" @checked(old('inventory_force_password_change', $selectedUser->inventory_force_password_change))>
                                <span>Force password change on next login</span>
                            </label>
                        </div>
                    </div>
                </div>

                @if($errors->updateUser->any())
                    <div class="access-divider"></div>
                    <div class="inv-alert">
                        <div class="inv-alert-ico"><i class="bi bi-exclamation-circle"></i></div>
                        <div>
                            <strong>Update failed</strong>
                            <div>{{ $errors->updateUser->first() }}</div>
                        </div>
                    </div>
                @endif

                <div class="access-divider"></div>

                @if($permissionsSupported)
                    <div id="inventoryPermissionsPanel" @if($editingRole === 'admin') hidden @endif>
                        <div class="access-head" style="margin-bottom:0;">
                            <div>
                                <h3 class="access-title" style="font-size:22px;">Permissions</h3>
                                <div class="access-sub">Choose what this user can view and what they can change.</div>
                            </div>
                        </div>

                        <div class="access-permission-grid">
                            @foreach($permissionCatalog as $pageKey => $pageMeta)
                                <div class="access-permission-card">
                                    <h4 class="access-permission-title">{{ $pageMeta['label'] }}</h4>
                                    <div class="access-permission-actions">
                                        @foreach(($pageMeta['actions'] ?? []) as $actionKey => $actionLabel)
                                            @php $permissionKey = $pageKey . '.' . $actionKey; @endphp
                                            <label class="access-permission-check">
                                                <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}" @checked(isset($checkedPermissionMap[$permissionKey]))>
                                                <span>
                                                    <strong>{{ ucfirst(str_replace('_', ' ', $actionKey)) }}</strong>
                                                    <span>{{ $actionLabel }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div id="inventoryAdminNotice" class="access-note" @if($editingRole !== 'admin') hidden @endif>
                        Admin role keeps full access to the entire inventory module. Custom permission checkboxes are ignored for admins.
                    </div>
                @else
                    <div class="access-note">
                        This database does not yet have the <code>inventory_permissions</code> column. Role changes still work, but custom per-page access is unavailable until the inventory permissions migration is applied.
                    </div>
                @endif

                <div class="access-actions">
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.settings.index', ['user' => $selectedUser->id]) }}" data-inv-loading>Reset Form</a>
                    <button class="btn btn-dark" type="submit">Save Access</button>
                </div>
            </form>

            <div class="access-divider"></div>
            <div class="access-head" style="margin-bottom:0;">
                <div>
                    <h3 class="access-title" style="font-size:22px;">User Activity</h3>
                    <div class="access-sub">Recent logins, actions, and viewed pages.</div>
                </div>
            </div>

            <div class="access-activity-table">
                <table>
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Action</th>
                            <th>Where</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activityLogs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td>{{ strtoupper(str_replace('_', ' ', $log->action)) }}</td>
                                <td>
                                    <div>{{ $log->route_name ?: '-' }}</div>
                                    @if($log->url)
                                        <div class="access-sub">{{ \Illuminate\Support\Str::limit($log->url, 60) }}</div>
                                    @endif
                                </td>
                                <td>{{ $log->ip_address ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="access-sub">No activity recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="access-note">Select a user from the left, or add a new one to manage access.</div>
        @endif
    </section>
</div>

<div id="createUserModal" class="access-modal" aria-hidden="true">
    <div class="access-modal-backdrop" onclick="closeCreateUserModal()"></div>
    <div class="access-modal-panel" role="dialog" aria-modal="true" aria-labelledby="createUserTitle">
        <div class="access-modal-head">
            <div>
                <h3 id="createUserTitle" class="access-modal-title">Create Inventory User</h3>
                <div class="access-sub">Add a new user and send login details.</div>
            </div>
            <button class="access-modal-close" type="button" onclick="closeCreateUserModal()" aria-label="Close">&times;</button>
        </div>

        @if($errors->createUser->any())
            <div class="inv-alert" style="margin-bottom:14px;">
                <div class="inv-alert-ico"><i class="bi bi-exclamation-circle"></i></div>
                <div>
                    <strong>Create failed</strong>
                    <div>{{ $errors->createUser->first() }}</div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('inventory.settings.users.store') }}">
            @csrf

            <div class="access-grid">
                <div class="access-field">
                    <label class="access-label">Name</label>
                    <input class="access-input" type="text" name="create[name]" value="{{ old('create.name') }}" required>
                </div>

                <div class="access-field">
                    <label class="access-label">Email</label>
                    <input class="access-input" type="email" name="create[email]" value="{{ old('create.email') }}" required>
                </div>

                @if($supportsPhoneNo)
                    <div class="access-field">
                        <label class="access-label">Phone (SMS)</label>
                        <input class="access-input" type="text" name="create[phone_no]" value="{{ old('create.phone_no') }}" placeholder="07XXXXXXXX">
                    </div>
                @endif

                <div class="access-field">
                    <label class="access-label">Role</label>
                    <select class="access-select" name="create[role]" required>
                        @foreach($roleOptions as $roleKey => $roleLabel)
                            <option value="{{ $roleKey }}" @selected($createRole === $roleKey)>{{ $roleLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="access-field">
                    <label class="access-label">Department</label>
                    <select class="access-select" name="create[department_id]">
                        <option value="">No department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('create.department_id') === (string) $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="access-field">
                    <label class="access-label">Password</label>
                    <input class="access-input" type="password" name="create[password]" placeholder="Leave blank for default">
                </div>

                <div class="access-field">
                    <label class="access-label">Confirm Password</label>
                    <input class="access-input" type="password" name="create[password_confirmation]" placeholder="Repeat password">
                </div>

                <div class="access-field full">
                    <label class="access-label">Account Options</label>
                    <div class="access-checks">
                        <input type="hidden" name="create[inventory_enabled]" value="0">
                        <label class="access-check">
                            <input type="checkbox" name="create[inventory_enabled]" value="1" @checked((string) old('create.inventory_enabled', '1') === '1')>
                            <span>Enable inventory login</span>
                        </label>
                        <input type="hidden" name="create[force_password_change]" value="0">
                        <label class="access-check">
                            <input type="checkbox" name="create[force_password_change]" value="1" @checked((string) old('create.force_password_change', '1') === '1')>
                            <span>Force password change on first login</span>
                        </label>
                        <input type="hidden" name="create[send_login_sms]" value="0">
                        <label class="access-check">
                            <input type="checkbox" name="create[send_login_sms]" value="1" @checked((string) old('create.send_login_sms', '1') === '1')>
                            <span>Send login SMS</span>
                        </label>
                    </div>
                    <div class="access-sub" style="margin-top:6px;">Default reset password: {{ $defaultPassword }}</div>
                </div>
            </div>

            <div class="access-actions">
                <button class="btn btn-outline-secondary" type="button" onclick="closeCreateUserModal()">Cancel</button>
                <button class="btn btn-dark" type="submit">Create User</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const roleSelect = document.getElementById('inventoryRoleSelect');
        const permissionsPanel = document.getElementById('inventoryPermissionsPanel');
        const adminNotice = document.getElementById('inventoryAdminNotice');

        function syncRolePanels() {
            if (!roleSelect || !permissionsPanel || !adminNotice) {
                return;
            }

            const isAdmin = roleSelect.value === 'admin';
            permissionsPanel.hidden = isAdmin;
            adminNotice.hidden = !isAdmin;
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', syncRolePanels);
            syncRolePanels();
        }
    })();

    const createModal = document.getElementById('createUserModal');

    function openCreateUserModal() {
        if (!createModal) return;
        createModal.classList.add('show');
        createModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('access-modal-open');
    }

    function closeCreateUserModal() {
        if (!createModal) return;
        createModal.classList.remove('show');
        createModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('access-modal-open');
    }

    window.openCreateUserModal = openCreateUserModal;
    window.closeCreateUserModal = closeCreateUserModal;

    const shouldOpenCreateModal = @json($errors->createUser->any() || old('create.name') || session('open_create_user_modal'));
    if (shouldOpenCreateModal) {
        openCreateUserModal();
    }
</script>
@endsection
