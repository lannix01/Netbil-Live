@extends('pettycash::layouts.app')

@section('title','Profile')

@push('styles')
<style>
    .content .container{max-width:100%}
    .wrap{max-width:none;width:100%;margin:0}
    .grid{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:12px}
    @media(max-width:980px){.grid{grid-template-columns:1fr}}
    .card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06)}
    .muted{color:#667085;font-size:12px}
    .title{margin:0;font-size:19px;font-weight:900}
    .profile-head{display:flex;align-items:center;gap:12px}
    .avatar{width:54px;height:54px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#eff8ff;border:1px solid #b2ddff;color:#175cd3;font-weight:900;font-size:18px}
    .stats{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .stat{padding:10px;border:1px solid #eaecf0;border-radius:10px;background:#fcfcfd}
    .stat .k{font-size:12px;color:#667085}
    .stat .v{font-size:20px;font-weight:900;color:#101828;line-height:1.1;margin-top:4px}
    .badge{display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;font-size:11px;font-weight:800}
    .badge-ok{background:#ecfdf3;color:#027a48;border:1px solid #abefc6}
    .badge-off{background:#fef3f2;color:#b42318;border:1px solid #fecdca}
    .badge-current{background:#eff8ff;color:#175cd3;border:1px solid #b2ddff}
    .btn-link{display:inline-block;padding:8px 10px;border:1px solid #d0d5dd;border-radius:10px;background:#fff;color:#344054;text-decoration:none;font-size:12px;font-weight:700}
    .btn-link[disabled]{opacity:.6;cursor:not-allowed}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid #eef2f6;font-size:13px;text-align:left;vertical-align:top}
    th{font-size:12px;color:#475467;white-space:nowrap}
    .section-title{margin:0;font-size:16px;font-weight:900}
    .table-wrap{overflow:auto;border:1px solid #eef2f6;border-radius:12px;margin-top:10px}
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="grid">
        <div class="card">
            @php
                $name = trim((string) ($currentUser->name ?? 'User'));
                $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));
            @endphp
            <div class="profile-head">
                <div class="avatar">{{ $initials }}</div>
                <div>
                    <h2 class="title">{{ $currentUser->name }}</h2>
                    <div class="muted">{{ $currentUser->email }}</div>
                    <div style="margin-top:6px">
                        <span class="badge {{ $currentUser->is_active ? 'badge-ok' : 'badge-off' }}">
                            {{ strtoupper((string) ($currentUser->role ?? 'user')) }}
                        </span>
                        @if($currentUser->last_login_at)
                            <span class="muted" style="margin-left:8px">Last login: {{ $currentUser->last_login_at->format('Y-m-d H:i') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="stats">
                @if($isAdmin)
                    <div class="stat">
                        <div class="k">Users</div>
                        <div class="v">{{ $stats['total_users'] }}</div>
                    </div>
                    <div class="stat">
                        <div class="k">Active Users</div>
                        <div class="v">{{ $stats['active_users'] }}</div>
                    </div>
                @endif
                <div class="stat">
                    <div class="k">Active Sessions</div>
                    <div class="v">{{ $stats['active_sessions'] }}</div>
                </div>
                <div class="stat">
                    <div class="k">My Sessions</div>
                    <div class="v">{{ $stats['my_sessions'] }}</div>
                </div>
            </div>
            <div class="muted" style="margin-top:10px">
                {{ $isAdmin ? 'Sessions are sourced from active PettyCash API tokens.' : 'Showing your login sessions only.' }}
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:12px">
        <h3 class="section-title">{{ $isAdmin ? 'Logged In Sessions' : 'My Login Sessions' }}</h3>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    @if($isAdmin)
                        <th>User</th>
                        <th>Role</th>
                    @endif
                    <th>IP Address</th>
                    <th>Device</th>
                    <th>Last Activity</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse($activeSessions as $s)
                    <tr>
                        @if($isAdmin)
                            <td>
                                <strong>{{ $s['name'] }}</strong>
                                <div class="muted">{{ $s['email'] }}</div>
                            </td>
                            <td>{{ strtoupper((string) $s['role']) }}</td>
                        @endif
                        <td>{{ $s['ip_address'] }}</td>
                        <td>
                            <div>{{ $s['device_name'] }}</div>
                            <div class="muted">{{ $s['user_agent'] }}</div>
                        </td>
                        <td>
                            @if($s['last_activity_at'])
                                {{ $s['last_activity_at']->format('Y-m-d H:i') }}
                                <div class="muted">{{ $s['last_activity_at']->diffForHumans() }}</div>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-ok">Active</span>
                            @if($s['expires_at'])
                                <div class="muted">Expires {{ $s['expires_at']->format('Y-m-d H:i') }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isAdmin ? 6 : 4 }}" class="muted">No active PettyCash sessions found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($isAdmin)
        <div class="card" style="margin-top:12px">
            <h3 class="section-title">Other Users in System</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        @if($supportsPhoneNo)
                            <th>Phone</th>
                            <th>Login SMS</th>
                        @endif
                        <th>Status</th>
                        <th>Last Login</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($otherUsers as $u)
                        <tr>
                            <td><strong>{{ $u->name }}</strong></td>
                            <td>{{ $u->email }}</td>
                            <td>{{ strtoupper((string) ($u->role ?? 'user')) }}</td>
                            @if($supportsPhoneNo)
                                <td>
                                    {{ $u->phone_no ?: '-' }}
                                    @if($supportsLoginSmsTracking && $u->login_sms_sent_at)
                                        <div class="muted">Last SMS: {{ $u->login_sms_sent_at->format('Y-m-d H:i') }}</div>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('petty.profile.users.send_login_sms', $u->id) }}" style="margin:0" onsubmit="return confirm('Send new login credentials by SMS to this user? This will reset their password.');">
                                        @csrf
                                        <button class="btn-link" type="submit" @disabled(empty($u->phone_no))>
                                            Send Login SMS
                                        </button>
                                    </form>
                                </td>
                            @endif
                            <td>
                                <span class="badge {{ $u->is_active ? 'badge-ok' : 'badge-off' }}">
                                    {{ $u->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td>{{ $u->last_login_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $supportsPhoneNo ? 7 : 5 }}" class="muted">No other users available.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
